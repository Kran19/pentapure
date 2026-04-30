$dir = "c:\Users\Admin\Desktop\projects\pentapure\resources\views\admin"
if (!(Test-Path $dir)) { New-Item -ItemType Directory -Path $dir | Out-Null }

$dashboardContent = @"
@extends('layouts.admin')

@section('content')
<h2 class="mb-1">Hello, Super Admin</h2>
<div class="card bg-gradient mb-1" style="color:white; padding:1.5rem;">
  <div style="font-size:0.9rem; margin-bottom:8px;">Total Active Users</div>
  <div style="font-size:2rem; font-weight:bold;">12</div>
</div>
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
  <div class="card" style="padding:1rem;">
    <div style="font-size:0.8rem; color:var(--text-muted);">Pending POs</div>
    <div style="font-size:1.5rem; font-weight:bold; color:var(--danger);">5</div>
  </div>
  <div class="card" style="padding:1rem;">
    <div style="font-size:0.8rem; color:var(--text-muted);">Low Stock Items</div>
    <div style="font-size:1.5rem; font-weight:bold; color:var(--warning);">3</div>
  </div>
</div>
@endsection
"@
Set-Content -Path "$dir\dashboard.blade.php" -Value $dashboardContent

$usersContent = @"
@extends('layouts.admin')

@section('content')
<div class="flex-between mb-1">
  <h2 style="margin:0;">User Management</h2>
  <button class="btn btn-sm btn-secondary" style="width:auto;">Add User</button>
</div>
<div class="card" style="padding:1rem;">
  <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--glass-border); padding-bottom:10px; margin-bottom:10px;">
    <div><strong>Amit</strong><br><small>RAW</small></div>
    <span class="badge badge-done">Active</span>
  </div>
  <div style="display:flex; justify-content:space-between; padding-bottom:10px;">
    <div><strong>Rahul</strong><br><small>SEMI</small></div>
    <span class="badge badge-done">Active</span>
  </div>
</div>
@endsection
"@
Set-Content -Path "$dir\users.blade.php" -Value $usersContent

$productsContent = @"
@extends('layouts.admin')

@section('content')
<div class="flex-between mb-1">
  <h2 style="margin:0;">Products Master</h2>
  <button class="btn btn-sm btn-secondary" style="width:auto;">Add Product</button>
</div>
<div class="card" style="padding:1rem;">
  <p>Product list goes here.</p>
</div>
@endsection
"@
Set-Content -Path "$dir\products.blade.php" -Value $productsContent

$stockContent = @"
@extends('layouts.admin')

@section('content')
<h2 class="mb-1">Live Stock</h2>
<div class="card" style="padding:1rem;">
  <p>Live stock views go here.</p>
</div>
@endsection
"@
Set-Content -Path "$dir\stock.blade.php" -Value $stockContent

$poContent = @"
@extends('layouts.admin')

@section('content')
<h2 class="mb-1">Purchase Orders</h2>
<div class="card" style="padding:1rem;">
  <p>Purchase Orders go here.</p>
</div>
@endsection
"@
Set-Content -Path "$dir\po.blade.php" -Value $poContent

$logsContent = @"
@extends('layouts.admin')

@section('content')
<h2 class="mb-1">Activity Logs</h2>
<div class="card" style="padding:1rem;">
  <p>System logs will be monitored here.</p>
</div>
@endsection
"@
Set-Content -Path "$dir\logs.blade.php" -Value $logsContent
