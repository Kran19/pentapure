@extends('layouts.admin')

@section('content')
<div style="padding: 1.5rem;">

  <h2 style="margin-bottom:1.5rem; color:var(--text-main);">
    📊 Dashboard Overview
  </h2>

  <!-- Low Stock Alerts -->
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    <!-- Raw Material Stock -->
    @if(($pageData['lowRawCount'] ?? 0) > 0)
    <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'raw']) }}" style="background-color: #dc2626; color: #ffffff; padding: 1rem; border-radius: 8px; border: 1px solid #b91c1c; display: flex; align-items: center; gap: 10px; text-decoration: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.25); transition: all 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 14px -1px rgba(220, 38, 38, 0.4)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(220, 38, 38, 0.25)';">
      <span style="font-size: 1.25rem;">⚠</span>
      <span style="font-size: 1.05rem; font-weight: 700; color: #ffffff; letter-spacing: 0.2px;">Raw Material Low Stock: {{ $pageData['lowRawCount'] }}</span>
    </a>
    @else
    <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'raw']) }}" style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 10px; text-decoration: none; cursor: pointer; transition: all 0.15s ease;">
      <span style="font-size: 1.25rem;">✅</span>
      <span style="font-size: 1.05rem; font-weight: 700; color: #155724;">Raw Material Low Stock: 0</span>
    </a>
    @endif

    <!-- Semi-Finished Stock -->
    @if(($pageData['lowSemiCount'] ?? 0) > 0)
    <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'semi']) }}" style="background-color: #dc2626; color: #ffffff; padding: 1rem; border-radius: 8px; border: 1px solid #b91c1c; display: flex; align-items: center; gap: 10px; text-decoration: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.25); transition: all 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 14px -1px rgba(220, 38, 38, 0.4)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(220, 38, 38, 0.25)';">
      <span style="font-size: 1.25rem;">⚠</span>
      <span style="font-size: 1.05rem; font-weight: 700; color: #ffffff; letter-spacing: 0.2px;">Semi-Finished Low Stock: {{ $pageData['lowSemiCount'] }}</span>
    </a>
    @else
    <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'semi']) }}" style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 10px; text-decoration: none; cursor: pointer; transition: all 0.15s ease;">
      <span style="font-size: 1.25rem;">✅</span>
      <span style="font-size: 1.05rem; font-weight: 700; color: #155724;">Semi-Finished Low Stock: 0</span>
    </a>
    @endif

    <!-- FG Stock -->
    @if(($pageData['lowFinishedCount'] ?? 0) > 0)
    <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'finished']) }}" style="background-color: #dc2626; color: #ffffff; padding: 1rem; border-radius: 8px; border: 1px solid #b91c1c; display: flex; align-items: center; gap: 10px; text-decoration: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.25); transition: all 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 14px -1px rgba(220, 38, 38, 0.4)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(220, 38, 38, 0.25)';">
      <span style="font-size: 1.25rem;">⚠</span>
      <span style="font-size: 1.05rem; font-weight: 700; color: #ffffff; letter-spacing: 0.2px;">FG Low Stock: {{ $pageData['lowFinishedCount'] }}</span>
    </a>
    @else
    <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'finished']) }}" style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 10px; text-decoration: none; cursor: pointer; transition: all 0.15s ease;">
      <span style="font-size: 1.25rem;">✅</span>
      <span style="font-size: 1.05rem; font-weight: 700; color: #155724;">FG Low Stock: 0</span>
    </a>
    @endif

    <!-- Packaging Stock -->
    @if(($pageData['lowPackagingCount'] ?? 0) > 0)
    <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'packaging']) }}" style="background-color: #dc2626; color: #ffffff; padding: 1rem; border-radius: 8px; border: 1px solid #b91c1c; display: flex; align-items: center; gap: 10px; text-decoration: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.25); transition: all 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 14px -1px rgba(220, 38, 38, 0.4)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(220, 38, 38, 0.25)';">
      <span style="font-size: 1.25rem;">⚠</span>
      <span style="font-size: 1.05rem; font-weight: 700; color: #ffffff; letter-spacing: 0.2px;">Packaging Low Stock: {{ $pageData['lowPackagingCount'] }}</span>
    </a>
    @else
    <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'packaging']) }}" style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 10px; text-decoration: none; cursor: pointer; transition: all 0.15s ease;">
      <span style="font-size: 1.25rem;">✅</span>
      <span style="font-size: 1.05rem; font-weight: 700; color: #155724;">Packaging Low Stock: 0</span>
    </a>
    @endif
  </div>

  <!-- KPI Cards -->
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:1rem; margin-bottom:2rem;">
    <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'raw']) }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--primary-light); word-break:break-word;">
        {{ number_format($pageData['rawQty'] ?? 0, 1) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Raw Stock (kg)</div>
    </a>
    <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'semi']) }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--secondary); word-break:break-word;">
        {{ number_format($pageData['semiQty'] ?? 0, 1) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Semi Stock (kg)</div>
    </a>
    <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'finished']) }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--warning); word-break:break-word;">
        {{ number_format($pageData['finishedQty'] ?? 0, 1) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">FG Stock (kg)</div>
    </a>
    <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'packaging']) }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:#0284c7; word-break:break-word;">
        {{ number_format($pageData['packagingQty'] ?? 0, 1) }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Packaging Stock</div>
    </a>

    <a href="{{ route(request()->segment(1) . '.dispatch.activity') }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--text-main); word-break:break-word;">
        {{ $pageData['totalOrders'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Total Sales Order</div>
    </a>

    <a href="{{ route(request()->segment(1) . '.po') }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--danger); word-break:break-word;">
        {{ $pageData['pendingPOs'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Pending Purchase Order</div>
    </a>
    <a href="{{ route(request()->segment(1) . '.attendance.workers') }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--info); word-break:break-word;">
        {{ $pageData['totalWorkers'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Total Employees</div>
    </a>
    <a href="{{ route(request()->segment(1) . '.attendance.daily') }}" class="card clickable-card" style="text-align:center; padding:1.2rem; overflow:hidden;">
      <div style="font-size:1.6rem; font-weight:bold; color:var(--secondary); word-break:break-word;">
        {{ $pageData['presentToday'] ?? 0 }}
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Present Today</div>
    </a>
  </div>

  <!-- Quick Links -->
  <div class="card" style="padding:1.2rem; margin-bottom:2rem;">
    <div class="card-title">Quick Actions</div>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:0.75rem; margin-top:0.5rem;">
      <a href="{{ route(request()->segment(1) . '.users') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">👥 Manage Users</a>
      <a href="{{ route(request()->segment(1) . '.products') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">🏷️ Products</a>
      <a href="{{ route(request()->segment(1) . '.stock') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">📦 Live Stock</a>
      <a href="{{ route(request()->segment(1) . '.po') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">📋 Purchase Requests</a>
      <a href="{{ route(request()->segment(1) . '.attendance.dashboard') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">🧑‍💼 Attendance</a>
      <a href="{{ route(request()->segment(1) . '.logs') }}" class="btn btn-secondary" style="text-align:center; text-decoration:none;">🕐 Activity Logs</a>

    </div>
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
