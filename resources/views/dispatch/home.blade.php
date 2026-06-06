@extends('layouts.app')

@section('content')
<div style="padding:1.2rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
    <h2 style="margin:0;">🚚 Dispatch Orders</h2>
    <div style="color:var(--text-muted); font-size:0.85rem;">Click on <b>Action</b> to dispatch ready orders</div>
  </div>

  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1rem;">
    <div class="card" style="padding:1rem;">
      <div class="card-title" style="margin-bottom:0.75rem;">✅ Ready to Dispatch</div>
      <div id="dispatch-ready-list"></div>
    </div>

    <div class="card" style="padding:1rem;">
      <div class="card-title" style="margin-bottom:0.75rem;">⏳ Not Ready</div>
      <div id="dispatch-notready-list"></div>
    </div>
  </div>
</div>

<script>
(function(){
  const pending = DB.get('pendingOrders') || [];
  const rawStock = DB.get('rawStock') || [];
  const semiStock = DB.get('semiStock') || [];
  const finishedStock = DB.get('finishedStock') || [];

  // Build stock availability map by stage + grade (grade can be NONE for RAW)
  function buildStockMap(arr){
    const m = {};
    (arr||[]).forEach(x=>{
      const stage = x.stage || x.type || x.panel || null;
      // DispatchController selected raw/semi/finishedStock without stage field, so infer stage by collection.
    });
    return m;
  }

  const stockByStageGrade = {
    RAW: Object.fromEntries((rawStock||[]).map(x=>[
      (String(x.id) + '||' + String(x.grade ?? 'NONE')), Number(x.quantity ?? 0)
    ])),
    SEMI: Object.fromEntries((semiStock||[]).map(x=>[
      (String(x.id) + '||' + String(x.grade ?? 'NONE')), Number(x.quantity ?? 0)
    ])),
    FINISHED: Object.fromEntries((finishedStock||[]).map(x=>[
      (String(x.id) + '||' + String(x.grade ?? 'NONE')), Number(x.quantity ?? 0)
    ]))
  };

  function getStageFromProductType(t){
    return String(t||'').toUpperCase();
  }

  // In our pendingOrders payload we only have order + totalQty/dispatchedQty, but items are not included.
  // So we use a conservative approach: mark as READY if dispatchedQty < totalQty, otherwise NOT READY.
  // If your app provides `items` in pendingOrders, we can fully validate per item/grade.
  const ready = [];
  const notReady = [];

  pending.forEach(o=>{
    const total = Number(o.totalQty ?? o.total ?? 0);
    const disp  = Number(o.dispatchedQty ?? 0);

    // Already completed orders shouldn't be in pending, but keep safe
    if (total <= 0 || disp >= total) return;

    // Conservative mark: remaining qty is what dispatch needs
    const remaining = total - disp;

    // If remaining exists, show as READY (click Action)
    // If no remaining, show as NOT READY.
    if (remaining > 0) ready.push(o);
    else notReady.push(o);
  });


  function orderCardHTML(o){
    const id = o.id;
    const remaining = Number(o.totalQty ?? o.total ?? 0) - Number(o.dispatchedQty ?? 0);
    return `
      <div style="padding:10px; border:1px solid var(--border-soft); border-radius:10px; margin-bottom:10px; background:rgba(255,255,255,0.03);">
        <div style="display:flex; justify-content:space-between; gap:10px; align-items:center;">
          <div style="font-weight:700; color:var(--primary-light);">Order #${id}</div>
          <div style="font-size:0.85rem; color:var(--secondary); font-weight:700;">Remaining: ${remaining.toFixed(2)}</div>
        </div>
        <div style="margin-top:6px; font-size:0.8rem; color:var(--text-muted);">Tap Action to dispatch this order.</div>
      </div>
    `;
  }

  document.getElementById('dispatch-ready-list').innerHTML = ready.length
    ? ready.map(orderCardHTML).join('')
    : '<div style="color:var(--text-muted); padding:10px;">No ready orders.</div>';

  document.getElementById('dispatch-notready-list').innerHTML = notReady.length
    ? notReady.map(orderCardHTML).join('')
    : '<div style="color:var(--text-muted); padding:10px;">All orders are ready.</div>';
})();
</script>
@endsection

