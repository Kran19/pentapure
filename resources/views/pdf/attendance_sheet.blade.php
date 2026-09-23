<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Sheet - {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</title>
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
            margin-bottom: 15px;
            border-bottom: 2px solid #f8c300;
            padding-bottom: 10px;
        }
        .header .brand-title {
            font-size: 22px;
            font-weight: bold;
            color: #101828;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .header .tagline {
            font-size: 13px;
            font-weight: bold;
            color: #101828;
            margin-top: 2px;
        }
        .subheader {
            margin-top: 10px;
            font-size: 13px;
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
        <table style="width: 100%; border-collapse: collapse; margin-top: 0; margin-bottom: 8px; border: none;">
            <tr>
                <td style="width: 20%; text-align: left; vertical-align: middle; border: none; background: transparent; padding: 0;">
                    @if(file_exists(public_path('logo.png')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 48px; height: 48px; object-fit: contain;">
                    @endif
                </td>
                <td style="width: 60%; text-align: center; vertical-align: middle; border: none; background: transparent; padding: 0;">
                    <div class="brand-title">PentaPure</div>
                    <div class="tagline">FOOD &amp; SPICES PVT.LTD.</div>
                </td>
                <td style="width: 20%; text-align: right; vertical-align: middle; border: none; background: transparent; padding: 0;">
                    @if(file_exists(public_path('logo.png')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 48px; height: 48px; object-fit: contain;">
                    @endif
                </td>
            </tr>
        </table>
        <div class="subheader">
            <div class="text-left">ATTENDANCE SHEET</div>
            <div class="date-section">DATE: {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</div>
        </div>
    </div>

    @foreach($grouped as $departmentName => $attendances)
        <div class="category-title">{{ $departmentName }}</div>
        
        @php
            $isMakadam = strtoupper($departmentName) === 'MAKADAM' || strtoupper($departmentName) === 'MUKADAM(LABOUR)' || strtoupper($departmentName) === 'MUKADAM (LABOUR)' || str_contains(strtoupper($departmentName), 'MUKADAM');
        @endphp

        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 30px; vertical-align: middle;">S.R.NO</th>
                    <th rowspan="2" class="text-left" style="vertical-align: middle;">EMPLOYEE NAME</th>
                    <th rowspan="2" style="width: 60px; vertical-align: middle;">STATUS</th>
                    @if($isMakadam)
                        <th rowspan="2" style="width: 45px; vertical-align: middle;">LABOUR</th>
                    @endif
                    <th colspan="2">DAY SHIFT</th>
                    <th colspan="2">NIGHT SHIFT</th>
                    <th rowspan="2" style="width: 55px; vertical-align: middle;">OT/UT</th>
                </tr>
                <tr>
                    <th style="width: 65px;">IN TIME</th>
                    <th style="width: 65px;">OUT TIME</th>
                    <th style="width: 65px;">IN TIME</th>
                    <th style="width: 65px;">OUT TIME</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $index => $att)
                    @php
                        $dayIn = '--:--';
                        $dayOut = '--:--';
                        $nightIn = '--:--';
                        $nightOut = '--:--';
                        
                        if ($att->shift_type === 'NIGHT') {
                            $nightIn = $att->in_time ? \Carbon\Carbon::parse($att->in_time)->format('h:i A') : '--:--';
                            $nightOut = $att->out_time ? \Carbon\Carbon::parse($att->out_time)->format('h:i A') : '--:--';
                        } else {
                            $dayIn = $att->in_time ? \Carbon\Carbon::parse($att->in_time)->format('h:i A') : '--:--';
                            $dayOut = $att->out_time ? \Carbon\Carbon::parse($att->out_time)->format('h:i A') : '--:--';
                            $nightIn = $att->break_in ? \Carbon\Carbon::parse($att->break_in)->format('h:i A') : '--:--';
                            $nightOut = $att->break_out ? \Carbon\Carbon::parse($att->break_out)->format('h:i A') : '--:--';
                        }

                        $statusText = $att->status ? strtoupper(str_replace('_', ' ', $att->status)) : 'ABSENT';

                        $otUtDisplay = '';
                        $isUt = false;
                        $isOt = false;

                        if ($att->ot_ut === 'OT' && $att->ot_ut_hours > 0) {
                            $otUtDisplay = '+' . (float)$att->ot_ut_hours . ' OT';
                            $isOt = true;
                        } elseif ($att->ot_ut === 'UT' && $att->ot_ut_hours > 0) {
                            $otUtDisplay = '-' . (float)$att->ot_ut_hours . ' UT';
                            $isUt = true;
                        }
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="text-left">{{ $att->worker->name ?? 'N/A' }}</td>
                        <td style="color: {{ $statusText === 'ABSENT' ? '#d92d20' : '#027a48' }}; font-weight: bold;">
                            {{ $statusText }}
                        </td>
                        @if($isMakadam)
                            <td>{{ $att->num_workers ?: '--' }}</td>
                        @endif
                        <td>{{ $dayIn }}</td>
                        <td>{{ $dayOut }}</td>
                        <td>{{ $nightIn }}</td>
                        <td>{{ $nightOut }}</td>
                        <td style="{{ $isUt ? 'background-color: #f8d7da; color: #721c24; font-weight: bold;' : ($isOt ? 'color: #027a48; font-weight: bold;' : '') }}">
                            {{ $otUtDisplay }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

</body>
</html>
