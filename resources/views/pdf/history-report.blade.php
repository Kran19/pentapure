<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PentaPure - {{ $panel }} History Report</title>
    <style>
        @page { margin: 18px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #101828; font-size: 13px; line-height: 1.45; text-transform: uppercase; }
        .page { border: 1px solid #1f2937; padding: 42px 46px 24px; min-height: 1060px; border-bottom: 7px solid #f8c300; }
        .top { display: table; width: 100%; padding-bottom: 22px; border-bottom: 1px solid #667085; }
        .brand, .contact { display: table-cell; vertical-align: top; width: 50%; }
        .contact { text-align: left; padding-left: 110px; font-size: 14px; }
        .contact div { margin-bottom: 9px; }
        .logo-mark {
            width: 82px;
            height: 82px;
            border: 5px solid #f8c300;
            border-radius: 50%;
            display: inline-block;
            vertical-align: middle;
            margin-right: 12px;
            background: #2b241c;
            color: #ffffff;
            text-align: center;
            line-height: 72px;
            font-size: 23px;
            font-weight: 800;
        }
        .brand-text { display: inline-block; vertical-align: middle; }
        .brand-title { font-size: 34px; font-weight: 800; color: #111827; }
        .tagline { font-size: 14px; margin-top: 2px; color: #111827; }
        .title { text-align: center; margin: 34px 0 26px; font-size: 32px; font-weight: 800; letter-spacing: 1px; color: #111827; }
        .meta { display: table; width: 100%; margin-bottom: 34px; font-size: 16px; }
        .meta-col { display: table-cell; width: 50%; }
        .meta-row { margin-bottom: 11px; }
        .label { display: inline-block; min-width: 112px; font-weight: 600; }
        .colon { display: inline-block; width: 20px; text-align: center; }
        .section-label { background: #f8c300; color: #101828; display: inline-block; padding: 8px 28px; border-radius: 4px; font-weight: 700; text-transform: uppercase; font-size: 15px; }
        .box { border: 1px solid #9db5d8; border-radius: 5px; padding: 28px 30px; margin-top: -1px; margin-bottom: 30px; }
        .details { display: table; width: 100%; }
        .details-col { display: table-cell; width: 50%; vertical-align: top; }
        .divider { border-left: 1px solid #d0d5dd; padding-left: 34px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f8c300; color: #101828; padding: 10px 6px; border: 1px solid #667085; font-size: 12px; }
        td { padding: 10px 6px; border: 1px solid #d9dee7; vertical-align: top; font-size: 12px; }
        td.center, th.center { text-align: center; }
        td.amount { text-align: right; font-weight: 700; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 5px; font-weight: 700; font-size: 12px; }
        .badge-ok { background: #e8f7e8; color: #1f7a1f; }
        .badge-warn { background: #fff3e0; color: #c76a00; }
        .summary { display: table; width: 100%; }
        .summary-item { display: table-cell; text-align: center; border-right: 1px solid #d0d5dd; width: 20%; }
        .summary-item:last-child { border-right: 0; }
        .summary-number { font-size: 22px; font-weight: 800; }
        .summary-label { font-size: 12px; }
        .footer { margin-top: 38px; padding-top: 26px; border-top: 1px solid #98a2b3; display: table; width: 100%; }
        .notes, .sign { display: table-cell; width: 50%; vertical-align: bottom; }
        .notes-title { color: #f8c300; font-weight: 800; margin-bottom: 12px; text-shadow: 0.5px 0.5px 0 #000; }
        .sign { text-align: center; padding-left: 130px; }
        .scribble { font-family: DejaVu Sans, sans-serif; font-size: 28px; margin-bottom: 6px; }
        .line { border-top: 1px solid #667085; margin: 0 auto 8px; width: 210px; }
        .text-green { color: #1f7a1f; font-weight: bold; }
        .text-red { color: #c76a00; font-weight: bold; }
    </style>
</head>
<body>
<div class="page">
    <div class="top">
        <div class="brand">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 82px; height: 82px; vertical-align: middle; margin-right: 12px; object-fit: contain;">
            <div class="brand-text">
                <div class="brand-title">PentaPure</div>
                <div class="tagline">FOOD &amp; SPICES PVT.LTD.</div>
            </div>
        </div>
        <div class="contact">
            <div>info@pentapure.com</div>
            <div>+91 98765 43210</div>
            <div>www.pentapure.com</div>
        </div>
    </div>

    <div class="title">{{ $isAttendance ? 'ATTENDANCE & PAYROLL REPORT' : 'HISTORY REPORT' }}</div>

    <div class="meta">
        <div class="meta-col">
            <div class="meta-row"><span class="label">Report ID</span><span class="colon">:</span>{{ $reportId }}</div>
            <div class="meta-row"><span class="label">Generated On</span><span class="colon">:</span>{{ $generatedOn }}</div>
        </div>
        <div class="meta-col">
            <div class="meta-row"><span class="label">From Date</span><span class="colon">:</span>{{ $fromDate }}</div>
            <div class="meta-row"><span class="label">To Date</span><span class="colon">:</span>{{ $toDate }}</div>
        </div>
    </div>

    <div class="section-label">{{ $panel }} Details</div>
    <div class="box">
        <div class="details">
            <div class="details-col">
                <div class="meta-row"><span class="label">Name</span><span class="colon">:</span>{{ $userName }}</div>
                <div class="meta-row"><span class="label">Role</span><span class="colon">:</span>{{ $userRole }}</div>
            </div>
            <div class="details-col divider">
                <div class="meta-row"><span class="label">Company</span><span class="colon">:</span>PentaPure</div>
                <div class="meta-row"><span class="label">Report Type</span><span class="colon">:</span>{{ $panel }} History</div>
            </div>
        </div>
    </div>

    @if($isAttendance)
        <div style="text-align:center; margin-bottom:24px;">
            <div style="font-size:24px; font-weight:800;">PENTAPURE FACTORY</div>
            <div style="font-size:15px; color:#475467;">Attendance & Payroll Report - {{ \Carbon\Carbon::parse($fromDate)->format('F Y') }}</div>
        </div>

        <div class="section-label">Attendance Details</div>
        <table>
            <thead>
                <tr>
                    <th style="width:20%;">Employee Name</th>
                    <th style="width:14%;">Department</th>
                    <th style="width:12%;">Salary</th>
                    <th class="center" style="width:9%;">Present</th>
                    <th class="center" style="width:10%;">Half Days</th>
                    <th class="center" style="width:9%;">Absent</th>
                    <th class="center" style="width:11%;">Total OT Hrs</th>
                    <th style="width:15%;">Total Payable (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['employee'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>
                            {{ number_format((float) $row['salary'], 2) }}<br>
                            <span style="font-size:10px; color:#667085;">{{ $row['salaryType'] }}</span>
                        </td>
                        <td class="center">{{ $row['present'] }}</td>
                        <td class="center">{{ $row['half'] }}</td>
                        <td class="center">{{ $row['absent'] }}</td>
                        <td class="center">{{ number_format((float) $row['ot'], 1) }}</td>
                        <td class="amount">{{ number_format((float) $row['payable'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="center">No attendance records found for this month.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif(strtoupper($panel) === 'RAW')
        <div class="section-label">Raw Material History</div>
        <table>
            <thead>
                <tr>
                    <th style="width:25%;">Product Name</th>
                    <th style="width:10%;">Type</th>
                    <th style="width:15%;">Quantity</th>
                    <th style="width:20%;">Date</th>
                    <th style="width:30%;">Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr>
                        <td><strong>{{ $row['product_name'] ?? '-' }}</strong></td>
                        <td class="center">
                            @if(($row['transaction_type'] ?? '') === 'IN')
                                <span class="badge" style="background:#d3d3d3de; color:#2ecc71; min-width: 55px; display: inline-block; text-align: center;">IN</span>
                            @else
                                <span class="badge" style="background:#d3d3d3de; color:red; min-width: 55px; display: inline-block; text-align: center;">OUT</span>
                            @endif
                        </td>
                        <td class="amount" style="color:{{ ($row['transaction_type'] ?? '') === 'IN' ? '#2ecc71' : 'red' }}; font-weight:bold;">
                            {{ ($row['transaction_type'] ?? '') === 'IN' ? '+' : '-' }}{{ number_format((float) ($row['quantity'] ?? 0), 2) }} <span style="font-size:10px; color:#667085;">{{ $row['unit'] ?? 'kg' }}</span>
                        </td>
                        <td>{{ $row['date'] }}</td>
                        <td style="font-size: 11px;">{{ $row['notes'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="center">No raw material history found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif(strtoupper($panel) === 'SEMI' || strtoupper($panel) === 'FINISHED')
        <div class="section-label">Production History ({{ $panel }})</div>
        <table>
            <thead>
                <tr>
                    <th class="center" style="width:6%;">#</th>
                    <th style="width:14%;">Batch ID</th>
                    <th style="width:14%;">Date</th>
                    <th style="width:25%;">Produced Item & Grade</th>
                    <th style="width:15%;">Produced Qty</th>
                    <th>Inputs Consumed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $row['id'] }}</td>
                        <td>{{ $row['date'] }}</td>
                        <td>
                            <strong>{{ $row['output_product'] ?? '-' }}</strong><br>
                            <span style="font-size: 11px; color: #475467;">Grade: {{ $row['output_grade'] ?? '-' }}</span>
                        </td>
                        <td class="amount text-green">+{{ number_format((float) ($row['output_qty'] ?? 0), 2) }} <span style="font-size:10px; color:#667085;">{{ $row['unit'] ?? 'kg' }}</span></td>
                        <td style="font-size: 11px; line-height: 1.6;">
                            @if(!empty($row['inputs']))
                                @foreach($row['inputs'] as $input)
                                    <div>&bull; {{ $input['name'] ?? '-' }} ({{ $input['grade'] ?? '-' }}): <span class="text-red">-{{ number_format((float) ($input['quantity'] ?? 0), 2) }} kg</span></div>
                                @endforeach
                            @else
                                <span style="color: #667085; font-style: italic;">No inputs recorded</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="center">No production history found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif(strtoupper($panel) === 'SALES')
        <div class="section-label">Sales / Order History</div>
        <table>
            <thead>
                <tr>
                    <th class="center" style="width:6%;">#</th>
                    <th style="width:14%;">Order ID</th>
                    <th style="width:14%;">Date</th>
                    <th style="width:20%;">Customer</th>
                    <th style="width:14%;">Status</th>
                    <th style="width:17%;">Order Items (Qty)</th>
                    <th style="width:15%;">Order Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $row['id'] }}</td>
                        <td>{{ $row['date'] }}</td>
                        <td><strong>{{ $row['company_name'] ?? '-' }}</strong></td>
                        <td>
                            <div style="margin-bottom: 4px;">
                                <span class="badge {{ in_array($row['status'] ?? '', ['PENDING', 'OPEN']) ? 'badge-warn' : 'badge-ok' }}">{{ $row['status'] ?? '-' }}</span>
                            </div>
                            <div style="font-size: 10px; color: #475467;">Dispatch: {{ $row['dispatch_status'] ?? '-' }}</div>
                        </td>
                        <td class="center">
                            <strong>{{ $row['total_items'] ?? 0 }}</strong> Items<br>
                            <span style="font-size: 10px; color: #667085;">Total {{ number_format((float) ($row['total_qty'] ?? 0), 2) }} KG</span>
                        </td>
                        <td class="amount">₹{{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="center">No sales history found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif(strtoupper($panel) === 'CASHIER')
        <div class="section-label">Cashier History</div>
        <table>
            <thead>
                <tr>
                    <th class="center" style="width:6%;">#</th>
                    <th style="width:14%;">TXN ID</th>
                    <th style="width:14%;">Date</th>
                    <th style="width:16%;">Category</th>
                    <th style="width:20%;">Note / Reference</th>
                    <th style="width:15%; text-align: right;">Income (+)</th>
                    <th style="width:15%; text-align: right;">Expense (-)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $row['id'] }}</td>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ strtoupper(str_replace('_', ' ', $row['category'] ?? '-')) }}</td>
                        <td style="font-size: 11px;">
                            <strong>{{ $row['note'] ?? '-' }}</strong>
                            @if(!empty($row['reference']))
                                <br><span style="color: #667085;">Ref: {{ $row['reference'] }}</span>
                            @endif
                        </td>
                        @if(($row['type'] ?? '') === 'Income' || (isset($row['amount']) && $row['amount'] >= 0))
                            <td class="amount text-green">₹{{ number_format(abs((float) ($row['amount'] ?? 0)), 2) }}</td>
                            <td class="amount">-</td>
                        @else
                            <td class="amount">-</td>
                            <td class="amount text-red">₹{{ number_format(abs((float) ($row['amount'] ?? 0)), 2) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="center">No cashier history found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <div class="section-label">History Details</div>
        <table>
            <thead>
                <tr>
                    <th class="center" style="width:6%;">#</th>
                    <th style="width:14%;">ID</th>
                    <th style="width:15%;">Type</th>
                    <th style="width:14%;">Date</th>
                    <th style="width:14%;">Status</th>
                    <th style="width:15%;">Amount (Rs.)</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $row['id'] }}</td>
                        <td>{{ $row['type'] ?? '-' }}</td>
                        <td>{{ $row['date'] }}</td>
                        <td class="center">
                            <span class="badge {{ in_array($row['status'] ?? '', ['PENDING', 'OPEN', 'ABSENT']) ? 'badge-warn' : 'badge-ok' }}">{{ $row['status'] ?? '-' }}</span>
                        </td>
                        <td class="amount">{{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                        <td>
                            {{ $row['description'] ?? '-' }}
                            @if(isset($row['lr_copy']) && $row['lr_copy'])
                                <div style="margin-top: 8px;">
                                @if(file_exists(public_path($row['lr_copy'])))
                                    <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path($row['lr_copy']))) }}" style="max-width: 140px; max-height: 100px; border: 1px solid #d0d5dd; border-radius: 4px; object-fit: contain;">
                                @endif                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="center">No history found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if(!empty($purchaseOrders))
        <div class="section-label" style="margin-top: 30px;">Purchase Request History</div>
        <table>
            <thead>
                <tr>
                    <th class="center" style="width:6%;">#</th>
                    <th style="width:14%;">PO ID</th>
                    <th style="width:14%;">Date</th>
                    <th style="width:30%;">Material</th>
                    <th style="width:16%;">Quantity</th>
                    <th style="width:20%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrders as $index => $po)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $po['id'] }}</td>
                        <td>{{ $po['date'] }}</td>
                        <td><strong>{{ $po['material'] }}</strong></td>
                        <td class="amount">{{ number_format((float) $po['quantity'], 2) }} kg</td>
                        <td class="center">
                            <span class="badge {{ $po['status'] === 'PENDING' ? 'badge-warn' : 'badge-ok' }}">
                                {{ $po['status'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-label">Summary</div>
    <div class="box">
        <div class="summary">
            <div class="summary-item"><div class="summary-number">{{ $totalRecords }}</div><div class="summary-label">Total Records</div></div>
            <div class="summary-item"><div class="summary-number">{{ $isAttendance ? collect($rows)->sum('present') : $completed }}</div><div class="summary-label">{{ $isAttendance ? 'Present' : 'Completed' }}</div></div>
            <div class="summary-item"><div class="summary-number">{{ $isAttendance ? collect($rows)->sum('half') : $pending }}</div><div class="summary-label">{{ $isAttendance ? 'Half Days' : 'Pending' }}</div></div>
            <div class="summary-item"><div class="summary-number">{{ $isAttendance ? collect($rows)->sum('absent') : $approved }}</div><div class="summary-label">{{ $isAttendance ? 'Absent' : 'Approved' }}</div></div>
            <div class="summary-item"><div class="summary-number">{{ number_format($amountTotal, 2) }}</div><div class="summary-label">{{ $isAttendance ? 'Total Payable (Rs.)' : 'Total Amount (Rs.)' }}</div></div>
        </div>
    </div>

    <div class="footer">
        <div class="notes">
            <div class="notes-title">Notes:</div>
            <div>This is a system generated report.<br>For any queries, contact the administrator.</div>
        </div>
        <div class="sign">
            <div class="scribble">PentaPure</div>
            <div class="line"></div>
            <strong>Authorized Signature</strong><br>
            PentaPure Admin
        </div>
    </div>
</div>
</body>
</html>
