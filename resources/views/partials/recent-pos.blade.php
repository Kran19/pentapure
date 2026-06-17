@if(isset($pageData['purchaseOrders']) && count($pageData['purchaseOrders']) > 0)
<div class="card mt-1" style="background:rgba(255,255,255,0.03);">
  <div class="card-title" style="font-size:0.9rem;">Recent Purchase Requests</div>
  @foreach(collect($pageData['purchaseOrders'])->sortByDesc('date')->take(3) as $p)
    <div class="flex-between" style="padding:0.5rem 0; border-bottom:1px solid rgba(255,255,255,0.05);">
      <div>
        <div style="font-size:0.85rem; font-weight:600;">{{ $p['materialName'] }}</div>
        <div style="font-size:0.7rem; color:var(--text-muted);">{{ \Carbon\Carbon::parse($p['date'])->format('d M Y') }} · {{ $p['quantity'] }} kg</div>
      </div>
      <span class="badge {{ $p['status'] === 'DONE' ? 'badge-done' : 'badge-pending' }}" style="font-size:0.65rem; padding:0.2rem 0.5rem;">{{ $p['status'] }}</span>
    </div>
  @endforeach
</div>
@endif
