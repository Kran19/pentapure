@extends('layouts.admin')

@section('content')
<div style="padding: 1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <h2 style="margin:0;">💰 Cashier Overview</h2>
        <div style="display:flex; gap:0.5rem;">
            <button class="btn btn-secondary" onclick="location.reload()" style="width:auto; padding:0.6rem 1rem;">🔄 Refresh</button>
            <a href="{{ route(request()->segment(1) . '.cashier.logs') }}" class="btn" style="width:auto; padding:0.6rem 1rem; background-color:var(--primary-light); text-decoration:none; display:flex; align-items:center; gap:5px;">
                📝 View Edit Logs
            </a>
            <button type="button" class="btn" onclick="window.downloadPdfAsync('{{ route(request()->segment(1) . '.cashier_overview.pdf') }}', {}, this)" style="width:auto; padding:0.6rem 1rem; display:flex; align-items:center; gap:5px; cursor:pointer;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Export PDF
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">
        <div class="card" style="padding:1.2rem; border-left: 4px solid var(--secondary);">
            <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Total Income</div>
            <div style="font-size:1.8rem; font-weight:bold; color:var(--secondary); margin-top:5px;">₹{{ number_format($pageData['summary']['totalIn'], 2) }}</div>
        </div>
        <div class="card" style="padding:1.2rem; border-left: 4px solid var(--danger);">
            <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Total Expenses</div>
            <div style="font-size:1.8rem; font-weight:bold; color:var(--danger); margin-top:5px;">₹{{ number_format($pageData['summary']['totalOut'], 2) }}</div>
        </div>
        <div class="card" style="padding:1.2rem; border-left: 4px solid var(--primary-light);">
            <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Net Balance</div>
            <div style="font-size:1.8rem; font-weight:bold; color:var(--primary-light); margin-top:5px;">₹{{ number_format($pageData['summary']['balance'], 2) }}</div>
        </div>
    </div>

    <!-- Cashier Breakdown -->
    <div style="margin-top:0.5rem; margin-bottom:2rem;">
        <h3 class="mb-1">Cashier Breakdown</h3>
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:1rem;">
            @foreach($pageData['summary']['byCashier'] as $vals)
            <div class="card" style="padding:1rem;">
                <div style="font-weight:600; color:var(--primary-light); margin-bottom:8px; border-bottom:1px solid var(--glass-border); padding-bottom:5px;">
                    {{ strtoupper($vals['name']) }}
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                    <span style="color:var(--text-muted);">In:</span>
                    <span style="color:var(--secondary); font-weight:600;">₹{{ number_format($vals['in'], 2) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                    <span style="color:var(--text-muted);">Out:</span>
                    <span style="color:var(--danger); font-weight:600;">₹{{ number_format($vals['out'], 2) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.9rem; margin-top:5px; padding-top:5px; border-top:1px dashed var(--glass-border);">
                    <span style="color:var(--text-muted);">Balance:</span>
                    <span style="font-weight:bold; color:{{ $vals['balance'] >= 0 ? 'var(--secondary)' : 'var(--danger)' }};">₹{{ number_format($vals['balance'], 2) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Details Table -->
    <div class="card" style="padding:1.2rem; margin-bottom:2rem;">
        <div class="card-title">Transaction Ledger</div>
        <div class="table-container">
            <table id="admin-cashier-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Cashier</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Notes</th>
                        <th>Reference</th>
                        <th>Bill</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pageData['transactions'] as $tx)
                    <tr>
                        <td style="font-size:0.85rem;">{{ $tx->created_at->format('d M Y, h:i A') }}</td>
                        <td><strong>{{ $tx->user->name }}</strong></td>
                        <td>
                            <span style="display:inline-block; min-width:55px; text-align:center; padding:4px 8px; border-radius:4px; font-weight:bold; background: #d3d3d3de; color:{{ $tx->type === 'IN' ? '#2ecc71' : 'red' }};">
                                {{ $tx->type }}
                            </span>
                        </td>
                        <td><span class="badge badge-info">{{ strtoupper($tx->category) }}</span></td>
                        <td style="font-weight:700; color: {{ $tx->type === 'IN' ? '#2ecc71' : 'red' }}">
                            {{ $tx->type === 'IN' ? '+' : '-' }}₹{{ number_format($tx->amount, 2) }}
                        </td>
                        <td style="font-size:0.9rem; max-width:200px;">{{ $tx->note ?? '—' }}</td>
                        <td style="font-size:0.85rem; color:var(--text-muted);">{{ $tx->reference ?? '—' }}</td>
                        <td>
                            @if($tx->bills && $tx->bills->count() > 0)
                                <div style="display:flex; flex-direction:column; gap:8px;">
                                @foreach($tx->bills as $bill)
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        @if(in_array($bill->file_type, ['image', 'jpg', 'jpeg', 'png']))
                                            <a href="javascript:void(0)" onclick="app.viewImage('{{ route('cashier.bill.view', $bill->id) }}')" style="color:var(--primary-light); text-decoration:underline; font-size:0.85rem; display:flex; align-items:center; gap:4px;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                Preview
                                            </a>
                                        @else
                                            <a href="{{ route('cashier.bill.view', $bill->id) }}" target="_blank" style="color:var(--primary-light); text-decoration:underline; font-size:0.85rem; display:flex; align-items:center; gap:4px;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                View PDF
                                            </a>
                                        @endif
                                        <a href="{{ route('cashier.bill.view', $bill->id) }}?download=1" download="{{ $bill->original_name }}" title="Download Bill" style="color:var(--secondary); font-size:1.1rem; text-decoration:none;">
                                            📥
                                        </a>
                                    </div>
                                @endforeach
                                </div>
                            @else
                                <span style="color:var(--text-muted);">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Category Breakdown -->
    <div style="margin-top:2rem;">
        <h3 class="mb-1">Category Breakdown</h3>
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:1rem;">
            @foreach($pageData['summary']['byCategory'] as $cat => $vals)
            <div class="card" style="padding:1rem;">
                <div style="font-weight:600; color:var(--primary-light); margin-bottom:8px; border-bottom:1px solid var(--glass-border); padding-bottom:5px;">
                    {{ strtoupper($cat) }}
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                    <span style="color:var(--text-muted);">In:</span>
                    <span style="color:var(--secondary); font-weight:600;">₹{{ number_format($vals['in'], 2) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                    <span style="color:var(--text-muted);">Out:</span>
                    <span style="color:var(--danger); font-weight:600;">₹{{ number_format($vals['out'], 2) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
  <script>
      window.app = window.app || {};
      window.app.viewImage = function(url) {
          Swal.fire({
              imageUrl: url,
              imageAlt: 'Bill Preview',
              width: 'auto',
              padding: '0',
              background: 'transparent',
              backdrop: 'rgba(0,0,0,0.8)',
              showConfirmButton: false,
              showCloseButton: true,
              customClass: {
                  popup: 'swal2-popup-custom',
                  image: 'swal2-image-custom'
              }
          });
      };
      
      // Custom style for image preview to not overflow
      const style = document.createElement('style');
      style.innerHTML = `
          .swal2-popup-custom { width: auto !important; max-width: 95vw !important; padding: 0 !important; background: transparent !important; }
          .swal2-image-custom { max-height: 90vh !important; max-width: 95vw !important; object-fit: contain; margin: 0 auto; display: block; border-radius: 8px; }
      `;
    document.head.appendChild(style);
</script>
@endpush
@endsection
