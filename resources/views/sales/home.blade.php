@extends('layouts.app')

@section('content')
<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">Sales Dashboard</h2>
</div>


<div class="dashboard-grid" style="margin-top:1rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
  <a class="stat-card clickable-card" href="{{ url('sales/history') }}" style="text-decoration:none;">
    <div style="color:var(--primary-light)">Total Orders</div>
    <div class="stat-value">{{ $pageData['stats']['totalOrders'] ?? 0 }}</div>
  </a>
  <a class="stat-card clickable-card" href="{{ url('sales/history') }}" style="text-decoration:none;">
    <div style="color:var(--warning)">Pending Orders</div>
    <div class="stat-value">{{ $pageData['stats']['pendingOrders'] ?? 0 }}</div>
  </a>
  <a class="stat-card clickable-card" href="{{ url('sales/history') }}" style="text-decoration:none;">
    <div style="color:var(--secondary)">Dispatched Orders</div>
    <div class="stat-value">{{ $pageData['stats']['dispatchedOrders'] ?? 0 }}</div>
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
