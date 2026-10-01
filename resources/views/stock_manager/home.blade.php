@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
<div style="padding: 1rem 0;">
  <h2 style="margin-bottom:1.5rem; color:var(--text-main);">📦 Stock Manager Dashboard</h2>

  @php
    $smPrefix = request()->segment(2) == 'stock-manager' ? request()->segment(1) . '/stock-manager' : request()->segment(1);
    $hasAnyLowStock = (($pageData['lowRawCount'] ?? 0) > 0) ||
                      (($pageData['lowSemiCount'] ?? 0) > 0) ||
                      (($pageData['lowFinishedCount'] ?? 0) > 0) ||
                      (($pageData['lowPackagingCount'] ?? 0) > 0);
  @endphp

  @if($hasAnyLowStock)
  <!-- Low Stock Alerts -->
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    <!-- Raw Material Stock -->
    @if(($pageData['lowRawCount'] ?? 0) > 0)
    <a href="{{ url($smPrefix . '/stock?type=raw&low_stock=1') }}" style="background-color: #dc2626; color: #ffffff; padding: 1rem; border-radius: 8px; border: 1px solid #b91c1c; display: flex; align-items: center; gap: 10px; text-decoration: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.25); transition: all 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 14px -1px rgba(220, 38, 38, 0.4)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(220, 38, 38, 0.25)';">
      <span style="font-size: 1.25rem;">⚠</span>
      <span style="font-size: 1.05rem; font-weight: 700; color: #ffffff; letter-spacing: 0.2px;">Raw Material Low Stock: {{ $pageData['lowRawCount'] }}</span>
    </a>
    @endif

    <!-- Semi-Finished Stock -->
    @if(($pageData['lowSemiCount'] ?? 0) > 0)
    <a href="{{ url($smPrefix . '/stock?type=semi&low_stock=1') }}" style="background-color: #dc2626; color: #ffffff; padding: 1rem; border-radius: 8px; border: 1px solid #b91c1c; display: flex; align-items: center; gap: 10px; text-decoration: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.25); transition: all 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 14px -1px rgba(220, 38, 38, 0.4)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(220, 38, 38, 0.25)';">
      <span style="font-size: 1.25rem;">⚠</span>
      <span style="font-size: 1.05rem; font-weight: 700; color: #ffffff; letter-spacing: 0.2px;">Semi-Finished Low Stock: {{ $pageData['lowSemiCount'] }}</span>
    </a>
    @endif

    <!-- FG Stock -->
    @if(($pageData['lowFinishedCount'] ?? 0) > 0)
    <a href="{{ url($smPrefix . '/stock?type=finished&low_stock=1') }}" style="background-color: #dc2626; color: #ffffff; padding: 1rem; border-radius: 8px; border: 1px solid #b91c1c; display: flex; align-items: center; gap: 10px; text-decoration: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.25); transition: all 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 14px -1px rgba(220, 38, 38, 0.4)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(220, 38, 38, 0.25)';">
      <span style="font-size: 1.25rem;">⚠</span>
      <span style="font-size: 1.05rem; font-weight: 700; color: #ffffff; letter-spacing: 0.2px;">FG Low Stock: {{ $pageData['lowFinishedCount'] }}</span>
    </a>
    @endif

    <!-- Packaging Stock -->
    @if(($pageData['lowPackagingCount'] ?? 0) > 0)
    <a href="{{ url($smPrefix . '/stock?type=packaging&low_stock=1') }}" style="background-color: #dc2626; color: #ffffff; padding: 1rem; border-radius: 8px; border: 1px solid #b91c1c; display: flex; align-items: center; gap: 10px; text-decoration: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.25); transition: all 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 14px -1px rgba(220, 38, 38, 0.4)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(220, 38, 38, 0.25)';">
      <span style="font-size: 1.25rem;">⚠</span>
      <span style="font-size: 1.05rem; font-weight: 700; color: #ffffff; letter-spacing: 0.2px;">Packaging Low Stock: {{ $pageData['lowPackagingCount'] }}</span>
    </a>
    @endif
  </div>
  @endif

  <!-- Stats Grid -->
  <div class="responsive-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    <div class="card" style="text-align:center; padding:1.2rem; background:var(--card-bg, rgba(255,255,255,0.05));">
      <div style="font-size:0.85rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Total Live Items</div>
      <div style="font-size:1.8rem; font-weight:bold; color:var(--primary); margin-top:5px;">{{ $pageData['totalItems'] }}</div>
    </div>
    <div class="card" style="text-align:center; padding:1.2rem; background:var(--card-bg, rgba(255,255,255,0.05));">
      <div style="font-size:0.85rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Total Net Qty</div>
      <div style="font-size:1.8rem; font-weight:bold; color:#10b981; margin-top:5px;">{{ number_format($pageData['totalNetQty'], 2) }} kg</div>
    </div>
    <div class="card" style="text-align:center; padding:1.2rem; background:var(--card-bg, rgba(255,255,255,0.05));">
      <div style="font-size:0.85rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Today Inward</div>
      <div style="font-size:1.8rem; font-weight:bold; color:#3b82f6; margin-top:5px;">{{ number_format($pageData['todayInward'], 2) }} kg</div>
    </div>
    <div class="card" style="text-align:center; padding:1.2rem; background:var(--card-bg, rgba(255,255,255,0.05));">
      <div style="font-size:0.85rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Today Outward</div>
      <div style="font-size:1.8rem; font-weight:bold; color:#ef4444; margin-top:5px;">{{ number_format($pageData['todayOutward'], 2) }} kg</div>
    </div>
  </div>

  <!-- Quick Action Navigation Cards -->
  <div class="responsive-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1.2rem;">
    @php
      $smPrefix = request()->segment(2) == 'stock-manager' ? request()->segment(1) . '/stock-manager' : request()->segment(1);
    @endphp
    <a href="{{ url($smPrefix . '/action') }}" class="card" style="text-decoration:none; padding:1.5rem; display:flex; flex-direction:column; align-items:center; text-align:center; border:1px solid var(--border-soft, #DDCFAF); transition:0.2s;">
      <div style="width:50px; height:50px; border-radius:50%; background:rgba(245, 158, 11, 0.15); display:flex; align-items:center; justify-content:center; margin-bottom:1rem; color:var(--primary);">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
      </div>
      <h3 style="margin:0 0 0.5rem 0; color:var(--text-main);">Stock Inward / Outward</h3>
      <p style="font-size:0.85rem; color:var(--text-muted); margin:0;">Record stock inward entries or process outward transfers</p>
    </a>

    <a href="{{ url($smPrefix . '/stock') }}" class="card" style="text-decoration:none; padding:1.5rem; display:flex; flex-direction:column; align-items:center; text-align:center; border:1px solid var(--border-soft, #DDCFAF); transition:0.2s;">
      <div style="width:50px; height:50px; border-radius:50%; background:rgba(16, 185, 129, 0.15); display:flex; align-items:center; justify-content:center; margin-bottom:1rem; color:#10b981;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
      </div>
      <h3 style="margin:0 0 0.5rem 0; color:var(--text-main);">Live Stock Panel</h3>
      <p style="font-size:0.85rem; color:var(--text-muted); margin:0;">View real-time live stock inventory and storage breakdowns</p>
    </a>

    <a href="{{ url($smPrefix . '/po') }}" class="card" style="text-decoration:none; padding:1.5rem; display:flex; flex-direction:column; align-items:center; text-align:center; border:1px solid var(--border-soft, #DDCFAF); transition:0.2s;">
      <div style="width:50px; height:50px; border-radius:50%; background:rgba(59, 130, 246, 0.15); display:flex; align-items:center; justify-content:center; margin-bottom:1rem; color:#3b82f6;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
      </div>
      <h3 style="margin:0 0 0.5rem 0; color:var(--text-main);">Purchase Requests (PO)</h3>
      <p style="font-size:0.85rem; color:var(--text-muted); margin:0;">Create and track purchase order requests</p>
    </a>

    <a href="{{ url($smPrefix . '/history') }}" class="card" style="text-decoration:none; padding:1.5rem; display:flex; flex-direction:column; align-items:center; text-align:center; border:1px solid var(--border-soft, #DDCFAF); transition:0.2s;">
      <div style="width:50px; height:50px; border-radius:50%; background:rgba(139, 92, 246, 0.15); display:flex; align-items:center; justify-content:center; margin-bottom:1rem; color:#8b5cf6;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
      </div>
      <h3 style="margin:0 0 0.5rem 0; color:var(--text-main);">Transaction History</h3>
      <p style="font-size:0.85rem; color:var(--text-muted); margin:0;">View detailed activity logs of stock inward and outward movements</p>
    </a>
  </div>
</div>
@endsection
