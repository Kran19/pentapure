<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>All Monthly Salary Sheets</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
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
            padding: 4px;
            text-align: center;
        }
        .header-box {
            border: 2px solid #000;
            padding: 8px;
            margin-bottom: 10px;
        }
        .summary-table {
            margin-top: 15px;
            width: 100%;
            border: 2px solid #000;
            border-collapse: collapse;
        }
        .summary-table td {
            border: none;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
        }
        .summary-table .amount {
            text-align: right;
            width: 35%;
            border-left: 1px solid #000;
        }
        .summary-table .row-bottom {
            border-bottom: 1px solid #000;
        }
        .signatures {
            margin-top: 40px;
            width: 100%;
        }
        .signatures td {
            border: none;
            text-align: center;
            font-weight: bold;
            padding-top: 30px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @php
        $logoBase64 = file_exists(public_path('logo.png')) ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('logo.png'))) : null;
    @endphp
    @foreach($allData as $data)
        @php
            extract($data);
        @endphp

        <div style="text-align:center; margin-bottom:15px;">
            <table style="width:100%; border:none; margin-bottom:5px;">
                <tr>
                    <td style="border:none; width:45px; text-align:left; vertical-align:middle; padding:0;">
                        @if($logoBase64)
                            <img src="{{ $logoBase64 }}" style="width: 42px; height: 42px; object-fit: contain;">
                        @endif
                    </td>
                    <td style="border:none; text-align:center; vertical-align:middle; padding:0;">
                        <div style="font-size:9px; font-weight:bold; border:1px solid #000; padding:2px 10px; display:inline-block; margin-bottom:3px;">OFFICIAL RECORD</div>
                        <div style="font-size:16px; font-weight:800; letter-spacing:1px; color:#101828;">PENTAPURE FOOD &amp; SPICES PVT.LTD.</div>
                        <div style="font-size:10px; margin-bottom:3px;">Factory &amp; Warehouse Operations</div>
                        <div style="border-top:2px solid #000; border-bottom:2px solid #000; padding:2px 10px; font-weight:bold; font-size:11px; display:inline-block;">MONTHLY SALARY SHEET</div>
                    </td>
                    <td style="border:none; width:45px; padding:0;"></td>
                </tr>
            </table>
        </div>

        @if($worker->salary_type !== 'LABOUR_MUKADAM')
        <div class="header-box">
            <table style="border:none;">
                <tr>
                    <td style="border:none; text-align:left; padding:0;">
                        <h2 style="margin:0; font-size:14px;">STAFF</h2>
                        <div style="font-size:12px;">NAME: {{ strtoupper($worker->name) }}</div>
                    </td>
                    <td style="border:none; text-align:right; font-size:14px; font-weight:bold; padding:0;">
                        {{ strtoupper(\Carbon\Carbon::parse($month)->format('Y F')) }}
                    </td>
                </tr>
            </table>
        </div>
        @endif

        <table>
            <thead>
                @if($worker->salary_type === 'LABOUR_MUKADAM')
                <tr style="background-color: #f0f0f0;">
                    <th colspan="2" style="border:2px solid #000; text-align:left; font-size:14px; padding:6px;">{{ strtoupper($worker->department->name ?? 'MAKADAM') }}</th>
                    <th colspan="7" style="border:2px solid #000; text-align:left; font-size:14px; padding:6px;">NAME : {{ strtoupper($worker->name) }}</th>
                </tr>
                <tr style="background-color: #f0f0f0;">
                    <th rowspan="2" style="border:2px solid #000; width:50px; font-size:12px;">{{ strtoupper(\Carbon\Carbon::parse($month)->format('Y')) }}<br>{{ strtoupper(\Carbon\Carbon::parse($month)->format('F')) }}</th>
                    <th rowspan="2" style="border:2px solid #000;">PRESENT<br>LABOUR</th>
                    <th colspan="2" style="border:2px solid #000;">DAY SHIFT</th>
                    <th colspan="2" style="border:2px solid #000;">NIGHT SHIFT</th>
                    <th rowspan="2" style="border:2px solid #000; width:60px;">OVER TIME /<br>UNDER TIME</th>
                    <th colspan="2" style="border:2px solid #000;">NO. : </th>
                </tr>
                <tr style="background-color: #f0f0f0;">
                    <th style="border:2px solid #000; border-top:1px solid #000;">IN TIME</th>
                    <th style="border:2px solid #000; border-top:1px solid #000;">OUT TIME</th>
                    <th style="border:2px solid #000; border-top:1px solid #000;">IN TIME</th>
                    <th style="border:2px solid #000; border-top:1px solid #000;">OUT TIME</th>
                    <th style="border:2px solid #000; border-top:1px solid #000; width:45px;">ADVANCE</th>
                    <th style="border:2px solid #000; border-top:1px solid #000; width:45px;">REMARK</th>
                </tr>
                @else
                <tr style="background-color: #f0f0f0;">
                    <th rowspan="2" style="border:2px solid #000; width:50px;">DATE</th>
                    <th rowspan="2" style="border:2px solid #000;">STATUS</th>
                    <th colspan="2" style="border:2px solid #000;">DAY SHIFT</th>
                    <th colspan="2" style="border:2px solid #000;">NIGHT SHIFT</th>
                    <th rowspan="2" style="border:2px solid #000; width:60px;">OVER TIME /<br>UNDER TIME</th>
                    <th rowspan="2" style="border:2px solid #000;">NO.</th>
                </tr>
                <tr style="background-color: #f0f0f0;">
                    <th style="border:2px solid #000; border-top:1px solid #000;">IN TIME</th>
                    <th style="border:2px solid #000; border-top:1px solid #000;">OUT TIME</th>
                    <th style="border:2px solid #000; border-top:1px solid #000;">IN TIME</th>
                    <th style="border:2px solid #000; border-top:1px solid #000;">OUT TIME</th>
                </tr>
                @endif
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
                        <td style="border-left:2px solid #000; font-weight:bold;">
                            {{ $date->format('j') }} ({{ substr($date->format('D'), 0, 3) }})
                        </td>
                        <td style="font-weight:bold; color:{{ $att?->status === 'ABSENT' ? '#d00' : '#000' }};">
                            {{ $att?->status ?? ($isSunday ? 'SUNDAY' : '') }}
                        </td>
                        <td>{{ !$isNight ? $inTime : '' }}</td>
                        <td>{{ !$isNight ? $outTime : '' }}</td>
                        <td>{{ $isNight ? $inTime : '' }}</td>
                        <td>{{ $isNight ? $outTime : '' }}</td>
                        <td style="font-weight:bold; color:{{ ($att?->overtime_hours ?? 0) < 0 ? '#d00' : '#000' }};">
                            @if($att && $att->overtime_hours != 0)
                                {{ $att->overtime_hours > 0 ? '+' : '' }}{{ number_format($att->overtime_hours, 1) }}
                            @endif
                        </td>
                        @if($worker->salary_type === 'LABOUR_MUKADAM')
                        <td style="border-right:1px solid #000; font-size:9px;">
                            {{ $att && $att->advance > 0 ? $att->advance : '' }}
                        </td>
                        <td style="border-right:2px solid #000; font-size:8px;">
                            {{ $att?->remark ?? '' }}
                        </td>
                        @else
                        <td style="border-right:2px solid #000; font-size:8px;">
                            @if($att && $att->advance > 0)
                                Adv: {{ $att->advance }}
                            @endif
                            {{ $att?->remark ?? '' }}
                        </td>
                        @endif
                    </tr>
                @endfor
                <tr><td colspan="{{ $worker->salary_type === 'LABOUR_MUKADAM' ? 9 : 8 }}" style="padding:0; border:none; border-top:2px solid #000;"></td></tr>
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
                <div style="display:inline-block; width:50%; text-align:center;">PETROL / FOODS</div>
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
              <td style="border:2px solid #000; padding:6px; text-align:center; width:17.5%; color:#d00;">{{ number_format($perDaySalary, 2) }}</td>
              <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($attendanceSalary, 2) }}</td>
            </tr>
            <tr>
              <td style="border:2px solid #000; padding:6px; text-align:center;">ADD OT / DEDUCT UT</td>
              <td style="border:2px solid #000; padding:6px; text-align:center;">{{ number_format($totalOT ?? 0, 2) }}</td>
              <td style="border:2px solid #000; padding:6px; text-align:center;">PER HOUR</td>
              <td style="border:2px solid #000; padding:6px; text-align:center; color:#d00;">{{ number_format($hourlyRate, 2) }}</td>
              <td style="border:2px solid #000; padding:6px; text-align:right;">{{ $otUtAdjustment >= 0 ? '' : '' }}{{ number_format($otUtAdjustment, 2) }}</td>
            </tr>
            <tr>
              <td style="border:2px solid #000; padding:6px; text-align:center;">OTHER</td>
              <td colspan="3" style="border:2px solid #000; padding:6px; text-align:center;">PETROL/ FOODS</td>
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
              <td style="border:2px solid #000; padding:6px; text-align:center; width:17.5%; color:#d00;">{{ number_format($perDaySalary, 2) }}</td>
              <td style="border:2px solid #000; padding:6px; text-align:right;">{{ number_format($attendanceSalary, 2) }}</td>
            </tr>
            <!-- Row 3 -->
            <tr>
              <td style="border:2px solid #000; padding:6px; text-align:center;">ADD OT / DEDUCT UT</td>
              <td style="border:2px solid #000; padding:6px; text-align:center;">{{ number_format($totalOT ?? 0, 2) }}</td>
              <td style="border:2px solid #000; padding:6px; text-align:center;">PER HOUR</td>
              <td style="border:2px solid #000; padding:6px; text-align:center; color:#d00;">{{ number_format($hourlyRate, 2) }}</td>
              <td style="border:2px solid #000; padding:6px; text-align:right;">{{ $otUtAdjustment >= 0 ? '' : '' }}{{ number_format($otUtAdjustment, 2) }}</td>
            </tr>
            <!-- Row 4 -->
            <tr>
              <td style="border:2px solid #000; padding:6px; text-align:center;">OTHER</td>
              <td colspan="3" style="border:2px solid #000; padding:6px; text-align:center;">PETROL/ FOODS</td>
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

        <table class="signatures">
            <tr>
                <td>
                    EMPLOYEE<br>
                    <span style="font-weight:normal; font-size:9px;">(NAME)</span>
                </td>
                <td>PREPARED BY</td>
                <td>VERIFIED BY</td>
            </tr>
        </table>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
