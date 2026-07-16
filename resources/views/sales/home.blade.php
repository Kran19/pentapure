@extends('layouts.app')

@section('content')
<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">Sales Dashboard</h2>
</div>


<div class="dashboard-grid" style="margin-top:1rem;">
  <a class="stat-card clickable-card" href="{{ url('sales/history') }}" style="text-decoration:none;">
    <div style="color:var(--primary-light)">Total Orders</div>
    <div class="stat-value">{{ $pageData['stats']['totalOrders'] }}</div>
  </a>
  <a class="stat-card clickable-card" href="{{ url('sales/history') }}" style="text-decoration:none;">
    <div style="color:var(--warning)">Open Orders</div>
    <div class="stat-value">{{ $pageData['stats']['openOrders'] }}</div>
  </a>
  <a class="stat-card clickable-card" href="{{ url('sales/history') }}" style="text-decoration:none;">
    <div style="color:var(--info)">Pending Dispatch</div>
    <div class="stat-value">{{ $pageData['stats']['pendingDisp'] }}</div>
  </a>
  

  <a class="stat-card clickable-card" href="{{ url('sales/history') }}" style="grid-column: 1 / -1; background:var(--dark-panel); text-decoration:none;">
    <div style="color:var(--text-muted)">Total Sales Value</div>
    <div class="stat-value" style="color:var(--secondary)">₹{{ number_format($pageData['stats']['totalValue'], 2) }}</div>
  </a>
</div>

<div class="card mt-2">
  <div class="card-title">Quick Links</div>
  <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
    <a class="btn btn-secondary" style="text-align:center; text-decoration:none;" href="{{ url('sales/action') }}">Create New Order</a>
    <a class="btn btn-secondary" style="text-align:center; text-decoration:none;" href="{{ url('sales/history') }}">View History</a>
  </div>
</div>
@endsection
