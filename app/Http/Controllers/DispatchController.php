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

    private function getStockMap(): array
    {
        $liveStocks = DB::table('stocks')
            ->selectRaw("product_id, stage, grade, SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as net_qty")
            ->groupBy('product_id', 'stage', 'grade')
            ->get();

        $stockMap = [];
        foreach ($liveStocks as $ls) {
            $rawStage = strtoupper(trim((string)$ls->stage));
            $normStage = match($rawStage) {
                'FG', 'FINISHED' => 'FINISHED',
                'PKG', 'PM', 'PACKAGING' => 'PACKAGING',
                'SEMI' => 'SEMI',
                'RAW' => 'RAW',
                default => $rawStage ?: 'FINISHED',
            };

            $rawGrade = strtoupper(trim((string)$ls->grade));
            $normGrade = in_array($rawGrade, ['', 'NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-'], true) ? 'NONE' : $rawGrade;

            $key = $ls->product_id . '_' . $normStage . '_' . $normGrade;
            $stockMap[$key] = ($stockMap[$key] ?? 0) + (float)$ls->net_qty;
        }

        return $stockMap;
    }

    private function getStageStock(array|string $stages)
    {
        $stages = array_map(fn($s) => strtoupper(trim((string)$s)), (array) $stages);
        $gradeNormExpr = "CASE WHEN stocks.grade IS NULL OR UPPER(TRIM(stocks.grade)) IN ('', 'NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-') THEN 'NONE' ELSE UPPER(TRIM(stocks.grade)) END";

        return DB::table(function ($query) use ($stages, $gradeNormExpr) {
            $query->from('stocks')
                ->join('products', 'stocks.product_id', '=', 'products.id')
                ->whereIn(DB::raw("UPPER(TRIM(stocks.stage))"), $stages)
                ->selectRaw("
                    stocks.product_id as id,
                    products.name,
                    products.unit,
                    {$gradeNormExpr} as grade,
                    stocks.transaction_type,
                    stocks.quantity
                ");
        }, 'sub')
        ->selectRaw("
            id,
            name,
            grade,
            unit,
            SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as quantity
        ")
        ->groupBy('id', 'name', 'grade', 'unit')
        ->havingRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) > 0")
        ->get();
    }

    private function resolveItemDispatchStatus(float $orderedQty, float $dispatchedQty): string
    {
        $remaining = max(0.0, $orderedQty - $dispatchedQty);
        if ($orderedQty > 0 && $remaining <= 0.001) {
            return 'DISPATCHED';
        }
        if ($dispatchedQty > 0.001) {
            return 'PARTIAL DISPATCH';
        }
        return 'NOT DISPATCHED';
    }

    private function calculateOrderReadiness($order, array $stockMap): array
    {
        $items = $order->items->map(function($i) use ($stockMap) {
            $qty = (float) $i->quantity;
            $disp = (float) $i->dispatched_qty;
            $needed = max(0, $qty - $disp);
            $rawType = strtoupper(trim((string)($i->product?->type ?? 'FINISHED')));
            $itemStage = match($rawType) {
                'FG', 'FINISHED' => 'FINISHED',
                'PKG', 'PM', 'PACKAGING' => 'PACKAGING',
                'SEMI' => 'SEMI',
                'RAW' => 'RAW',
                default => $rawType ?: 'FINISHED',
            };

            $rawGrade = strtoupper(trim((string)$i->grade));
            $itemGrade = in_array($rawGrade, ['', 'NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-'], true) ? 'NONE' : $rawGrade;

            $key = $i->product_id . '_' . $itemStage . '_' . $itemGrade;
            $avail = max(0, (float)($stockMap[$key] ?? 0));
            return [
                'id'                 => $i->id,
                'productId'          => $i->product_id,
                'rawProductName'     => $i->product?->name ?? 'Unknown',
                'productName'        => $i->product?->name ?? 'Unknown',
                'formattedName'      => $i->product ? $i->product->formatName($i->grade) : 'Unknown',
                'productType'        => $i->product?->type,
                'quantity'           => $qty,
                'dispatchedQty'      => $disp,
                'remainingQty'       => $needed,
                'availStock'         => $avail,
                'grade'              => $i->grade,
                'itemDispatchStatus' => $this->resolveItemDispatchStatus($qty, $disp),
            ];
        });

        $remainingItems = $items->filter(fn($i) => $i['remainingQty'] > 0);
        $totalRemainingItems = $remainingItems->count();
        $orderTotalQty = (float) $items->sum('quantity');

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

        if ($orderTotalQty <= 0) {
            $readiness = 'NOT_READY';
        } elseif ($totalRemainingItems == 0) {
            $readiness = 'DONE';
        } elseif ($fullCount === $totalRemainingItems) {
            $readiness = 'READY_DISPATCH';
        } elseif ($partialCount > 0) {
            $readiness = 'READY_PARTIAL';
        } else {
            $readiness = 'NOT_READY';
        }

        return [$readiness, $items];
    }

    public function home()
    {
        $pending   = Order::with(['company', 'transporter', 'items.product', 'creator', 'dispatchLogs:id,order_id'])
            ->where(function($q) {
                $q->whereNotIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY DISPATCHED'])
                  ->orWhereNull('dispatch_status');
            })
            ->where(function($q) {
                $q->where('status', '!=', 'CANCELLED')
                  ->orWhereNull('status');
            })
            ->orderByDesc('created_at')
            ->get();
        $completed = Order::with(['company', 'transporter', 'creator', 'items.product', 'dispatchLogs:id,order_id'])
            ->whereIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY DISPATCHED'])
            ->where(function($q) {
                $q->where('status', '!=', 'CANCELLED')
                  ->orWhereNull('status');
            })
            ->orderByDesc('created_at')
            ->get();

        foreach ($pending as $po) {
            foreach ($po->items as $pi) {
                $pi->syncDispatchedQty();
            }
        }
        foreach ($completed as $co) {
            foreach ($co->items as $ci) {
                $ci->syncDispatchedQty();
            }
        }

        $rawStock       = $this->getStageStock(['RAW']);
        $semiStock      = $this->getStageStock(['SEMI']);
        $finishedStock  = $this->getStageStock(['FINISHED', 'FG']);
        $packagingStock = $this->getStageStock(['PACKAGING', 'PKG', 'PM']);

        $stockMap = $this->getStockMap();

        $today = now()->timezone('Asia/Kolkata')->startOfDay();
        $pendingMapped = $pending->map(function($o) use ($stockMap) {
            [$readiness, $items] = $this->calculateOrderReadiness($o, $stockMap);
            $latestLogId = $o->dispatchLogs ? $o->dispatchLogs->sortByDesc('id')->first()?->id : null;

            return [
                'id'                  => $o->id,
                'latestDispatchLogId' => $latestLogId,
                'companyId'           => $o->company_id,
                'companyName'         => $o->company?->name,
                'transportId'         => $o->transporter_id,
                'transporterName'     => $o->transporter?->name,
                'salesPerson'         => $o->creator?->name ?? 'N/A',
                'total'               => $o->total,
                'date'                => $o->created_at ? $o->created_at->toISOString() : ($o->date ? \Carbon\Carbon::parse($o->date)->toISOString() : now()->toISOString()),
                'dueDate'             => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('d-m-Y') : null,
                'rawDueDate'          => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('Y-m-d') : null,
                'totalQty'            => $o->items->sum('quantity'),
                'dispatchedQty'       => $o->items->sum('dispatched_qty'),
                'dispatchStatus'      => $o->dispatch_status,
                'status'              => $o->status,
                'readiness'           => $readiness,
                'notes'               => $o->notes,
                'items'               => $items,
            ];
        })->sortBy(function($o) use ($today) {
            $rawDueDate = $o['rawDueDate'] ?? null;
            if ($rawDueDate) {
                $dueObj = \Carbon\Carbon::parse($rawDueDate, 'Asia/Kolkata')->startOfDay();
                $diffDays = (int) $today->diffInDays($dueObj, false);
                if ($diffDays === 0) {
                    return sprintf('0_%010d', 9999999999 - (int)($o['id'] ?? 0));
                } elseif ($diffDays < 0) {
                    return sprintf('1_%06d_%010d', abs($diffDays), 9999999999 - (int)($o['id'] ?? 0));
                } else {
                    return sprintf('2_%06d_%010d', $diffDays, 9999999999 - (int)($o['id'] ?? 0));
                }
            }
            return sprintf('3_%010d', 9999999999 - (int)($o['id'] ?? 0));
        })->values();

        $pageData = [
            'rawStock'        => $rawStock,
            'semiStock'       => $semiStock,
            'finishedStock'   => $finishedStock,
            'packagingStock'  => $packagingStock,
            'pendingOrders'   => $pendingMapped,
            'completedOrders' => $completed->map(fn($o) => [
                'id'                  => $o->id,
                'latestDispatchLogId' => $o->dispatchLogs ? $o->dispatchLogs->sortByDesc('id')->first()?->id : null,
                'companyId'           => $o->company_id,
                'companyName'         => $o->company?->name,
                'transportId'         => $o->transporter_id,
                'transporterName'     => $o->transporter?->name,
                'salesPerson'         => $o->creator?->name ?? 'N/A',
                'total'               => $o->total,
                'totalQty'            => $o->items->sum('quantity'),
                'dispatchedQty'       => $o->items->sum('dispatched_qty'),
                'date'                => $o->created_at ? $o->created_at->toISOString() : ($o->date ? \Carbon\Carbon::parse($o->date)->toISOString() : now()->toISOString()),
                'dueDate'             => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('d-m-Y') : null,
                'rawDueDate'          => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('Y-m-d') : null,
                'status'              => $o->status,
                'notes'               => $o->notes,
                'items'               => $o->items->map(fn($i) => [
                    'id'                 => $i->id,
                    'productId'          => $i->product_id,
                    'rawProductName'     => $i->product?->name ?? 'Unknown',
                    'productName'        => $i->product?->name ?? 'Unknown',
                    'formattedName'      => $i->product ? $i->product->formatName($i->grade) : 'Unknown',
                    'productType'        => $i->product?->type,
                    'quantity'           => (float) $i->quantity,
                    'dispatchedQty'      => (float) $i->dispatched_qty,
                    'remainingQty'       => (float) max(0, (float)$i->quantity - (float)$i->dispatched_qty),
                    'itemDispatchStatus' => $this->resolveItemDispatchStatus((float) $i->quantity, (float) $i->dispatched_qty),
                    'grade'              => $i->grade,
                    'price'              => (float) ($i->price > 0 ? $i->price : ($i->product?->rate ?? 0)),
                    'rate'               => (float) ($i->price > 0 ? $i->price : ($i->product?->rate ?? 0)),
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
        $targetOrderId = request('order_id');

        $pendingOrders = Order::with(['company', 'transporter', 'items.product', 'creator'])
            ->where(function($q) {
                $q->whereNotIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY DISPATCHED', 'FULLY_DISPATCHED'])
                  ->orWhereNull('dispatch_status');
            })
            ->where(function($q) {
                $q->where('status', '!=', 'CANCELLED')
                  ->orWhereNull('status');
            })
            ->orderByDesc('created_at')
            ->get();

        // If a specific order was requested via URL, ensure it is present in pendingOrders
        if ($targetOrderId && !$pendingOrders->contains('id', (int)$targetOrderId)) {
            $targetOrder = Order::with(['company', 'transporter', 'items.product', 'creator'])->find($targetOrderId);
            if ($targetOrder && strtoupper((string)$targetOrder->status) !== 'CANCELLED') {
                $pendingOrders->prepend($targetOrder);
            }
        }

        foreach ($pendingOrders as $po) {
            foreach ($po->items as $pi) {
                $pi->syncDispatchedQty();
            }
        }

        $today = now()->timezone('Asia/Kolkata')->startOfDay();
        $pendingOrders = $pendingOrders->sortBy(function($o) use ($today) {
            if ($o->due_date) {
                $dueObj = \Carbon\Carbon::parse($o->due_date, 'Asia/Kolkata')->startOfDay();
                $diffDays = (int) $today->diffInDays($dueObj, false);
                if ($diffDays === 0) {
                    return sprintf('0_%010d', 9999999999 - (int)$o->id);
                } elseif ($diffDays < 0) {
                    return sprintf('1_%06d_%010d', abs($diffDays), 9999999999 - (int)$o->id);
                } else {
                    return sprintf('2_%06d_%010d', $diffDays, 9999999999 - (int)$o->id);
                }
            }
            return sprintf('3_%010d', 9999999999 - (int)$o->id);
        })->values();

        $stockMap = $this->getStockMap();
        $pageData = [
            'pendingOrders' => $pendingOrders->map(function($o) use ($stockMap) {
                [$readiness, $items] = $this->calculateOrderReadiness($o, $stockMap);
                return [
                    'id'          => $o->id,
                    'status'      => $o->status,
                    'readiness'   => $readiness,
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
                    'items'       => $items->values()
                ];
            })->values()
        ];

        return response()
            ->view('dispatch.action', compact('pageData'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function getOrderDetails($param1, $param2 = null)
    {
        $id = request()->route('id') ?? (($param2 !== null && !is_object($param2)) ? $param2 : $param1);
        $order = Order::with(['company', 'transporter', 'items.product', 'creator'])->find($id);
        if (!$order || $order->status === 'CANCELLED') {
            return response()->json(['success' => false, 'message' => 'Order is cancelled or not found.'], 404);
        }

        foreach ($order->items as $item) {
            $item->syncDispatchedQty();
        }

        $stockMap = $this->getStockMap();
        [$readiness, $items] = $this->calculateOrderReadiness($order, $stockMap);

        return response()->json([
            'success' => true,
            'order'   => [
                'id'          => $order->id,
                'readiness'   => $readiness,
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
                'items'       => $items->values(),
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

            if (!$order || $order->dispatch_status === 'DONE' || $order->status === 'CANCELLED') {
                $response = response()->json(['success' => false, 'message' => 'Cannot dispatch: Order is cancelled, already fully dispatched, or invalid.'], 422);
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
                $rawGrade      = strtoupper(trim((string)$orderItem->grade));
                $itemGrade     = in_array($rawGrade, ['', 'NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-'], true) ? 'NONE' : $rawGrade;
                
                $rawType       = strtoupper(trim((string)($orderItem->product?->type ?? 'FINISHED')));
                $itemStages    = match($rawType) {
                    'FG', 'FINISHED' => ['FINISHED', 'FG'],
                    'PKG', 'PM', 'PACKAGING' => ['PACKAGING', 'PKG', 'PM'],
                    'SEMI' => ['SEMI'],
                    'RAW' => ['RAW'],
                    default => [$rawType ?: 'FINISHED'],
                };
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

                        $availableAtLocation = (float) (DB::table('stocks')
                            ->where('product_id', $orderItem->product_id)
                            ->whereIn('stage', $itemStages)
                            ->where(function($q) use ($itemGrade) {
                                if ($itemGrade === 'NONE') {
                                    $q->where('grade', 'NONE')->orWhereNull('grade')->orWhere('grade', '')->orWhereIn(DB::raw("UPPER(TRIM(grade))"), ['NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-']);
                                } else {
                                    $q->where('grade', $itemGrade);
                                }
                            })
                            ->where('location_id', $locationId)
                            ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")
                            ->value('net') ?? 0);

                        if ($allocQty > $availableAtLocation) {
                            $response = response()->json([
                                'success' => false,
                                'message' => "Insufficient stock at location '{$locationName}'. Need: {$allocQty} kg, Have: {$availableAtLocation} kg"
                            ], 422);
                            return;
                        }
                    }
                } else {
                    $available = (float) (DB::table('stocks')
                        ->where('product_id', $orderItem->product_id)
                        ->whereIn('stage', $itemStages)
                        ->where(function($q) use ($itemGrade) {
                            if ($itemGrade === 'NONE') {
                                $q->where('grade', 'NONE')->orWhereNull('grade')->orWhere('grade', '')->orWhereIn(DB::raw("UPPER(TRIM(grade))"), ['NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-']);
                            } else {
                                $q->where('grade', $itemGrade);
                            }
                        })
                        ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")
                        ->value('net') ?? 0);

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
                $lrPath = self::saveLrImagePayload($request->lr_image);
            }
            $dispatchTransporterId = (!empty($request->transporter_id) && $request->transporter_id != 0) 
                ? (int) $request->transporter_id 
                : ($order->transporter_id ? (int) $order->transporter_id : null);
            if (!$dispatchTransporterId) {
                try {
                    $fallback = \App\Models\Transporter::firstOrCreate(['name' => 'N/A'], ['contact' => '—', 'gst' => '—']);
                    $dispatchTransporterId = $fallback->id;
                } catch (\Throwable $ignored) {}
            }

            \App\Http\Controllers\SalesController::ensureVehicleNoColumnExists();

            $driverNo = $request->driver_no ?? $request->driver_number;
            $vehicleNo = $request->vehicle_number ?? $request->vehicle_no;

            // Also link the order to this transporter if order didn't have one
            if ($dispatchTransporterId && (empty($order->transporter_id) || $order->transporter?->name === 'N/A')) {
                try {
                    $order->transporter_id = $dispatchTransporterId;
                    $order->saveQuietly();
                } catch (\Throwable $ignored) {}
            }

            // Create dispatch log for this round
            $dispatchLogData = [
                'user_id'        => $user['id'],
                'order_id'       => $order->id,
                'transporter_id' => $dispatchTransporterId,
                'lr_image_path'  => $lrPath,
                'driver_no'      => $driverNo,
                'vehicle_no'     => $vehicleNo,
                'lr_no'          => $request->lr_no,
                'notes'          => $request->notes,
            ];
            $dispatchLog = DispatchLog::create($dispatchLogData);
            if ($lrPath) {
                self::linkLrDbBackupToLog($lrPath, (int) $dispatchLog->id);
            }

            // Process each item
            foreach ($request->items as $dispatchItem) {
                $orderItem   = $order->items->firstWhere('id', $dispatchItem['order_item_id']);
                $dispatchQty = (float) $dispatchItem['quantity'];
                $rawGrade  = strtoupper(trim((string)$orderItem->grade));
                $itemGrade = in_array($rawGrade, ['', 'NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL'], true) ? 'NONE' : $rawGrade;
                $rawType   = strtoupper(trim((string)($orderItem->product?->type ?? 'FINISHED')));
                $itemStages = match($rawType) {
                    'FG', 'FINISHED' => ['FINISHED', 'FG'],
                    'PKG', 'PM', 'PACKAGING' => ['PACKAGING', 'PKG', 'PM'],
                    'SEMI' => ['SEMI'],
                    'RAW' => ['RAW'],
                    default => [$rawType ?: 'FINISHED'],
                };

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

                        $existingStock = DB::table('stocks')
                            ->where('product_id', $orderItem->product_id)
                            ->whereIn('stage', $itemStages)
                            ->where(function($q) use ($itemGrade) {
                                if ($itemGrade === 'NONE') {
                                    $q->whereIn(DB::raw("UPPER(TRIM(COALESCE(grade, '')))"), ['', 'NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-']);
                                } else {
                                    $q->where(DB::raw("UPPER(TRIM(grade))"), $itemGrade);
                                }
                            })
                            ->where('location_id', $locationId)
                            ->first(['stage', 'grade']);

                        $outStage = $existingStock?->stage ?: $itemStages[0];
                        $outGrade = $existingStock?->grade ?? $itemGrade;

                        // Create stock OUT transaction for this location
                        $stock = Stock::create([
                            'product_id'       => $orderItem->product_id,
                            'user_id'          => $user['id'],
                            'stage'            => $outStage,
                            'grade'            => $outGrade,
                            'location_id'      => $locationId,
                            'quantity'         => $allocQty,
                            'transaction_type' => 'OUT',
                            'date'             => now()->toDateString(),
                            'notes'            => "Dispatched: Order #{$order->id} from Location '{$locationName}'",
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
                        $itemStages[0],
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
            $order->load('items');
            $allDone = $order->items->isNotEmpty() && $order->items->every(fn($item) => $item->remainingQty() <= 0.001);

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
                    $rawGrade = strtoupper(trim((string)$orderItem->grade));
                    $itemGrade = in_array($rawGrade, ['', 'NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-'], true) ? 'NONE' : $rawGrade;
                    $rawType = strtoupper(trim((string)($orderItem->product?->type ?? 'FINISHED')));
                    $itemStages = match($rawType) {
                        'FG', 'FINISHED' => ['FINISHED', 'FG'],
                        'PKG', 'PM', 'PACKAGING' => ['PACKAGING', 'PKG', 'PM'],
                        'SEMI' => ['SEMI'],
                        'RAW' => ['RAW'],
                        default => [$rawType ?: 'FINISHED'],
                    };

                    $availableStock = (float) (DB::table('stocks')
                        ->where('product_id', $orderItem->product_id)
                        ->whereIn('stage', $itemStages)
                        ->where(function($q) use ($itemGrade) {
                            if ($itemGrade === 'NONE') {
                                $q->where('grade', 'NONE')->orWhereNull('grade')->orWhere('grade', '')->orWhereIn(DB::raw("UPPER(TRIM(grade))"), ['NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-']);
                            } else {
                                $q->where('grade', $itemGrade);
                            }
                        })
                        ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")
                        ->value('net') ?? 0);

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

                            $existingStock = DB::table('stocks')
                                ->where('product_id', $orderItem->product_id)
                                ->whereIn('stage', $itemStages)
                                ->where(function($q) use ($itemGrade) {
                                    if ($itemGrade === 'NONE') {
                                        $q->whereIn(DB::raw("UPPER(TRIM(COALESCE(grade, '')))"), ['', 'NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-']);
                                    } else {
                                        $q->where(DB::raw("UPPER(TRIM(grade))"), $itemGrade);
                                    }
                                })
                                ->where('location_id', $locationId)
                                ->first(['stage', 'grade']);

                            $outStage = $existingStock?->stage ?: $itemStages[0];
                            $outGrade = $existingStock?->grade ?? $itemGrade;

                            $stock = Stock::create([
                                'product_id'       => $orderItem->product_id,
                                'user_id'          => $user['id'],
                                'stage'            => $outStage,
                                'grade'            => $outGrade,
                                'location_id'      => $locationId,
                                'quantity'         => $allocQty,
                                'transaction_type' => 'OUT',
                                'date'             => now()->toDateString(),
                                'notes'            => "Dispatched: Order #{$order->id} (Partial round #{$log->id}) from Location '{$locationName}'",
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
                            $itemStages[0],
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

        // Handle LR image - decode and save to public, Hostinger storage, and MySQL backup safely
        $lrPath = self::saveLrImagePayload($request->lr_image, (int) $log->id);
        if (!$lrPath) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save LR image to server disk. Please check image data and storage permissions.',
            ], 500);
        }

        // Delete old physical & backup image copies if exists (unless identical filename)
        if ($log->lr_image_path) {
            foreach (self::getLrImagePaths($log) as $oldRp) {
                if (basename($oldRp) !== basename($lrPath)) {
                    self::deletePhysicalLrCopies($oldRp, true);
                }
            }
        }

        DB::transaction(function() use ($log, $lrPath) {
            $log->lr_image_path = $lrPath;
            $log->updated_at = now();
            $log->save();
        });

        self::linkLrDbBackupToLog($lrPath, (int) $log->id);

        $userSlug = request()->segment(1) ?: 'dispatch';
        $ver = $log->updated_at?->timestamp ?? time();

        return response()->json([
            'success'  => true, 
            'message'  => 'LR Copy updated successfully for Order #' . $log->order_id . '!',
            'lr_url'   => asset($lrPath) . '?v=' . $ver,
            'view_url' => url($userSlug . '/dispatch/view-lr/' . $log->id) . '?v=' . $ver,
            'order_id' => $log->order_id,
            'log_id'   => $log->id,
        ]);
    }

    /**
     * Bulk restore historical LR images from an uploaded .zip archive or multiple image files.
     */
    public function restoreLrArchive(Request $request)
    {
        $user = $this->authUser();
        if (!empty($user['is_view_only'])) {
            return response()->json(['success' => false, 'message' => 'View-only users cannot restore LR files.'], 403);
        }

        // Map known historical filenames -> dispatch_log_id
        $logByFilename = [];
        foreach (DispatchLog::whereNotNull('lr_image_path')->get(['id', 'order_id', 'lr_image_path']) as $dl) {
            foreach (self::getLrImagePaths($dl) as $rp) {
                $bn = basename(trim(str_replace('\\', '/', $rp)));
                if ($bn) {
                    $logByFilename[strtolower($bn)] = ['log_id' => (int) $dl->id, 'order_id' => (int) $dl->order_id, 'exact_name' => $bn];
                }
            }
        }

        $restoredCount = 0;
        $matchedOrders = [];
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        $processEntry = function (string $entryName, string $bytes) use (&$restoredCount, &$matchedOrders, $logByFilename, $allowedExts) {
            $base = basename(str_replace('\\', '/', $entryName));
            $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
            if (!$base || str_starts_with($base, '.') || !in_array($ext, $allowedExts, true) || strlen($bytes) < 20) {
                return;
            }
            $lookup = strtolower($base);
            $matched = $logByFilename[$lookup] ?? null;
            if (!$matched && !str_starts_with($lookup, 'lr_')) {
                return;
            }
            $targetFilename = $matched['exact_name'] ?? $base;
            $relPath = 'lr_images/' . $targetFilename;
            if (self::writeBinaryToLrDirectories($targetFilename, $bytes) > 0) {
                self::storeLrDbBackup($relPath, $bytes, $matched['log_id'] ?? null);
                $restoredCount++;
                if ($matched) {
                    $matchedOrders[] = '#' . $matched['order_id'];
                }
            }
        };

        $uploadedFiles = [];
        if ($request->hasFile('files')) {
            $rawFiles = $request->file('files');
            $uploadedFiles = is_array($rawFiles) ? $rawFiles : [$rawFiles];
        }
        if ($request->hasFile('archive')) {
            $uploadedFiles[] = $request->file('archive');
        }

        if (empty($uploadedFiles)) {
            return response()->json(['success' => false, 'message' => 'Please select a .zip backup or LR image files to restore.'], 422);
        }

        foreach ($uploadedFiles as $uFile) {
            if (!$uFile || !$uFile->isValid()) {
                continue;
            }
            $origName = $uFile->getClientOriginalName();
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if ($ext === 'zip' && class_exists('ZipArchive')) {
                $zip = new \ZipArchive();
                if ($zip->open($uFile->getRealPath()) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $stat = $zip->statIndex($i);
                        if (!$stat || empty($stat['name']) || str_ends_with($stat['name'], '/')) {
                            continue;
                        }
                        $content = $zip->getFromIndex($i);
                        if ($content !== false) {
                            $processEntry($stat['name'], $content);
                        }
                    }
                    $zip->close();
                }
            } else {
                $bytes = @file_get_contents($uFile->getRealPath());
                if ($bytes !== false) {
                    $processEntry($origName, $bytes);
                }
            }
        }

        $matchedOrders = array_values(array_unique($matchedOrders));
        if ($restoredCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No matching LR_*.jpg files were found in the uploaded selection.',
            ], 422);
        }

        $orderNote = !empty($matchedOrders) ? (' Matched Orders: ' . implode(', ', $matchedOrders) . '.') : '';
        return response()->json([
            'success'        => true,
            'restored_count' => $restoredCount,
            'matched_orders' => $matchedOrders,
            'message'        => "Restored {$restoredCount} LR image file(s) permanently to disk and database!{$orderNote}",
        ]);
    }

    /**
     * Reset a missing historical LR path cleanly back to LR PENDING if the old paper file is unavailable.
     */
    public function clearMissingLR(Request $request, $param1, $param2 = null)
    {
        $user = $this->authUser();
        if (!empty($user['is_view_only'])) {
            return response()->json(['success' => false, 'message' => 'View-only users cannot modify LR records.'], 403);
        }

        $id = ($param2 !== null && !is_object($param2)) ? $param2 : $param1;
        $log = DispatchLog::findOrFail($id);

        foreach (self::getLrImagePaths($log) as $rp) {
            if (self::resolveLocalFilePath($rp)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This LR image exists on disk and is not missing.',
                ], 422);
            }
        }

        $log->lr_image_path = null;
        $log->save();

        return response()->json([
            'success' => true,
            'message' => 'Marked Order #' . $log->order_id . ' cleanly as LR Pending.',
        ]);
    }

    /**
     * Helper to reliably decode and save a base64 LR image to disk storage AND MySQL backup table.
     */
    public static function saveLrImagePayload(string $base64Data, ?int $logId = null): ?string
    {
        if (empty($base64Data)) {
            return null;
        }

        $cleanData = preg_replace('#^data:image/[\w+.-]+;base64,#i', '', $base64Data);
        $cleanData = str_replace(' ', '+', trim($cleanData));
        $imageData = base64_decode($cleanData, true);
        if (!$imageData || strlen($imageData) < 20) {
            return null;
        }

        $filename = uniqid('LR_') . '.jpg';
        $relativeFilePath = 'lr_images/' . $filename;

        $savedCount = self::writeBinaryToLrDirectories($filename, $imageData);
        if ($savedCount > 0) {
            self::storeLrDbBackup($relativeFilePath, $imageData, $logId);
            return $relativeFilePath;
        }

        return null;
    }

    /**
     * Write raw image bytes into all candidate LR directories (public_html, public, storage, vault).
     */
    public static function writeBinaryToLrDirectories(string $filename, string $imageData): int
    {
        $base = basename(str_replace('\\', '/', $filename));
        if (!$base || $base === '.' || $base === '..' || strlen($imageData) < 20) {
            return 0;
        }

        $relativeDir = 'lr_images';
        $targetDirs = array_values(array_unique([
            storage_path('app/public/' . $relativeDir),
            storage_path('app/lr_vault'),
            public_path($relativeDir),
            base_path('../public_html/' . $relativeDir),
            base_path('../public_html/penta-pure/' . $relativeDir),
            base_path('public/' . $relativeDir),
        ]));

        $savedCount = 0;
        foreach ($targetDirs as $dir) {
            $parent = dirname($dir);
            if (file_exists($parent) && is_dir($parent)) {
                if (!file_exists($dir)) {
                    @mkdir($dir, 0775, true);
                }
                if (file_exists($dir) && is_dir($dir)) {
                    $targetFile = $dir . DIRECTORY_SEPARATOR . $base;
                    $bytes = @file_put_contents($targetFile, $imageData);
                    if ($bytes !== false && $bytes > 0) {
                        @chmod($targetFile, 0644);
                        $savedCount++;
                    }
                }
            }
        }

        if ($savedCount === 0) {
            $storageDir = storage_path('app/public/' . $relativeDir);
            if (!file_exists($storageDir)) {
                @mkdir($storageDir, 0775, true);
            }
            $targetFile = $storageDir . DIRECTORY_SEPARATOR . $base;
            $bytes = @file_put_contents($targetFile, $imageData);
            if ($bytes !== false && $bytes > 0) {
                @chmod($targetFile, 0644);
                $savedCount++;
            }
        }

        return $savedCount;
    }

    /**
     * Store or update an LR image backup inside MySQL dispatch_lr_backups so SQL dumps preserve LR images.
     */
    public static function storeLrDbBackup(string $relativeFilePath, string $imageData, ?int $logId = null): bool
    {
        $base = basename(str_replace('\\', '/', $relativeFilePath));
        if (!$base || strlen($imageData) < 20) {
            return false;
        }

        try {
            $mime = 'image/jpeg';
            if (function_exists('finfo_open')) {
                $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $detected = @finfo_buffer($finfo, $imageData);
                    @finfo_close($finfo);
                    if ($detected && str_starts_with($detected, 'image/')) {
                        $mime = $detected;
                    }
                }
            }

            $attrs = [
                'file_path'    => 'lr_images/' . $base,
                'mime_type'    => $mime,
                'image_base64' => base64_encode($imageData),
            ];
            if ($logId) {
                $attrs['dispatch_log_id'] = $logId;
            }

            \App\Models\DispatchLrBackup::updateOrCreate(
                ['file_name' => $base],
                $attrs
            );
            return true;
        } catch (\Throwable $ignored) {
            return false;
        }
    }

    /**
     * Link a saved MySQL LR backup record to its DispatchLog ID.
     */
    public static function linkLrDbBackupToLog(string $relativeFilePath, int $logId): void
    {
        $base = basename(str_replace('\\', '/', $relativeFilePath));
        if (!$base || $logId <= 0) {
            return;
        }
        try {
            \App\Models\DispatchLrBackup::where('file_name', $base)
                ->whereNull('dispatch_log_id')
                ->update(['dispatch_log_id' => $logId]);
        } catch (\Throwable $ignored) {}
    }

    /**
     * Delete all physical copies (and optionally DB backup) of a given LR image path.
     */
    public static function deletePhysicalLrCopies(string $rp, bool $alsoDeleteDbBackup = true): void
    {
        if (empty($rp)) {
            return;
        }
        $cleanRp = $rp;
        if (str_starts_with($cleanRp, 'http://') || str_starts_with($cleanRp, 'https://')) {
            $cleanRp = (string) parse_url($cleanRp, PHP_URL_PATH);
        }
        $relative = ltrim(str_replace('\\', '/', $cleanRp), '/');
        $basename = basename($relative);

        $paths = [
            public_path($relative),
            storage_path('app/public/' . $relative),
            base_path('../public_html/' . $relative),
            base_path('public/' . $relative),
        ];
        if ($basename && $basename !== '.' && $basename !== '..') {
            $paths[] = public_path('lr_images/' . $basename);
            $paths[] = base_path('../public_html/lr_images/' . $basename);
            $paths[] = storage_path('app/public/lr_images/' . $basename);
            $paths[] = storage_path('app/lr_vault/' . $basename);
        }

        foreach (array_unique($paths) as $p) {
            if ($p && file_exists($p) && is_file($p)) {
                @unlink($p);
            }
        }

        if ($alsoDeleteDbBackup && $basename && $basename !== '.' && $basename !== '..') {
            try {
                \App\Models\DispatchLrBackup::where('file_name', $basename)->delete();
            } catch (\Throwable $ignored) {}
        }
    }

    /**
     * View / stream LR copy image for a specific dispatch log.
     */
    public function viewLR($param1, $param2 = null)
    {
        $id = ($param2 !== null && !is_object($param2)) ? $param2 : $param1;
        if (is_object($param1)) {
            $id = $param2;
        }

        $log = DispatchLog::with('order.dispatchLogs')->find($id);
        if (!$log) {
            abort(404, 'Dispatch log record not found.');
        }

        $rawPaths = self::getLrImagePaths($log);
        $validResolved = [];
        foreach ($rawPaths as $rp) {
            $resolved = self::resolveLocalFilePath($rp);
            if ($resolved) {
                $validResolved[] = $resolved;
            }
        }

        if (empty($validResolved) && $log->order && $log->order->dispatchLogs) {
            foreach ($log->order->dispatchLogs->sortByDesc('id') as $ol) {
                if ($ol->id === $log->id || empty($ol->lr_image_path)) {
                    continue;
                }
                foreach (self::getLrImagePaths($ol) as $orp) {
                    $res = self::resolveLocalFilePath($orp);
                    if ($res) {
                        $validResolved[] = $res;
                    }
                }
                if (!empty($validResolved)) {
                    break;
                }
            }
        }

        if (empty($validResolved)) {
            abort(404, 'LR image file not found on server disk.');
        }

        $reqIdx = max(0, (int) request()->query('idx', 0));
        $fullPath = $validResolved[$reqIdx] ?? $validResolved[0];

        if (!$fullPath || !file_exists($fullPath)) {
            abort(404, 'LR image file not found on server disk.');
        }

        $mime = @mime_content_type($fullPath) ?: 'image/jpeg';
        $mtime = @filemtime($fullPath) ?: time();

        return response()->file($fullPath, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'private, no-cache, must-revalidate',
            'ETag'          => '"' . md5($fullPath . '|' . $mtime) . '"',
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
     * Locate an existing LR file strictly on disk (without triggering DB self-heal).
     */
    public static function findExistingDiskFileOnly(string $rp): ?string
    {
        if (empty(trim($rp))) {
            return null;
        }
        $cleanRp = trim($rp);
        if (str_starts_with($cleanRp, 'http://') || str_starts_with($cleanRp, 'https://')) {
            $cleanRp = (string) parse_url($cleanRp, PHP_URL_PATH);
        }
        $relative = ltrim(str_replace('\\', '/', $cleanRp), '/');
        if ($relative === '') {
            return null;
        }

        $candidates = [
            public_path($relative),
            base_path('../public_html/' . $relative),
            storage_path('app/public/' . $relative),
            storage_path($relative),
            base_path($relative),
            base_path('../public_html/penta-pure/' . $relative),
            base_path('public/' . $relative),
        ];

        if (str_starts_with($relative, 'public/')) {
            $sub = substr($relative, 7);
            $candidates[] = public_path($sub);
            $candidates[] = base_path('../public_html/' . $sub);
            $candidates[] = storage_path('app/public/' . $sub);
            $candidates[] = base_path('../public_html/penta-pure/' . $sub);
        }

        if (str_starts_with($relative, 'storage/')) {
            $sub = substr($relative, 8);
            $candidates[] = storage_path('app/public/' . $sub);
            $candidates[] = public_path($sub);
            $candidates[] = base_path('../public_html/storage/' . $sub);
        }

        $basename = basename($relative);
        if ($basename && $basename !== '.' && $basename !== '..') {
            $candidates[] = public_path('lr_images/' . $basename);
            $candidates[] = base_path('../public_html/lr_images/' . $basename);
            $candidates[] = storage_path('app/public/lr_images/' . $basename);
            $candidates[] = storage_path('app/lr_vault/' . $basename);
            $candidates[] = base_path('../../../_fleet_vault/lr_images_backup/' . $basename);
        }

        foreach ($candidates as $cand) {
            if ($cand && file_exists($cand) && is_file($cand)) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Helper to resolve physical local file path for an image URL/relative path.
     * Automatically self-heals missing disk files from MySQL dispatch_lr_backups when available.
     */
    public static function resolveLocalFilePath(string $rp): ?string
    {
        $existing = self::findExistingDiskFileOnly($rp);
        if ($existing) {
            return $existing;
        }

        $cleanRp = trim($rp);
        if (str_starts_with($cleanRp, 'http://') || str_starts_with($cleanRp, 'https://')) {
            $cleanRp = (string) parse_url($cleanRp, PHP_URL_PATH);
        }
        $basename = basename(ltrim(str_replace('\\', '/', $cleanRp), '/'));
        if (!$basename || $basename === '.' || $basename === '..') {
            return null;
        }

        try {
            $backup = \App\Models\DispatchLrBackup::where('file_name', $basename)->first();
            if ($backup && !empty($backup->image_base64)) {
                $bytes = base64_decode($backup->image_base64, true);
                if ($bytes && strlen($bytes) >= 20) {
                    if (self::writeBinaryToLrDirectories($basename, $bytes) > 0) {
                        return self::findExistingDiskFileOnly($rp);
                    }
                }
            }
        } catch (\Throwable $ignored) {}

        return null;
    }

    /**
     * Download LR copy file for a specific dispatch log.
     */
    public function downloadLR($param1, $param2 = null)
    {
        $id = ($param2 !== null && !is_object($param2)) ? $param2 : $param1;
        if (is_object($param1)) {
            $id = $param2;
        }

        $log = DispatchLog::with('order.dispatchLogs')->findOrFail($id);
        $rawPaths = self::getLrImagePaths($log);

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

        if (empty($validFiles) && $log->order && $log->order->dispatchLogs) {
            foreach ($log->order->dispatchLogs->sortByDesc('id') as $otherLog) {
                if ($otherLog->id === $log->id || empty($otherLog->lr_image_path)) {
                    continue;
                }
                $otherPaths = self::getLrImagePaths($otherLog);
                foreach ($otherPaths as $idx => $orp) {
                    $fullPath = self::resolveLocalFilePath($orp);
                    if ($fullPath) {
                        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'jpg');
                        $lrNo = $otherLog->lr_no ? '_' . preg_replace('/[^A-Za-z0-9_-]/', '', $otherLog->lr_no) : '';
                        $suffix = count($otherPaths) > 1 ? "_img" . ($idx + 1) : '';
                        $name = "LR_Order_{$otherLog->order_id}_Log_{$otherLog->id}{$lrNo}{$suffix}.{$ext}";
                        $validFiles[] = [
                            'path' => $fullPath,
                            'name' => $name,
                        ];
                    }
                }
                if (!empty($validFiles)) {
                    break;
                }
            }
        }

        if (empty($validFiles)) {
            return back()->with('error', 'LR image file not found on server. Please re-upload the LR copy.');
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
            'order.dispatchLogs.dispatchItems',
            'user',
            'dispatchItems.orderItem.product'
        ])
            ->whereHas('order', function($q) {
                $q->where(function($sq) {
                    $sq->where('status', '!=', 'CANCELLED')
                       ->orWhereNull('status');
                });
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

        $userSlug = request()->segment(1) ?: 'dispatch';
        $logsData = $logs->map(function($d) use ($userSlug) {
            $rawPaths = self::getLrImagePaths($d);
            $ver = $d->updated_at?->timestamp ?? ($d->created_at?->timestamp ?? 1);

            $ownValidImages = [];
            foreach ($rawPaths as $idx => $rp) {
                if (self::resolveLocalFilePath($rp)) {
                    $ownValidImages[] = [
                        'path'    => $rp,
                        'url'     => asset($rp) . '?v=' . $ver,
                        'viewUrl' => url($userSlug . '/dispatch/view-lr/' . $d->id) . '?idx=' . $idx . '&v=' . $ver,
                    ];
                }
            }
            $ownLr = !empty($ownValidImages) ? $ownValidImages[0]['url'] : null;
            $ownViewUrl = !empty($ownValidImages) ? $ownValidImages[0]['viewUrl'] : null;

            // Collect all physically existing LR copies for this entire order (across all dispatch rounds)
            $orderLrCopies = [];
            $orderHasAnyRecordLr = false;
            if ($d->order && $d->order->dispatchLogs) {
                foreach ($d->order->dispatchLogs as $ol) {
                    if ($ol->lr_image_path) {
                        $orderHasAnyRecordLr = true;
                        $olVer = $ol->updated_at?->timestamp ?? ($ol->created_at?->timestamp ?? 1);
                        $olPaths = self::getLrImagePaths($ol);
                        foreach ($olPaths as $idx => $olp) {
                            if (self::resolveLocalFilePath($olp)) {
                                $orderLrCopies[] = [
                                    'logId'      => $ol->id,
                                    'url'        => asset($olp) . '?v=' . $olVer,
                                    'viewUrl'    => url($userSlug . '/dispatch/view-lr/' . $ol->id) . '?idx=' . $idx . '&v=' . $olVer,
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
            }

            // Fallback: if this specific round has no physical LR image, but the order has a valid LR on another round, use that order's LR
            $orderLatestLr = (!$ownLr && !empty($orderLrCopies)) ? ($orderLrCopies[0]['url'] ?? null) : null;
            $orderLatestViewUrl = (!$ownViewUrl && !empty($orderLrCopies)) ? ($orderLrCopies[0]['viewUrl'] ?? null) : null;
            $effectiveLr = $ownLr ?: $orderLatestLr;
            $effectiveViewUrl = $ownViewUrl ?: ($orderLatestViewUrl ?: (url($userSlug . '/dispatch/view-lr/' . $d->id) . '?v=' . $ver));
            $hasPhysicalLr = !empty($ownValidImages) || !empty($orderLrCopies);
            $hasRecordLr = !empty($rawPaths) || $orderHasAnyRecordLr;
            $effectiveLrLogId = !empty($ownValidImages) ? $d->id : ($orderLrCopies[0]['logId'] ?? $d->id);

            $orderTotalQty = (float) ($d->order?->items?->sum('quantity') ?? 0);
            $orderRemainingQty = (float) ($d->order?->items?->sum(fn($i) => $i->remainingQty()) ?? 0);
            $rawDispStatus = strtoupper(trim((string)($d->order?->dispatch_status ?? '')));
            $isOrderDone = in_array($rawDispStatus, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED']) || ($orderTotalQty > 0 && $orderRemainingQty <= 0);
            $computedStatus = $isOrderDone ? 'DONE' : 'PARTIAL';

            $totalPrevDispatched = 0;
            $items = $d->dispatchItems->filter(fn($di) => $di->orderItem && $di->orderItem->order_id == $d->order_id)->map(function($di) use ($d, &$totalPrevDispatched) {
                // Calculate previous dispatch quantity for this specific order item before this dispatch round
                $prevDispatched = 0;
                if ($d->order && $d->order->dispatchLogs) {
                    foreach ($d->order->dispatchLogs as $priorLog) {
                        $isPrior = ($priorLog->created_at && $d->created_at)
                            ? ($priorLog->created_at < $d->created_at || ($priorLog->created_at == $d->created_at && $priorLog->id < $d->id))
                            : ($priorLog->id < $d->id);
                        if ($isPrior && $priorLog->dispatchItems) {
                            foreach ($priorLog->dispatchItems as $pdi) {
                                if ($pdi->order_item_id == $di->order_item_id) {
                                    $prevDispatched += (float) $pdi->quantity;
                                }
                            }
                        }
                    }
                }
                $totalPrevDispatched += $prevDispatched;

                $totItemQty = (float) ($di->orderItem?->quantity ?? 0);
                $cumDispQty = (float) $prevDispatched + (float) $di->quantity;

                return [
                    'id'                 => $di->id,
                    'dispatchItemId'     => $di->id,
                    'orderItemId'        => $di->order_item_id,
                    'productName'        => $di->orderItem?->product?->name ?? 'Unknown',
                    'rawProductName'     => $di->orderItem?->product?->name ?? 'Unknown',
                    'formattedName'      => $di->orderItem?->product ? $di->orderItem->product->formatName($di->orderItem->grade) : 'Unknown',
                    'grade'              => $di->orderItem?->grade,
                    'productType'        => $di->orderItem?->product?->type,
                    'totalQty'           => $totItemQty,
                    'prevDispatchedQty'  => (float) $prevDispatched,
                    'dispatchedQty'      => (float) $di->quantity,
                    'remainingQty'       => (float) max(0, $totItemQty - $cumDispQty),
                    'itemDispatchStatus' => $this->resolveItemDispatchStatus($totItemQty, $cumDispQty),
                ];
            })->values();

            $totalCurrentDispatched = (float) $items->sum('dispatchedQty');
            $totalRemaining = (float) $items->sum('remainingQty');
            $totalOrdered = (float) $items->sum('totalQty');

            return [
                'id'                        => $d->id,
                'dispatchId'                => $d->id,
                'isOrderOnly'               => false,
                'orderId'                   => $d->order_id,
                'companyId'                 => $d->order?->company_id,
                'companyName'               => $d->order?->company?->name,
                'transportName'             => $d->transporter?->name ?? $d->order?->transporter?->name,
                'salesPerson'               => $d->order?->creator?->name ?? 'N/A',
                'salesBy'                   => $d->order?->creator?->name ?? 'N/A',
                'dispatchedBy'              => $d->user?->name,
                'lrImage'                   => $effectiveLr,
                'lrViewUrl'                 => $effectiveViewUrl,
                'hasPhysicalLr'             => $hasPhysicalLr,
                'hasRecordLr'               => $hasRecordLr,
                'effectiveLrLogId'          => $effectiveLrLogId,
                'lrVersion'                 => $ver,
                'ownLrImage'                => $ownLr,
                'isOrderLrCopy'             => empty($ownLr) && !empty($effectiveLr),
                'lrImages'                  => $ownValidImages,
                'orderLrCopies'             => $orderLrCopies,
                'orderTotal'                => $d->order?->total,
                'status'                    => $d->order?->status,
                'dispatchStatus'            => $computedStatus,
                'date'                      => $d->created_at ? $d->created_at->toISOString() : ($d->date ? \Carbon\Carbon::parse($d->date)->toISOString() : now()->toISOString()),
                'dispatchDate'              => $d->created_at ? $d->created_at->timezone('Asia/Kolkata')->format('d-m-Y, h:i A') : ($d->date ? \Carbon\Carbon::parse($d->date)->timezone('Asia/Kolkata')->format('d-m-Y, h:i A') : now()->timezone('Asia/Kolkata')->format('d-m-Y, h:i A')),
                'notes'                     => $d->notes ?: $d->order?->notes,
                'dispatchNotes'             => $d->notes,
                'orderNotes'                => $d->order?->notes,
                'totalPrevDispatchedQty'    => (float) $totalPrevDispatched,
                'totalCurrentDispatchedQty' => (float) $totalCurrentDispatched,
                'totalRemainingQty'         => (float) $totalRemaining,
                'totalOrderQty'             => (float) $totalOrdered,
                'items'                     => $items,
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
        $orders = Order::with(['company', 'transporter', 'items.product', 'dispatchLogs.user', 'creator'])
            ->where(function($q) {
                $q->where('status', '!=', 'CANCELLED')
                  ->orWhereNull('status');
            })
            ->orderByDesc('created_at')
            ->get();

        $reportOrders = collect();

        foreach ($orders as $o) {
            foreach ($o->items as $oi) {
                $oi->syncDispatchedQty();
            }

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

            // Resolve dispatch date & logs
            $sortedLogs = $o->dispatchLogs ? $o->dispatchLogs->sortByDesc(fn($l) => $l->created_at ? $l->created_at->timestamp : $l->id)->values() : collect();
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
            } elseif ($isFullyDispatched || $dispatchedQty > 0) {
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
                ];
            })->values()->toArray();

            $reportOrders->push([
                'id'                  => $o->id,
                'orderId'             => $o->id,
                'latestDispatchLogId' => $latestDispatchLog?->id,
                'companyId'           => $o->company_id,
                'companyName'         => $o->company?->name,
                'transportName'       => $o->transporter?->name,
                'salesPerson'         => $o->creator?->name ?? 'N/A',
                'salesBy'             => $o->creator?->name ?? 'N/A',
                'orderTotal'          => $o->total,
                'status'              => $o->status,
                'dispatchStatus'      => $dispatchStatus,
                'dispatchDate'        => $dispatchDate,
                'dispatchDateTime'    => $dispatchDateTime,
                'rawDispatchDate'     => $rawDispatchDate,
                'dispatchLogs'        => $dispatchLogsData,
                'date'                => $o->created_at ? $o->created_at->toISOString() : ($o->date ? \Carbon\Carbon::parse($o->date)->toISOString() : now()->toISOString()),
                'dueDate'             => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('d-m-Y') : null,
                'rawDueDate'          => $o->due_date ? \Carbon\Carbon::parse($o->due_date)->format('Y-m-d') : null,
                'notes'               => $o->notes,
                'totalQty'            => $totalQty,
                'dispatchedQty'       => $dispatchedQty,
                'remainingQty'        => $remainingQty,
                'items'               => $o->items->map(fn($i) => [
                    'id'                 => $i->id,
                    'productName'        => $i->product?->name ?? 'Unknown',
                    'rawProductName'     => $i->product?->name ?? 'Unknown',
                    'formattedName'      => $i->product ? $i->product->formatName($i->grade) : 'Unknown',
                    'grade'              => $i->grade,
                    'productType'        => $i->product?->type,
                    'quantity'           => (float) $i->quantity,
                    'dispatchedQty'      => (float) $i->dispatched_qty,
                    'remainingQty'       => (float) $i->remainingQty(),
                    'itemDispatchStatus' => $this->resolveItemDispatchStatus((float) $i->quantity, (float) $i->dispatched_qty),
                    'price'              => (float) ($i->price > 0 ? $i->price : ($i->product?->rate ?? 0)),
                    'rate'               => (float) ($i->price > 0 ? $i->price : ($i->product?->rate ?? 0)),
                ])->values(),
            ]);
        }

        $today = now()->timezone('Asia/Kolkata')->startOfDay();
        $sortedOrders = $reportOrders->sortBy(function($d) use ($today) {
            $st = strtoupper(trim(str_replace('_', ' ', (string)($d['dispatchStatus'] ?? $d['status'] ?? ''))));
            $isDone = in_array($st, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED', 'FULLY_DISPATCHED']) ||
                      ((float)($d['totalQty'] ?? 0) > 0 && (float)($d['remainingQty'] ?? 0) <= 0);

            $rawDueDate = $d['rawDueDate'] ?? null;
            $diffDays = null;
            if ($rawDueDate) {
                $dueObj = \Carbon\Carbon::parse($rawDueDate, 'Asia/Kolkata')->startOfDay();
                $diffDays = (int) $today->diffInDays($dueObj, false);
            }

            if (!$isDone && $diffDays !== null && $diffDays === 0) {
                // DUE TODAY -> TOP (Group 0)
                return sprintf('0_%010d', 9999999999 - (int)($d['id'] ?? 0));
            } elseif (!$isDone && $diffDays !== null && $diffDays < 0) {
                // OVERDUE -> Group 1 (most recent overdue first: 1 day overdue, 2 days overdue...)
                return sprintf('1_%06d_%010d', abs($diffDays), 9999999999 - (int)($d['id'] ?? 0));
            } elseif (!$isDone && $diffDays !== null && $diffDays > 0) {
                // UPCOMING -> Group 2 (closest due date first: 1 day left, 2 days left...)
                return sprintf('2_%06d_%010d', $diffDays, 9999999999 - (int)($d['id'] ?? 0));
            } elseif (!$isDone) {
                // NO DUE DATE -> Group 3
                return sprintf('3_%010d', 9999999999 - (int)($d['id'] ?? 0));
            } elseif ($isDone) {
                // COMPLETED -> Group 4 (Sorted by latest dispatch date descending: 3rd Nov on top, 2nd Nov below, etc.)
                $dispTime = !empty($d['rawDispatchDate']) ? strtotime($d['rawDispatchDate']) : (!empty($d['dispatchDate']) ? strtotime($d['dispatchDate']) : strtotime($d['date']));
                return sprintf('4_%012d_%010d', 999999999999 - $dispTime, 9999999999 - (int)($d['id'] ?? 0));
            } else {
                // CANCELLED -> Group 5
                return sprintf('5_%012d_%010d', 999999999999 - strtotime($d['date']), 9999999999 - (int)($d['id'] ?? 0));
            }
        })->values();

        $companies = Company::orderBy('name')->get()->map(fn($c) => [
            'id'   => $c->id,
            'name' => strtoupper($c->name ?? '')
        ]);

        $pageData = [
            'orders'    => $sortedOrders,
            'companies' => $companies,
        ];
        return view('dispatch.report', compact('pageData'));
    }

    public function profile()
    {
        return view('dispatch.profile');
    }
    public function revertDispatch(Request $request, $param1, $param2 = null)
    {
        $id = ($param2 !== null && !is_object($param2)) ? $param2 : $param1;
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

            // Delete the log image copies if exists
            if ($log->lr_image_path) {
                foreach (self::getLrImagePaths($log) as $rp) {
                    self::deletePhysicalLrCopies($rp);
                }
            }

            // Delete the dispatch log itself
            $log->delete();

            // Update order status accurately
            $order->refresh();
            $order->load('items');
            $anyDispatched = $order->items->filter(fn($item) => (float)$item->dispatched_qty > 0.001)->isNotEmpty();
            $allDone = $order->items->isNotEmpty() && $order->items->every(fn($item) => $item->remainingQty() <= 0.001);
            
            $order->update([
                'status' => $allDone ? 'CLOSED' : 'OPEN',
                'dispatch_status' => $allDone ? 'DONE' : ($anyDispatched ? 'PARTIAL' : 'PENDING')
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Dispatch reverted successfully!']);
    }

    public function clearHistory(Request $request)
    {
        return app(\App\Http\Controllers\SalesController::class)->clearHistory($request);
    }
}
