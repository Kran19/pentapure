<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transporter;
use App\Models\DispatchLog;
use App\Models\DispatchLogItem;
use App\Models\DispatchItemLocation;
use App\Models\Stock;
use App\Http\Controllers\DispatchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SalesController extends Controller
{
    private function authUser(): array { return session('auth_user'); }

    private function ensureDueDateColumnExists(): bool
    {
        static $hasColumn = null;
        if ($hasColumn !== null) {
            return $hasColumn;
        }

        if (Schema::hasColumn('orders', 'due_date')) {
            $hasColumn = true;
            return true;
        }

        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->date('due_date')->nullable()->after('date');
            });
            $hasColumn = true;
            return true;
        } catch (\Throwable $e) {
            $hasColumn = false;
            return false;
        }
    }

    public static function ensureVehicleNoColumnExists(): bool
    {
        if (Schema::hasColumn('dispatch_logs', 'vehicle_no')) {
            return true;
        }

        try {
            Schema::table('dispatch_logs', function (Blueprint $table) {
                $table->string('vehicle_no')->nullable()->after('driver_no');
            });
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function isDispatchNoteVehicle(?string $vehicles): bool
    {
        if (empty($vehicles)) {
            return false;
        }
        $upper = strtoupper(trim($vehicles));
        return str_contains($upper, 'ACTUAL DISPATCH') 
            || str_contains($upper, 'DISPATCH')
            || str_contains($upper, 'DUE TO') 
            || str_contains($upper, 'APP.PROBLEM')
            || str_contains($upper, 'APP')
            || str_contains($upper, 'PROBLEM')
            || str_contains($upper, 'DELAY')
            || str_contains($upper, 'PENDING')
            || str_contains($upper, 'REASON')
            || str_contains($upper, 'REMARK')
            || str_contains($upper, 'NOTE')
            || (bool) preg_match('/\d{2}[\/\-]\d{2}[\/\-]\d{4}/', $upper)
            || strlen($upper) > 35;
    }

    public static function cleanContaminatedTransporterVehicles(): void
    {
        try {
            Transporter::where('vehicles', 'LIKE', '%ACTUAL DISPATCH%')
                ->orWhere('vehicles', 'LIKE', '%DISPATCH%')
                ->orWhere('vehicles', 'LIKE', '%DUE TO%')
                ->orWhere('vehicles', 'LIKE', '%APP.PROBLEM%')
                ->orWhere('vehicles', 'LIKE', '%APP%')
                ->orWhere('vehicles', 'LIKE', '%PROBLEM%')
                ->orWhere('vehicles', 'LIKE', '%DELAY%')
                ->orWhere('vehicles', 'LIKE', '%PENDING%')
                ->orWhere('vehicles', 'LIKE', '%REASON%')
                ->orWhere('vehicles', 'LIKE', '%REMARK%')
                ->orWhere('vehicles', 'LIKE', '%NOTE%')
                ->orWhereRaw('LENGTH(vehicles) > 35')
                ->update(['vehicles' => null]);

            Transporter::where('name', 'LIKE', '%JAI TRANSPORT%')
                ->where(function ($q) {
                    $q->where('vehicles', 'LIKE', '%ACTUAL DISPATCH%')
                      ->orWhere('vehicles', 'LIKE', '%DISPATCH%')
                      ->orWhere('vehicles', 'LIKE', '%PROBLEM%');
                })
                ->update(['vehicles' => null]);
        } catch (\Throwable $e) {}
    }

    public static function getTransportCompanies()
    {
        self::ensureVehicleNoColumnExists();
        self::cleanContaminatedTransporterVehicles();

        return Transporter::withCount(['orders', 'dispatchLogs'])->orderByDesc('id')->get()->map(function ($t) {
            $vehicles = $t->vehicles;
            if ($vehicles && self::isDispatchNoteVehicle($vehicles)) {
                $vehicles = null;
            }

            return [
                'id'                  => $t->id,
                'name'                => strtoupper($t->name ?? ''),
                'gst'                 => $t->gst,
                'contact'             => $t->contact ?: '—',
                'vehicles'            => $vehicles ?: '—',
                'date'                => $t->created_at ? $t->created_at->toISOString() : now()->toISOString(),
                'orders_count'        => (int) $t->orders_count,
                'dispatch_logs_count' => (int) $t->dispatch_logs_count,
                'is_used'             => (($t->orders_count + $t->dispatch_logs_count) > 0),
            ];
        });
    }

    private function resolveTransporterId($transporterId): ?int
    {
        if (!empty($transporterId) && is_numeric($transporterId)) {
            return (int) $transporterId;
        }

        // Transporter not specified or N/A: ensure column is nullable
        static $ensuredNullable = null;
        if ($ensuredNullable === null) {
            if (DB::getDriverName() === 'sqlite') {
                $ensuredNullable = true;
            } else {
                try {
                    DB::statement("ALTER TABLE `orders` MODIFY `transporter_id` BIGINT UNSIGNED NULL");
                    DB::statement("ALTER TABLE `dispatch_logs` MODIFY `transporter_id` BIGINT UNSIGNED NULL");
                    $ensuredNullable = true;
                } catch (\Throwable $e) {
                    $ensuredNullable = false;
                }
            }
        }

        // If the database still requires NOT NULL, provide an "N/A" fallback record
        if ($ensuredNullable === false) {
            try {
                $fallback = Transporter::firstOrCreate(
                    ['name' => 'N/A'],
                    ['contact' => '—', 'gst' => '—']
                );
                return $fallback->id;
            } catch (\Throwable $e) {}
        }

        return null;
    }

    public function home()
    {
        $user = $this->authUser();
        $query = Order::with(['company', 'transporter', 'items.product']);
        if ($user && isset($user['role']) && $user['role'] === 'SALES') {
            $query->where('created_by', $user['id']);
        }
        $orders = $query->orderByDesc('created_at')->get();
        $totalOrders      = $orders->count();
        $totalValue       = $orders->sum('total');

        foreach ($orders as $o) {
            if ($o->items) {
                foreach ($o->items as $i) {
                    $i->syncDispatchedQty();
                }
            }
        }

        $dispatchedList = $orders->filter(function($o) {
            if ($o->status === 'CANCELLED') return false;
            $ds = strtoupper(str_replace('_', ' ', $o->dispatch_status ?? ''));
            $rem = (float) $o->items->sum(fn($i) => $i->remainingQty());
            $tot = (float) $o->items->sum('quantity');
            return in_array($ds, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'DISPATCHED', 'CLOSED']) || $o->status === 'CLOSED' || ($tot > 0 && $rem <= 0.001);
        })->values();

        $partialList = $orders->filter(function($o) use ($dispatchedList) {
            if ($o->status === 'CANCELLED' || $dispatchedList->contains('id', $o->id)) return false;
            $ds = strtoupper(str_replace('_', ' ', $o->dispatch_status ?? ''));
            $disp = (float) $o->items->sum('dispatched_qty');
            return in_array($ds, ['PARTIAL', 'PARTIAL PENDING', 'PARTIAL DISPATCH', 'PARTIALLY DISPATCHED']) || $disp > 0.001;
        })->values();

        $pendingList = $orders->filter(function($o) use ($dispatchedList, $partialList) {
            if ($o->status === 'CANCELLED') return false;
            return !$dispatchedList->contains('id', $o->id) && !$partialList->contains('id', $o->id);
        })->values();

        $pendingOrdersCount    = $pendingList->count();
        $partialOrdersCount    = $partialList->count();
        $dispatchedOrdersCount = $dispatchedList->count();

        $todayDate = now()->timezone('Asia/Kolkata')->format('Y-m-d');
        $dueTodayList = $orders->filter(function($o) use ($todayDate, $dispatchedList) {
            if (!$o->due_date || $o->status === 'CANCELLED' || $dispatchedList->contains('id', $o->id)) return false;
            return \Carbon\Carbon::parse($o->due_date, 'Asia/Kolkata')->format('Y-m-d') === $todayDate;
        })->values();
        $dueTodayOrdersCount = $dueTodayList->count();

        // Active open orders due today for sales/home dashboard
        $dueTodayOpenOrders = $dueTodayList;

        $dueTodayPendingList = $dueTodayOpenOrders->filter(function($o) use ($pendingList) {
            return $pendingList->contains('id', $o->id);
        })->values();

        $dueTodayPartialList = $dueTodayOpenOrders->filter(function($o) use ($partialList) {
            return $partialList->contains('id', $o->id);
        })->values();

        $mapOrderFn = fn($o) => [
            'id'              => $o->id,
            'companyId'       => $o->company_id,
            'companyName'     => strtoupper($o->company?->name ?? ''),
            'transportId'     => $o->transporter_id,
            'transportName'   => strtoupper($o->transporter?->name ?? ''),
            'total'           => (float) $o->total,
            'status'          => $o->status,
            'dispatchStatus'  => $o->dispatch_status,
            'notes'           => $o->notes,
            'date'            => $o->created_at->toISOString(),
            'dueDate'         => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('d-m-Y') : null,
            'rawDueDate'      => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('Y-m-d') : null,
            'totalQty'        => (float) $o->items->sum('quantity'),
            'dispatchedQty'   => (float) $o->items->sum('dispatched_qty'),
            'remainingQty'    => (float) $o->items->sum(fn($i) => $i->remainingQty()),
            'products'        => $o->items->map(fn($i) => [
                'id'            => $i->id,
                'productId'     => $i->product_id,
                'productName'   => strtoupper($i->product ? $i->product->formatName($i->grade) : ''),
                'grade'         => strtoupper($i->grade ?? ''),
                'quantity'      => (float) $i->quantity,
                'dispatchedQty' => (float) $i->dispatched_qty,
                'remainingQty'  => (float) $i->remainingQty(),
                'price'         => (float) $i->price,
            ]),
        ];

        $pageData = [
            'orders'             => $dueTodayOpenOrders->map($mapOrderFn),
            'pendingOrdersList'  => $dueTodayPendingList->map($mapOrderFn),
            'partialOrdersList'  => $dueTodayPartialList->map($mapOrderFn),
            'dueTodayOrders'     => $dueTodayOpenOrders->map($mapOrderFn),
            'companies'          => Company::orderByDesc('id')->get()->map(fn($c)=>[
                'id'=>$c->id,'name'=>strtoupper($c->name ?? ''),'gst'=>$c->gst,'address'=>$c->address,'pincode'=>$c->pincode,'contact'=>$c->contact,'date'=>$c->created_at->toISOString()
            ]),
            'transportCompanies' => self::getTransportCompanies(),
            'products'           => Product::target()->active()->visibleTo($this->authUser()['role'])->get(['id', 'name', 'unit', 'type'])->map(fn($p) => [
                'id' => $p->id, 'name' => strtoupper($p->name ?? ''), 'unit' => $p->unit, 'type' => $p->type
            ]),
            'stats'              => [
                'totalOrders'         => $totalOrders,
                'pendingOrders'       => $pendingOrdersCount,
                'partialOrders'       => $partialOrdersCount,
                'dispatchedOrders'    => $dispatchedOrdersCount,
                'totalValue'          => $totalValue,
                'dueTodayOrdersCount' => $dueTodayOrdersCount,
            ],
        ];
        return view('sales.home', compact('pageData'));
    }

    public function action(Request $request)
    {
        $companies = Company::withCount('orders')->orderByDesc('id')->get()->map(fn($c)=>[
            'id'=>$c->id,'name'=>strtoupper($c->name ?? ''),'gst'=>$c->gst,'address'=>$c->address,'pincode'=>$c->pincode,'contact'=>$c->contact,'date'=>$c->created_at ? $c->created_at->toISOString() : now()->toISOString(),'orders_count'=>(int)$c->orders_count,'is_used'=>($c->orders_count > 0)
        ]);
        $transportCompanies = self::getTransportCompanies();
        $products = Product::active()->with('grades')->orderBy('name')->get(['id', 'name', 'unit', 'type'])->map(fn($p) => [
            'id' => $p->id, 'name' => strtoupper($p->name ?? ''), 'unit' => $p->unit, 'type' => $p->type,
            'grades' => $p->grades->pluck('name')->map('strtoupper')->toArray()
        ]);
        $grades = \App\Models\Grade::where('is_active', true)->pluck('name')->map('strtoupper')->toArray();

        $editOrder = null;
        if ($request->edit) {
            $editOrder = Order::with(['items.product.grades', 'dispatchLogs.dispatchItems'])->find($request->edit);
            if ($editOrder) {
                foreach ($editOrder->items as $item) {
                    $item->syncDispatchedQty();
                    $dispQty = max((float)$item->dispatched_qty, (float)\App\Models\DispatchLogItem::where('order_item_id', $item->id)->sum('quantity'));
                    $itemQty = (float) $item->quantity;
                    $isFullyDisp = ($dispQty >= $itemQty && $itemQty > 0.0001);
                    $isPartDisp = ($dispQty > 0.0001 && !$isFullyDisp);

                    $item->dispatched_qty = $dispQty;
                    $item->is_dispatched = ($dispQty > 0.0001);
                    $item->is_fully_dispatched = $isFullyDisp;
                    $item->is_partially_dispatched = $isPartDisp;
                }

                $rawDisp = strtoupper(trim(str_replace('_', ' ', (string)($editOrder->dispatch_status ?? ''))));
                $allDone = $editOrder->items->isNotEmpty() && $editOrder->items->every(fn($i) => (float)$i->dispatched_qty >= (float)$i->quantity && (float)$i->quantity > 0.0001);
                $anyDisp = $editOrder->items->contains(fn($i) => (float)$i->dispatched_qty > 0.0001);
                $hasLogs = $editOrder->dispatchLogs && $editOrder->dispatchLogs->isNotEmpty();

                if ($allDone || in_array($rawDisp, ['DONE', 'COMPLETED', 'FULLY DISPATCHED', 'CLOSED'], true)) {
                    $editOrder->dispatch_status = 'DONE';
                    $editOrder->is_fully_dispatched = true;
                    $editOrder->is_partial = false;
                } elseif ($anyDisp || $hasLogs || in_array($rawDisp, ['PARTIAL', 'PARTIAL DISPATCH', 'PARTIALLY DISPATCHED', 'PARTIAL PENDING'], true)) {
                    $editOrder->dispatch_status = 'PARTIAL PENDING';
                    $editOrder->is_partial = true;
                    $editOrder->is_fully_dispatched = false;
                } else {
                    $editOrder->is_partial = false;
                    $editOrder->is_fully_dispatched = false;
                }

                $existingProdIds = $products->pluck('id')->toArray();
                foreach ($editOrder->items as $item) {
                    if ($item->product && !in_array($item->product->id, $existingProdIds)) {
                        $products->push([
                            'id' => $item->product->id,
                            'name' => strtoupper($item->product->name ?? ''),
                            'unit' => $item->product->unit,
                            'type' => $item->product->type,
                            'grades' => $item->product->grades ? $item->product->grades->pluck('name')->map('strtoupper')->toArray() : []
                        ]);
                        $existingProdIds[] = $item->product->id;
                    }
                }
            }
        }

        $editCompany = null;
        if ($request->editCompany) {
            $editCompany = Company::find($request->editCompany);
        }

        $pageData = compact('companies', 'transportCompanies', 'products', 'grades', 'editOrder', 'editCompany');
        return view('sales.action', compact('pageData'));
    }

    public function storeOrder(Request $request)
    {
        // Convert grade to uppercase for consistency and case-insensitive validation/storage
        $items = collect($request->input('items', []))->map(function ($item) {
            if (isset($item['grade'])) {
                $item['grade'] = strtoupper($item['grade']);
            }
            return $item;
        })->toArray();
        $request->merge(['items' => $items]);

        // Prevent duplicate orders: route to the update logic if an order ID is provided
        $orderId = $request->input('order_id') ?? $request->input('id');
        if ($orderId) {
            return $this->updateOrder($request, $orderId);
        }

        // Allow NA / empty transporter
        $transporterId = $request->input('transporter_id');
        if (in_array(strtoupper(trim((string)$transporterId)), ['', 'NA', 'N/A', 'NONE', 'NULL'], true)) {
            $transporterId = null;
        }
        $request->merge(['transporter_id' => $transporterId]);

        $request->validate([
            'company_id'      => 'required|exists:companies,id',
            'transporter_id'  => 'nullable|exists:transporters,id',
            'due_date'        => 'nullable|date',
            'notes'           => 'required|string',
            'items'           => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.grade'      => 'required|string',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.price'      => 'required|numeric|min:0',
        ], [
            'notes.required'  => 'Notes / Special Instructions is required. Please write instructions/notes.',
        ]);

        $user  = $this->authUser();

        // Security check: Ensure products are visible to this user role and grade belongs to product
        $visibleProductIds = Product::active()->pluck('id')->toArray();
        foreach ($request->items as $item) {
            if (!in_array($item['product_id'], $visibleProductIds)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized product access.'], 403);
            }
            $product = Product::with('grades')->find($item['product_id']);
            if ($product && $product->grades->isNotEmpty()) {
                $allowedGrades = $product->grades->pluck('name')->map('strtoupper')->toArray();
                $itemGrade = strtoupper(trim((string)$item['grade']));
                if (!in_array($itemGrade, $allowedGrades, true) && !in_array($itemGrade, ['NA', 'N/A', 'NONE'], true)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Grade '{$item['grade']}' is invalid for product '{$product->name}'."
                    ], 422);
                }
            }
        }
        $total = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['price']);
        $hasDueDate = $this->ensureDueDateColumnExists();
        $finalTransporterId = $this->resolveTransporterId($request->transporter_id);

        DB::transaction(function () use ($request, $user, $total, $hasDueDate, $finalTransporterId) {
            $orderAttributes = [
                'created_by'      => $user['id'],
                'company_id'      => $request->company_id,
                'transporter_id'  => $finalTransporterId,
                'total'           => $total,
                'status'          => 'OPEN',
                'dispatch_status' => 'PENDING',
                'notes'           => $request->notes,
            ];
            if ($hasDueDate) {
                $orderAttributes['due_date'] = $request->due_date ?: null;
            }

            $order = Order::create($orderAttributes);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'grade'      => $item['grade'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Order generated successfully!']);
    }

    private function validateAndFormatContact(string $contact): ?string
    {
        $clean = trim($contact);

        // International number (starts with '+' followed by non-91 country code, e.g. +1, +44, +971)
        if (preg_match('/^\+(?!91\b)[0-9\s\-()]{6,20}$/', $clean)) {
            return $clean;
        }

        // 079 Landline (e.g. 079-12345678, 07912345678, +91 79 12345678)
        if (preg_match('/^(\+91[\s\-]?)?0?79[\s\-]?[0-9]{6,8}$/', $clean)) {
            return $clean;
        }

        // Standard Indian 10-digit mobile
        $digitsOnly = preg_replace('/^\+91[\s\-]*/', '', $clean);
        $digitsOnly = preg_replace('/[\s\-()]/', '', $digitsOnly);
        if (preg_match('/^[0-9]{10}$/', $digitsOnly)) {
            return '+91 ' . $digitsOnly;
        }

        return null;
    }

    public function storeCompany(Request $request)
    {
        $request->merge(['name' => strtoupper($request->name)]);

        $gstRule = ['nullable', 'string', 'regex:/^(N\/A|[A-Za-z0-9]{15})$/i'];
        if ($request->gst && strtoupper($request->gst) !== 'N/A') {
            $gstRule[] = 'unique:companies,gst';
        }

        $formattedContact = null;
        if (!empty($request->contact)) {
            $formattedContact = $this->validateAndFormatContact($request->contact);
            if (!$formattedContact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact must be a 10-digit Indian mobile (+91), a 079 landline number, or an international number (+...).'
                ], 422);
            }
        }

        $request->validate([
            'name'    => 'required|string|max:255|unique:companies,name',
            'gst'     => $gstRule,
            'address' => 'nullable|string|max:500',
            'pincode' => 'nullable|string|regex:/^[0-9]{6}$/',
        ], [
            'gst.regex'     => 'GST number must be 15 alphanumeric characters or N/A',
            'name.unique'   => 'Company name already exists',
            'gst.unique'    => 'GST number already registered',
            'pincode.regex' => 'Pincode must be 6 digits',
        ]);

        $request->merge(['contact' => $formattedContact]);

        Company::create($request->only('name', 'gst', 'address', 'pincode', 'contact'));
        return response()->json(['success' => true, 'message' => 'Company saved!']);
    }

    public function updateCompany(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $request->merge(['name' => strtoupper($request->name)]);

        $gstRule = ['nullable', 'string', 'regex:/^(N\/A|[A-Za-z0-9]{15})$/i'];
        if ($request->gst && strtoupper($request->gst) !== 'N/A') {
            $gstRule[] = 'unique:companies,gst,' . $company->id;
        }

        $formattedContact = null;
        if (!empty($request->contact)) {
            $formattedContact = $this->validateAndFormatContact($request->contact);
            if (!$formattedContact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact must be a 10-digit Indian mobile (+91), a 079 landline number, or an international number (+...).'
                ], 422);
            }
        }

        $request->validate([
            'name'    => 'required|string|max:255|unique:companies,name,' . $company->id,
            'gst'     => $gstRule,
            'address' => 'nullable|string|max:500',
            'pincode' => 'nullable|string|regex:/^[0-9]{6}$/',
        ], [
            'gst.regex'     => 'GST number must be 15 alphanumeric characters or N/A',
            'name.unique'   => 'Company name already exists',
            'gst.unique'    => 'GST number already registered',
            'pincode.regex' => 'Pincode must be 6 digits',
        ]);

        $request->merge(['contact' => $formattedContact]);

        $company->update($request->only('name', 'gst', 'address', 'pincode', 'contact'));
        return response()->json(['success' => true, 'message' => 'Company updated!']);
    }

    public function destroyCompany($id)
    {
        $company = Company::findOrFail($id);
        if ($company->orders()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete company "' . $company->name . '" because it is linked to existing orders or dispatch records. Only edit is allowed.'
            ], 422);
        }

        $company->delete();
        return response()->json(['success' => true, 'message' => 'Company deleted successfully!']);
    }

    public function destroyTransporter($id)
    {
        $transporter = Transporter::findOrFail($id);
        if ($transporter->orders()->exists() || $transporter->dispatchLogs()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete transporter "' . $transporter->name . '" because it is linked to existing orders or dispatch records. Only edit is allowed.'
            ], 422);
        }

        $transporter->delete();
        return response()->json(['success' => true, 'message' => 'Transporter deleted successfully!']);
    }

    public function storeTransporter(Request $request)
    {
        $request->merge(['name' => strtoupper($request->name)]);

        $gstRule = ['nullable', 'string', 'regex:/^(N\/A|[A-Za-z0-9]{15})$/i'];
        if ($request->gst && strtoupper($request->gst) !== 'N/A') {
            $gstRule[] = 'unique:transporters,gst';
        }

        $formattedContact = null;
        if (!empty($request->contact)) {
            $formattedContact = $this->validateAndFormatContact($request->contact);
            if (!$formattedContact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact must be a 10-digit Indian mobile (+91), a 079 landline number, or an international number (+...).'
                ], 422);
            }
        }

        $request->validate([
            'name'     => 'required|string|max:255|unique:transporters,name',
            'gst'      => $gstRule,
            'vehicles' => 'nullable|string',
        ], [
            'gst.regex'   => 'GST number must be 15 alphanumeric characters or N/A',
            'name.unique' => 'Transporter already exists',
            'gst.unique'  => 'GST number already registered'
        ]);

        $request->merge(['contact' => $formattedContact]);

        $vehiclesInput = $request->vehicles;
        if ($vehiclesInput && self::isDispatchNoteVehicle($vehiclesInput)) {
            $vehiclesInput = null;
        }

        $transporter = Transporter::create([
            'name'     => $request->name,
            'gst'      => $request->gst,
            'contact'  => $formattedContact,
            'vehicles' => $vehiclesInput,
        ]);
        return response()->json([
            'success' => true, 
            'message' => 'Transporter saved!',
            'transporter' => [
                'id' => $transporter->id,
                'name' => $transporter->name,
                'gst' => $transporter->gst,
                'contact' => $transporter->contact,
                'vehicles' => $transporter->vehicles,
            ]
        ]);
    }

    public function updateTransporter(Request $request, $id)
    {
        $transporter = Transporter::findOrFail($id);
        $request->merge(['name' => strtoupper($request->name)]);

        $gstRule = ['nullable', 'string', 'regex:/^(N\/A|[A-Za-z0-9]{15})$/i'];
        if ($request->gst && strtoupper($request->gst) !== 'N/A') {
            $gstRule[] = 'unique:transporters,gst,' . $transporter->id;
        }

        $formattedContact = null;
        if (!empty($request->contact)) {
            $formattedContact = $this->validateAndFormatContact($request->contact);
            if (!$formattedContact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact must be a 10-digit Indian mobile (+91), a 079 landline number, or an international number (+...).'
                ], 422);
            }
        }

        $request->validate([
            'name'     => 'required|string|max:255|unique:transporters,name,' . $transporter->id,
            'gst'      => $gstRule,
            'vehicles' => 'nullable|string',
        ], [
            'gst.regex'   => 'GST number must be 15 alphanumeric characters or N/A',
            'name.unique' => 'Transporter name already exists',
            'gst.unique'  => 'GST number already registered'
        ]);

        $request->merge(['contact' => $formattedContact]);

        $vehiclesInput = $request->vehicles;
        if ($vehiclesInput && self::isDispatchNoteVehicle($vehiclesInput)) {
            $vehiclesInput = null;
        }

        $transporter->update([
            'name'     => $request->name,
            'gst'      => $request->gst,
            'contact'  => $formattedContact,
            'vehicles' => $vehiclesInput,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Transporter updated!',
            'transporter' => [
                'id' => $transporter->id,
                'name' => $transporter->name,
                'gst' => $transporter->gst,
                'contact' => $transporter->contact,
                'vehicles' => $transporter->vehicles,
            ]
        ]);
    }

    public function history()
    {
        $user = $this->authUser();
        $query = Order::with(['company', 'transporter', 'items.product', 'dispatchLogs.user', 'creator']);
        if ($user && isset($user['role']) && $user['role'] === 'SALES') {
            $query->where('created_by', $user['id']);
        }
        $orders = $query->orderByDesc('created_at')->get();
        $companies = Company::orderBy('name')->get();
        $transporters = Transporter::orderBy('name')->get();

        // Sync order items dispatched quantities for data consistency
        foreach ($orders as $o) {
            if ($o->items) {
                foreach ($o->items as $i) {
                    $i->syncDispatchedQty();
                }
            }
        }

        $pageData = [
            'orders'             => $orders->map(function($o) {
                $totalQty = (float) $o->items->sum('quantity');
                $dispatchedQty = (float) $o->items->sum('dispatched_qty');
                $remainingQty = (float) $o->items->sum(fn($i) => $i->remainingQty());

                $rawSt = strtoupper(trim(str_replace('_', ' ', (string)($o->dispatch_status ?? 'PENDING'))));
                $hasLogs = $o->dispatchLogs && $o->dispatchLogs->isNotEmpty();

                if ($o->status === 'CANCELLED') {
                    $computedStatus = 'CANCELLED';
                } elseif (
                    ($totalQty > 0 && $remainingQty <= 0.001) ||
                    in_array($rawSt, ['DONE', 'COMPLETED', 'FULLY DISPATCHED', 'FULLY_DISPATCHED', 'DISPATCHED', 'CLOSED']) ||
                    $o->status === 'CLOSED'
                ) {
                    $computedStatus = 'DONE';
                } elseif ($dispatchedQty > 0 || in_array($rawSt, ['PARTIAL', 'PARTIAL PENDING', 'PARTIAL_PENDING', 'PARTIAL DISPATCH', 'PARTIAL_DISPATCH', 'PARTIALLY DISPATCHED'])) {
                    $computedStatus = 'PARTIAL';
                } elseif ($hasLogs) {
                    $computedStatus = ($remainingQty <= 0.001) ? 'DONE' : 'PARTIAL';
                } else {
                    $computedStatus = 'PENDING';
                }

                // Keep order dispatch_status and status up-to-date in DB if drifted
                if ($o->status !== 'CANCELLED') {
                    $orderUpdates = [];
                    if ($o->dispatch_status !== $computedStatus) {
                        $orderUpdates['dispatch_status'] = $computedStatus;
                    }
                    if ($computedStatus === 'DONE' && $o->status !== 'CLOSED') {
                        $orderUpdates['status'] = 'CLOSED';
                    }
                    if (!empty($orderUpdates)) {
                        $o->update($orderUpdates);
                    }
                }

                // Resolve dispatch date & logs
                $sortedLogs = $o->dispatchLogs ? $o->dispatchLogs->sortByDesc('created_at')->values() : collect();
                $latestDispatchLog = $sortedLogs->first();
                $dispatchDate = null;
                $dispatchDateTime = null;
                $rawDispatchDate = null;

                if ($latestDispatchLog && $latestDispatchLog->created_at) {
                    $dispatchDate = $latestDispatchLog->created_at->timezone('Asia/Kolkata')->format('d-m-Y');
                    $dispatchDateTime = $latestDispatchLog->created_at->timezone('Asia/Kolkata')->format('d-m-Y, h:i A');
                    $rawDispatchDate = $latestDispatchLog->created_at->toISOString();
                } elseif ($latestDispatchLog && !empty($latestDispatchLog->date)) {
                    $dCarbon = \Carbon\Carbon::parse($latestDispatchLog->date)->timezone('Asia/Kolkata');
                    $dispatchDate = $dCarbon->format('d-m-Y');
                    $dispatchDateTime = $dCarbon->format('d-m-Y, h:i A');
                    $rawDispatchDate = $dCarbon->toISOString();
                } elseif (in_array($computedStatus, ['DONE', 'COMPLETED', 'FULLY DISPATCHED', 'FULLY_DISPATCHED']) || $dispatchedQty > 0) {
                    $fallbackDate = $o->updated_at ?? $o->created_at;
                    if ($fallbackDate) {
                        $dispatchDate = $fallbackDate->timezone('Asia/Kolkata')->format('d-m-Y');
                        $dispatchDateTime = $fallbackDate->timezone('Asia/Kolkata')->format('d-m-Y, h:i A');
                        $rawDispatchDate = $fallbackDate->toISOString();
                    }
                }

                $dispatchLogsData = $sortedLogs->map(function($dl) {
                    return [
                        'id'           => $dl->id,
                        'dispatchId'   => 'DSP-' . str_pad($dl->id, 4, '0', STR_PAD_LEFT),
                        'date'         => $dl->created_at ? $dl->created_at->timezone('Asia/Kolkata')->format('d-m-Y') : null,
                        'dateTime'     => $dl->created_at ? $dl->created_at->timezone('Asia/Kolkata')->format('d-m-Y, h:i A') : null,
                        'rawDate'      => $dl->created_at ? $dl->created_at->toISOString() : null,
                        'dispatchedBy' => $dl->user?->name ?? 'System',
                        'driverNo'     => $dl->driver_no,
                        'lrNo'         => $dl->lr_no,
                    ];
                })->values()->toArray();

                return [
                    'id'                 => $o->id,
                    'createdBy'          => $o->creator?->name ?? 'System',
                    'companyId'          => $o->company_id,
                    'companyName'        => strtoupper($o->company?->name ?? ''),
                    'transportId'        => $o->transporter_id,
                    'transportName'      => strtoupper($o->transporter?->name ?? ''),
                    'transporter'        => $o->transporter ? ['id' => $o->transporter->id, 'name' => strtoupper($o->transporter->name)] : null,
                    'total'              => (float) $o->total,
                    'totalQty'           => $totalQty,
                    'dispatchedQty'      => $dispatchedQty,
                    'remainingQty'       => $remainingQty,
                    'status'             => $o->status,
                    'dispatchStatus'     => $computedStatus,
                    'date'               => $o->created_at->toISOString(),
                    'dueDate'            => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('d-m-Y') : null,
                    'rawDueDate'         => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('Y-m-d') : null,
                    'dispatchDate'       => $dispatchDate,
                    'dispatchDateTime'   => $dispatchDateTime,
                    'rawDispatchDate'    => $rawDispatchDate,
                    'dispatchLogs'       => $dispatchLogsData,
                    'notes'              => $o->notes,
                    'lrCopies'       => $o->dispatchLogs ? $o->dispatchLogs->filter(fn($l) => !empty($l->lr_image_path))->flatMap(function($l) {
                        $rawPaths = \App\Http\Controllers\DispatchController::getLrImagePaths($l);
                        if (empty($rawPaths)) return [];
                        $slug = request()->segment(1) ?: 'sales';
                        $ver = $l->updated_at?->timestamp ?? ($l->created_at?->timestamp ?? 1);
                        $validEntries = [];
                        foreach ($rawPaths as $idx => $path) {
                            if (\App\Http\Controllers\DispatchController::resolveLocalFilePath($path)) {
                                $suffix = count($rawPaths) > 1 ? ' (' . ($idx + 1) . ')' : '';
                                $validEntries[] = [
                                    'id'         => $l->id,
                                    'dispatchId' => 'DSP-' . str_pad($l->id, 4, '0', STR_PAD_LEFT) . $suffix,
                                    'url'        => url($slug . '/dispatch/view-lr/' . $l->id) . '?idx=' . $idx . '&v=' . $ver,
                                    'date'       => $l->created_at ? $l->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : '',
                                    'driverNo'   => $l->driver_no,
                                    'lrNo'       => $l->lr_no,
                                ];
                            }
                        }
                        return $validEntries;
                    })->values()->toArray() : [],
                    'items'          => $o->items->map(fn($i)=>[
                        'id'            => $i->id,
                        'productId'     => $i->product_id,
                        'productName'   => strtoupper($i->product ? $i->product->formatName($i->grade) : ''),
                        'rawProductName'=> strtoupper($i->product?->name ?? ''),
                        'grade'         => strtoupper($i->grade ?? ''),
                        'quantity'      => (float) $i->quantity,
                        'dispatchedQty' => (float) $i->dispatched_qty,
                        'remainingQty'  => (float) $i->remainingQty(),
                        'price'         => (float) $i->price,
                    ]),
                ];
            }),
            'companies'          => $companies->map(fn($c)=>[
                'id'=>$c->id,'name'=>strtoupper($c->name ?? ''),'gst'=>$c->gst,'contact'=>$c->contact,'address'=>$c->address,'date'=>$c->created_at->toISOString()
            ]),
            'transportCompanies' => self::getTransportCompanies(),
        ];
        return view('sales.history', compact('pageData'));
    }

    public function updateOrder(Request $request, $id)
    {
        // Convert grade to uppercase for consistency and case-insensitive validation/storage
        $items = collect($request->input('items', []))->map(function ($item) {
            if (isset($item['grade'])) {
                $item['grade'] = strtoupper($item['grade']);
            }
            return $item;
        })->toArray();
        $request->merge(['items' => $items]);

        $order = Order::with('items')->findOrFail($id);

        $rawDispStatus = strtoupper(trim(str_replace('_', ' ', (string)($order->dispatch_status ?? 'PENDING'))));
        $isOrderFullyDispatched = in_array($rawDispStatus, ['DONE', 'COMPLETED', 'FULLY DISPATCHED', 'CLOSED'], true)
            || $order->status === 'CLOSED';
        $isOrderPartial = in_array($rawDispStatus, ['PARTIAL', 'PARTIAL DISPATCH', 'PARTIALLY DISPATCHED', 'PARTIAL PENDING'], true)
            || ($order->items->sum('dispatched_qty') > 0 && !$isOrderFullyDispatched);

        // Allow editing unless the order is fully dispatched or closed
        if ($isOrderFullyDispatched) {
            return response()->json(['success' => false, 'message' => 'Fully dispatched or closed orders cannot be edited.'], 422);
        }

        // If order has any dispatch progress, disallow removing already dispatched products
        if ($isOrderPartial || $isOrderFullyDispatched) {
            $existingItemIds = $order->items->pluck('id')->all();
            $submittedItemIds = collect($request->items)->pluck('id')->filter()->map('intval')->all();
            foreach ($order->items as $existingItem) {
                if (!in_array((int)$existingItem->id, $submittedItemIds, true)) {
                    $existingItem->syncDispatchedQty();
                    $dispQty = max((float)$existingItem->dispatched_qty, (float)\App\Models\DispatchLogItem::where('order_item_id', $existingItem->id)->sum('quantity'));
                    if ($dispQty > 0.0001) {
                        $prodName = $existingItem->product ? $existingItem->product->formatName($existingItem->grade) : 'Product';
                        return response()->json([
                            'success' => false,
                            'message' => "Cannot remove '{$prodName}'. This product has already been dispatched ({$dispQty} kg) and cannot be removed."
                        ], 422);
                    }
                }
            }
        }

        // Allow NA / empty transporter
        $transporterId = $request->input('transporter_id');
        if (in_array(strtoupper(trim((string)$transporterId)), ['', 'NA', 'N/A', 'NONE', 'NULL'], true)) {
            $transporterId = null;
        }
        $request->merge(['transporter_id' => $transporterId]);

        $request->validate([
            'company_id'      => 'required|exists:companies,id',
            'transporter_id'  => 'nullable|exists:transporters,id',
            'due_date'        => 'nullable|date',
            'notes'           => 'required|string',
            'items'           => 'required|array|min:1',
            'items.*.id'      => 'nullable|exists:order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.grade'      => 'required|string',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.price'      => 'required|numeric|min:0',
        ], [
            'notes.required'  => 'Notes / Special Instructions is required. Please write instructions/notes.',
        ]);

        $user = $this->authUser();
        $visibleProductIds = Product::active()->pluck('id')->toArray();
        foreach ($request->items as $item) {
            if (!in_array($item['product_id'], $visibleProductIds)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized product access.'], 403);
            }
            $product = Product::with('grades')->find($item['product_id']);
            if ($product && $product->grades->isNotEmpty()) {
                $allowedGrades = $product->grades->pluck('name')->map('strtoupper')->toArray();
                $itemGrade = strtoupper(trim((string)$item['grade']));
                if (!in_array($itemGrade, $allowedGrades, true) && !in_array($itemGrade, ['NA', 'N/A', 'NONE'], true)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Grade '{$item['grade']}' is invalid for product '{$product->name}'."
                    ], 422);
                }
            }
        }

        // Validate that quantities don't go below dispatched amounts and fully dispatched items are not modified
        foreach ($request->items as $item) {
            if (!empty($item['id'])) {
                $orderItem = OrderItem::find($item['id']);
                if ($orderItem) {
                    $orderItem->syncDispatchedQty();
                    $dispQty = (float) $orderItem->dispatched_qty;
                    $newQty  = (float) $item['quantity'];
                    $origQty = (float) $orderItem->quantity;
                    $isItemFullyDispatched = ($dispQty >= $origQty && $origQty > 0.001);

                    // Fully dispatched item: quantity cannot be edited
                    if ($isItemFullyDispatched && abs($newQty - $origQty) > 0.001) {
                        $product = $orderItem->product;
                        $productName = $product ? $product->formatName($orderItem->grade) : 'Product';
                        return response()->json([
                            'success' => false,
                            'message' => "Cannot edit quantity for '{$productName}'. This product is already fully dispatched ({$dispQty} kg)."
                        ], 422);
                    }

                    // Partially dispatched item: cannot reduce quantity below dispatched amount
                    if ($dispQty > 0 && $newQty < $dispQty) {
                        $product = $orderItem->product;
                        $productName = $product ? $product->formatName($item['grade']) : 'Unknown';
                        return response()->json([
                            'success' => false,
                            'message' => "Cannot reduce {$productName} quantity to {$newQty} kg. Already dispatched: {$dispQty} kg. Minimum allowed: {$dispQty} kg."
                        ], 422);
                    }

                    // Partially or fully dispatched item: cannot change product_id or grade
                    if ($dispQty > 0) {
                        if ((int)$item['product_id'] !== (int)$orderItem->product_id || strtoupper(trim((string)$item['grade'])) !== strtoupper(trim((string)$orderItem->grade))) {
                            $product = $orderItem->product;
                            $productName = $product ? $product->formatName($orderItem->grade) : 'Product';
                            return response()->json([
                                'success' => false,
                                'message' => "Cannot change product or grade for '{$productName}' because it has already been dispatched ({$dispQty} kg). Just product quantity can be edited."
                            ], 422);
                        }
                    }
                }
            }
        }

        $total = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['price']);
        $hasDueDate = $this->ensureDueDateColumnExists();
        $finalTransporterId = $this->resolveTransporterId($request->transporter_id);

        DB::transaction(function () use ($request, $order, $total, $hasDueDate, $finalTransporterId) {
            $updateAttributes = [
                'company_id'     => $request->company_id,
                'transporter_id' => $finalTransporterId,
                'total'          => $total,
                'notes'          => $request->notes,
            ];
            if ($hasDueDate) {
                $updateAttributes['due_date'] = $request->due_date ?: null;
            }

            $order->update($updateAttributes);

            $existingItemIds = [];

            foreach ($request->items as $item) {
                // Update existing item if ID is provided, maintaining dispatch history
                if (!empty($item['id'])) {
                    $orderItem = OrderItem::where('id', $item['id'])->where('order_id', $order->id)->first();
                    if ($orderItem) {
                        // Ensure we don't reduce quantity below what's already dispatched
                        $newQty = max((float)$item['quantity'], (float)$orderItem->dispatched_qty);
                        $orderItem->update([
                            'product_id' => $item['product_id'],
                            'grade'      => $item['grade'],
                            'quantity'   => $newQty,
                            'price'      => $item['price'],
                        ]);
                        $existingItemIds[] = $orderItem->id;
                        continue;
                    }
                }

                // Otherwise, create a new item attached to the same order
                $newItem = OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'grade'      => $item['grade'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);
                $existingItemIds[] = $newItem->id;
            }

            // Only delete removed items if they haven't been dispatched
            $order->items()->whereNotIn('id', $existingItemIds)->where('dispatched_qty', '<=', 0)->delete();
        });

        return response()->json(['success' => true, 'message' => 'Order updated successfully!']);
    }

    public function cancelOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        if ($order->dispatch_status === 'DONE' || $order->dispatch_status === 'PARTIAL' || $order->status === 'CLOSED') {
            return response()->json(['success' => false, 'message' => 'Cannot cancel an order that is closed or has dispatch progress.'], 422);
        }

        $order->update(['status' => 'CANCELLED']);
        return response()->json(['success' => true, 'message' => 'Order cancelled successfully!']);
    }

    public function destroyOrder(Request $request, $id)
    {
        $order = Order::with(['items', 'dispatchLogs.dispatchItems'])->findOrFail($id);

        $totalQty = (float) $order->items->sum('quantity');
        $remainingQty = (float) $order->items->sum(fn($i) => $i->remainingQty());
        $rawSt = strtoupper(trim(str_replace('_', ' ', (string)($order->dispatch_status ?? 'PENDING'))));
        $isFullyDispatched = in_array($rawSt, ['DONE', 'COMPLETED', 'FULLY DISPATCHED', 'FULLY_DISPATCHED', 'CLOSED'])
            || ($totalQty > 0 && $remainingQty <= 0);

        if ($isFullyDispatched) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete order: This order has already been fully dispatched.'
            ], 422);
        }

        $dispatchedQty = (float) $order->items->sum('dispatched_qty');
        $hasDispatchLogs = DispatchLog::where('order_id', $order->id)->exists();
        $isPartial = in_array($rawSt, ['PARTIAL', 'PARTIAL DISPATCH', 'PARTIALLY DISPATCHED', 'PARTIAL PENDING']) || $dispatchedQty > 0 || $hasDispatchLogs;

        if ($isPartial) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete order: This order has partial deliveries already dispatched. Please edit the order instead.'
            ], 422);
        }

        DB::transaction(function () use ($order) {
            // 1. Revert and clean up all dispatch logs, locations, and stock deductions
            $dispatchLogs = DispatchLog::with('dispatchItems')->where('order_id', $order->id)->get();
            foreach ($dispatchLogs as $log) {
                $itemIds = $log->dispatchItems->pluck('id')->toArray();

                // Collect stock IDs from dispatch item locations
                $stockIdsFromLocations = DispatchItemLocation::whereIn(
                    'dispatch_log_item_id', 
                    $itemIds
                )->pluck('stock_id')->toArray();

                // Collect stock IDs created with round notes
                $stockIdsFromNotes = Stock::where('notes', 'LIKE', "%round #{$log->id}%")
                    ->orWhere('notes', 'LIKE', "%round #{$log->id})%")
                    ->pluck('id')
                    ->toArray();

                $allStockIds = array_unique(array_filter(array_merge($stockIdsFromLocations, $stockIdsFromNotes)));
                if (!empty($allStockIds)) {
                    Stock::whereIn('id', $allStockIds)->delete();
                }

                // Delete physical LR files
                foreach (DispatchController::getLrImagePaths($log) as $imgPath) {
                    if ($imgPath && file_exists(public_path($imgPath))) {
                        @unlink(public_path($imgPath));
                    }
                }

                // Delete dispatch item locations
                DispatchItemLocation::whereIn('dispatch_log_item_id', $itemIds)->delete();

                // Delete dispatch log items
                DispatchLogItem::where('dispatch_log_id', $log->id)->delete();

                // Delete the dispatch log itself
                $log->delete();
            }

            // Also clean up any stock deductions directly referencing this order
            Stock::where('notes', 'LIKE', "%Order #{$order->id}%")
                ->orWhere('notes', 'LIKE', "%order #{$order->id}%")
                ->delete();

            // 2. Delete all order items
            $order->items()->delete();

            // 3. Delete the order itself
            $order->delete();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Order #{$id} and all related records have been completely deleted."
            ]);
        }

        return redirect()->back()->with('success', "Order #{$id} and all related records have been completely deleted.");
    }

    public function profile()
    {
        return view('sales.profile');
    }

    public function clearHistory(Request $request)
    {
        $user = $this->authUser();
        if (($user['role'] ?? '') !== 'ADMIN') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Only Admin can clear sales and dispatch data.'], 403);
            }
            abort(403, 'Unauthorized. Only Admin can clear sales and dispatch data.');
        }

        try {
            Schema::disableForeignKeyConstraints();
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } catch (\Throwable $e) {}

        $tables = [
            'dispatch_item_locations',
            'dispatch_log_items',
            'dispatch_logs',
            'order_items',
            'orders',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    DB::table($table)->truncate();
                } catch (\Throwable $e) {
                    DB::table($table)->delete();
                }
            }
        }

        try {
            Schema::enableForeignKeyConstraints();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (\Throwable $e) {}

        $lrDir = public_path('lr_images');
        if (!app()->environment('testing') && is_dir($lrDir)) {
            $files = glob($lrDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'All sales orders and dispatch history have been successfully cleared!'
            ]);
        }

        return redirect()->back()->with('success', 'All sales orders and dispatch history have been successfully cleared!');
    }
}
