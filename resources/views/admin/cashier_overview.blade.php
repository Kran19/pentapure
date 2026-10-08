@extends('layouts.admin')

@section('content')
<div style="padding: 1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <h2 style="margin:0;">💰 Cashier Overview</h2>
        <div style="display:flex; gap:0.5rem;">
            <button class="btn btn-secondary" onclick="location.reload()" style="width:auto; padding:0.6rem 1rem;">🔄 Refresh</button>
            <a href="{{ route(request()->segment(1) . '.cashier.logs') }}" class="btn" style="width:auto; padding:0.6rem 1rem; background-color:#eab308 !important; color:#000000 !important; font-weight:700; text-decoration:none; display:flex; align-items:center; gap:5px; border-radius:12px;" onmouseover="this.style.backgroundColor='#ca8a04'" onmouseout="this.style.backgroundColor='#eab308'">
                📝 View Edit Logs
            </a>
            <button type="button" class="btn" onclick="window.downloadPdfAsync('{{ route(request()->segment(1) . '.cashier_overview.pdf') }}', { cashier_id: '{{ request('cashier_id') }}', type: '{{ request('type') ?: request('status') }}' }, this)" style="width:auto; padding:0.6rem 1rem; display:flex; align-items:center; gap:5px; cursor:pointer;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Export PDF
            </button>
        </div>
    </div>

    <!-- Filter Card: Cashier & Status Filter -->
    <div class="card" style="padding:0.85rem 1.2rem; margin-bottom:1.5rem; background:var(--bg-card); border:1px solid var(--border-soft); border-radius:10px;">
        <form method="GET" action="{{ url()->current() }}" style="display:flex; align-items:center; gap:1.2rem; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:0.6rem; flex:1 1 240px;">
                <label for="cashier_id_select" style="font-weight:600; font-size:0.85rem; color:var(--text-muted); white-space:nowrap; margin:0;">👤 SELECT CASHIER:</label>
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

            @php
                $currentStatus = strtoupper(trim((string)(request('type') ?: request('status'))));
            @endphp
            <div style="display:flex; align-items:center; gap:0.6rem; flex:1 1 200px;">
                <label for="status_select" style="font-weight:600; font-size:0.85rem; color:var(--text-muted); white-space:nowrap; margin:0;">📊 STATUS:</label>
                <select name="type" id="status_select" onchange="this.form.submit()" style="width:100%; padding:0.6rem 0.9rem; border-radius:8px; font-size:0.9rem; border:1px solid var(--border-soft); background:var(--bg-hover); color:var(--text-main); font-weight:600; outline:none;">
                    <option value="" {{ empty($currentStatus) ? 'selected' : '' }}>-- ALL (IN & OUT) --</option>
                    <option value="IN" {{ $currentStatus === 'IN' ? 'selected' : '' }}>🟢 IN (CASH IN)</option>
                    <option value="OUT" {{ $currentStatus === 'OUT' ? 'selected' : '' }}>🔴 OUT (CASH OUT)</option>
                </select>
            </div>

            @if(request('cashier_id') || request('type') || request('status'))
                <a href="{{ url()->current() }}" class="btn btn-secondary" style="width:auto; padding:0.55rem 1rem; font-size:0.85rem; text-decoration:none;">✕ Clear Filter</a>
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
                            {{ \Carbon\Carbon::parse($tx->date ?: $tx->created_at)->format('d/m/Y') }}<br>
                            <span style="color:var(--text-muted);">{{ \Carbon\Carbon::parse($tx->date ?: $tx->created_at)->format('h:i A') }}</span>
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
                        <td style="padding:12px; text-align:center; min-width:80px;">
                            @if($tx->bills && $tx->bills->count() > 0)
                                <div style="display:flex; flex-wrap:wrap; justify-content:center; align-items:center; gap:6px;">
                                @foreach($tx->bills as $bill)
                                    <button type="button" onclick="app.viewBill({{ $bill->id }}, '{{ $bill->file_type }}')" title="View {{ $bill->original_name }}" style="background:none; border:none; cursor:pointer; color:var(--primary); padding:2px; font-size:1.1rem; line-height:1;">
                                        📎
                                    </button>
                                    <a href="{{ url(request()->segment(1) . '/cashier/bill/' . $bill->id . '/view') }}?download=1" download="{{ $bill->original_name }}" title="Download {{ $bill->original_name }}" style="color:var(--secondary); font-size:1rem; text-decoration:none; display:inline-flex; align-items:center; line-height:1;">
                                        📥
                                    </a>
                                @endforeach
                                </div>
                            @else
                                <span style="color:var(--text-muted); font-size:0.75rem;">No Bills</span>
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

@endsection
