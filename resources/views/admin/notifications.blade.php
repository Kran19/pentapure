@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">

  {{-- ── Header ──────────────────────────────────────── --}}
  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <div>
      <h2 style="margin:0; font-size:1.6rem;">🔔 Notification History</h2>
      <p style="margin:0.3rem 0 0; font-size:0.9rem; color:var(--text-muted);">
        {{ $pageData['totalCount'] }} total &nbsp;·&nbsp;
        <span style="color:var(--danger); font-weight:600;">{{ $pageData['unreadCount'] }} unread</span>
      </p>
    </div>
    @if($pageData['unreadCount'] > 0)
    <button class="btn" id="mark-all-btn" onclick="markAllRead()"
      style="width:auto; padding:0.55rem 1.2rem; font-size:0.9rem; background:var(--primary); color:#fff; border-radius:8px; border:none; cursor:pointer;">
      ✅ Mark All as Read
    </button>
    @endif
  </div>

  {{-- ── Filter Tabs ──────────────────────────────────── --}}
  <div style="display:flex; gap:0.5rem; margin-bottom:1.2rem; flex-wrap:wrap;">
    <button class="notif-tab active" data-filter="all"    onclick="filterNotifs('all',    this)">All</button>
    <button class="notif-tab"        data-filter="unread" onclick="filterNotifs('unread', this)">Unread</button>
    <button class="notif-tab"        data-filter="read"   onclick="filterNotifs('read',   this)">Read</button>
    <button class="notif-tab"        data-filter="warning"    onclick="filterNotifs('warning',    this)">⚠️ Low Stock</button>
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
        $bgColor = $n->is_read ? 'var(--card-bg)' : 'rgba(59,130,246,0.05)';
        $iconMap  = ['warning' => '⚠️', 'danger' => '🔴', 'success' => '✅', 'info' => 'ℹ️'];
        $icon     = $iconMap[$n->type] ?? '🔔';
      @endphp
      <div class="notif-card"
           data-read="{{ $n->is_read ? 'read' : 'unread' }}"
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
            <div style="font-weight:{{ $n->is_read ? '500' : '700' }}; font-size:1rem; color:var(--text-main);">
              {{ $n->title }}
              @if(!$n->is_read)
                <span style="display:inline-block; width:8px; height:8px; background:#3b82f6; border-radius:50%; margin-left:6px; vertical-align:middle;"></span>
              @endif
            </div>
            <div style="font-size:0.78rem; color:var(--text-muted); white-space:nowrap;">
              {{ $n->created_at->diffForHumans() }}
              &nbsp;·&nbsp;
              {{ $n->created_at->format('d M Y, H:i') }}
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

            @if($n->is_read)
              <span style="font-size:0.75rem; color:#22c55e;">
                ✓ Read {{ $n->read_at ? $n->read_at->diffForHumans() : '' }}
              </span>
            @else
              <button onclick="markOneRead('{{ $n->id }}')"
                style="font-size:0.78rem; border:none; background:none; color:#3b82f6; cursor:pointer; padding:0; text-decoration:underline;">
                Mark as read
              </button>
            @endif

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
    const readState = card.dataset.read;  // 'read' | 'unread'
    const type      = card.dataset.type;  // warning, danger, info, success

    let show = false;
    if      (filter === 'all')    show = true;
    else if (filter === 'read')   show = readState === 'read';
    else if (filter === 'unread') show = readState === 'unread';
    else                          show = type === filter;

    card.style.display = show ? 'flex' : 'none';
    if (show) visible++;
  });

  const empty = document.getElementById('notif-empty');
  if (empty) empty.style.display = visible === 0 ? 'block' : 'none';
}

function markOneRead(id) {
  fetch(`/api/notifications/${id}/read`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      const card = document.querySelector(`.notif-card[data-id="${id}"]`);
      if (card) {
        card.dataset.read = 'read';
        card.style.background = 'var(--card-bg)';
        // Remove the blue dot
        const dot = card.querySelector('span[style*="border-radius:50%"]');
        if (dot) dot.remove();
        // Replace "Mark as read" button with read text
        const btn = card.querySelector('button[onclick*="markOneRead"]');
        if (btn) {
          const span = document.createElement('span');
          span.style.cssText = 'font-size:0.75rem; color:#22c55e;';
          span.textContent = '✓ Read just now';
          btn.replaceWith(span);
        }
        // Bold -> normal weight
        const title = card.querySelector('[style*="font-weight"]');
        if (title) title.style.fontWeight = '500';
      }
      // Update unread count in header
      updateUnreadCount(-1);
    }
  });
}

function markAllRead() {
  fetch(window.baseUrl + '/' + window.userSlug + '/api/notifications/read-all', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      location.reload();
    }
  });
}

function updateUnreadCount(delta) {
  const el = document.querySelector('span[style*="color:var(--danger)"]');
  if (!el) return;
  const current = parseInt(el.textContent) || 0;
  const next = Math.max(0, current + delta);
  el.textContent = `${next} unread`;
  if (next === 0) {
    const btn = document.getElementById('mark-all-btn');
    if (btn) btn.style.display = 'none';
  }
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
