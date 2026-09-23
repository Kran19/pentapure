<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Payroll Summary Report - {{ \Carbon\Carbon::parse($month)->format('F Y') }}</title>
    <style>
        @page {
            margin: 12mm 15mm 12mm 15mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5px;
            margin: 0;
            padding: 0;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background-color: #f1f5f9;
            color: #0f172a;
            border: 1px solid #64748b;
            padding: 6px 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        td {
            border: 1px solid #94a3b8;
            padding: 5px 6px;
            vertical-align: middle;
        }
        .dept-row {
            background-color: #f8fafc;
            font-weight: bold;
        }
        .dept-row td {
            border-top: 2px solid #334155;
            border-bottom: 2px solid #334155;
            color: #1e293b;
            font-size: 9.5px;
            padding: 6px;
        }
        .grand-total-row td {
            background-color: #f1f5f9;
            border-top: 2.5px solid #0f172a;
            border-bottom: 2.5px solid #0f172a;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-paid {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 8px;
            display: inline-block;
        }
        .badge-unpaid {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 8px;
            display: inline-block;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table style="width:100%; border:none; margin-bottom:12px;">
        <tr>
            <td style="border:none; width:70px; text-align:left; vertical-align:middle; padding:0;">
                @if(file_exists(public_path('logo.png')))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 42px; height: 42px; object-fit: contain;">
                @endif
            </td>
            <td style="border:none; text-align:center; vertical-align:middle; padding:0;">
                <div style="font-size:16px; font-weight:800; letter-spacing:1px; color:#0f172a; text-transform:uppercase;">PENTAPURE FOOD &amp; SPICES PVT.LTD.</div>
                <div style="font-size:10px; color:#475569; font-weight:bold; margin-top:2px;">MONTHLY ATTENDANCE &amp; PAYROLL SUMMARY REPORT</div>
                <div style="font-size:11px; color:#000; font-weight:bold; margin-top:2px; text-transform:uppercase;">PERIOD: {{ \Carbon\Carbon::parse($month)->format('F Y') }}</div>
            </td>
            <td style="border:none; width:130px; text-align:right; vertical-align:middle; padding:0;">
                <div style="font-size:8.5px; color:#475569;">Generated On: {{ date('d-m-Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    @php
        $grandTotal = 0;
        $grouped = [];
        foreach($reportData as $d) {
            $dept = $d['worker']->department->name ?? 'Other';
            $grouped[$dept][] = $d;
        }
    @endphp

    <!-- Main Summary Table -->
    <table>
        <thead>
            <tr>
                <th style="width:5%; text-align:center;">#</th>
                <th style="width:25%; text-align:left;">EMPLOYEE NAME</th>
                <th style="width:15%; text-align:left;">DEPARTMENT</th>
                <th style="width:14%; text-align:left;">SALARY</th>
                <th style="width:10%; text-align:center;">PRESENT</th>
                <th style="width:10%; text-align:center;">TOTAL OT</th>
                <th style="width:13%; text-align:right;">PAYABLE (₹)</th>
                <th style="width:8%; text-align:center;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grouped as $deptName => $workers)
                @php
                    $deptTotalPresent = array_sum(array_column($workers, 'present'));
                    $deptTotalOt = array_sum(array_column($workers, 'total_ot'));
                    $deptTotalWage = array_sum(array_column($workers, 'total_wage'));
                    $deptWorkerCount = count($workers);
                @endphp
                <tr class="dept-row">
                    <td colspan="4" style="text-align:left;">
                        DEPARTMENT: {{ strtoupper($deptName) }} <span style="font-size:8.5px; font-weight:normal; opacity:0.85;">(Workers: {{ $deptWorkerCount }})</span>
                    </td>
                    <td style="text-align:center;">{{ $deptTotalPresent > 0 ? (floor($deptTotalPresent) == $deptTotalPresent ? number_format($deptTotalPresent, 0) : number_format($deptTotalPresent, 1)) : 0 }}</td>
                    <td style="text-align:center;">{{ number_format($deptTotalOt, 1) }}</td>
                    <td style="text-align:right;">₹{{ number_format($deptTotalWage, 2) }}</td>
                    <td></td>
                </tr>

                @foreach($workers as $data)
                    @php
                        $grandTotal += $data['total_wage'];
                        $adj = $data['adjustment'] ?? null;
                        $isPaid = (bool)($adj?->is_paid ?? false);
                        $rawDate = $adj?->paid_at ? \Carbon\Carbon::parse($adj->paid_at)->format('Y-m-d') : ($adj?->paid_note && preg_match('/^\d{4}-\d{2}-\d{2}$/', $adj->paid_note) ? $adj->paid_note : '');
                        $paidDate = $isPaid ? ($rawDate ?: date('Y-m-d')) : $rawDate;
                    @endphp
                    <tr>
                        <td style="text-align:center; color:#64748b; font-weight:bold;">{{ $data['worker_number'] ?? '-' }}</td>
                        <td style="font-weight:bold; color:#0f172a;">{{ strtoupper($data['worker']->name) }}</td>
                        <td style="color:#334155;">{{ strtoupper($data['worker']->department->name ?? '-') }}</td>
                        <td>
                            <div style="font-weight:bold; color:#0f172a;">₹{{ number_format($data['worker']->salary_amount, 0) }}</div>
                            <div style="font-size:7.5px; color:#64748b;">{{ strtoupper(str_replace('_', ' ', $data['worker']->salary_type ?? '')) }}</div>
                        </td>
                        <td style="text-align:center; font-weight:bold; color:#0f172a;">{{ $data['present'] }}</td>
                        <td style="text-align:center; font-weight:bold; color:#0f172a;">{{ number_format($data['total_ot'], 1) }}</td>
                        <td style="text-align:right; font-weight:bold; color:#0f172a;">₹{{ number_format($data['total_wage'], 2) }}</td>
                        <td style="text-align:center;">
                            @if($isPaid)
                                <span class="badge-paid">PAID</span>
                            @else
                                <span class="badge-unpaid">UNPAID</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endforeach

            <tr class="grand-total-row">
                <td colspan="4" style="text-align:right; text-transform:uppercase;">Grand Total Payroll Liability:</td>
                <td colspan="2"></td>
                <td style="text-align:right; color:#0f172a;">₹{{ number_format($grandTotal, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Signature Footer -->
    <table style="width:100%; border:none; margin-top:25px;">
        <tr>
            <td style="border:none; text-align:center; width:33%;">
                <div style="border-top:1.5px solid #000; width:80%; margin:0 auto; padding-top:4px; font-weight:bold; font-size:9px;">PREPARED BY</div>
            </td>
            <td style="border:none; text-align:center; width:33%;">
                <div style="border-top:1.5px solid #000; width:80%; margin:0 auto; padding-top:4px; font-weight:bold; font-size:9px;">CHECKED &amp; VERIFIED BY</div>
            </td>
            <td style="border:none; text-align:center; width:34%;">
                <div style="border-top:1.5px solid #000; width:80%; margin:0 auto; padding-top:4px; font-weight:bold; font-size:9px;">AUTHORIZED SIGNATORY</div>
            </td>
        </tr>
    </table>

</body>
</html>
