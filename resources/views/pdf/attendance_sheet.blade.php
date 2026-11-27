<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Sheet - {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 8mm 12mm 8mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
            color: #101828;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #f8c300;
            padding-bottom: 8px;
        }
        .header .brand-title {
            font-size: 24px;
            font-weight: 800;
            color: #101828;
            margin: 0;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .header .tagline {
            font-size: 11px;
            font-weight: bold;
            color: #b45309;
            margin-top: 2px;
            letter-spacing: 0.8px;
        }
        
        .department-section {
            page-break-inside: auto;
            margin-bottom: 14px;
        }
        .category-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            background-color: #f8fafc;
            border-left: 4px solid #f8c300;
            padding: 4px 8px;
            margin-top: 14px;
            margin-bottom: 6px;
            page-break-after: avoid;
            break-after: avoid;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
            page-break-inside: auto;
        }
        thead {
            display: table-header-group;
        }
        thead tr {
            page-break-inside: avoid;
            break-inside: avoid;
            page-break-after: avoid;
            break-after: avoid;
        }
        tbody tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            text-align: center;
            font-size: 9px;
            vertical-align: middle;
        }
        th {
            background-color: #f1f5f9;
            font-weight: 800;
            color: #1e293b;
            text-transform: uppercase;
            font-size: 8.5px;
        }
        .th-day {
            background-color: #fef3c7;
        }
        .th-night {
            background-color: #e0e7ff;
        }
        .text-left {
            text-align: left;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%; border-collapse: collapse; margin-top: 0; margin-bottom: 6px; border: none;">
            <tr>
                <td style="width: 25%; text-align: left; vertical-align: middle; border: none; background: transparent; padding: 0;">
                    @if(extension_loaded('gd') && file_exists(public_path('logo.png')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 70px; height: 70px; object-fit: contain;">
                    @endif
                </td>
                <td style="width: 50%; text-align: center; vertical-align: middle; border: none; background: transparent; padding: 0;">
                    <div class="brand-title">PPF</div>
                </td>
                <td style="width: 25%; text-align: right; vertical-align: middle; border: none; background: transparent; padding: 0;">
                </td>
            </tr>
        </table>
        <table style="width: 100%; border-collapse: collapse; margin-top: 4px; margin-bottom: 0; border: none;">
            <tr>
                <td style="width: 25%; text-align: left; vertical-align: middle; border: none; background: transparent; padding: 0;"></td>
                <td style="width: 50%; text-align: center; vertical-align: middle; border: none; background: transparent; padding: 0; font-size: 13px; font-weight: 800; letter-spacing: 0.5px;">
                    ATTENDANCE SHEET
                </td>
                <td style="width: 25%; text-align: right; vertical-align: middle; border: none; background: transparent; padding: 0; font-size: 10.5px; font-weight: bold;">
                    DATE: {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}
                </td>
            </tr>
        </table>
    </div>

    @foreach($grouped as $departmentName => $attendances)
        <div class="department-section">
            <div class="category-title">{{ $departmentName }}</div>
            
            @php
                $isMakadam = strtoupper($departmentName) === 'MAKADAM' || strtoupper($departmentName) === 'MUKADAM(LABOUR)' || strtoupper($departmentName) === 'MUKADAM (LABOUR)' || str_contains(strtoupper($departmentName), 'MUKADAM') || $attendances->contains(fn($a) => ($a->worker->salary_type ?? '') === 'LABOUR_MUKADAM');
            @endphp

            <table>
                <thead>
                    <tr>
                        <th style="width: 32px;">S.R.NO</th>
                        <th class="text-left" style="width: 170px;">EMPLOYEE NAME</th>
                        @if($isMakadam)
                            <th style="width: 70px;">LABOUR<br>PRESENT</th>
                        @else
                            <th style="width: 65px;">STATUS</th>
                        @endif
                        <th class="th-day" style="width: 62px;">
                            <div style="font-size: 7.5px; color: #92400e; font-weight: 800; border-bottom: 1px solid #fcd34d; padding-bottom: 1px; margin-bottom: 2px;">DAY SHIFT</div>
                            <div style="font-size: 8.5px; font-weight: bold; color: #78350f;">IN TIME</div>
                        </th>
                        <th class="th-day" style="width: 62px;">
                            <div style="font-size: 7.5px; color: #92400e; font-weight: 800; border-bottom: 1px solid #fcd34d; padding-bottom: 1px; margin-bottom: 2px;">DAY SHIFT</div>
                            <div style="font-size: 8.5px; font-weight: bold; color: #78350f;">OUT TIME</div>
                        </th>
                        <th class="th-night" style="width: 62px;">
                            <div style="font-size: 7.5px; color: #3730a3; font-weight: 800; border-bottom: 1px solid #c7d2fe; padding-bottom: 1px; margin-bottom: 2px;">NIGHT SHIFT</div>
                            <div style="font-size: 8.5px; font-weight: bold; color: #312e81;">IN TIME</div>
                        </th>
                        <th class="th-night" style="width: 62px;">
                            <div style="font-size: 7.5px; color: #3730a3; font-weight: 800; border-bottom: 1px solid #c7d2fe; padding-bottom: 1px; margin-bottom: 2px;">NIGHT SHIFT</div>
                            <div style="font-size: 8.5px; font-weight: bold; color: #312e81;">OUT TIME</div>
                        </th>
                        <th style="width: 55px;">OT/UT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendances as $index => $att)
                        @php
                            $dayIn = '--:--';
                            $dayOut = '--:--';
                            $nightIn = '--:--';
                            $nightOut = '--:--';
                            
                            $isDouble = in_array(strtoupper((string)($att->status ?? '')), ['DOUBLE', 'DOUBLE (OFF)']);
                            $isNight = ($att->shift_type === 'NIGHT');

                            $fIn = $att->in_time ? \Carbon\Carbon::parse($att->in_time)->format('h:i A') : '';
                            $fOut = $att->out_time ? \Carbon\Carbon::parse($att->out_time)->format('h:i A') : '';
                            $fBin = $att->break_in ? \Carbon\Carbon::parse($att->break_in)->format('h:i A') : '';
                            $fBout = $att->break_out ? \Carbon\Carbon::parse($att->break_out)->format('h:i A') : '';

                            if ($isDouble) {
                                if ($isNight) {
                                    $nightIn = $fIn ?: '--:--';
                                    $nightOut = $fOut ?: '--:--';
                                    $dayIn = $fBin ?: ($fOut ?: '--:--');
                                    $dayOut = $fBout ?: ($fIn ?: '--:--');
                                } else {
                                    $dayIn = $fIn ?: '--:--';
                                    $dayOut = $fOut ?: '--:--';
                                    $nightIn = $fBin ?: ($fOut ?: '--:--');
                                    $nightOut = $fBout ?: ($fIn ?: '--:--');
                                }
                            } elseif ($isNight) {
                                $nightIn = $fIn ?: '--:--';
                                $nightOut = $fOut ?: '--:--';
                            } else {
                                $dayIn = $fIn ?: '--:--';
                                $dayOut = $fOut ?: '--:--';
                                $nightIn = $fBin ?: '--:--';
                                $nightOut = $fBout ?: '--:--';
                            }

                            $statusText = $att->status ? strtoupper(str_replace('_', ' ', $att->status)) : 'ABSENT';

                            $isFixedSalary = in_array(strtoupper($att->worker->salary_type ?? ''), ['FIXED_MONTHLY', 'FIX_MONTHLY', 'FIXED', 'FIXED_SALARY']);
                            $otUtDisplay = '';
                            $isUt = false;
                            $isOt = false;

                            if (!$isFixedSalary) {
                                if ($att->ot_ut === 'OT' && $att->ot_ut_hours > 0) {
                                    $otUtDisplay = '+' . (float)$att->ot_ut_hours . ' OT';
                                    $isOt = true;
                                } elseif ($att->ot_ut === 'UT' && $att->ot_ut_hours > 0) {
                                    $otUtDisplay = '-' . (float)$att->ot_ut_hours . ' UT';
                                    $isUt = true;
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="text-left">{{ $att->worker->name ?? 'N/A' }}</td>
                            @if($isMakadam)
                                <td style="font-weight: bold; color: {{ $statusText === 'ABSENT' && empty($att->num_workers) ? '#d92d20' : ($att->num_workers ? '#027a48' : '#101828') }};">
                                    {{ $att->num_workers ?: ($statusText === 'ABSENT' ? 'ABSENT' : '--') }}
                                </td>
                            @else
                                <td style="color: {{ $statusText === 'ABSENT' ? '#d92d20' : '#027a48' }}; font-weight: bold;">
                                    {{ $statusText }}
                                </td>
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
        </div>
    @endforeach

</body>
</html>
