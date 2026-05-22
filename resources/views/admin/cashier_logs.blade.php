@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <h2 style="margin:0;">📝 Cashier Activity Logs</h2>
  </div>

  <div class="card" style="padding:1.2rem;">
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Cashier</th>
            <th>Action</th>
            <th>Transaction ID</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          @foreach($pageData['logs'] as $log)
          <tr>
            <td style="font-size:0.8rem; white-space:nowrap;">
              {{ $log->created_at->format('d M Y') }}<br>
              <span style="color:var(--text-muted);">{{ $log->created_at->format('h:i A') }}</span>
            </td>
            <td style="font-weight:600;">{{ $log->user->name ?? 'System' }}</td>
            <td>
              @if($log->action === 'EDITED')
                <span class="badge badge-pending">EDITED</span>
              @elseif($log->action === 'DELETED')
                <span class="badge badge-danger">DELETED</span>
              @elseif($log->action === 'BILL_UPLOADED')
                <span class="badge badge-info">BILL UPLOADED</span>
              @elseif($log->action === 'BILL_DELETED')
                <span class="badge badge-danger">BILL DELETED</span>
              @else
                <span class="badge">{{ $log->action }}</span>
              @endif
            </td>
            <td>
                @if($log->transaction_id)
                  <span style="color:var(--secondary); font-weight:bold;">#{{ $log->transaction_id }}</span>
                @else
                  <span style="color:var(--text-muted);">-</span>
                @endif
            </td>
            <td style="font-size:0.8rem; max-width: 400px;">
              @if($log->action === 'EDITED')
                <div style="color:var(--text-muted); margin-bottom: 4px;">Old Data:</div>
                <pre style="margin:0; background:rgba(0,0,0,0.2); padding:6px; border-radius:4px; overflow-x:auto;">{{ json_encode($log->old_data, JSON_PRETTY_PRINT) }}</pre>
                <div style="color:var(--text-muted); margin-top: 8px; margin-bottom: 4px;">New Data:</div>
                <pre style="margin:0; background:rgba(0,0,0,0.2); padding:6px; border-radius:4px; overflow-x:auto;">{{ json_encode($log->new_data, JSON_PRETTY_PRINT) }}</pre>
              @elseif($log->action === 'DELETED')
                <div style="color:var(--text-muted); margin-bottom: 4px;">Deleted Transaction Data:</div>
                <pre style="margin:0; background:rgba(0,0,0,0.2); padding:6px; border-radius:4px; overflow-x:auto;">{{ json_encode($log->old_data, JSON_PRETTY_PRINT) }}</pre>
              @else
                <pre style="margin:0; background:rgba(0,0,0,0.2); padding:6px; border-radius:4px; overflow-x:auto;">{{ json_encode($log->new_data ?? $log->old_data, JSON_PRETTY_PRINT) }}</pre>
              @endif
            </td>
          </tr>
          @endforeach
          @if($pageData['logs']->isEmpty())
          <tr>
            <td colspan="5" style="text-align:center; padding:2rem; color:var(--text-muted);">No activity logs found.</td>
          </tr>
          @endif
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top:1.5rem; display:flex; justify-content:center;">
      {{ $pageData['logs']->links() }}
    </div>
  </div>
</div>
@endsection
