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
        <thead><tr><th>Product</th><th>Grade</th><th>Boxes</th><th>Total Qty</th><th>Unit</th><th>Action</th></tr></thead>
        <tbody>
          @foreach($finishedItems as $s)
          <tr>
            <td style="font-weight:600;">{{ $s->name }}</td>
            <td><span class="badge badge-info">{{ $s->grade }}</span></td>
            <td style="color:var(--text-accent); font-weight:500;">
              @if($s->boxes > 0)
                {{ $s->boxes }} <span style="font-size:0.75rem; color:var(--text-muted);">({{ $s->weightPerBox }}kg each)</span>
              @else
                -
              @endif
            </td>
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
  const stageLabel = { RAW: '🌿 Raw', SEMI: '⚗️ Semi-Finished', FINISHED: '✅ Finished' }[stage] || stage;

  Swal.fire({
    title: 'Adjust Stock',
    html: `
      <div style="text-align:left; font-size:0.9rem; margin-bottom:1rem; color:#8b949e;">
        <strong style="color:#e6edf3;">${grade}</strong> &nbsp;·&nbsp; ${stageLabel}
      </div>

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#8b949e;margin-bottom:0.35rem;">
        Adjustment Type
      </label>
      <select id="swal-adj-type" style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#161b22; border:1px solid #30363d; color:#e6edf3;
        font-size:0.95rem; margin-bottom:1rem; outline:none;
      ">
        <option value="set">🎯 Set — Override to exact quantity</option>
        <option value="add">➕ Add — Increase current stock</option>
        <option value="subtract">➖ Subtract — Decrease current stock</option>
      </select>

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#8b949e;margin-bottom:0.35rem;">
        Quantity (kg)
      </label>
      <input id="swal-qty" type="number" min="0" step="0.01" placeholder="e.g. 150.00" style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#161b22; border:1px solid #30363d; color:#e6edf3;
        font-size:1rem; margin-bottom:1rem; outline:none; box-sizing:border-box;
      ">

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#8b949e;margin-bottom:0.35rem;">
        Reason / Note <span style="font-weight:400;">(optional)</span>
      </label>
      <textarea id="swal-reason" rows="2" placeholder="e.g. Physical count correction, spillage, etc." style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#161b22; border:1px solid #30363d; color:#e6edf3;
        font-size:0.9rem; resize:vertical; outline:none; box-sizing:border-box;
      "></textarea>
    `,
    background: '#0d1117',
    color: '#e6edf3',
    showCancelButton: true,
    confirmButtonText: 'Apply Adjustment',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#238636',
    cancelButtonColor: '#30363d',
    focusConfirm: false,
    width: '460px',
    customClass: {
      popup: 'swal-stock-popup',
      confirmButton: 'swal-confirm-btn',
      cancelButton: 'swal-cancel-btn',
    },
    preConfirm: () => {
      const qty    = parseFloat(document.getElementById('swal-qty').value);
      const type   = document.getElementById('swal-adj-type').value;
      const reason = document.getElementById('swal-reason').value.trim();

      if (isNaN(qty) || qty < 0) {
        Swal.showValidationMessage('⚠️ Please enter a valid quantity (≥ 0).');
        return false;
      }
      return { qty, type, reason };
    }
  }).then(result => {
    if (!result.isConfirmed) return;

    const { qty, type, reason } = result.value;

    Swal.fire({
      title: 'Applying…',
      text: 'Updating stock record.',
      allowOutsideClick: false,
      background: '#0d1117',
      color: '#e6edf3',
      didOpen: () => Swal.showLoading()
    });

    fetch('/admin/stock/adjust', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({
        product_id: productId,
        stage,
        grade,
        quantity: qty,
        adjust_type: type,
        reason
      })
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        Swal.fire({
          icon: 'success',
          title: 'Stock Updated',
          text: d.message || 'Adjustment applied successfully.',
          background: '#0d1117',
          color: '#e6edf3',
          confirmButtonColor: '#238636',
          timer: 2000,
          timerProgressBar: true,
          showConfirmButton: false
        }).then(() => location.reload());
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Failed',
          text: d.message || 'Something went wrong.',
          background: '#0d1117',
          color: '#e6edf3',
          confirmButtonColor: '#238636',
        });
      }
    })
    .catch(() => {
      Swal.fire({
        icon: 'error',
        title: 'Network Error',
        text: 'Could not reach the server. Please try again.',
        background: '#0d1117',
        color: '#e6edf3',
        confirmButtonColor: '#238636',
      });
    });
  });
}
</script>

<style>
.swal-stock-popup { border: 1px solid #30363d !important; border-radius: 14px !important; }
.swal-confirm-btn, .swal-cancel-btn { border-radius: 8px !important; font-weight: 600 !important; padding: 0.55rem 1.4rem !important; }
#swal-qty:focus, #swal-reason:focus, #swal-adj-type:focus {
  border-color: #238636 !important;
  box-shadow: 0 0 0 3px rgba(35,134,54,0.25) !important;
}
</style>
@endsection
