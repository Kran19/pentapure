@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">
  <h2 style="margin-bottom:1.5rem;">📋 Purchase Orders</h2>

  @if(empty($pageData['purchaseOrders']))
    <div class="card" style="padding:2rem; text-align:center;">
      <p style="color:var(--text-muted); margin:0;">No purchase orders yet. Users will submit them from their profiles.</p>
    </div>
  @else
  <div class="card" style="padding:1.2rem;">
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Requested By</th>
            <th>Material</th>
            <th>Qty (kg)</th>
            <th>Note</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($pageData['purchaseOrders'] as $po)
          <tr id="po-row-{{ $po->id }}">
            <td style="font-size:0.8rem;">{{ $po->created_at->format('d M Y') }}</td>
            <td>
              <div style="font-weight:600;">{{ $po->user?->name }}</div>
              <div style="font-size:0.75rem; color:var(--text-muted);">{{ $po->user?->role }}</div>
            </td>
            <td style="font-weight:600;">{{ $po->product ? $po->product->formatName() : 'Unknown' }}</td>
            <td style="color:var(--primary-light); font-weight:bold;">{{ number_format($po->quantity, 1) }}</td>
            <td style="font-size:0.85rem; color:var(--text-muted);">{{ $po->note ?? '—' }}</td>
            <td>
              <span class="badge {{ $po->status === 'DONE' ? 'badge-done' : 'badge-pending' }}">
                {{ $po->status === 'DONE' ? 'READ' : $po->status }}
              </span>
            </td>
            <td>
              <div class="action-btns">
                @if($po->status === 'PENDING')
                <button class="btn btn-sm" style="width:auto; padding:0.3rem 0.8rem; background:var(--secondary);"
                  onclick="adminApprovePO({{ $po->id }}, this)">
                  ✅ Mark as Read
                </button>
                @else
                  <span style="font-size:0.8rem; color:var(--secondary); margin-right:8px;">✓ Read</span>
                @endif
                <button class="btn-icon delete" onclick="adminDeletePO({{ $po->id }})" title="Delete">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <!-- Pagination Links -->
    <div style="margin-top:1.5rem; display:flex; justify-content:center;">
      {{ $pageData['purchaseOrders']->links() }}
    </div>
  </div>
  @endif
</div>

<script>
function adminDeletePO(id) {
  Swal.fire({
    title: 'Are you sure?',
    text: "Delete this purchase request?",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, delete!'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(`/admin/po/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken }
      }).then(r => r.json()).then(d => {
        if (d.success) {
          Swal.fire('Deleted!', d.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          Swal.fire('Error!', d.message || 'Error', 'error');
        }
      });
    }
  });
}

function adminApprovePO(id, btn) {
  Swal.fire({
    title: 'Mark as Read?',
    text: "This will acknowledge the request without modifying stock.",
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, mark as read'
  }).then((result) => {
    if (result.isConfirmed) {
      btn.disabled = true;
      btn.textContent = 'Processing...';
      fetch('/admin/po/approve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ po_id: id })
      }).then(r => r.json()).then(d => {
        if (d.success) {
          Swal.fire('Read!', d.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          Swal.fire('Error!', d.message || 'Error', 'error');
          btn.disabled = false;
          btn.textContent = '✅ Mark as Read';
        }
      });
    }
  });
}

</script>
@endsection
