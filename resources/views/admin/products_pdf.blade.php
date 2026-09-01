<!DOCTYPE html>
<html>
<head>
    <title>Products Master List</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; text-transform: uppercase; background-color: #ffffff; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #f8c300; padding-bottom: 10px; }
        .header .brand-title { font-size: 24px; font-weight: bold; color: #101828; margin: 0; }
        .header .tagline { font-size: 14px; font-weight: bold; color: #101828; margin-top: 5px; }
        .header .report-title { margin-top: 15px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 14px; font-weight: bold; color: #344054; }
        .header p { margin: 5px 0 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        th { background-color: #f8c300; color: #101828; text-align: left; padding: 8px; border: 1px solid #ddd; }
        td { padding: 8px; border: 1px solid #ddd; vertical-align: top; }
        .footer { margin-top: 30px; font-size: 10px; color: #888; text-align: center; }
        h3 { color: #333; margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%; border-collapse: collapse; margin-top: 0; margin-bottom: 10px; border: none;">
            <tr>
                <td style="width: 20%; text-align: left; vertical-align: middle; border: none; background: transparent;">
                    @if(extension_loaded('gd') && file_exists(public_path('logo.png')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 50px; height: 50px; object-fit: contain;">
                    @endif
                </td>
                <td style="width: 60%; text-align: center; vertical-align: middle; border: none; background: transparent;">
                    <div class="brand-title">PentaPure</div>
                    <div class="tagline">FOOD &amp; SPICES PVT.LTD.</div>
                </td>
                <td style="width: 20%; text-align: right; vertical-align: middle; border: none; background: transparent;">
                    @if(extension_loaded('gd') && file_exists(public_path('logo.png')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 50px; height: 50px; object-fit: contain;">
                    @endif
                </td>
            </tr>
        </table>
        <div class="report-title">Products Master List</div>
        <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
    </div>

    @if($rawProducts->count() > 0)
    <h3>RAW Materials ({{ $rawProducts->count() }})</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">#</th>
                <th style="width: 40%;">Name</th>
                <th style="width: 30%;">Grades</th>
                <th style="width: 20%;">Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rawProducts as $p)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $p->name }}</td>
                <td>
                    @if(!empty($p->gradeNames))
                        {{ implode(', ', $p->gradeNames) }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $p->unit }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($semiProducts->count() > 0)
    <h3>SEMI Products ({{ $semiProducts->count() }})</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">#</th>
                <th style="width: 40%;">Name</th>
                <th style="width: 30%;">Grades</th>
                <th style="width: 20%;">Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($semiProducts as $p)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $p->name }}</td>
                <td>
                    @if(!empty($p->gradeNames))
                        {{ implode(', ', $p->gradeNames) }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $p->unit }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($finishedProducts->count() > 0)
    <h3>FINISHED Products ({{ $finishedProducts->count() }})</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">#</th>
                <th style="width: 40%;">Name</th>
                <th style="width: 30%;">Grades</th>
                <th style="width: 20%;">Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($finishedProducts as $p)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $p->name }}</td>
                <td>
                    @if(!empty($p->gradeNames))
                        {{ implode(', ', $p->gradeNames) }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $p->unit }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        Generated by PentaPure ERP System on {{ now()->format('d M Y, h:i A') }}
    </div>
</body>
</html>
