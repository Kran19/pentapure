@extends('layouts.app')

@section('content')
<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">✅ Finished Goods Stock</h2>
  <a class="btn btn-sm btn-secondary" style="width:auto; padding:0.5rem 1rem; text-decoration:none;" href="{{ url('finished/po') }}">Purchase Orders</a>
</div>

@include('partials.recent-pos', ['pageData' => $pageData])

<div class="card mb-2" style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; margin-top:1rem;">
  <div class="card-title" style="font-size:0.85rem; color:var(--primary-light); display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
    <div style="display:flex; align-items:center; gap:8px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--secondary);"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
      Available Semi-Finished Materials
    </div>
    <div style="font-size:0.7rem; color:var(--text-muted); font-weight:normal; display:flex; gap:5px;">
      <button type="button" onclick="document.getElementById('finished-scroll-container').scrollBy({left:-200, behavior:'smooth'})" style="border:1px solid #444; background:transparent; color:#ccc; border-radius:4px; cursor:pointer; padding:2px 8px;">&larr;</button>
      <button type="button" onclick="document.getElementById('finished-scroll-container').scrollBy({left:200, behavior:'smooth'})" style="border:1px solid #444; background:transparent; color:#ccc; border-radius:4px; cursor:pointer; padding:2px 8px;">&rarr;</button>
    </div>
  </div>
  <div id="finished-scroll-container" style="display:flex; overflow-x:auto; gap:12px; padding-bottom:10px; scrollbar-width:none; -ms-overflow-style:none;">
    <style>#finished-scroll-container::-webkit-scrollbar { display: none; }</style>
    @forelse($pageData['finishedStock'] as $s)
      @php $lowStock = $s['quantity'] < 500; @endphp
      <div class="animation-fadeIn" style="flex:0 0 200px; background:rgba(255,255,255,0.04); padding:12px; border-radius:10px; border:1px solid rgba(255,255,255,0.05); position:relative; overflow:hidden;">
        <div style="font-size:0.8rem; font-weight:700; color:var(--text-main); margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $s['name'] }}</div>
        <div style="font-size:0.65rem; color:var(--text-muted); margin-bottom:6px;">Grade: <span class="badge badge-info" style="font-size:0.55rem; padding:2px 5px;">{{ $s['grade'] }}</span></div>
        <div style="display:flex; justify-content:space-between; align-items:baseline;">
          <div style="font-weight:800; color:{{ $lowStock ? 'var(--warning)' : 'var(--secondary)' }}; font-size:1rem;">
            {{ number_format($s['quantity'], 2) }} <span style="font-size:0.7rem; font-weight:400; color:var(--text-muted);">{{ $s['unit'] }}</span>
          </div>
        </div>
        @if($lowStock)
          <div style="font-size:0.55rem; color:var(--warning); margin-top:5px; display:flex; align-items:center; gap:2px;"><span style="font-size:0.7rem;">⚠</span> Low</div>
        @endif
        <div style="position:absolute; top:0; right:0; width:3px; height:100%; background:{{ $lowStock ? 'var(--warning)' : 'var(--secondary)' }}; opacity:0.6;"></div>
      </div>
    @empty
      <div style="width:100%; padding:20px; text-align:center; background:rgba(0,0,0,0.1); border-radius:10px;">
        <div style="color:var(--text-muted); font-size:0.85rem;">No semi-finished material stock available.</div>
      </div>
    @endforelse
  </div>
</div>

<h3 style="margin-top:1.5rem; margin-bottom:0.8rem; color:var(--primary-light);">📦 Live Finished Goods Stock</h3>
<div class="responsive-grid">
  @forelse($pageData['finishedStock'] as $s)
    <div class="card" style="padding: 1rem; margin-bottom: 0;">
      <div class="flex-between">
        <div>
          <div style="font-weight:600; font-size:1.1rem;">{{ $s['name'] }}</div>
          <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Grade: <span class="badge badge-info">{{ $s['grade'] }}</span></div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:1.4rem; font-weight:bold; color:var(--primary-light);">{{ number_format($s['quantity'], 2) }} <span style="font-size:0.9rem; color:var(--text-muted);">{{ $s['unit'] }}</span></div>
        </div>
      </div>
    </div>
  @empty
    <div class="card" style="grid-column:1/-1;"><p class="text-center text-muted">No finished goods stock recorded yet.</p></div>
  @endforelse
</div>
@endsection
