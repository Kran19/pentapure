<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RawController extends Controller
{
    private function authUser(): array
    {
        return session('auth_user');
    }

    // ── HOME: Stock / Inward / Outward tabs ────────────────────────────────
    public function home()
    {
        $user = $this->authUser();

        // Aggregated live stock (NET = SUM IN - SUM OUT) per product+grade
        $rawStock = $this->getLiveStock('RAW');

        // All individual stock ledger entries for inward/outward tabs
        $ledger = Stock::with('product')
            ->where('stage', 'RAW')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($s) => [
                'id'               => $s->id,
                'productId'        => $s->product_id,
                'productName'      => $s->product?->name ?? 'Unknown',
                'grade'            => $s->grade,
                'quantity'         => $s->transaction_type === 'IN' ? $s->quantity : -$s->quantity,
                'transaction_type' => $s->transaction_type,
                'date'             => $s->created_at->toISOString(),
                'unit'             => $s->product?->unit ?? 'kg',
            ]);

        $rawMaterials = Product::raw()->active()->visibleTo($user['role'])->get(['id', 'name', 'unit', 'image_url']);

        $myPOs = PurchaseOrder::with('product')
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
            'rawMaterialsList' => $rawMaterials,
            'rawStock'         => $rawStock,
            'rawLedger'        => $ledger,
            'purchaseOrders'   => $myPOs,
            'products'         => [],
        ];

        return view('raw.home', compact('pageData'));
    }

    // ── ACTION: Inward form ────────────────────────────────────────────────
    public function action()
    {
        $rawMaterials = Product::raw()->active()->visibleTo($this->authUser()['role'])->get(['id', 'name', 'unit', 'image_url']);
        $pageData = ['rawMaterialsList' => $rawMaterials];
        return view('raw.action', compact('pageData'));
    }

    // ── POST: Save Inward Stock ────────────────────────────────────────────
    public function storeInward(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|numeric|min:0.001',
            'grade'      => 'nullable|string|max:50',
        ]);

        $user = $this->authUser();

        // Security check
        if (!Product::visibleTo($user['role'])->where('id', $request->product_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized product access.'], 403);
        }

        Stock::create([
            'product_id'       => $request->product_id,
            'user_id'          => $user['id'],
            'stage'            => 'RAW',
            'grade'            => $request->grade ?? 'NONE',
            'quantity'         => $request->quantity,
            'transaction_type' => 'IN',
            'notes'            => $request->notes,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Raw material added to stock!']);
        }

        return redirect('/raw/home')->with('success', 'Raw material added to stock!');
    }

    // ── POST: Purchase Order Request ───────────────────────────────────────
    public function storePO(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|numeric|min:0.001',
        ]);

        $user = $this->authUser();

        // Security check
        if (!Product::visibleTo($user['role'])->where('id', $request->product_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized product access.'], 403);
        }

        PurchaseOrder::create([
            'user_id'    => $user['id'],
            'product_id' => $request->product_id,
            'quantity'   => $request->quantity,
            'note'       => $request->note,
            'status'     => 'PENDING',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Purchase request sent to Admin!']);
        }

        return redirect('/raw/home')->with('success', 'Purchase request sent!');
    }

    // ── HISTORY ────────────────────────────────────────────────────────────
    public function history()
    {
        $ledger = Stock::with('product')
            ->where('stage', 'RAW')
            ->where('transaction_type', 'IN')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($s) => [
                'id'          => $s->id,
                'productId'   => $s->product_id,
                'productName' => $s->product?->name,
                'image'       => $s->product?->image_url,
                'quantity'    => $s->quantity,
                'unit'        => $s->product?->unit ?? 'kg',
                'grade'       => $s->grade,
                'date'        => $s->created_at->toISOString(),
            ]);

        $pageData = ['rawStockHistory' => $ledger];
        return view('raw.history', compact('pageData'));
    }

    // ── PROFILE ────────────────────────────────────────────────────────────
    public function profile()
    {
        return view('raw.profile');
    }

    // ── HELPER: Compute live net stock per product+grade ───────────────────
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
            ->groupBy('stocks.product_id', 'stocks.grade', 'products.name', 'products.unit')
            ->selectRaw("
                stocks.product_id as productId,
                products.name,
                products.unit,
                stocks.grade,
                SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }
}
