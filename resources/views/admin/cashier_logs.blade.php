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
          @php
            $txId = $log->resolved_transaction_id ?? $log->transaction_id ?? ($log->old_data['id'] ?? ($log->new_data['id'] ?? null));
            $old = is_array($log->old_data) ? $log->old_data : (json_decode($log->old_data ?? '[]', true) ?: []);
            $new = is_array($log->new_data) ? $log->new_data : (json_decode($log->new_data ?? '[]', true) ?: []);
          @endphp
          <tr style="border-bottom:1px solid var(--border-soft); vertical-align:top;">
            <td style="padding:12px; font-size:0.8rem; white-space:nowrap;">
              {{ $log->created_at->format('d M Y') }}<br>
              <span style="color:var(--text-muted);">{{ $log->created_at->format('h:i A') }}</span>
            </td>
            <td style="padding:12px; font-weight:600;">
              {{ $log->user->name ?? 'System' }}
            </td>
            <td style="padding:12px; text-align:center;">
              @if($log->action === 'EDITED')
                <span class="badge badge-pending" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; padding:4px 8px; border-radius:6px; font-weight:700; font-size:0.75rem;">✏️ EDITED</span>
              @elseif($log->action === 'DELETED')
                <span class="badge badge-danger" style="background:#fee2e2; color:#dc2626; border:1px solid #fecaca; padding:4px 8px; border-radius:6px; font-weight:700; font-size:0.75rem;">🗑️ DELETED</span>
              @elseif($log->action === 'BILL_UPLOADED')
                <span class="badge badge-info" style="background:#e0f2fe; color:#0284c7; border:1px solid #bae6fd; padding:4px 8px; border-radius:6px; font-weight:700; font-size:0.75rem;">📎 BILL ADDED</span>
              @elseif($log->action === 'BILL_DELETED')
                <span class="badge badge-danger" style="background:#fee2e2; color:#dc2626; border:1px solid #fecaca; padding:4px 8px; border-radius:6px; font-weight:700; font-size:0.75rem;">✕ BILL DELETED</span>
              @else
                <span class="badge">{{ $log->action }}</span>
              @endif
            </td>
            <td style="padding:12px; text-align:center;">
              @if($txId)
                <span style="display:inline-block; font-weight:700; font-size:0.88rem; color:var(--primary-dark, #b45309); background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.25); padding:2px 8px; border-radius:6px;">#{{ $txId }}</span>
              @else
                <span style="color:var(--text-muted);">-</span>
              @endif
            </td>
            <td style="padding:12px; font-size:0.82rem; min-width:280px; max-width:550px;">
              @if($log->action === 'DELETED')
                @php
                  $amount = isset($old['amount']) ? (float)$old['amount'] : null;
                  $type = $old['type'] ?? '';
                  $cat = $old['category'] ?? '';
                  $site = $old['site'] ?? '';
                  $note = $old['note'] ?? '';
                  $desc = $old['description'] ?? '';
                  $ref = $old['reference'] ?? '';
                  $txDate = $old['date'] ?? ($old['created_at'] ?? '');
                @endphp
                <div style="background:#fff1f2; border:1px solid #fecdd3; border-radius:8px; padding:0.65rem 0.85rem;">
                  <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:6px;">
                      <span style="font-weight:700; font-size:0.75rem; padding:2px 7px; border-radius:4px; background:{{ $type === 'IN' ? '#dcfce7' : '#fee2e2' }}; color:{{ $type === 'IN' ? '#16a34a' : '#dc2626' }};">
                        {{ $type ?: 'TX' }}
                      </span>
                      @if($amount !== null)
                        <span style="font-weight:800; font-size:1rem; color:{{ $type === 'IN' ? '#16a34a' : '#dc2626' }};">
                          {{ $type === 'IN' ? '+' : '-' }}₹{{ number_format($amount, 2) }}
                        </span>
                      @endif
                    </div>
                    @if($cat)
                      <span style="font-size:0.72rem; font-weight:700; text-transform:uppercase; background:rgba(0,0,0,0.06); padding:2px 8px; border-radius:6px; color:#475569;">
                        🏷️ {{ str_replace('_', ' ', $cat) }}
                      </span>
                    @endif
                  </div>

                  @if($note || $desc)
                    <div style="font-weight:600; font-size:0.85rem; color:#1e293b; margin-top:3px;">
                      {{ $note ?: $desc }}
                    </div>
                    @if($note && $desc && $note !== $desc)
                      <div style="font-size:0.75rem; color:#64748b; margin-top:1px;">{{ $desc }}</div>
                    @endif
                  @endif

                  <div style="display:flex; gap:12px; flex-wrap:wrap; font-size:0.73rem; color:#64748b; margin-top:4px;">
                    @if($site)
                      <span>📍 Site: <strong>{{ $site }}</strong></span>
                    @endif
                    @if($ref)
                      <span>Ref: <strong>{{ $ref }}</strong></span>
                    @endif
                    @if($txDate)
                      <span>📅 Date: <strong>{{ \Carbon\Carbon::parse($txDate)->format('d M Y, h:i A') }}</strong></span>
                    @endif
                  </div>

                  <details style="margin-top:6px;">
                    <summary style="font-size:0.7rem; color:#94a3b8; cursor:pointer; user-select:none;">🔍 View Raw JSON</summary>
                    <pre style="margin:4px 0 0 0; background:#ffffff; color:#334155; font-size:0.7rem; padding:6px 8px; border-radius:4px; border:1px solid #fecdd3; max-height:160px; overflow:auto;">{{ json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                  </details>
                </div>

              @elseif($log->action === 'EDITED')
                @php
                  $tracked = [
                    'amount'      => 'Amount',
                    'type'        => 'Type',
                    'category'    => 'Category',
                    'note'        => 'Note',
                    'description' => 'Description',
                    'reference'   => 'Reference',
                    'site'        => 'Site',
                    'date'        => 'Date',
                  ];
                  $changes = [];
                  foreach ($tracked as $k => $label) {
                    $vOld = $old[$k] ?? null;
                    $vNew = $new[$k] ?? null;
                    if ((string)$vOld !== (string)$vNew) {
                      $changes[$label] = ['old' => $vOld, 'new' => $vNew];
                    }
                  }
                @endphp
                <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:0.65rem 0.85rem;">
                  @if(!empty($changes))
                    <div style="font-size:0.75rem; font-weight:700; color:#92400e; margin-bottom:4px;">Changed Fields:</div>
                    <div style="display:flex; flex-direction:column; gap:4px;">
                      @foreach($changes as $label => $c)
                        <div style="font-size:0.78rem; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                          <span style="font-weight:600; color:#475569; min-width:80px;">{{ $label }}:</span>
                          <span style="color:#dc2626; text-decoration:line-through; background:rgba(239,68,68,0.1); padding:1px 5px; border-radius:4px;">
                            {{ $label === 'Amount' && is_numeric($c['old']) ? '₹' . number_format($c['old'], 2) : ($c['old'] ?? 'empty') }}
                          </span>
                          <span>➔</span>
                          <span style="color:#16a34a; font-weight:700; background:rgba(22,163,74,0.1); padding:1px 5px; border-radius:4px;">
                            {{ $label === 'Amount' && is_numeric($c['new']) ? '₹' . number_format($c['new'], 2) : ($c['new'] ?? 'empty') }}
                          </span>
                        </div>
                      @endforeach
                    </div>
                  @else
                    <div style="font-size:0.78rem; color:#92400e;">Transaction updated.</div>
                  @endif

                  <details style="margin-top:6px;">
                    <summary style="font-size:0.7rem; color:#94a3b8; cursor:pointer; user-select:none;">🔍 View Raw Changes JSON</summary>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-top:4px;">
                      <div>
                        <div style="font-size:0.68rem; font-weight:700; color:#dc2626;">Old:</div>
                        <pre style="margin:2px 0 0 0; background:#ffffff; color:#334155; font-size:0.68rem; padding:6px; border-radius:4px; border:1px solid #fde68a; max-height:140px; overflow:auto;">{{ json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                      </div>
                      <div>
                        <div style="font-size:0.68rem; font-weight:700; color:#16a34a;">New:</div>
                        <pre style="margin:2px 0 0 0; background:#ffffff; color:#334155; font-size:0.68rem; padding:6px; border-radius:4px; border:1px solid #fde68a; max-height:140px; overflow:auto;">{{ json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                      </div>
                    </div>
                  </details>
                </div>

              @elseif($log->action === 'BILL_UPLOADED' || $log->action === 'BILL_DELETED')
                @php
                  $billInfo = !empty($new['bill_id']) ? $new : $old;
                @endphp
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.6rem 0.8rem; font-size:0.8rem;">
                  <span>📎 Bill: <strong>{{ $billInfo['original_name'] ?? 'Document' }}</strong></span>
                  @if(!empty($billInfo['bill_id']))
                    <span style="color:#64748b; font-size:0.72rem; margin-left:6px;">(Bill #{{ $billInfo['bill_id'] }})</span>
                  @endif
                </div>

              @else
                <pre style="margin:0; background:rgba(0,0,0,0.05); padding:6px; border-radius:4px; overflow-x:auto;">{{ json_encode($new ?: $old, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
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
