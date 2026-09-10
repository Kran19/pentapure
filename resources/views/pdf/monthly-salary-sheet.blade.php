<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Salary Sheet</title>
    <style>
        @page {
            margin: 10px 15px 10px 15px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8px;
            margin: 0;
            padding: 0;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 3.5px 4px;
            text-align: center;
        }
        .header-box {
            border: 2px solid #000;
            padding: 4px 8px;
            margin-bottom: 6px;
        }
        .summary-table {
            margin-top: 14px;
            width: 100%;
            border: 2px solid #000;
            border-collapse: collapse;
        }
        .summary-table td {
            border: none;
            padding: 4px 6px;
            text-align: left;
            font-weight: bold;
        }
        .signatures {
            position: absolute;
            bottom: 65px;
            left: 0;
            right: 0;
            width: 100%;
        }
        .signatures td {
            border: none;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div style="text-align:center; margin-bottom:8px;">
        <table style="width:100%; border:none; margin-bottom:3px;">
            <tr>
                <td style="border:none; width:75px; text-align:left; vertical-align:middle; padding:0;">
                    @if(file_exists(public_path('logo.png')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 36px; height: 36px; object-fit: contain;">
                    @endif
                </td>
                <td style="border:none; text-align:center; vertical-align:middle; padding:0;">
                    <div style="font-size:8px; font-weight:bold; border:1px solid #000; padding:1px 8px; display:inline-block; margin-bottom:2px;">OFFICIAL RECORD</div>
                    <div style="font-size:14px; font-weight:800; letter-spacing:1px; color:#101828;">PENTAPURE FOOD &amp; SPICES PVT.LTD.</div>
                    <div style="font-size:9px; margin-bottom:2px;">Factory &amp; Warehouse Operations</div>
                    <div style="border-top:1.5px solid #000; border-bottom:1.5px solid #000; padding:1px 8px; font-weight:bold; font-size:10px; display:inline-block;">MONTHLY SALARY SHEET</div>
                </td>
                <td style="border:none; width:130px; text-align:right; vertical-align:top; padding:0;">
                    @if(!empty($adjustment) && $adjustment->is_paid)
                        <span style="background:#27ae60; color:#ffffff; padding:4px 10px; border-radius:3px; font-size:10px; font-weight:bold; display:inline-block; text-transform:uppercase;">PAID</span>
                    @else
                        <span style="background:#e74c3c; color:#ffffff; padding:4px 10px; border-radius:3px; font-size:10px; font-weight:bold; display:inline-block; text-transform:uppercase;">UNPAID</span>
                    @endif
                    @if(!empty($adjustment->paid_note))
                        @php
                          try {
                            $formattedPaidNote = \Carbon\Carbon::parse($adjustment->paid_note)->format('d-m-Y');
                          } catch (\Throwable $e) {
                            $formattedPaidNote = $adjustment->paid_note;
                          }
                        @endphp
                        <div style="font-size:8px; color:#222; margin-top:3px; font-style:italic; font-weight:bold;">
                            Note: {{ $formattedPaidNote }}
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="header-box">
        <table style="border:none; width:100%;">
            <tr>
                <td style="border:none; text-align:left; vertical-align:middle; width:30%; padding:0;">
                    <strong style="font-size:11px; text-transform:uppercase;">{{ strtoupper($worker->department->name ?? 'MAKADAM') }}</strong>
                </td>
                <td style="border:none; text-align:center; vertical-align:middle; width:45%; padding:0; font-size:10px; font-weight:bold;">
                    @if(!empty($workerNumber))
                        <span style="margin-right:8px; border:1px solid #777; padding:1px 6px; border-radius:3px; background:#f5f5f5;">EMP NO: {{ $workerNumber }}</span>
                    @endif
                    NAME: {{ strtoupper($worker->name) }}
                </td>
                <td style="border:none; text-align:right; vertical-align:middle; width:25%; padding:0; font-size:11px; font-weight:bold;">
                    {{ strtoupper(\Carbon\Carbon::parse($month)->format('Y F')) }}
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th rowspan="2" style="border:2px solid #000; width:8%; font-size:8.5px;">DATE</th>
                <th rowspan="2" style="border:2px solid #000; width:10%; font-size:8.5px;">{{ $worker->salary_type === 'LABOUR_MUKADAM' ? 'PRESENT LABOUR' : 'STATUS' }}</th>
                <th colspan="2" style="border:2px solid #000; width:21%; font-size:8.5px;">DAY SHIFT</th>
                <th colspan="2" style="border:2px solid #000; width:21%; font-size:8.5px;">NIGHT SHIFT</th>
                <th rowspan="2" style="border:2px solid #000; width:10%; font-size:7.5px;">OVER TIME /<br>UNDER TIME</th>
                <th rowspan="2" style="border:2px solid #000; width:10%; font-size:8.5px;">ADVANCE</th>
                <th rowspan="2" style="border:2px solid #000; width:20%; font-size:8.5px;">REMARK</th>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <th style="border:2px solid #000; border-top:1px solid #000; width:10.5%; font-size:7px;">IN TIME</th>
                <th style="border:2px solid #000; border-top:1px solid #000; width:10.5%; font-size:7px;">OUT TIME</th>
                <th style="border:2px solid #000; border-top:1px solid #000; width:10.5%; font-size:7px;">IN TIME</th>
                <th style="border:2px solid #000; border-top:1px solid #000; width:10.5%; font-size:7px;">OUT TIME</th>
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
                <tr style="{{ $isSunday ? 'background-color:#fff8f8;' : '' }}">
                    <td style="border-left:2px solid #000; font-weight:bold; text-align:center; white-space:nowrap; font-size:8px;">
                        {{ $date->format('j') }} ({{ substr($date->format('D'), 0, 3) }})
                    </td>
                    <td style="font-weight:bold; font-size:7.5px; color:{{ $att?->status === 'ABSENT' ? '#d00' : '#000' }};">
                        {{ $worker->salary_type === 'LABOUR_MUKADAM' ? ($att?->num_workers ?? '') : ($att?->status ?? '') }}
                    </td>
                    <td style="font-size:7.5px; white-space:nowrap;">{{ !$isNight ? $inTime : '' }}</td>
                    <td style="font-size:7.5px; white-space:nowrap;">{{ !$isNight ? $outTime : '' }}</td>
                    <td style="font-size:7.5px; white-space:nowrap;">{{ $isNight ? $inTime : '' }}</td>
                    <td style="font-size:7.5px; white-space:nowrap;">{{ $isNight ? $outTime : '' }}</td>
                    <td style="font-weight:bold; font-size:7.5px; color:{{ ($att?->overtime_hours ?? 0) < 0 ? '#d00' : '#000' }};">
                        @if($att && $att->overtime_hours != 0)
                            {{ $att->overtime_hours > 0 ? '+' : '' }}{{ number_format($att->overtime_hours, 1) }}
                        @endif
                    </td>
                    <td style="font-size:7.5px; text-align:center;">
                        {{ $att && $att->advance > 0 ? $att->advance : '' }}
                    </td>
                    <td style="border-right:2px solid #000; font-size:7px; text-align:left; padding-left:3px;">
                        {{ $att?->remark ?? '' }}
                    </td>
                </tr>
            @endfor
            <tr><td colspan="9" style="padding:0; border:none; border-top:2px solid #000;"></td></tr>
        </tbody>
    </table>

      @if($worker->salary_type === 'FIXED_MONTHLY')
      <table class="summary-table" style="width:100%; border:2px solid #000; border-collapse:collapse; font-size:12px; font-weight:bold;">
        <tr>
          <td style="border:2px solid #000; padding:6px; width:75%;">FIX MONTHLY SALARY</td>
          <td style="border:2px solid #000; padding:6px; text-align:right; width:25%;">{{ number_format($worker->salary_amount, 2) }}</td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:6px;">
            <div style="display:inline-block; width:45%; color:#000;">OTHER</div>
            <div style="display:inline-block; width:50%; text-align:center;">{{ strtoupper($adjustment->other_allowance_label ?? 'PETROL / FOODS') }}</div>
          </td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">
            {{ $adjustment->petrol_food_amount > 0 ? '+' : '' }}{{ number_format($adjustment->petrol_food_amount, 2) }}
          </td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:6px;">TOTAL SALARY</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($totalWage, 2) }}</td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:6px;">ADVANCE</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($totalAdvance, 2) }}</td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:6px;">PAYABLE SALARY</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($payableSalary, 2) }}</td>
        </tr>
      </table>
      @elseif($worker->salary_type === 'LABOUR_MUKADAM')
      <table class="summary-table" style="width:100%; border:2px solid #000; border-collapse:collapse; font-size:12px; font-weight:bold;">
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:6px; text-align:center;">PER LABOUR SALARY</td>
          <td style="border:2px solid #000; padding:6px; text-align:right; width:25%;">{{ number_format($worker->salary_amount, 2) }}</td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:6px; text-align:center; width:25%;">TOTAL LABOUR</td>
          <td style="border:2px solid #000; padding:6px; text-align:center; width:12.5%;">{{ number_format($presentDays, 2) }}</td>
          <td style="border:2px solid #000; padding:6px; text-align:center; width:20%;">PER LABOUR</td>
          <td style="border:2px solid #000; padding:6px; text-align:center; width:17.5%;">{{ number_format($perDaySalary, 2) }}</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($attendanceSalary, 2) }}</td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:6px; text-align:center;">ADD OT / DEDUCT UT</td>
          <td style="border:2px solid #000; padding:6px; text-align:center;">{{ number_format($totalOT ?? 0, 2) }}</td>
          <td style="border:2px solid #000; padding:6px; text-align:center;">PER HOUR</td>
          <td style="border:2px solid #000; padding:6px; text-align:center;">{{ number_format($hourlyRate, 2) }}</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ $otUtAdjustment >= 0 ? '' : '' }}{{ number_format($otUtAdjustment, 2) }}</td>
        </tr>
        <tr>
          <td style="border:2px solid #000; padding:6px; text-align:center;">OTHER</td>
          <td colspan="3" style="border:2px solid #000; padding:6px; text-align:center;">{{ strtoupper($adjustment->other_allowance_label ?? 'PETROL / FOODS') }}</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ $adjustment->petrol_food_amount > 0 ? '+' : '' }}{{ number_format($adjustment->petrol_food_amount, 2) }}</td>
        </tr>
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:6px; text-align:center;">TOTAL SALARY</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($totalWage, 2) }}</td>
        </tr>
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:6px; text-align:center;">ADVANCE</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($totalAdvance, 2) }}</td>
        </tr>
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:6px; text-align:center;">PAYABLE SALARY</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($payableSalary, 2) }}</td>
        </tr>
      </table>
      @else
    <table class="summary-table" style="width:100%; border:2px solid #000; border-collapse:collapse; font-size:12px; font-weight:bold;">
        <!-- Row 1 -->
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:6px; text-align:center;">{{ $worker->salary_type === 'DAILY' ? 'PER DAY SALARY' : 'MONTHLY SALARY' }}</td>
          <td style="border:2px solid #000; padding:6px; text-align:right; width:25%;">{{ number_format($worker->salary_type === 'DAILY' ? $worker->salary_amount : ($worker->salary_type === 'MONTHLY' || $worker->salary_type === 'FIXED_MONTHLY' ? $worker->salary_amount : ($worker->daily_salary * 30)), 2) }}</td>
        </tr>
        <!-- Row 2 -->
        <tr>
          <td style="border:2px solid #000; padding:6px; text-align:center; width:25%;">TOTAL ATTENDENCE</td>
          <td style="border:2px solid #000; padding:6px; text-align:center; width:12.5%;">{{ number_format($presentDays, 2) }}</td>
          <td style="border:2px solid #000; padding:6px; text-align:center; width:20%;">PER DAY</td>
          <td style="border:2px solid #000; padding:6px; text-align:center; width:17.5%;">{{ number_format($perDaySalary, 2) }}</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($attendanceSalary, 2) }}</td>
        </tr>
        <!-- Row 3 -->
        <tr>
          <td style="border:2px solid #000; padding:6px; text-align:center;">ADD OT / DEDUCT UT</td>
          <td style="border:2px solid #000; padding:6px; text-align:center;">{{ number_format($totalOT ?? 0, 2) }}</td>
          <td style="border:2px solid #000; padding:6px; text-align:center;">PER HOUR</td>
          <td style="border:2px solid #000; padding:6px; text-align:center;">{{ number_format($hourlyRate, 2) }}</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ $otUtAdjustment >= 0 ? '' : '' }}{{ number_format($otUtAdjustment, 2) }}</td>
        </tr>
        <!-- Row 4 -->
        <tr>
          <td style="border:2px solid #000; padding:6px; text-align:center;">OTHER</td>
          <td colspan="3" style="border:2px solid #000; padding:6px; text-align:center;">{{ strtoupper($adjustment->other_allowance_label ?? 'PETROL / FOODS') }}</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($adjustment->petrol_food_amount, 2) }}</td>
        </tr>
        <!-- Row 5 -->
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:6px; text-align:center;">TOTAL SALARY</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($totalWage, 2) }}</td>
        </tr>
        <!-- Row 6 -->
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:6px; text-align:center;">ADVANCE</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($totalAdvance, 2) }}</td>
        </tr>
        <!-- Row 7 -->
        <tr>
          <td colspan="4" style="border:2px solid #000; padding:6px; text-align:center;">PAYABLE SALARY</td>
          <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($payableSalary, 2) }}</td>
        </tr>
    </table>
    @endif

    <table class="signatures" style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="border:none; text-align:center; padding:0 10px; vertical-align:bottom; width:33.33%;">
                <div style="font-weight:bold; font-size:11px; color:#000; margin-bottom:2px;">{{ strtoupper($worker->name) }}</div>
                <div style="border-bottom:1px solid #000; width:85%; margin:0 auto;"></div>
                <div style="font-size:9px; font-weight:bold; color:#000; margin-top:3px;">(EMPLOYEE SIGN)</div>
            </td>
            <td style="border:none; text-align:center; padding:0 10px; vertical-align:bottom; width:33.33%;">
                <div style="font-weight:bold; font-size:11px; color:#000; margin-bottom:2px;">{{ strtoupper(session('auth_user')['name'] ?? 'MANAGER') }}</div>
                <div style="border-bottom:1px solid #000; width:85%; margin:0 auto;"></div>
                <div style="font-size:9px; font-weight:bold; color:#000; margin-top:3px;">(PREPARED BY)</div>
            </td>
            <td style="border:none; text-align:center; padding:0 10px; vertical-align:bottom; width:33.33%;">
                <div style="font-weight:bold; font-size:11px; color:#000; margin-bottom:2px;">&nbsp;</div>
                <div style="border-bottom:1px solid #000; width:85%; margin:0 auto;"></div>
                <div style="font-size:9px; font-weight:bold; color:#000; margin-top:3px;">VERIFIED BY</div>
            </td>
        </tr>
    </table>

</body>
</html>
