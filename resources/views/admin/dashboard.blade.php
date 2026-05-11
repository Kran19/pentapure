@extends('layouts.admin')

@section('content')
<div style="padding: 1.5rem;">

  <h2 style="margin-bottom:1.5rem; color:var(--text-main);">
    📊 Dashboard Overview
  </h2>

  <!-- KPI Cards -->
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:1rem; margin-bottom:2rem;">
    <div class="card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--primary-light);">
        {{ number_format($pageData['rawQty'] ?? 0, 1) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Raw Stock (kg)</div>
    </div>
    <div class="card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--secondary);">
        {{ number_format($pageData['semiQty'] ?? 0, 1) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Semi Stock (kg)</div>
    </div>
    <div class="card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--warning);">
        {{ number_format($pageData['finishedQty'] ?? 0, 1) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Finished Stock (kg)</div>
    </div>
    <div class="card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--text-main);">
        {{ $pageData['totalOrders'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Total Orders</div>
    </div>
    <div class="card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--secondary);">
        ₹{{ number_format($pageData['totalRevenue'] ?? 0, 0) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Total Revenue</div>
    </div>
    <div class="card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--danger);">
        {{ $pageData['pendingPOs'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Pending POs</div>
    </div>
    <div class="card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--info);">
        {{ $pageData['totalWorkers'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Total Employees</div>
    </div>
    <div class="card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--secondary);">
        {{ $pageData['presentToday'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Present Today</div>
    </div>
  </div>

  <!-- Quick Links -->
  <div class="card" style="padding:1.2rem;">
    <div class="card-title">Quick Actions</div>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:0.75rem; margin-top:0.5rem;">
      <a href="{{ url('admin/users') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">👥 Manage Users</a>
      <a href="{{ url('admin/products') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">🏷️ Products</a>
      <a href="{{ url('admin/stock') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">📦 Live Stock</a>
      <a href="{{ url('admin/po') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">📋 Purchase Orders</a>
      <a href="{{ url('admin/logs') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">🕐 Activity Logs</a>
      <a href="{{ url('admin/debug-notifications') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none; background: var(--danger); border:none;">🔔 Notifications Debug</a>
    </div>
  </div>

</div>
@endsection
