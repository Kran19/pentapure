$folders = @('semi', 'finished', 'cashier', 'sales', 'dispatch')
foreach ($folder in $folders) {
    $dir = "c:\Users\Admin\Desktop\projects\pentapure\resources\views\$folder"
    if (!(Test-Path $dir)) { New-Item -ItemType Directory -Path $dir | Out-Null }

    $title = (Get-Culture).TextInfo.ToTitleCase($folder)

    $homeContent = @"
@extends('layouts.app')

@section('content')
<div class="flex-between mb-1">
  <h2 style="margin:0;">$title Overview</h2>
</div>

<div class="tabs">
  <a href="#" class="tab-btn active">Overview</a>
  <a href="#" class="tab-btn">Recent Activity</a>
</div>

<div class="card" style="padding: 1rem;">
  <p class="text-center text-muted">Welcome to $title dashboard.</p>
</div>
@endsection
"@
    Set-Content -Path "$dir\home.blade.php" -Value $homeContent

    $actionContent = @"
@extends('layouts.app')

@section('content')
<div class="card">
  <div class="card-title">$title Action</div>
  <div class="form-group">
    <label>Sample Input</label>
    <input type="text" placeholder="Enter details" style="padding:0.7rem;">
  </div>
  <button class="btn mt-1">Submit</button>
</div>
@endsection
"@
    Set-Content -Path "$dir\action.blade.php" -Value $actionContent

    $historyContent = @"
@extends('layouts.app')

@section('content')
<h2 class="mb-1">$title History</h2>
<div class="table-container">
  <table>
    <thead><tr><th>Date</th><th>Activity</th><th>Status</th></tr></thead>
    <tbody>
      <tr>
        <td style="font-size:0.8rem;">2026-04-25 10:00</td>
        <td>System Login</td>
        <td><span class="badge badge-done">Success</span></td>
      </tr>
    </tbody>
  </table>
</div>
@endsection
"@
    Set-Content -Path "$dir\history.blade.php" -Value $historyContent
}
