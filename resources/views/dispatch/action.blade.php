@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
<style>
.custom-location-dropdown {
    width: 100%;
    position: relative;
}
.custom-location-dropdown button {
    width: 100%;
    text-align: left;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff !important;
    border: 1px solid #d1d5db !important;
    padding: 0.65rem 0.75rem;
    font-size: 0.88rem;
    font-weight: 600;
    color: #111827 !important;
    border-radius: 8px;
    cursor: pointer;
}
.custom-location-dropdown ul.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    width: 100%;
    max-height: 260px;
    overflow-y: auto;
    background: #ffffff !important;
    border: 1px solid #d1d5db !important;
    border-radius: 8px;
    list-style: none;
    margin-top: 0.25rem;
    padding: 0.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}
/* Hide spin arrows on number inputs */
input[type=number].no-spinners::-webkit-outer-spin-button,
input[type=number].no-spinners::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number].no-spinners {
  -moz-appearance: textfield;
}

#action-order-search:focus {
  border-color: var(--primary, #D88A00) !important;
  box-shadow: 0 0 0 3px rgba(216, 138, 0, 0.22) !important;
}
.action-search-item:hover {
  background: #f8fafc !important;
}
.action-search-item.active-item {
  background: #f1f5f9 !important;
  border-left: 3px solid var(--primary, #D88A00) !important;
}
html.dark-mode #action-order-search {
  background: rgba(255, 255, 255, 0.05) !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
  color: #ffffff !important;
}
html.dark-mode #action-order-search::placeholder {
  color: #9ca3af !important;
}
html.dark-mode #action-order-search-results {
  background: #1f2937 !important;
  border-color: #374151 !important;
  box-shadow: 0 12px 28px rgba(0,0,0,0.5) !important;
}
html.dark-mode .action-search-item {
  border-bottom-color: rgba(255, 255, 255, 0.06) !important;
}
html.dark-mode .action-search-item:hover {
  background: rgba(255, 255, 255, 0.06) !important;
}
html.dark-mode .action-search-item.active-item {
  background: rgba(255, 255, 255, 0.1) !important;
}
</style>

<div class="card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.04);">
  <div class="card-title" style="color:#111827; font-weight:800;">Dispatch Customer Order</div>
  
  <!-- Order Smart Search Bar -->
  <div class="form-group" style="margin-bottom:1.1rem;" id="action-order-search-group">
    <label style="color:#374151; font-weight:700; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:6px;">
      <span style="display:inline-flex; align-items:center; gap:6px;">
        <span style="color:var(--primary, #D88A00);">🔍</span>
        <span>Search &amp; Select Order</span>
      </span>
      <span style="font-size:0.75rem; color:#64748b; font-weight:500;">Type #ID, Customer, Transporter, or Items</span>
    </label>
    
    <div style="position:relative;" id="action-order-search-container">
      <div style="position:relative; display:flex; align-items:center;">
        <svg style="position:absolute; left:12px; width:18px; height:18px; color:#9ca3af; pointer-events:none;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="action-order-search"
               placeholder="🔍 Search by Order #ID, customer name, transporter, or items..."
               autocomplete="off"
               oninput="onActionOrderSearchInput(this.value)"
               onfocus="onActionOrderSearchFocus()"
               onkeydown="onActionOrderSearchKeydown(event)"
               style="width:100%; padding:0.72rem 2.5rem 0.72rem 2.4rem; font-size:0.92rem; border-radius:8px; border:1.5px solid #d1d5db; background:#ffffff; color:#111827; outline:none; font-weight:600; box-shadow:0 1px 3px rgba(0,0,0,0.03); transition:border-color 0.15s, box-shadow 0.15s;">
        <button type="button" id="action-order-search-clear" onclick="clearActionOrderSearch()" style="display:none; position:absolute; right:10px; background:none; border:none; color:#9ca3af; font-size:1.25rem; cursor:pointer; padding:0 4px; line-height:1;" title="Clear search">
          &times;
        </button>
      </div>

      <!-- Real-time Autocomplete / Match Dropdown List -->
      <div id="action-order-search-results" style="display:none; position:absolute; top:calc(100% + 4px); left:0; right:0; max-height:320px; overflow-y:auto; background:#ffffff; border:1.5px solid #cbd5e1; border-radius:10px; box-shadow:0 12px 28px rgba(0,0,0,0.15); z-index:1050; padding:6px 0;">
      </div>
    </div>
  </div>

  <div class="form-group" style="margin-bottom:1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
      <label style="color:#64748b; font-size:0.8rem; font-weight:600; margin:0;">Or Select from Order Dropdown</label>
      <span id="action-order-count-badge" style="font-size:0.72rem; color:#64748b; font-weight:600;">
        {{ collect($pageData['pendingOrders'] ?? [])->filter(fn($o) => strtoupper(trim((string)($o['status'] ?? ''))) !== 'CANCELLED')->count() }} pending orders
      </span>
    </div>
    <select id="dispatch-order" onchange="onDispatchOrderDropdownChange(this.value)" style="background:#ffffff; border:1.5px solid #d1d5db; color:#111827; padding:0.68rem; border-radius:8px; width:100%; font-weight:600; font-size:0.9rem;">
      <option value="" disabled selected>-- Select Order --</option>
      @foreach(collect($pageData['pendingOrders'] ?? [])->filter(fn($o) => strtoupper(trim((string)($o['status'] ?? ''))) !== 'CANCELLED') as $o)
        @php
          $rTag = ($o['readiness'] ?? '') === 'READY_DISPATCH' ? ' [Ready to Dispatch]' : (($o['readiness'] ?? '') === 'READY_PARTIAL' ? ' [Partial Ready]' : ' [Not Ready]');
        @endphp
        <option value="{{ $o['id'] }}">#{{ strtoupper((string)$o['id']) }} - {{ $o['company']['name'] }}{{ $rTag }}</option>
      @endforeach
    </select>
  </div>
  
  <div id="order-preview" style="display:none; background:#ffffff; border:1px solid #e2e8f0; padding:1.2rem; border-radius:12px; margin-bottom:1.5rem; font-size:0.9rem; box-shadow:0 2px 8px rgba(0,0,0,0.03);"></div>
  
  <div id="dispatch-details" style="display:none;">
    <div class="form-group">
      <label>Override Transporter (Optional)</label>
      <select id="dispatch-transporter">
        <option value="">-- Keep Order Transporter --</option>
        @foreach(\App\Models\Transporter::all() as $t)
          <option value="{{ $t->id }}">{{ $t->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="form-group">
      <label>Driver Contact / Mobile No.</label>
      <div style="display:flex; gap:8px;">
        <select id="dispatch-country-code" onchange="app.onCountryCodeChange('dispatch')" style="width:68px; padding:0.7rem 0.2rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333); font-weight:600; flex-shrink:0; text-align:center; cursor:pointer;">
          <option value="+91" selected>+91</option>
          <option value="+1">+1</option>
          <option value="+44">+44</option>
          <option value="+971">+971</option>
          <option value="+966">+966</option>
          <option value="+61">+61</option>
          <option value="+65">+65</option>
          <option value="+49">+49</option>
          <option value="+33">+33</option>
          <option value="+86">+86</option>
          <option value="+81">+81</option>
          <option value="other">+...</option>
        </select>
        <input type="text" id="dispatch-contact" placeholder="10-digit mobile number" maxlength="10" oninput="app.handleContactInput(this, document.getElementById('dispatch-country-code'))" style="flex:1; padding:0.7rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      </div>
    </div>
    <div class="form-group">
      <label>Vehicle Number</label>
      <input type="text" id="dispatch-vehicle-no" placeholder="e.g. GJ-01-AB-1234 (Optional)" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
    </div>

    <div class="form-group" style="margin-top:1.2rem;">
      <label>LR Copy (Optional)</label>
      <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <button type="button" class="btn btn-sm btn-media-camera" onclick="document.getElementById('dispatch-lr-cam').click()" title="Take LR photo with camera">
          📷 Camera
        </button>
        <button type="button" class="btn btn-sm btn-media-gallery" onclick="document.getElementById('dispatch-lr-file').click()" title="Choose photo from device">
          📁 Choose Photo
        </button>
        <input type="file" id="dispatch-lr-cam" accept="image/*" capture="environment" style="display:none;" onchange="app.handleDispatchLRPick(event)">
        <input type="file" id="dispatch-lr-file" accept="image/*" style="display:none;" onchange="app.handleDispatchLRPick(event)">

        <div id="dispatch-lr-preview-container" style="display:none; align-items:center; gap:12px; margin-top:8px; width:100%; padding:10px 14px; background:#fffbeb; border-radius:10px; border:1.5px solid #fde68a;">
          <img id="lr-preview" src="" style="width:65px; height:65px; object-fit:cover; border-radius:8px; border:2px solid #f59e0b; cursor:pointer;" onclick="app.viewImage(this.src)" title="View Cropped Image">
          <div style="display:flex; flex-direction:column; gap:6px;">
            <span id="dispatch-lr-filename" style="font-size:0.88rem; font-weight:700; color:#1e293b;"></span>
            <div style="display:flex; gap:8px;">
              <button type="button" class="btn btn-sm" onclick="app.recropDispatchLR()" style="width:auto; padding:5px 10px; font-size:0.78rem; background:#0284c7; color:#fff !important; font-weight:600; border:none; border-radius:6px;">✂️ Re-crop</button>
              <button type="button" class="btn btn-sm btn-danger" onclick="app.removeDispatchLR()" style="width:auto; padding:5px 10px; font-size:0.78rem; background:#dc2626; color:#fff !important; border:none; border-radius:6px;">🗑️ Remove</button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
  
  @if(empty($isReadOnly))
  <button class="btn mt-2" onclick="app.submitDispatch()">Dispatch Items</button>
  @endif

</div>

<script>
  window.currentPendingOrders = @json($pageData['pendingOrders'] ?? []);
  let selectedResultIndex = -1;

  function escapeActionHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function renderActionSearchResults(query) {
    const container = document.getElementById('action-order-search-results');
    if (!container) return;

    const orders = window.currentPendingOrders || [];
    const q = (query || '').toLowerCase().trim();
    const tokens = q ? q.split(/\s+/).filter(Boolean) : [];

    const filtered = orders.filter(o => {
      if (String(o.status || '').toUpperCase() === 'CANCELLED') return false;
      if (!tokens.length) return true;

      const itemsStr = (o.items || []).map(i => `${i.productName || i.rawProductName || ''} ${i.grade || ''} ${i.productType || ''}`).join(' ');
      const searchTarget = `${o.id} #${o.id} ${o.company?.name || ''} ${o.transporter?.name || ''} ${o.salesPerson || ''} ${o.notes || ''} ${itemsStr}`.toLowerCase();

      return tokens.every(tok => searchTarget.includes(tok));
    });

    if (filtered.length === 0) {
      container.innerHTML = `
        <div style="padding:14px; text-align:center; color:#64748b; font-size:0.85rem;">
          <div>🔍 No pending orders match "<strong>${escapeActionHtml(query)}</strong>"</div>
          <div style="font-size:0.75rem; margin-top:4px; color:#94a3b8;">Try searching with a different order #, customer, transporter, or item.</div>
        </div>
      `;
      container.style.display = 'block';
      selectedResultIndex = -1;
      return;
    }

    selectedResultIndex = -1;
    const html = filtered.map((o, idx) => {
      const readiness = o.readiness || 'NOT_READY';
      let rBadge = '';
      if (readiness === 'READY_DISPATCH') {
        rBadge = '<span style="background:#16a34a; color:#fff; font-size:0.68rem; font-weight:700; padding:2px 8px; border-radius:4px;">READY TO DISPATCH</span>';
      } else if (readiness === 'READY_PARTIAL') {
        rBadge = '<span style="background:#f59e0b; color:#fff; font-size:0.68rem; font-weight:700; padding:2px 8px; border-radius:4px;">PARTIAL READY</span>';
      } else {
        rBadge = '<span style="background:#dc2626; color:#fff; font-size:0.68rem; font-weight:700; padding:2px 8px; border-radius:4px;">NOT READY</span>';
      }

      const itemsPreview = (o.items || []).map(i => {
        const pName = (i.rawProductName || i.productName || 'Item').replace(/\s*\((FG|SEMI|RAW|FINISHED|PM|PKG)\)$/i, '').trim();
        const grade = (i.grade && i.grade !== 'NONE' && i.grade !== 'N/A') ? ` ${i.grade}` : '';
        const remQty = i.remainingQty ?? (i.quantity - (i.dispatchedQty || 0));
        return `${pName}${grade} (${remQty} kg)`;
      }).join(', ');

      return `
        <div class="action-search-item" data-idx="${idx}" data-order-id="${o.id}"
             onclick="selectActionOrder('${o.id}')"
             style="padding:10px 14px; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:background 0.1s ease;">
          <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:3px;">
            <div style="display:flex; align-items:center; gap:6px;">
              <strong style="color:var(--primary, #D88A00); font-size:0.95rem;">Order #${o.id}</strong>
              <span style="font-weight:700; color:#111827; font-size:0.92rem;">• ${escapeActionHtml(o.company?.name || 'Customer')}</span>
            </div>
            ${rBadge}
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.78rem; color:#64748b; flex-wrap:wrap; gap:8px;">
            <div>
              <span>🚚 ${escapeActionHtml(o.transporter?.name || 'No Transporter')}</span>
              <span style="margin:0 4px;">|</span>
              <span>Sales: <strong>${escapeActionHtml(o.salesPerson || 'N/A')}</strong></span>
            </div>
            ${o.dueDate ? `<span style="color:#0284c7; font-weight:600;">Due: ${escapeActionHtml(o.dueDate)}</span>` : ''}
          </div>
          ${itemsPreview ? `
            <div style="font-size:0.75rem; color:#475569; margin-top:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; background:#f8fafc; padding:3px 8px; border-radius:4px;">
              📦 ${escapeActionHtml(itemsPreview)}
            </div>
          ` : ''}
        </div>
      `;
    }).join('');

    container.innerHTML = html;
    container.style.display = 'block';
  }

  function onActionOrderSearchInput(val) {
    const clearBtn = document.getElementById('action-order-search-clear');
    if (clearBtn) {
      clearBtn.style.display = val ? 'block' : 'none';
    }
    renderActionSearchResults(val);
    filterDropdownOptions(val);
  }

  function onActionOrderSearchFocus() {
    const searchInp = document.getElementById('action-order-search');
    renderActionSearchResults(searchInp ? searchInp.value : '');
  }

  function onActionOrderSearchKeydown(e) {
    const container = document.getElementById('action-order-search-results');
    if (!container || container.style.display === 'none') return;
    const items = container.querySelectorAll('.action-search-item');
    if (!items.length) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      selectedResultIndex = (selectedResultIndex + 1) % items.length;
      updateSelectedResult(items);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      selectedResultIndex = (selectedResultIndex - 1 + items.length) % items.length;
      updateSelectedResult(items);
    } else if (e.key === 'Enter') {
      e.preventDefault();
      const targetIdx = selectedResultIndex >= 0 ? selectedResultIndex : 0;
      const targetItem = items[targetIdx];
      if (targetItem) {
        const orderId = targetItem.getAttribute('data-order-id');
        selectActionOrder(orderId);
      }
    } else if (e.key === 'Escape') {
      container.style.display = 'none';
    }
  }

  function updateSelectedResult(items) {
    items.forEach((item, idx) => {
      if (idx === selectedResultIndex) {
        item.classList.add('active-item');
        item.scrollIntoView({ block: 'nearest' });
      } else {
        item.classList.remove('active-item');
      }
    });
  }

  function selectActionOrder(orderId) {
    if (!orderId) return;
    const select = document.getElementById('dispatch-order');
    if (select) {
      select.value = orderId;
    }

    const orders = window.currentPendingOrders || [];
    const o = orders.find(x => String(x.id) === String(orderId));
    const searchInp = document.getElementById('action-order-search');
    const clearBtn = document.getElementById('action-order-search-clear');
    const resultsContainer = document.getElementById('action-order-search-results');

    if (searchInp) {
      searchInp.value = o ? `#${o.id} - ${o.company?.name || 'Customer'}` : (select ? select.options[select.selectedIndex]?.text : `#${orderId}`);
    }
    if (clearBtn) clearBtn.style.display = 'block';
    if (resultsContainer) resultsContainer.style.display = 'none';

    const appObj = window.app || (typeof app !== 'undefined' ? app : null);
    if (appObj && typeof appObj.onDispatchOrderSelect === 'function') {
      appObj.onDispatchOrderSelect(orderId);
    }
  }

  function onDispatchOrderDropdownChange(val) {
    selectActionOrder(val);
  }

  function clearActionOrderSearch() {
    const searchInp = document.getElementById('action-order-search');
    const clearBtn = document.getElementById('action-order-search-clear');
    const resultsContainer = document.getElementById('action-order-search-results');
    if (searchInp) {
      searchInp.value = '';
      searchInp.focus();
    }
    if (clearBtn) clearBtn.style.display = 'none';
    if (resultsContainer) resultsContainer.style.display = 'none';
    filterDropdownOptions('');
  }

  function filterDropdownOptions(query) {
    const select = document.getElementById('dispatch-order');
    const countBadge = document.getElementById('action-order-count-badge');
    if (!select) return;
    const q = (query || '').toLowerCase().trim();
    const tokens = q ? q.split(/\s+/).filter(Boolean) : [];
    
    let visibleCount = 0;
    for (let i = 0; i < select.options.length; i++) {
      const opt = select.options[i];
      if (opt.value === '') continue; // skip default placeholder
      if (!tokens.length) {
        opt.hidden = false;
        visibleCount++;
        continue;
      }
      const text = opt.text.toLowerCase();
      const match = tokens.every(tok => text.includes(tok));
      opt.hidden = !match;
      if (match) visibleCount++;
    }

    if (countBadge) {
      countBadge.textContent = tokens.length > 0 
        ? `${visibleCount} matching orders` 
        : `${select.options.length - 1} pending orders`;
    }
  }

  function triggerAutoSelect(autoId) {
    if (!autoId) return;
    const select = document.getElementById('dispatch-order');
    if (select) {
      select.value = autoId;
    }
    
    // Update search bar text
    const orders = window.currentPendingOrders || [];
    const o = orders.find(x => String(x.id) === String(autoId));
    const searchInp = document.getElementById('action-order-search');
    const clearBtn = document.getElementById('action-order-search-clear');
    if (searchInp) {
      searchInp.value = o ? `#${o.id} - ${o.company?.name || 'Customer'}` : (select ? select.options[select.selectedIndex]?.text : `#${autoId}`);
      if (clearBtn) clearBtn.style.display = 'block';
    }

    const callHandler = () => {
      const appObj = window.app || (typeof app !== 'undefined' ? app : null);
      if (appObj && typeof appObj.onDispatchOrderSelect === 'function') {
        appObj.onDispatchOrderSelect(autoId);
        return true;
      }
      return false;
    };

    if (!callHandler()) {
      let attempts = 0;
      const timer = setInterval(() => {
        attempts++;
        if (callHandler() || attempts > 30) {
          clearInterval(timer);
        }
      }, 50);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    window.currentPendingOrders = @json($pageData['pendingOrders'] ?? []);
    const appObj = window.app || (typeof app !== 'undefined' ? app : null);
    if (appObj && typeof appObj.removeDispatchLR === 'function') {
      appObj.removeDispatchLR(true);
    }
    
    // Check for auto-select from URL param or localStorage (redirected from home page click)
    const urlParams = new URLSearchParams(window.location.search);
    const autoId = urlParams.get('order_id') || localStorage.getItem('auto_dispatch_id');
    if (autoId) {
      localStorage.removeItem('auto_dispatch_id');
      triggerAutoSelect(autoId);
    }
  });

  window.addEventListener('pageshow', function(event) {
    if (event.persisted || (window.performance && window.performance.navigation && window.performance.navigation.type === 2)) {
      window.location.reload();
    }
  });

  document.addEventListener('click', function(e) {
    if (!e.target.closest('#action-order-search-container')) {
      const resultsContainer = document.getElementById('action-order-search-results');
      if (resultsContainer) resultsContainer.style.display = 'none';
    }
    if (!e.target.closest('.custom-location-dropdown')) {
      document.querySelectorAll('.custom-location-dropdown .dropdown-menu').forEach(menu => {
        menu.style.display = 'none';
      });
    }
  });
</script>
@endsection
