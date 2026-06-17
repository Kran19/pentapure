@extends('layouts.app')

@section('content')
<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">Sales Dashboard</h2>
</div>

@include('partials.recent-pos', ['pageData' => $pageData])

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
  
  @php
    $productsMap = collect($pageData['products'])->keyBy('id');
    $rawCount = 0;
    $semiCount = 0;
    $finishedCount = 0;
    
    foreach($pageData['orders'] as $o) {
      $hasRaw = false;
      $hasSemi = false;
      $hasFin = false;
      foreach($o['products'] as $item) {
        $pId = $item['productId'] ?? null;
        $prod = $productsMap->get($pId);
        if ($prod) {
          if (($prod['type'] ?? '') === 'RAW') $hasRaw = true;
          if (($prod['type'] ?? '') === 'SEMI') $hasSemi = true;
          if (($prod['type'] ?? '') === 'FINISHED') $hasFin = true;
        }
      }
      if ($hasRaw) $rawCount++;
      if ($hasSemi) $semiCount++;
      if ($hasFin) $finishedCount++;
    }
  @endphp

  <div class="stat-card" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05);">
    <div style="color:var(--secondary);">Raw Sales</div>
    <div class="stat-value">{{ $rawCount }}</div>
  </div>
  <div class="stat-card" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05);">
    <div style="color:var(--warning);">Semi Sales</div>
    <div class="stat-value">{{ $semiCount }}</div>
  </div>
  <div class="stat-card" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05);">
    <div style="color:var(--primary-light);">Finished Sales</div>
    <div class="stat-value">{{ $finishedCount }}</div>
  </div>

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
