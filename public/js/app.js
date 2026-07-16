// Intercept fetch to automatically prepend the base URL if the app is in a subdirectory
const _originalFetch = window.fetch;
window.fetch = function(url, options) {
    if (typeof url === 'string' && url.startsWith('/')) {
        const metaBase = document.querySelector('meta[name="base-url"]');
        if (metaBase && metaBase.content) {
            let base = metaBase.content;
            if (base.endsWith('/')) base = base.slice(0, -1);
            url = base + url;
        }
    }
    return _originalFetch.call(this, url, options);
};

const app = {
  currentUser: null,
  currentView: 'home',
  currentLang: 'en',
  storageLocations: ['Warehouse A', 'Warehouse B', 'Rack 1', 'Cold Room'],

  translations: {
    'en': {
      'Home': 'Home', 'Action': 'Action', 'History': 'History', 'Profile': 'Profile', 'Team': 'Team',
      'Select Role to Login': 'Select Role to Login',
      'Raw Material Overview': 'Raw Material Overview',
      'Semi-Finished Stock': 'Semi-Finished Stock',
      'Finished Goods Stock': 'Finished Goods Stock',
      'Inward': 'Inward', 'Outward': 'Outward', 'Stock': 'Stock',
      'Search product...': 'Search product...',
      'Load More': 'Load More', 'Show Less': 'Show Less',
      'Purchase Orders': 'Purchase Orders', 'Pending': 'Pending', 'Completed': 'Completed'
    },
    'hi': {
      'Home': 'होम', 'Action': 'कार्य', 'History': 'इतिहास', 'Profile': 'प्रोफ़ाइल', 'Team': 'टीम',
      'Select Role to Login': 'लॉगिन करने के लिए भूमिका चुनें',
      'Raw Material Overview': 'कच्चा माल अवलोकन',
      'Semi-Finished Stock': 'अर्ध-निर्मित स्टॉक',
      'Finished Goods Stock': 'तैयार माल स्टॉक',
      'Inward': 'आवक (Inward)', 'Outward': 'जावक (Outward)', 'Stock': 'स्टॉक',
      'Search product...': 'उत्पाद खोजें...',
      'Load More': 'और लोड करें', 'Show Less': 'कम दिखाएं',
      'Purchase Orders': 'खरीद आदेश', 'Pending': 'लंबित', 'Completed': 'पूरा हुआ'
    },
    'gu': {
      'Home': 'હોમ', 'Action': 'ક્રિયા', 'History': 'ઇતિહાસ', 'Profile': 'પ્રોફાઇલ', 'Team': 'ટીમ',
      'Select Role to Login': 'લૉગિન કરવા માટે ભૂમિકા પસંદ કરો',
      'Raw Material Overview': 'કાચો માલ વિહંગાવલોકન',
      'Semi-Finished Stock': 'અર્ધ-તૈયાર સ્ટોક',
      'Finished Goods Stock': 'તૈયાર માલ સ્ટોક',
      'Inward': 'આવક (Inward)', 'Outward': 'જાવક (Outward)', 'Stock': 'સ્ટોક',
      'Search product...': 'ઉત્પાદન શોધો...',
      'Load More': 'વધુ લોડ કરો', 'Show Less': 'ઓછું બતાવો',
      'Purchase Orders': 'ખરીદી ઓર્ડર', 'Pending': 'પેન્ડિંગ', 'Completed': 'પૂર્ણ'
    }
  },

  t(text) {
    return this.translations[this.currentLang][text] || text;
  },

  // Format ISO date string in IST (UTC+5:30) matching server-side Carbon format: "17 Jun 2026, 05:42 PM"
  formatDateIST(isoString) {
    if (!isoString) return 'N/A';
    try {
      const d = new Date(isoString);
      // IST offset is +5:30 = 330 minutes
      const istOffset = 330;
      const localOffset = d.getTimezoneOffset(); // minutes behind UTC
      const istDate = new Date(d.getTime() + (istOffset + localOffset) * 60000);
      const day = String(istDate.getDate()).padStart(2, '0');
      const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      const month = months[istDate.getMonth()];
      const year = istDate.getFullYear();
      let hour = istDate.getHours();
      const min = String(istDate.getMinutes()).padStart(2, '0');
      const ampm = hour >= 12 ? 'PM' : 'AM';
      hour = hour % 12 || 12;
      return `${day} ${month} ${year}, ${String(hour).padStart(2,'0')}:${min} ${ampm}`;
    } catch(e) {
      return isoString;
    }
  },

  setLanguage(lang) {
    this.currentLang = lang;
    localStorage.setItem('pentapure_lang', lang);
    this.refreshAppTranslatables();
    this.refreshCurrentView();
  },

  refreshAppTranslatables() {
    const els = document.querySelectorAll('.bottom-nav .nav-item span');
    if(els.length >= 4) {
      els[0].innerText = this.t('Home');
      els[1].innerText = this.t('Action');
      els[2].innerText = this.t('History');
      
      if (els.length === 5) {
        els[3].innerText = this.t('Team');
        els[4].innerText = this.t('Profile');
      } else {
        els[3].innerText = this.t('Profile');
      }
    }
  },

  togglePassword(id) {
    const input = document.getElementById(id);
    if (!input) return;
    const btn = input.nextElementSibling;
    if (input.type === 'password') {
      input.type = 'text';
      btn.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;
    } else {
      input.type = 'password';
      btn.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    }
  },

  init() {
    const lang = localStorage.getItem('pentapure_lang') || 'en';
    this.currentLang = lang;
    
    window.currentDateRange = 'this_week';
    window.customStart = '';
    window.customEnd = '';
    window.rawHomeTab = 'stock';

    this.fetchLocations();

    this.fetchNotifications();
    setInterval(() => this.fetchNotifications(), 10000);

    const splash = document.getElementById('splash-screen');
    if (splash) {
      splash.style.opacity = '0';
      setTimeout(() => splash.remove(), 500);
    }

    this.registerServiceWorker();
  },

  fetchLocations() {
    fetch('/api/locations')
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          this.storageLocations = data.locations.map(l => l.name);
        }
      })
      .catch(e => console.error("Failed to fetch locations", e));
  },

  fetchJson(url, options = {}) {
    const headers = {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(options.headers || {})
    };

    return fetch(url, { ...options, headers }).then(async response => {
      const contentType = response.headers.get('content-type') || '';
      if (!response.ok) throw new Error(`Request failed (${response.status})`);
      if (!contentType.includes('application/json')) {
        throw new Error('Server returned HTML instead of JSON. Please refresh and login again.');
      }
      return response.json();
    });
  },

  toast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = '';
    if(type === 'success') icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>';
    else if(type === 'error') icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
    else icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';

    toast.innerHTML = `${icon} <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.animation = 'fadeOut 0.3s forwards';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  },

  exportHistoryPdf(btn, url) {
    if (!url) return;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span style="display:inline-flex;align-items:center;gap:6px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
      Generating...
    </span>`;

    // Direct window location change is the most reliable way to trigger a file download 
    // without navigating away when Content-Disposition: attachment is returned.
    window.location.href = url;

    // After 4s assume download started/completed, re-enable button
    setTimeout(() => {
      btn.disabled = false;
      btn.innerHTML = orig;
      this.toast('📄 PDF downloaded successfully!', 'success');
    }, 4000);
  },

  openModal(html) {
    const overlay = document.getElementById('modal-overlay');
    const content = document.getElementById('modal-content');
    if (content && overlay) {
      content.innerHTML = html;
      overlay.classList.add('active');
    }
  },

  closeModal() {
    const overlay = document.getElementById('modal-overlay');
    if (overlay) overlay.classList.remove('active');
  },

  openDrawer(htmlOrView) {
    const container = document.getElementById('drawer-content');
    const overlay = document.getElementById('bottom-drawer-overlay');
    if (!container || !overlay) return;
    if (htmlOrView === 'notifications') {
      this.renderNotifications(container);
    } else {
      container.innerHTML = htmlOrView;
    }
    overlay.classList.add('active');
  },

  closeDrawer() {
    const overlay = document.getElementById('bottom-drawer-overlay');
    if (overlay) overlay.classList.remove('active');
  },

  fetchNotifications() {
    fetch('/api/notifications')
      .then(r => r.json())
      .then(d => {
        const oldLen = (this.notifications || []).length;
        const newNotifs = d.notifications || [];
        
        this.notifications = newNotifs;
        this.updateNotifBadge();
        
        if (oldLen !== undefined && newNotifs.length > oldLen) {
          const diff = newNotifs.length - oldLen;
          this.toast(`You have ${diff} new notification${diff > 1 ? 's' : ''}`, 'info');
        }
      })
      .catch(() => {});
  },

  updateNotifBadge() {
    const badge = document.getElementById('notif-badge');
    const adminBadge = document.getElementById('nav-notif-count');
    const count = (this.notifications || []).length;
    
    [badge, adminBadge].forEach(el => {
      if (!el) return;
      if (count > 0) {
        el.innerText = count;
        el.style.display = 'inline-block';
      } else {
        el.style.display = 'none';
      }
    });
  },

  toggleNotifications() {
    this.notifications = this.notifications || [];
    if (this.notifications.length === 0) {
      this.toast('No new notifications', 'info');
      return;
    }
    this.openDrawer('notifications');
  },

  renderNotifications(container) {
    if ((this.notifications || []).length === 0) {
      container.innerHTML = `<div style="padding:2.5rem 1rem; text-align:center; color:var(--text-muted);">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom:1rem; opacity:0.5;">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        <p>No new notifications</p>
      </div>`;
      return;
    }
    
    container.innerHTML = `
      <div style="padding:1rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
          <h3 style="margin:0; font-size:1.3rem;">Notifications</h3>
          <button class="btn btn-secondary" onclick="app.markAllNotificationsRead()" style="width:auto; padding:0.4rem 0.8rem; font-size:0.8rem;">Mark all as read</button>
        </div>
        <div style="display:grid; gap:0.85rem;">
          ${this.notifications.map(n => `
            <div class="card" style="padding:1rem; border-left:4px solid var(--${n.type || 'info'}); position:relative; background:var(--card-bg);">
              <div style="font-weight:bold; margin-bottom:6px; display:flex; justify-content:space-between; padding-right:20px; font-size:1rem;">
                ${n.title}
                <span style="font-size:0.75rem; color:var(--text-muted); font-weight:normal;">${n.created_at}</span>
              </div>
              <div style="font-size:0.9rem; color:var(--text-main); line-height:1.4;">${n.message}</div>
              <button onclick="app.markNotificationRead('${n.id}')" style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.05); border:none; color:var(--text-muted); cursor:pointer; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">&times;</button>
            </div>
          `).join('')}
        </div>
      </div>
    `;
  },

  markNotificationRead(id) {
    fetch(`/api/notifications/${id}/read`, { method: 'POST', headers: { 'X-CSRF-TOKEN': window.csrfToken } })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          this.notifications = this.notifications.filter(n => n.id !== id);
          this.updateNotifBadge();
          const container = document.getElementById('drawer-content');
          const overlay = document.getElementById('bottom-drawer-overlay');
          if (container && overlay && overlay.classList.contains('active')) {
            this.renderNotifications(container);
          }
          if (this.notifications.length === 0) this.closeDrawer();
        }
      });
  },

  markAllNotificationsRead() {
    fetch('/api/notifications/read-all', { method: 'POST', headers: { 'X-CSRF-TOKEN': window.csrfToken } })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          this.notifications = [];
          this.updateNotifBadge();
          this.closeDrawer();
        }
      });
  },

  logout() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.logoutUrl || '/logout';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    form.appendChild(csrfInput);
    document.body.appendChild(form);
    
    this.currentUser = null;
    form.submit();
  },

  navigate(view) {
    const role = (this.currentUser?.role || 'admin').toLowerCase();
    if (view.startsWith(role + '/')) {
      window.location.href = `/${view}`;
    } else {
      window.location.href = `/${role}/${view}`;
    }
  },

  refreshCurrentView() {
    console.log("refreshCurrentView() - Server-rendered views handle layout changes.");
  },

  submitPO(btn) {
    const matId = document.getElementById('po-material').value;
    const qty   = Number(document.getElementById('po-qty').value);
    const note  = document.getElementById('po-note').value;

    if (!matId) return this.toast('Select a material', 'error');
    if (!qty || qty <= 0) return this.toast('Enter valid quantity', 'error');

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin" style="vertical-align: middle; margin-right:5px;"><circle cx="12" cy="12" r="10" opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"></path></svg> Sending...`;
    }

    const rolePrefix = this.currentUser.role.toLowerCase();
    fetch(`/${rolePrefix}/po`, {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.csrfToken || csrfToken 
      },
      body: JSON.stringify({ product_id: matId, quantity: qty, note })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        this.toast(data.message || 'Request sent!');
        setTimeout(() => location.reload(), 600);
      } else {
        this.toast(data.message || 'Error', 'error');
        if (btn) { btn.disabled = false; btn.innerText = 'Send Request to Admin'; }
      }
    })
    .catch(() => {
      this.toast('Network error.', 'error');
      if (btn) { btn.disabled = false; btn.innerText = 'Send Request to Admin'; }
    });
  },

  onFinishedOutputSelected() {
    try {
      const productId = document.getElementById('finished-output-id').value;
      const allProds = (window.serverPageData && window.serverPageData.products) || [];
      const p = allProds.find(x => x.id == productId);
      
      const gradeSelect = document.getElementById('finished-grade');
      const gradeGroup = document.getElementById('finished-grade-selection-group');
      
      if (p && p.gradeNames && p.gradeNames.length > 0) {
        gradeSelect.innerHTML = `<option value="" disabled selected>-- Select Grade --</option>` + 
          p.gradeNames.map(g => `<option value="${g}">${g}</option>`).join('') + 
          (p.gradeNames.includes('N/A') ? '' : `<option value="N/A">N/A</option>`);
        gradeGroup.classList.remove('hidden');
      } else {
        gradeGroup.classList.add('hidden');
      }
    } catch (err) {
      console.error("Error in onFinishedOutputSelected:", err);
      this.toast("JS Error: " + err.message, "error");
    }
  },

  reviewFinishedProduction() {
    const outProdId = document.getElementById('finished-output-id').value;
    const outGrade = document.getElementById('finished-grade') ? document.getElementById('finished-grade').value : 'NONE';
    const outQty = Number(document.getElementById('finished-out-qty').value);
    const notes = document.getElementById('finished-notes').value;
    const loc = document.getElementById('finished-storage-location').value;

    if (!outProdId) return this.toast('Select output product', 'error');
    if (!outQty || outQty <= 0) return this.toast('Enter valid output quantity', 'error');

    const inputs = [];
    let validationFailed = false;

    document.querySelectorAll('#input-rows .dynamic-row').forEach(row => {
      const selectEl = row.querySelector('.prod-in-id');
      const val = selectEl.value;
      const qty = Number(row.querySelector('.prod-in-qty').value);
      
      if (val && qty > 0) {
        const [id, grade] = val.split('|');
        const option = selectEl.options[selectEl.selectedIndex];
        const available = Number(option.dataset.max) || 0;
        
        if (qty > available) {
          const pName = option.text;
          this.toast(`Not enough stock for ${pName}. Max: ${available}`, 'error');
          validationFailed = true;
        }
        inputs.push({ productId: id, grade: grade, quantity: qty, name: option.text.split('(')[0].trim() });
      }
    });

    if (validationFailed) return;
    if (inputs.length === 0) return this.toast('Add at least one consumed material', 'error');

    window.tempFinishedProductionData = { outProdId, outGrade, outQty, notes, loc, inputs };
    const outProdName = ((window.serverPageData && window.serverPageData.products) || []).find(x => x.id == outProdId)?.name;
    
    this.openDrawer(`
      <h3 style="margin-bottom:1rem; color:var(--secondary);">Review Finished Production</h3>
      <div style="background:rgba(255,255,255,0.05); padding:1rem; border-radius:10px; margin-bottom:1rem; border:1px solid var(--glass-border);">
        <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:4px;">Target Output</div>
        <div style="font-weight:700; font-size:1.1rem; color:var(--text-main);">${outQty} kg of ${outProdName}</div>
        <div style="font-size:0.85rem; margin-top:2px;">Grade: <span class="badge badge-info">${outGrade}</span></div>
      </div>
      
      <div style="font-size:0.9rem; font-weight:600; margin-bottom:0.8rem; color:var(--primary-light);">Semi-Finished Materials to Consume:</div>
      <ul style="list-style:none; padding:0; margin:0 0 1.5rem 0;">
        ${inputs.map(inp => `
          <li style="display:flex; justify-content:space-between; padding:0.6rem 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.9rem;">
            <span>${inp.name} <span style="font-size:0.75rem; color:var(--text-muted);">(${inp.grade})</span></span>
            <span style="font-weight:600; color:var(--danger);">- ${inp.quantity} kg</span>
          </li>
        `).join('')}
      </ul>
      
      <div style="display:flex; gap:10px;">
        <button class="btn btn-secondary" style="flex:1;" onclick="app.closeDrawer()">Cancel</button>
        <button class="btn" style="flex:2;" onclick="app.confirmFinishedProduction(this)">Confirm & Process</button>
      </div>
    `);
  },

  confirmFinishedProduction(btn) {
    const data = window.tempFinishedProductionData;
    if (!data) return;

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin"><circle cx="12" cy="12" r="10" opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"></path></svg> Processing...`;
    }

    const payload = {
      output_product_id: data.outProdId,
      output_grade:      data.outGrade,
      output_qty:        data.outQty,
      location:          data.loc,
      notes:             data.notes,
      inputs: data.inputs.map(inp => ({
        product_id: inp.productId,
        grade:      inp.grade,
        quantity:   inp.quantity
      }))
    };

    fetch('/finished/action', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.csrfToken || csrfToken 
      },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        this.toast(res.message || 'Finished Production logged successfully!');
        window.tempFinishedProductionData = null;
        this.closeDrawer();
        document.getElementById('finished-out-qty').value = '';
        document.getElementById('finished-notes').value = '';
        document.querySelectorAll('.prod-in-qty').forEach(el => el.value = '');
        if (btn) { btn.disabled = false; btn.innerHTML = `Confirm Production`; }
      } else {
        this.toast(res.message || 'Error logging production', 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = `Confirm Production`; }
      }
    })
    .catch(() => {
      this.toast('Network error. Try again.', 'error');
      if (btn) { btn.disabled = false; btn.innerHTML = `Confirm Production`; }
    });
  },

  onTargetProductSelected() {
    const productId = document.getElementById('prod-output').value;
    const p = ((window.serverPageData && window.serverPageData.products) || []).find(x => x.id == productId);
    
    const gradeSelect = document.getElementById('prod-grade');
    const gradeGroup = document.getElementById('grade-selection-group');
    
    if (p && p.gradeNames && p.gradeNames.length > 0) {
      gradeSelect.innerHTML = `<option value="" disabled selected>-- Select Grade --</option>` + 
        p.gradeNames.map(g => `<option value="${g}">${g}</option>`).join('') + 
        (p.gradeNames.includes('N/A') ? '' : `<option value="N/A">N/A</option>`);
      gradeGroup.classList.remove('hidden');
      document.getElementById('materials-section').classList.add('hidden');
    } else {
      gradeGroup.classList.add('hidden');
      document.getElementById('materials-section').classList.remove('hidden');
      if(document.getElementById('input-rows').children.length === 0) {
        this.addInputRow();
      }
    }
  },

  onGradeSelected() {
    document.getElementById('materials-section').classList.remove('hidden');
    if(document.getElementById('input-rows').children.length === 0) {
      this.addInputRow();
    }
  },

  addInputRow() {
    // Group stock by product (sum across grades since raw has no meaningful grade for display)
    const stockItems = window.currentAvailableInputStock || [];
    const productMap = {};
    stockItems.forEach(s => {
      // Use grade and stage as part of the unique key so they don't combine incorrectly
      const key = `${s.id}_${s.grade}_${s.stage}`;
      if (!productMap[key]) {
        productMap[key] = { id: s.id, name: s.name, grade: s.grade, stage: s.stage, quantity: parseFloat(s.quantity) };
      } else {
        productMap[key].quantity += parseFloat(s.quantity);
      }
    });
    const products = Object.values(productMap);

    const div = document.createElement('div');
    div.className = 'dynamic-row';
    div.style.display = 'flex';
    div.style.flexDirection = 'column';
    div.style.gap = '12px';
    div.style.alignItems = 'stretch';
    div.style.position = 'relative';
    div.style.background = 'rgba(255,255,255,0.05)';
    div.style.padding = '12px';
    div.style.borderRadius = '12px';
    
    div.innerHTML = `
      <div class="form-group" style="margin-bottom:0;">
        <label style="font-size:0.75rem; margin-bottom:4px;">Material</label>
        <select class="prod-in-id" onchange="app.validateRowStock(this)" style="padding:0.75rem;">
          <option value="" disabled selected>Select Material</option>
          ${products.map(s => {
            const displayStage = s.stage === 'FINISHED' ? 'FG' : (s.stage ? s.stage.toLowerCase() : 'raw');
            const displayGrade = s.grade || 'N/A';
            return `<option value="${s.id}|${s.grade}" data-max="${s.quantity}">${s.name} - (grade- ${displayGrade}) (type - ${displayStage}) &mdash; Available: ${parseFloat(s.quantity).toFixed(2)} kg</option>`;
          }).join('')}
        </select>
        <div class="stock-hint" style="font-size:0.75rem; color:var(--secondary); margin-top:4px; font-weight:600; min-height:12px;"></div>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label style="font-size:0.75rem; margin-bottom:4px;">Qty (kg)</label>
        <input type="number" class="prod-in-qty" placeholder="Enter quantity" style="padding:0.75rem;">
      </div>
      <button class="btn btn-danger btn-sm" style="position:absolute; top:8px; right:8px; width:32px; height:32px; padding:0; border-radius:50%; display:flex; align-items:center; justify-content:center;" onclick="this.parentElement.remove()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    `;
    document.getElementById('input-rows').appendChild(div);
  },

  validateRowStock(selectEl) {
    const option = selectEl.options[selectEl.selectedIndex];
    const available = Number(option.dataset.max) || 0;
    
    const hint = selectEl.parentElement.querySelector('.stock-hint');
    if (hint) {
      hint.innerText = available > 0 ? `✓ ${available.toFixed(2)} kg available` : '⚠ No stock available';
      if(available === 0) hint.style.color = 'var(--danger)';
      else hint.style.color = 'var(--secondary)';
    }
  },

  reviewProduction(inputType, outputType, inputStockKey) {
    const outProdId = document.getElementById('prod-output').value;
    const gradeEl = document.getElementById('prod-grade');
    const gradeGroup = document.getElementById('grade-selection-group');
    const gradeHidden = gradeGroup && gradeGroup.classList.contains('hidden');
    const outGrade = gradeHidden ? 'N/A' : (gradeEl ? gradeEl.value : 'N/A');
    const outQty = Number(document.getElementById('prod-out-qty').value);
    
    if (!outProdId) return this.toast('Select target product', 'error');
    if (!gradeHidden && !outGrade) return this.toast('Select grade', 'error');
    if (!outQty || outQty <= 0) return this.toast('Enter valid output quantity', 'error');

    const inputs = [];
    let validationFailed = false;

    document.querySelectorAll('#input-rows .dynamic-row').forEach(row => {
      const selectEl = row.querySelector('.prod-in-id');
      const val = selectEl.value;
      const qty = Number(row.querySelector('.prod-in-qty').value);
      
      if (val && qty > 0) {
        const [id, grade] = val.split('|');
        const option = selectEl.options[selectEl.selectedIndex];
        const available = Number(option.dataset.max) || 0;
        
        if (qty > available) {
          const pName = option.text;
          this.toast(`Not enough stock for ${pName}. Max: ${available}`, 'error');
          validationFailed = true;
        }
        // Extract just the product name (before ' - (grade-')
        const rawName = option.text.split(' - (grade-')[0].trim();
        inputs.push({ productId: id, grade: grade, quantity: qty, name: rawName });
      }
    });

    if (validationFailed) return;
    if (inputs.length === 0) return this.toast('Add at least one consumed material', 'error');

    const loc = document.getElementById('semi-storage-location').value;

    window.tempProductionData = { inputType, outputType, inputStockKey, outProdId, outGrade, outQty, inputs, loc };
    const outProdName = ((window.serverPageData && window.serverPageData.products) || []).find(x => x.id == outProdId)?.name;
    
    this.openDrawer(`
      <h3 style="margin-bottom:1rem; color:var(--warning);">Review Production</h3>
      <div style="background:rgba(255,255,255,0.05); padding:1rem; border-radius:10px; margin-bottom:1rem; border:1px solid var(--glass-border);">
        <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:4px;">Target Output</div>
        <div style="font-weight:700; font-size:1.1rem; color:var(--text-main);">${outQty} kg of ${outProdName} - (grade- ${outGrade || 'N/A'}) (type - ${outputType === 'FINISHED' ? 'FG' : outputType.toLowerCase()})</div>
      </div>
      
      <div style="font-size:0.9rem; font-weight:600; margin-bottom:0.8rem; color:var(--primary-light);">Materials to Consume:</div>
      <ul style="list-style:none; padding:0; margin:0 0 1.5rem 0;">
        ${inputs.map(inp => `
          <li style="display:flex; justify-content:space-between; padding:0.6rem 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.9rem;">
            <span>${inp.name}</span>
            <span style="font-weight:600; color:var(--danger);">- ${inp.quantity} kg</span>
          </li>
        `).join('')}
      </ul>
      
      <div style="display:flex; gap:10px;">
        <button class="btn btn-secondary" style="flex:1;" onclick="app.closeDrawer()">Cancel</button>
        <button class="btn" style="flex:2;" onclick="app.confirmProduction(this)">Confirm & Process</button>
      </div>
    `);
  },

  confirmProduction(btn) {
    const data = window.tempProductionData;
    if (!data) return;

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin"><circle cx="12" cy="12" r="10" opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"></path></svg> Processing...`;
    }

    const endpoint = data.outputType === 'SEMI' ? '/semi/action' : '/finished/action';
    const payload  = {
      output_product_id: data.outProdId,
      output_grade:      data.outGrade,
      output_qty:        data.outQty,
      location:          data.loc,
      inputs: data.inputs.map(inp => ({
        product_id: inp.productId,
        grade:      inp.grade,
        quantity:   inp.quantity
      }))
    };

    fetch(endpoint, {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.csrfToken || csrfToken 
      },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        this.toast(res.message || `${data.outputType} Production logged!`);
        window.tempProductionData = null;
        setTimeout(() => location.reload(), 1000);
      } else {
        this.toast(res.message || 'Error confirming production', 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = `Confirm & Process`; }
      }
    })
    .catch(() => {
      this.toast('Network error. Try again.', 'error');
      if (btn) { btn.disabled = false; btn.innerHTML = `Confirm & Process`; }
    });
  },

  showQuickProductModal(defaultType = 'FINISHED') {
    this.openModal(`
      <div class="card" style="margin:0;">
        <div class="card-title">Quick Create Product</div>
        <div class="form-group">
          <label>Product Name</label>
          <input type="text" id="quick-p-name" placeholder="Enter product name">
        </div>
        <div class="form-group">
          <label>Type</label>
          <select id="quick-p-type">
            <option value="FINISHED" ${defaultType==='FINISHED'?'selected':''}>FG</option>
            <option value="SEMI" ${defaultType==='SEMI'?'selected':''}>SEMI</option>
            <option value="RAW" ${defaultType==='RAW'?'selected':''}>RAW</option>
          </select>
        </div>
        <div class="form-group">
          <label>Unit</label>
          <input type="text" id="quick-p-unit" value="kg">
        </div>
        <div style="display:flex; gap:10px; margin-top:1.5rem;">
          <button class="btn btn-secondary" style="flex:1;" onclick="app.closeModal()">Cancel</button>
          <button class="btn" style="flex:2;" onclick="app.submitQuickProduct()">Create & Select</button>
        </div>
      </div>
    `);
  },

  submitQuickProduct() {
    const name = document.getElementById('quick-p-name').value;
    const type = document.getElementById('quick-p-type').value;
    const unit = document.getElementById('quick-p-unit').value;

    if (!name) return this.toast('Name is required', 'error');

    const rolePrefix = this.currentUser.role.toLowerCase();
    const endpoint = rolePrefix === 'admin' ? '/admin/products' : `/finished/quick-product`;

    fetch(endpoint, {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.csrfToken || csrfToken 
      },
      body: JSON.stringify({ name, type, unit })
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        this.toast(res.message || 'Product created!');
        this.closeModal();
        
        if (res.product) {
          const allProds = (window.serverPageData && window.serverPageData.products) || [];
          allProds.push(res.product);
          allProds.sort((a,b) => (a.name||'').localeCompare(b.name||''));
          DB.set('products', allProds);
          
          if (window.currentFinProds) {
            window.currentFinProds.push(res.product);
            window.currentFinProds.sort((a,b) => (a.name||'').localeCompare(b.name||''));
          }

          const selects = document.querySelectorAll('select');
          selects.forEach(sel => {
            if (sel.id === 'finished-output-id' || sel.id === 'prod-output' || sel.classList.contains('o-prod-id') || sel.classList.contains('prod-in-id') || sel.id === 'finished-input-id') {
              const opt = document.createElement('option');
              opt.value = res.product.id;
              const displayType = res.product.type === 'FINISHED' ? 'FG' : (res.product.type ? res.product.type.toLowerCase() : '');
              opt.text = `${res.product.name} (type - ${displayType})`;
              sel.appendChild(opt);
              if (sel.id === 'finished-output-id' || sel.id === 'prod-output' || sel.classList.contains('o-prod-id')) {
                sel.value = res.product.id;
              }
            }
          });
        }
      } else {
        this.toast(res.message || 'Error creating product', 'error');
      }
    })
    .catch(() => this.toast('Network error.', 'error'));
  },

  onSalesCompanySelect(id) {
    const comp = ((window.serverPageData && window.serverPageData.companies) || []).find(c => c.id == id);
    const div = document.getElementById('company-details');
    if(comp && div) {
      div.style.display = 'block';
      div.classList.add('animation-fadeIn');
      div.innerHTML = `
        <div style="display:grid; grid-template-columns:1fr; gap:4px;">
          <div><span style="color:var(--primary-light); font-weight:600;">GST:</span> ${comp.gst||'N/A'}</div>
          <div><span style="color:var(--primary-light); font-weight:600;">Contact:</span> ${comp.contact||'N/A'}</div>
          <div><span style="color:var(--primary-light); font-weight:600;">Address:</span> ${comp.address||'N/A'}</div>
        </div>
      `;
    } else if (div) {
      div.style.display = 'none';
    }
  },

  onSalesTransportSelect(id) {
    const trans = ((window.serverPageData && window.serverPageData.transportCompanies) || []).find(t => t.id == id);
    const div = document.getElementById('transport-details');
    if(trans && div) {
      div.style.display = 'block';
      div.classList.add('animation-fadeIn');
      div.innerHTML = `
        <div style="display:grid; grid-template-columns:1fr; gap:4px;">
          <div><span style="color:var(--primary-light); font-weight:600;">GST:</span> ${trans.gst||'N/A'}</div>
          <div><span style="color:var(--primary-light); font-weight:600;">Contact:</span> ${trans.contact||'N/A'}</div>
          <div><span style="color:var(--primary-light); font-weight:600;">Vehicles:</span> ${trans.vehicles||'N/A'}</div>
        </div>
      `;
    } else if (div) {
      div.style.display = 'none';
    }
  },

  onOrderTypeSelect(type) {
    window.currentOrderType = type;
    const allProds = (window.serverPageData && window.serverPageData.products) || [];
    if (type === 'ALL') {
      window.currentFinProds = allProds;
    } else if (type === 'SEMI' || type === 'FINISHED') {
      window.currentFinProds = allProds.filter(p => p.type === 'SEMI' || p.type === 'FINISHED');
    } else {
      window.currentFinProds = allProds.filter(p => p.type === type);
    }
    
    const section = document.getElementById('order-products-section');
    if (section) {
      section.style.display = 'block';
      section.classList.add('animation-fadeIn');
    }
    
    const prodList = document.getElementById('order-products');
    if(prodList) {
      prodList.innerHTML = '';
      this.addOrderProductRow();
    }
  },

  addOrderProductRow(prefillData = null) {
    const finProds = window.currentFinProds || [];
    const allGrades = (window.serverPageData && window.serverPageData.grades) || [];
    const div = document.createElement('div');
    div.className = 'dynamic-row';
    
    const selectedProdId = prefillData ? prefillData.product_id : '';
    const selectedGrade = prefillData ? prefillData.grade : '';
    const qty = prefillData ? prefillData.quantity : '';
    const price = prefillData ? prefillData.price : '';
    const itemId = prefillData ? prefillData.id : '';
    
    div.innerHTML = `
      ${itemId ? `<input type="hidden" class="o-item-id" value="${itemId}">` : ''}
      <div class="form-group" style="flex:1 1 100%;">
        <select class="o-prod-id" style="width:100%;" onchange="app.onSalesProductSelect(this)">
          <option value="" disabled ${!selectedProdId ? 'selected' : ''}>Product</option>
          ${finProds.map(p => {
            const displayType = p.type === 'FINISHED' ? 'FG' : (p.type ? p.type.toLowerCase() : '');
            return `<option value="${p.id}" ${p.id == selectedProdId ? 'selected' : ''}>${p.name} - (grade- N/A) (type - ${displayType})</option>`;
          }).join('')}
        </select>
      </div>
      <div class="form-group grade-container" style="flex:1 1 30%;${prefillData && prefillData.product && prefillData.product.type === 'RAW' ? ' display:none;' : ''}">
        <select class="o-prod-grade" style="width:100%;">
          <option value="" disabled ${!selectedGrade ? 'selected' : ''}>Grade</option>
          ${allGrades.map(g => `<option value="${g}" ${g === selectedGrade ? 'selected' : ''}>${g}</option>`).join('')}
          ${allGrades.includes('N/A') ? '' : `<option value="N/A" ${selectedGrade === 'N/A' ? 'selected' : ''}>N/A</option>`}
        </select>
      </div>
      <div class="form-group" style="flex:1 1 25%;">
        <input type="number" class="o-prod-qty" placeholder="Qty" value="${qty}">
      </div>
      <div class="form-group" style="flex:1 1 25%;">
        <input type="number" class="o-prod-price" placeholder="₹/Unit" value="${price}">
      </div>
      <button class="btn btn-danger" style="flex:0 0 36px; height:36px; padding:0; display:flex; align-items:center; justify-content:center;" onclick="this.parentElement.remove()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    `;
    const container = document.getElementById('order-products');
    if (container) container.appendChild(div);
  },

  onSalesProductSelect(selectEl) {
    const prodId = selectEl.value;
    const allProds = (window.serverPageData && window.serverPageData.products) || [];
    const prod = allProds.find(p => p.id == prodId);
    if (!prod) return;
    
    const row = selectEl.closest('.dynamic-row');
    const gradeContainer = row.querySelector('.grade-container');
    const gradeSelect = row.querySelector('.o-prod-grade');
    const allGrades = (window.serverPageData && window.serverPageData.grades) || [];
    const currentVal = gradeSelect.value;
    
    if (prod.type === 'RAW') {
      gradeContainer.style.display = 'none';
      
      let html = `<option value="" disabled>Grade</option>`;
      html += `<option value="N/A" selected>N/A</option>`;
      gradeSelect.innerHTML = html;
      gradeSelect.value = 'N/A';
    } else {
      gradeContainer.style.display = 'block';
      let validGrades = prod.grades && prod.grades.length > 0 ? prod.grades : allGrades;
      let html = `<option value="" disabled>Grade</option>`;
      
      let foundCurrent = false;
      validGrades.forEach(g => {
         if (g === currentVal && currentVal !== 'N/A') foundCurrent = true;
         html += `<option value="${g}">${g}</option>`;
      });
      
      // If we don't have valid grades or N/A is globally allowed and we have no other options
      if (allGrades.includes('N/A') && !validGrades.includes('N/A')) {
          html += `<option value="N/A">N/A</option>`;
          if (currentVal === 'N/A') foundCurrent = true;
      }

      gradeSelect.innerHTML = html;

      if (foundCurrent) {
        gradeSelect.value = currentVal;
      } else {
        gradeSelect.value = gradeSelect.options[1] ? gradeSelect.options[1].value : '';
      }
    }
  },

  submitCompany() {
    const name    = document.getElementById('comp-name').value;
    const gst     = document.getElementById('comp-gst').value;
    const address = document.getElementById('comp-address').value;
    const contact = document.getElementById('comp-contact').value;
    
    if (!name || !address || !contact) return this.toast('Name, Address and Contact are required', 'error');

    if(gst && gst.toUpperCase() !== 'N/A' && !/^[A-Za-z0-9]{15}$/.test(gst)) return this.toast('GST must be 15 alphanumeric characters or N/A', 'error');
    if(!/^(\+91\s*)?[0-9]{10}$/.test(contact)) return this.toast('Mobile number must be 10 digits', 'error');

    const editIdEl = document.getElementById('edit-comp-id');
    const isEdit = editIdEl && editIdEl.value;

    const exists = ((window.serverPageData && window.serverPageData.companies) || []).find(c => c.id != (isEdit || 0) && (c.name.toLowerCase() === name.toLowerCase() || (gst && gst.toUpperCase() !== 'N/A' && c.gst === gst)));
    if(exists) return this.toast('Company with this name or GST already exists', 'error');

    const url = isEdit ? '/sales/company/' + isEdit : '/sales/company';
    fetch(url, {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.csrfToken || csrfToken 
      },
      body: JSON.stringify({ name, gst, address, contact })
    }).then(r => r.json()).then(d => {
      if (d.success) { 
        this.toast(d.message); 
        setTimeout(() => location.reload(), 600); 
      }
      else this.toast(d.message, 'error');
    }).catch(() => this.toast('Network error.', 'error'));
  },

  submitTransport() {
    const name     = document.getElementById('trans-name').value;
    const gst      = document.getElementById('trans-gst').value;
    const contact  = document.getElementById('trans-contact').value;
    const vehicles = document.getElementById('trans-vehicles').value;
    
    if (!name || !contact) return this.toast('Name and Contact are required', 'error');
    if(gst && gst.toUpperCase() !== 'N/A' && !/^[A-Za-z0-9]{15}$/.test(gst)) return this.toast('GST must be 15 alphanumeric characters or N/A', 'error');
    if(!/^(\+91\s*)?[0-9]{10}$/.test(contact)) return this.toast('Mobile number must be 10 digits', 'error');

    fetch('/sales/transport', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.csrfToken || csrfToken 
      },
      body: JSON.stringify({ name, gst, contact, vehicles })
    }).then(r => r.json()).then(d => {
      if (d.success) { 
        this.toast(d.message); 
        setTimeout(() => location.reload(), 600);
      }
      else this.toast(d.message, 'error');
    }).catch(() => this.toast('Network error.', 'error'));
  },

  submitOrder() {
    const companyId   = document.getElementById('order-company').value;
    const transportId = document.getElementById('order-transport').value;
    const notes       = document.getElementById('order-notes').value;

    if (!companyId || !transportId) return this.toast('Select Company and Transport', 'error');

    const items = [];
    document.querySelectorAll('#order-products .dynamic-row').forEach(row => {
      const id       = row.querySelector('.o-prod-id').value;
      const grade    = row.querySelector('.o-prod-grade').value;
      const qty      = Number(row.querySelector('.o-prod-qty').value);
      const price    = Number(row.querySelector('.o-prod-price').value);
      const itemIdEl = row.querySelector('.o-item-id');
      const itemId   = itemIdEl ? itemIdEl.value : null;

      if (id && grade && qty > 0 && price > 0) {
        const itemObj = { product_id: id, grade, quantity: qty, price };
        if (itemId) itemObj.id = itemId;
        items.push(itemObj);
      }
    });

    if (items.length === 0) return this.toast('Add valid products and grades', 'error');

    const body = { company_id: companyId, transporter_id: transportId, notes, items };
    const editOrderIdEl = document.getElementById('edit-order-id');
    if (editOrderIdEl) {
      body.order_id = editOrderIdEl.value;
    }

    fetch('/sales/order', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.csrfToken || csrfToken 
      },
      body: JSON.stringify(body)
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { this.toast(d.message); setTimeout(() => window.location.href = '/sales/history', 600); }
      else this.toast(d.message || 'Error saving order', 'error');
    })
    .catch(() => this.toast('Network error.', 'error'));
  },

  previewLR(event) {
    const file = event.target.files[0];
    if (!file) return;

    const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type)) {
      this.toast('Only JPG, JPEG, PNG, and WEBP images are allowed.', 'error');
      event.target.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = function(){
      const img = document.getElementById('lr-preview');
      if (img) {
        img.src = reader.result;
        img.style.display = 'block';
      }
      window.currentLRImage = reader.result;
    };
    reader.readAsDataURL(file);
  },

  submitDispatch() {
    const orderId = document.getElementById('dispatch-order').value;
    if (!orderId) return this.toast('Select an order', 'error');

    const itemInputs = document.querySelectorAll('.dispatch-item-qty');
    if (itemInputs.length === 0) return this.toast('No items to dispatch', 'error');

    const items = [];
    let hasError = false;
    let locationUpdates = [];
    itemInputs.forEach(input => {
      const qty = Number(input.value);
      const max = Number(input.dataset.max);
      const itemId = input.dataset.itemId;
      
      if (qty > 0) {
        if (qty > max) {
          this.toast(`Quantity exceeds remaining (max: ${max} kg)`, 'error');
          hasError = true;
        }

        const splits = [];
        const splitInputs = document.querySelectorAll(`.loc-split-qty[data-item-id="${itemId}"]`);
        let splitSum = 0;
        
        splitInputs.forEach(sInp => {
          const sQty = Number(sInp.value);
          if (sQty > 0) {
             const sMax = Number(sInp.dataset.max);
             if (sQty > sMax) {
               this.toast(`Location qty exceeds available in ${sInp.dataset.loc}`, 'error');
               hasError = true;
             }
             splitSum += sQty;
             splits.push({ location_key: sInp.dataset.loc, dispatch_location_qty: sQty });
             
             const orderItem = (window.currentDispatchOrderItems || []).find(x => x.id == itemId);
             if (orderItem) {
                const locKey = `${orderItem.productId}_${orderItem.grade || 'NONE'}_${orderItem.productType || 'FINISHED'}`;
                locationUpdates.push({ locKey, location: sInp.dataset.loc, deduct: sQty });
             }
          }
        });

        splitSum = Math.round(splitSum * 1000) / 1000;
        const roundedQty = Math.round(qty * 1000) / 1000;

        if (splitInputs.length > 0 && splitSum !== roundedQty) {
           this.toast(`You must allocate the full Dispatch Qty (${roundedQty} kg) across locations. Currently allocated: ${splitSum} kg.`, 'error');
           hasError = true;
        }

        items.push({ order_item_id: itemId, quantity: qty, location_splits: splits });
      }
    });

    if (hasError) return;
    if (items.length === 0) return this.toast('Enter at least one item quantity to dispatch', 'error');

    fetch('/dispatch/action', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.csrfToken || csrfToken 
      },
      body: JSON.stringify({ 
        order_id: orderId, 
        items: items, 
        lr_image: window.currentLRImage,
        driver_no: document.getElementById('dispatch-driver')?.value || '',
        lr_no: document.getElementById('dispatch-lr-no')?.value || '',
        transporter_id: document.getElementById('dispatch-transporter')?.value || null
      })
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        window.currentLRImage = null;
        this.toast(d.message || 'Dispatch recorded!');
        setTimeout(() => this.navigate('home'), 600);
      } else {
        this.toast(d.message || 'Dispatch failed', 'error');
      }
    })
    .catch(() => this.toast('Network error.', 'error'));
  },

  onDispatchOrderSelect(id) {
    const orders = window.currentPendingOrders || [];
    const o = orders.find(x => x.id == id);
    const div = document.getElementById('order-preview');
    if(o) {
      window.currentDispatchOrderItems = o.items;
      div.style.display = 'block';
      div.classList.add('animation-fadeIn');
      const detailsDiv = document.getElementById('dispatch-details');
      if(detailsDiv) detailsDiv.style.display = 'block';
      const itemRows = (o.items || []).map((i, idx) => {
        const remaining = i.remainingQty ?? (i.quantity - (i.dispatchedQty || 0));
        const alreadyDispatched = i.dispatchedQty || 0;
        if (remaining <= 0) return ''; // skip fully dispatched items
        return `
        <div style="display:flex; flex-direction:column; gap:6px; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.05);">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span style="font-weight:500;">${i.productName} - (grade- ${i.grade || 'N/A'}) (type - ${i.productType === 'FINISHED' ? 'FG' : (i.productType ? i.productType.toLowerCase() : 'N/A')})</span>
            <span style="color:var(--text-muted); font-size:0.8rem;">
              Total: ${i.quantity} kg
              ${alreadyDispatched > 0 ? ` · Already sent: ${alreadyDispatched} kg` : ''}
            </span>
          </div>
          <div style="display:flex; align-items:center; gap:10px; margin-bottom: 8px;">
            <label style="font-size:0.75rem; color:var(--secondary); white-space:nowrap; margin:0;">Dispatch Qty (max ${remaining} kg):</label>
            <input type="number" class="dispatch-item-qty" data-item-id="${i.id}" data-max="${remaining}" 
                   value="" placeholder="Enter quantity..." max="${remaining}" min="0.001" step="0.001"
                   style="flex:1; padding:0.6rem; font-size:1rem; font-weight:bold; color:var(--secondary); background:rgba(0,0,0,0.2); border:1px solid var(--glass-border); border-radius:8px;">
          </div>
          <div id="loc-splits-${i.id}" style="margin-top:4px;">
            <div style="font-size:0.75rem; color:var(--text-muted);">⏳ Loading stock locations...</div>
          </div>
        </div>
      `}).join('');
      
      div.innerHTML = `
        <div style="background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:20px; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid var(--primary-light); padding-bottom:10px;">
            <h3 style="margin:0; color:var(--primary-light); font-size:1.3rem; letter-spacing:1px;">ORDER #${String(o.id).toUpperCase()}</h3>
            <span class="badge badge-pending" style="padding:4px 12px; font-size:0.75rem;">READY FOR DISPATCH</span>
          </div>
          
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:25px; margin-bottom:20px;">
            <div>
              <div style="display:flex; align-items:center; gap:8px; color:var(--text-muted); font-size:0.7rem; text-transform:uppercase; margin-bottom:8px; font-weight:bold;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Customer Details
              </div>
              <div style="font-size:1.1rem; font-weight:700; margin-bottom:4px; color:#fff;">${o.company.name}</div>
              <div style="color:var(--secondary); font-size:0.85rem; font-weight:500; margin-bottom:4px;">${o.company.contact}</div>
              <div style="font-size:0.8rem; opacity:0.7; line-height:1.4;">${o.company.address}</div>
              <div style="margin-top:6px; font-size:0.75rem; font-family:monospace; background:rgba(255,255,255,0.05); padding:2px 6px; display:inline-block; border-radius:4px;">GST: ${o.company.gst}</div>
            </div>
            
            <div>
              <div style="display:flex; align-items:center; gap:8px; color:var(--text-muted); font-size:0.7rem; text-transform:uppercase; margin-bottom:8px; font-weight:bold;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                Transport Info
              </div>
              <div style="font-size:1.1rem; font-weight:700; margin-bottom:4px; color:#fff;">${o.transporter.name}</div>
              <div style="color:var(--secondary); font-size:0.85rem; font-weight:500; margin-bottom:4px;">${o.transporter.contact}</div>
              <div style="font-size:0.8rem; background:rgba(255,107,107,0.1); color:var(--secondary); padding:4px 8px; border-radius:4px; display:inline-block; font-weight:bold;">Vehicles: ${o.transporter.vehicles || 'N/A'}</div>
            </div>
          </div>

          ${o.notes ? `
          <div style="margin-bottom:20px; background:rgba(244,180,0,0.06); border:1px dashed rgba(244,180,0,0.3); border-radius:8px; padding:15px; border-left:4px solid var(--primary-light);">
            <div style="display:flex; align-items:center; gap:8px; color:var(--primary-light); font-size:0.75rem; text-transform:uppercase; margin-bottom:6px; font-weight:bold;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
              Notes & Special Instructions (Sales)
            </div>
            <div style="font-size:0.95rem; color:#fff; line-height:1.5; font-style:italic;">"${o.notes}"</div>
          </div>
          ` : ''}

          <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:15px; border-left:4px solid var(--secondary);">
            <div style="display:flex; align-items:center; gap:8px; color:var(--text-muted); font-size:0.7rem; text-transform:uppercase; margin-bottom:10px; font-weight:bold;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
              Items to Dispatch (Edit quantities for partial dispatch)
            </div>
            ${itemRows || '<div class="text-center py-1">All items already dispatched</div>'}
          </div>
        </div>
      `;

      // Fetch location stocks splits dynamically
      o.items.forEach(i => {
        const remaining = i.remainingQty ?? (i.quantity - (i.dispatchedQty || 0));
        if (remaining <= 0) return;
        
        fetch(`/api/stock/locations?product_id=${i.productId}&stage=${i.productType}&grade=${encodeURIComponent(i.grade)}`)
          .then(r => r.json())
          .then(data => {
            const container = document.getElementById(`loc-splits-${i.id}`);
            if (!container) return;
            
            if (data.success && data.breakdown && data.breakdown.length > 0) {
              container.innerHTML = `
                <div style="margin-top:8px; padding:8px; background:rgba(0,0,0,0.1); border-radius:6px; font-size:0.8rem;">
                  <div style="color:var(--text-muted); margin-bottom:4px;">Select Locations & Quantities:</div>
                  ${data.breakdown.map(loc => `
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                      <span>${loc.name} <span style="color:var(--secondary); font-size:0.75rem;">(Avail: ${loc.quantity} kg)</span></span>
                      <input type="number" class="loc-split-qty" data-item-id="${i.id}" data-loc="${loc.name}" 
                             data-max="${loc.quantity}" max="${loc.quantity}" min="0" step="0.01" placeholder="Qty" 
                             style="width:80px; padding:0.4rem; border-radius:4px; background:var(--glass-bg); color:#fff; border:1px solid var(--glass-border);">
                    </div>
                  `).join('')}
                </div>
              `;
            } else {
              container.innerHTML = `
                <div style="margin-top:8px; padding:8px; background:rgba(239,68,68,0.1); color:#f87171; border-radius:6px; font-size:0.8rem; text-align:center;">
                  ⚠ No stock found in any location for this product & grade!
                </div>
              `;
            }
          })
          .catch(err => {
            const container = document.getElementById(`loc-splits-${i.id}`);
            if (container) {
              container.innerHTML = `
                <div style="margin-top:8px; padding:8px; background:rgba(239,68,68,0.1); color:#f87171; border-radius:6px; font-size:0.8rem; text-align:center;">
                  ❌ Failed to load stock locations.
                </div>
              `;
            }
          });
      });
    } else {
      div.style.display = 'none';
    }
  },

  previewLateLR(event, logId, idx) {
    const file = event.target.files[0];
    if (!file) return;
    
    const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type)) {
      this.toast('Only JPG, JPEG, PNG, and WEBP images are allowed.', 'error');
      event.target.value = '';
      return;
    }
    
    const reader = new FileReader();
    reader.onload = () => {
      window.tempLRData = reader.result;
      const container = document.getElementById('late-lr-preview-container');
      if (container) {
        container.innerHTML = `
          <div style="margin-top:1rem; padding:10px; background:rgba(255,255,255,0.05); border-radius:10px; border:1px solid var(--secondary);">
            <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:8px;">Preview:</div>
            <img src="${reader.result}" style="width:100%; max-height:200px; object-fit:contain; border-radius:6px; margin-bottom:10px;">
            <button class="btn" onclick="app.submitLateLR(${logId}, ${idx})" style="padding:0.8rem;">Confirm & Submit LR</button>
            <button class="btn btn-secondary mt-1" onclick="app.openDispatchDrawer(${idx})" style="padding:0.6rem; font-size:0.8rem;">Cancel</button>
          </div>
        `;
      }
    };
    reader.readAsDataURL(file);
  },

  submitLateLR(logId, idx) {
    const imageData = window.tempLRData;
    if (!imageData) return this.toast('No image data found', 'error');
    
    this.toast('Uploading LR...', 'info');
    fetch('/dispatch/update-lr', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.csrfToken || csrfToken },
      body: JSON.stringify({ log_id: logId, lr_image: imageData })
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        this.toast(d.message);
        window.tempLRData = null;
        const log = window._historyLogs[idx];
        if (log) log.lrImage = d.lr_url;
        this.openDispatchDrawer(idx);
      } else {
        this.toast(d.message, 'error');
      }
    })
    .catch(() => this.toast('Network error.', 'error'));
  },

  handleLateLRUpload(event, logId, idx) {
    this.previewLateLR(event, logId, idx);
  },

  addNewExpenseCategory() {
    const allCats = (window.serverPageData && window.serverPageData.categories) ? window.serverPageData.categories : [];
    
    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Manage Categories</h3>
      
      <div style="max-height:200px; overflow-y:auto; margin-bottom:1.5rem; background:#0d1117; border:1px solid #30363d; border-radius:8px; padding:0.5rem;">
        ${allCats.length === 0 ? '<div style="color:#8b949e; font-size:0.85rem; text-align:center; padding:10px;">No categories found</div>' : ''}
        ${allCats.map(c => `
          <div style="display:flex; justify-content:space-between; align-items:center; padding:0.5rem; border-bottom:1px solid #21262d;">
            <span style="font-size:0.9rem; color:#e6edf3;">${c.label}</span>
            <button onclick="app.deleteExpenseCategory(${c.id}, '${c.label}')" style="background:none; border:none; color:#f87171; cursor:pointer; font-size:0.85rem; padding:4px 8px;">Delete</button>
          </div>
        `).join('')}
      </div>

      <div class="form-group">
        <label>Add New Category</label>
        <input type="text" id="new-cat-name" placeholder="e.g. Packaging, Office Supplies..." style="font-size:1rem; margin-bottom:0.5rem;">
      </div>
      
      <div style="display:flex; gap:10px; margin-top:1rem;">
        <button class="btn btn-secondary" style="flex:1;" onclick="app.closeDrawer()">Close</button>
        <button class="btn" style="flex:2;" onclick="app.saveNewExpenseCategory()">Save New</button>
      </div>
    `);
  },

  saveNewExpenseCategory() {
    const nameInput = document.getElementById('new-cat-name');
    const name = nameInput.value.trim();
    if (!name) return this.toast('Enter a category name', 'error');
    
    fetch('/cashier/category', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': window.csrfToken || csrfToken, 'Content-Type': 'application/json' },
      body: JSON.stringify({ name })
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        this.toast(d.message, 'success');
        if (!window.serverPageData.categories) window.serverPageData.categories = [];
        window.serverPageData.categories.push(d.category);
        window.serverPageData.categories.sort((a,b) => a.label.localeCompare(b.label));
        
        this.closeDrawer();
        const select = document.getElementById('tx-category');
        if (select) {
          select.innerHTML = window.serverPageData.categories.map(c => `<option value="${c.value}">${c.label}</option>`).join('');
          select.value = d.category.value;
        }
      } else {
        this.toast(d.message || 'Failed to add category', 'error');
      }
    })
    .catch(() => this.toast('Network error', 'error'));
  },

  deleteExpenseCategory(id, name) {
    if (!confirm(`Are you sure you want to delete the category "${name}"?`)) return;
    
    fetch(`/cashier/category/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': window.csrfToken || csrfToken, 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        this.toast(d.message, 'success');
        window.serverPageData.categories = window.serverPageData.categories.filter(c => c.id !== id);
        
        const select = document.getElementById('tx-category');
        if (select) {
          select.innerHTML = window.serverPageData.categories.map(c => `<option value="${c.value}">${c.label}</option>`).join('');
        }
        this.addNewExpenseCategory();
      } else {
        this.toast(d.message || 'Failed to delete', 'error');
      }
    })
    .catch(() => this.toast('Network error', 'error'));
  },

  submitTransaction() {
    const amount   = Number(document.getElementById('tx-amount').value);
    const note     = document.getElementById('tx-note').value;
    const category = document.getElementById('tx-category').value;
    const ref      = document.getElementById('tx-ref').value;
    const billFile = document.getElementById('tx-bill')?.files[0];
    
    if (!amount || amount <= 0) return this.toast('Valid amount required', 'error');

    const formData = new FormData();
    formData.append('type', window.txType || 'IN');
    formData.append('amount', amount);
    formData.append('note', note);
    formData.append('category', category);
    formData.append('reference', ref);
    if (billFile) formData.append('bill_file', billFile);

    fetch('/cashier/action', {
      method: 'POST',
      headers: { 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.csrfToken || csrfToken 
      },
      body: formData
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { this.toast(d.message); setTimeout(() => location.reload(), 600); }
      else this.toast(d.message || 'Error', 'error');
    })
    .catch(() => this.toast('Network error.', 'error'));
  },

  downloadCashierPdf() {
    const txs   = window.serverPageData?.transactions || [];
    const cats  = [...new Set(txs.map(t => t.category).filter(Boolean))].sort();
    const sites = [...new Set(txs.map(t => t.site).filter(Boolean))].sort();

    const today = new Date().toISOString().split('T')[0];
    const oneMonthAgo = new Date(Date.now() - 30*24*60*60*1000).toISOString().split('T')[0];

    Swal.fire({
      title: '📄 Generate Account Statement',
      html: `
        <div style="text-align:left; font-size:0.9rem;">
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem; margin-bottom:0.8rem;">
            <div>
              <label style="font-size:0.78rem; color:#8b949e; display:block; margin-bottom:4px;">From Date</label>
              <input id="sp-from" type="date" value="${oneMonthAgo}" style="width:100%; padding:0.5rem; border-radius:6px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
            </div>
            <div>
              <label style="font-size:0.78rem; color:#8b949e; display:block; margin-bottom:4px;">To Date</label>
              <input id="sp-to" type="date" value="${today}" style="width:100%; padding:0.5rem; border-radius:6px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
            </div>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem; margin-bottom:0.8rem;">
            <div>
              <label style="font-size:0.78rem; color:#8b949e; display:block; margin-bottom:4px;">Category</label>
              <select id="sp-cat" style="width:100%; padding:0.5rem; border-radius:6px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
                <option value="all">All Categories</option>
                ${cats.map(c => `<option value="${c}">${c.replace(/_/g,' ').replace(/\b\w/g,l=>l.toUpperCase())}</option>`).join('')}
              </select>
            </div>
            <div>
              <label style="font-size:0.78rem; color:#8b949e; display:block; margin-bottom:4px;">Site / Branch</label>
              <select id="sp-site" style="width:100%; padding:0.5rem; border-radius:6px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
                <option value="all">All Sites</option>
                ${sites.map(s => `<option value="${s}">${s}</option>`).join('')}
              </select>
            </div>
          </div>

          <div style="margin-bottom:0.8rem;">
            <label style="font-size:0.78rem; color:#8b949e; display:block; margin-bottom:4px;">Opening Balance (₹)</label>
            <input id="sp-opening" type="number" step="0.01" placeholder="Leave blank to auto-calculate" style="width:100%; padding:0.5rem; border-radius:6px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
          </div>

          <div style="background:rgba(35,134,54,0.1); border:1px solid #238636; border-radius:8px; padding:0.7rem; display:flex; align-items:center; gap:0.6rem;">
            <input type="checkbox" id="sp-bills" checked style="width:16px; height:16px; accent-color:#238636;">
            <label for="sp-bills" style="font-size:0.85rem; cursor:pointer; color:#e6edf3;">
              📎 Include bill attachments as extra pages after statement
            </label>
          </div>
        </div>
      `,
      background: '#0d1117',
      color: '#e6edf3',
      showCancelButton: true,
      confirmButtonText: '📄 Generate PDF',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#238636',
      cancelButtonColor: '#30363d',
      width: '500px',
      preConfirm: () => {
        const from = document.getElementById('sp-from').value;
        const to   = document.getElementById('sp-to').value;
        if (!from || !to) { Swal.showValidationMessage('Please select both dates'); return false; }
        if (from > to)    { Swal.showValidationMessage('From date must be before To date'); return false; }
        return {
          from, to,
          category: document.getElementById('sp-cat').value,
          site: document.getElementById('sp-site').value,
          include_bills: document.getElementById('sp-bills').checked ? 'yes' : 'no',
          opening_balance: document.getElementById('sp-opening').value || '',
        };
      }
    }).then(result => {
      if (!result.isConfirmed) return;
      const p = result.value;
      let url = `/cashier/history/pdf?from=${p.from}&to=${p.to}&include_bills=${p.include_bills}&category=${p.category}&site=${p.site}`;
      if (p.opening_balance) url += `&opening_balance=${p.opening_balance}`;
      this.toast('Generating PDF... this may take a moment ⏳', 'info');
      window.open(url, '_blank');
    });
  },

  editTransaction(id) {
    const allTxs = window.serverPageData.transactions || [];
    const teamTxs = window.serverPageData.teamTransactions || [];
    const t = allTxs.find(x => x.id == id) || teamTxs.find(x => x.id == id);
    if (!t) return this.toast('Transaction not found', 'error');

    const allCats = (window.serverPageData && window.serverPageData.categories) ? window.serverPageData.categories : [];
    const catOptions = allCats.map(c => {
        const val = c.name.toLowerCase().replace(/[^a-z0-9]+/g, '_');
        return `<option value="${val}" ${t.category === val ? 'selected' : ''}>${c.name}</option>`;
    }).join('');

    Swal.fire({
      title: 'Edit Transaction',
      html: `
        <div style="text-align:left;">
          <div class="form-group mb-1">
            <label style="color:var(--text-muted); font-size:0.8rem;">Amount</label>
            <input type="number" id="edit-tx-amount" value="${t.amount}" class="swal2-input" style="width:100%; margin:0; box-sizing:border-box;">
          </div>
          <div class="form-group mb-1">
            <label style="color:var(--text-muted); font-size:0.8rem;">Category</label>
            <select id="edit-tx-category" class="swal2-select" style="width:100%; margin:0; box-sizing:border-box;">
              <option value="general" ${t.category === 'general' ? 'selected' : ''}>General</option>
              ${catOptions}
            </select>
          </div>
          <div class="form-group mb-1">
            <label style="color:var(--text-muted); font-size:0.8rem;">Note</label>
            <input type="text" id="edit-tx-note" value="${t.note || ''}" class="swal2-input" style="width:100%; margin:0; box-sizing:border-box;">
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Save Changes',
      confirmButtonColor: 'var(--primary)',
      cancelButtonColor: '#30363d',
      background: 'var(--dark-panel)',
      color: 'var(--text-main)',
      preConfirm: () => this.saveTransactionEdit(id)
    });
  },

  saveTransactionEdit(id) {
    const amount = Number(document.getElementById('edit-tx-amount').value);
    const category = document.getElementById('edit-tx-category').value;
    const note = document.getElementById('edit-tx-note').value;
    
    if (!amount || amount <= 0) return this.toast('Invalid amount', 'error');

    fetch(`/cashier/action/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken || csrfToken },
      body: JSON.stringify({ amount, category, note })
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        this.toast(d.message, 'success');
        this.closeDrawer();
        setTimeout(() => location.reload(), 600);
      } else {
        this.toast(d.message || 'Update failed', 'error');
      }
    });
  },

  deleteTransaction(id) {
    if (!confirm('Are you sure you want to completely delete this transaction? This action is permanent and will affect the balance.')) return;
    
    fetch(`/cashier/action/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': window.csrfToken || csrfToken }
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        this.toast(d.message, 'success');
        setTimeout(() => location.reload(), 600);
      } else {
        this.toast(d.message || 'Delete failed', 'error');
      }
    });
  },

  viewBill(billId, fileType) {
    window.open(`/cashier/bill/${billId}/view`, '_blank');
  },

  showBillUpload(txId) {
    const txs = (window.serverPageData?.transactions || []);
    const tx  = txs.find(t => t.id == txId);
    const existingBills = tx?.bills || [];

    Swal.fire({
      title: '📎 Manage Bills',
      html: `
        <div style="text-align:left;">
          ${existingBills.length > 0 ? `
            <div style="margin-bottom:1rem;">
              <div style="font-size:0.8rem; color:#8b949e; margin-bottom:0.5rem;">Attached Bills (${existingBills.length}):</div>
              <div style="display:flex; flex-direction:column; gap:0.4rem;">
                ${existingBills.map(b => `
                  <div style="display:flex; align-items:center; justify-content:space-between; background:#161b22; border:1px solid #30363d; border-radius:6px; padding:0.5rem 0.7rem;">
                    <span style="font-size:0.82rem;">${b.file_type==='pdf'?'📄':'🖼️'} ${b.original_name}</span>
                    <div style="display:flex; gap:0.4rem;">
                      <button onclick="window.open('/cashier/bill/${b.id}/view','_blank')" style="background:rgba(59,130,246,0.2); border:none; color:#60a5fa; border-radius:4px; padding:3px 8px; font-size:0.75rem; cursor:pointer;">View</button>
                      <button onclick="app.deleteBill(${b.id}, ${txId})" style="background:rgba(239,68,68,0.2); border:none; color:#f87171; border-radius:4px; padding:3px 8px; font-size:0.75rem; cursor:pointer;">Delete</button>
                    </div>
                  </div>
                `).join('')}
              </div>
            </div>
          ` : '<p style="color:#8b949e; font-size:0.85rem; margin-bottom:0.8rem;">No bills attached yet.</p>'}

          <div style="background:#0d1117; border:2px dashed #30363d; border-radius:8px; padding:1rem; text-align:center;">
            <div style="font-size:0.85rem; color:#8b949e; margin-bottom:0.5rem;">📁 Upload New Bill</div>
            <input type="file" id="bill-upload-input" accept="image/jpeg,image/png,application/pdf" style="display:block; margin:0 auto; font-size:0.8rem;">
            <div style="font-size:0.72rem; color:#555; margin-top:0.4rem;">JPG, PNG or PDF · Max 10MB</div>
          </div>
        </div>
      `,
      background: '#0d1117',
      color: '#e6edf3',
      showCancelButton: true,
      confirmButtonText: 'Upload',
      cancelButtonText: 'Close',
      confirmButtonColor: '#238636',
      cancelButtonColor: '#30363d',
      width: '480px',
      preConfirm: () => {
        const fileInput = document.getElementById('bill-upload-input');
        if (!fileInput.files.length) {
          Swal.showValidationMessage('Please select a file to upload');
          return false;
        }
        return fileInput.files[0];
      }
    }).then(result => {
      if (!result.isConfirmed) return;
      const formData = new FormData();
      formData.append('transaction_id', txId);
      formData.append('bill_file', result.value);
      formData.append('_token', window.csrfToken || csrfToken);

      fetch('/cashier/bill/upload', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            this.toast('Bill uploaded! ✅', 'success');
            setTimeout(() => location.reload(), 1000);
          } else {
            this.toast(d.message || 'Upload failed', 'error');
          }
        })
        .catch(() => this.toast('Network error', 'error'));
    });
  },

  deleteBill(billId, txId) {
    Swal.fire({
      title: 'Delete Bill?',
      text: 'This will permanently remove the bill attachment.',
      icon: 'warning',
      background: '#0d1117',
      color: '#e6edf3',
      showCancelButton: true,
      confirmButtonText: 'Delete',
      confirmButtonColor: '#b91c1c',
      cancelButtonColor: '#30363d',
    }).then(result => {
      if (!result.isConfirmed) return;
      fetch(`/cashier/bill/${billId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': window.csrfToken || csrfToken, 'Content-Type': 'application/json' }
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          this.toast('Bill deleted', 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          this.toast(d.message || 'Failed', 'error');
        }
      });
    });
  },

  openCashierDrawer(idx) {
    const t = window._historyLogs[idx];
    if(!t) return;
    const color = t.type==='IN' ? 'var(--secondary)' : 'var(--danger)';
    
    // Add edit form hidden by default
    const allCats = (window.serverPageData && window.serverPageData.categories) ? window.serverPageData.categories : [];
    const catOptions = allCats.map(c => {
        const val = c.name.toLowerCase().replace(/[^a-z0-9]+/g, '_');
        return `<option value="${val}" ${t.category === val ? 'selected' : ''}>${c.name}</option>`;
    }).join('');

    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Transaction Details</h3>
      <div id="tx-view-${t.id}">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem;">
          <div><div style="color:var(--text-muted); font-size:0.8rem;">Type</div><div style="font-weight:600;">${t.type}</div></div>
          <div><div style="color:var(--text-muted); font-size:0.8rem;">Amount</div><div style="font-weight:700; font-size:1.2rem; color:${color}">\u20b9${Number(t.amount).toLocaleString()}</div></div>
          <div><div style="color:var(--text-muted); font-size:0.8rem;">Category</div><div>${(t.category||'general').replace(/_/g,' ').toUpperCase()}</div></div>
          <div><div style="color:var(--text-muted); font-size:0.8rem;">Date</div><div>${new Date(t.date || t.created_at).toLocaleString()}</div></div>
        </div>
        <div style="margin-top:1rem;"><div style="color:var(--text-muted); font-size:0.8rem;">Note</div><div>${t.note||'\u2014'}</div></div>
        ${t.reference ? '<div style="margin-top:0.5rem;"><div style="color:var(--text-muted); font-size:0.8rem;">Reference</div><div>'+t.reference+'</div></div>' : ''}
        
        <button class="btn btn-secondary mt-2" onclick="document.getElementById('tx-view-${t.id}').style.display='none'; document.getElementById('tx-edit-${t.id}').style.display='block';" style="width:100%; margin-bottom:10px;">Edit Transaction</button>
        <button class="btn btn-secondary" onclick="app.closeDrawer()" style="width:100%;">Close</button>
      </div>
      
      <div id="tx-edit-${t.id}" style="display:none;">
        <div class="form-group">
            <label>Amount</label>
            <input type="number" id="edit-tx-amount" value="${t.amount}" style="width:100%; padding:0.6rem; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;" min="0.01" step="0.01">
        </div>
        <div class="form-group mt-1">
            <label>Category</label>
            <select id="edit-tx-category" style="width:100%; padding:0.6rem; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
                <option value="general" ${t.category === 'general' ? 'selected' : ''}>General</option>
                ${catOptions}
            </select>
        </div>
        <div class="form-group mt-1">
            <label>Note</label>
            <input type="text" id="edit-tx-note" value="${t.note || ''}" style="width:100%; padding:0.6rem; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
        </div>
        <button class="btn mt-2" onclick="app.submitEditTransaction(${t.id})" style="width:100%; margin-bottom:10px;">Save Changes</button>
        <button class="btn btn-secondary" onclick="document.getElementById('tx-edit-${t.id}').style.display='none'; document.getElementById('tx-view-${t.id}').style.display='block';" style="width:100%;">Cancel</button>
      </div>
    `);
  },

  submitEditTransaction(id) {
    const amount = document.getElementById('edit-tx-amount').value;
    const category = document.getElementById('edit-tx-category').value;
    const note = document.getElementById('edit-tx-note').value;

    fetch('/cashier/action/' + id, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.csrfToken || csrfToken },
        body: JSON.stringify({ amount, category, note })
    }).then(r => r.json()).then(d => {
        if (d.success) {
            this.toast(d.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            this.toast(d.message || 'Error updating transaction', 'error');
        }
    }).catch(err => this.toast('Network error', 'error'));
  },

  openProductionDrawer(idx) {
    const l = window._historyLogs[idx];
    if(!l) return;
    const prods = (window.serverPageData && window.serverPageData.products) || [];
    const rmList = (window.serverPageData && window.serverPageData.rawMaterialsList) || [];
    const pName = prods.find(x=>x.id==l.outputProductId)?.name || rmList.find(x=>x.id==l.outputProductId)?.name || l.outputName || 'Unknown Product';
    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Production Details</h3>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem;">
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Product</div><div style="font-weight:600;">${pName}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Grade</div><div>${l.outputGrade}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Total Quantity</div><div style="font-weight:700; font-size:1.2rem; color:var(--secondary);">${l.outputQty} kg</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Date</div><div>${this.formatDateIST(l.date)}</div></div>
        ${l.notes ? `
          <div><div style="color:var(--text-muted); font-size:0.8rem;">Notes</div><div style="font-weight:600;">${l.notes}</div></div>
        ` : ''}
      </div>
      <div style="margin-top:1.5rem; margin-bottom:1rem;">
        <div style="font-size:0.9rem; font-weight:600; color:var(--primary-light); margin-bottom:0.5rem; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:4px;">Consumed Materials</div>
        <div style="display:flex; flex-direction:column; gap:8px;">
          ${(l.consumedInputs || []).map(i => {
            const p = rmList.find(x=>x.id==i.productId) || prods.find(x=>x.id==i.productId) || {};
            return `
              <div style="display:flex; justify-content:space-between; font-size:0.9rem; background:rgba(255,255,255,0.03); padding:8px 12px; border-radius:8px;">
                <span>${p.name || i.name || 'Material'} <span style="font-size:0.75rem; color:var(--text-muted);">(${i.grade})</span></span>
                <span style="font-weight:600; color:var(--danger);">${i.quantity} kg</span>
              </div>
            `;
          }).join('') || '<div class="text-muted text-center" style="font-size:0.8rem;">No ingredients recorded</div>'}
        </div>
      </div>
      <button class="btn btn-secondary" onclick="app.closeDrawer()" style="width:100%;">Close</button>
    `);
  },

  openSalesDrawer(idx) {
    const item = window._historyLogs[idx];
    if(!item) return;
    if(item._type === 'COMPANY') return this.openSalesCompanyDrawer(idx);
    if(item._type === 'TRANSPORT') return this.openSalesTransportDrawer(idx);
    
    const o = item;
    const comps = (window.serverPageData && window.serverPageData.companies) || [];
    const transps = (window.serverPageData && window.serverPageData.transportCompanies) || [];
    const prods = (window.serverPageData && window.serverPageData.products) || [];
    
    const comp = comps.find(c=>c.id==o.companyId) || o.company || {};
    const trans = transps.find(t=>t.id==o.transportId) || o.transporter || {};
    
    const prodRows = (o.items || o.products || []).map(p => {
      const prod = prods.find(x => x.id == p.productId);
      const typeStr = prod ? ` <span style="font-size:0.7rem; color:var(--text-muted);">(${prod.type})</span>` : '';
      return `<tr><td>${p.productName || 'Unknown'}${typeStr}</td><td>${p.grade}</td><td>${p.quantity} kg</td><td>\u20b9${(p.price||0).toLocaleString()}</td></tr>`;
    }).join('');
    
    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Order #${String(o.id).toUpperCase()}</h3>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem; margin-bottom:1rem;">
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Company</div><div style="font-weight:600;">${comp.name||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Status</div><div><span class="badge ${o.status==='OPEN'?'badge-open':'badge-closed'}">${o.status}</span></div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Transport</div><div>${trans.name||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Dispatch</div><div><span class="badge ${o.dispatchStatus==='PENDING'?'badge-pending':'badge-done'}">${o.dispatchStatus}</span></div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Date</div><div>${new Date(o.date).toLocaleString()}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Total</div><div style="font-weight:700; font-size:1.2rem; color:var(--secondary);">\u20b9${(o.total||0).toLocaleString()}</div></div>
      </div>
      ${o.notes ? '<div style="margin-bottom:1rem;"><div style="color:var(--text-muted); font-size:0.8rem;">Notes</div><div>'+o.notes+'</div></div>' : ''}
      <div class="table-container" style="margin-bottom:1rem;">
        <table style="font-size:0.85rem;">
          <thead><tr><th>Product</th><th>Grade</th><th>Qty</th><th>Price</th></tr></thead>
          <tbody>${prodRows || '<tr><td colspan="4" style="text-align:center;">No products</td></tr>'}</tbody>
        </table>
      </div>
      ${o.status === 'OPEN' && (o.dispatchStatus === 'PENDING' || o.dispatchStatus === '') ? `
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom: 0.5rem;">
          <a class="btn" href="/sales/action?edit=${o.id}" style="text-decoration:none; display:flex; align-items:center; justify-content:center; gap:6px; background:var(--warning); color:#000; font-weight:bold;">✏️ Edit Order</a>
          <button class="btn" onclick="app.cancelSalesOrder(${o.id})" style="display:flex; align-items:center; justify-content:center; gap:6px; background:var(--danger); font-weight:bold;">❌ Cancel Order</button>
        </div>
      ` : ''}
      <a class="btn mt-1" href="/order/pdf/${o.id}" target="_blank" style="width:100%; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:6px; background:var(--secondary); font-weight:bold; margin-bottom: 0.5rem;">📄 Download PDF</a>
      <button class="btn btn-secondary" onclick="app.closeDrawer()" style="width:100%;">Close</button>
    `);
  },

  cancelSalesOrder(id) {
    if(!confirm('Are you sure you want to cancel this order?')) return;
    fetch('/sales/order/'+id+'/cancel', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': window.csrfToken || csrfToken }
    }).then(r=>r.json()).then(d=>{
      if(d.success) { this.toast(d.message); setTimeout(()=>location.reload(),800); }
      else this.toast(d.message, 'error');
    }).catch(()=>this.toast('Network error', 'error'));
  },

  openSalesCompanyDrawer(idx) {
    const c = window._historyLogs[idx];
    if(!c) return;
    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Company Details</h3>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem; margin-bottom:1rem;">
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Name</div><div style="font-weight:600;">${c.name}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">GST</div><div>${c.gst||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Contact</div><div>${c.contact||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Date</div><div>${c.date ? new Date(c.date).toLocaleString() : 'N/A'}</div></div>
      </div>
      ${c.address ? '<div style="margin-bottom:1rem;"><div style="color:var(--text-muted); font-size:0.8rem;">Address</div><div>'+c.address+'</div></div>' : ''}
      <button class="btn mt-1" onclick="app.editCompany(${c.id})" style="width:100%; display:flex; align-items:center; justify-content:center; gap:6px; background:var(--warning); color:#000; font-weight:bold; margin-bottom:0.5rem;">✏️ Edit Company</button>
      <button class="btn btn-secondary" onclick="app.closeDrawer()" style="width:100%;">Close</button>
    `);
  },

  editCompany(id) {
    this.closeDrawer();
    this.navigate('sales/action?editCompany='+id);
  },

  openSalesTransportDrawer(idx) {
    const t = window._historyLogs[idx];
    if(!t) return;
    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Transport Details</h3>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem; margin-bottom:1rem;">
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Name</div><div style="font-weight:600;">${t.name}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">GST</div><div>${t.gst||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Contact</div><div>${t.contact||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Vehicles</div><div>${t.vehicles||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Date</div><div>${t.date ? new Date(t.date).toLocaleString() : 'N/A'}</div></div>
      </div>
      <button class="btn btn-secondary" onclick="app.closeDrawer()" style="width:100%;">Close</button>
    `);
  },

  openDispatchDrawer(idx) {
    const d = window._historyLogs[idx];
    if(!d) return;

    
    const itemsHtml = (d.items && d.items.length > 0) ? `
      <div style="margin-bottom:1rem; background:rgba(0,0,0,0.2); border-radius:8px; padding:12px; border-left:3px solid var(--secondary);">
        <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; margin-bottom:8px; font-weight:bold;">Items Dispatched in this Round</div>
        ${d.items.map(i => `
          <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.9rem;">
            <span>${i.productName || 'Unknown'} <span style="color:var(--text-muted); font-size:0.75rem;">(${i.grade}) (${i.productType || 'N/A'})</span></span>
            <span style="font-weight:bold; color:var(--secondary);">${i.quantity} kg</span>
          </div>
        `).join('')}
      </div>
    ` : '';

    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Dispatch Details</h3>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem; margin-bottom:1rem;">
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Order</div><div style="font-weight:600;">#${String(d.orderId || '').toUpperCase()}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Date</div><div>${new Date(d.date).toLocaleString()}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Company</div><div>${d.companyName||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Transport</div><div>${d.transportName||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Dispatched By</div><div>${d.dispatchedBy||'System'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Order Value</div><div style="font-weight:700; color:var(--secondary);">\u20b9${(d.orderTotal||0).toLocaleString()}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">LR Status</div><div>${d.lrImage ? '<span class="badge badge-done">UPLOADED</span>' : '<span class="badge badge-pending">PENDING</span>'}</div></div>
      </div>
      ${itemsHtml}
      ${d.lrImage ? `
        <div style="margin-bottom:1rem;">
          <div style="color:var(--text-muted); font-size:0.8rem; margin-bottom:0.5rem;">LR Copy</div>
          <img src="${d.lrImage}" style="width:100%; border-radius:10px; max-height:200px; object-fit:contain; cursor:pointer;" onclick="app.viewImage(this.src)">
          <div id="late-lr-preview-container">
            <button class="btn btn-sm btn-secondary mt-1" style="width:100%; font-size:0.7rem;" onclick="document.getElementById('late-lr-input').click()">Update LR Copy</button>
          </div>
        </div>
      ` : `
        <div style="margin-bottom:1rem; padding:1.5rem; background:rgba(255,165,0,0.05); border:1px dashed rgba(255,165,0,0.3); border-radius:12px; text-align:center;">
          <div style="color:var(--warning); font-weight:600; font-size:0.9rem; margin-bottom:10px;">LR Copy Pending</div>
          <div id="late-lr-preview-container">
            <button class="btn btn-secondary" style="width:100%;" onclick="document.getElementById('late-lr-input').click()">Upload LR Now</button>
          </div>
        </div>
      `}
      <input type="file" id="late-lr-input" accept=".jpg,.jpeg,.png,.webp" style="display:none;" onchange="app.handleLateLRUpload(event, ${d.id}, ${idx})">
      <a href="/dispatch/pdf/${d.id}" target="_blank" class="btn mt-1 mb-1" style="width:100%; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
        📄 Download Dispatch PDF
      </a>
      <button class="btn btn-danger" onclick="app.revertDispatch(${d.id})" style="width:100%; margin-bottom:10px;">Revert Dispatch</button>
      <button class="btn btn-secondary" onclick="app.closeDrawer()" style="width:100%;">Close</button>
    `);
  },

  revertDispatch(id) {
    if(!confirm('Are you sure you want to revert this dispatch? This will restore stock, delete the dispatch log, and update the order status.')) return;
    fetch('/dispatch/revert/' + id, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': window.csrfToken || csrfToken }
    }).then(r => r.json()).then(d => {
      if(d.success) {
        this.toast(d.message, 'success');
        setTimeout(() => location.reload(), 800);
      } else {
        this.toast(d.message, 'error');
      }
    }).catch(() => this.toast('Error reverting dispatch', 'error'));
  },

  viewImage(src) {
    this.openModal(`
      <div style="text-align:center; position:relative;">
        <img src="${src}" style="width:100%; max-height:80vh; object-fit:contain; border-radius:12px;">
      </div>
    `);
  },

  registerServiceWorker() {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
      let swUrl = '/sw.js';
      const metaBase = document.querySelector('meta[name="base-url"]');
      if (metaBase && metaBase.content) {
          let base = metaBase.content;
          if (base.endsWith('/')) base = base.slice(0, -1);
          swUrl = base + swUrl;
      }
      return navigator.serviceWorker.register(swUrl)
        .then(swReg => {
          this.swRegistration = swReg;
          return swReg;
        })
        .catch(error => {
          console.error('Service Worker Error', error);
          return null;
        });
    }
    return Promise.resolve(null);
  },

  urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  },

  async subscribeUser() {
    console.log('Attempting to subscribe user for push...');
    
    if (!this.swRegistration) {
      console.log('Service Worker not ready, attempting registration...');
      this.swRegistration = await this.registerServiceWorker();
    }

    if (!this.swRegistration) {
      console.error('Service Worker registration failed or not supported!');
      return null;
    }
    
    const publicKey = 'BGdLKYW9TRF_uXoOfrbyNhJXUEG139KLYS1DK4uWqDdD6Psrkg3_Zn-9ix52gRtHbNg46TQHnMZjQRxmSQ22sxI';
    const applicationServerKey = this.urlBase64ToUint8Array(publicKey);
    
    try {
      const subscription = await this.swRegistration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: applicationServerKey
      });
      
      console.log('Browser subscription successful:', subscription);
      
      fetch('/notifications/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken || csrfToken },
        body: JSON.stringify(subscription)
      }).then(res => {
        console.log('Server subscription response status:', res.status);
      }).catch(e => console.log('Silent fail (expected if not logged in):', e));
      
      return subscription;
    } catch (err) {
      console.error('Failed to subscribe browser: ', err);
      return null;
    }
  },

  async requestNotificationPermission() {
    console.log('Requesting notification permission...');
    if (!('Notification' in window)) {
      console.error('Notifications not supported in this browser.');
      return null;
    }

    const permission = await Notification.requestPermission();
    console.log('Permission status:', permission);
    if (permission === 'granted') {
      return await this.subscribeUser();
    }
    return null;
  }
};

window.onload = () => {
  setTimeout(() => app.init(), 10);
};
