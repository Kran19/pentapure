@php
  $oStatus = strtoupper(str_replace('_', ' ', $dOrder['status'] ?? ''));
  $ds = strtoupper(str_replace('_', ' ', $dOrder['dispatchStatus'] ?? 'PENDING'));
  if ($oStatus === 'CANCELLED') {
    $statusText = 'CANCELLED';
    $badgeStyle = 'background:#dc2626; color:#fff;';
  } elseif ($ds === 'DONE' || $ds === 'COMPLETED' || $ds === 'FULLY DISPATCHED' || $ds === 'DISPATCHED') {
    $statusText = 'FULLY DISPATCHED';
    $badgeStyle = 'background:#16a34a; color:#fff;';
  } elseif (in_array($ds, ['PARTIAL', 'PARTIAL DISPATCH', 'PARTIALLY DISPATCHED', 'PARTIAL PENDING', 'PARTIAL_PENDING', 'PARTIAL_DISPATCH'])) {
    $statusText = 'PARTIAL';
    $badgeStyle = 'background:#f59e0b; color:#fff;';
  } else {
    $statusText = 'PENDING';
    $badgeStyle = 'background:#3b82f6; color:#fff;';
  }
  $canEdit = ($dOrder['status'] ?? '') === 'OPEN' && ($ds === 'PENDING' || $ds === 'UNASSIGNED' || empty($dOrder['dispatchStatus']));

  $dueDaysBadge = '';
  if (!empty($dOrder['rawDueDate'])) {
    $todayKolkata = now()->timezone('Asia/Kolkata')->startOfDay();
    $diffDays = (int) $todayKolkata->diffInDays(\Carbon\Carbon::parse($dOrder['rawDueDate'], 'Asia/Kolkata')->startOfDay(), false);
    if ($diffDays < 0) {
      $days = abs($diffDays);
      $dueDaysBadge = '<span style="font-size:0.72rem; padding:2px 7px; border-radius:4px; font-weight:700; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; margin-left:6px;">' . $days . ' ' . ($days === 1 ? 'day' : 'days') . ' overdue</span>';
    } elseif ($diffDays === 0) {
      $dueDaysBadge = '<span style="font-size:0.72rem; padding:2px 7px; border-radius:4px; font-weight:700; background:#fffbeb; border:1px solid #fde68a; color:#b45309; margin-left:6px;">Due today</span>';
    } else {
      $dueDaysBadge = '<span style="font-size:0.72rem; padding:2px 7px; border-radius:4px; font-weight:700; background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; margin-left:6px;">' . $diffDays . ' ' . ($diffDays === 1 ? 'day' : 'days') . ' left</span>';
    }
  }
@endphp
<div class="sales-order-card" style="padding:1rem; border-radius:10px; border:1px solid var(--glass-border, rgba(255,255,255,0.08)); background:var(--card-bg, rgba(255,255,255,0.02)); transition:background 0.2s ease;">
  <!-- Header Row (Clickable) -->
  <div class="sales-card-header" onclick="toggleSalesOrderDetails('order-details-{{ $dOrder['id'] }}', this)" style="cursor:pointer; display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; user-select:none;">
    <div style="flex:1; min-width:240px;">
      <div style="font-weight:700; font-size:1.05rem; color:var(--text-main); display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
        <span>#{{ strtoupper((string)$dOrder['id']) }}</span>
        <span>—</span>
        <span>{{ $dOrder['companyName'] ?? 'N/A' }}</span>
      </div>
      <div style="margin-top:6px; font-size:0.82rem; color:var(--text-muted); display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
        <span>Ordered: <strong>{{ \Carbon\Carbon::parse($dOrder['date'])->format('d-m-Y') }}</strong></span>
        <span>•</span>
        <span>EXPECTED DELIVERY DATE: <strong style="color:var(--text-main, #111827); font-weight:700;">{{ $dOrder['dueDate'] ?? 'NOT SPECIFIED' }}</strong>{!! $dueDaysBadge !!}</span>
        @if(!empty($dOrder['transportName']))
          <span>•</span>
          <span>Transport: <strong>{{ $dOrder['transportName'] }}</strong></span>
        @endif
      </div>
    </div>

    <div style="display:flex; align-items:center; gap:10px; text-align:right; flex-wrap:wrap; justify-content:flex-end;">
      <span class="badge" style="{{ $badgeStyle }} font-size:0.75rem; padding:4px 10px; border-radius:10px; font-weight:700;">
        {{ $statusText }}
      </span>
      <div style="font-weight:800; color:var(--primary, #D88A00); font-size:1.15rem; white-space:nowrap;">
        ₹{{ number_format($dOrder['total'] ?? 0, 2) }}
      </div>
      <button type="button" class="btn btn-sm btn-order-dropdown" onclick="event.stopPropagation(); toggleSalesOrderDetails('order-details-{{ $dOrder['id'] }}', this.closest('.sales-card-header'))" style="width:auto; padding:0.35rem 0.75rem; font-size:0.78rem; font-weight:700; border-radius:6px; background:rgba(216, 138, 0, 0.1); color:var(--primary, #D88A00) !important; border:1px solid rgba(216, 138, 0, 0.35); display:inline-flex; align-items:center; gap:5px; cursor:pointer; transition:all 0.2s ease;">
        <span class="dropdown-label">View Detail</span>
        <svg class="dropdown-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="transition:transform 0.25s ease;">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </button>
    </div>
  </div>

  <!-- Collapsible Dropdown Details Drawer -->
  <div id="order-details-{{ $dOrder['id'] }}" class="order-details-drawer" style="display:none; margin-top:0.85rem; padding-top:0.85rem; border-top:1px dashed var(--glass-border, rgba(255,255,255,0.08));">
    <!-- Product items breakdown -->
    @if(!empty($dOrder['products']) && count($dOrder['products']) > 0)
      <div>
        <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:600; margin-bottom:6px;">Items to Dispatch:</div>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
          @foreach($dOrder['products'] as $prod)
            @php
              $totQ = (float)($prod['quantity'] ?? 0);
              $dispQ = (float)($prod['dispatchedQty'] ?? 0);
              $remQ = (float)($prod['remainingQty'] ?? max(0, $totQ - $dispQ));
              $fmtQ = fn($v) => (floor($v) == $v ? number_format($v, 0) : number_format($v, 2)) . ' kg';
            @endphp
            <span style="font-size:0.82rem; padding:4px 10px; background:rgba(0,0,0,0.03); border:1px solid var(--border-soft, rgba(0,0,0,0.08)); border-radius:6px; color:var(--text-main);">
              <strong>{{ $prod['productName'] ?? 'Item' }}</strong>
              @if(!empty($prod['grade'])) ({{ $prod['grade'] }}) @endif
              — Total: <span style="font-weight:700; color:var(--primary, #D88A00);">{{ $fmtQ($totQ) }}</span>
              @if($dispQ > 0)
                | <span style="color:#16a34a; font-weight:700;">Disp: {{ $fmtQ($dispQ) }}</span>
                | <span style="color:#dc2626; font-weight:700;">Rem: {{ $fmtQ($remQ) }}</span>
              @endif
            </span>
          @endforeach
        </div>
      </div>
    @endif

    @if(!empty($dOrder['notes']))
      <div style="margin-top:0.75rem; font-size:0.82rem; color:var(--text-muted); font-style:italic; background:rgba(0,0,0,0.02); padding:6px 10px; border-radius:6px; border-left:3px solid var(--primary, #D88A00);">
        <strong>Note:</strong> {{ $dOrder['notes'] }}
      </div>
    @endif

    <div style="display:flex; gap:8px; margin-top:0.85rem; justify-content:flex-end; flex-wrap:wrap;">
      @if($canEdit)
        <a class="btn btn-sm" href="{{ url(request()->segment(1) . '/action?edit=' . $dOrder['id']) }}" onclick="event.stopPropagation()" style="width:auto; padding:0.35rem 0.8rem; font-size:0.75rem; background:var(--warning, #FFA500); color:#000; font-weight:600; text-decoration:none; border-radius:6px;">
          ✏️ Edit Order
        </a>
      @endif
      <a class="btn btn-sm btn-secondary" href="{{ url((request()->segment(1) === 'sales' ? 'sales' : request()->segment(1) . '/sales') . '/order/pdf/' . $dOrder['id']) }}" target="_blank" onclick="event.stopPropagation()" style="width:auto; padding:0.35rem 0.8rem; font-size:0.75rem; text-decoration:none; border-radius:6px;">
        📄 Order PDF
      </a>
      <a class="btn btn-sm btn-secondary" href="{{ url(request()->segment(1) . '/history?q=' . $dOrder['id']) }}" onclick="event.stopPropagation()" style="width:auto; padding:0.35rem 0.8rem; font-size:0.75rem; text-decoration:none; border-radius:6px;">
        🔍 History Details
      </a>
    </div>
  </div>
</div>
