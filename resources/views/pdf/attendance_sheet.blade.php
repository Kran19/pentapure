<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Sheet - {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0;
            padding: 0;
            text-transform: uppercase;
        }
        .subheader {
            margin-top: 10px;
            font-size: 14px;
            font-weight: bold;
            display: table;
            width: 100%;
        }
        .subheader > div {
            display: table-cell;
        }
        .date-section {
            text-align: right;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-transform: uppercase;
        }
        .text-left {
            text-align: left;
        }
        .category-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 5px;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ $companyName }}</h1>
        <div class="subheader">
            <div class="text-left">ATTENDANCE SHEET</div>
            <div class="date-section">DATE: {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</div>
        </div>
    </div>

    @foreach($grouped as $departmentName => $attendances)
        <div class="category-title">{{ $departmentName }}</div>
        
        @php
            $isMakadam = strtoupper($departmentName) === 'MAKADAM';
        @endphp

        <table>
            <thead>
                @if($isMakadam)
                    <tr>
                        <th style="width: 40px;">S.R.NO</th>
                        <th class="text-left">{{ $departmentName }}</th>
                        <th style="width: 60px;">LABOUR</th>
                        <th style="width: 80px;">BOILER O.T.</th>
                        <th style="width: 80px;">IN TIME</th>
                        <th style="width: 80px;">OUT TIME</th>
                        <th style="width: 80px;">TOTAL O.T.</th>
                    </tr>
                @else
                    <tr>
                        <th style="width: 40px;">S.R.NO</th>
                        <th class="text-left">{{ $departmentName }}</th>
                        <th style="width: 65px;">IN TIME</th>
                        <th style="width: 65px;">OUT TIME</th>
                        <th style="width: 50px;">O.T.</th>
                        <th style="width: 65px;">IN TIME</th>
                        <th style="width: 65px;">OUT TIME</th>
                        <th style="width: 50px;">O.T.</th>
                    </tr>
                @endif
            </thead>
            <tbody>
                @foreach($attendances as $index => $att)
                    @if($isMakadam)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="text-left">{{ $att->worker->name }}</td>
                            <td>{{ $att->num_workers ?: '' }}</td>
                            <td>{{ $att->overtime_hours > 0 ? '+' . (float)$att->overtime_hours : '' }}</td>
                            <td>{{ $att->in_time ? \Carbon\Carbon::parse($att->in_time)->format('h:i A') : '' }}</td>
                            <td>{{ $att->out_time ? \Carbon\Carbon::parse($att->out_time)->format('h:i A') : '' }}</td>
                            <td>{{ $att->ot_ut_hours > 0 ? '+' . (float)$att->ot_ut_hours : '' }}</td>
                        </tr>
                    @else
                        @php
                            $dayIn = '';
                            $dayOut = '';
                            $nightIn = '';
                            $nightOut = '';
                            
                            if ($att->shift_type === 'NIGHT') {
                                $nightIn = $att->in_time ? \Carbon\Carbon::parse($att->in_time)->format('h:i A') : '';
                                $nightOut = $att->out_time ? \Carbon\Carbon::parse($att->out_time)->format('h:i A') : '';
                            } else {
                                $dayIn = $att->in_time ? \Carbon\Carbon::parse($att->in_time)->format('h:i A') : '';
                                $dayOut = $att->out_time ? \Carbon\Carbon::parse($att->out_time)->format('h:i A') : '';
                                $nightIn = $att->break_in ? \Carbon\Carbon::parse($att->break_in)->format('h:i A') : '';
                                $nightOut = $att->break_out ? \Carbon\Carbon::parse($att->break_out)->format('h:i A') : '';
                            }
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="text-left">{{ $att->worker->name }}</td>
                            <td>{{ $dayIn }}</td>
                            <td>{{ $dayOut }}</td>
                            <td>{{ $att->overtime_hours > 0 ? '+' . (float)$att->overtime_hours : '' }}</td>
                            <td>{{ $nightIn }}</td>
                            <td>{{ $nightOut }}</td>
                            <td>{{ $att->ot_ut_hours > 0 ? '+' . (float)$att->ot_ut_hours : '' }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endforeach

</body>
</html>
