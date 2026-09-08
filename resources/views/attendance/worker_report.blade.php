@extends($layout)

@section('content')
<div style="padding:0.5rem;">
  <div class="flex-between mb-1 no-print" style="flex-wrap:wrap; gap:10px;">
    <h2 style="margin:0;">📅 Monthly Attendance Sheet</h2>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-sm btn-secondary" onclick="window.history.back()">Back</button>
        @if(str_contains(request()->path(), 'admin'))
            <a href="{{ url(str_replace('reports', 'reports', request()->path()) . '/pdf' . '?month=' . $month) }}" class="btn btn-sm" style="background:#e74c3c; color:white; text-decoration:none;">📄 Download PDF</a>
        @else
            <a href="{{ url(request()->path() . '/pdf' . '?month=' . $month) }}" class="btn btn-sm" style="background:#e74c3c; color:white; text-decoration:none;">📄 Download PDF</a>
        @endif
        <button class="btn btn-sm" onclick="exportToExcel()" style="background:#2ecc71;">📊 Export to Excel</button>
    </div>
  </div>

  <div class="card" id="printable-sheet" style="padding:0.5rem; background:white; color:black; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; border:1px solid #ddd;">
    
    <div style="margin-bottom:1rem; border:2px solid #000; padding:8px 12px;">
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight:bold; font-size:1.1rem; text-transform:uppercase;">
          {{ strtoupper($worker->department->name ?? 'GENERAL') }}
        </div>
        <div style="font-weight:bold; font-size:0.95rem; text-align:center;">
          NAME: {{ strtoupper($worker->name) }} 
          @if(!empty($workerNumber)) 
            <span style="margin-left:10px; background:#eee; padding:2px 8px; border:1px solid #999; border-radius:3px;">EMP NO: {{ $workerNumber }}</span> 
          @endif
        </div>
        <div style="font-weight:bold; font-size:1.1rem; text-align:right; display:flex; align-items:center; justify-content:flex-end; gap:12px;">
          <div style="display:flex; flex-direction:column; align-items:flex-end;">
            @if(!empty($adjustment) && $adjustment->is_paid)
              <span style="background:#27ae60; color:#fff; padding:3px 10px; border-radius:4px; font-size:0.85rem; font-weight:bold; text-transform:uppercase;">
                ✓ PAID
              </span>
            @else
              <span style="background:#e74c3c; color:#fff; padding:3px 10px; border-radius:4px; font-size:0.85rem; font-weight:bold; text-transform:uppercase;">
                ✕ UNPAID
              </span>
            @endif
            @if(!empty($adjustment->paid_note))
              <small style="font-size:0.75rem; color:#444; font-weight:normal; margin-top:2px; font-style:italic;">Note: {{ $adjustment->paid_note }}</small>
            @endif
          </div>
          <span>{{ strtoupper(\Carbon\Carbon::parse($month)->format('Y F')) }}</span>
        </div>
      </div>
    </div>

    <!-- Detailed Ledger Table -->
    <div class="table-scroll">
      <table style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr style="background:#f0f0f0;">
            <th rowspan="2" style="border:2px solid #000; padding:6px; font-size:0.78rem; width:80px;">DATE</th>
            <th rowspan="2" style="border:2px solid #000; padding:6px; font-size:0.78rem; width:95px;">{{ $worker->salary_type === 'LABOUR_MUKADAM' ? 'PRESENT LABOUR' : 'STATUS' }}</th>
            <th colspan="2" style="border:2px solid #000; padding:6px; font-size:0.78rem;">DAY SHIFT</th>
            <th colspan="2" style="border:2px solid #000; padding:6px; font-size:0.78rem;">NIGHT SHIFT</th>
            <th rowspan="2" style="border:2px solid #000; padding:6px; font-size:0.72rem; width:95px;">OVER TIME /<br>UNDER TIME</th>
            <th rowspan="2" style="border:2px solid #000; padding:6px; font-size:0.78rem; width:90px;">ADVANCE</th>
            <th rowspan="2" style="border:2px solid #000; padding:6px; font-size:0.78rem;">REMARK</th>
          </tr>
          <tr style="background:#f0f0f0;">
            <th style="border:2px solid #000; border-top:1px solid #000; padding:6px; font-size:0.75rem; width:90px;">IN TIME</th>
            <th style="border:2px solid #000; border-top:1px solid #000; padding:6px; font-size:0.75rem; width:90px;">OUT TIME</th>
            <th style="border:2px solid #000; border-top:1px solid #000; padding:6px; font-size:0.75rem; width:90px;">IN TIME</th>
            <th style="border:2px solid #000; border-top:1px solid #000; padding:6px; font-size:0.75rem; width:90px;">OUT TIME</th>
          </tr>
        </thead>
        <tbody>
          @for($date = $start->copy(); $date->lte($end); $date->addDay())
            @php 
                $dStr = $date->toDateString();
                $att = $attendances->get($dStr);
                $isSunday = $date->isSunday();
                $isNight = $att && $att->shift_type === 'NIGHT';
                
                $inTime = $att?->in_time ? date('h:i A', strtotime($att->in_time)) : '';
                $outTime = $att?->out_time ? date('h:i A', strtotime($att->out_time)) : '';
            @endphp
            <tr style="border-bottom:1px solid #000; {{ $isSunday ? 'background:#fff8f8;' : '' }}">
              @if($worker->salary_type === 'LABOUR_MUKADAM')
              <td style="border:1px solid #000; border-left:2px solid #000; padding:3px; text-align:center; font-size:0.8rem; font-weight:600;">
                {{ $date->format('j') }} ({{ substr($date->format('D'), 0, 3) }})
              </td>
              <td style="border:1px solid #000; padding:3px; text-align:center; font-weight:bold; font-size:0.75rem; color:{{ $att?->status === 'ABSENT' ? '#d00' : '#000' }};">
                {{ $att?->num_workers ?? '' }}
              </td>
              <!-- DAY SHIFT -->
              <td style="border:1px solid #000; padding:3px; text-align:center; font-size:0.85rem;">{{ !$isNight ? $inTime : '' }}</td>
              <td style="border:1px solid #000; padding:3px; text-align:center; font-size:0.85rem;">{{ !$isNight ? $outTime : '' }}</td>
              
              <!-- NIGHT SHIFT -->
              <td style="border:1px solid #000; padding:3px; text-align:center; font-size:0.85rem;">{{ $isNight ? $inTime : '' }}</td>
              <td style="border:1px solid #000; padding:3px; text-align:center; font-size:0.85rem;">{{ $isNight ? $outTime : '' }}</td>
              
              <!-- OT / UT -->
              <td style="border:1px solid #000; padding:3px; text-align:center; font-size:0.85rem; font-weight:bold; color: {{ ($att?->overtime_hours ?? 0) < 0 ? '#d00' : 'inherit' }};">
                @if($att && $att->overtime_hours != 0)
                  {{ $att->overtime_hours > 0 ? '+' : '' }}{{ number_format($att->overtime_hours, 1) }}
                @endif
              </td>
              <!-- ADVANCE -->
              <td style="border:1px solid #000; padding:3px; font-size:0.75rem; text-align:center;">
                {{ $att?->advance ?? '' }}
              </td>
              <!-- REMARK -->
              <td style="border:1px solid #000; border-right:2px solid #000; padding:3px; font-size:0.75rem; text-align:center;">
                {{ $att?->remark ?? '' }}
              </td>
              @else
              <td style="border:1px solid #000; border-left:2px solid #000; padding:3px 6px; text-align:left; font-size:0.8rem; font-weight:600; white-space:nowrap;">
                <span style="display:inline-block; width:22px; text-align:right;">{{ $date->format('j') }}</span> ({{ substr($date->format('D'), 0, 3) }})
              </td>
              <td style="border:1px solid #000; padding:3px; text-align:center; font-weight:bold; font-size:0.75rem; color:{{ $att?->status === 'PRESENT' ? '#000' : ($att?->status === 'ABSENT' ? '#d00' : '#000') }};">
                {{ $att?->status ?? '' }}
              </td>
              <!-- DAY SHIFT -->
              <td style="border:1px solid #000; padding:3px; text-align:center; font-size:0.8rem; white-space:nowrap;">{{ !$isNight ? $inTime : '' }}</td>
              <td style="border:1px solid #000; padding:3px; text-align:center; font-size:0.8rem; white-space:nowrap;">{{ !$isNight ? $outTime : '' }}</td>
              
              <!-- NIGHT SHIFT -->
              <td style="border:1px solid #000; padding:3px; text-align:center; font-size:0.8rem; white-space:nowrap;">{{ $isNight ? $inTime : '' }}</td>
              <td style="border:1px solid #000; padding:3px; text-align:center; font-size:0.8rem; white-space:nowrap;">{{ $isNight ? $outTime : '' }}</td>
              
              <!-- OT / UT -->
              <td style="border:1px solid #000; padding:3px; text-align:center; font-size:0.8rem; font-weight:bold; color: {{ ($att?->overtime_hours ?? 0) < 0 ? '#d00' : 'inherit' }};">
                @if($att && $att->overtime_hours != 0)
                  {{ $att->overtime_hours > 0 ? '+' : '' }}{{ number_format($att->overtime_hours, 1) }}
                @endif
              </td>
              
              <!-- ADVANCE -->
              <td style="border:1px solid #000; padding:3px; font-size:0.75rem; text-align:center;">
                {{ $att?->advance > 0 ? $att->advance : '' }}
              </td>
              <!-- REMARK -->
              <td style="border:1px solid #000; border-right:2px solid #000; padding:3px 6px; font-size:0.75rem; text-align:left;">
                {{ $att?->remark ?? '' }}
              </td>
              @endif
            </tr>
          @endfor
        </tbody>
      </table>
    </div>

    <!-- Salary Summary -->
    <div style="margin-top:20px; width:100%;">
      @if($worker->salary_type === 'FIXED_MONTHLY')
      <table style="width:100%; border:2px solid #000; border-collapse:collapse; font-size:0.95rem; font-weight:bold;">
        <tr>
          <td style="border:2px solid #000; padding:8px; width:75%;">FIX MONTHLY SALARY</td>
          <td style="border:2px solid #000; padding:8px; text-align:right; width:25%;">{{ number_format($worker->salary_amount, 0) }}</td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:8px;">
            <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                <span style="color:#000;">OTHER</span>
                <div style="display:inline-flex; align-items:center; gap:8px;">
                    <span class="no-print">
                        <input type="text" class="allowance-label-input" value="{{ $adjustment->other_allowance_label ?? 'PETROL / FOODS' }}" 
                               oninput="syncAllowanceLabel(this.value)"
                               onchange="quickSaveAllowance(this.value, null)"
                               style="background:#fffbeb; border:1px solid #fde047; border-radius:12px; padding:3px 10px; font-weight:bold; font-size:0.85rem; text-align:right; text-transform:uppercase; width:160px; color:#0f172a; outline:none;"
                               title="Click to edit allowance label">
                    </span>
                    <span class="allowance-label-display" style="text-transform:uppercase;">{{ strtoupper($adjustment->other_allowance_label ?? 'PETROL / FOODS') }}</span>
                </div>
            </div>
          </td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">
            <div style="display:inline-flex; align-items:center; justify-content:flex-end; gap:8px; width:100%;">
                <span class="no-print">
                    ✏️
                    <input type="number" step="0.01" class="allowance-amount-input" value="{{ $adjustment->petrol_food_amount }}" 
                           oninput="syncAllowanceAmount(this.value)"
                           onchange="quickSaveAllowance(null, this.value)"
                           style="background:#fffbeb; border:1px solid #fde047; border-radius:12px; padding:3px 10px; font-weight:bold; font-size:0.9rem; text-align:right; width:90px; color:#0f172a; outline:none;"
                           title="Click to edit allowance amount">
                </span>
                <span class="allowance-amount-display">{{ number_format($adjustment->petrol_food_amount, 0) }}</span>
            </div>
          </td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:8px;">TOTAL SALARY</td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">{{ number_format($totalWage, 0) }}</td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:8px;">ADVANCE</td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">{{ number_format($totalAdvance, 0) }}</td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:8px;">PAYABLE SALARY</td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">{{ number_format($payableSalary, 0) }}</td>
        </tr>
      </table>
      @elseif($worker->salary_type === 'LABOUR_MUKADAM')
      <table style="width:100%; border:2px solid #000; border-collapse:collapse; font-size:0.95rem; font-weight:bold;">
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:8px; text-align:center;">PER LABOUR SALARY</td>
          <td style="border:2px solid #000; padding:8px; text-align:right; width:25%;">{{ number_format($worker->salary_amount, 2) }}</td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:8px; text-align:center; width:25%;">TOTAL LABOUR</td>
          <td style="border:2px solid #000; padding:8px; text-align:center; width:12.5%;">{{ number_format($presentDays, 1) }}</td>
          <td style="border:2px solid #000; padding:8px; text-align:center; width:20%;">PER LABOUR</td>
          <td style="border:2px solid #000; padding:8px; text-align:center; width:17.5%; color:#d00;">{{ number_format($perDaySalary, 2) }}</td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">{{ number_format($attendanceSalary, 0) }}</td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:8px; text-align:center;">ADD OT / DEDUCT UT</td>
          <td style="border:2px solid #000; padding:8px; text-align:center;">{{ number_format($totalOT ?? 0, 1) }}</td>
          <td style="border:2px solid #000; padding:8px; text-align:center;">PER HOUR</td>
          <td style="border:2px solid #000; padding:8px; text-align:center; color:#d00;">{{ number_format($hourlyRate, 2) }}</td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">{{ number_format($otUtAdjustment, 0) }}</td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:6px 8px; text-align:center;">OTHER</td>
          <td colspan="3" style="border:2px solid #000; padding:6px 8px; text-align:right;">
            <div style="display:inline-flex; align-items:center; justify-content:flex-end; gap:8px; width:100%;">
                <span class="no-print">
                    <input type="text" class="allowance-label-input" value="{{ $adjustment->other_allowance_label ?? 'PETROL / FOODS' }}" 
                           oninput="syncAllowanceLabel(this.value)"
                           onchange="quickSaveAllowance(this.value, null)"
                           style="background:#fffbeb; border:1px solid #fde047; border-radius:12px; padding:3px 10px; font-weight:bold; font-size:0.85rem; text-align:right; text-transform:uppercase; width:160px; color:#0f172a; outline:none;"
                           title="Click to edit allowance label">
                </span>
                <span class="allowance-label-display" style="text-transform:uppercase;">{{ strtoupper($adjustment->other_allowance_label ?? 'PETROL / FOODS') }}</span>
            </div>
          </td>
          <td style="border:2px solid #000; padding:6px 8px; text-align:right;">
            <div style="display:inline-flex; align-items:center; justify-content:flex-end; gap:8px; width:100%;">
                <span class="no-print">
                    ✏️
                    <input type="number" step="0.01" class="allowance-amount-input" value="{{ $adjustment->petrol_food_amount }}" 
                           oninput="syncAllowanceAmount(this.value)"
                           onchange="quickSaveAllowance(null, this.value)"
                           style="background:#fffbeb; border:1px solid #fde047; border-radius:12px; padding:3px 10px; font-weight:bold; font-size:0.9rem; text-align:right; width:90px; color:#0f172a; outline:none;"
                           title="Click to edit allowance amount">
                </span>
                <span class="allowance-amount-display">{{ number_format($adjustment->petrol_food_amount, 0) }}</span>
            </div>
          </td>
        </tr>
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:8px; text-align:center;">TOTAL SALARY</td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">{{ number_format($totalWage, 0) }}</td>
        </tr>
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:8px; text-align:center;">ADVANCE</td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">{{ number_format($totalAdvance, 0) }}</td>
        </tr>
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:8px; text-align:center;">PAYABLE SALARY</td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">{{ number_format($payableSalary, 0) }}</td>
        </tr>
      </table>
      @else
      <table style="width:100%; border:2px solid #000; border-collapse:collapse; font-size:0.95rem; font-weight:bold;">
        <!-- Row 1 -->
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:8px; text-align:center;">{{ $worker->salary_type === 'DAILY' ? 'PER DAY SALARY' : 'MONTHLY SALARY' }}</td>
          <td style="border:2px solid #000; padding:8px; text-align:right; width:25%;">{{ number_format($worker->salary_type === 'DAILY' ? $worker->salary_amount : ($worker->salary_type === 'MONTHLY' || $worker->salary_type === 'FIXED_MONTHLY' ? $worker->salary_amount : ($worker->daily_salary * 30)), 2) }}</td>
        </tr>
        <!-- Row 2 -->
        <tr>
          <td style="border:2px solid #000; padding:8px; text-align:center; width:25%;">TOTAL ATTENDENCE</td>
          <td style="border:2px solid #000; padding:8px; text-align:center; width:12.5%;">{{ number_format($presentDays, 1) }}</td>
          <td style="border:2px solid #000; padding:8px; text-align:center; width:20%;">PER DAY</td>
          <td style="border:2px solid #000; padding:8px; text-align:center; width:17.5%; color:#d00;">{{ number_format($perDaySalary, 2) }}</td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">{{ number_format($attendanceSalary, 0) }}</td>
        </tr>
        <!-- Row 3 -->
        <tr>
          <td style="border:2px solid #000; padding:8px; text-align:center;">ADD OT / DEDUCT UT</td>
          <td style="border:2px solid #000; padding:8px; text-align:center;">{{ number_format($totalOT ?? 0, 1) }}</td>
          <td style="border:2px solid #000; padding:8px; text-align:center;">PER HOUR</td>
          <td style="border:2px solid #000; padding:8px; text-align:center; color:#d00;">{{ number_format($hourlyRate, 2) }}</td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">{{ number_format($otUtAdjustment, 0) }}</td>
        </tr>
        <!-- Row 4 -->
        <tr>
          <td style="border:2px solid #000; padding:6px 8px; text-align:center;">OTHER</td>
          <td colspan="3" style="border:2px solid #000; padding:6px 8px; text-align:right;">
            <div style="display:inline-flex; align-items:center; justify-content:flex-end; gap:8px; width:100%;">
                <span class="no-print">
                    <input type="text" class="allowance-label-input" value="{{ $adjustment->other_allowance_label ?? 'PETROL / FOODS' }}" 
                           oninput="syncAllowanceLabel(this.value)"
                           onchange="quickSaveAllowance(this.value, null)"
                           style="background:#fffbeb; border:1px solid #fde047; border-radius:12px; padding:3px 10px; font-weight:bold; font-size:0.85rem; text-align:right; text-transform:uppercase; width:160px; color:#0f172a; outline:none;"
                           title="Click to edit allowance label">
                </span>
                <span class="allowance-label-display" style="text-transform:uppercase;">{{ strtoupper($adjustment->other_allowance_label ?? 'PETROL / FOODS') }}</span>
            </div>
          </td>
          <td style="border:2px solid #000; padding:6px 8px; text-align:right;">
            <div style="display:inline-flex; align-items:center; justify-content:flex-end; gap:8px; width:100%;">
                <span class="no-print">
                    ✏️
                    <input type="number" step="0.01" class="allowance-amount-input" value="{{ $adjustment->petrol_food_amount }}" 
                           oninput="syncAllowanceAmount(this.value)"
                           onchange="quickSaveAllowance(null, this.value)"
                           style="background:#fffbeb; border:1px solid #fde047; border-radius:12px; padding:3px 10px; font-weight:bold; font-size:0.9rem; text-align:right; width:90px; color:#0f172a; outline:none;"
                           title="Click to edit allowance amount">
                </span>
                <span class="allowance-amount-display">{{ number_format($adjustment->petrol_food_amount, 0) }}</span>
            </div>
          </td>
        </tr>
        <!-- Row 5 -->
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:8px; text-align:center;">TOTAL SALARY</td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">{{ number_format($totalWage, 0) }}</td>
        </tr>
        <!-- Row 6 -->
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:8px; text-align:center;">ADVANCE</td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">{{ number_format($totalAdvance, 0) }}</td>
        </tr>
        <!-- Row 7 -->
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:8px; text-align:center;">PAYABLE SALARY</td>
          <td style="border:2px solid #000; padding:8px; text-align:right;">{{ number_format($payableSalary, 0) }}</td>
        </tr>
      </table>
      @endif
    </div>

    <!-- Official Authorization / Signatures -->
    <div style="margin-top:1.5rem; margin-bottom:0.5rem; display:grid; grid-template-columns: repeat(3, 1fr); gap:30px; text-align:center;">
      <div>
        <div style="height:35px; font-size:0.82rem; font-weight:bold; color:#000; display:flex; align-items:flex-end; justify-content:center;">{{ strtoupper($worker->name) }}</div>
        <div style="border-top:1.5px solid #000; width:85%; margin:4px auto;"></div>
        <div style="font-size:0.7rem; color:#000; font-weight:bold; margin-top:1px;">(EMPLOYEE SIGN)</div>
      </div>
      <div>
        <div style="height:35px; font-size:0.82rem; font-weight:bold; color:#000; display:flex; align-items:flex-end; justify-content:center;">{{ strtoupper(session('auth_user')['name'] ?? 'MANAGER') }}</div>
        <div style="border-top:1.5px solid #000; width:85%; margin:4px auto;"></div>
        <div style="font-size:0.7rem; color:#000; font-weight:bold; margin-top:1px;">(PREPARED BY)</div>
      </div>
      <div>
        <div style="height:35px;"></div>
        <div style="border-top:1.5px solid #000; width:85%; margin:4px auto;"></div>
        <div style="font-size:0.82rem; font-weight:bold; color:#000;">VERIFIED BY</div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Adjustment Modal -->
<div id="editAdjustmentModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
    <div style="background-color:#fefefe; margin:10% auto; padding:20px; border:1px solid #888; width:90%; max-width:500px; border-radius:8px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3 style="margin:0; color:#000;">Edit Adjustments - {{ \Carbon\Carbon::parse($month)->format('M Y') }}</h3>
            <span onclick="document.getElementById('editAdjustmentModal').style.display='none'" style="cursor:pointer; font-size:1.5rem; color:#aaa;">&times;</span>
        </div>
        <form method="POST" action="{{ str_contains(request()->path(), 'admin') ? url(str_replace('reports', 'reports', request()->path()) . '/adjust') : url(request()->path() . '/adjust') }}">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <div class="form-group mb-3">
                <label style="color:#000; font-weight:bold;">Allowance / Expense Name</label>
                <input type="text" name="other_allowance_label" class="form-control" value="{{ $adjustment->other_allowance_label ?? 'PETROL / FOODS' }}" placeholder="e.g. PETROL / FOODS, INCENTIVE" required>
            </div>
            <div class="form-group mb-3">
                <label style="color:#000; font-weight:bold;">Allowance Amount (₹)</label>
                <input type="number" step="0.01" name="petrol_food_amount" class="form-control" value="{{ $adjustment->petrol_food_amount }}" required>
            </div>
            <div class="form-group mb-3">
                <label style="color:#000; font-weight:bold;">Advance Taken (₹)</label>
                <input type="number" step="0.01" name="advance" class="form-control" value="{{ $adjustment->advance }}" required>
            </div>
            <div class="form-group mb-4">
                <label style="color:#000; font-weight:bold;">Remarks</label>
                <input type="text" name="remark" class="form-control" value="{{ $adjustment->remark }}">
            </div>
            <div style="text-align:right;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editAdjustmentModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function syncAllowanceLabel(val) {
    document.querySelectorAll('.allowance-label-display').forEach(el => {
        el.innerText = (val || 'PETROL / FOODS').toUpperCase();
    });
    document.querySelectorAll('.allowance-label-input').forEach(el => {
        if (el !== document.activeElement) {
            el.value = val;
        }
    });
}

function syncAllowanceAmount(val) {
    document.querySelectorAll('.allowance-amount-display').forEach(el => {
        const num = parseFloat(val);
        el.innerText = isNaN(num) ? '0' : num.toLocaleString('en-US');
    });
    document.querySelectorAll('.allowance-amount-input').forEach(el => {
        if (el !== document.activeElement) {
            el.value = val;
        }
    });
}

function exportToExcel() {
    let table = document.querySelector("#printable-sheet table");
    let rows = Array.from(table.rows);
    let csv = rows.map(row => {
        let cells = Array.from(row.cells);
        return cells.map(cell => `"${cell.innerText.replace(/"/g, '""').trim()}"`).join(",");
    }).join("\n");
    
    let blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    let link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.setAttribute("download", "{{ $worker->name }}_Attendance_{{ $month }}.csv");
    link.click();
}

function quickSaveAllowance(label, amount) {
    const postUrl = "{{ str_contains(request()->path(), 'admin') ? url(str_replace('reports', 'reports', request()->path()) . '/adjust') : url(request()->path() . '/adjust') }}";
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('month', '{{ $month }}');
    
    // Pick values from input elements if not passed explicitly
    if (label !== null) {
        formData.append('other_allowance_label', label);
    } else {
        const modalLabel = document.querySelector('input[name="other_allowance_label"]');
        formData.append('other_allowance_label', modalLabel ? modalLabel.value : 'PETROL / FOODS');
    }

    if (amount !== null) {
        formData.append('petrol_food_amount', amount);
    } else {
        const modalAmount = document.querySelector('input[name="petrol_food_amount"]');
        formData.append('petrol_food_amount', modalAmount ? modalAmount.value : 0);
    }

    const modalAdvance = document.querySelector('input[name="advance"]');
    const modalRemark = document.querySelector('input[name="remark"]');
    formData.append('advance', modalAdvance ? modalAdvance.value : '{{ $adjustment->advance }}');
    formData.append('remark', modalRemark ? modalRemark.value : '{{ $adjustment->remark }}');

    fetch(postUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(res => {
        if (res.ok) {
            window.location.reload();
        }
    }).catch(err => console.error('Quick save error:', err));
}
</script>

<style>
#printable-sheet {
    background: white !important;
    color: black !important;
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0.5rem !important;
    box-shadow: none;
    border-radius: 0;
    overflow-x: hidden;
}
.table-scroll {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
#printable-sheet table {
    width: 100%; 
    min-width: 800px;
    border-collapse: collapse;
}
</style>
@endsection
