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
      <a href="{{ route('admin.stock', ['type' => 'raw']) }}" style="font-size: 1.1rem; font-weight: bold; color: inherit; text-decoration: none;">Raw Material Low Stock: {{ $pageData['lowRawCount'] }}</a>
    </div>
    @else
    <div style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 1.2rem;">✅</span>
      <a href="{{ route('admin.stock', ['type' => 'raw']) }}" style="font-size: 1.1rem; font-weight: bold; color: inherit; text-decoration: none;">Raw Material Low Stock: 0</a>
    </div>
    @endif

    <!-- Semi-Finished Stock -->
    @if(($pageData['lowSemiCount'] ?? 0) > 0)
    <div style="background-color: #fff3cd; color: #856404; padding: 1rem; border-radius: 8px; border: 1px solid #ffeeba; display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 1.2rem;">⚠</span>
      <a href="{{ route('admin.stock', ['type' => 'semi']) }}" style="font-size: 1.1rem; font-weight: bold; color: inherit; text-decoration: none;">Semi-Finished Low Stock: {{ $pageData['lowSemiCount'] }}</a>
    </div>
    @else
    <div style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 1.2rem;">✅</span>
      <a href="{{ route('admin.stock', ['type' => 'semi']) }}" style="font-size: 1.1rem; font-weight: bold; color: inherit; text-decoration: none;">Semi-Finished Low Stock: 0</a>
    </div>
    @endif

    <!-- Finished Goods Stock -->
    @if(($pageData['lowFinishedCount'] ?? 0) > 0)
    <div style="background-color: #fff3cd; color: #856404; padding: 1rem; border-radius: 8px; border: 1px solid #ffeeba; display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 1.2rem;">⚠</span>
      <a href="{{ route('admin.stock', ['type' => 'finished']) }}" style="font-size: 1.1rem; font-weight: bold; color: inherit; text-decoration: none;">Finished Goods Low Stock: {{ $pageData['lowFinishedCount'] }}</a>
    </div>
    @else
    <div style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 1.2rem;">✅</span>
      <a href="{{ route('admin.stock', ['type' => 'finished']) }}" style="font-size: 1.1rem; font-weight: bold; color: inherit; text-decoration: none;">Finished Goods Low Stock: 0</a>
    </div>
    @endif
  </div>

  <!-- KPI Cards -->
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:1rem; margin-bottom:2rem;">
    <a href="{{ route('admin.stock', ['type' => 'raw']) }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--primary-light); word-break:break-word;">
        {{ number_format($pageData['rawQty'] ?? 0, 1) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Raw Stock (kg)</div>
    </a>
    <a href="{{ route('admin.stock', ['type' => 'semi']) }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--secondary); word-break:break-word;">
        {{ number_format($pageData['semiQty'] ?? 0, 1) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Semi Stock (kg)</div>
    </a>
    <a href="{{ route('admin.stock', ['type' => 'finished']) }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--warning); word-break:break-word;">
        {{ number_format($pageData['finishedQty'] ?? 0, 1) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Finished Stock (kg)</div>
    </a>

    <a href="{{ route('admin.dispatch.activity') }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--text-main); word-break:break-word;">
        {{ $pageData['totalOrders'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Total Orders</div>
    </a>

    <a href="{{ route('admin.logs') }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--secondary); word-break:break-word;">
        ₹{{ number_format($pageData['totalRevenue'] ?? 0, 0) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Total Revenue</div>
    </a>
    <a href="{{ route('admin.po') }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--danger); word-break:break-word;">
        {{ $pageData['pendingPOs'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Pending POs</div>
    </a>
    <a href="{{ route('admin.attendance.workers') }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--info); word-break:break-word;">
        {{ $pageData['totalWorkers'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Total Employees</div>
    </a>
    <a href="{{ route('admin.attendance.daily') }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--secondary); word-break:break-word;">
        {{ $pageData['presentToday'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Present Today</div>
    </a>
  </div>

  <!-- Charts Section -->
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
    <!-- Sales Trend Chart -->
    <div class="card" style="padding:1.5rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h3 style="margin:0; font-size:1.1rem; color:var(--text-main);">📈 Sales Trend (Last 7 Days)</h3>
        <span style="font-size:0.75rem; color:var(--text-muted);">Revenue in ₹</span>
      </div>
      <div style="height: 250px; position: relative;">
        <canvas id="salesChart"></canvas>
      </div>
    </div>

    <!-- Production vs Stock Distribution -->
    <div class="card" style="padding:1.5rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h3 style="margin:0; font-size:1.1rem; color:var(--text-main);">🏗️ Production Activity</h3>
        <span style="font-size:0.75rem; color:var(--text-muted);">Qty in kg</span>
      </div>
      <div style="height: 250px; position: relative;">
        <canvas id="productionChart"></canvas>
      </div>
    </div>
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

    </div>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark-mode');
    const textColor = isDark ? '#e2e8f0' : '#475569';
    const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';

    // --- Sales Chart ---
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesGradient = salesCtx.createLinearGradient(0, 0, 0, 250);
    salesGradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
    salesGradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: @json($pageData['days']),
            datasets: [{
                label: 'Revenue',
                data: @json($pageData['salesTrend']),
                borderColor: '#10b981',
                borderWidth: 3,
                backgroundColor: salesGradient,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#10b981'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 } } },
                x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 } } }
            }
        }
    });

    // --- Production Chart ---
    const prodCtx = document.getElementById('productionChart').getContext('2d');
    new Chart(prodCtx, {
        type: 'bar',
        data: {
            labels: @json($pageData['days']),
            datasets: [{
                label: 'Production Qty',
                data: @json($pageData['productionTrend']),
                backgroundColor: '#f59e0b',
                borderRadius: 6,
                barThickness: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 } } },
                x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 } } }
            }
        }
    });
});
</script>
@endsection
