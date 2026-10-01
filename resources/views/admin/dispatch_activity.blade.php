@extends('layouts.admin')

@section('content')
@php
  $currentStatus = strtoupper(trim((string)($pageData['filters']['status'] ?? request('status', 'ALL'))));
  if (!$currentStatus) $currentStatus = 'ALL';
  $dateRange = request('range', $pageData['filters']['range'] ?? 'all');
  $startDate = request('start', request('date_from', $pageData['filters']['start'] ?? ''));
  $endDate = request('end', request('date_to', $pageData['filters']['end'] ?? ''));
  $companyId = request('company_id', $pageData['filters']['company_id'] ?? '');

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
  font-weight: 700;
  text-decoration: none;
  color: #4b5563;
  transition: all 0.2s ease;
  white-space: nowrap;
  text-transform: uppercase;
}
.status-tab-btn:hover {
  color: #111827;
  background: #f3f4f6;
}
.status-tab-btn .tab-badge {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 10px;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  color: #4b5563;
}

/* Active Tab Styles */
.status-tab-btn.active-all {
  background: var(--primary, #D88A00);
  color: #000000 !important;
  font-weight: 700;
}
.status-tab-btn.active-all .tab-badge {
  background: rgba(0, 0, 0, 0.18);
  border-color: transparent;
  color: #000000;
}

.status-tab-btn.active-pending {
  background: #ef4444;
  color: #ffffff !important;
  font-weight: 700;
}
.status-tab-btn.active-pending .tab-badge {
  background: rgba(0, 0, 0, 0.22);
  border-color: transparent;
  color: #ffffff;
}

.status-tab-btn.active-partial {
  background: #f59e0b;
  color: #ffffff !important;
  font-weight: 700;
}
.status-tab-btn.active-partial .tab-badge {
  background: rgba(0, 0, 0, 0.22);
  border-color: transparent;
  color: #ffffff;
}

.status-tab-btn.active-done {
  background: #16a34a;
  color: #ffffff !important;
  font-weight: 700;
}
.status-tab-btn.active-done .tab-badge {
  background: rgba(0, 0, 0, 0.22);
  border-color: transparent;
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
    width: 100%;
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

  <!-- Filter Card with Date Range, Company, Search & Status Tabs -->
  <div class="card" style="padding:1.1rem 1.2rem; margin-bottom:1.5rem; background:var(--bg-card, #ffffff); border:1px solid var(--border-soft, #e5e7eb); border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <form method="GET" action="{{ $activityRoute }}" style="display:flex; flex-direction:column; gap:10px; margin-bottom:12px;">
      <input type="hidden" name="status" value="{{ $currentStatus }}">

      <!-- 2 Dropdown Filters in 1 Line: Date Range & Company Name -->
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; align-items:center;">
        
        <!-- 1st: Date Range Filter -->
        <div>
          <select name="range" onchange="this.form.submit()" style="width:100%; padding:0.65rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, #ffffff); color:var(--text-main, #333); font-weight:600; font-size:0.88rem;">
            <option value="all" {{ $dateRange==='all'?'selected':'' }}>UP TO DATE</option>
            <option value="today" {{ $dateRange==='today'?'selected':'' }}>TODAY</option>
            <option value="yesterday" {{ $dateRange==='yesterday'?'selected':'' }}>YESTERDAY</option>
            <option value="this_week" {{ $dateRange==='this_week'?'selected':'' }}>THIS WEEK</option>
            <option value="last_week" {{ $dateRange==='last_week'?'selected':'' }}>LAST WEEK</option>
            <option value="this_month" {{ $dateRange==='this_month'?'selected':'' }}>THIS MONTH</option>
            <option value="last_month" {{ $dateRange==='last_month'?'selected':'' }}>LAST MONTH</option>
            <option value="custom" {{ $dateRange==='custom'?'selected':'' }}>CUSTOM RANGE</option>
          </select>
        </div>

        <!-- 2nd: Company Name Filter -->
        <div>
          <select name="company_id" onchange="this.form.submit()" style="width:100%; padding:0.65rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, #ffffff); color:var(--text-main, #333); font-weight:600; font-size:0.88rem;">
            <option value="">ALL COMPANIES</option>
            @foreach($pageData['companies'] ?? [] as $comp)
              <option value="{{ $comp['id'] }}" {{ (string)$companyId === (string)$comp['id'] ? 'selected' : '' }}>
                {{ strtoupper($comp['name']) }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      @if($dateRange === 'custom')
        <div style="display:flex; gap:10px; align-items:center;">
          <input type="date" name="start" value="{{ $startDate }}" onchange="this.form.submit()" title="From Date"
            style="flex:1; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, #ffffff); color:var(--text-main, #333);">
          <input type="date" name="end" value="{{ $endDate }}" onchange="this.form.submit()" title="To Date"
            style="flex:1; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, #ffffff); color:var(--text-main, #333);">
        </div>
      @endif

      <!-- Search Input Bar & Action Buttons -->
      <div style="display:flex; gap:8px; align-items:center;">
        <div style="flex:1;">
          <input type="text" name="q" placeholder="Search Order #, Company, Product..." value="{{ request('q') }}" onchange="this.form.submit()"
            style="width:100%; padding:0.6rem 0.9rem; font-size:0.88rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, #ffffff); color:var(--text-main, #333);">
        </div>
        <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.1rem; font-size:0.85rem;">🔍 Filter</button>
        <a href="{{ $activityRoute }}" class="btn" style="width:auto; padding:0.6rem 1rem; font-size:0.85rem; background:#ffffff; border:1px solid #e5e7eb; color:var(--text-main, #111827); text-decoration:none;">🔄 Reset</a>
      </div>
    </form>

    <!-- Bottom Row: Status Tabs & Orders Count -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; border-top:1px solid #f1f5f9; padding-top:12px;">
      <div class="status-tabs-wrapper" style="display:inline-flex; align-items:center; background:#ffffff; padding:4px; border-radius:10px; border:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,0.04); gap:4px; flex-wrap:wrap;">
        <!-- All -->
        <a href="{{ request()->fullUrlWithQuery(['status' => 'ALL', 'page' => 1]) }}"
           class="status-tab-btn {{ in_array($currentStatus, ['', 'ALL']) ? 'active-all' : '' }}" title="Show All Orders">
          ALL <span class="tab-badge">{{ $pageData['statusCounts']['ALL'] ?? 0 }}</span>
        </a>
        <!-- Pending -->
        <a href="{{ request()->fullUrlWithQuery(['status' => 'PENDING', 'page' => 1]) }}"
           class="status-tab-btn {{ $currentStatus === 'PENDING' ? 'active-pending' : '' }}" title="Filter Pending Orders">
          PENDING <span class="tab-badge">{{ $pageData['statusCounts']['PENDING'] ?? 0 }}</span>
        </a>
        <!-- Partial -->
        <a href="{{ request()->fullUrlWithQuery(['status' => 'PARTIAL', 'page' => 1]) }}"
           class="status-tab-btn {{ in_array($currentStatus, ['PARTIAL', 'PARTIAL_PENDING', 'PARTIAL_DISPATCH']) ? 'active-partial' : '' }}" title="Filter Partial Orders">
          PARTIAL <span class="tab-badge">{{ $pageData['statusCounts']['PARTIAL'] ?? 0 }}</span>
        </a>
        <!-- Fully Dispatched -->
        <a href="{{ request()->fullUrlWithQuery(['status' => 'FULLY_DISPATCH', 'page' => 1]) }}"
           class="status-tab-btn {{ in_array($currentStatus, ['FULLY_DISPATCH', 'FULLY_DISPATCHED', 'DONE']) ? 'active-done' : '' }}" title="Filter Fully Dispatched Orders">
          FULLY DISPATCHED <span class="tab-badge">{{ $pageData['statusCounts']['FULLY_DISPATCH'] ?? 0 }}</span>
        </a>
      </div>

      <div style="font-size:0.85rem; color:var(--text-muted); font-weight:600;">
        Showing <strong>{{ $pageData['orders']->total() }}</strong> {{ $pageData['orders']->total() == 1 ? 'order' : 'orders' }}
      </div>
    </div>
  </div>

  <!-- Orders Card Accordion List -->
  @if($pageData['orders']->isEmpty())
    <div class="card" style="padding:3rem; text-align:center; color:var(--text-muted); border-radius:12px; background:var(--bg-card, #ffffff); border:1px solid var(--border-soft, #e5e7eb);">
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

          $diffDays = null;
          $overdueBadge = '';
          if ($order->due_date && !in_array($rawSt, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED', 'FULLY_DISPATCHED'])) {
              $diffDays = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($order->due_date)->startOfDay(), false);
              if ($diffDays < 0) {
                  $days = abs($diffDays);
                  $overdueBadge = '<span style="display:inline-block; font-size:0.72rem; padding:2px 6px; border-radius:4px; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; font-weight:700; margin-left:4px;">' . $days . ' ' . ($days === 1 ? 'day' : 'days') . ' overdue</span>';
              } elseif ($diffDays === 0) {
                  $overdueBadge = '<span style="display:inline-block; font-size:0.72rem; padding:2px 6px; border-radius:4px; background:#fffbeb; border:1px solid #fde68a; color:#b45309; font-weight:700; margin-left:4px;">Due today</span>';
              }
          }
        @endphp

        <div class="card dispatch-history-card" style="margin-bottom:0; padding:0; overflow:hidden; border-radius:12px; border:1px solid var(--border-soft, #e5e7eb); background:var(--bg-card, #ffffff); box-shadow:0 1px 3px rgba(0,0,0,0.04); transition:all 0.2s ease;">
          <!-- Clickable Header Row -->
          <div onclick="toggleReportAccordion('act-acc-{{ $order->id }}', this)" style="cursor:pointer; padding:1.1rem; display:flex; justify-content:space-between; align-items:center; user-select:none;">
            <div style="flex:1; padding-right:15px;">
              <div style="font-weight:600; font-size:1rem; color:var(--text-main); line-height:1.3;">
                ORDER #{{ $order->id }} - {{ $companyName }}
              </div>
              <div style="margin-top:6px; font-size:0.8rem; color:var(--text-muted); display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                <span>SALES BY: <strong style="color:var(--text-main, #111827);">{{ $salesPerson }}</strong></span>
                <span>•</span>
                <span>TRANSPORTER: {{ $transportName }}</span>
                <span>•</span>
                <span>ORDERED: {{ $orderDate ? $orderDate->timezone('Asia/Kolkata')->format('d-m-Y, h:i A') : 'N/A' }}</span>
                <span>•</span>
                <span>DUE DATE: <strong style="color:var(--text-main, #111827); font-weight:700;">{{ $order->due_date ? \Carbon\Carbon::parse($order->due_date)->format('d-m-Y') : 'N/A' }}</strong>{!! $overdueBadge !!}</span>
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
          <div id="act-acc-{{ $order->id }}" class="act-accordion-content" style="display:none; padding:1.2rem; border-top:1px solid #f3f4f6; background:#ffffff;">
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
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Due Date</div>
                <div style="font-size:0.85rem; font-weight:700; color:var(--text-main, #111827);">{{ $order->due_date ? \Carbon\Carbon::parse($order->due_date)->format('d-m-Y') : '—' }} {!! $overdueBadge !!}</div>
              </div>
              <div>
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Company</div>
                <div style="font-weight:600; font-size:0.9rem;">{{ $companyName }}</div>
              </div>
              <div>
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Sales By</div>
                <div style="font-weight:600; font-size:0.9rem; color:var(--text-main, #111827);">{{ $salesPerson }}</div>
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
              <div style="margin-bottom:1rem; padding:8px 12px; background:#f9fafb; border:1px solid #f3f4f6; border-radius:6px; display:flex; align-items:center; gap:16px; font-size:0.82rem; flex-wrap:wrap;">
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
                  <div style="font-size:0.88rem; color:var(--text-main, #111827); line-height:1.45; word-break:break-word; white-space:pre-wrap;">{{ $order->notes }}</div>
                </div>
              </div>
            @endif

            <!-- Ordered Items List -->
            @if($order->items && count($order->items) > 0)
              <div style="margin-bottom:0.5rem; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; padding:12px; border-left:3px solid var(--primary, #D88A00); box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <div style="color:#6b7280; font-size:0.75rem; text-transform:uppercase; margin-bottom:8px; font-weight:bold;">Ordered Items List</div>
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
                  <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f3f4f6; font-size:0.88rem; flex-wrap:wrap; gap:12px;">
                    <div style="flex:1; min-width:200px; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                      <span style="font-weight:600; color:#111827;">{{ $pName }}</span>
                      @if($gName)
                        <strong style="font-weight:800; color:var(--primary, #D88A00);">{{ $gName }}</strong>
                      @endif
                      <span style="color:#6b7280; font-size:0.78rem; font-weight:700;">({{ $tName }})</span>
                    </div>
                    <div class="dispatch-item-badges" style="display:grid; grid-template-columns:135px 145px 145px; gap:8px; align-items:center; flex-shrink:0;">
                      <span style="background:#f9fafb; padding:4px 8px; border-radius:6px; border:1px solid #e5e7eb; font-weight:600; color:#111827; width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
                        <span style="color:#6b7280; font-size:0.72rem; font-weight:700;">ORDER:</span>
                        <strong style="color:var(--primary, #D88A00);">{{ $fmtQty($tot) }}</strong>
                      </span>
                      <span style="background:rgba(22,163,74,0.12); padding:4px 8px; border-radius:6px; border:1px solid rgba(22,163,74,0.3); font-weight:700; color:#15803d; width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
                        <span style="font-size:0.72rem;">DISPATCHED:</span>
                        <strong>{{ $fmtQty($disp) }}</strong>
                      </span>
                      <span style="background:rgba(239,68,68,0.12); padding:4px 8px; border-radius:6px; border:1px solid rgba(239,68,68,0.3); font-weight:700; color:#b91c1c; width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
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
