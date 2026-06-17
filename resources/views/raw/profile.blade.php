@extends('layouts.app')

@section('content')
<div style="max-width: 600px; margin: 0 auto;">
  <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
    <div style="width:100px; height:100px; background:var(--primary); border-radius:50%; margin:0 auto 1.5rem; display:flex; align-items:center; justify-content:center; font-size:2.5rem; font-weight:bold; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3); color: white;">
      {{ substr($authUser['name'], 0, 1) }}
    </div>
    <h2 style="margin-bottom: 0.5rem; font-size: 1.8rem;">{{ $authUser['name'] }}</h2>
    <span class="badge" style="background:rgba(79, 70, 229, 0.2); color:var(--primary-light); margin-bottom: 2.5rem; padding: 0.4rem 1rem; font-size: 0.9rem;">{{ $authUser['role'] }}</span>
    
    <div class="form-group" style="text-align:left; margin-bottom: 2.5rem; background:rgba(255,255,255,0.03); padding:1.5rem; border-radius:12px;">
      <label style="color:var(--primary-light); font-weight:600; margin-bottom:0.8rem;">Language Preference</label>
      <select onchange="app.setLanguage(this.value)" style="background:rgba(0,0,0,0.3); border-color:rgba(255,255,255,0.1); width:100%; padding:0.7rem; border-radius:8px; color:#fff;">
        <option value="en">English</option>
        <option value="hi">हिंदी (Hindi)</option>
        <option value="gu">ગુજરાતી (Gujarati)</option>
      </select>
    </div>

    <form method="POST" action="/logout" style="margin:0;">
      @csrf
      <button type="submit" class="btn btn-danger" style="padding:1rem; width:100%; display:flex; align-items:center; justify-content:center; gap:0.5rem;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        Logout Securely
      </button>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const select = document.querySelector('select');
  if (select) {
    select.value = localStorage.getItem('pentapure_lang') || 'en';
  }
});
</script>
@endsection
