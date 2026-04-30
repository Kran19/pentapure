$folders = @('raw', 'semi', 'finished', 'cashier', 'sales', 'dispatch')
foreach ($folder in $folders) {
    $dir = "c:\Users\Admin\Desktop\projects\pentapure\resources\views\$folder"
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir | Out-Null
    }
    $content = @"
@extends('layouts.app')

@section('content')
<div class="card" style="text-align: center; padding: 2rem 1rem;">
  <div style="width:80px; height:80px; background:var(--primary); border-radius:50%; margin:0 auto 1rem; display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:bold;">
    U
  </div>
  <h2 style="margin-bottom: 0.5rem;">User Name ($folder)</h2>
  <span class="badge" style="background:var(--primary-light); color:var(--dark); margin-bottom: 2rem;">ROLE</span>
  
  <div class="form-group" style="text-align:left; margin-bottom: 2rem;">
    <label>Language Preference</label>
    <select>
      <option value="en" selected>English</option>
      <option value="hi">हिंदी (Hindi)</option>
      <option value="gu">ગુજરાતી (Gujarati)</option>
    </select>
  </div>

  <a href="{{ url('/login') }}" class="btn btn-danger" style="text-decoration:none; display:inline-block; text-align:center;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
    Logout Securely
  </a>
</div>
@endsection
"@
    Set-Content -Path "$dir\profile.blade.php" -Value $content
}
