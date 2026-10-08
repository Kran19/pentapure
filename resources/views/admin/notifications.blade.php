@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">

  {{-- ── Header ──────────────────────────────────────── --}}
  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <div>
      <h2 style="margin:0; font-size:1.6rem;">🔔 Notification History</h2>
      <p style="margin:0.3rem 0 0; font-size:0.9rem; color:var(--text-muted);">
        <span id="notif-total-count">{{ $pageData['totalCount'] }}</span> total notifications
      </p>
    </div>
    @if($pageData['totalCount'] > 0 && empty($isReadOnly))
    <div style="display:flex; gap:0.5rem;" id="clear-all-wrap">
      <button type="button" onclick="clearAllNotifications()" class="btn" style="background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.3); padding:0.5rem 1rem; border-radius:8px; font-size:0.85rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:5px;" onmouseover="this.style.background='#ef4444'; this.style.color='#fff';" onmouseout="this.style.background='rgba(239,68,68,0.1)'; this.style.color='#ef4444';">
        🗑️ Clear All Notifications
      </button>
    </div>
    @endif
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
  <div id="notif-none" class="card" style="{{ $pageData['notifications']->isEmpty() ? '' : 'display:none;' }} padding:3rem 2rem; text-align:center; color:var(--text-muted);">
    <div style="font-size:3rem; margin-bottom:1rem;">🔕</div>
    <h3 style="margin:0 0 0.5rem;">No Notifications Yet</h3>
    <p style="margin:0; font-size:0.9rem;">Notifications will appear here when stock alerts or system events occur.</p>
  </div>

  @if($pageData['notifications']->isNotEmpty())
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
           id="notif-card-{{ $n->id }}"
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
             transition: background 0.3s, opacity 0.3s, transform 0.3s;
             position: relative;
           ">

        {{-- Icon --}}
        <div style="font-size:1.5rem; flex-shrink:0; margin-top:0.15rem;">{{ $icon }}</div>

        {{-- Body --}}
        <div style="flex:1; min-width:0;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.5rem;">
            <div style="font-weight:600; font-size:1rem; color:var(--text-main);">
              {{ $n->title }}
            </div>
            <div style="display:flex; align-items:center; gap:0.75rem;">
              <div style="font-size:0.78rem; color:var(--text-muted); white-space:nowrap;">
                {{ $n->created_at->diffForHumans() }}
                &nbsp;·&nbsp;
                {{ $n->created_at->format('d-m-Y, H:i') }}
              </div>
              @if(empty($isReadOnly))
              <button type="button" 
                      onclick="deleteNotification('{{ $n->id }}', this)" 
                      title="Remove notification"
                      style="background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); color:#ef4444; border-radius:6px; padding:3px 8px; font-size:0.75rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:3px;"
                      onmouseover="this.style.background='#ef4444'; this.style.color='#fff';"
                      onmouseout="this.style.background='rgba(239,68,68,0.08)'; this.style.color='#ef4444';">
                🗑️ Delete
              </button>
              @endif
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
const deleteUrlBase = '{{ url(request()->segment(1) . "/notifications") }}';
const clearUrl = '{{ url(request()->segment(1) . "/notifications/clear") }}';

function deleteNotification(id, btn) {
  if (!confirm('Are you sure you want to remove this notification?')) return;
  
  btn.disabled = true;
  btn.innerHTML = '⏳ Removing...';

  fetch(`${deleteUrlBase}/${id}`, {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json'
    }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      const card = document.getElementById(`notif-card-${id}`) || btn.closest('.notif-card');
      if (card) {
        card.style.opacity = '0';
        card.style.transform = 'scale(0.95)';
        setTimeout(() => {
          card.remove();
          updateNotifCount();
        }, 250);
      }
    } else {
      alert(data.message || 'Failed to delete notification');
      btn.disabled = false;
      btn.innerHTML = '🗑️ Delete';
    }
  })
  .catch(err => {
    console.error(err);
    alert('An error occurred while deleting the notification');
    btn.disabled = false;
    btn.innerHTML = '🗑️ Delete';
  });
}

function clearAllNotifications() {
  if (!confirm('Are you sure you want to delete ALL notifications? This action cannot be undone.')) return;

  fetch(clearUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json'
    }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      location.reload();
    } else {
      alert(data.message || 'Failed to clear notifications');
    }
  })
  .catch(err => {
    console.error(err);
    alert('An error occurred while clearing notifications');
  });
}

function updateNotifCount() {
  const cards = document.querySelectorAll('.notif-card');
  const countEl = document.getElementById('notif-total-count');
  if (countEl) countEl.innerText = cards.length;
  if (cards.length === 0) {
    const list = document.getElementById('notif-list');
    if (list) list.style.display = 'none';
    const none = document.getElementById('notif-none');
    if (none) none.style.display = 'block';
    const clearWrap = document.getElementById('clear-all-wrap');
    if (clearWrap) clearWrap.style.display = 'none';
  }
}

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
  if (empty) empty.style.display = (visible === 0 && cards.length > 0) ? 'block' : 'none';
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
