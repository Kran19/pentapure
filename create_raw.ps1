$dir = "c:\Users\Admin\Desktop\projects\pentapure\resources\views\raw"
if (!(Test-Path $dir)) { New-Item -ItemType Directory -Path $dir | Out-Null }

$homeContent = @"
@extends('layouts.app')

@section('content')
<div class="flex-between mb-1">
  <h2 style="margin:0;">Raw Material Overview</h2>
</div>

<div class="tabs">
  <a href="#" class="tab-btn active">Stock</a>
  <a href="#" class="tab-btn">Inward</a>
  <a href="#" class="tab-btn">Outward</a>
</div>

<!-- Dummy Data Loop -->
<div class="card" style="padding: 1rem;">
  <div class="flex-between">
    <div>
      <div style="font-weight:600; font-size:1.1rem;">PP Granules</div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Grade: <span class="badge badge-info">A-1</span></div>
    </div>
    <div style="text-align:right;">
      <div style="font-size:1.4rem; font-weight:bold; color:var(--primary-light);">1,500 <span style="font-size:0.9rem; color:var(--text-muted);">kg</span></div>
    </div>
  </div>
</div>
<div class="card" style="padding: 1rem;">
  <div class="flex-between">
    <div>
      <div style="font-weight:600; font-size:1.1rem;">Carbon Powder</div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Grade: <span class="badge badge-info">Standard</span></div>
    </div>
    <div style="text-align:right;">
      <div style="font-size:1.4rem; font-weight:bold; color:var(--primary-light);">500 <span style="font-size:0.9rem; color:var(--text-muted);">kg</span></div>
    </div>
  </div>
</div>
@endsection
"@
Set-Content -Path "$dir\home.blade.php" -Value $homeContent

$actionContent = @"
@extends('layouts.app')

@section('content')
<div class="card">
  <div class="card-title">Inward Raw Material</div>
  <div class="form-group" style="margin-bottom:0.8rem;">
    <input type="text" placeholder="🔍 Search product..." style="padding:0.6rem 0.8rem; font-size:0.85rem;">
  </div>
  
  <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:8px; margin-bottom:1rem;">
    <!-- Dummy selection cards -->
    <div class="rm-card" style="border:2px solid var(--primary); border-radius:10px; overflow:hidden; background:rgba(255,255,255,0.05); text-align:center; padding-bottom:4px;">
      <img src="https://via.placeholder.com/100x60?text=IMG" style="width:100%; height:60px; object-fit:cover;">
      <div style="font-size:0.7rem; font-weight:600; padding:2px 3px;">PP Granules</div>
    </div>
    <div class="rm-card" style="border:2px solid transparent; border-radius:10px; overflow:hidden; background:rgba(255,255,255,0.05); text-align:center; padding-bottom:4px;">
      <img src="https://via.placeholder.com/100x60?text=IMG" style="width:100%; height:60px; object-fit:cover;">
      <div style="font-size:0.7rem; font-weight:600; padding:2px 3px;">Carbon Block</div>
    </div>
  </div>

  <div class="form-group">
    <label>Quantity (kg)</label>
    <input type="number" placeholder="Enter inward quantity" style="padding:0.7rem;">
  </div>
  <button class="btn mt-1">Add to Stock</button>
</div>
@endsection
"@
Set-Content -Path "$dir\action.blade.php" -Value $actionContent

$historyContent = @"
@extends('layouts.app')

@section('content')
<h2 class="mb-1">History</h2>
<div class="filter-bar" style="flex-wrap:wrap; gap:8px; margin-bottom:1rem; padding: 0.5rem; background:rgba(0,0,0,0.2); border-radius:8px;">
  <select style="width:auto; flex:1;">
    <option value="today">Today</option>
    <option value="this_week">This Week</option>
  </select>
</div>
<div class="table-container">
  <table>
    <thead><tr><th>Date</th><th>Product</th><th>Qty</th></tr></thead>
    <tbody>
      <tr>
        <td style="font-size:0.8rem;">2026-04-25 10:00</td>
        <td>PP Granules</td>
        <td style="font-weight:bold; color:var(--secondary)">+500 kg</td>
      </tr>
      <tr>
        <td style="font-size:0.8rem;">2026-04-24 14:30</td>
        <td>Carbon Powder</td>
        <td style="font-weight:bold; color:var(--secondary)">+200 kg</td>
      </tr>
    </tbody>
  </table>
</div>
@endsection
"@
Set-Content -Path "$dir\history.blade.php" -Value $historyContent
