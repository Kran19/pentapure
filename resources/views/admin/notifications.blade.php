@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">

  {{-- ── Header ──────────────────────────────────────── --}}
  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <div>
      <h2 style="margin:0; font-size:1.6rem;">🔔 Notification History</h2>
      <p style="margin:0.3rem 0 0; font-size:0.9rem; color:var(--text-muted);">
        {{ $pageData['totalCount'] }} total notifications
      </p>
    </div>
  </div>

  {{-- ── Filter Tabs ──────────────────────────────────── --}}
  <div style="display:flex; gap:0.5rem; margin-bottom:1.2rem; flex-wrap:wrap;">
    <button class="notif-tab active" data-filter="all"    onclick="filterNotifs('all',    this)">All</button>
    <button class="notif-tab"        data-filter="warning"onclick="filterNotifs('warning',this)">⚠️ Low Stock</button>
    <button class="notif-tab"        data-filter="info"   onclick="filterNotifs('info',   this)">ℹ️ Info</button>
    <button class="notif-tab"        data-filter="success"onclick="filterNotifs('success',this)">✅ Success</button>
    <button class="notif-tab"        data-filter="danger" onclick="filterNotifs('danger', this)">🔴 Alert</button>
  </div>

  {{-- ── Notifications List ───────────────────────────── --}}
  @if($pageData['notifications']->isEmpty())
    <div class="card" style="padding:3rem 2rem; text-align:center; color:var(--text-muted);">
      <div style="font-size:3rem; margin-bottom:1rem;">🔕</div>
      <h3 style="margin:0 0 0.5rem;">No Notifications Yet</h3>
      <p style="margin:0; font-size:0.9rem;">Notifications will appear here when stock alerts or system events occur.</p>
    </div>
  @else
    <div id="notif-list" style="display:grid; gap:0.75rem;">
      @foreach($pageData['notifications'] as $n)
      @php
        $borderColor = match($n->type) {
          'warning' => '#f59e0b',
          'danger'  => '#ef4444',
          'success' => '#22c55e',
          default   => '#3b82f6',
        };
        $bgColor = 'var(--card-bg)';
        $iconMap  = ['warning' => '⚠️', 'danger' => '🔴', 'success' => '✅', 'info' => 'ℹ️'];
        $icon     = $iconMap[$n->type] ?? '🔔';
      @endphp
      <div class="notif-card"
           data-type="{{ $n->type }}"
           data-id="{{ $n->id }}"
           style="
             background: {{ $bgColor }};
             border-radius: 12px;
             border-left: 4px solid {{ $borderColor }};
             padding: 1rem 1.2rem;
             display: flex;
             align-items: flex-start;
             gap: 1rem;
             box-shadow: 0 1px 4px rgba(0,0,0,0.06);
             transition: background 0.3s, opacity 0.3s;
             position: relative;
           ">

        {{-- Icon --}}
        <div style="font-size:1.5rem; flex-shrink:0; margin-top:0.15rem;">{{ $icon }}</div>

        {{-- Body --}}
        <div style="flex:1; min-width:0;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.25rem;">
            <div style="font-weight:600; font-size:1rem; color:var(--text-main);">
              {{ $n->title }}
            </div>
            <div style="font-size:0.78rem; color:var(--text-muted); white-space:nowrap;">
              {{ $n->created_at->diffForHumans() }}
              &nbsp;·&nbsp;
              {{ $n->created_at->format('d-m-Y, H:i') }}
            </div>
          </div>

          <div style="font-size:0.9rem; color:var(--text-muted); margin-top:0.35rem; line-height:1.5;">
            {{ $n->message }}
          </div>

          <div style="display:flex; align-items:center; gap:1rem; margin-top:0.6rem; flex-wrap:wrap;">
            {{-- Source class badge --}}
            <span style="font-size:0.72rem; background:rgba(0,0,0,0.08); color:var(--text-muted); padding:2px 8px; border-radius:20px;">
              {{ $n->notif_class }}
            </span>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    {{-- Empty state when filter returns nothing --}}
    <div id="notif-empty" style="display:none; padding:3rem 2rem; text-align:center; color:var(--text-muted);">
      <div style="font-size:2.5rem;">🔍</div>
      <p>No notifications match this filter.</p>
    </div>
  @endif

</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function filterNotifs(filter, btn) {
  document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');

  const cards = document.querySelectorAll('.notif-card');
  let visible = 0;
  cards.forEach(card => {
    const type = card.dataset.type; // warning, danger, info, success

    let show = false;
    if (filter === 'all') show = true;
    else show = type === filter;

    card.style.display = show ? 'flex' : 'none';
    if (show) visible++;
  });

  const empty = document.getElementById('notif-empty');
  if (empty) empty.style.display = visible === 0 ? 'block' : 'none';
}
</script>

<style>
.notif-tab {
  padding: 0.4rem 1rem;
  border: 1px solid var(--glass-border);
  border-radius: 20px;
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  font-size: 0.85rem;
  transition: all 0.2s;
}
.notif-tab:hover  { background: rgba(0,0,0,0.06); color: var(--text-main); }
.notif-tab.active { background: var(--primary); color: #fff; border-color: var(--primary); font-weight: 600; }

.notif-card:hover { box-shadow: 0 3px 12px rgba(0,0,0,0.1) !important; }
</style>
@endsection
