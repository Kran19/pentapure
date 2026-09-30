@extends('layouts.admin')

@section('content')
@php
  $currentStatus = strtoupper(trim((string)($pageData['filters']['status'] ?? request('status', 'ALL'))));
  if (!$currentStatus) $currentStatus = 'ALL';

  $pdfRoute = Route::has(request()->segment(1) . '.dispatch.pdf') 
    ? route(request()->segment(1) . '.dispatch.pdf') 
    : (Route::has('admin.dispatch.pdf') ? route('admin.dispatch.pdf') : url(request()->segment(1) . '/dispatch-activity/pdf'));

  $activityRoute = Route::has(request()->segment(1) . '.dispatch.activity') 
    ? route(request()->segment(1) . '.dispatch.activity') 
    : (Route::has('admin.dispatch.activity') ? route('admin.dispatch.activity') : url(request()->segment(1) . '/dispatch-activity'));
@endphp

<style>
.status-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0.42rem 0.85rem;
  border-radius: 7px;
  font-size: 0.82rem;
  font-weight: 600;
  text-decoration: none;
  color: var(--text-muted, #9ca3af);
  transition: all 0.2s ease;
  white-space: nowrap;
}
.status-tab-btn:hover {
  color: var(--text-main, #ffffff);
  background: rgba(255, 255, 255, 0.05);
}
.status-tab-btn .tab-badge {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.12);
  color: inherit;
}

/* Active Tab Styles */
.status-tab-btn.active-all {
  background: var(--primary, #D88A00);
  color: #000000 !important;
  font-weight: 700;
}
.status-tab-btn.active-all .tab-badge {
  background: rgba(0, 0, 0, 0.22);
  color: #000000;
}

.status-tab-btn.active-pending {
  background: #ef4444;
  color: #ffffff !important;
  font-weight: 700;
}
.status-tab-btn.active-pending .tab-badge {
  background: rgba(0, 0, 0, 0.25);
  color: #ffffff;
}

.status-tab-btn.active-partial {
  background: #f59e0b;
  color: #ffffff !important;
  font-weight: 700;
}
.status-tab-btn.active-partial .tab-badge {
  background: rgba(0, 0, 0, 0.25);
  color: #ffffff;
}

.status-tab-btn.active-done {
  background: #16a34a;
  color: #ffffff !important;
  font-weight: 700;
}
.status-tab-btn.active-done .tab-badge {
  background: rgba(0, 0, 0, 0.25);
  color: #ffffff;
}

@media (max-width: 720px) {
  .dispatch-item-badges {
    grid-template-columns: repeat(3, 1fr) !important;
    width: 100% !important;
  }
  .dispatch-filter-row {
    flex-direction: column !important;
    align-items: stretch !important;
  }
  .status-tabs-wrapper {
    justify-content: center !important;
  }
}
</style>

<div style="padding:1.5rem;">
  <!-- Title and PDF Export Bar -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">🚚 Dispatch Order Activity</h2>
    <button type="button" class="btn" onclick="window.downloadPdfAsync('{{ $pdfRoute }}', {{ json_encode(request()->all()) }}, this)" style="width:auto; padding:0.6rem 1.2rem; background:var(--secondary); cursor:pointer;">
      📥 Download PDF Report
    </button>
  </div>

  <!-- Filter Card with Search/Date on Left and Status Tabs on Right -->
  <div class="card" style="padding:1rem 1.2rem; margin-bottom:1.5rem; background:var(--card-bg, rgba(255,255,255,0.03)); border:1px solid var(--glass-border, rgba(255,255,255,0.06)); border-radius:12px;">
    <div class="dispatch-filter-row" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
      
      <!-- Left: Search and Date Filter Form -->
      <form method="GET" action="{{ $activityRoute }}" style="display:flex; gap:0.6rem; flex-wrap:wrap; align-items:center; flex:1; min-width:320px;">
        <input type="hidden" name="status" value="{{ $currentStatus }}">
        <div style="flex:2; min-width:200px;">
          <input type="text" name="q" class="form-control" placeholder="Search Order #, Company, Product..." value="{{ request('q') }}" style="width:100%; padding:0.55rem 0.8rem; font-size:0.85rem; border-radius:8px;">
        </div>
        <div style="flex:1; min-width:130px;">
          <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" title="From Date" style="width:100%; padding:0.55rem 0.6rem; font-size:0.85rem; border-radius:8px;">
        </div>
        <div style="flex:1; min-width:130px;">
          <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" title="To Date" style="width:100%; padding:0.55rem 0.6rem; font-size:0.85rem; border-radius:8px;">
        </div>
        <div style="display:flex; gap:0.4rem;">
          <button type="submit" class="btn" style="width:auto; padding:0.55rem 1rem; font-size:0.85rem;">🔍 Filter</button>
          <a href="{{ $activityRoute }}" class="btn" style="width:auto; padding:0.55rem 0.85rem; font-size:0.85rem; background:var(--glass-bg, rgba(255,255,255,0.05)); color:var(--text-main, #ffffff); text-decoration:none;">🔄 Reset</a>
        </div>
      </form>

      <!-- Right: Status Tabs (All, Pending, Partial, Fully Dispatched) -->
      <div style="display:flex; align-items:center; justify-content:flex-end;">
        <div class="status-tabs-wrapper" style="display:inline-flex; align-items:center; background:rgba(0,0,0,0.22); padding:4px; border-radius:10px; border:1px solid var(--glass-border, rgba(255,255,255,0.08)); gap:4px; flex-wrap:wrap;">
          <!-- All -->
          <a href="{{ request()->fullUrlWithQuery(['status' => 'ALL', 'page' => 1]) }}"
             class="status-tab-btn {{ in_array($currentStatus, ['', 'ALL']) ? 'active-all' : '' }}" title="Show All Orders">
            All <span class="tab-badge">{{ $pageData['statusCounts']['ALL'] ?? 0 }}</span>
          </a>
          <!-- Pending -->
          <a href="{{ request()->fullUrlWithQuery(['status' => 'PENDING', 'page' => 1]) }}"
             class="status-tab-btn {{ $currentStatus === 'PENDING' ? 'active-pending' : '' }}" title="Filter Pending Orders">
            Pending <span class="tab-badge">{{ $pageData['statusCounts']['PENDING'] ?? 0 }}</span>
          </a>
          <!-- Partial -->
          <a href="{{ request()->fullUrlWithQuery(['status' => 'PARTIAL', 'page' => 1]) }}"
             class="status-tab-btn {{ in_array($currentStatus, ['PARTIAL', 'PARTIAL_PENDING', 'PARTIAL_DISPATCH']) ? 'active-partial' : '' }}" title="Filter Partial Orders">
            Partial <span class="tab-badge">{{ $pageData['statusCounts']['PARTIAL'] ?? 0 }}</span>
          </a>
          <!-- Fully Dispatched -->
          <a href="{{ request()->fullUrlWithQuery(['status' => 'FULLY_DISPATCH', 'page' => 1]) }}"
             class="status-tab-btn {{ in_array($currentStatus, ['FULLY_DISPATCH', 'FULLY_DISPATCHED', 'DONE']) ? 'active-done' : '' }}" title="Filter Fully Dispatched Orders">
            Fully Dispatched <span class="tab-badge">{{ $pageData['statusCounts']['FULLY_DISPATCH'] ?? 0 }}</span>
          </a>
        </div>
      </div>

    </div>
  </div>

  <!-- Orders Card Accordion List -->
  @if($pageData['orders']->isEmpty())
    <div class="card" style="padding:3rem; text-align:center; color:var(--text-muted); border-radius:12px; background:var(--card-bg, rgba(255,255,255,0.03)); border:1px solid var(--glass-border, rgba(255,255,255,0.06));">
      <p style="margin:0; font-size:1rem;">No orders found matching the selected filter criteria.</p>
    </div>
  @else
    <div style="display:flex; flex-direction:column; gap:10px;">
      @foreach($pageData['orders'] as $order)
        @php
          $orderDate = $order->date ? \Carbon\Carbon::parse($order->date) : $order->created_at;
          $rawSt = strtoupper(trim((string)($order->dispatch_status ?? $order->status ?? 'PENDING')));
          
          $totalOrderQty = (float) $order->items->sum('quantity');
          $totalDispatchedQty = (float) $order->items->sum('dispatched_qty');
          $totalRemainingQty = max(0, $totalOrderQty - $totalDispatchedQty);

          $statusBadge = '';
          if (in_array($rawSt, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED', 'FULLY_DISPATCHED']) || ($totalOrderQty > 0 && $totalRemainingQty <= 0)) {
            $statusBadge = '<span class="badge" style="font-size:0.72rem; background:#16a34a; color:#ffffff !important; padding:3px 9px; border-radius:5px; font-weight:700; letter-spacing:0.3px;">FULLY DISPATCHED</span>';
          } elseif (in_array($rawSt, ['PARTIAL', 'PARTIAL DISPATCH', 'PARTIAL PENDING', 'PARTIAL_PENDING', 'PARTIAL_DISPATCH', 'PARTIALLY DISPATCHED']) || ($totalDispatchedQty > 0 && $totalRemainingQty > 0)) {
            $statusBadge = '<span class="badge" style="font-size:0.72rem; background:#f59e0b; color:#ffffff !important; padding:3px 9px; border-radius:5px; font-weight:700; letter-spacing:0.3px;">PARTIAL</span>';
          } else {
            $statusBadge = '<span class="badge" style="font-size:0.72rem; background:#ef4444; color:#ffffff !important; padding:3px 9px; border-radius:5px; font-weight:700; letter-spacing:0.3px;">PENDING</span>';
          }

          $orderTotal = (float)($order->total ?: $order->items->sum(fn($i) => (float)$i->quantity * (float)$i->price));
          $companyName = strtoupper($order->company?->name ?? 'N/A');
          $salesPerson = strtoupper($order->creator?->name ?? 'N/A');
          $transportName = strtoupper($order->transporter?->name ?? 'N/A');
        @endphp

        <div class="card dispatch-history-card" style="margin-bottom:0; padding:0; overflow:hidden; border-radius:12px; border:1px solid var(--glass-border, rgba(255,255,255,0.06)); background:var(--card-bg, rgba(255,255,255,0.03)); transition:all 0.2s ease;">
          <!-- Clickable Header Row -->
          <div onclick="toggleReportAccordion('act-acc-{{ $order->id }}', this)" style="cursor:pointer; padding:1.1rem; display:flex; justify-content:space-between; align-items:center; user-select:none;">
            <div style="flex:1; padding-right:15px;">
              <div style="font-weight:600; font-size:1rem; color:var(--text-main); line-height:1.3;">
                ORDER #{{ $order->id }} - {{ $companyName }}
              </div>
              <div style="margin-top:6px; font-size:0.8rem; color:var(--text-muted); display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                <span>SALES BY: <strong style="color:var(--text-main, #ffffff);">{{ $salesPerson }}</strong></span>
                <span>•</span>
                <span>TRANSPORTER: {{ $transportName }}</span>
                <span>•</span>
                <span>ORDERED: {{ $orderDate ? $orderDate->timezone('Asia/Kolkata')->format('d-m-Y, h:i A') : 'N/A' }}</span>
              </div>
            </div>
            <div style="display:flex; align-items:center; gap:12px; text-align:right; flex-wrap:nowrap;">
              <div style="display:flex; flex-direction:column; gap:5px; align-items:flex-end;">
                <div style="font-weight:700; font-size:1.1rem; color:var(--primary, #D88A00);">₹{{ number_format($orderTotal, 2) }}</div>
                {!! $statusBadge !!}
              </div>
              <div class="acc-chevron" style="transition:transform 0.25s ease; color:var(--text-muted); display:flex; align-items:center;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </div>
            </div>
          </div>

          <!-- Expandable Details Dropdown / Collapsible -->
          <div id="act-acc-{{ $order->id }}" class="act-accordion-content" style="display:none; padding:1.2rem; border-top:1px solid var(--glass-border, rgba(255,255,255,0.06)); background:rgba(0,0,0,0.02);">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:1rem; margin-bottom:1rem;">
              <div>
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Order ID</div>
                <div style="display:flex; align-items:center; gap:8px;">
                  <span style="font-weight:700;">#{{ $order->id }}</span>
                  <a href="{{ url(request()->segment(1) . '/sales/order/pdf/' . $order->id) }}" target="_blank" onclick="event.stopPropagation()" class="btn btn-sm" style="width:auto; padding:0.2rem 0.55rem; font-size:0.72rem; text-decoration:none; display:inline-flex; align-items:center; gap:3px; font-weight:600; background:var(--primary, #D88A00); color:#000000; border-radius:4px;">
                    📄 Order PDF
                  </a>
                </div>
              </div>
              <div>
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Date & Time</div>
                <div style="font-size:0.85rem; font-weight:500;">{{ $orderDate ? $orderDate->timezone('Asia/Kolkata')->format('d-m-Y, h:i:s A') : 'N/A' }}</div>
              </div>
              <div>
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Company</div>
                <div style="font-weight:600; font-size:0.9rem;">{{ $companyName }}</div>
              </div>
              <div>
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Sales By</div>
                <div style="font-weight:600; font-size:0.9rem; color:var(--text-main, #ffffff);">{{ $salesPerson }}</div>
              </div>
              <div>
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Transport</div>
                <div style="font-weight:600; font-size:0.9rem;">{{ $transportName }}</div>
              </div>
              <div>
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Total Order Qty</div>
                <div style="font-weight:700; font-size:0.95rem;">{{ number_format($totalOrderQty, 3) }} kg</div>
              </div>
              <div>
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Dispatched Qty</div>
                <div style="font-weight:700; font-size:0.95rem; color:#16a34a;">{{ number_format($totalDispatchedQty, 3) }} kg</div>
              </div>
              <div>
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Remaining Qty</div>
                <div style="font-weight:700; font-size:0.95rem; color:#ef4444;">{{ number_format($totalRemainingQty, 3) }} kg</div>
              </div>
              <div>
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Sales Note</div>
                <div style="font-weight:600; font-size:0.88rem; color:{{ !empty(trim((string)$order->notes)) ? 'var(--primary, #D88A00)' : 'var(--text-muted)' }};">
                  {{ !empty(trim((string)$order->notes)) ? $order->notes : '—' }}
                </div>
              </div>
            </div>

            <!-- Dispatch User Info & LR Copy (if available) -->
            @php
              $lastLog = $order->dispatchLog ?? $order->dispatchLogs?->last();
            @endphp
            @if($lastLog)
              <div style="margin-bottom:1rem; padding:8px 12px; background:rgba(255,255,255,0.03); border-radius:6px; display:flex; align-items:center; gap:16px; font-size:0.82rem; flex-wrap:wrap;">
                @if($lastLog->user)
                  <span style="color:var(--text-muted);">Dispatched by: <strong style="color:var(--text-main);">{{ $lastLog->user->name }}</strong></span>
                @endif
                @if($lastLog->lr_image_path)
                  <a href="javascript:void(0)" onclick="app.viewImage('{{ asset($lastLog->lr_image_path) }}')" style="color:var(--primary, #D88A00); font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    View LR Copy
                  </a>
                @endif
              </div>
            @endif

            <!-- Sales Note / Special Instructions -->
            @if(!empty(trim((string)$order->notes)))
              <div style="margin-bottom:1rem; padding:10px 14px; background:rgba(216,138,0,0.08); border-left:3px solid var(--primary, #D88A00); border-radius:6px; display:flex; gap:10px; align-items:flex-start;">
                <span style="font-size:1.1rem; line-height:1;">📝</span>
                <div style="flex:1;">
                  <div style="color:var(--primary, #D88A00); font-size:0.75rem; text-transform:uppercase; font-weight:700; margin-bottom:3px; letter-spacing:0.3px;">Sales Note / Instructions</div>
                  <div style="font-size:0.88rem; color:var(--text-main, #ffffff); line-height:1.45; word-break:break-word; white-space:pre-wrap;">{{ $order->notes }}</div>
                </div>
              </div>
            @endif

            <!-- Ordered Items List -->
            @if($order->items && count($order->items) > 0)
              <div style="margin-bottom:0.5rem; background:rgba(0,0,0,0.15); border-radius:8px; padding:12px; border-left:3px solid var(--primary, #D88A00);">
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; margin-bottom:8px; font-weight:bold;">Ordered Items List</div>
                @foreach($order->items as $item)
                  @php
                    $rawName = $item->product?->name ?? 'Unknown';
                    $pName = trim(preg_replace('/\s*\((FG|SEMI|RAW|FINISHED)\)$/i', '', $rawName));
                    $gName = ($item->grade && $item->grade !== 'NONE' && $item->grade !== 'N/A') ? trim($item->grade) : '';
                    if ($gName) {
                      $pName = trim(preg_replace('/\s+' . preg_quote($gName, '/') . '$/i', '', $pName));
                    }
                    $rawType = strtoupper((string)($item->product?->type ?? 'FINISHED'));
                    $tName = ($rawType === 'FINISHED' || $rawType === 'FG') ? 'FG' : ($rawType === 'SEMI' ? 'SEMI' : ($rawType === 'RAW' ? 'RAW' : $rawType));
                    $tot = (float)($item->quantity ?? 0);
                    $disp = (float)($item->dispatched_qty ?? 0);
                    $rem = max(0, (float)$item->remainingQty());
                    $fmtQty = fn($val) => (floor($val) == $val ? number_format($val, 0) : number_format($val, 2)) . ' kg';
                  @endphp
                  <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.88rem; flex-wrap:wrap; gap:12px;">
                    <div style="flex:1; min-width:200px; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                      <span style="font-weight:600; color:var(--text-main, #ffffff);">{{ $pName }}</span>
                      @if($gName)
                        <strong style="font-weight:800; color:var(--primary, #D88A00);">{{ $gName }}</strong>
                      @endif
                      <span style="color:var(--text-muted, #9ca3af); font-size:0.78rem; font-weight:700;">({{ $tName }})</span>
                    </div>
                    <div class="dispatch-item-badges" style="display:grid; grid-template-columns:135px 145px 145px; gap:8px; align-items:center; flex-shrink:0;">
                      <span style="background:rgba(255,255,255,0.08); padding:4px 8px; border-radius:6px; border:1px solid rgba(255,255,255,0.15); font-weight:600; color:var(--text-main, #ffffff); width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
                        <span style="color:var(--text-muted, #9ca3af); font-size:0.72rem; font-weight:700;">ORDER:</span>
                        <strong style="color:var(--primary, #D88A00);">{{ $fmtQty($tot) }}</strong>
                      </span>
                      <span style="background:rgba(22,163,74,0.12); padding:4px 8px; border-radius:6px; border:1px solid rgba(22,163,74,0.3); font-weight:700; color:#16a34a; width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
                        <span style="font-size:0.72rem;">DISPATCHED:</span>
                        <strong>{{ $fmtQty($disp) }}</strong>
                      </span>
                      <span style="background:rgba(239,68,68,0.12); padding:4px 8px; border-radius:6px; border:1px solid rgba(239,68,68,0.3); font-weight:700; color:#ef4444; width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
                        <span style="font-size:0.72rem;">PENDING:</span>
                        <strong>{{ $fmtQty($rem) }}</strong>
                      </span>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      @endforeach
    </div>

    <!-- Pagination -->
    <div style="margin-top:1.5rem; display:flex; justify-content:center;">
      {{ $pageData['orders']->links() }}
    </div>
  @endif
</div>

<script>
  function toggleReportAccordion(contentId, headerEl) {
    const content = document.getElementById(contentId);
    if (!content) return;
    const chevron = headerEl.querySelector('.acc-chevron');
    const isHidden = content.style.display === 'none' || content.style.display === '';
    
    if (isHidden) {
      content.style.display = 'block';
      if (chevron) chevron.style.transform = 'rotate(180deg)';
    } else {
      content.style.display = 'none';
      if (chevron) chevron.style.transform = 'rotate(0deg)';
    }
  }
</script>
@endsection
