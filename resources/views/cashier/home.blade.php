@extends('layouts.app')

@section('content')
<div class="card" style="text-align: center; padding: 3rem 1rem; background: linear-gradient(135deg, rgba(79,70,229,0.2), rgba(16,185,129,0.1));">
  <div style="font-size:1.1rem; color:var(--text-muted); margin-bottom: 0.5rem;">Current Ledger Balance</div>
  <h1 style="font-size:3rem; color:var(--text-main); margin:0;">₹{{ number_format($pageData['balance'], 2) }}</h1>
</div>

@include('partials.recent-pos', ['pageData' => $pageData])
@endsection
