<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\DispatchLog;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Transporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchController extends Controller
{
    private function authUser(): array { return session('auth_user'); }

    public function home()
    {
        $pending   = Order::with(['company', 'transporter', 'items.product'])->whereIn('dispatch_status', ['PENDING', 'PARTIAL'])->orderByDesc('created_at')->get();
        $completed = Order::with(['company', 'transporter'])->where('dispatch_status', 'DONE')->orderByDesc('created_at')->get();

        $rawStock = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.stage', 'RAW')
            ->groupBy('stocks.product_id', 'stocks.grade', 'products.name', 'products.unit')
            ->selectRaw("stocks.product_id as id, products.name, stocks.grade, products.unit, SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->get();

        $semiStock = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.stage', 'SEMI')
            ->groupBy('stocks.product_id', 'stocks.grade', 'products.name', 'products.unit')
            ->selectRaw("stocks.product_id as id, products.name, stocks.grade, products.unit, SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->get();

        $finishedStock = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.stage', 'FINISHED')
            ->groupBy('stocks.product_id', 'stocks.grade', 'products.name', 'products.unit')
            ->selectRaw("stocks.product_id as id, products.name, stocks.grade, products.unit, SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->get();

        $pageData = [
            'rawStock'        => $rawStock,
            'semiStock'       => $semiStock,
            'finishedStock'   => $finishedStock,
            'pendingOrders'   => $pending->map(fn($o) => [
                'id'           => $o->id,
                'companyId'    => $o->company_id,
                'companyName'  => $o->company?->name,
                'transportId'  => $o->transporter_id,
                'transporterName' => $o->transporter?->name,
                'total'        => $o->total,
                'date'         => $o->created_at->toISOString(),
                'totalQty'     => $o->items->sum('quantity'),
                'dispatchedQty'=> $o->items->sum('dispatched_qty'),
                'notes'        => $o->notes,
                'items'        => $o->items->map(fn($i) => [
                    'id'            => $i->id,
                    'productId'     => $i->product_id,
                    'rawProductName'=> $i->product?->name ?? 'Unknown',
                    'productName'   => $i->product?->name ?? 'Unknown',
                    'formattedName' => $i->product ? $i->product->formatName($i->grade) : 'Unknown',
                    'productType'   => $i->product?->type,
                    'quantity'      => (float) $i->quantity,
                    'dispatchedQty' => (float) $i->dispatched_qty,
                    'remainingQty'  => $i->remainingQty(),
                    'grade'         => $i->grade,
                ]),
            ]),
            'completedOrders' => $completed->map(fn($o) => [
                'id'           => $o->id,
                'companyId'    => $o->company_id,
                'transportId'  => $o->transporter_id,
                'total'        => $o->total,
                'date'         => $o->created_at->toISOString(),
                'notes'        => $o->notes,
            ]),
            'companies'           => Company::all(['id', 'name']),
            'transportCompanies'  => Transporter::all(['id', 'name']),
            'products'            => Product::active()->get(['id', 'name', 'unit', 'type']),
        ];
        return view('dispatch.home', compact('pageData'));
    }

    public function action()
    {
        $pendingOrders = Order::with(['company', 'transporter', 'items.product'])
            ->whereIn('dispatch_status', ['PENDING', 'PARTIAL'])
            ->orderByDesc('created_at')
            ->get();

        $pageData = [
            'pendingOrders' => $pendingOrders->map(fn($o)=>[
                'id'          => $o->id,
                'notes'       => $o->notes,
                'company'     => [
                    'name'    => $o->company?->name,
                    'gst'     => $o->company?->gst,
                    'contact' => $o->company?->contact,
                    'address' => $o->company?->address,
                ],
                'transporter' => [
                    'name'     => $o->transporter?->name,
                    'gst'      => $o->transporter?->gst,
                    'contact'  => $o->transporter?->contact,
                    'vehicles' => $o->transporter?->vehicles,
                ],
                'items'       => $o->items->map(fn($i)=>[
                    'id'            => $i->id,
                    'rawProductName'=> $i->product?->name ?? 'Unknown',
                    'productName'   => $i->product?->name ?? 'Unknown',
                    'formattedName' => $i->product ? $i->product->formatName($i->grade) : 'Unknown',
                    'productId'     => $i->product_id,
                    'productType'   => $i->product?->type,
                    'quantity'      => (float) $i->quantity,
                    'dispatchedQty' => (float) $i->dispatched_qty,
                    'remainingQty'  => $i->remainingQty(),
                    'grade'         => $i->grade,
                ])
            ])
        ];
        return view('dispatch.action', compact('pageData'));
    }

    public function storeDispatch(Request $request)
    {
        $request->validate([
            'order_id'                    => 'required|exists:orders,id',
            'items'                       => 'required|array|min:1',
            'items.*.order_item_id'       => 'required|exists:order_items,id',
            'items.*.quantity'            => 'required|numeric|min:0.001',
            'items.*.location_splits'             => 'nullable|array',
            'items.*.location_splits.*.location_key' => 'required_with:items.*.location_splits|string',
            'items.*.location_splits.*.dispatch_location_qty' => 'required_with:items.*.location_splits|numeric|min:0.001',
            'lr_image'                    => 'nullable|string',
            'driver_no'                   => 'nullable|string',
            'lr_no'                       => 'nullable|string',
            'notes'                       => 'nullable|string',
            'transporter_id'              => 'nullable|exists:transporters,id',
        ]);

        $user = $this->authUser();
        $response = null;
        $message = '';

        DB::transaction(function () use ($request, $user, &$response, &$message) {
            // Lock order row for update to prevent concurrent duplicate dispatches
            /** @var Order $order */
            $order = Order::with('items.product')->where('id', $request->order_id)->lockForUpdate()->first();

            if (!$order || $order->dispatch_status === 'DONE') {
                $response = response()->json(['success' => false, 'message' => 'Order already fully dispatched or invalid.'], 422);
                return;
            }

            // Validate each item and stock availability under transaction lock
            foreach ($request->items as $dispatchItem) {
                $orderItem = $order->items->firstWhere('id', $dispatchItem['order_item_id']);
                if (!$orderItem) {
                    $response = response()->json(['success' => false, 'message' => 'Invalid order item.'], 422);
                    return;
                }

                $dispatchQty = (float) $dispatchItem['quantity'];
                $remaining   = $orderItem->remainingQty();

                if ($dispatchQty > $remaining) {
                    $response = response()->json([
                        'success' => false,
                        'message' => "Cannot dispatch {$dispatchQty} kg of {$orderItem->product?->name}. Remaining: {$remaining} kg"
                    ], 422);
                    return;
                }

                if (!empty($dispatchItem['location_splits'])) {
                    $totalAllocated = collect($dispatchItem['location_splits'])->sum(fn($s) => (float)$s['dispatch_location_qty']);
                    if (abs($totalAllocated - $dispatchQty) > 0.001) {
                        $response = response()->json([
                            'success' => false,
                            'message' => "Location allocation total ({$totalAllocated} kg) doesn't match dispatch quantity ({$dispatchQty} kg)"
                        ], 422);
                        return;
                    }

                    foreach ($dispatchItem['location_splits'] as $split) {
                        $locationName = $split['location_key'];
                        $allocQty = (float) $split['dispatch_location_qty'];
                        $locationId = \App\Models\Location::firstOrCreate(['name' => $locationName])->id;

                        $availableAtLocation = DB::table('stocks')
                            ->where('product_id', $orderItem->product_id)
                            ->where('stage', $orderItem->product->type)
                            ->where('grade', $orderItem->grade)
                            ->where('location_id', $locationId)
                            ->lockForUpdate()
                            ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")
                            ->value('net') ?? 0;

                        if ($allocQty > $availableAtLocation) {
                            $response = response()->json([
                                'success' => false,
                                'message' => "Insufficient stock at location. Need: {$allocQty} kg, Have: {$availableAtLocation} kg"
                            ], 422);
                            return;
                        }
                    }
                } else {
                    $available = DB::table('stocks')
                        ->where('product_id', $orderItem->product_id)
                        ->where('stage', $orderItem->product->type)
                        ->where('grade', $orderItem->grade)
                        ->lockForUpdate()
                        ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")
                        ->value('net') ?? 0;

                    if ($dispatchQty > $available) {
                        $pName = $orderItem->product?->name;
                        $response = response()->json([
                            'success' => false,
                            'message' => "Insufficient stock for {$pName} ({$orderItem->grade}). Need: {$dispatchQty} kg, Have: {$available} kg"
                        ], 422);
                        return;
                    }
                }
            }
            // Handle LR image
            $lrPath = null;
            if ($request->lr_image) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->lr_image));
                $lrPath    = 'lr_images/' . uniqid('LR_') . '.jpg';
                file_put_contents(public_path($lrPath), $imageData);
            }

            // Create dispatch log for this round
            $dispatchLog = DispatchLog::create([
                'user_id'        => $user['id'],
                'order_id'       => $order->id,
                'transporter_id' => $request->transporter_id ?? $order->transporter_id,
                'lr_image_path'  => $lrPath,
                'driver_no'      => $request->driver_no,
                'lr_no'          => $request->lr_no,
                'notes'          => $request->notes,
            ]);

            // Process each item
            foreach ($request->items as $dispatchItem) {
                $orderItem   = $order->items->firstWhere('id', $dispatchItem['order_item_id']);
                $dispatchQty = (float) $dispatchItem['quantity'];

                // Record what was dispatched in this round
                $dispatchLogItem = \App\Models\DispatchLogItem::create([
                    'dispatch_log_id' => $dispatchLog->id,
                    'order_item_id'   => $orderItem->id,
                    'quantity'        => $dispatchQty,
                ]);

                $locNotes = '';
                $locationSplits = $dispatchItem['location_splits'] ?? [];

                if (!empty($locationSplits)) {
                    // Deduct from specific locations and track allocations
                    foreach ($locationSplits as $split) {
                        $locationName = $split['location_key'];
                        $allocQty = (float) $split['dispatch_location_qty'];
                        $locationId = \App\Models\Location::firstOrCreate(['name' => $locationName])->id;

                        // Create stock OUT transaction for this location
                        $stock = Stock::create([
                            'product_id'       => $orderItem->product_id,
                            'user_id'          => $user['id'],
                            'stage'            => $orderItem->product->type,
                            'grade'            => $orderItem->grade,
                            'location_id'      => $locationId,
                            'quantity'         => $allocQty,
                            'transaction_type' => 'OUT',
                            'notes'            => "Dispatched: Order #{$order->id} from Location #{$locationId}",
                        ]);

                        // Record location allocation
                        \App\Models\DispatchItemLocation::create([
                            'dispatch_log_item_id' => $dispatchLogItem->id,
                            'location_id'          => $locationId,
                            'quantity'             => $allocQty,
                            'stock_id'             => $stock->id,
                        ]);

                        $locNotes .= "{$allocQty}kg from Loc#{$locationId}, ";
                    }
                    $locNotes = rtrim($locNotes, ', ');
                    $locNotes = " [" . $locNotes . "]";
                } else {
                    // Deduct from total stock without location tracking
                    Stock::deductStock(
                        $orderItem->product_id,
                        $orderItem->product->type,
                        $orderItem->grade,
                        $dispatchQty,
                        $user['id'],
                        "Dispatched: Order #{$order->id} (Partial round #{$dispatchLog->id}){$locNotes}"
                    );
                }

                // Update dispatched_qty on the order item
                $orderItem->increment('dispatched_qty', $dispatchQty);
            }

            // Check if ALL items in the order are now fully dispatched
            $order->refresh();
            $allDone = $order->items->every(fn($item) => $item->remainingQty() <= 0);

            if ($allDone) {
                $order->update(['status' => 'CLOSED', 'dispatch_status' => 'DONE']);
                $message = 'Order fully dispatched! All items delivered.';
            } else {
                $order->update(['dispatch_status' => 'PARTIAL']);
                $message = 'Partial dispatch recorded. Remaining items can be dispatched in the next round.';
            }
        });

        if ($response) {
            return $response;
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function updateDispatch(Request $request, $id)
    {
        $request->validate([
            'items'                       => 'required|array|min:1',
            'items.*.dispatch_item_id'    => 'required|exists:dispatch_log_items,id',
            'items.*.quantity'            => 'required|numeric|min:0.001',
            'items.*.location_splits'     => 'nullable|array',
            'items.*.location_splits.*.location_key' => 'required_with:items.*.location_splits|string',
            'items.*.location_splits.*.dispatch_location_qty' => 'required_with:items.*.location_splits|numeric|min:0.001',
        ]);

        $user = $this->authUser();
        $log = DispatchLog::with('order.items', 'dispatchItems')->findOrFail($id);
        $order = $log->order;

        // Restore stock from original dispatch
        DB::transaction(function () use ($request, $user, $log, $order, $id) {
            // Delete existing stock OUT transactions for this dispatch
            $stockIds = \App\Models\DispatchLogItem::where('dispatch_log_id', $log->id)
                ->with('locationAllocations')
                ->get()
                ->flatMap(fn($di) => $di->locationAllocations->pluck('stock_id'))
                ->unique()
                ->toArray();

            Stock::whereIn('id', array_filter($stockIds))->delete();

            // Restore dispatched_qty on order items
            foreach ($log->dispatchItems as $di) {
                $di->orderItem->decrement('dispatched_qty', $di->quantity);
            }

            // Delete dispatch item locations
            \App\Models\DispatchItemLocation::whereIn('dispatch_log_item_id', 
                $log->dispatchItems->pluck('id')->toArray()
            )->delete();

            // Process new dispatch items
            $totalDispatched = 0;
            foreach ($request->items as $dispatchItem) {
                $dispatchLogItem = $log->dispatchItems->firstWhere('id', $dispatchItem['dispatch_item_id']);
                if (!$dispatchLogItem) continue;

                $newQty = (float) $dispatchItem['quantity'];
                $orderItem = $dispatchLogItem->orderItem;
                
                // Validate against remaining qty
                $remaining = $orderItem->quantity - (float) $orderItem->dispatched_qty + (float) $dispatchLogItem->quantity;
                if ($newQty > $remaining) {
                    throw new \Exception("Cannot update to {$newQty} kg. Available: {$remaining} kg");
                }

                // Update dispatch log item quantity
                $dispatchLogItem->update(['quantity' => $newQty]);
                $totalDispatched += $newQty;

                // Create stock transactions per location
                $locationSplits = $dispatchItem['location_splits'] ?? [];
                if (!empty($locationSplits)) {
                    foreach ($locationSplits as $split) {
                        $locationName = $split['location_key'];
                        $allocQty = (float) $split['dispatch_location_qty'];
                        $locationId = \App\Models\Location::firstOrCreate(['name' => $locationName])->id;

                        $stock = Stock::create([
                            'product_id'       => $orderItem->product_id,
                            'user_id'          => $user['id'],
                            'stage'            => $orderItem->product->type,
                            'grade'            => $orderItem->grade,
                            'location_id'      => $locationId,
                            'quantity'         => $allocQty,
                            'transaction_type' => 'OUT',
                            'notes'            => "Dispatch Updated: Order #{$order->id} from Location #{$locationId}",
                        ]);

                        \App\Models\DispatchItemLocation::create([
                            'dispatch_log_item_id' => $dispatchLogItem->id,
                            'location_id'          => $locationId,
                            'quantity'             => $allocQty,
                            'stock_id'             => $stock->id,
                        ]);
                    }
                } else {
                    // Create single stock transaction without location
                    Stock::deductStock(
                        $orderItem->product_id,
                        $orderItem->product->type,
                        $orderItem->grade,
                        $newQty,
                        $user['id'],
                        "Dispatch Updated: Order #{$order->id} (No specific location)"
                    );
                }

                // Update order item dispatched_qty
                $orderItem->increment('dispatched_qty', $newQty);
            }

            // Check if order is now fully dispatched
            $order->refresh();
            $allDone = $order->items->every(fn($item) => $item->remainingQty() <= 0);

            if ($allDone) {
                $order->update(['status' => 'CLOSED', 'dispatch_status' => 'DONE']);
            } else {
                $anyDispatched = $order->items->filter(fn($item) => $item->dispatched_qty > 0)->isNotEmpty();
                $order->update([
                    'status' => 'OPEN',
                    'dispatch_status' => $anyDispatched ? 'PARTIAL' : 'PENDING'
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Dispatch updated successfully!']);
    }

    public function updateLR(Request $request)
    {
        $request->validate([
            'log_id'   => 'required|exists:dispatch_logs,id',
            'lr_image' => 'required|string',
        ]);

        $log = DispatchLog::findOrFail($request->log_id);

        // Handle LR image - save base64 as file
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->lr_image));
        $lrPath    = 'lr_images/' . uniqid('LR_') . '.jpg';
        file_put_contents(public_path($lrPath), $imageData);

        // Delete old image if exists
        if ($log->lr_image_path && file_exists(public_path($log->lr_image_path))) {
            @unlink(public_path($log->lr_image_path));
        }

        DB::transaction(function() use ($log, $lrPath) {
            $log->update(['lr_image_path' => $lrPath]);
        });

        return response()->json([
            'success' => true, 
            'message' => 'LR Copy updated successfully!',
            'lr_url'  => asset($lrPath)
        ]);
    }

    public function history()
    {
        $logs = DispatchLog::with(['order.company', 'order.transporter', 'user', 'dispatchItems.orderItem.product'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($d) => [
                'id'            => $d->id,
                'orderId'       => $d->order_id,
                'companyName'   => $d->order?->company?->name,
                'transportName' => $d->transporter?->name ?? $d->order?->transporter?->name,
                'dispatchedBy'  => $d->user?->name,
                'lrImage'       => $d->lr_image_path ? asset($d->lr_image_path) : null,
                'orderTotal'    => $d->order?->total,
                'date'          => $d->created_at->toISOString(),
                'notes'         => $d->notes ?: $d->order?->notes,
                'dispatchNotes' => $d->notes,
                'orderNotes'    => $d->order?->notes,
                'items'         => $d->dispatchItems->map(fn($di) => [
                    'productName' => $di->orderItem?->product ? $di->orderItem->product->formatName($di->orderItem->grade) : 'Unknown',
                    'grade'       => $di->orderItem?->grade,
                    'productType' => $di->orderItem?->product?->type,
                    'quantity'    => (float) $di->quantity,
                ]),
            ]);

        $pageData = ['dispatchLogs' => $logs];
        return view('dispatch.history', compact('pageData'));
    }

    public function profile()
    {
        return view('dispatch.profile');
    }
    public function revertDispatch(Request $request, $id)
    {
        $log = DispatchLog::with('order.items', 'dispatchItems')->findOrFail($id);
        $order = $log->order;

        if ($order->status === 'CANCELLED') {
            return response()->json(['success' => false, 'message' => 'Cannot revert dispatch for a cancelled order.'], 400);
        }

        DB::transaction(function () use ($log, $order) {
            // Collect stock IDs from locationAllocations
            $stockIdsFromLocations = \App\Models\DispatchItemLocation::whereIn(
                'dispatch_log_item_id', 
                $log->dispatchItems->pluck('id')->toArray()
            )->pluck('stock_id')->toArray();

            // Collect stock IDs created via deductStock with round notes
            $stockIdsFromNotes = Stock::where('notes', 'LIKE', "%round #{$log->id}%")
                ->orWhere('notes', 'LIKE', "%round #{$log->id})%")
                ->pluck('id')
                ->toArray();

            $allStockIds = array_unique(array_filter(array_merge($stockIdsFromLocations, $stockIdsFromNotes)));
            if (!empty($allStockIds)) {
                Stock::whereIn('id', $allStockIds)->delete();
            }

            // Restore dispatched_qty on order items
            foreach ($log->dispatchItems as $di) {
                $di->orderItem->decrement('dispatched_qty', $di->quantity);
            }

            // Delete dispatch item locations
            \App\Models\DispatchItemLocation::whereIn('dispatch_log_item_id', 
                $log->dispatchItems->pluck('id')->toArray()
            )->delete();

            // Delete the dispatch log items
            \App\Models\DispatchLogItem::where('dispatch_log_id', $log->id)->delete();

            // Delete the log image if exists
            if ($log->lr_image_path && file_exists(public_path($log->lr_image_path))) {
                @unlink(public_path($log->lr_image_path));
            }

            // Delete the dispatch log itself
            $log->delete();

            // Update order status accurately
            $order->refresh();
            $anyDispatched = $order->items->filter(fn($item) => $item->dispatched_qty > 0)->isNotEmpty();
            $allDone = $order->items->isNotEmpty() && $order->items->every(fn($item) => $item->remainingQty() <= 0);
            
            $order->update([
                'status' => 'OPEN',
                'dispatch_status' => $allDone ? 'DONE' : ($anyDispatched ? 'PARTIAL' : 'PENDING')
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Dispatch reverted successfully!']);
    }
}
