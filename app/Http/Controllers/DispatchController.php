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
        $pending   = Order::with(['company', 'transporter', 'items.product', 'creator'])
            ->where(function($q) {
                $q->whereNotIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY DISPATCHED'])
                  ->orWhereNull('dispatch_status');
            })
            ->orderByDesc('created_at')
            ->get();
        $completed = Order::with(['company', 'transporter', 'creator', 'items.product'])->whereIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY DISPATCHED'])->orderByDesc('created_at')->get();

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

        $packagingStock = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.stage', 'PACKAGING')
            ->groupBy('stocks.product_id', 'stocks.grade', 'products.name', 'products.unit')
            ->selectRaw("stocks.product_id as id, products.name, stocks.grade, products.unit, SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->get();

        $liveStocks = DB::table('stocks')
            ->selectRaw("product_id, grade, SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as net_qty")
            ->groupBy('product_id', 'grade')
            ->get();

        $stockMap = [];
        foreach ($liveStocks as $ls) {
            $g = $ls->grade ?: 'NONE';
            $key = $ls->product_id . '_' . $g;
            $stockMap[$key] = ($stockMap[$key] ?? 0) + max(0, (float)$ls->net_qty);

            $allKey = $ls->product_id . '_ALL';
            $stockMap[$allKey] = ($stockMap[$allKey] ?? 0) + max(0, (float)$ls->net_qty);
        }

        $pageData = [
            'rawStock'        => $rawStock,
            'semiStock'       => $semiStock,
            'finishedStock'   => $finishedStock,
            'packagingStock'  => $packagingStock,
            'pendingOrders'   => $pending->map(function($o) use ($stockMap) {
                $items = $o->items->map(function($i) use ($stockMap) {
                    $needed = max(0, (float)$i->quantity - (float)$i->dispatched_qty);
                    $g = $i->grade ?: 'NONE';
                    $key = $i->product_id . '_' . $g;
                    $avail = $stockMap[$key] ?? $stockMap[$i->product_id . '_ALL'] ?? 0;
                    return [
                        'id'            => $i->id,
                        'productId'     => $i->product_id,
                        'rawProductName'=> $i->product?->name ?? 'Unknown',
                        'productName'   => $i->product?->name ?? 'Unknown',
                        'formattedName' => $i->product ? $i->product->formatName($i->grade) : 'Unknown',
                        'productType'   => $i->product?->type,
                        'quantity'      => (float) $i->quantity,
                        'dispatchedQty' => (float) $i->dispatched_qty,
                        'remainingQty'  => $needed,
                        'availStock'    => $avail,
                        'grade'         => $i->grade,
                    ];
                });

                $remainingItems = $items->filter(fn($i) => $i['remainingQty'] > 0);
                $totalRemainingItems = $remainingItems->count();

                $fullCount = 0;
                $partialCount = 0;

                foreach ($remainingItems as $ri) {
                    if ($ri['availStock'] >= $ri['remainingQty']) {
                        $fullCount++;
                        $partialCount++;
                    } elseif ($ri['availStock'] > 0) {
                        $partialCount++;
                    }
                }

                if ($totalRemainingItems == 0) {
                    $readiness = 'DONE';
                } elseif ($fullCount === $totalRemainingItems) {
                    $readiness = 'READY_DISPATCH';
                } elseif ($partialCount > 0) {
                    $readiness = 'READY_PARTIAL';
                } else {
                    $readiness = 'NOT_READY';
                }

                return [
                    'id'           => $o->id,
                    'companyId'    => $o->company_id,
                    'companyName'  => $o->company?->name,
                    'transportId'  => $o->transporter_id,
                    'transporterName' => $o->transporter?->name,
                    'salesPerson'  => $o->creator?->name ?? 'N/A',
                    'total'        => $o->total,
                    'date'         => $o->created_at ? $o->created_at->toISOString() : ($o->date ? \Carbon\Carbon::parse($o->date)->toISOString() : now()->toISOString()),
                    'dueDate'      => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('d-m-Y') : null,
                    'rawDueDate'   => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('Y-m-d') : null,
                    'totalQty'     => $o->items->sum('quantity'),
                    'dispatchedQty'=> $o->items->sum('dispatched_qty'),
                    'dispatchStatus' => $o->dispatch_status,
                    'readiness'    => $readiness,
                    'notes'        => $o->notes,
                    'items'        => $items,
                ];
            }),
            'completedOrders' => $completed->map(fn($o) => [
                'id'           => $o->id,
                'companyId'    => $o->company_id,
                'companyName'  => $o->company?->name,
                'transportId'  => $o->transporter_id,
                'transporterName' => $o->transporter?->name,
                'salesPerson'  => $o->creator?->name ?? 'N/A',
                'total'        => $o->total,
                'date'         => $o->created_at ? $o->created_at->toISOString() : ($o->date ? \Carbon\Carbon::parse($o->date)->toISOString() : now()->toISOString()),
                'dueDate'      => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('d-m-Y') : null,
                'rawDueDate'   => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('Y-m-d') : null,
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
                    'remainingQty'  => (float) max(0, (float)$i->quantity - (float)$i->dispatched_qty),
                    'grade'         => $i->grade,
                ]),
            ]),
            'companies'           => Company::all(['id', 'name']),
            'transportCompanies'  => Transporter::all(['id', 'name']),
            'products'            => Product::active()->get(['id', 'name', 'unit', 'type']),
        ];
        return view('dispatch.home', compact('pageData'));
    }

    public function action()
    {
        $pendingOrders = Order::with(['company', 'transporter', 'items.product', 'creator'])
            ->where(function($q) {
                $q->whereNotIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY DISPATCHED'])
                  ->orWhereNull('dispatch_status');
            })
            ->orderByDesc('created_at')
            ->get();

        foreach ($pendingOrders as $po) {
            foreach ($po->items as $pi) {
                $pi->syncDispatchedQty();
            }
        }

        $pageData = [
            'pendingOrders' => $pendingOrders->map(fn($o)=>[
                'id'          => $o->id,
                'notes'       => $o->notes,
                'salesPerson' => $o->creator?->name ?? 'N/A',
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

        return response()
            ->view('dispatch.action', compact('pageData'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function getOrderDetails($id)
    {
        $order = Order::with(['company', 'transporter', 'items.product', 'creator'])->find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        foreach ($order->items as $item) {
            $item->syncDispatchedQty();
        }

        $items = $order->items->map(fn($i) => [
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
        ]);

        return response()->json([
            'success' => true,
            'order'   => [
                'id'          => $order->id,
                'notes'       => $order->notes,
                'salesPerson' => $order->creator?->name ?? 'N/A',
                'company'     => [
                    'name'    => $order->company?->name,
                    'gst'     => $order->company?->gst,
                    'contact' => $order->company?->contact,
                    'address' => $order->company?->address,
                ],
                'transporter' => [
                    'name'     => $order->transporter?->name,
                    'gst'      => $order->transporter?->gst,
                    'contact'  => $order->transporter?->contact,
                    'vehicles' => $order->transporter?->vehicles,
                ],
                'items'       => $items,
            ]
        ]);
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
            'driver_number'               => 'nullable|string',
            'vehicle_no'                  => 'nullable|string',
            'vehicle_number'              => 'nullable|string',
            'lr_no'                       => 'nullable|string',
            'notes'                       => 'nullable|string',
            'transporter_id'              => 'nullable|exists:transporters,id',
        ]);

        // Rapid concurrent submission prevention (atomic lock per order)
        $lock = null;
        try {
            $lock = \Illuminate\Support\Facades\Cache::lock("dispatch_lock_order_{$request->order_id}", 5);
            if (!$lock->get()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Another dispatch request is currently in progress. Please wait a moment.'
                ], 429);
            }
        } catch (\Throwable $e) {
            $lock = null;
        }

        $user = $this->authUser();
        $response = null;
        $message = '';

        try {
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

                // Sync dispatched_qty with actual recorded dispatch log items
                $orderItem->syncDispatchedQty();

                $dispatchQty   = (float) $dispatchItem['quantity'];
                $remaining     = $orderItem->remainingQty();
                $itemGrade     = (!empty($orderItem->grade) && $orderItem->grade !== 'NONE') ? $orderItem->grade : 'NONE';
                $itemStage     = !empty($orderItem->product?->type) ? $orderItem->product->type : 'RAW';
                $formattedName = $orderItem->product ? $orderItem->product->formatName($orderItem->grade) : ($orderItem->product?->name ?? 'Product');
                $unit          = $orderItem->product?->unit ?? 'kg';

                if ($dispatchQty > $remaining) {
                    $response = response()->json([
                        'success' => false,
                        'message' => "Cannot dispatch {$dispatchQty} {$unit} of {$formattedName}. Remaining pending order: {$remaining} {$unit} (Total: {$orderItem->quantity} {$unit}, Dispatched: {$orderItem->dispatched_qty} {$unit}).",
                        'order_id' => $order->id,
                        'item_id' => $orderItem->id,
                        'remaining_qty' => $remaining,
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
                            ->where('stage', $itemStage)
                            ->where(function($q) use ($itemGrade) {
                                if ($itemGrade === 'NONE') {
                                    $q->where('grade', 'NONE')->orWhereNull('grade')->orWhere('grade', '');
                                } else {
                                    $q->where('grade', $itemGrade);
                                }
                            })
                            ->where('location_id', $locationId)
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
                        ->where('stage', $itemStage)
                        ->where(function($q) use ($itemGrade) {
                            if ($itemGrade === 'NONE') {
                                $q->where('grade', 'NONE')->orWhereNull('grade')->orWhere('grade', '');
                            } else {
                                $q->where('grade', $itemGrade);
                            }
                        })
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
                $dir = public_path('lr_images');
                if (!file_exists($dir)) {
                    @mkdir($dir, 0777, true);
                }
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->lr_image));
                $lrPath    = 'lr_images/' . uniqid('LR_') . '.jpg';
                @file_put_contents(public_path($lrPath), $imageData);
            }
            $dispatchTransporterId = $request->transporter_id ?? $order->transporter_id;
            if (!$dispatchTransporterId) {
                try {
                    $fallback = \App\Models\Transporter::firstOrCreate(['name' => 'N/A'], ['contact' => '—', 'gst' => '—']);
                    $dispatchTransporterId = $fallback->id;
                } catch (\Throwable $ignored) {}
            }

            $driverNo = $request->driver_no ?? $request->driver_number;
            $vehicleNo = $request->vehicle_number ?? $request->vehicle_no;

            // If vehicle or driver contact provided, ensure transporter has it
            if ($dispatchTransporterId) {
                $transporter = \App\Models\Transporter::find($dispatchTransporterId);
                if ($transporter) {
                    $updates = [];
                    if (!empty($vehicleNo) && (empty($transporter->vehicles) || $transporter->vehicles === '—' || $transporter->vehicles === 'N/A')) {
                        $updates['vehicles'] = $vehicleNo;
                    }
                    if (!empty($driverNo) && (empty($transporter->contact) || $transporter->contact === '—' || $transporter->contact === 'N/A')) {
                        $updates['contact'] = $driverNo;
                    }
                    if (!empty($updates)) {
                        $transporter->update($updates);
                    }
                }
            }

            // Create dispatch log for this round
            $dispatchLogData = [
                'user_id'        => $user['id'],
                'order_id'       => $order->id,
                'transporter_id' => $dispatchTransporterId,
                'lr_image_path'  => $lrPath,
                'driver_no'      => $driverNo,
                'lr_no'          => $request->lr_no,
                'notes'          => $request->notes,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('dispatch_logs', 'vehicle_no')) {
                $dispatchLogData['vehicle_no'] = $vehicleNo;
            }
            $dispatchLog = DispatchLog::create($dispatchLogData);

            // Process each item
            foreach ($request->items as $dispatchItem) {
                $orderItem   = $order->items->firstWhere('id', $dispatchItem['order_item_id']);
                $dispatchQty = (float) $dispatchItem['quantity'];
                $itemGrade   = (!empty($orderItem->grade) && $orderItem->grade !== 'NONE') ? $orderItem->grade : 'NONE';
                $itemStage   = !empty($orderItem->product?->type) ? $orderItem->product->type : 'RAW';

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
                            'stage'            => $itemStage,
                            'grade'            => $itemGrade,
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
                        $itemStage,
                        $itemGrade,
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
                $order->update(['dispatch_status' => 'PARTIAL PENDING']);
                $message = 'Partial dispatch recorded. Remaining items are saved under Partial Pending.';
            }
            });
            if ($response) {
                return $response;
            }

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Dispatch store error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'order_id' => $request->order_id,
                'items' => $request->items
            ]);
            return response()->json(['success' => false, 'message' => 'Dispatch error: ' . $e->getMessage()], 500);
        } finally {
            if ($lock) {
                try {
                    $lock->release();
                } catch (\Throwable $e) {}
            }
        }
    }

    public function updateDispatch(Request $request, $id)
    {
        $user = $this->authUser();
        if (!empty($user['is_view_only'])) {
            return response()->json(['success' => false, 'message' => 'You have View-Only permission. Updating dispatch is disabled.'], 403);
        }

        $request->validate([
            'items'                                           => 'required|array|min:1',
            'items.*.dispatch_item_id'                        => 'required|exists:dispatch_log_items,id',
            'items.*.quantity'                                => 'required|numeric|min:0.001',
            'items.*.location_splits'                         => 'nullable|array',
            'items.*.location_splits.*.location_key'          => 'required_with:items.*.location_splits|string',
            'items.*.location_splits.*.dispatch_location_qty' => 'required_with:items.*.location_splits|numeric|min:0.001',
            'notes'                                           => 'nullable|string',
        ]);

        $log = DispatchLog::with(['order.items', 'dispatchItems.orderItem.product'])->findOrFail($id);
        $order = $log->order;

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Associated order not found.'], 404);
        }

        if ($order->status === 'CANCELLED') {
            return response()->json(['success' => false, 'message' => 'Cannot update dispatch for a cancelled order.'], 422);
        }

        try {
            DB::transaction(function () use ($request, $user, $log, $order) {
                // 1. Collect and delete all stock OUT records created for this dispatch log
                $stockIdsFromLocations = \App\Models\DispatchItemLocation::whereIn(
                    'dispatch_log_item_id', 
                    $log->dispatchItems->pluck('id')->toArray()
                )->pluck('stock_id')->toArray();

                $stockIdsFromNotes = Stock::where('notes', 'LIKE', "%round #{$log->id}%")
                    ->orWhere('notes', 'LIKE', "%round #{$log->id})%")
                    ->pluck('id')
                    ->toArray();

                $allStockIds = array_unique(array_filter(array_merge($stockIdsFromLocations, $stockIdsFromNotes)));
                if (!empty($allStockIds)) {
                    Stock::whereIn('id', $allStockIds)->delete();
                }

                // Delete previous location allocations
                \App\Models\DispatchItemLocation::whereIn(
                    'dispatch_log_item_id', 
                    $log->dispatchItems->pluck('id')->toArray()
                )->delete();

                // 2. Temporarily decrement order item dispatched_qty by the previous round quantities
                foreach ($log->dispatchItems as $di) {
                    if ($di->orderItem) {
                        $di->orderItem->decrement('dispatched_qty', $di->quantity);
                        $di->orderItem->refresh();
                    }
                }

                // 3. Process new dispatch quantities
                foreach ($request->items as $dispatchItem) {
                    $dispatchLogItem = $log->dispatchItems->firstWhere('id', $dispatchItem['dispatch_item_id']);
                    if (!$dispatchLogItem) {
                        throw new \Exception("Invalid dispatch log item #{$dispatchItem['dispatch_item_id']}.");
                    }

                    $newQty = (float) $dispatchItem['quantity'];
                    if ($newQty <= 0) {
                        throw new \Exception("Dispatch quantity must be greater than 0.");
                    }

                    $orderItem = $dispatchLogItem->orderItem;
                    if (!$orderItem) {
                        throw new \Exception("Order item not found for dispatch item #{$dispatchLogItem->id}.");
                    }

                    $orderItem->refresh();
                    // Remaining order quantity available for this item
                    $maxAllowed = (float) $orderItem->quantity - (float) $orderItem->dispatched_qty;
                    if ($newQty > $maxAllowed + 0.0001) {
                        $formattedName = $orderItem->product ? $orderItem->product->formatName($orderItem->grade) : ($orderItem->product?->name ?? 'Product');
                        throw new \Exception("Cannot dispatch {$newQty} kg of {$formattedName}. Maximum allowed for this round: " . round($maxAllowed, 3) . " kg.");
                    }

                    // Check stock inventory availability
                    $itemGrade = (!empty($orderItem->grade) && $orderItem->grade !== 'NONE') ? $orderItem->grade : 'NONE';
                    $itemStage = !empty($orderItem->product?->type) ? $orderItem->product->type : 'FINISHED';

                    $availableStock = DB::table('stocks')
                        ->where('product_id', $orderItem->product_id)
                        ->where('stage', $itemStage)
                        ->where(function($q) use ($itemGrade) {
                            if ($itemGrade === 'NONE') {
                                $q->where('grade', 'NONE')->orWhereNull('grade')->orWhere('grade', '');
                            } else {
                                $q->where('grade', $itemGrade);
                            }
                        })
                        ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")
                        ->value('net') ?? 0;

                    if ($newQty > (float) $availableStock + 0.0001) {
                        $pName = $orderItem->product?->name ?? 'Product';
                        throw new \Exception("Insufficient stock in inventory for {$pName} ({$orderItem->grade}). Requested: {$newQty} kg, Available: " . round($availableStock, 3) . " kg.");
                    }

                    // Update dispatch log item quantity
                    $dispatchLogItem->update(['quantity' => $newQty]);

                    // Deduct new stock with location splits or general deductStock
                    $locationSplits = $dispatchItem['location_splits'] ?? [];
                    if (!empty($locationSplits)) {
                        foreach ($locationSplits as $split) {
                            $locationName = $split['location_key'];
                            $allocQty = (float) ($split['dispatch_location_qty'] ?? $split['dispatch_qty'] ?? 0);
                            if ($allocQty <= 0) continue;
                            $locationId = \App\Models\Location::firstOrCreate(['name' => $locationName])->id;

                            $stock = Stock::create([
                                'product_id'       => $orderItem->product_id,
                                'user_id'          => $user['id'],
                                'stage'            => $itemStage,
                                'grade'            => $itemGrade,
                                'location_id'      => $locationId,
                                'quantity'         => $allocQty,
                                'transaction_type' => 'OUT',
                                'notes'            => "Dispatched: Order #{$order->id} (Partial round #{$log->id}) from Loc#{$locationId}",
                            ]);

                            \App\Models\DispatchItemLocation::create([
                                'dispatch_log_item_id' => $dispatchLogItem->id,
                                'location_id'          => $locationId,
                                'quantity'             => $allocQty,
                                'stock_id'             => $stock->id,
                            ]);
                        }
                    } else {
                        Stock::deductStock(
                            $orderItem->product_id,
                            $itemStage,
                            $itemGrade,
                            $newQty,
                            $user['id'],
                            "Dispatched: Order #{$order->id} (Partial round #{$log->id})"
                        );
                    }

                    // Increment order item dispatched_qty with new quantity
                    $orderItem->increment('dispatched_qty', $newQty);
                    $orderItem->refresh();
                }

                // Update notes if provided
                if ($request->has('notes')) {
                    $log->update(['notes' => $request->notes]);
                }

                // 4. Recalculate order status accurately
                $order->refresh();
                $order->load('items');
                $anyDispatched = $order->items->filter(fn($item) => (float) $item->dispatched_qty > 0)->isNotEmpty();
                $allDone = $order->items->isNotEmpty() && $order->items->every(fn($item) => $item->remainingQty() <= 0.0001);

                $order->update([
                    'status'          => $allDone ? 'CLOSED' : 'OPEN',
                    'dispatch_status' => $allDone ? 'DONE' : ($anyDispatched ? 'PARTIAL PENDING' : 'PENDING')
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }

        return response()->json(['success' => true, 'message' => 'Dispatch quantity updated successfully!']);
    }

    public function updateLR(Request $request)
    {
        $request->validate([
            'log_id'   => 'nullable',
            'order_id' => 'nullable',
            'lr_image' => 'required|string',
        ]);

        $log = null;
        if ($request->filled('log_id') && is_numeric($request->log_id)) {
            $log = DispatchLog::find($request->log_id);
        }
        if (!$log && $request->filled('order_id') && is_numeric($request->order_id)) {
            $log = DispatchLog::where('order_id', $request->order_id)->latest('id')->first();
        }
        // If log_id was actually passed as an order_id fallback:
        if (!$log && $request->filled('log_id') && is_numeric($request->log_id)) {
            $log = DispatchLog::where('order_id', $request->log_id)->latest('id')->first();
        }

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Dispatch log record not found for updating LR.',
            ], 404);
        }

        // Handle LR image - save base64 as file
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->lr_image));
        $dir = public_path('lr_images');
        if (!file_exists($dir)) {
            @mkdir($dir, 0777, true);
        }
        $lrPath = 'lr_images/' . uniqid('LR_') . '.jpg';
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
            'message' => 'LR Copy updated successfully for Order #' . $log->order_id . '!',
            'lr_url'  => asset($lrPath),
            'order_id'=> $log->order_id,
            'log_id'  => $log->id,
        ]);
    }

    /**
     * Helper to retrieve all image paths associated with a dispatch log.
     */
    public static function getLrImagePaths($log): array
    {
        if (!$log || empty($log->lr_image_path)) {
            return [];
        }
        $raw = trim($log->lr_image_path);
        if (str_starts_with($raw, '[') && str_ends_with($raw, ']')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded));
            }
        }
        if (str_contains($raw, ',')) {
            return array_values(array_filter(array_map('trim', explode(',', $raw))));
        }
        return [$raw];
    }

    /**
     * Helper to resolve physical local file path for an image URL/relative path.
     */
    public static function resolveLocalFilePath(string $rp): ?string
    {
        $cleanRp = $rp;
        if (str_starts_with($cleanRp, 'http://') || str_starts_with($cleanRp, 'https://')) {
            $cleanRp = parse_url($cleanRp, PHP_URL_PATH);
        }
        $relative = ltrim(str_replace('\\', '/', $cleanRp), '/');

        $candidates = [
            public_path($relative),
            storage_path('app/public/' . $relative),
            storage_path($relative),
            base_path($relative),
        ];

        if (str_starts_with($relative, 'public/')) {
            $sub = substr($relative, 7);
            $candidates[] = public_path($sub);
            $candidates[] = storage_path('app/public/' . $sub);
        }

        if (str_starts_with($relative, 'storage/')) {
            $sub = substr($relative, 8);
            $candidates[] = storage_path('app/public/' . $sub);
            $candidates[] = public_path($sub);
        }

        foreach ($candidates as $cand) {
            if (file_exists($cand) && is_file($cand)) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Download LR copy file for a specific dispatch log.
     */
    public function downloadLR($id)
    {
        $log = DispatchLog::findOrFail($id);
        $rawPaths = self::getLrImagePaths($log);

        if (empty($rawPaths) && $log->order && $log->order->dispatchLogs) {
            $otherLog = $log->order->dispatchLogs->first(fn($ol) => !empty($ol->lr_image_path));
            if ($otherLog) {
                $rawPaths = self::getLrImagePaths($otherLog);
                $log = $otherLog;
            }
        }

        if (empty($rawPaths)) {
            return back()->with('error', 'No LR image attached to this dispatch record.');
        }

        $validFiles = [];
        foreach ($rawPaths as $idx => $rp) {
            $fullPath = self::resolveLocalFilePath($rp);
            if ($fullPath) {
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'jpg');
                $lrNo = $log->lr_no ? '_' . preg_replace('/[^A-Za-z0-9_-]/', '', $log->lr_no) : '';
                $suffix = count($rawPaths) > 1 ? "_img" . ($idx + 1) : '';
                $name = "LR_Order_{$log->order_id}_Log_{$log->id}{$lrNo}{$suffix}.{$ext}";
                $validFiles[] = [
                    'path' => $fullPath,
                    'name' => $name,
                ];
            }
        }

        if (empty($validFiles)) {
            return back()->with('error', 'LR image file not found on server.');
        }

        if (count($validFiles) === 1) {
            return response()->download($validFiles[0]['path'], $validFiles[0]['name']);
        }

        // Multiple images in single dispatch - create ZIP
        if (class_exists('ZipArchive')) {
            $tempDir = storage_path('app');
            if (!file_exists($tempDir)) {
                @mkdir($tempDir, 0777, true);
            }
            $zipFileName = "LR_Order_{$log->order_id}_Log_{$log->id}_" . count($validFiles) . '_files.zip';
            $tempZipPath = $tempDir . '/lr_export_' . uniqid() . '.zip';

            $zip = new \ZipArchive();
            if ($zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                foreach ($validFiles as $item) {
                    $zip->addFile($item['path'], $item['name']);
                }
                $zip->close();
                return response()->download($tempZipPath, $zipFileName)->deleteFileAfterSend(true);
            }
        }

        return response()->download($validFiles[0]['path'], $validFiles[0]['name']);
    }

    /**
     * Download multiple LR copies (e.g. 2, 3 or more selected, or all for an order).
     */
    public function downloadMultipleLR(Request $request)
    {
        $ids = [];
        if ($request->filled('ids')) {
            $rawIds = $request->input('ids');
            $ids = is_array($rawIds) ? $rawIds : explode(',', (string) $rawIds);
        } elseif ($request->filled('order_id')) {
            $ids = DispatchLog::where('order_id', $request->order_id)
                ->whereNotNull('lr_image_path')
                ->pluck('id')
                ->toArray();
        }

        $ids = array_values(array_filter(array_map('intval', (array) $ids)));

        if (empty($ids)) {
            return back()->with('error', 'No dispatch records selected for download.');
        }

        $logs = DispatchLog::whereIn('id', $ids)
            ->whereNotNull('lr_image_path')
            ->get();

        $validFiles = [];
        foreach ($logs as $log) {
            $rawPaths = self::getLrImagePaths($log);
            foreach ($rawPaths as $idx => $rp) {
                $fullPath = self::resolveLocalFilePath($rp);
                if ($fullPath) {
                    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'jpg');
                    $lrNo = $log->lr_no ? '_' . preg_replace('/[^A-Za-z0-9_-]/', '', $log->lr_no) : '';
                    $suffix = count($rawPaths) > 1 ? "_img" . ($idx + 1) : '';
                    $name = "LR_Order_{$log->order_id}_Log_{$log->id}{$lrNo}{$suffix}.{$ext}";
                    $validFiles[] = [
                        'path' => $fullPath,
                        'name' => $name,
                    ];
                }
            }
        }

        if (empty($validFiles)) {
            return back()->with('error', 'No LR image files found on server for the selected records.');
        }

        // If only 1 file is selected, download directly
        if (count($validFiles) === 1) {
            return response()->download($validFiles[0]['path'], $validFiles[0]['name']);
        }

        // 2, 3 or more files: bundle into ZIP
        if (class_exists('ZipArchive')) {
            $tempDir = storage_path('app');
            if (!file_exists($tempDir)) {
                @mkdir($tempDir, 0777, true);
            }
            $prefix = $request->filled('order_id') ? "LR_Copies_Order_{$request->order_id}_" : "LR_Copies_Batch_";
            $zipFileName = $prefix . count($validFiles) . '_files_' . date('Ymd_His') . '.zip';
            $tempZipPath = $tempDir . '/lr_export_' . uniqid() . '.zip';

            $zip = new \ZipArchive();
            if ($zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $usedNames = [];
                foreach ($validFiles as $item) {
                    $entryName = $item['name'];
                    $counter = 1;
                    while (in_array($entryName, $usedNames)) {
                        $pInfo = pathinfo($item['name']);
                        $entryName = $pInfo['filename'] . "_{$counter}." . ($pInfo['extension'] ?? 'jpg');
                        $counter++;
                    }
                    $usedNames[] = $entryName;
                    $zip->addFile($item['path'], $entryName);
                }
                $zip->close();

                return response()->download($tempZipPath, $zipFileName)->deleteFileAfterSend(true);
            }
        }

        // Fallback to first file if ZipArchive is unavailable
        return response()->download($validFiles[0]['path'], $validFiles[0]['name']);
    }

    public function history()
    {
        $logs = DispatchLog::with([
            'order.company',
            'order.transporter',
            'order.creator',
            'order.dispatchLogs',
            'user',
            'dispatchItems.orderItem.product'
        ])
            ->whereHas('order', function($q) {
                $q->where('status', '!=', 'CANCELLED');
            })
            ->orderByDesc('created_at')
            ->get();

        // Sync order items dispatched quantities for data consistency
        foreach ($logs as $d) {
            if ($d->order && $d->order->items) {
                foreach ($d->order->items as $oi) {
                    $oi->syncDispatchedQty();
                }
            }
        }

        $logsData = $logs->map(function($d) {
            $rawPaths = self::getLrImagePaths($d);
            $lrImages = array_map(fn($p) => ['path' => $p, 'url' => asset($p)], $rawPaths);
            $ownLr = !empty($lrImages) ? $lrImages[0]['url'] : null;

            // Collect all LR copies for this entire order (across all dispatch rounds, e.g. 2 or 3 rounds)
            $orderLrCopies = [];
            if ($d->order && $d->order->dispatchLogs) {
                foreach ($d->order->dispatchLogs as $ol) {
                    if ($ol->lr_image_path) {
                        $olPaths = self::getLrImagePaths($ol);
                        foreach ($olPaths as $idx => $olp) {
                            $orderLrCopies[] = [
                                'logId'      => $ol->id,
                                'url'        => asset($olp),
                                'path'       => $olp,
                                'lrNo'       => $ol->lr_no,
                                'date'       => $ol->created_at ? $ol->created_at->timezone('Asia/Kolkata')->format('d-m-Y, h:i A') : '',
                                'isCurrent'  => $ol->id === $d->id,
                                'roundIndex' => $idx + 1,
                            ];
                        }
                    }
                }
            }

            // Fallback: if this specific round has no LR image, but the order has an LR uploaded on another round, use that order's LR
            $orderLatestLr = null;
            if (!$ownLr && !empty($orderLrCopies)) {
                $orderLatestLr = $orderLrCopies[0]['url'] ?? null;
            }
            $effectiveLr = $ownLr ?: $orderLatestLr;

            $orderTotalQty = (float) ($d->order?->items?->sum('quantity') ?? 0);
            $orderRemainingQty = (float) ($d->order?->items?->sum(fn($i) => $i->remainingQty()) ?? 0);
            $rawDispStatus = strtoupper(trim((string)($d->order?->dispatch_status ?? '')));
            $isOrderDone = in_array($rawDispStatus, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED']) || ($orderTotalQty > 0 && $orderRemainingQty <= 0);
            $computedStatus = $isOrderDone ? 'DONE' : 'PARTIAL';

            return [
                'id'            => $d->id,
                'isOrderOnly'   => false,
                'orderId'       => $d->order_id,
                'companyId'     => $d->order?->company_id,
                'companyName'   => $d->order?->company?->name,
                'transportName' => $d->transporter?->name ?? $d->order?->transporter?->name,
                'salesPerson'   => $d->order?->creator?->name ?? 'N/A',
                'salesBy'       => $d->order?->creator?->name ?? 'N/A',
                'dispatchedBy'  => $d->user?->name,
                'lrImage'       => $effectiveLr,
                'ownLrImage'    => $ownLr,
                'isOrderLrCopy' => empty($ownLr) && !empty($effectiveLr),
                'lrImages'      => $lrImages,
                'orderLrCopies' => $orderLrCopies,
                'orderTotal'    => $d->order?->total,
                'status'        => $d->order?->status,
                'dispatchStatus'=> $computedStatus,
                'date'          => $d->created_at ? $d->created_at->toISOString() : ($d->date ? \Carbon\Carbon::parse($d->date)->toISOString() : now()->toISOString()),
                'notes'         => $d->notes ?: $d->order?->notes,
                'dispatchNotes' => $d->notes,
                'orderNotes'    => $d->order?->notes,
                'items'         => $d->dispatchItems->filter(fn($di) => $di->orderItem && $di->orderItem->order_id == $d->order_id)->map(fn($di) => [
                    'id'            => $di->id,
                    'dispatchItemId'=> $di->id,
                    'orderItemId'   => $di->order_item_id,
                    'productName'   => $di->orderItem?->product?->name ?? 'Unknown',
                    'rawProductName'=> $di->orderItem?->product?->name ?? 'Unknown',
                    'formattedName' => $di->orderItem?->product ? $di->orderItem->product->formatName($di->orderItem->grade) : 'Unknown',
                    'grade'         => $di->orderItem?->grade,
                    'productType'   => $di->orderItem?->product?->type,
                    'totalQty'      => (float) ($di->orderItem?->quantity ?? 0),
                    'dispatchedQty' => (float) $di->quantity,
                    'remainingQty'  => (float) max(0, ($di->orderItem?->quantity ?? 0) - ($di->orderItem?->dispatched_qty ?? 0)),
                ])->values(),
            ];
        });

        $allData = $logsData;

        $companies = Company::orderBy('name')->get()->map(fn($c) => [
            'id' => $c->id,
            'name' => strtoupper($c->name ?? '')
        ]);

        $pageData = [
            'dispatchLogs' => $allData,
            'companies'    => $companies,
        ];
        return view('dispatch.history', compact('pageData'));
    }

    public function report()
    {
        $orders = Order::with(['company', 'transporter', 'items.product', 'dispatchLogs', 'creator'])
            ->where('status', '!=', 'CANCELLED')
            ->orderByDesc('created_at')
            ->get();

        $reportOrders = collect();

        foreach ($orders as $o) {
            $totalQty = (float) $o->items->sum('quantity');
            $dispatchedQty = (float) $o->items->sum('dispatched_qty');
            $remainingQty = (float) $o->items->sum(fn($i) => $i->remainingQty());

            $isFullyDispatched = ($totalQty > 0 && $remainingQty <= 0) || in_array(strtoupper(trim((string)$o->dispatch_status)), ['DONE', 'COMPLETED', 'FULLY DISPATCHED', 'FULLY_DISPATCHED', 'CLOSED']);
            $isPartial = !$isFullyDispatched && ($dispatchedQty > 0 && $remainingQty > 0);

            if ($isFullyDispatched) {
                $dispatchStatus = 'DONE';
            } elseif ($isPartial) {
                $dispatchStatus = 'PARTIAL';
            } else {
                $dispatchStatus = 'PENDING';
            }

            $reportOrders->push([
                'id'             => $o->id,
                'orderId'        => $o->id,
                'companyId'      => $o->company_id,
                'companyName'    => $o->company?->name,
                'transportName'  => $o->transporter?->name,
                'salesPerson'    => $o->creator?->name ?? 'N/A',
                'salesBy'        => $o->creator?->name ?? 'N/A',
                'orderTotal'     => $o->total,
                'status'         => $o->status,
                'dispatchStatus' => $dispatchStatus,
                'date'           => $o->created_at ? $o->created_at->toISOString() : ($o->date ? \Carbon\Carbon::parse($o->date)->toISOString() : now()->toISOString()),
                'dueDate'        => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('d-m-Y') : null,
                'notes'          => $o->notes,
                'totalQty'       => $totalQty,
                'dispatchedQty'  => $dispatchedQty,
                'remainingQty'   => $remainingQty,
                'items'          => $o->items->map(fn($i) => [
                    'id'            => $i->id,
                    'productName'   => $i->product?->name ?? 'Unknown',
                    'rawProductName'=> $i->product?->name ?? 'Unknown',
                    'formattedName' => $i->product ? $i->product->formatName($i->grade) : 'Unknown',
                    'grade'         => $i->grade,
                    'productType'   => $i->product?->type,
                    'quantity'      => (float) $i->quantity,
                    'dispatchedQty' => (float) $i->dispatched_qty,
                    'remainingQty'  => (float) $i->remainingQty(),
                ])->values(),
            ]);
        }

        $companies = Company::orderBy('name')->get()->map(fn($c) => [
            'id'   => $c->id,
            'name' => strtoupper($c->name ?? '')
        ]);

        $pageData = [
            'orders'    => $reportOrders,
            'companies' => $companies,
        ];
        return view('dispatch.report', compact('pageData'));
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
