@extends('layouts.admin')

@section('content')
<div style="padding:0.25rem 0 1rem 0;">
  <h2 style="margin-bottom:1.5rem;">📋 PO Received by System</h2>

  @if(empty($pageData['purchaseOrders']))
    <div class="card" style="padding:2rem; text-align:center;">
      <p style="color:var(--text-muted); margin:0;">No purchase requests yet. Users will submit them from their profiles.</p>
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
            <th>Available Qty (kg)</th>
            <th>Order Quantity (kg)</th>
            <th style="min-width:280px; width:340px; padding-right:1.5rem;">Note</th>
            <th style="white-space:nowrap; padding-left:1.5rem; padding-right:2rem;">Status</th>
            <th style="padding-left:1.5rem; min-width:180px;">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($pageData['purchaseOrders'] as $po)
          <tr id="po-row-{{ $po->id }}">
            <td style="font-size:0.8rem; color:var(--text-muted); white-space:nowrap;">
              <div style="font-weight:600; color:var(--text-main);">{{ $po->created_at->format('d-m-Y') }}</div>
              @if($po->status === 'RECEIVED' && ($po->date || $po->updated_at))
                <div style="font-size:0.72rem; color:#059669; font-weight:600;">Rec: {{ \Carbon\Carbon::parse($po->date ?? $po->updated_at)->format('d-m-Y') }}</div>
              @endif
            </td>
            <td>
              <div style="font-weight:600;">{{ $po->user?->name }}</div>
              <div style="font-size:0.75rem; color:var(--text-muted);">{{ $po->user?->role }}</div>
            </td>
            <td style="font-weight:600;">{{ $po->product ? $po->product->formatName() : 'Unknown' }}</td>
            <td style="color:#10b981; font-weight:bold;">{{ number_format($po->product ? $po->product->totalAvailableStock() : 0, 1) }}</td>
            <td style="color:var(--primary-light); font-weight:bold;">{{ number_format($po->quantity, 1) }}</td>
            <td style="font-size:0.85rem; color:var(--text-muted); min-width:280px; width:340px; padding-right:1.5rem;">
              <div style="word-break:break-word; white-space:normal; line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                {{ $po->note ?? '—' }}
              </div>    
            </td>
            <td style="white-space:nowrap; padding-left:1.5rem; padding-right:2.5rem;">
              @if($po->status === 'PENDING')
                <span class="badge" style="background:#ef4444; color:#ffffff !important;">PENDING</span>
              @elseif($po->status === 'READ')
                <span class="badge" style="background:#eab308; color:#ffffff !important;">READ</span>
              @elseif($po->status === 'ORDERED')
                <span class="badge" style="background:#3b82f6; color:#ffffff !important;">ORDERED</span>
              @elseif($po->status === 'RECEIVED')
                <span class="badge badge-done">RECEIVED</span>
              @elseif($po->status === 'REJECTED')
                <span class="badge" style="background:#dc2626; color:#ffffff !important;">REJECTED</span>
              @else
                <span class="badge badge-pending">{{ $po->status }}</span>
              @endif
            </td>
            <td style="padding-left:1.5rem; min-width:180px;">
              <div class="action-btns" style="display:flex; align-items:center; gap:0.4rem; flex-wrap:nowrap;">
                @if($po->status === 'PENDING')
                  <button class="btn btn-sm" style="width:auto; padding:0.3rem 0.6rem; background:#eab308; color:#ffffff !important; border:none; border-radius:4px; font-size:0.75rem; cursor:pointer; white-space:nowrap;"
                    onclick="adminApprovePO({{ $po->id }}, this)">
                    ✅ Mark as Read
                  </button>
                @elseif($po->status === 'READ')
                  <button class="btn btn-sm" style="width:auto; padding:0.3rem 0.6rem; background:#3b82f6; color:#ffffff !important; border:none; border-radius:4px; font-size:0.75rem; cursor:pointer; white-space:nowrap;"
                    onclick="adminOrderPO({{ $po->id }}, this)">
                    🛒 Mark as Order
                  </button>
                @endif
                <button class="btn btn-sm" style="width:auto; padding:0.3rem 0.6rem; background:#ef4444; color:#ffffff !important; border:none; border-radius:4px; font-size:0.75rem; cursor:pointer; display:inline-flex; align-items:center; gap:0.25rem; white-space:nowrap;"
                  onclick="adminDeletePO({{ $po->id }})" title="Delete">
                  🗑️ Delete
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
const csrfToken = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
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
      fetch(window.baseUrl + '/' + window.userSlug + '/po/' + id, {
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
      fetch(window.baseUrl + '/' + window.userSlug + '/po/approve', {
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

function adminOrderPO(id, btn) {
  Swal.fire({
    title: 'Mark as Order?',
    text: "This will update the order status to ORDERED for the Stock Manager.",
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, mark as ordered'
  }).then((result) => {
    if (result.isConfirmed) {
      btn.disabled = true;
      btn.textContent = 'Processing...';
      fetch(window.baseUrl + '/' + window.userSlug + '/po/order', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ po_id: id })
      }).then(r => r.json()).then(d => {
        if (d.success) {
          Swal.fire('Ordered!', d.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          Swal.fire('Error!', d.message || 'Error', 'error');
          btn.disabled = false;
          btn.textContent = '🛒 Mark as Order';
        }
      });
    }
  });
}

function adminRejectPO(id, btn) {
  Swal.fire({
    title: 'Reject Request?',
    text: "This will mark the purchase request as REJECTED.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, reject request'
  }).then((result) => {
    if (result.isConfirmed) {
      btn.disabled = true;
      btn.textContent = 'Processing...';
      fetch(window.baseUrl + '/' + window.userSlug + '/po/reject', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ po_id: id })
      }).then(r => r.json()).then(d => {
        if (d.success) {
          Swal.fire('Rejected!', d.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          Swal.fire('Error!', d.message || 'Error', 'error');
          btn.disabled = false;
          btn.textContent = '❌ Reject';
        }
      });
    }
  });
}

function adminReceivePO(id, btn) {
  const todayStr = new Date().toISOString().split('T')[0];
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Mark as Received?',
      html: `
        <div style="text-align: left; padding: 0.2rem 0.4rem;">
          <p style="color: #4b5563; font-size: 0.88rem; margin: 0 0 1rem 0;">Confirm that this order has been received.</p>
          
          <div style="margin-bottom: 1rem;">
            <label for="swal-receive-date" style="display: block; font-weight: 600; font-size: 0.85rem; color: #374151; margin-bottom: 0.35rem;">
              Received Date *
            </label>
            <input type="date" id="swal-receive-date" class="swal2-input" value="${todayStr}" max="${todayStr}" style="width: 100%; height: 2.6rem; margin: 0; box-sizing: border-box; font-size: 0.9rem; border: 1px solid #d1d5db; border-radius: 6px; padding: 0 0.75rem;">
          </div>
          
          <div style="margin-bottom: 0.5rem;">
            <label for="swal-receive-note" style="display: block; font-weight: 600; font-size: 0.85rem; color: #374151; margin-bottom: 0.35rem;">
              Note <span style="font-weight: 400; color: #9ca3af;">(Optional)</span>
            </label>
            <textarea id="swal-receive-note" class="swal2-textarea" placeholder="Add optional note..." style="width: 100%; height: 65px; margin: 0; box-sizing: border-box; font-size: 0.88rem; border: 1px solid #d1d5db; border-radius: 6px; padding: 0.5rem; resize: vertical;"></textarea>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Yes, Received!',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#10b981',
      cancelButtonColor: '#6b7280',
      focusConfirm: false,
      didOpen: () => {
        const dateInput = document.getElementById('swal-receive-date');
        if (dateInput) dateInput.focus();
      },
      preConfirm: () => {
        const dateVal = document.getElementById('swal-receive-date').value;
        if (!dateVal) {
          Swal.showValidationMessage('Please select a received date');
          return false;
        }
        const noteVal = document.getElementById('swal-receive-note').value;
        return { date: dateVal, note: noteVal };
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        const { date, note } = result.value;
        if (btn) {
          btn.disabled = true;
          btn.textContent = 'Processing...';
        }
        fetch(window.baseUrl + '/' + window.userSlug + '/po/receive', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
          body: JSON.stringify({ po_id: id, date: date, note: note })
        }).then(r => r.json()).then(d => {
          if (d.success) {
            Swal.fire({
              icon: 'success',
              title: 'Received!',
              text: d.message || 'PO marked as received successfully!',
              timer: 1500,
              showConfirmButton: false
            }).then(() => location.reload());
          } else {
            Swal.fire('Error!', d.message || 'Error', 'error');
            if (btn) {
              btn.disabled = false;
              btn.textContent = '📦 Mark as Received';
            }
          }
        }).catch(err => {
          Swal.fire('Error!', 'An error occurred while updating the order status.', 'error');
          if (btn) {
            btn.disabled = false;
            btn.textContent = '📦 Mark as Received';
          }
        });
      }
    });
  } else {
    const dateVal = prompt('Enter Received Date (YYYY-MM-DD):', todayStr);
    if (dateVal !== null) {
      const note = prompt('Add optional note:') || '';
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Processing...';
      }
      fetch(window.baseUrl + '/' + window.userSlug + '/po/receive', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ po_id: id, date: dateVal, note: note })
      }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
        else {
          alert(d.message || 'Error updating status');
          if (btn) {
            btn.disabled = false;
            btn.textContent = '📦 Mark as Received';
          }
        }
      });
    }
  }
}

</script>
@endsection
