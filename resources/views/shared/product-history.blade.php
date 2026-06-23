@extends('layouts.app')

@section('content')
@php
    $user = session('auth_user');
    $role = strtoupper($user['role'] ?? '');
    
    // Default fallback
    $backUrl = url($role === 'ADMIN' ? 'admin/products' : strtolower($role) . '/home');

    // If they came from a specific page (via query param), go back there
    if (request()->query('from') === 'history') {
        $backUrl = url(strtolower($role) . '/history');
    } elseif (request()->query('from') === 'home') {
        $backUrl = url(strtolower($role) . '/home');
    } elseif (request()->query('from') === 'products') {
        $backUrl = url('admin/products');
    }
@endphp
<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
    <h2 style="margin:0;">📦 Product History: {{ $product->formatName($grade) }}</h2>
    <a href="{{ $backUrl }}" class="btn btn-sm btn-secondary" style="width:auto; padding:0.5rem 1rem; text-decoration:none;">&laquo; Back</a>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="flex-between">
        <div>
            <div style="font-size:0.9rem; color:var(--text-muted);">Stage & Grade</div>
            <div style="font-weight:600; font-size:1.1rem; margin-top:4px;">{{ strtoupper($stage) }} - <span class="badge badge-info">{{ $grade }}</span></div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:0.9rem; color:var(--text-muted);">Current Stock</div>
            <div style="font-size:1.4rem; font-weight:bold; color:var(--primary-light);">{{ number_format($currentTotal, 2) }} <span style="font-size:0.9rem; color:var(--text-muted);">{{ $product->unit }}</span></div>
        </div>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>User</th>
                <th>Type</th>
                <th>Qty ({{ $product->unit }})</th>
                <th>Location</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stockLogs as $log)
                <tr>
                    <td style="font-size:0.85rem;">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                    <td>{{ $log->user->name ?? 'System' }}</td>
                    <td>
                        @if($log->transaction_type === 'IN')
                            <span class="badge" style="background:#d3d3d3de; color:#2ecc71; min-width: 55px; display: inline-block; text-align: center;">IN</span>
                        @else
                            <span class="badge" style="background:#d3d3d3de; color:red; min-width: 55px; display: inline-block; text-align: center;">OUT</span>
                        @endif
                    </td>

                    <td style="font-weight:bold; color:{{ $log->transaction_type === 'IN' ? '#2ecc71' : 'red' }}">
                        {{ $log->transaction_type === 'IN' ? '+' : '-' }}{{ number_format($log->quantity, 2) }}
                    </td>
                    <td>{{ $log->location->name ?? 'Default' }}</td>
                    <td style="font-size:0.85rem; color:var(--text-muted); max-width:250px; overflow-wrap:break-word;">
                        {{ $log->notes ?: '-' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No history found for this product.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 1rem; display:flex; justify-content:center;">
    {{ $stockLogs->links('pagination::bootstrap-4') }}
</div>

<style>
/* Basic Pagination Styling for Dark Mode */
.pagination { display:flex; flex-wrap:wrap; list-style:none; padding:0; margin:0; gap:5px; }
.pagination li a, .pagination li span { padding:0.4rem 0.8rem; border-radius:4px; background:#21262d; border:1px solid #30363d; color:#8b949e; text-decoration:none; font-size:0.9rem; }
.pagination li a:hover { background:#30363d; color:#fff; }
.pagination li.active span { background:var(--primary); color:#fff; border-color:var(--primary); }
.pagination li.disabled span { opacity:0.5; cursor:not-allowed; }
</style>
@endsection
