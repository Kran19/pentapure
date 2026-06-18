<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductionLog;
use App\Models\ProductionLogInput;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SemiController extends Controller
{
    private function authUser(): array { return session('auth_user'); }

    private function getLiveStock(string $stage): array
    {
        $userRole = $this->authUser()['role'];
        return DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.stage', $stage)
            ->where(function($q) use ($userRole) {
                if ($userRole === 'ADMIN') return $q;
                $q->whereNull('products.allowed_roles')
                  ->orWhereJsonContains('products.allowed_roles', $userRole);
            })
            ->groupBy('stocks.product_id', 'stocks.grade', 'products.name', 'products.unit', 'products.id')
            ->selectRaw("
                stocks.product_id as productId,
                products.id as id,
                products.name,
                products.unit,
                stocks.grade,
                SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->get()->map(fn($r) => (array) $r)->toArray();
    }

    public function home()
    {
        $semiStock    = $this->getLiveStock('SEMI');
        $user = $this->authUser();
        $ledger = Stock::with('product')
            ->where(function($q) use ($user) {
                $q->where('stage', 'SEMI')
                  ->orWhere('user_id', $user['id']);
            })
            ->orderByDesc('created_at')
            ->get()->map(fn($s) => [
                'productId'   => $s->product_id,
                'productName' => $s->product?->name,
                'grade'       => $s->grade,
                'quantity'    => $s->transaction_type === 'IN' ? $s->quantity : -$s->quantity,
                'date'        => $s->created_at->toISOString(),
                'unit'        => $s->product?->unit ?? 'kg',
            ]);

        $myPOs = \App\Models\PurchaseOrder::with('product')
            ->where('user_id', $user['id'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($po) => [
                'id'           => $po->id,
                'userId'       => $po->user_id,
                'materialId'   => $po->product_id,
                'materialName' => $po->product?->name,
                'quantity'     => $po->quantity,
                'note'         => $po->note,
                'status'       => $po->status,
                'date'         => $po->created_at->toISOString(),
            ]);

        $pageData = [
            'semiStock'      => $semiStock,
            'rawStock'       => $this->getLiveStock('RAW'),
            'semiLedger'     => $ledger,
            'purchaseOrders' => $myPOs,
            'rawMaterialsList' => Product::raw()->active()->visibleTo($user['role'])->get(['id', 'name', 'unit']),
            'products'       => Product::with('grades')->active()->visibleTo($user['role'])->get()->map(fn($p)=>[
                'id'=>$p->id,'name'=>$p->name,'type'=>$p->type,'unit'=>$p->unit,
                'gradeNames'=>$p->grades->pluck('name')
            ]),
        ];
        return view('semi.home', compact('pageData'));
    }

    public function action()
    {
        $rawStock = $this->getLiveStock('RAW');
        $grades   = \App\Models\Grade::where('is_active', true)->pluck('name')->toArray();

        $pageData = [
            'rawStock' => $rawStock,
            'grades'   => $grades,
            'products' => Product::with('grades')->active()->visibleTo($this->authUser()['role'])->get()->map(fn($p)=>[
                'id'=>$p->id,'name'=>$p->name,'type'=>$p->type,'unit'=>$p->unit,
                'gradeNames'=>$p->grades->pluck('name')
            ]),
        ];
        return view('semi.action', compact('pageData'));
    }

    // ── PO ─────────────────────────────────────────────────────────────────
    public function po()
    {
        $user = $this->authUser();
        $pos = PurchaseOrder::with('product')
            ->where('user_id', $user['id'])
            ->orderByDesc('created_at')
            ->paginate(15);
            
        $pageData = ['purchaseOrders' => $pos];
        return view('raw.po', compact('pageData')); // Share view with raw
    }

    // POST: Log semi production — deduct raw inputs, add semi output
    public function storeProduction(Request $request)
    {
        $request->validate([
            'output_product_id' => 'required|exists:products,id',
            'output_grade'      => 'required|string',
            'output_qty'        => 'required|numeric|min:0.001',
            'location'          => 'nullable|string',
            'inputs'            => 'required|array|min:1',
            'inputs.*.product_id' => 'required|exists:products,id',
            'inputs.*.grade'      => 'required|string',
            'inputs.*.quantity'   => 'required|numeric|min:0.001',
        ]);

        $user = $this->authUser();

        // Security check: Ensure products are visible to this user role
        $visibleProductIds = Product::visibleTo($user['role'])->pluck('id')->toArray();
        if (!in_array($request->output_product_id, $visibleProductIds)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized product access (Output).'], 403);
        }

        foreach ($request->inputs as $inp) {
            if (!in_array($inp['product_id'], $visibleProductIds)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized product access (Input).'], 403);
            }
        }

        // Validate stock availability for all inputs BEFORE saving anything
        foreach ($request->inputs as $inp) {
            $available = DB::table('stocks')
                ->where('product_id', $inp['product_id'])
                ->where('stage', 'RAW')
                ->where('grade', $inp['grade'])
                ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")
                ->value('net') ?? 0;

            if ($inp['quantity'] > $available) {
                $pName = Product::find($inp['product_id'])?->name;
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient raw stock for {$pName} ({$inp['grade']}). Available: {$available} kg"
                ], 422);
            }
        }

        $locationName = $request->location ?: 'Main Warehouse';
        $locationId = \App\Models\Location::firstOrCreate(['name' => $locationName])->id;

        DB::transaction(function () use ($request, $user, $locationId) {
            // 1. Create production log
            $log = ProductionLog::create([
                'user_id'           => $user['id'],
                'type'              => 'SEMI',
                'output_product_id' => $request->output_product_id,
                'output_grade'      => $request->output_grade,
                'output_qty'        => $request->output_qty,
            ]);

            // 2. Deduct each raw input from RAW stock
            foreach ($request->inputs as $inp) {
                ProductionLogInput::create([
                    'production_log_id' => $log->id,
                    'input_product_id'  => $inp['product_id'],
                    'input_grade'       => $inp['grade'],
                    'quantity'          => $inp['quantity'],
                ]);

                Stock::deductStock(
                    $inp['product_id'],
                    'RAW',
                    $inp['grade'],
                    $inp['quantity'],
                    $user['id'],
                    "Consumed in SEMI production log #{$log->id}"
                );
            }

            // 3. Add output to SEMI stock
            Stock::create([
                'product_id'       => $request->output_product_id,
                'user_id'          => $user['id'],
                'stage'            => 'SEMI',
                'grade'            => $request->output_grade,
                'location_id'      => $locationId,
                'quantity'         => $request->output_qty,
                'transaction_type' => 'IN',
                'notes'            => "Produced: Production log #{$log->id}",
            ]);
        });

        return response()->json(['success' => true, 'message' => 'SEMI production logged successfully!']);
    }

    public function history()
    {
        $logs = ProductionLog::with(['outputProduct', 'inputs.inputProduct'])
            ->where('type', 'SEMI')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($l) => [
                'id'              => $l->id,
                'type'            => 'SEMI',
                'outputProductId' => $l->output_product_id,
                'outputName'      => $l->outputProduct?->name,
                'outputGrade'     => $l->output_grade,
                'outputQty'       => $l->output_qty,
                'date'            => $l->created_at->toISOString(),
                'consumedInputs'  => $l->inputs->map(fn($i) => [
                    'productId' => $i->input_product_id,
                    'name'      => $i->inputProduct?->name,
                    'grade'     => $i->input_grade,
                    'quantity'  => $i->quantity,
                ]),
            ]);

        $purchaseOrders = PurchaseOrder::with('product')
            ->where('user_id', $this->authUser()['id'])
            ->orderByDesc('created_at')
            ->get();

        $pageData = [
            'productionLogs' => $logs,
            'purchaseOrders' => $purchaseOrders
        ];
        return view('semi.history', compact('pageData'));
    }

    public function profile()
    {
        return view('semi.profile');
    }
}
