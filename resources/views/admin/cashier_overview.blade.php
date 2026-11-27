@extends('layouts.admin')

@section('content')
<div style="padding: 1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <h2 style="margin:0;">💰 Cashier Overview</h2>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <button class="btn btn-secondary" onclick="location.reload()" style="width:auto; padding:0.6rem 1rem;">🔄 Refresh</button>
            <button type="button" class="btn btn-sm" onclick="recoverOldBillsPrompt()" style="width:auto; padding:0.6rem 1rem; background:#2563eb; color:#fff; border:none; border-radius:6px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:0.4rem;" title="Scan server disk and recover old bill images">
                🔄 Recover Bills
            </button>
            <a href="{{ route(request()->segment(1) . '.cashier.logs') }}" class="btn" style="width:auto; padding:0.6rem 1rem; background-color:#eab308 !important; color:#000000 !important; font-weight:700; text-decoration:none; display:flex; align-items:center; gap:5px; border-radius:12px;" onmouseover="this.style.backgroundColor='#ca8a04'" onmouseout="this.style.backgroundColor='#eab308'">
                📝 View Edit Logs
            </a>
            <button type="button" class="btn" onclick="window.downloadPdfAsync('{{ route(request()->segment(1) . '.cashier_overview.pdf') }}', {{ json_encode(request()->all()) }}, this)" style="width:auto; padding:0.6rem 1rem; display:flex; align-items:center; gap:5px; cursor:pointer;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Export PDF
            </button>
        </div>
    </div>

    @php
        $dateRange = $pageData['dateRange'] ?? (request('range') ?: (request('start') || request('end') || request('date_from') || request('date_to') ? 'custom' : 'all'));
        $startDate = $pageData['startDate'] ?? (request('start') ?: request('date_from'));
        $endDate = $pageData['endDate'] ?? (request('end') ?: request('date_to'));
        $currentStatus = strtoupper(trim((string)(request('type') ?: request('status'))));
    @endphp

    <!-- Filter Card: Date Range, Cashier & Status Filter -->
    <div class="card" style="padding:1rem 1.2rem; margin-bottom:1.5rem; background:var(--bg-card); border:1px solid var(--border-soft); border-radius:10px;">
        <form method="GET" action="{{ url()->current() }}" id="cashierOverviewFilterForm" style="display:flex; flex-direction:column; gap:0.9rem;">
            <div style="display:flex; align-items:center; gap:1.2rem; flex-wrap:wrap;">
                
                <!-- 1st: Date Range Filter -->
                <div style="display:flex; align-items:center; gap:0.6rem; flex:1 1 200px;">
                    <label for="date_range_select" style="font-weight:600; font-size:0.85rem; color:var(--text-muted); white-space:nowrap; margin:0;">📅 DATE RANGE:</label>
                    <select name="range" id="date_range_select" onchange="this.form.submit()" style="width:100%; padding:0.6rem 0.9rem; border-radius:8px; font-size:0.9rem; border:1px solid var(--border-soft); background:var(--bg-hover); color:var(--text-main); font-weight:600; outline:none;">
                        <option value="all" {{ $dateRange === 'all' ? 'selected' : '' }}>UP TO DATE</option>
                        <option value="today" {{ $dateRange === 'today' ? 'selected' : '' }}>TODAY</option>
                        <option value="yesterday" {{ $dateRange === 'yesterday' ? 'selected' : '' }}>YESTERDAY</option>
                        <option value="this_week" {{ $dateRange === 'this_week' ? 'selected' : '' }}>THIS WEEK</option>
                        <option value="last_week" {{ $dateRange === 'last_week' ? 'selected' : '' }}>LAST WEEK</option>
                        <option value="this_month" {{ $dateRange === 'this_month' ? 'selected' : '' }}>THIS MONTH</option>
                        <option value="last_month" {{ $dateRange === 'last_month' ? 'selected' : '' }}>LAST MONTH</option>
                        <option value="custom" {{ $dateRange === 'custom' ? 'selected' : '' }}>CUSTOM RANGE</option>
                    </select>
                </div>

                <!-- 2nd: Select Cashier -->
                <div style="display:flex; align-items:center; gap:0.6rem; flex:1 1 220px;">
                    <label for="cashier_id_select" style="font-weight:600; font-size:0.85rem; color:var(--text-muted); white-space:nowrap; margin:0;">👤 CASHIER:</label>
                    <select name="cashier_id" id="cashier_id_select" onchange="this.form.submit()" style="width:100%; padding:0.6rem 0.9rem; border-radius:8px; font-size:0.9rem; border:1px solid var(--border-soft); background:var(--bg-hover); color:var(--text-main); font-weight:600; outline:none;">
                        <option value="">-- ALL CASHIERS --</option>
                        @if(!empty($pageData['cashiers']))
                            @foreach($pageData['cashiers'] as $c)
                                <option value="{{ $c->id }}" {{ request('cashier_id') == $c->id ? 'selected' : '' }}>
                                    {{ strtoupper($c->name) }} @if($c->username)({{ strtoupper($c->username) }})@endif
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- 3rd: Status Filter (All, In, Out) -->
                <div style="display:flex; align-items:center; gap:0.6rem; flex:1 1 180px;">
                    <label for="status_select" style="font-weight:600; font-size:0.85rem; color:var(--text-muted); white-space:nowrap; margin:0;">📊 STATUS:</label>
                    <select name="type" id="status_select" onchange="this.form.submit()" style="width:100%; padding:0.6rem 0.9rem; border-radius:8px; font-size:0.9rem; border:1px solid var(--border-soft); background:var(--bg-hover); color:var(--text-main); font-weight:600; outline:none;">
                        <option value="" {{ empty($currentStatus) ? 'selected' : '' }}>-- ALL (IN & OUT) --</option>
                        <option value="IN" {{ $currentStatus === 'IN' ? 'selected' : '' }}>🟢 IN (CASH IN)</option>
                        <option value="OUT" {{ $currentStatus === 'OUT' ? 'selected' : '' }}>🔴 OUT (CASH OUT)</option>
                    </select>
                </div>

                @if(($dateRange && $dateRange !== 'all') || request('cashier_id') || request('type') || request('status') || request('start') || request('end'))
                    <a href="{{ url()->current() }}" class="btn btn-secondary" style="width:auto; padding:0.55rem 1rem; font-size:0.85rem; text-decoration:none; white-space:nowrap;">✕ Clear Filter</a>
                @endif
            </div>

            <!-- Custom Date Range Row (visible when 'custom' is selected) -->
            @if($dateRange === 'custom')
                <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; padding-top:0.6rem; border-top:1px dashed var(--border-soft);">
                    <div style="display:flex; align-items:center; gap:8px; flex:1 1 200px;">
                        <label style="font-weight:600; font-size:0.82rem; color:var(--text-muted); white-space:nowrap; margin:0;">FROM DATE:</label>
                        <input type="date" name="start" value="{{ $startDate }}" onchange="this.form.submit()" title="From Date"
                            style="width:100%; padding:0.55rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft); background:var(--bg-hover); color:var(--text-main); font-size:0.88rem; outline:none;">
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; flex:1 1 200px;">
                        <label style="font-weight:600; font-size:0.82rem; color:var(--text-muted); white-space:nowrap; margin:0;">TO DATE:</label>
                        <input type="date" name="end" value="{{ $endDate }}" onchange="this.form.submit()" title="To Date"
                            style="width:100%; padding:0.55rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft); background:var(--bg-hover); color:var(--text-main); font-size:0.88rem; outline:none;">
                    </div>
                </div>
            @endif
        </form>
    </div>

    <!-- Summary Cards -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">
        <div class="card" style="padding:1.2rem; border-left: 4px solid #16a34a;">
            <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Total Income</div>
            <div style="font-size:1.8rem; font-weight:bold; color:#16a34a; margin-top:5px;">₹{{ number_format($pageData['summary']['totalIn'], 2) }}</div>
        </div>
        <div class="card" style="padding:1.2rem; border-left: 4px solid var(--danger);">
            <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Total Expenses</div>
            <div style="font-size:1.8rem; font-weight:bold; color:var(--danger); margin-top:5px;">₹{{ number_format($pageData['summary']['totalOut'], 2) }}</div>
        </div>
        <div class="card" style="padding:1.2rem; border-left: 4px solid {{ $pageData['summary']['balance'] >= 0 ? '#16a34a' : 'var(--danger)' }};">
            <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Net Balance</div>
            <div style="font-size:1.8rem; font-weight:bold; color:{{ $pageData['summary']['balance'] >= 0 ? '#16a34a' : 'var(--danger)' }}; margin-top:5px;">₹{{ number_format($pageData['summary']['balance'], 2) }}</div>
        </div>
    </div>

    <!-- Cashier Breakdown -->
    <div style="margin-top:0.5rem; margin-bottom:2rem;">
        <h3 class="mb-1">Cashier Breakdown</h3>
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:1rem;">
            @foreach($pageData['summary']['byCashier'] as $vals)
            <div class="card" style="padding:1rem;">
                <div style="font-weight:600; color:var(--primary-light); margin-bottom:8px; border-bottom:1px solid var(--glass-border); padding-bottom:5px;">
                    <div>{{ strtoupper($vals['name']) }}</div>
                    @if(!empty($vals['username']))
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:none; margin-top:2px;">User ID: {{ $vals['username'] }}</div>
                    @endif
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                    <span style="color:var(--text-muted);">In:</span>
                    <span style="color:#16a34a; font-weight:600;">₹{{ number_format($vals['in'], 2) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                    <span style="color:var(--text-muted);">Out:</span>
                    <span style="color:var(--danger); font-weight:600;">₹{{ number_format($vals['out'], 2) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.9rem; margin-top:5px; padding-top:5px; border-top:1px dashed var(--glass-border);">
                    <span style="color:var(--text-muted);">Balance:</span>
                    <span style="font-weight:bold; color:{{ $vals['balance'] >= 0 ? '#16a34a' : 'var(--danger)' }};">₹{{ number_format($vals['balance'], 2) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    @php
        if (!isset($pageData['balMap'])) {
            $balMap = [];
            $runningBal = 0;
            $chrono = collect($pageData['transactions'])->sort(function($a, $b) {
                $tA = strtotime($a->date ?: $a->created_at);
                $tB = strtotime($b->date ?: $b->created_at);
                return $tA === $tB ? ($a->id <=> $b->id) : ($tA <=> $tB);
            });
            foreach ($chrono as $t) {
                $runningBal += ($t->type === 'IN' ? (float)$t->amount : -(float)$t->amount);
                $balMap[$t->id] = $runningBal;
            }
            $pageData['balMap'] = $balMap;
        }
    @endphp

    <!-- Details Table -->
    <div class="card" style="padding:1.2rem; margin-bottom:2rem;">
        <div class="card-title" style="margin-bottom:1rem;">Transaction Ledger</div>
        <div class="table-container" style="overflow-x:auto;">
            <table id="admin-cashier-table" style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                <thead>
                    <tr style="background:rgba(0,0,0,0.05); border-bottom:1px solid var(--border-soft, #DDCFAF);">
                        <th style="padding:12px; text-align:left;">Date</th>
                        <th style="padding:12px; text-align:left;">Cashier</th>
                        <th style="padding:12px; text-align:center;">Type</th>
                        <th style="padding:12px; text-align:left;">Particulars / Note</th>
                        <th style="padding:12px; text-align:right;">Amount</th>
                        <th style="padding:12px; text-align:right;">Balance</th>
                        <th style="padding:12px; text-align:center;">Bills</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pageData['transactions'] as $tx)
                    <tr style="border-bottom:1px solid var(--border-soft, #DDCFAF);">
                        <td style="padding:12px; font-size:0.75rem; white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($tx->date ?: $tx->created_at)->timezone('Asia/Kolkata')->format('d/m/Y') }}<br>
                            <span style="color:var(--text-muted);">{{ \Carbon\Carbon::parse($tx->date ?: $tx->created_at)->timezone('Asia/Kolkata')->format('h:i A') }}</span>
                        </td>
                        <td style="padding:12px; font-weight:600; color:var(--text-main); white-space:nowrap;">
                            <div>👤 {{ $tx->user?->name ?? 'Unknown' }}</div>
                            @if($tx->user)
                                <div style="font-size:0.75rem; color:var(--primary-light); font-weight:700; margin-top:2px;">
                                    User ID: {{ $tx->user->username ?: $tx->user->id }}
                                </div>
                            @endif
                        </td>
                        <td style="padding:12px; text-align:center;">
                            <span style="display:inline-block; min-width:55px; text-align:center; padding:4px 8px; border-radius:4px; font-weight:bold; background: #d3d3d3de; color:{{ $tx->type === 'IN' ? '#2ecc71' : 'red' }};">
                                {{ $tx->type }}
                            </span>
                        </td>
                        <td style="padding:12px;">
                            <div style="font-weight:600; color:var(--text-main);">{{ $tx->note ?: 'Cash ' . $tx->type }}</div>
                            @if($tx->description)
                                <div style="font-size:0.72rem; color:var(--text-muted);">{{ $tx->description }}</div>
                            @endif
                            @if($tx->site)
                                <div style="font-size:0.7rem; color:var(--text-muted); margin-top:2px;">📍 {{ $tx->site }}</div>
                            @endif
                        </td>
                        <td style="padding:12px; font-weight:bold; color:{{ $tx->type === 'IN' ? '#16a34a' : '#dc2626' }}; text-align:right; white-space:nowrap;">
                            {{ $tx->type === 'IN' ? '+' : '-' }}₹{{ number_format($tx->amount, 2) }}
                        </td>
                        @php $cBal = $pageData['balMap'][$tx->id] ?? 0; @endphp
                        <td style="padding:12px; font-weight:bold; color:{{ $cBal >= 0 ? '#16a34a' : '#dc2626' }}; text-align:right; white-space:nowrap;">
                            ₹{{ number_format($cBal, 2) }}
                        </td>
                        <td style="padding:12px; text-align:center; min-width:90px;">
                            @if($tx->bills && $tx->bills->count() > 0)
                                <div style="display:flex; flex-wrap:wrap; justify-content:center; align-items:center; gap:8px;">
                                @foreach($tx->bills as $bill)
                                    @php
                                        $viewUrl = url(request()->segment(1) . '/cashier/bill/' . $bill->id . '/view');
                                        $downloadUrl = $viewUrl . '?download=1';
                                        $isImg = $bill->isImage();
                                        $isPdf = $bill->isPdf();
                                    @endphp
                                    @if($isImg)
                                        <div style="display:inline-flex; flex-direction:column; align-items:center; gap:2px;">
                                            <div style="position:relative; width:44px; height:44px; border-radius:6px; overflow:hidden; border:1px solid #cbd5e1; box-shadow:0 1px 3px rgba(0,0,0,0.12); cursor:pointer; background:#f8fafc;"
                                                 title="Click to preview {{ $bill->original_name }}"
                                                 onclick="app.viewBill({{ $bill->id }}, 'image')">
                                                <img src="{{ $viewUrl }}"
                                                     alt="{{ $bill->original_name }}"
                                                     loading="lazy"
                                                     style="width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.15s ease;"
                                                     onmouseover="this.style.transform='scale(1.15)';"
                                                     onmouseout="this.style.transform='scale(1)';"
                                                     onerror="if(this.dataset.triedFallback !== '1'){ this.dataset.triedFallback='1'; this.src='{{ $bill->url ?: '' }}'; } else { this.style.display='none'; this.nextElementSibling.style.display='flex'; }" />
                                                <div style="display:none; width:100%; height:100%; align-items:center; justify-content:center; font-size:1.1rem; background:#f1f5f9; color:#64748b;">
                                                    🖼️
                                                </div>
                                            </div>
                                            <a href="{{ $downloadUrl }}" download="{{ $bill->original_name }}" title="Download {{ $bill->original_name }}" style="color:var(--secondary, #d97706); font-size:0.8rem; text-decoration:none; line-height:1; margin-top:1px;">
                                                📥
                                            </a>
                                        </div>
                                    @elseif($isPdf)
                                        <div style="display:inline-flex; flex-direction:column; align-items:center; gap:2px;">
                                            <button type="button" onclick="app.viewBill({{ $bill->id }}, 'pdf')" title="View {{ $bill->original_name }}" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; border-radius:4px; padding:3px 6px; font-size:0.75rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:2px; line-height:1.2;">
                                                📄 PDF
                                            </button>
                                            <a href="{{ $downloadUrl }}" download="{{ $bill->original_name }}" title="Download {{ $bill->original_name }}" style="color:var(--secondary, #d97706); font-size:0.8rem; text-decoration:none; line-height:1; margin-top:1px;">
                                                📥
                                            </a>
                                        </div>
                                    @else
                                        <div style="display:inline-flex; flex-direction:column; align-items:center; gap:2px;">
                                            <button type="button" onclick="app.viewBill({{ $bill->id }}, '{{ $bill->file_type }}')" title="View {{ $bill->original_name }}" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:4px; padding:3px 6px; font-size:0.75rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:2px; line-height:1.2;">
                                                📎 Doc
                                            </button>
                                            <a href="{{ $downloadUrl }}" download="{{ $bill->original_name }}" title="Download {{ $bill->original_name }}" style="color:var(--secondary, #d97706); font-size:0.8rem; text-decoration:none; line-height:1; margin-top:1px;">
                                                📥
                                            </a>
                                        </div>
                                    @endif
                                @endforeach
                                    <button type="button" onclick="app.uploadBillPrompt({{ $tx->id }})" title="Attach Another Bill" style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:6px; width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; font-size:1.1rem; cursor:pointer; color:#64748b; line-height:1;" onmouseover="this.style.borderColor='var(--secondary, #d97706)'; this.style.color='var(--secondary, #d97706)';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.color='#64748b';">
                                        +
                                    </button>
                                </div>
                            @else
                                <div style="display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                                    <span style="color:var(--text-muted); font-size:0.75rem;">No Bills</span>
                                    <button type="button" onclick="app.uploadBillPrompt({{ $tx->id }})" title="Attach / Upload Bill" style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:4px; padding:2px 7px; font-size:0.75rem; font-weight:600; cursor:pointer; color:#374151; display:inline-flex; align-items:center; gap:3px;" onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#94a3b8';" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1';">
                                        📎 Add
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding:2.5rem; text-align:center; color:var(--text-muted);">
                            No transactions found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function recoverOldBillsPrompt() {
  Swal.fire({
    title: 'Scan & Recover Bill Images?',
    html: 'This will search server directories, backups, and storage locations for your uploaded bill images and restore them automatically.<br><br>Do you want to proceed?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, Scan & Recover',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#2563eb'
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        title: 'Scanning Server Disk...',
        text: 'Searching for bill image files...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      const url = '{{ url(request()->segment(1) . "/cashier/auto-recover-bills") }}';
      fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        }
      })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          Swal.fire({
            icon: 'error',
            title: 'Failed',
            text: data.message || 'Error occurred while recovering bills.'
          });
          return;
        }

        if (data.recovered > 0) {
          Swal.fire({
            icon: 'success',
            title: 'Recovery Successful!',
            html: `<b>${data.recovered}</b> bill image(s) were restored to storage!<br><br>The page will now reload to display them.`,
            confirmButtonText: 'Great!'
          }).then(() => {
            location.reload();
          });
        } else if (data.stillMissing > 0) {
          Swal.fire({
            icon: 'info',
            title: 'Files Need Restoring from Backup',
            html: `
              <div style="text-align:left; font-size:0.85rem; line-height:1.5;">
                <p>Database has <b>${data.total}</b> bill records, but <b>${data.stillMissing}</b> image file(s) are missing from the server folder (because the home directory was reset).</p>
                <hr style="border:0; border-top:1px solid #e2e8f0; margin:10px 0;">
                <b>To restore from Hostinger Automated Backups:</b>
                <ol style="margin:5px 0 10px 18px; padding:0;">
                  <li>Log in to <b>Hostinger hPanel</b> &rarr; <b>pentapureadmin.in</b></li>
                  <li>Go to <b>Files</b> &rarr; <b>Backups</b> &rarr; <b>File Backups</b></li>
                  <li>Select date <b>25 Nov 2026</b> or <b>26 Nov 2026</b></li>
                  <li>Select <code>pentapure/storage/app/public/bills</code> and click <b>Restore files</b></li>
                </ol>
                <p style="margin-bottom:0; color:#64748b; font-size:0.8rem;">After restoring files in Hostinger, click 'Recover Bills' or reload this page and your images will show up instantly!</p>
              </div>
            `,
            confirmButtonText: 'Got It',
            confirmButtonColor: '#2563eb',
            width: '550px'
          });
        } else {
          Swal.fire({
            icon: 'success',
            title: 'All Good!',
            text: data.message || 'All bill images are present on disk.',
            confirmButtonColor: '#2563eb'
          });
        }
      })
      .catch(err => {
        Swal.fire({
          icon: 'error',
          title: 'Server Error',
          text: 'Could not communicate with server: ' + err.message
        });
      });
    }
  });
}
</script>
@endsection
