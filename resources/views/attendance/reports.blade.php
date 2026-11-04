@extends($layout)

@php
    $allSheetsPdfUrl = request()->segment(1) === 'attendance'
        ? url('attendance/history/all-sheets/pdf?month=' . $month)
        : url(request()->segment(1) . '/attendance/reports/all-sheets/pdf?month=' . $month);

    $summaryPdfUrl = request()->segment(1) === 'attendance'
        ? url('attendance/reports/summary/pdf?month=' . $month)
        : url(request()->segment(1) . '/attendance/reports/summary/pdf?month=' . $month);
@endphp

@section('content')
<div style="padding:1.5rem;">
  <div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px;">
    <h2 style="margin:0;">📑 Monthly Attendance & Salary Report</h2>
    
    <div style="display:flex; gap:10px; align-items:center;">
      <form method="GET" action="{{ url()->current() }}" style="display:flex; gap:10px; align-items:center;">
        <label style="font-weight:bold;">Month:</label>
        <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()" onclick="if(typeof this.showPicker === 'function'){ try{ this.showPicker(); }catch(e){} }" style="padding:0.4rem 0.6rem; border-radius:6px; border:1px solid #cbd5e1; cursor:pointer; font-weight:500;">
      </form>
      <button class="btn btn-sm" onclick="exportToExcel()" style="width:auto; padding:0.4rem 1rem; background:#27ae60; color:white;">📗 Export to Excel</button>
      <a class="btn btn-sm" href="{{ $allSheetsPdfUrl }}" target="_blank" style="width:auto; padding:0.4rem 1rem; background:#3498db; color:white; border:none; text-decoration:none; display:inline-flex; align-items:center; font-weight:600;">📄 Download All Sheets</a>
      <a class="btn btn-sm" href="{{ $summaryPdfUrl }}" target="_blank" style="width:auto; padding:0.4rem 1rem; background:#e74c3c; color:white; border:none; text-decoration:none; display:inline-flex; align-items:center; font-weight:600;">📄 Download Summary PDF</a>
    </div>
  </div>

  <div class="card" id="printable-report" style="padding:1.5rem;">
    <div style="text-align:center; margin-bottom:1.5rem;">
      <h3 style="margin:0;">PPF</h3>
      <div style="color:var(--text-muted);">Attendance &amp; Payroll Report - {{ \Carbon\Carbon::parse($month)->format('F Y') }}</div>
    </div>

    <!-- Search Toolbar -->
    <div class="no-print" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; flex-wrap:wrap; gap:0.75rem;">
      <div style="position:relative; flex:1; max-width:440px; min-width:260px;">
        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:0.95rem; opacity:0.6; pointer-events:none;">🔍</span>
        <input type="text" 
               id="reportSearchInput" 
               value="{{ $search ?? '' }}"
               placeholder="Search employee by name, department, status..." 
               oninput="filterReportsTable()" 
               style="width:100%; padding:0.55rem 2.2rem 0.55rem 2.4rem; border-radius:8px; border:1px solid var(--border-soft, #cbd5e1); background:var(--input-bg, #ffffff); font-size:0.875rem; color:inherit; outline:none; transition:border-color 0.2s, box-shadow 0.2s;">
        <button type="button" 
                id="reportSearchClear" 
                onclick="clearReportSearch()" 
                style="{{ !empty($search) ? 'display:inline-block;' : 'display:none;' }} position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:#9ca3af; font-size:1.1rem; cursor:pointer; padding:0 4px; line-height:1;" 
                title="Clear search">✕</button>
      </div>
      <div style="display:flex; align-items:center; gap:0.75rem;">
        <span id="reportCountBadge" style="font-size:0.82rem; font-weight:600; padding:5px 12px; border-radius:6px; background:rgba(59, 130, 246, 0.1); color:#3b82f6; border:1px solid rgba(59, 130, 246, 0.25);">
          Total: {{ count($reportData) }} employees
        </span>
      </div>
    </div>

    <div class="table-container">
      <table data-filterable="false" data-sortable="false" class="no-sort no-filter">
        <thead>
          <tr>
            <th style="width:45px;">#</th>
            <th>Employee Name</th>
            <th>Department</th>
            <th>Salary</th>
            <th class="no-print">Action</th>
            <th>Total Present</th>
            <th>Total OT / UT Hrs</th>
            <th>Advance (₹)</th>
            <th>Total Payable (₹)</th>
            <th style="min-width:220px;">Mark as Paid</th>
          </tr>
        </thead>
        <tbody>
          @php 
            $grandTotal = 0; 
            $isAttendanceApp = request()->segment(1) === 'attendance';
            $grouped = [];
            foreach($reportData as $d) {
                $dept = $d['worker']->department->name ?? 'Other';
                $grouped[$dept][] = $d;
            }
          @endphp
          
          @foreach($grouped as $deptName => $workers)
            @php
              $deptTotalPresent = array_sum(array_column($workers, 'present'));
              $deptTotalOt = array_sum(array_column($workers, 'total_ot'));
              $deptTotalUt = array_sum(array_column($workers, 'total_ut'));
              $deptNetOtUt = $deptTotalOt - $deptTotalUt;
              $deptTotalAdvance = array_sum(array_column($workers, 'advance'));
              $deptTotalEarned = array_sum(array_column($workers, 'attendance_salary'));
              $deptTotalPayable = array_sum(array_map(fn($w) => (float)($w['payable_salary'] ?? $w['total_wage']), $workers));
              $deptWorkerCount = count($workers);
              $deptSlug = \Illuminate\Support\Str::slug($deptName ?: 'other');
            @endphp
            <tr class="dept-header-row" data-dept-group="{{ $deptSlug }}" style="background:rgba(255,255,255,0.07); border-top:2px solid var(--border-soft, #e5e7eb);">
              <td colspan="5" style="font-weight:bold; color:var(--secondary); font-size:0.92rem;">
                📂 {{ strtoupper($deptName) }} <span style="font-size:0.8rem; font-weight:600; opacity:0.85; margin-left:0.4rem; color:var(--text-color);">(Total Workers: {{ $deptWorkerCount }})</span>
              </td>
              <td style="font-weight:bold; color:#000000; font-size:0.95rem;" data-csv-value="{{ $deptTotalPresent }}">
                <div>{{ $deptTotalPresent > 0 ? (floor($deptTotalPresent) == $deptTotalPresent ? number_format($deptTotalPresent, 0) : number_format($deptTotalPresent, 1)) : 0 }}</div>
                @if($deptTotalEarned > 0)
                  <div style="font-size:0.72rem; font-weight:700; color:#000000; margin-top:2px; white-space:nowrap;">
                    Earned: ₹{{ number_format($deptTotalEarned, 0) }}
                  </div>
                @endif
              </td>
              <td style="font-weight:bold; font-size:0.9rem;" title="Total OT: {{ number_format($deptTotalOt, 1) }} | Total UT: {{ number_format($deptTotalUt, 1) }}">
                {{ number_format($deptTotalOt, 1) }}
                @if($deptTotalUt > 0)
                  <span style="color:#ea580c; font-size:0.8rem; margin-left:4px;">(-{{ number_format($deptTotalUt, 1) }} UT)</span>
                @endif
              </td>
              <td style="font-weight:bold; font-size:0.95rem; color:#dc2626;" data-csv-value="{{ number_format($deptTotalAdvance, 2, '.', '') }}">{{ $deptTotalAdvance > 0 ? '₹' . number_format($deptTotalAdvance, 0) : '—' }}</td>
              <td style="font-weight:bold; color:var(--primary-light); font-size:1.05rem;" data-csv-value="{{ number_format($deptTotalPayable, 2, '.', '') }}">
                <div>₹{{ number_format($deptTotalPayable, 2) }}</div>
                @php
                  $deptTotalAllowance = array_sum(array_column($workers, 'allowance'));
                @endphp
                @if($deptTotalAllowance != 0)
                  <div style="font-size:0.7rem; font-weight:600; margin-top:3px; line-height:1.35; white-space:nowrap;">
                    @if($deptTotalAllowance > 0)
                      <span style="color:#2563eb;">Other: ₹{{ number_format($deptTotalAllowance, 0) }}</span>
                    @else
                      <span style="color:#dc2626;">- Other: ₹{{ number_format(abs($deptTotalAllowance), 0) }}</span>
                    @endif
                  </div>
                @endif
              </td>
              <td></td>
            </tr>
            @foreach($workers as $data)
              @php 
                $payable = (float)($data['payable_salary'] ?? $data['total_wage']);
                $grandTotal += $payable; 
                $adj = $data['adjustment'] ?? null;
                $isPaid = (bool)($adj?->is_paid ?? false);
                $rawDate = $adj?->paid_at ? \Carbon\Carbon::parse($adj->paid_at)->format('Y-m-d') : ($adj?->paid_note && preg_match('/^\d{4}-\d{2}-\d{2}$/', $adj->paid_note) ? $adj->paid_note : '');
                $paidDate = $isPaid ? ($rawDate ?: now()->format('Y-m-d')) : $rawDate;
              @endphp
              <tr class="report-row" 
                  id="row-worker-{{ $data['worker']->id }}"
                  data-worker-id="{{ $data['worker']->id }}"
                  data-dept-group="{{ $deptSlug }}"
                  data-name="{{ strtolower($data['worker']->name) }}"
                  data-dept="{{ strtolower($deptName) }}"
                  data-status="{{ ($data['is_inactive'] ?? false) ? 'inactive' : 'active' }}">
                <td style="font-weight:bold; color:var(--text-muted);">{{ $data['worker_number'] ?? '-' }}</td>
                <td style="font-weight:600;">
                  {{ $data['worker']->name }}
                  @if(!empty($data['is_inactive']))
                    <span style="font-size:0.65rem; background:rgba(239, 68, 68, 0.15); color:#ef4444; border:1px solid rgba(239, 68, 68, 0.3); padding:1px 5px; border-radius:4px; font-weight:700; margin-left:4px; vertical-align:middle;">INACTIVE</span>
                  @endif
                </td>
                <td>{{ $data['worker']->department->name }}</td>
                <td>
                  @php
                    $effSalary = $data['salary_amount'] ?? $data['worker']->salary_amount;
                    $effType   = $data['salary_type'] ?? $data['worker']->salary_type;
                    $isMukadam = ($effType === 'LABOUR_MUKADAM' || stripos($data['worker']->department->name ?? '', 'MUKADAM') !== false);
                  @endphp
                  <div style="display:flex; align-items:center; gap:6px;">
                    <div style="font-weight:bold; font-size:0.95rem;">₹{{ number_format($effSalary, 0) }}</div>
                    @if(empty($isReadOnly))
                      <button type="button" 
                              onclick="editMonthlySalary({{ $data['worker']->id }}, '{{ addslashes($data['worker']->name) }}', {{ (float)$effSalary }}, '{{ $month }}')"
                              class="no-print"
                              style="background:none; border:none; cursor:pointer; font-size:0.85rem; padding:0 2px; opacity:0.6; transition:opacity 0.2s;"
                              onmouseover="this.style.opacity='1'" 
                              onmouseout="this.style.opacity='0.6'"
                              title="Click to edit {{ \Carbon\Carbon::parse($month)->format('M Y') }} salary">
                        ✏️
                      </button>
                    @endif
                  </div>
                  @if($isMukadam)
                    <div style="font-size:0.65rem; font-weight:bold; color:#000000;">PER LABOUR SALARY</div>
                  @else
                    <div style="font-size:0.65rem; opacity:0.7;">{{ $effType }}</div>
                  @endif
                </td>
                <td class="no-print">
                  @php
                    $reportUrl = $isAttendanceApp 
                        ? url('attendance/history/worker/' . $data['worker']->id)
                        : url(request()->segment(1) . '/attendance/reports/worker/' . $data['worker']->id);
                  @endphp
                  <a href="{{ $reportUrl }}?month={{ $month }}" class="btn btn-sm" style="width:auto; padding:0.2rem 0.6rem; font-size:0.7rem; text-transform:uppercase;">View Sheet</a>
                </td>
                <td style="color:#000000; font-weight:bold;" data-csv-value="{{ $data['present'] }}">
                  <div>{{ $data['present'] }}</div>
                  <div style="font-size:0.72rem; font-weight:700; color:#000000; margin-top:2px; white-space:nowrap;">
                    Earned: ₹{{ number_format($data['attendance_salary'] ?? 0, 0) }}
                  </div>
                </td>
                <td style="font-weight:bold;" data-csv-value="{{ number_format(($data['total_ot'] ?? 0) - ($data['total_ut'] ?? 0), 1, '.', '') }}">
                  @php
                    $wOt = (float)($data['total_ot'] ?? 0);
                    $wUt = (float)($data['total_ut'] ?? 0);
                    $wNet = $wOt - $wUt;
                  @endphp
                  @if($wNet > 0)
                    <span style="color:#16a34a;" title="OT: {{ number_format($wOt, 1) }} | UT: {{ number_format($wUt, 1) }}">+{{ number_format($wNet, 1) }} OT</span>
                  @elseif($wNet < 0)
                    <span style="color:#ea580c;" title="OT: {{ number_format($wOt, 1) }} | UT: {{ number_format($wUt, 1) }}">-{{ number_format(abs($wNet), 1) }} UT</span>
                  @else
                    <span style="color:var(--text-muted); opacity:0.6;">0.0</span>
                  @endif
                </td>
                <td style="font-weight:bold; color:{{ ($data['advance'] ?? 0) > 0 ? '#dc2626' : 'var(--text-muted)' }}; font-size:0.95rem;" data-csv-value="{{ number_format($data['advance'] ?? 0, 2, '.', '') }}">
                  @if(($data['advance'] ?? 0) > 0)
                    ₹{{ number_format($data['advance'], 0) }}
                  @else
                    <span style="color:var(--text-muted); opacity:0.6;">—</span>
                  @endif
                </td>
                <td style="font-weight:bold; color:var(--primary-light); font-size:1.05rem;" data-csv-value="{{ number_format($payable, 2, '.', '') }}">
                  <div>₹{{ number_format($payable, 2) }}</div>
                  @if(($data['allowance'] ?? 0) != 0)
                    @php
                      $otherCustomLabel = !empty($data['allowance_label']) && !in_array(strtoupper($data['allowance_label']), ['OTHER', 'PETROL / FOODS']) ? $data['allowance_label'] : (!empty($data['deduction_label']) && !in_array(strtoupper($data['deduction_label']), ['OTHER', 'PETROL / FOODS']) ? $data['deduction_label'] : null);
                    @endphp
                    <div style="font-size:0.7rem; font-weight:600; margin-top:3px; line-height:1.35; white-space:nowrap;">
                      @if(($data['allowance'] ?? 0) > 0)
                        <span style="color:#2563eb;" @if($otherCustomLabel) title="{{ $otherCustomLabel }}" @endif>Other: ₹{{ number_format($data['allowance'], 0) }}</span>
                      @else
                        <span style="color:#dc2626;" @if($otherCustomLabel) title="{{ $otherCustomLabel }}" @endif>- Other: ₹{{ number_format(abs($data['allowance']), 0) }}</span>
                      @endif
                    </div>
                  @endif
                </td>
                <td>
                  @if(empty($isReadOnly))
                  <div style="display:flex; align-items:center; gap:6px;">
                    <button type="button" 
                            onclick="togglePaidStatus({{ $data['worker']->id }}, true)"
                            class="btn-paid-check"
                            style="border:none; background:{{ $isPaid ? '#22c55e' : '#e2e8f0' }}; color:{{ $isPaid ? '#fff' : '#64748b' }}; border-radius:50%; width:28px; height:28px; font-weight:bold; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-size:14px;"
                            title="Mark as Paid">
                      ✓
                    </button>
                    <button type="button" 
                            onclick="togglePaidStatus({{ $data['worker']->id }}, false)"
                            class="btn-paid-cross"
                            style="border:none; background:{{ !$isPaid ? '#ef4444' : '#e2e8f0' }}; color:{{ !$isPaid ? '#fff' : '#64748b' }}; border-radius:50%; width:28px; height:28px; font-weight:bold; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-size:14px;"
                            title="Mark as Unpaid">
                      ✕
                    </button>
                    <input type="date" 
                            id="paid-date-{{ $data['worker']->id }}" 
                            value="{{ $paidDate }}" 
                            onchange="updatePaidDate({{ $data['worker']->id }})"
                            onclick="if(typeof this.showPicker === 'function'){ try{ this.showPicker(); }catch(e){} }"
                            style="padding:4px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.78rem; width:135px; background:white; color:#0f172a; outline:none; cursor:pointer; font-weight:500;"
                            title="Click to open calendar and select payment date">
                  </div>
                  @endif
                  <div id="paid-badge-{{ $data['worker']->id }}" style="margin-top:3px; font-size:0.75rem; font-weight:bold; color:{{ $isPaid ? '#22c55e' : '#ef4444' }};">
                    {{ $isPaid ? '✓ PAID' . ($paidDate ? ' (' . \Carbon\Carbon::parse($paidDate)->format('d-m-Y') . ')' : '') : '✕ UNPAID' }}
                  </div>
                </td>
              </tr>
            @endforeach
          @endforeach
          
          <tr id="report-no-match-row" style="display:none;">
            <td colspan="10" style="text-align:center; padding:2.5rem 1rem; color:var(--text-muted);">
              <div style="font-size:1.8rem; margin-bottom:0.5rem; opacity:0.6;">🔍</div>
              <div style="font-weight:600; font-size:0.95rem;">No employees found</div>
              <div style="font-size:0.82rem; margin-top:0.25rem;">No employees matched "<span id="reportSearchKeyword" style="font-weight:bold; color:var(--text-color, #0f172a);"></span>"</div>
            </td>
          </tr>

          @if(empty($reportData))
            <tr><td colspan="10" style="text-align:center; color:var(--text-muted);">No attendance records found for this month.</td></tr>
          @else
            @php
              $grandTotalAdvance = array_sum(array_column($reportData, 'advance'));
              $grandTotalPresent = array_sum(array_column($reportData, 'present'));
              $grandTotalEarned = array_sum(array_column($reportData, 'attendance_salary'));
              $grandTotalAllowance = array_sum(array_column($reportData, 'allowance'));
            @endphp
            <tr class="payroll-grand-total-row" style="background:var(--glass-bg); font-weight:bold;">
              <td colspan="4" style="text-align:right;">Grand Total Payroll Liability:</td>
              <td class="no-print"></td>
              <td style="color:#000000; font-size:0.95rem; font-weight:bold;" data-csv-value="{{ $grandTotalPresent }}">
                <div>{{ $grandTotalPresent > 0 ? (floor($grandTotalPresent) == $grandTotalPresent ? number_format($grandTotalPresent, 0) : number_format($grandTotalPresent, 1)) : 0 }}</div>
                @if($grandTotalEarned > 0)
                  <div style="font-size:0.72rem; font-weight:700; color:#000000; margin-top:2px; white-space:nowrap;">
                    Earned: ₹{{ number_format($grandTotalEarned, 0) }}
                  </div>
                @endif
              </td>
              <td></td>
              <td style="color:#dc2626; font-size:1.05rem;" data-csv-value="{{ number_format($grandTotalAdvance, 2, '.', '') }}">{{ $grandTotalAdvance > 0 ? '₹' . number_format($grandTotalAdvance, 0) : '—' }}</td>
              <td style="color:var(--secondary); font-size:1.2rem;" data-csv-value="{{ number_format($grandTotal, 2, '.', '') }}">
                <div>₹{{ number_format($grandTotal, 2) }}</div>
                @if(!empty($reportData) && $grandTotalAllowance != 0)
                  <div style="font-size:0.75rem; font-weight:600; margin-top:3px; line-height:1.35; white-space:nowrap;">
                    @if($grandTotalAllowance > 0)
                      <span style="color:#2563eb;">Other: ₹{{ number_format($grandTotalAllowance, 0) }}</span>
                    @else
                      <span style="color:#dc2626;">- Other: ₹{{ number_format(abs($grandTotalAllowance), 0) }}</span>
                    @endif
                  </div>
                @endif
              </td>
              <td></td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function getTodayString() {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatDisplayDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function togglePaidStatus(workerId, isPaid) {
    const dateInput = document.getElementById('paid-date-' + workerId);
    if (isPaid && dateInput && !dateInput.value) {
        dateInput.value = getTodayString();
    }
    const paidDate = dateInput ? dateInput.value : '';
    sendPaidStatusUpdate(workerId, isPaid, paidDate);
}

function updatePaidDate(workerId) {
    const badge = document.getElementById('paid-badge-' + workerId);
    const isPaid = badge ? badge.innerText.includes('PAID') : false;
    const dateInput = document.getElementById('paid-date-' + workerId);
    const paidDate = dateInput ? dateInput.value : '';
    sendPaidStatusUpdate(workerId, isPaid, paidDate);
}

function sendPaidStatusUpdate(workerId, isPaid, paidDate) {
    const postUrl = "{{ str_contains(request()->path(), 'admin') ? url(str_replace('reports', 'reports', request()->path()) . '/worker/') : url(request()->path() . '/worker/') }}/" + workerId + "/toggle-paid";
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('month', '{{ $month }}');
    formData.append('is_paid', isPaid ? 1 : 0);
    formData.append('paid_date', paidDate || '');
    formData.append('paid_note', paidDate || '');

    fetch(postUrl, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(res => res.json()).then(data => {
        if (data.success) {
            const row = document.getElementById('row-worker-' + workerId);
            if (row) {
                const btnCheck = row.querySelector('.btn-paid-check');
                const btnCross = row.querySelector('.btn-paid-cross');
                const badge = document.getElementById('paid-badge-' + workerId);
                const dateInput = document.getElementById('paid-date-' + workerId);

                if (data.paid_date && dateInput) {
                    dateInput.value = data.paid_date;
                }

                if (data.is_paid) {
                    btnCheck.style.background = '#22c55e'; btnCheck.style.color = '#fff';
                    btnCross.style.background = '#e2e8f0'; btnCross.style.color = '#64748b';
                    if (badge) { 
                        const formatted = data.paid_date ? formatDisplayDate(data.paid_date) : '';
                        badge.innerText = '✓ PAID' + (formatted ? ' (' + formatted + ')' : ''); 
                        badge.style.color = '#22c55e'; 
                    }
                } else {
                    btnCheck.style.background = '#e2e8f0'; btnCheck.style.color = '#64748b';
                    btnCross.style.background = '#ef4444'; btnCross.style.color = '#fff';
                    if (badge) { badge.innerText = '✕ UNPAID'; badge.style.color = '#ef4444'; }
                }
            }
        }
    }).catch(err => console.error('Error updating paid status:', err));
}

function editMonthlySalary(workerId, workerName, currentSalary, monthStr) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Edit Salary (' + monthStr + ')',
            html: '<div style="margin-bottom:8px; font-weight:600; color:var(--text-color);">' + workerName + '</div>' +
                  '<div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:10px;">This salary change applies specifically to <b>' + monthStr + '</b> without altering past or other months.</div>',
            input: 'number',
            inputValue: currentSalary,
            inputAttributes: {
                step: '0.01',
                min: '0',
                style: 'font-size:1.15rem; font-weight:bold; text-align:center;'
            },
            showCancelButton: true,
            confirmButtonText: 'Save Salary',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3b82f6',
            preConfirm: (value) => {
                if (value === '' || isNaN(value) || parseFloat(value) < 0) {
                    Swal.showValidationMessage('Please enter a valid salary amount');
                    return false;
                }
                return value;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                saveMonthlySalary(workerId, result.value, monthStr);
            }
        });
    } else {
        const val = prompt('Enter ' + monthStr + ' salary for ' + workerName + ' (₹):', currentSalary);
        if (val !== null && val.trim() !== '' && !isNaN(val)) {
            saveMonthlySalary(workerId, val.trim(), monthStr);
        }
    }
}

function saveMonthlySalary(workerId, salaryVal, monthStr) {
    const postUrl = "{{ str_contains(request()->path(), 'admin') ? url(str_replace('reports', 'reports', request()->path()) . '/worker/') : url(request()->path() . '/worker/') }}/" + workerId + "/adjust";
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('month', monthStr);
    formData.append('salary_amount', salaryVal);

    fetch(postUrl, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(d => {
        if (d.success) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'Salary for ' + monthStr + ' updated.',
                    timer: 1200,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                location.reload();
            }
        } else {
            alert(d.message || 'Failed to update salary');
        }
    }).catch(err => {
        console.error('Error saving salary:', err);
        location.reload();
    });
}

function exportToExcel() {
    let csv = [];
    const table = document.querySelector("table");
    const rows = table.querySelectorAll("tr");
    
    for (let i = 0; i < rows.length; i++) {
        let row = [];
        let cols = rows[i].querySelectorAll("td, th");
        
        if (rows[i].classList.contains('no-print')) continue;
        if (rows[i].classList.contains('report-breakdown-row')) continue;

        for (let j = 0; j < cols.length; j++) {
            if (cols[j].classList.contains('no-print')) continue;
            
            let val = cols[j].getAttribute('data-csv-value');
            let data = (val !== null) ? val : cols[j].innerText.trim()
                .replace(/\n/g, " ")
                .replace(/\s\s+/g, " ");
            
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        
        if (row.length > 0) {
            csv.push(row.join(","));
        }
    }
    
    const csvFile = new Blob([csv.join("\n")], {type: "text/csv;charset=utf-8;"});
    const downloadLink = document.createElement("a");
    const fileName = "Monthly_Payroll_Report_{{ $month }}.csv";
    
    if (navigator.msSaveBlob) {
        navigator.msSaveBlob(csvFile, fileName);
    } else {
        downloadLink.download = fileName;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
}

function downloadAllIndividualSheets() {
    window.open("{{ $allSheetsPdfUrl }}", '_blank');
}

function filterReportsTable() {
    const input = document.getElementById('reportSearchInput');
    const clearBtn = document.getElementById('reportSearchClear');
    const badge = document.getElementById('reportCountBadge');
    const query = (input ? input.value : '').trim().toLowerCase();

    if (clearBtn) {
        clearBtn.style.display = query.length > 0 ? 'inline-block' : 'none';
    }

    const rows = document.querySelectorAll('.report-row');
    const deptHeaders = document.querySelectorAll('.dept-header-row');
    const grandTotalRow = document.querySelector('.payroll-grand-total-row');
    const deptMatchCounts = {};
    let totalVisible = 0;

    deptHeaders.forEach(h => {
        const group = h.getAttribute('data-dept-group');
        if (group) deptMatchCounts[group] = 0;
    });

    rows.forEach(row => {
        const group = row.getAttribute('data-dept-group');

        if (!query) {
            row.style.display = '';
            totalVisible++;
            if (group) deptMatchCounts[group] = (deptMatchCounts[group] || 0) + 1;
            return;
        }

        const name = row.getAttribute('data-name') || '';
        const dept = row.getAttribute('data-dept') || '';
        const status = row.getAttribute('data-status') || '';
        const fullText = (row.textContent || '').toLowerCase();

        const matches = name.includes(query) || 
                        dept.includes(query) || 
                        status.includes(query) || 
                        fullText.includes(query);

        if (matches) {
            row.style.display = '';
            totalVisible++;
            if (group) deptMatchCounts[group] = (deptMatchCounts[group] || 0) + 1;
        } else {
            row.style.display = 'none';
        }
    });

    // Show or hide department header rows based on whether their workers match
    deptHeaders.forEach(h => {
        const group = h.getAttribute('data-dept-group');
        if (!group) return;
        if (!query || (deptMatchCounts[group] && deptMatchCounts[group] > 0)) {
            h.style.display = '';
        } else {
            h.style.display = 'none';
        }
    });

    const noMatchRow = document.getElementById('report-no-match-row');
    if (noMatchRow) {
        noMatchRow.style.display = (totalVisible === 0 && rows.length > 0) ? '' : 'none';
        const keywordSpan = document.getElementById('reportSearchKeyword');
        if (keywordSpan) keywordSpan.textContent = query;
    }

    if (grandTotalRow) {
        grandTotalRow.style.display = (totalVisible === 0) ? 'none' : '';
    }

    if (badge) {
        if (!query) {
            badge.textContent = `Total: ${rows.length} employees`;
        } else {
            badge.textContent = `Showing ${totalVisible} of ${rows.length} employees`;
        }
    }
}

function clearReportSearch() {
    const input = document.getElementById('reportSearchInput');
    if (input) {
        input.value = '';
        filterReportsTable();
        input.focus();
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const active = document.activeElement;
        if (active && active.id === 'reportSearchInput') {
            clearReportSearch();
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('reportSearchInput');
    if (input && input.value.trim().length > 0) {
        filterReportsTable();
    }
});
</script>

<style>
/* Remove any sort / filter arrows and click cursor on report headers */
#printable-report table thead th,
#printable-report table thead th.sortable {
  cursor: default !important;
  user-select: text !important;
  pointer-events: none !important;
}

#printable-report table thead th::after,
#printable-report table thead th::before,
#printable-report table thead th.sortable::after,
#printable-report table thead th.sortable::before,
#printable-report table thead th.sorted-asc::after,
#printable-report table thead th.sorted-desc::after {
  display: none !important;
  content: none !important;
}

@media print {
  @page { margin: 10mm; size: auto; }
  body * { visibility: hidden !important; }
  #printable-report, #printable-report * { visibility: visible !important; }
  #printable-report { 
    position: static !important; 
    width: 100% !important; 
    box-shadow: none !important; 
    border: none !important;
    padding: 0 !important;
    background: white !important;
    color: black !important;
  }
  #printable-report table {
    width: 100% !important;
    border-collapse: collapse !important;
  }
  #printable-report tr {
    page-break-inside: avoid;
  }
  .no-print, .filter-bar, .table-pagination-footer { display: none !important; }
  .admin-sidebar, .admin-mobile-header { display: none !important; }
}
</style>
@endsection
