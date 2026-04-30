@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">
  <h2 style="margin-bottom:1.5rem;">📦 Live Stock Overview</h2>

  @php
    $rawItems      = collect($pageData['allStock'])->where('stage', 'RAW');
    $semiItems     = collect($pageData['allStock'])->where('stage', 'SEMI');
    $finishedItems = collect($pageData['allStock'])->where('stage', 'FINISHED');
  @endphp

  <!-- RAW Stock -->
  <div class="card" style="padding:1.2rem; margin-bottom:1rem;">
    <div class="card-title" style="color:var(--primary-light);">🌿 Raw Material Stock ({{ $rawItems->count() }} items)</div>
    @if($rawItems->isEmpty())
      <p class="text-muted text-center">No raw stock recorded yet.</p>
    @else
    <div class="table-container">
      <table>
        <thead><tr><th>Product</th><th>Grade</th><th>Qty</th><th>Unit</th><th>Action</th></tr></thead>
        <tbody>
          @foreach($rawItems as $s)
          <tr>
            <td style="font-weight:600;">{{ $s->name }}</td>
            <td><span class="badge badge-info">{{ $s->grade }}</span></td>
            <td style="font-weight:bold; color:var(--secondary);">{{ number_format($s->quantity, 2) }}</td>
            <td style="color:var(--text-muted);">{{ $s->unit }}</td>
            <td>
              <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}')" title="Adjust">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>

  <!-- SEMI Stock -->
  <div class="card" style="padding:1.2rem; margin-bottom:1rem;">
    <div class="card-title" style="color:var(--warning);">⚗️ Semi-Finished Stock ({{ $semiItems->count() }} items)</div>
    @if($semiItems->isEmpty())
      <p class="text-muted text-center">No semi stock recorded yet.</p>
    @else
    <div class="table-container">
      <table>
        <thead><tr><th>Product</th><th>Grade</th><th>Qty</th><th>Unit</th><th>Action</th></tr></thead>
        <tbody>
          @foreach($semiItems as $s)
          <tr>
            <td style="font-weight:600;">{{ $s->name }}</td>
            <td><span class="badge badge-info">{{ $s->grade }}</span></td>
            <td style="font-weight:bold; color:var(--warning);">{{ number_format($s->quantity, 2) }}</td>
            <td style="color:var(--text-muted);">{{ $s->unit }}</td>
            <td>
              <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}')" title="Adjust">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>

  <!-- Finished Stock -->
  <div class="card" style="padding:1.2rem;">
    <div class="card-title" style="color:var(--secondary);">✅ Finished Goods Stock ({{ $finishedItems->count() }} items)</div>
    @if($finishedItems->isEmpty())
      <p class="text-muted text-center">No finished stock recorded yet.</p>
    @else
    <div class="table-container">
      <table>
        <thead><tr><th>Product</th><th>Grade</th><th>Qty</th><th>Unit</th><th>Action</th></tr></thead>
        <tbody>
          @foreach($finishedItems as $s)
          <tr>
            <td style="font-weight:600;">{{ $s->name }}</td>
            <td><span class="badge badge-info">{{ $s->grade }}</span></td>
            <td style="font-weight:bold; color:var(--secondary);">{{ number_format($s->quantity, 2) }}</td>
            <td style="color:var(--text-muted);">{{ $s->unit }}</td>
            <td>
              <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}')" title="Adjust">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>
</div>

<script>
function adminAdjustStock(productId, stage, grade) {
  const qty = prompt('Enter new absolute quantity (kg):');
  if(qty === null || isNaN(qty)) return;
  
  fetch('/admin/stock/adjust', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({ product_id: productId, stage, grade, quantity: qty })
  }).then(r => r.json()).then(d => {
    if (d.success) { 
      app.toast(d.message); 
      setTimeout(() => location.reload(), 800); 
    } else {
      app.toast(d.message || 'Error', 'error');
    }
  });
}
</script>
@endsection
