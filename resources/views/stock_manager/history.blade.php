@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
<div style="padding: 1rem 0;">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:1.5rem; gap:1rem;">
    <h2 style="margin:0; color:var(--text-main);">📜 Stock Activity History</h2>
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <input type="text" id="history-search" placeholder="🔍 Search history..." oninput="filterHistoryTable(this.value)" style="padding:0.6rem 1rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main);">
      @if($pageData['history']->total() > 0)
        <button type="button" onclick="clearAllStockHistory()" class="btn btn-sm" style="background:#dc2626; color:#ffffff; padding:0.6rem 1rem; border-radius:8px; font-weight:700; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:6px; white-space:nowrap; box-shadow:0 2px 4px rgba(220,38,38,0.2);">
          🗑️ Clear All History
        </button>
      @endif
    </div>
  </div>

  <div class="card" style="padding:1.2rem;">
    <div class="table-container" style="overflow-x: auto; max-width: 100%; -webkit-overflow-scrolling: touch; padding-bottom: 6px;">
      <table id="history-table" style="width: 100%; min-width: 980px; border-collapse: collapse;">
        <thead>
          <tr>
            <th style="white-space: nowrap;">#</th>
            <th style="white-space: nowrap;">Date & Time</th>
            <th style="white-space: nowrap;">Product Name</th>
            <th style="white-space: nowrap;">Stage</th>
            <th style="white-space: nowrap;">Grade</th>
            <th style="white-space: nowrap;">Type</th>
            <th style="white-space: nowrap;">Quantity</th>
            <th style="white-space: nowrap;">Location</th>
            <th style="white-space: nowrap;">Notes</th>
            <th style="white-space: nowrap;">Recorded By</th>
            <th style="white-space: nowrap; text-align:center;">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pageData['history'] as $s)
          <tr id="history-row-{{ $s->id }}" class="history-row" data-name="{{ strtolower(($s->product?->name ?? '') . ' ' . ($s->notes ?? '') . ' ' . ($s->location?->name ?? '') . ' ' . ($s->stage ?? '') . ' ' . ($s->grade ?? '')) }}">
            <td style="white-space: nowrap;">{{ $loop->iteration }}</td>
            <td style="font-size:0.85rem; color:var(--text-muted); white-space: nowrap;">
              {{ $s->created_at ? $s->created_at->format('d-m-Y, h:i A') : '-' }}
            </td>
            <td style="font-weight:600; color:var(--text-main); white-space: nowrap;">
              {{ $s->product?->name ?? 'Unknown' }}
              <span style="font-weight:bold; font-size:0.8rem; color:var(--text-muted);">
                ({{ $s->stage === 'FINISHED' ? 'FG' : ($s->stage === 'PACKAGING' ? 'PM' : $s->stage) }})
              </span>
            </td>
            <td style="white-space: nowrap;">
              <span class="badge" style="background:{{ $s->stage==='RAW'?'#f59e0b':($s->stage==='SEMI'?'#3b82f6':'#10b981') }}; color:#fff; padding:3px 8px; border-radius:6px; font-size:0.75rem;">
                {{ $s->stage === 'FINISHED' ? 'FG' : ($s->stage === 'PACKAGING' ? 'PM' : $s->stage) }}
              </span>
            </td>
            <td style="white-space: nowrap;">{{ $s->grade }}</td>
            <td style="white-space: nowrap;">
              @if($s->transaction_type === 'IN')
                <span class="badge" style="background:#10b981; color:#ffffff !important; padding:3px 8px; border-radius:6px; font-size:0.75rem; font-weight:bold;">INWARD</span>
              @else
                <span class="badge" style="background:#ef4444; color:#ffffff !important; padding:3px 8px; border-radius:6px; font-size:0.75rem; font-weight:bold;">OUTWARD</span>
              @endif
            </td>
            <td style="font-weight:bold; color:{{ $s->transaction_type==='IN'?'#10b981':'#ef4444' }}; white-space: nowrap;">
              {{ $s->transaction_type === 'IN' ? '+' : '-' }}{{ number_format($s->quantity, 2) }} {{ $s->product?->unit ?? 'kg' }}
            </td>
            <td style="font-size:0.85rem; font-weight:600; color:var(--text-main); white-space: nowrap;">📍 {{ $s->location?->name ?? 'Main Warehouse' }}</td>
            <td style="font-size:0.85rem; color:var(--text-muted); min-width:220px; max-width:380px; white-space:normal; word-break:break-word; overflow-wrap:break-word; vertical-align:middle;">
              <div style="display:flex; align-items:center; justify-content:space-between; gap:6px;">
                <span id="note-text-{{ $s->id }}">{{ $s->notes ?? '-' }}</span>
                <button type="button" onclick="editStockNote({{ $s->id }}, '{{ addslashes($s->notes ?? '') }}')" title="Edit Note" style="background:none; border:none; color:var(--primary, #3b82f6); cursor:pointer; padding:2px 4px; border-radius:4px; font-size:0.85rem; opacity:0.75;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.75">
                  ✏️
                </button>
              </div>
            </td>
            <td style="font-size:0.85rem; white-space: nowrap;">{{ $s->user?->name ?? 'System' }}</td>
            <td style="white-space: nowrap; text-align:center;">
              <button type="button" onclick="deleteStockHistory({{ $s->id }})" title="Delete this entry" style="background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.25); color:#ef4444; cursor:pointer; padding:3px 8px; border-radius:6px; font-size:0.82rem; font-weight:600; display:inline-flex; align-items:center; gap:4px; transition:all 0.15s ease;" onmouseover="this.style.background='rgba(239,68,68,0.18)'" onmouseout="this.style.background='rgba(239,68,68,0.08)'">
                🗑️ Delete
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="11" style="text-align:center; padding:2rem; color:var(--text-muted);">No stock history logs recorded yet.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div style="margin-top:1.5rem; display:flex; justify-content:center;">
      {{ $pageData['history']->links() }}
    </div>
  </div>
</div>

<script>
function filterHistoryTable(q) {
  const query = q.trim().toLowerCase();
  document.querySelectorAll('.history-row').forEach(row => {
    const text = row.getAttribute('data-name') || '';
    if (!query || text.includes(query)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

function editStockNote(id, currentNote) {
  const segments = window.location.pathname.split('/').filter(s => s && s !== 'penta-pure' && s !== 'public');
  const currentUrlPrefix = segments.length > 0 ? segments[0] : 'stock_manager';
  
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Edit Stock Note',
      input: 'textarea',
      inputValue: currentNote,
      inputPlaceholder: 'Enter note...',
      showCancelButton: true,
      confirmButtonText: 'Save Note',
      confirmButtonColor: '#3b82f6',
      cancelButtonText: 'Cancel',
      inputValidator: (value) => {
        if (value && value.length > 1000) {
          return 'Note cannot exceed 1000 characters';
        }
      }
    }).then((result) => {
      if (result.isConfirmed) {
        saveStockNote(id, result.value, currentUrlPrefix);
      }
    });
  } else {
    const newNote = prompt('Edit Stock Note:', currentNote);
    if (newNote !== null) {
      saveStockNote(id, newNote, currentUrlPrefix);
    }
  }
}

function saveStockNote(id, newNote, prefix) {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const baseUrl = (typeof app !== 'undefined' && typeof app.getBaseUrl === 'function') ? app.getBaseUrl() : '';
  fetch(`${baseUrl}/${prefix}/stock/note/${id}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json',
    },
    body: JSON.stringify({ notes: newNote })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      const el = document.getElementById(`note-text-${id}`);
      if (el) {
        el.innerText = data.notes || '-';
      }
      if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'success', title: 'Updated!', text: 'Note updated successfully.', timer: 1500, showConfirmButton: false });
      }
    } else {
      alert(data.message || 'Failed to update note.');
    }
  })
  .catch(err => {
    console.error(err);
    alert('An error occurred while updating the note.');
  });
}

function deleteStockHistory(id) {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const segments = window.location.pathname.split('/').filter(s => s && s !== 'penta-pure' && s !== 'public');
  const prefix = segments.length > 0 ? segments[0] : 'stock_manager';
  const baseUrl = (typeof app !== 'undefined' && typeof app.getBaseUrl === 'function') ? app.getBaseUrl() : '';

  const doDelete = () => {
    fetch(`${baseUrl}/${prefix}/history/${id}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
      }
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const row = document.getElementById(`history-row-${id}`);
        if (row) {
          row.style.transition = 'opacity 0.3s ease';
          row.style.opacity = '0';
          setTimeout(() => row.remove(), 300);
        }
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'success', title: 'Deleted!', text: data.message || 'Entry deleted successfully.', timer: 1500, showConfirmButton: false });
        }
      } else {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Error', data.message || 'Failed to delete entry.', 'error');
        } else {
          alert(data.message || 'Failed to delete entry.');
        }
      }
    })
    .catch(err => {
      console.error(err);
      if (typeof Swal !== 'undefined') {
        Swal.fire('Error', 'An error occurred while deleting the entry.', 'error');
      } else {
        alert('An error occurred while deleting the entry.');
      }
    });
  };

  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Delete this history record?',
      text: 'Are you sure you want to delete this stock history log entry?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Yes, Delete',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        doDelete();
      }
    });
  } else {
    if (confirm('Are you sure you want to delete this stock history entry?')) {
      doDelete();
    }
  }
}

function clearAllStockHistory() {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const segments = window.location.pathname.split('/').filter(s => s && s !== 'penta-pure' && s !== 'public');
  const prefix = segments.length > 0 ? segments[0] : 'stock_manager';
  const baseUrl = (typeof app !== 'undefined' && typeof app.getBaseUrl === 'function') ? app.getBaseUrl() : '';

  const doClear = () => {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Clearing history...',
        text: 'Please wait while all stock history logs are deleted.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });
    }

    fetch(`${baseUrl}/${prefix}/history/clear`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
      }
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'success', title: 'Cleared!', text: data.message || 'All stock history has been cleared.', timer: 1800, showConfirmButton: false }).then(() => {
            window.location.reload();
          });
        } else {
          alert(data.message || 'All stock history has been cleared.');
          window.location.reload();
        }
      } else {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Error', data.message || 'Failed to clear stock history.', 'error');
        } else {
          alert(data.message || 'Failed to clear stock history.');
        }
      }
    })
    .catch(err => {
      console.error(err);
      if (typeof Swal !== 'undefined') {
        Swal.fire('Error', 'An error occurred while clearing history.', 'error');
      } else {
        alert('An error occurred while clearing history.');
      }
    });
  };

  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Clear All Stock History?',
      text: 'Are you sure you want to delete ALL stock history records? This cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Yes, Delete All',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        doClear();
      }
    });
  } else {
    if (confirm('Are you sure you want to delete ALL stock history records? This cannot be undone.')) {
      doClear();
    }
  }
}
</script>
@endsection
