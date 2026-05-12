@extends('layouts.admin')

@section('content')
<div style="padding: 1.5rem;">

  <h2 style="margin-bottom:1.5rem; color:var(--text-main);">
    📊 Dashboard Overview
  </h2>

  <!-- Low Stock Alerts -->
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    <!-- Raw Material Stock -->
    @if(($pageData['lowRawCount'] ?? 0) > 0)
    <div style="background-color: #fff3cd; color: #856404; padding: 1rem; border-radius: 8px; border: 1px solid #ffeeba; display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 1.2rem;">⚠</span>
      <a href="{{ route('admin.stock') }}" style="font-size: 1.1rem; font-weight: bold; color: inherit; text-decoration: none;">Raw Material Low Stock: {{ $pageData['lowRawCount'] }}</a>
    </div>
    @else
    <div style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 1.2rem;">✅</span>
      <a href="{{ route('admin.stock') }}" style="font-size: 1.1rem; font-weight: bold; color: inherit; text-decoration: none;">Raw Material Low Stock: 0</a>
    </div>
    @endif

    <!-- Semi-Finished Stock -->
    @if(($pageData['lowSemiCount'] ?? 0) > 0)
    <div style="background-color: #fff3cd; color: #856404; padding: 1rem; border-radius: 8px; border: 1px solid #ffeeba; display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 1.2rem;">⚠</span>
      <a href="{{ route('admin.stock') }}" style="font-size: 1.1rem; font-weight: bold; color: inherit; text-decoration: none;">Semi-Finished Low Stock: {{ $pageData['lowSemiCount'] }}</a>
    </div>
    @else
    <div style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 1.2rem;">✅</span>
      <a href="{{ route('admin.stock') }}" style="font-size: 1.1rem; font-weight: bold; color: inherit; text-decoration: none;">Semi-Finished Low Stock: 0</a>
    </div>
    @endif

    <!-- Finished Goods Stock -->
    @if(($pageData['lowFinishedCount'] ?? 0) > 0)
    <div style="background-color: #fff3cd; color: #856404; padding: 1rem; border-radius: 8px; border: 1px solid #ffeeba; display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 1.2rem;">⚠</span>
      <a href="{{ route('admin.stock') }}" style="font-size: 1.1rem; font-weight: bold; color: inherit; text-decoration: none;">Finished Goods Low Stock: {{ $pageData['lowFinishedCount'] }}</a>
    </div>
    @else
    <div style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 1.2rem;">✅</span>
      <a href="{{ route('admin.stock') }}" style="font-size: 1.1rem; font-weight: bold; color: inherit; text-decoration: none;">Finished Goods Low Stock: 0</a>
    </div>
    @endif
  </div>

  <!-- KPI Cards -->
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:1rem; margin-bottom:2rem;">
    <a href="{{ route('admin.stock') }}" class="card clickable-card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--primary-light);">
        {{ number_format($pageData['rawQty'] ?? 0, 1) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Raw Stock (kg)</div>
    </a>
    <a href="{{ route('admin.stock') }}" class="card clickable-card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--secondary);">
        {{ number_format($pageData['semiQty'] ?? 0, 1) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Semi Stock (kg)</div>
    </a>
    <a href="{{ route('admin.stock') }}" class="card clickable-card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--warning);">
        {{ number_format($pageData['finishedQty'] ?? 0, 1) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Finished Stock (kg)</div>
    </a>
    <a href="{{ route('admin.logs') }}" class="card clickable-card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--text-main);">
        {{ $pageData['totalOrders'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Total Orders</div>
    </a>
    <a href="{{ route('admin.logs') }}" class="card clickable-card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--secondary);">
        ₹{{ number_format($pageData['totalRevenue'] ?? 0, 0) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Total Revenue</div>
    </a>
    <a href="{{ route('admin.po') }}" class="card clickable-card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--danger);">
        {{ $pageData['pendingPOs'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Pending POs</div>
    </a>
    <a href="{{ route('admin.attendance.workers') }}" class="card clickable-card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--info);">
        {{ $pageData['totalWorkers'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Total Employees</div>
    </a>
    <a href="{{ route('admin.attendance.daily') }}" class="card clickable-card" style="text-align:center; padding:1.2rem;">
      <div style="font-size:2rem; font-weight:bold; color:var(--secondary);">
        {{ $pageData['presentToday'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Present Today</div>
    </a>
  </div>

  <!-- Quick Links -->
  <div class="card" style="padding:1.2rem;">
    <div class="card-title">Quick Actions</div>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:0.75rem; margin-top:0.5rem;">
      <a href="{{ route('admin.users') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">👥 Manage Users</a>
      <a href="{{ route('admin.products') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">🏷️ Products</a>
      <a href="{{ route('admin.stock') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">📦 Live Stock</a>
      <a href="{{ route('admin.po') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">📋 Purchase Orders</a>
      <a href="{{ route('admin.logs') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">🕐 Activity Logs</a>
      <a href="{{ url('admin/debug-notifications') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none; background: var(--danger); border:none;">🔔 Notifications Debug</a>
    </div>
  </div>

</div>
@endsection
