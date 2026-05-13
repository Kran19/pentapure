const app = {
  currentUser: null,
  currentView: 'home',
  currentLang: 'en',

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

  setLanguage(lang) {
    this.currentLang = lang;
    localStorage.setItem('pentapure_lang', lang);
    this.refreshAppTranslatables();
    this.refreshCurrentView();
  },

  refreshAppTranslatables() {
    // Refresh hardcoded HTML parts like bottom nav
    const els = document.querySelectorAll('.bottom-nav .nav-item span');
    if(els.length >= 4) {
      els[0].innerText = this.t('Home');
      els[1].innerText = this.t('Action');
      els[2].innerText = this.t('History');
      
      // If 5 items, item 3 is Team, item 4 is Profile
      // If 4 items, item 3 is Profile
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
    // 1. Language Init
    const lang = localStorage.getItem('pentapure_lang') || 'en';
    this.currentLang = lang;
    
    window.currentDateRange = 'this_week';
    window.customStart = '';
    window.customEnd = '';
    window.rawHomeTab = 'stock';

    // 2. Clear Splash
    const splash = document.getElementById('splash-screen');
    if (splash) {
      splash.style.opacity = '0';
      setTimeout(() => splash.remove(), 500);
    }

    // 3. Register Service Worker for Notifications
    this.registerServiceWorker();

    // 3. Render current page based on URL (MPA mode)
    const path = window.location.pathname;
    const segments = path.split('/').filter(x => x);
    
    if (segments.length >= 2) {
      const roleStr = segments[0].toUpperCase();
      const viewStr = segments[1];
      
      // Don't overwrite if already set by Blade (Layout)
      if (!this.currentUser) {
        const users = DB.get('users') || [];
        this.currentUser = users.find(u => u.role === roleStr);
      }
      
      this.currentView = viewStr;
      const container = document.getElementById('content-area');
      
      // Safeguard: If we are in the Admin panel, Blade handles the rendering.
      // We should NOT trigger SPA rendering functions that might perform fetch calls.
      if (roleStr === 'ADMIN' || path.startsWith('/admin')) {
        console.log("Admin page detected, skipping SPA rendering.");
        return;
      }

      if (!container) return;

      if (roleStr === 'ADMIN') {
        // Admin views handled by AdminController + Blade
        // Only trigger JS interactive parts if necessary
      } else {
        if (viewStr === 'home') this.renderHome(container);
        else if (viewStr === 'action') this.renderAction(container);
        else if (viewStr === 'history') this.renderHistory(container);
        else if (viewStr === 'team') this.renderAttendanceTeam(container);
        else if (viewStr === 'profile') this.renderProfile(container);
        else if (viewStr === 'po') this.renderPurchaseOrder(container);
      }
    }
  },

  // --- CORE UTILS ---
  toast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Add icon based on type
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

  openModal(html) {
    const overlay = document.getElementById('modal-overlay');
    const content = document.getElementById('modal-content');
    content.innerHTML = html;
    overlay.classList.add('active');
  },

  closeModal() {
    document.getElementById('modal-overlay').classList.remove('active');
  },

  openDrawer(html) {
    document.getElementById('drawer-content').innerHTML = html;
    document.getElementById('bottom-drawer-overlay').classList.add('active');
  },

  closeDrawer() {
    document.getElementById('bottom-drawer-overlay').classList.remove('active');
  },

  // --- AUTH & NAV ---
  renderLogin() {
    const users = DB.get('users').filter(u => u.status !== 'BLOCKED');
    const list = document.getElementById('users-list');
    list.innerHTML = users.map(u => 
      `<button class="user-btn" onclick="app.login('${u.id}')">
        <div>
          <div style="font-weight:600;">${u.name}</div>
          <div style="font-size:0.8rem; color:var(--text-muted);">${u.role}</div>
        </div>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
      </button>`
    ).join('');
    
    document.getElementById('login-screen').classList.remove('hidden');
    document.getElementById('main-app').classList.add('hidden');
    document.body.classList.remove('admin-mode');
  },

  login(userId) {
    this.currentUser = DB.get('users').find(u => u.id == userId);
    document.getElementById('login-screen').classList.add('hidden');
    document.getElementById('main-app').classList.remove('hidden');
    
    document.getElementById('current-user-name').innerText = this.currentUser.name;
    document.getElementById('current-user-role').innerText = this.currentUser.role;

    if (this.currentUser.role === 'ADMIN') {
      document.body.classList.add('admin-mode');
      document.getElementById('admin-sidebar').classList.remove('hidden');
      document.getElementById('admin-hamburger').classList.remove('hidden');
      document.getElementById('app-header').classList.add('hidden');
      document.getElementById('bottom-nav').classList.add('hidden');
      this.renderAdminDashboard(document.querySelector('.admin-sidebar .nav-item'));
    } else {
      document.body.classList.remove('admin-mode');
      document.getElementById('admin-sidebar').classList.add('hidden');
      document.getElementById('admin-hamburger').classList.add('hidden');
      document.getElementById('app-header').classList.remove('hidden');
      document.getElementById('bottom-nav').classList.remove('hidden');
      this.navigate('home');
    }
    this.toast(`Welcome back, ${this.currentUser.name}!`);
  },

  logout() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/logout';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken; // from layouts/app.blade.php
    
    form.appendChild(csrfInput);
    document.body.appendChild(form);
    
    this.currentUser = null;
    form.submit();
  },

  navigate(view) {
    const role = (this.currentUser?.role || 'admin').toLowerCase();
    // If view already starts with role/, don't double prefix
    if (view.startsWith(role + '/')) {
      window.location.href = `/${view}`;
    } else {
      window.location.href = `/${role}/${view}`;
    }
  },

  // --- PAGINATION HELPERS ---
  paginate(items, page, pageSize = 10) {
    const totalPages = Math.ceil(items.length / pageSize) || 1;
    const start = (page - 1) * pageSize;
    return {
      paginated: items.slice(start, start + pageSize),
      totalPages
    };
  },
  
  renderPaginationControls(page, totalPages) {
    if (totalPages <= 1) return '';
    return `
      <div class="pagination" style="display:flex; justify-content:center; align-items:center; gap:10px; margin-top:1rem; padding: 1rem 0;">
        <button class="btn btn-sm btn-secondary" onclick="window.currentPage--; app.refreshCurrentView()" ${page === 1 ? 'disabled' : ''}>Prev</button>
        <span style="font-size:0.9rem;">Page ${page} of ${totalPages}</span>
        <button class="btn btn-sm btn-secondary" onclick="window.currentPage++; app.refreshCurrentView()" ${page === totalPages ? 'disabled' : ''}>Next</button>
      </div>
    `;
  },

  refreshCurrentView() {
    if (this.currentUser && this.currentUser.role === 'ADMIN') {
      const activeEl = document.querySelector('.admin-sidebar .nav-item.active');
      if(activeEl) activeEl.click();
    } else {
      const content = document.getElementById('content-area');
      if (this.currentView === 'home') this.renderHome(content);
      else if (this.currentView === 'action') this.renderAction(content);
      else if (this.currentView === 'history') this.renderHistory(content);
      else if (this.currentView === 'profile') this.renderProfile(content);
    }
  },

  // --- DATA HELPERS ---
  getAggregatedStock(stockType) {
    const items = DB.get(stockType) || [];
    const products = DB.get('products') || [];
    const rmList = DB.get('rawMaterialsList') || [];
    const agg = {};
    const gradeMap = {};

    items.forEach(i => {
      const key = `${i.productId}_${i.grade || 'NONE'}`;
      
      agg[key] = (agg[key] || 0) + Number(i.quantity);
      gradeMap[key] = i.grade || 'NONE';
    });

    return Object.keys(agg).map(key => {
      const parts = key.split('_');
      const id = parts[0];
      let p = rmList.find(prod => prod.id == id);
      if (!p) p = products.find(prod => prod.id == id);
      
      if (!p) return { id, name: 'Unknown Product', quantity: agg[key], unit: '?', grade: gradeMap[key] };
      
      return { 
        id: p.id, 
        name: p.name, 
        quantity: agg[key], 
        unit: p.unit || 'kg', 
        grade: gradeMap[key] !== 'NONE' ? gradeMap[key] : (p.grade || 'NONE')
      };
    });
  },

  renderPurchaseOrder(container) {
    const role = this.currentUser.role;
    const isProdRole = role === 'RAW' || role === 'SEMI' || role === 'FINISHED';
    
    if (isProdRole) {
      const rmList = DB.get('rawMaterialsList') || [];
      const myPOs = (DB.get('purchaseOrders') || []).sort((a,b)=>new Date(b.date)-new Date(a.date));
      container.innerHTML = `
        <div class="flex-between mb-1">
          <h2 style="margin:0;">Request Stock</h2>
          <button class="btn btn-sm" style="width:auto; padding:0.4rem 1rem;" onclick="app.navigate('home')">Back</button>
        </div>
        <div class="card">
          <div class="card-title">New Request to Admin</div>
          <div class="form-group">
            <label>Select Required Material (from Master List)</label>
            <select id="po-material">
              <option value="" disabled selected>-- Select --</option>
              ${rmList.map(r => `<option value="${r.id}">${r.name}</option>`).join('')}
            </select>
          </div>
          <div class="form-group">
            <label>Quantity Required (kg)</label>
            <input type="number" id="po-qty" placeholder="Enter quantity">
          </div>
          <div class="form-group">
            <label>Note / Necessity</label>
            <input type="text" id="po-note" placeholder="Optional notes for Admin">
          </div>
          <button class="btn mt-1" onclick="app.submitPO()">Send Request to Admin</button>
        </div>

        <h3 class="mt-2 mb-1">My Purchase Requests</h3>
        <div class="table-container">
          <table>
            <thead><tr><th>Material</th><th>Qty</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
              ${myPOs.map(p => {
                const prodList = DB.get('products') || [];
                const mat = rmList.find(x=>x.id == p.materialId)?.name || prodList.find(x=>x.id == p.materialId)?.name || 'Unknown';
                return `<tr>
                  <td>${mat}</td>
                  <td>${p.quantity} kg</td>
                  <td><span class="badge ${p.status==='DONE'?'badge-done':'badge-pending'}">${p.status}</span></td>
                  <td style="font-size:0.8rem;">${new Date(p.date).toLocaleDateString()}</td>
                </tr>`;
              }).join('') || '<tr><td colspan="4" class="text-center text-muted">No requests found.</td></tr>'}
            </tbody>
          </table>
        </div>
      `;
    }
  },

  submitPO() {
    const matId = document.getElementById('po-material').value;
    const qty   = Number(document.getElementById('po-qty').value);
    const note  = document.getElementById('po-note').value;

    if (!matId) return this.toast('Select a material', 'error');
    if (!qty || qty <= 0) return this.toast('Enter valid quantity', 'error');

    const rolePrefix = this.currentUser.role.toLowerCase();
    fetch(`/${rolePrefix}/po`, {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken 
      },
      body: JSON.stringify({ product_id: matId, quantity: qty, note })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        this.toast(data.message || 'Request sent!');
        setTimeout(() => this.navigate('home'), 600);
      } else {
        this.toast(data.message || 'Error', 'error');
      }
    })
    .catch(() => this.toast('Network error.', 'error'));
  },

  // --- PROFILE VIEW ---
  renderProfile(container) {
    container.innerHTML = `
      <div style="max-width: 600px; margin: 0 auto;">
        <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
          <div style="width:100px; height:100px; background:var(--primary); border-radius:50%; margin:0 auto 1.5rem; display:flex; align-items:center; justify-content:center; font-size:2.5rem; font-weight:bold; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);">
            ${this.currentUser.name.charAt(0)}
          </div>
          <h2 style="margin-bottom: 0.5rem; font-size: 1.8rem;">${this.currentUser.name}</h2>
          <span class="badge" style="background:rgba(79, 70, 229, 0.2); color:var(--primary-light); margin-bottom: 2.5rem; padding: 0.4rem 1rem; font-size: 0.9rem;">${this.currentUser.role}</span>
          
          <div class="form-group" style="text-align:left; margin-bottom: 2.5rem; background:rgba(255,255,255,0.03); padding:1.5rem; border-radius:12px;">
            <label style="color:var(--primary-light); font-weight:600; margin-bottom:0.8rem;">Language Preference</label>
            <select onchange="app.setLanguage(this.value)" style="background:rgba(0,0,0,0.3); border-color:rgba(255,255,255,0.1);">
              <option value="en" ${this.currentLang==='en'?'selected':''}>English</option>
              <option value="hi" ${this.currentLang==='hi'?'selected':''}>हिंदी (Hindi)</option>
              <option value="gu" ${this.currentLang==='gu'?'selected':''}>ગુજરાતી (Gujarati)</option>
            </select>
          </div>

          <button class="btn btn-danger" onclick="app.logout()" style="padding:1rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Logout Securely
          </button>
        </div>
      </div>
    `;
  },

  filterByDateRange(items, rangeType, customStart, customEnd) {
    if(!rangeType || rangeType === 'all') return items;
    const now = new Date();
    const startOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    let startD, endD = new Date(now);
    
    if(rangeType === 'today') {
      startD = startOfDay;
    } else if(rangeType === 'this_week') {
      const day = now.getDay() || 7; 
      startD = new Date(startOfDay);
      startD.setDate(startD.getDate() - day + 1);
    } else if(rangeType === 'last_week') {
      const day = now.getDay() || 7; 
      endD = new Date(startOfDay);
      endD.setDate(endD.getDate() - day);
      startD = new Date(endD);
      startD.setDate(startD.getDate() - 6);
      endD.setHours(23,59,59,999);
    } else if(rangeType === 'this_month') {
      startD = new Date(now.getFullYear(), now.getMonth(), 1);
    } else if(rangeType === 'last_month') {
      startD = new Date(now.getFullYear(), now.getMonth()-1, 1);
      endD = new Date(now.getFullYear(), now.getMonth(), 0, 23, 59, 59, 999);
    } else if(rangeType === 'custom') {
      startD = new Date(customStart);
      endD = new Date(customEnd);
      endD.setHours(23,59,59,999);
    }

    return items.filter(item => {
      if(!item.date) return false;
      const d = new Date(item.date);
      return d >= startD && d <= endD;
    });
  },

  renderDateFilterControls(onChangeName) {
    const rType = window.currentDateRange || 'today';
    return `
      <div class="filter-bar" style="flex-wrap:wrap; gap:8px; margin-bottom:1rem; padding: 0.5rem; background:rgba(0,0,0,0.2); border-radius:8px;">
        <select style="width:auto; flex:1;" onchange="window.currentDateRange=this.value; ${onChangeName}()">
          <option value="today" ${rType==='today'?'selected':''}>Today</option>
          <option value="this_week" ${rType==='this_week'?'selected':''}>This Week</option>
          <option value="last_week" ${rType==='last_week'?'selected':''}>Last Week</option>
          <option value="this_month" ${rType==='this_month'?'selected':''}>This Month</option>
          <option value="last_month" ${rType==='last_month'?'selected':''}>Last Month</option>
          <option value="custom" ${rType==='custom'?'selected':''}>Custom Range</option>
          <option value="all" ${rType==='all'?'selected':''}>All Time</option>
        </select>
        ${rType === 'custom' ? `
          <input type="date" id="custom-start" value="${window.customStart||''}" onchange="window.customStart=this.value; ${onChangeName}()" style="width:auto; padding:0.4rem;">
          <input type="date" id="custom-end" value="${window.customEnd||''}" onchange="window.customEnd=this.value; ${onChangeName}()" style="width:auto; padding:0.4rem;">
        ` : ''}
      </div>
    `;
  },

  // --- ROLE: RAW ---
  renderRawHome(container) {
    const tab = window.rawHomeTab || 'stock';
    const rawStock = DB.get('rawStock');
    
    let html = `
      <div class="flex-between mb-1">
        <h2 style="margin:0;">${this.t('Raw Material Overview')}</h2>
        <button class="btn btn-sm btn-secondary" style="width:auto;" onclick="app.navigate('po')">${this.t('Purchase Orders')}</button>
      </div>
      <div class="tabs">
        <div class="tab-btn ${tab==='stock'?'active':''}" onclick="window.rawHomeTab='stock'; window.currentPage=1; app.refreshCurrentView()">${this.t('Stock')}</div>
        <div class="tab-btn ${tab==='inward'?'active':''}" onclick="window.rawHomeTab='inward'; window.currentPage=1; app.refreshCurrentView()">${this.t('Inward')}</div>
        <div class="tab-btn ${tab==='outward'?'active':''}" onclick="window.rawHomeTab='outward'; window.currentPage=1; app.refreshCurrentView()">${this.t('Outward')}</div>
      </div>
      ${tab === 'stock' ? this.renderRecentPOs() : ''}
    `;

    if (tab === 'stock') {
      const stock = this.getAggregatedStock('rawStock').filter(s => s.quantity > 0);
      html += `<div class="responsive-grid">`;
      html += stock.map(s => `
        <div class="card" style="padding: 1rem; margin-bottom: 0;">
          <div class="flex-between">
            <div>
              <div style="font-weight:600; font-size:1.1rem;">${s.name}</div>
              <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Grade: <span class="badge badge-info">${s.grade}</span></div>
            </div>
            <div style="text-align:right;">
              <div style="font-size:1.4rem; font-weight:bold; color:var(--primary-light);">${s.quantity.toLocaleString()} <span style="font-size:0.9rem; color:var(--text-muted);">${s.unit}</span></div>
            </div>
          </div>
        </div>
      `).join('') || '<div class="card" style="grid-column: 1/-1;"><p class="text-center text-muted">No raw stock available.</p></div>';
      html += `</div>`;
    } else {
      html += this.renderDateFilterControls("app.refreshCurrentView");
      let filtered = this.filterByDateRange(DB.get('rawLedger') || [], window.currentDateRange, window.customStart, window.customEnd);
      
      // search
      const q = (window.rawSearchQuery||'').toLowerCase();
      html += `<div class="form-group"><input type="text" placeholder="${this.t('Search product...')}" value="${q}" oninput="window.rawSearchQuery=this.value; app.refreshCurrentView()"></div>`;
      
      if (tab === 'inward') {
        filtered = filtered.filter(s => s.quantity > 0);
      } else {
        filtered = filtered.filter(s => s.quantity < 0);
      }

      const rmList = DB.get('rawMaterialsList') || [];
      if(q) {
        filtered = filtered.filter(s => {
          const nm = (rmList.find(r=>r.id===s.productId)?.name||'').toLowerCase();
          return nm.includes(q);
        });
      }
      
      filtered.sort((a,b)=>new Date(b.date)-new Date(a.date));

      const page = window.currentPage || 1;
      const { paginated, totalPages } = this.paginate(filtered, page, 10);
      
      html += `<div class="table-container"><table><thead><tr><th>Product</th><th>Qty</th><th>Date</th></tr></thead><tbody>`;
      html += paginated.map(s => {
        const p = rmList.find(x=>x.id===s.productId) || {};
        const qColor = s.quantity > 0 ? 'var(--secondary)' : 'var(--danger)';
        const sign = s.quantity > 0 ? '+' : '';
        const dateStr = s.date ? new Date(s.date).toLocaleString() : 'N/A';
        return `<tr>
          <td>${p.name||s.productId} <br><small class="text-muted">${s.grade}</small></td>
          <td style="font-weight:bold; color:${qColor}">${sign}${s.quantity} kg</td>
          <td style="font-size:0.8rem;">${dateStr}</td>
        </tr>`;
      }).join('');
      html += `</tbody></table></div>`;
      html += this.renderPaginationControls(page, totalPages);
    }
    container.innerHTML = html;
  },
  
  renderRawAdd(container) {
    const rmList = DB.get('rawMaterialsList') || [];
    window._rawShowAll = window._rawShowAll || false;
    window._rawSearch = window._rawSearch || '';
    
    let filtered = rmList;
    if (window._rawSearch) {
      filtered = rmList.filter(rm => rm.name.toLowerCase().includes(window._rawSearch.toLowerCase()));
    }
    const displayList = window._rawShowAll ? filtered : filtered.slice(0, 8);
    const hasMore = !window._rawShowAll && filtered.length > 8;
    
    container.innerHTML = `
      <div class="card">
        <div class="card-title">Inward Raw Material</div>
        <div class="form-group" style="margin-bottom:0.8rem;">
          <input type="text" id="raw-search" placeholder="🔍 Search product..." value="${window._rawSearch}" 
            oninput="window._rawSearch=this.value; window._rawShowAll=false; app.renderRawAdd(document.getElementById('content-area'))" 
            style="padding:0.6rem 0.8rem; font-size:0.85rem;">
        </div>
        <div class="responsive-grid" style="grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); margin-bottom:1rem;">
          ${displayList.map(rm => `
            <div class="rm-card" onclick="app.selectRawMaterial('${rm.id}', this)" 
              style="border:2px solid transparent; border-radius:10px; overflow:hidden; cursor:pointer; background:rgba(255,255,255,0.05); text-align:center; padding-bottom:4px; transition:0.2s;">
              <img src="${rm.image_url}" style="width:100%; height:80px; object-fit:cover;" onerror="this.src='https://via.placeholder.com/100x60?text=IMG'">
              <div style="font-size:0.75rem; font-weight:600; padding:4px 3px; line-height:1.2;">${rm.name}</div>
            </div>
          `).join('')}
        </div>
        ${hasMore ? `<button class="btn btn-sm btn-secondary" style="width:100%; margin-bottom:1rem; padding:0.6rem;" onclick="window._rawShowAll=true; app.renderRawAdd(document.getElementById('content-area'))">Load More (${filtered.length - 8} more)</button>` : ''}
        ${window._rawShowAll && filtered.length > 8 ? `<button class="btn btn-sm btn-secondary" style="width:100%; margin-bottom:1rem; padding:0.6rem;" onclick="window._rawShowAll=false; app.renderRawAdd(document.getElementById('content-area'))">Show Less</button>` : ''}
        <input type="hidden" id="raw-prod" value="">
        <div id="raw-selected-name" style="font-size:0.85rem; color:var(--primary-light); margin-bottom:0.5rem; min-height:1.2em;"></div>
        <div class="form-group">
          <label>Quantity (kg)</label>
          <input type="number" id="raw-qty" placeholder="Enter inward quantity" style="padding:0.7rem;">
        </div>
        <button class="btn mt-1" onclick="app.submitRawStock()">Add to Stock</button>
      </div>
    `;
  },

  selectRawMaterial(id, el) {
    document.querySelectorAll('.rm-card').forEach(c => c.style.borderColor = 'transparent');
    el.style.borderColor = 'var(--primary)';
    document.getElementById('raw-prod').value = id;
    const rm = (DB.get('rawMaterialsList') || []).find(x => x.id == id);
    document.getElementById('raw-selected-name').innerText = rm ? `Selected: ${rm.name}` : '';
  },

  submitRawStock() {
    const prodId = document.getElementById('raw-prod').value;
    const qty    = Number(document.getElementById('raw-qty').value);

    if (!prodId) return this.toast('Please select a material from the grid', 'error');
    if (!qty || qty <= 0) return this.toast('Enter a valid quantity', 'error');

    fetch('/raw/action', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken 
      },
      body: JSON.stringify({ product_id: prodId, quantity: qty, grade: 'NONE' })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        this.toast(data.message || 'Stock added!');
        setTimeout(() => this.navigate('home'), 600);
      } else {
        this.toast(data.message || 'Error saving stock', 'error');
      }
    })
    .catch(() => this.toast('Network error. Please try again.', 'error'));
  },

  // --- ROLE: SEMI & FINISHED (Production Flow) ---
  renderProductionHome(container, outputType) {
    const tab = window.prodHomeTab || 'stock';
    const stockKey = outputType === 'SEMI' ? 'semiStock' : 'finishedStock';
    const rawStockKey = outputType === 'SEMI' ? 'rawStock' : 'semiStock'; // This is what they consume
    const title = outputType === 'SEMI' ? 'Semi-Finished Stock' : 'Finished Goods Stock';
    
    let html = `
      <div class="flex-between mb-1">
        <h2 style="margin:0;">${this.t(title)}</h2>
        <button class="btn btn-sm btn-secondary" style="width:auto;" onclick="app.navigate('po')">${this.t('Purchase Orders')}</button>
      </div>
      ${this.renderRecentPOs()}
    `;

    if (outputType === 'SEMI') {
      const rawStock = this.getAggregatedStock('rawStock').filter(s => s.quantity > 0);
      html += `
        <div class="card mb-2" style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem;">
          <div class="card-title" style="font-size:0.85rem; color:var(--primary-light); display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
            <div style="display:flex; align-items:center; gap:8px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--secondary);"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
              Available Raw Materials
            </div>
            <div style="font-size:0.7rem; color:var(--text-muted); font-weight:normal;">Scroll &rarr;</div>
          </div>
          <div style="display:flex; overflow-x:auto; gap:12px; padding-bottom:10px; scrollbar-width:none; -ms-overflow-style:none;">
            <style>div::-webkit-scrollbar { display: none; }</style>
            ${rawStock.map(s => {
              const lowStock = s.quantity < 500;
              return `
                <div class="animation-fadeIn" style="flex:0 0 200px; background:rgba(255,255,255,0.04); padding:12px; border-radius:10px; border:1px solid rgba(255,255,255,0.05); position:relative; overflow:hidden;">
                  <div style="font-size:0.8rem; font-weight:700; color:var(--text-main); margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${s.name}</div>
                  <div style="font-size:0.65rem; color:var(--text-muted); margin-bottom:6px;">Grade: <span class="badge badge-info" style="font-size:0.55rem; padding:2px 5px;">${s.grade}</span></div>
                  <div style="display:flex; justify-content:space-between; align-items:baseline;">
                    <div style="font-weight:800; color:${lowStock ? 'var(--warning)' : 'var(--secondary)'}; font-size:1rem;">
                      ${s.quantity.toLocaleString()} <span style="font-size:0.7rem; font-weight:400; color:var(--text-muted);">${s.unit}</span>
                    </div>
                  </div>
                  ${lowStock ? `<div style="font-size:0.55rem; color:var(--warning); margin-top:5px; display:flex; align-items:center; gap:2px;"><span style="font-size:0.7rem;">⚠</span> Low</div>` : ''}
                  <div style="position:absolute; top:0; right:0; width:3px; height:100%; background:${lowStock ? 'var(--warning)' : 'var(--secondary)'}; opacity:0.6;"></div>
                </div>
              `;
            }).join('') || `<div style="width:100%; padding:20px; text-align:center; background:rgba(0,0,0,0.1); border-radius:10px;">
                <div style="color:var(--text-muted); font-size:0.85rem;">No raw material stock available.</div>
              </div>`}
          </div>
        </div>
      `;
    }

    html += `
      <div class="tabs">
        <div class="tab-btn ${tab==='stock'?'active':''}" onclick="window.prodHomeTab='stock'; window.currentPage=1; app.refreshCurrentView()">${this.t('Stock')}</div>
        <div class="tab-btn ${tab==='inward'?'active':''}" onclick="window.prodHomeTab='inward'; window.currentPage=1; app.refreshCurrentView()">${this.t('Inward')}</div>
        <div class="tab-btn ${tab==='outward'?'active':''}" onclick="window.prodHomeTab='outward'; window.currentPage=1; app.refreshCurrentView()">${this.t('Outward')}</div>
      </div>
    `;

    if (tab === 'stock') {
      const stock = this.getAggregatedStock(stockKey).filter(s => s.quantity > 0);
      html += `<div class="responsive-grid">`;
      html += stock.map(s => `
        <div class="list-item" style="margin-bottom: 0;">
          <div class="list-item-content">
            <div class="list-item-title">${s.name}</div>
            <div class="list-item-meta">
              Grade: ${s.grade}
            </div>
          </div>
          <div class="list-item-right">
            <div style="font-weight:bold; font-size:1.1rem; color:var(--text-main);">${s.quantity.toLocaleString()}</div>
            <div style="font-size:0.75rem; color:var(--text-muted);">${s.unit}</div>
          </div>
        </div>
      `).join('') || '<p class="text-center" style="grid-column: 1/-1;">No stock available.</p>';
      html += `</div>`;
    } else {
      // Inward (production of this type) and Outward (usage of this type or dispatch)
      html += this.renderDateFilterControls("app.refreshCurrentView");
      const q = (window.prodSearchQuery||'').toLowerCase();
      html += `<div class="form-group"><input type="text" placeholder="${this.t('Search product...')}" value="${q}" oninput="window.prodSearchQuery=this.value; app.refreshCurrentView()"></div>`;
      
      let filtered = DB.get(outputType === 'SEMI' ? 'semiLedger' : 'finishedLedger') || [];
      filtered = this.filterByDateRange(filtered, window.currentDateRange, window.customStart, window.customEnd);

      if (tab === 'inward') {
        filtered = filtered.filter(s => s.quantity > 0);
      } else {
        filtered = filtered.filter(s => s.quantity < 0);
      }

      const allProds = DB.get('products');
      if(q) {
        filtered = filtered.filter(s => {
          const nm = (allProds.find(r=>r.id===s.productId)?.name||'').toLowerCase();
          return nm.includes(q);
        });
      }
      
      filtered.sort((a,b)=>new Date(b.date)-new Date(a.date));

      const page = window.currentPage || 1;
      const { paginated, totalPages } = this.paginate(filtered, page, 10);
      
      html += `<div class="table-container"><table><thead><tr><th>Product</th><th>Qty</th><th>Date</th></tr></thead><tbody>`;
      html += paginated.map(s => {
        const p = allProds.find(x=>x.id===s.productId) || {};
        const qColor = s.quantity > 0 ? 'var(--secondary)' : 'var(--danger)';
        const sign = s.quantity > 0 ? '+' : '';
        const dateStr = s.date ? new Date(s.date).toLocaleString() : 'N/A';
        const pName = p.name || s.productName || s.productId || 'Unknown';
        return `<tr>
          <td>${pName} <br><span style="font-size:0.7rem; color:var(--text-muted);">${s.grade}</span></td>
          <td style="font-weight:bold; color:${qColor}">${sign}${s.quantity || 0} kg</td>
          <td style="font-size:0.8rem;">${dateStr}</td>
        </tr>`;
      }).join('');
      html += `</tbody></table></div>`;
      html += this.renderPaginationControls(page, totalPages);
    }

    container.innerHTML = html;
  },

  renderProductionAdd(container, outputType) {
    const inputType = outputType === 'SEMI' ? 'RAW' : 'SEMI';
    const inputStockKey = outputType === 'SEMI' ? 'rawStock' : 'semiStock';
    
    const outProds = DB.get('products').filter(p => p.type === outputType);
    const allGrades = DB.get('grades');
    
    const availableInputStock = this.getAggregatedStock(inputStockKey).filter(s => s.quantity > 0);
    window.currentAvailableInputStock = availableInputStock;
    
    if (outputType === 'FINISHED') {
      container.innerHTML = `
        <div class="card">
          <div class="card-title">Create Finished Goods</div>
          
          <div class="form-group">
            <label>Select Semi-Finished Material to Process</label>
            <select id="finished-input-id" onchange="app.validateFinishedRowStock()">
              <option value="" disabled selected>-- Select Material --</option>
              ${availableInputStock.map(s => `<option value="${s.id}|${s.grade}" data-max="${s.quantity}">${s.name} (Grade: ${s.grade})</option>`).join('')}
            </select>
            <div id="finished-stock-hint" style="font-size:0.7rem; color:var(--text-muted); margin-top:4px; min-height:12px;"></div>
          </div>

          <div class="form-group mt-1">
            <label>Consumed Quantity (kg)</label>
            <input type="number" id="finished-in-qty" placeholder="Quantity consumed">
          </div>
          
          <div class="form-group mt-1">
            <label>Notes (Optional)</label>
            <input type="text" id="finished-notes" placeholder="Enter notes here...">
          </div>
          
          <div class="form-group mt-1">
            <label>Total Expected Output (kg)</label>
            <input type="number" id="finished-out-qty" placeholder="Enter total output quantity" style="font-weight:bold; color:var(--secondary);">
          </div>
          
          <button class="btn mt-2" onclick="app.submitFinishedProduction()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Confirm Production
          </button>
        </div>
      `;
    } else {
      container.innerHTML = `
        <div class="card">
          <div class="card-title">Create Semi Production Order</div>
          
          <div class="form-group">
            <label>Target Product</label>
            <select id="prod-output" onchange="app.onTargetProductSelected()">
              <option value="" disabled selected>-- Select Product --</option>
              ${outProds.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
            </select>
          </div>

          <div class="form-group hidden" id="grade-selection-group">
            <label>Select Grade</label>
            <select id="prod-grade" onchange="app.onGradeSelected()">
              <!-- Grades injected dynamically -->
            </select>
          </div>
          
          <div id="materials-section" class="hidden" style="margin-top: 2rem; border-top: 1px dashed var(--glass-border); padding-top: 1.5rem;">
            <div class="form-group">
              <label>Expected Output Quantity (kg)</label>
              <input type="number" id="prod-out-qty" placeholder="Quantity produced">
            </div>

            <div class="flex-between mb-1 mt-1">
              <label style="margin:0; font-size:1rem; color:var(--primary-light);">Add Material (Consumed)</label>
              <button class="btn btn-sm btn-secondary" onclick="app.addInputRow()">+ Add Material</button>
            </div>
            
            <div id="input-rows" style="display:flex; flex-direction:column; gap:10px;">
              <!-- Rows injected here -->
            </div>
            
            <button class="btn mt-2" onclick="app.reviewProduction('${inputType}', '${outputType}', '${inputStockKey}')">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
              Review Production
            </button>
          </div>
        </div>
      `;
    }
  },

  validateFinishedRowStock() {
    const selectEl = document.getElementById('finished-input-id');
    const option = selectEl.options[selectEl.selectedIndex];
    const available = Number(option.dataset.max) || 0;
    
    const hint = document.getElementById('finished-stock-hint');
    hint.innerText = `Available: ${available}`;
    if(available === 0) hint.style.color = 'var(--danger)';
    else hint.style.color = 'var(--secondary)';
  },

  calculateFinishedTotal() {
    // Deprecated: No longer auto-calculating as we use direct note entry
  },

  submitFinishedProduction() {
    const val = document.getElementById('finished-input-id').value;
    const inQty = Number(document.getElementById('finished-in-qty').value);
    const notes = document.getElementById('finished-notes').value;
    const outQty = Number(document.getElementById('finished-out-qty').value);
    
    if (!val) return this.toast('Select a semi-finished material', 'error');
    if (!inQty || inQty <= 0) return this.toast('Enter valid consumed quantity', 'error');
    if (!outQty || outQty <= 0) return this.toast('Enter valid output quantity', 'error');

    const [id, grade] = val.split('|');
    const selectEl = document.getElementById('finished-input-id');
    const option = selectEl.options[selectEl.selectedIndex];
    const available = Number(option.dataset.max) || 0;

    if (inQty > available) return this.toast(`Not enough stock. Max: ${available}`, 'error');

    const payload = {
      output_product_id: id,
      output_grade:      grade,
      output_qty:        outQty,
      notes:             notes,
      inputs: [
        { product_id: id, grade: grade, quantity: inQty }
      ]
    };

    fetch('/finished/action', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken 
      },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        this.toast(res.message || 'Finished Production logged successfully!');
        this.navigate('home');
      } else {
        this.toast(res.message || 'Error logging production', 'error');
      }
    })
    .catch(() => this.toast('Network error. Try again.', 'error'));
  },

  onTargetProductSelected() {
    const productId = document.getElementById('prod-output').value;
    const p = (DB.get('products') || []).find(x => x.id == productId);
    
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
    // Add one row by default if empty
    if(document.getElementById('input-rows').children.length === 0) {
      this.addInputRow();
    }
  },

  addInputRow() {
    const stockItems = window.currentAvailableInputStock || [];
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
          ${stockItems.map(s => `<option value="${s.id}|${s.grade}" data-max="${s.quantity}">${s.name} (Grade: ${s.grade})</option>`).join('')}
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
    hint.innerText = `Available: ${available}`;
    if(available === 0) hint.style.color = 'var(--danger)';
    else hint.style.color = 'var(--secondary)';
  },

  reviewProduction(inputType, outputType, inputStockKey) {
    const outProdId = document.getElementById('prod-output').value;
    const outGrade = document.getElementById('prod-grade').value;
    const outQty = Number(document.getElementById('prod-out-qty').value);
    
    if (!outProdId || !outGrade) return this.toast('Select target product and grade', 'error');
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

    // Save state for confirmation
    window.tempProductionData = { inputType, outputType, inputStockKey, outProdId, outGrade, outQty, inputs };
    
    const outProdName = DB.get('products').find(x => x.id == outProdId)?.name;
    
    this.openDrawer(`
      <h3 style="margin-bottom:1rem; color:var(--warning);">Review Production</h3>
      <div style="background:rgba(255,255,255,0.05); padding:1rem; border-radius:10px; margin-bottom:1rem; border:1px solid var(--glass-border);">
        <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:4px;">Target Output</div>
        <div style="font-weight:700; font-size:1.1rem; color:var(--text-main);">${outQty} kg of ${outProdName}</div>
        <div style="font-size:0.85rem; margin-top:2px;">Grade: <span class="badge badge-info">${outGrade}</span></div>
      </div>
      
      <div style="font-size:0.9rem; font-weight:600; margin-bottom:0.8rem; color:var(--primary-light);">Materials to Consume:</div>
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
        <button class="btn" style="flex:2;" onclick="app.confirmProduction()">Confirm & Process</button>
      </div>
    `);
  },

  confirmProduction() {
    const data = window.tempProductionData;
    if (!data) return;

    const endpoint = data.outputType === 'SEMI' ? '/semi/action' : '/finished/action';
    const payload  = {
      output_product_id: data.outProdId,
      output_grade:      data.outGrade,
      output_qty:        data.outQty,
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
        'X-CSRF-TOKEN': csrfToken 
      },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        this.toast(res.message || `${data.outputType} Production logged!`);
        window.tempProductionData = null;
        this.closeDrawer();
        setTimeout(() => this.navigate('home'), 600);
      } else {
        this.toast(res.message || 'Error confirming production', 'error');
      }
    })
    .catch(() => this.toast('Network error. Try again.', 'error'));
  },

  // --- ROLE: SALES ---
  renderSalesHome(container) {
    const orders = DB.get('orders') || [];
    const companies = DB.get('companies') || [];
    const transports = DB.get('transportCompanies') || [];
    
    const totalOrders = orders.length;
    const openOrders = orders.filter(o => o.status === 'OPEN').length;
    const totalValue = orders.reduce((s,o) => s + Number(o.total || 0), 0);
    const pendingDispatch = orders.filter(o => o.dispatchStatus === 'PENDING').length;

    container.innerHTML = `
      <h2 class="mb-1">Sales Dashboard</h2>
      ${this.renderRecentPOs()}
      <div class="dashboard-grid">
        <div class="stat-card clickable-card" role="button" tabindex="0" onclick="app.navigate('history')">
          <div style="color:var(--primary-light)">Total Orders</div>
          <div class="stat-value">${totalOrders}</div>
        </div>
        <div class="stat-card clickable-card" role="button" tabindex="0" onclick="app.navigate('history')">
          <div style="color:var(--warning)">Open Orders</div>
          <div class="stat-value">${openOrders}</div>
        </div>
        <div class="stat-card clickable-card" role="button" tabindex="0" onclick="app.navigate('history')">
          <div style="color:var(--info)">Pending Dispatch</div>
          <div class="stat-value">${pendingDispatch}</div>
        </div>
        
        <!-- Type Breakdown -->
        <div class="stat-card" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05);">
          <div style="color:var(--secondary); font-size:0.8rem;">Raw Sales</div>
          <div style="font-size:1.2rem; font-weight:700;">${orders.filter(o => o.products && o.products.some(p => {
            const pr = DB.get('products').find(x => x.id == p.productId);
            return pr && pr.type === 'RAW';
          })).length}</div>
        </div>
        <div class="stat-card" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05);">
          <div style="color:var(--warning); font-size:0.8rem;">Semi Sales</div>
          <div style="font-size:1.2rem; font-weight:700;">${orders.filter(o => o.products && o.products.some(p => {
            const pr = DB.get('products').find(x => x.id == p.productId);
            return pr && pr.type === 'SEMI';
          })).length}</div>
        </div>
        <div class="stat-card" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05);">
          <div style="color:var(--primary-light); font-size:0.8rem;">Finished Sales</div>
          <div style="font-size:1.2rem; font-weight:700;">${orders.filter(o => o.products && o.products.some(p => {
            const pr = DB.get('products').find(x => x.id == p.productId);
            return pr && pr.type === 'FINISHED';
          })).length}</div>
        </div>

        <div class="stat-card clickable-card" role="button" tabindex="0" onclick="app.navigate('history')" style="grid-column: 1 / -1; background:var(--dark-panel);">
          <div style="color:var(--text-muted)">Total Sales Value</div>
          <div class="stat-value" style="color:var(--secondary)">₹${totalValue.toLocaleString()}</div>
        </div>
      </div>
      
      <div class="card mt-2">
        <div class="card-title">Quick Links</div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
          <button class="btn btn-secondary" onclick="app.navigate('action')">Create New Order</button>
          <button class="btn btn-secondary" onclick="app.navigate('history')">View History</button>
        </div>
      </div>
    `;
  },

  renderSalesAdd(container) {
    container.innerHTML = `
      <div class="tabs">
        <div class="tab-btn active" onclick="app.switchSalesTab('order', this)">Create Order</div>
        <div class="tab-btn" onclick="app.switchSalesTab('company', this)">Company</div>
        <div class="tab-btn" onclick="app.switchSalesTab('transport', this)">Transport</div>
      </div>
      <div id="sales-form-container"></div>
    `;
    this.switchSalesTab('order', document.querySelector('.tab-btn'));
  },

  switchSalesTab(tab, element) {
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
    const container = document.getElementById('sales-form-container');
    
    if(tab === 'order') {
      const companies = DB.get('companies');
      const transports = DB.get('transportCompanies');
      window.currentFinProds = [];
      
      container.innerHTML = `
        <div class="card animation-fadeIn">
          <div class="form-group">
            <label>Customer Company</label>
            <select id="order-company" onchange="app.onSalesCompanySelect(this.value)">
              <option value="" disabled selected>-- Select Company --</option>
              ${companies.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
            </select>
            <div id="company-details" style="font-size:0.8rem; color:var(--text-muted); margin-top:8px; display:none; background:rgba(0,0,0,0.2); padding:8px; border-radius:6px;"></div>
          </div>

          <div class="form-group">
            <label>Order Type</label>
            <select id="order-type" onchange="app.onOrderTypeSelect(this.value)">
              <option value="" disabled selected>-- Select Type --</option>
              <option value="RAW">Raw Material Sales</option>
              <option value="SEMI">Semi-Finished Sales</option>
              <option value="FINISHED">Finished Goods Sales</option>
            </select>
          </div>
          
          <div id="order-products-section" style="display:none;">
            <label style="display:block; margin-top:1.5rem; font-size:0.85rem; color:var(--text-muted); margin-bottom:0.4rem;">Products</label>
            <div id="order-products"></div>
            <button class="btn btn-sm btn-secondary mb-1" onclick="app.addOrderProductRow()" style="padding:0.6rem;">+ Add Product</button>
          </div>
          
          <div class="form-group mt-1">
            <label>Transport Partner</label>
            <select id="order-transport" onchange="app.onSalesTransportSelect(this.value)">
              <option value="" disabled selected>-- Select Transport --</option>
              ${transports.map(t => `<option value="${t.id}">${t.name}</option>`).join('')}
            </select>
            <div id="transport-details" style="font-size:0.8rem; color:var(--text-muted); margin-top:8px; display:none; background:rgba(0,0,0,0.2); padding:8px; border-radius:6px;"></div>
          </div>
          <div class="form-group">
            <label>Notes / Instructions</label>
            <textarea id="order-notes" rows="2" placeholder="Optional notes..."></textarea>
          </div>
          
          <button class="btn mt-1" onclick="app.submitOrder()" style="padding:1rem; font-size:1.1rem;">Generate Order</button>
        </div>
      `;
    } else if (tab === 'company') {
      container.innerHTML = `
        <div class="card animation-fadeIn">
          <div class="form-group"><label>Company Name</label><input type="text" id="comp-name"></div>
          <div class="form-group"><label>GST No.</label><input type="text" id="comp-gst" placeholder="e.g. 22AAAAA0000A1Z5"></div>
          <div class="form-group"><label>Address</label><textarea id="comp-address" rows="2"></textarea></div>
          <div class="form-group"><label>Mobile No</label><input type="text" id="comp-contact" placeholder="10-digit mobile"></div>
          <button class="btn" onclick="app.submitCompany()" style="padding:1rem; font-size:1.1rem; margin-top:0.5rem;">Save Company</button>
        </div>
      `;
    } else if (tab === 'transport') {
      container.innerHTML = `
        <div class="card animation-fadeIn">
          <div class="form-group"><label>Transporter Name</label><input type="text" id="trans-name"></div>
          <div class="form-group"><label>GST No.</label><input type="text" id="trans-gst"></div>
          <div class="form-group"><label>Driver Mobile No</label><input type="text" id="trans-contact"></div>
          <div class="form-group"><label>Vehicle No.</label><input type="text" id="trans-vehicles" placeholder="e.g. MH 12 AB 1234"></div>
          <button class="btn" onclick="app.submitTransport()" style="padding:1rem; font-size:1.1rem; margin-top:0.5rem;">Save Transport</button>
        </div>
      `;
    }
  },

  onSalesCompanySelect(id) {
    const comp = DB.get('companies').find(c => c.id == id);
    const div = document.getElementById('company-details');
    if(comp) {
      div.style.display = 'block';
      div.classList.add('animation-fadeIn');
      div.innerHTML = `
        <div style="display:grid; grid-template-columns:1fr; gap:4px;">
          <div><span style="color:var(--primary-light); font-weight:600;">GST:</span> ${comp.gst||'N/A'}</div>
          <div><span style="color:var(--primary-light); font-weight:600;">Contact:</span> ${comp.contact||'N/A'}</div>
          <div><span style="color:var(--primary-light); font-weight:600;">Address:</span> ${comp.address||'N/A'}</div>
        </div>
      `;
    } else {
      div.style.display = 'none';
    }
  },

  onSalesTransportSelect(id) {
    const trans = DB.get('transportCompanies').find(t => t.id == id);
    const div = document.getElementById('transport-details');
    if(trans) {
      div.style.display = 'block';
      div.classList.add('animation-fadeIn');
      div.innerHTML = `
        <div style="display:grid; grid-template-columns:1fr; gap:4px;">
          <div><span style="color:var(--primary-light); font-weight:600;">GST:</span> ${trans.gst||'N/A'}</div>
          <div><span style="color:var(--primary-light); font-weight:600;">Contact:</span> ${trans.contact||'N/A'}</div>
          <div><span style="color:var(--primary-light); font-weight:600;">Vehicles:</span> ${trans.vehicles||'N/A'}</div>
        </div>
      `;
    } else {
      div.style.display = 'none';
    }
  },

  onOrderTypeSelect(type) {
    window.currentOrderType = type;
    window.currentFinProds = (DB.get('products') || []).filter(p => p.type === type);
    
    const section = document.getElementById('order-products-section');
    section.style.display = 'block';
    section.classList.add('animation-fadeIn');
    
    const prodList = document.getElementById('order-products');
    if(prodList.children.length > 0) {
      prodList.innerHTML = '';
    }
    this.addOrderProductRow();
  },

  addOrderProductRow() {
    const finProds = window.currentFinProds || [];
    const allGrades = DB.get('grades');
    const div = document.createElement('div');
    div.className = 'dynamic-row';
    div.innerHTML = `
      <div class="form-group" style="flex:1 1 100%;">
        <select class="o-prod-id" style="width:100%;">
          <option value="" disabled selected>Product</option>
          ${finProds.map(p => `<option value="${p.id}">${p.name} (${p.type})</option>`).join('')}
        </select>
      </div>
      <div class="form-group" style="flex:1 1 30%;">
        <select class="o-prod-grade" style="width:100%;">
          <option value="" disabled selected>Grade</option>
          ${allGrades.map(g => `<option value="${g}">${g}</option>`).join('')}
          ${allGrades.includes('N/A') ? '' : `<option value="N/A">N/A</option>`}
        </select>
      </div>
      <div class="form-group" style="flex:1 1 25%;">
        <input type="number" class="o-prod-qty" placeholder="Qty">
      </div>
      <div class="form-group" style="flex:1 1 25%;">
        <input type="number" class="o-prod-price" placeholder="₹/Unit">
      </div>
      <button class="btn btn-danger" style="flex:0 0 36px; height:36px; padding:0; display:flex; align-items:center; justify-content:center;" onclick="this.parentElement.remove()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    `;
    document.getElementById('order-products').appendChild(div);
  },

  submitCompany() {
    const name    = document.getElementById('comp-name').value;
    const gst     = document.getElementById('comp-gst').value;
    const address = document.getElementById('comp-address').value;
    const contact = document.getElementById('comp-contact').value;
    
    if (!name || !gst || !address || !contact) return this.toast('All fields are required', 'error');

    // JS Regex Validation
    const gstRegex = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
    if(!gstRegex.test(gst)) return this.toast('Invalid GST format (e.g. 22AAAAA0000A1Z5)', 'error');
    if(!/^[0-9]{10}$/.test(contact)) return this.toast('Mobile number must be 10 digits', 'error');

    // Duplicate Check
    const exists = (DB.get('companies') || []).find(c => c.name.toLowerCase() === name.toLowerCase() || c.gst === gst);
    if(exists) return this.toast('Company with this name or GST already exists', 'error');

    fetch('/sales/company', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken 
      },
      body: JSON.stringify({ name, gst, address, contact })
    }).then(r => r.json()).then(d => {
      if (d.success) { 
        this.toast(d.message); 
        this.navigate('sales/action'); 
      }
      else this.toast(d.message, 'error');
    }).catch(() => this.toast('Network error.', 'error'));
  },

  submitTransport() {
    const name     = document.getElementById('trans-name').value;
    const gst      = document.getElementById('trans-gst').value;
    const contact  = document.getElementById('trans-contact').value;
    const vehicles = document.getElementById('trans-vehicles').value;
    
    if (!name || !gst || !contact) return this.toast('Name, GST and Contact are required', 'error');

    if(!/^[0-9]{10}$/.test(contact)) return this.toast('Mobile number must be 10 digits', 'error');

    fetch('/sales/transport', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken 
      },
      body: JSON.stringify({ name, gst, contact, vehicles })
    }).then(r => r.json()).then(d => {
      if (d.success) { 
        this.toast(d.message); 
        this.navigate('sales/action');
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
      const id    = row.querySelector('.o-prod-id').value;
      const grade = row.querySelector('.o-prod-grade').value;
      const qty   = Number(row.querySelector('.o-prod-qty').value);
      const price = Number(row.querySelector('.o-prod-price').value);
      if (id && grade && qty > 0 && price > 0) items.push({ product_id: id, grade, quantity: qty, price });
    });

    if (items.length === 0) return this.toast('Add valid products and grades', 'error');

    fetch('/sales/order', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken 
      },
      body: JSON.stringify({ company_id: companyId, transporter_id: transportId, notes, items })
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { this.toast(d.message); setTimeout(() => this.navigate('home'), 600); }
      else this.toast(d.message || 'Error creating order', 'error');
    })
    .catch(() => this.toast('Network error.', 'error'));
  },

  // --- ROLE: DISPATCH ---
  renderDispatchHome(container) {
    const tab = window.dispatchTab || 'PENDING';
    const pending = DB.get('pendingOrders') || [];
    const completed = DB.get('completedOrders') || [];
    const filtered = tab === 'PENDING' ? pending : completed;
    
    const page = window.currentPage || 1;
    const { paginated, totalPages } = this.paginate(filtered, page, 10);

    let html = `
      <div class="flex-between mb-1">
        <h2 style="margin:0;">Dispatches</h2>
      </div>
      <div class="tabs">
        <div class="tab-btn ${tab==='PENDING'?'active':''}" onclick="window.dispatchTab='PENDING'; window.currentPage=1; app.refreshCurrentView()">${this.t('Pending')}</div>
        <div class="tab-btn ${tab==='COMPLETED'?'active':''}" onclick="window.dispatchTab='COMPLETED'; window.currentPage=1; app.refreshCurrentView()">${this.t('Completed')}</div>
      </div>
    `;

    if (paginated.length === 0) {
      html += `<p class="text-center text-muted card">No ${tab.toLowerCase()} dispatches available.</p>`;
    } else {
      html += paginated.map(o => {
          const totalQty = o.totalQty || 0;
          const dispatchedQty = o.dispatchedQty || 0;
          const pct = totalQty > 0 ? Math.round((dispatchedQty / totalQty) * 100) : 0;
          const progressColor = pct === 0 ? 'var(--warning)' : 'var(--secondary)';
          return `
          <div class="card">
            <div class="flex-between mb-1">
              <span style="font-weight:bold;">Order #${String(o.id).toUpperCase()}</span>
              ${tab === 'PENDING' ? `<button class="btn btn-sm" onclick="localStorage.setItem('auto_dispatch_id', '${o.id}'); app.navigate('action');">Dispatch</button>` : `<span class="badge badge-done">Done</span>`}
            </div>
            <div style="font-size:0.85rem; color:var(--text-muted);">
              To: ${(DB.get('companies') || []).find(c=>c.id==o.companyId)?.name} <br>
              Via: ${(DB.get('transportCompanies') || []).find(t=>t.id==o.transportId)?.name}
            </div>
            ${tab === 'PENDING' && totalQty > 0 ? `
              <div style="margin-top:10px;">
                <div style="display:flex; justify-content:space-between; font-size:0.75rem; margin-bottom:4px;">
                  <span style="color:var(--text-muted);">Dispatch Progress</span>
                  <span style="color:${progressColor}; font-weight:bold;">${dispatchedQty}/${totalQty} kg (${pct}%)</span>
                </div>
                <div style="background:rgba(255,255,255,0.1); border-radius:6px; height:6px; overflow:hidden;">
                  <div style="background:${progressColor}; height:100%; width:${pct}%; border-radius:6px; transition:width 0.3s;"></div>
                </div>
              </div>
            ` : ''}
          </div>
        `}).join('');
    }
      
    html += this.renderPaginationControls(page, totalPages);
    container.innerHTML = html;
  },

  renderDispatchAdd(container) {
    const orders = DB.get('pendingOrders') || [];
    container.innerHTML = `
      <div class="card">
        <div class="card-title">Dispatch Order</div>
        <div class="form-group">
          <label>Select Pending Order</label>
          <select id="dispatch-order" onchange="app.onDispatchOrderSelect(this.value)">
            <option value="" disabled selected>-- Select Order --</option>
            ${orders.map(o => `<option value="${o.id}">#${String(o.id).toUpperCase()} - ${(DB.get('companies') || []).find(c=>c.id===o.companyId)?.name || 'Unknown'}</option>`).join('')}
          </select>
        </div>
        
        <div id="order-preview" style="display:none; background:rgba(0,0,0,0.1); padding:1rem; border-radius:8px; margin-bottom:1.5rem; font-size:0.9rem;"></div>
        
        <div class="form-group mt-1">
          <label>Upload Lorry Receipt (LR) Copy <span style="font-weight:normal; color:var(--text-muted); font-size:0.75rem;">(Optional - Upload Later Allowed)</span></label>
          <div class="image-upload-wrapper" onclick="document.getElementById('dispatch-lr').click()">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-muted); margin-bottom:10px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            <div style="font-size:0.9rem; color:var(--text-muted);">Click to upload LR Image</div>
            <input type="file" id="dispatch-lr" accept="image/*" style="display:none;" onchange="app.previewLR(event)">
            <img id="lr-preview" class="image-preview">
          </div>
        </div>
        
        <button class="btn mt-2" onclick="app.submitDispatch()">Dispatch Items</button>
      </div>
    `;

    // Check for auto-select
    const autoId = localStorage.getItem('auto_dispatch_id');
    if(autoId) {
      localStorage.removeItem('auto_dispatch_id');
      const select = document.getElementById('dispatch-order');
      if(select) {
        select.value = autoId;
        this.onDispatchOrderSelect(autoId);
      }
    }
  },

  onDispatchOrderSelect(id) {
    const orders = DB.get('pendingOrders') || [];
    const o = orders.find(x => x.id == id);
    const div = document.getElementById('order-preview');
    if(o) {
      div.style.display = 'block';
      div.classList.add('animation-fadeIn');
      const itemRows = (o.items || []).map((i, idx) => {
        const remaining = i.remainingQty ?? (i.quantity - (i.dispatchedQty || 0));
        const alreadyDispatched = i.dispatchedQty || 0;
        if (remaining <= 0) return ''; // skip fully dispatched items
        return `
        <div style="display:flex; flex-direction:column; gap:6px; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.05);">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span style="font-weight:500;">${i.productName} (${i.grade})</span>
            <span style="color:var(--text-muted); font-size:0.8rem;">
              Total: ${i.quantity} kg
              ${alreadyDispatched > 0 ? ` · Already sent: ${alreadyDispatched} kg` : ''}
            </span>
          </div>
          <div style="display:flex; align-items:center; gap:10px;">
            <label style="font-size:0.75rem; color:var(--secondary); white-space:nowrap; margin:0;">Dispatch Qty (max ${remaining} kg):</label>
            <input type="number" class="dispatch-item-qty" data-item-id="${i.id}" data-max="${remaining}" 
                   value="${remaining}" max="${remaining}" min="0.001" step="0.001"
                   style="flex:1; padding:0.6rem; font-size:1rem; font-weight:bold; color:var(--secondary); background:rgba(0,0,0,0.2); border:1px solid var(--glass-border); border-radius:8px;">
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

          <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:15px; border-left:4px solid var(--secondary);">
            <div style="display:flex; align-items:center; gap:8px; color:var(--text-muted); font-size:0.7rem; text-transform:uppercase; margin-bottom:10px; font-weight:bold;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
              Items to Dispatch (Edit quantities for partial dispatch)
            </div>
            ${itemRows || '<div class="text-center py-1">All items already dispatched</div>'}
          </div>
        </div>
      `;
    } else {
      div.style.display = 'none';
    }
  },

  previewLR(event) {
    const reader = new FileReader();
    reader.onload = function(){
      const img = document.getElementById('lr-preview');
      img.src = reader.result;
      img.style.display = 'block';
      window.currentLRImage = reader.result;
    };
    if(event.target.files[0]) reader.readAsDataURL(event.target.files[0]);
  },

  submitDispatch() {
    const orderId = document.getElementById('dispatch-order').value;
    if (!orderId) return this.toast('Select an order', 'error');

    // Collect per-item dispatch quantities
    const itemInputs = document.querySelectorAll('.dispatch-item-qty');
    if (itemInputs.length === 0) return this.toast('No items to dispatch', 'error');

    const items = [];
    let hasError = false;
    itemInputs.forEach(input => {
      const qty = Number(input.value);
      const max = Number(input.dataset.max);
      const itemId = input.dataset.itemId;
      
      if (qty > 0) {
        if (qty > max) {
          this.toast(`Quantity exceeds remaining (max: ${max} kg)`, 'error');
          hasError = true;
        }
        items.push({ order_item_id: itemId, quantity: qty });
      }
    });

    if (hasError) return;
    if (items.length === 0) return this.toast('Enter at least one item quantity to dispatch', 'error');

    fetch('/dispatch/action', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken 
      },
      body: JSON.stringify({ order_id: orderId, items: items, lr_image: window.currentLRImage })
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

  handleLateLRUpload(event, logId, idx) {
    const reader = new FileReader();
    reader.onload = () => {
      const payload = { log_id: logId, lr_image: reader.result };
      fetch('/dispatch/update-lr', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          this.toast(d.message);
          // Update local data
          const log = window._historyLogs[idx];
          if (log) log.lrImage = d.lr_url;
          this.openDispatchDrawer(idx);
        } else {
          this.toast(d.message, 'error');
        }
      });
    };
    if (event.target.files[0]) reader.readAsDataURL(event.target.files[0]);
  },

  // --- ROLE: CASHIER ---
  renderCashierHome(container) {
    const txs = DB.get('transactions');
    const balance = txs.reduce((sum, t) => t.type === 'IN' ? sum + Number(t.amount) : sum - Number(t.amount), 0);
    
    container.innerHTML = `
      <div class="card" style="text-align: center; padding: 3rem 1rem; background: linear-gradient(135deg, rgba(79,70,229,0.2), rgba(16,185,129,0.1));">
        <div style="font-size:1.1rem; color:var(--text-muted); margin-bottom: 0.5rem;">Current Ledger Balance</div>
        <h1 style="font-size:3rem; color:var(--text-main);">₹${balance.toLocaleString()}</h1>
      </div>
      ${this.renderRecentPOs()}
    `;
  },

  renderCashierAdd(container) {
    // Default categories + user-added ones from localStorage
    const defaultCats = [
      { value: 'general', label: 'General' },
      { value: 'small', label: 'Small Expense (< ₹5,000)' },
      { value: 'big', label: 'Big Expense (≥ ₹5,000)' },
      { value: 'salary', label: 'Salary' },
      { value: 'transport', label: 'Transport' },
      { value: 'maintenance', label: 'Maintenance' },
      { value: 'raw_material', label: 'Raw Material Purchase' },
      { value: 'utilities', label: 'Utilities (Electricity/Water)' },
    ];
    const customCats = JSON.parse(localStorage.getItem('cashier_custom_categories') || '[]');
    const allCats = [...defaultCats, ...customCats];

    container.innerHTML = `
      <div class="card">
        <div class="card-title">New Transaction</div>
        
        <div class="tabs">
          <div class="tab-btn active" onclick="document.querySelectorAll('.tab-btn').forEach(e=>e.classList.remove('active')); this.classList.add('active'); window.txType='IN';">INCOME (IN)</div>
          <div class="tab-btn" onclick="document.querySelectorAll('.tab-btn').forEach(e=>e.classList.remove('active')); this.classList.add('active'); window.txType='OUT';">EXPENSE (OUT)</div>
        </div>
        
        <div class="form-group mt-1">
          <label>Expense Category</label>
          <div style="display:flex; gap:8px; align-items:stretch;">
            <select id="tx-category" style="flex:1;">
              ${allCats.map(c => `<option value="${c.value}">${c.label}</option>`).join('')}
            </select>
            <button class="btn btn-secondary" onclick="app.addNewExpenseCategory()" style="width:auto; padding:0.5rem 1rem; white-space:nowrap; font-size:0.8rem;">+ New</button>
          </div>
        </div>
        
        <div class="form-group">
          <label>Amount (₹)</label>
          <input type="number" id="tx-amount" placeholder="0.00" style="font-size:1.3rem; padding:0.8rem;">
        </div>
        <div class="form-group">
          <label>Particulars / Note</label>
          <input type="text" id="tx-note" placeholder="Description of transaction">
        </div>
        <div class="form-group">
          <label>Reference / Bill No. (optional)</label>
          <input type="text" id="tx-ref" placeholder="e.g. INV-2024-001">
        </div>
        <button class="btn mt-1" onclick="app.submitTransaction()">Save Transaction</button>
      </div>
    `;
    window.txType = 'IN';
  },

  addNewExpenseCategory() {
    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Add New Category</h3>
      <div class="form-group">
        <label>Category Name</label>
        <input type="text" id="new-cat-name" placeholder="e.g. Packaging, Office Supplies..." style="font-size:1rem;">
      </div>
      <div style="display:flex; gap:10px; margin-top:1rem;">
        <button class="btn btn-secondary" style="flex:1;" onclick="app.closeDrawer()">Cancel</button>
        <button class="btn" style="flex:2;" onclick="app.saveNewExpenseCategory()">Save Category</button>
      </div>
    `);
  },

  saveNewExpenseCategory() {
    const nameInput = document.getElementById('new-cat-name');
    const name = nameInput.value.trim();
    if (!name) return this.toast('Enter a category name', 'error');
    
    const value = name.toLowerCase().replace(/[^a-z0-9]+/g, '_');
    const customCats = JSON.parse(localStorage.getItem('cashier_custom_categories') || '[]');
    
    // Check for duplicates
    if (customCats.some(c => c.value === value)) {
      return this.toast('This category already exists', 'error');
    }
    
    customCats.push({ value, label: name });
    localStorage.setItem('cashier_custom_categories', JSON.stringify(customCats));
    
    this.closeDrawer();
    this.toast(`Category "${name}" added!`);
    
    // Re-render the form to include the new category
    const container = document.getElementById('app-content');
    this.renderCashierAdd(container);
    
    // Auto-select the newly added category
    setTimeout(() => {
      const select = document.getElementById('tx-category');
      if (select) select.value = value;
    }, 100);
  },

  submitTransaction() {
    const amount   = Number(document.getElementById('tx-amount').value);
    const note     = document.getElementById('tx-note').value;
    const category = document.getElementById('tx-category').value;
    const ref      = document.getElementById('tx-ref').value;
    if (!amount || amount <= 0) return this.toast('Valid amount required', 'error');

    fetch('/cashier/action', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken 
      },
      body: JSON.stringify({ type: window.txType, amount, note, category, reference: ref })
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { this.toast(d.message); setTimeout(() => this.navigate('home'), 600); }
      else this.toast(d.message || 'Error', 'error');
    })
    .catch(() => this.toast('Network error.', 'error'));
  },

  downloadCashierPdf() {
    // Get today and 30 days ago as defaults
    const today = new Date().toISOString().split('T')[0];
    const monthAgo = new Date(new Date().getFullYear(), new Date().getMonth() - 1, new Date().getDate()).toISOString().split('T')[0];

    this.openDrawer(`
      <h3 style="margin-bottom:1.2rem;">Download PDF Report</h3>
      <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1.5rem;">
        Select a date range to generate your transaction history report.
      </p>
      <div class="form-group">
        <label>From Date</label>
        <input type="date" id="pdf-from" value="${monthAgo}" style="font-size:1rem; padding:0.7rem;">
      </div>
      <div class="form-group">
        <label>To Date</label>
        <input type="date" id="pdf-to" value="${today}" max="${today}" style="font-size:1rem; padding:0.7rem;">
      </div>
      <div style="display:flex; gap:8px; margin-top:0.5rem;">
        <button class="btn btn-sm" onclick="app.setPdfRange('week')" style="flex:1; font-size:0.75rem;">Last 7 Days</button>
        <button class="btn btn-sm" onclick="app.setPdfRange('month')" style="flex:1; font-size:0.75rem;">Last 30 Days</button>
        <button class="btn btn-sm" onclick="app.setPdfRange('year')" style="flex:1; font-size:0.75rem;">Last Year</button>
        <button class="btn btn-sm" onclick="app.setPdfRange('all')" style="flex:1; font-size:0.75rem;">All Time</button>
      </div>
      <button class="btn mt-2" onclick="app.confirmCashierPdf()" style="width:100%; padding:1rem; font-size:1.1rem; display:flex; align-items:center; justify-content:center; gap:8px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Generate & Download PDF
      </button>
      <button class="btn btn-secondary mt-1" onclick="app.closeDrawer()" style="width:100%;">Cancel</button>
    `);
  },

  setPdfRange(preset) {
    const today = new Date();
    const toStr = today.toISOString().split('T')[0];
    let fromStr = '';

    if (preset === 'week') {
      fromStr = new Date(today - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    } else if (preset === 'month') {
      fromStr = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate()).toISOString().split('T')[0];
    } else if (preset === 'year') {
      fromStr = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate()).toISOString().split('T')[0];
    } else if (preset === 'all') {
      fromStr = '2020-01-01';
    }

    document.getElementById('pdf-from').value = fromStr;
    document.getElementById('pdf-to').value = toStr;
    this.toast(`Range set: ${preset}`, 'success');
  },

  confirmCashierPdf() {
    const from = document.getElementById('pdf-from').value;
    const to = document.getElementById('pdf-to').value;

    if (!from || !to) return this.toast('Please select both dates', 'error');
    if (from > to) return this.toast('From date must be before To date', 'error');

    const url = `/cashier/history/pdf?from=${from}&to=${to}`;
    window.open(url, '_blank');
    this.closeDrawer();
    this.toast('PDF is being generated...');
  },

  // --- GENERAL HISTORY & ROUTING ---
  renderHome(container) {
    if (!this.currentUser) return console.warn("No current user for rendering home");
    const role = this.currentUser.role;
    if(role === 'RAW') this.renderRawHome(container);
    else if(role === 'SEMI') this.renderProductionHome(container, 'SEMI');
    else if(role === 'FINISHED') this.renderProductionHome(container, 'FINISHED');
    else if(role === 'SALES') this.renderSalesHome(container);
    else if(role === 'DISPATCH') this.renderDispatchHome(container);
    else if(role === 'CASHIER') this.renderCashierHome(container);
    else if(role === 'ATTENDANCE') this.renderAttendanceHome(container);
  },

  renderAction(container) {
    const role = this.currentUser.role;
    if(role === 'RAW') this.renderRawAdd(container);
    else if(role === 'SEMI') this.renderProductionAdd(container, 'SEMI');
    else if(role === 'FINISHED') this.renderProductionAdd(container, 'FINISHED');
    else if(role === 'SALES') this.renderSalesAdd(container);
    else if(role === 'DISPATCH') this.renderDispatchAdd(container);
    else if(role === 'CASHIER') this.renderCashierAdd(container);
    else if(role === 'ATTENDANCE') this.renderAttendanceAction(container);
  },

  renderHistory(container) {
    const role = this.currentUser.role;
    const page = window.currentPage || 1;
    let logs = [];
    let renderFn = null;
    
    if(role === 'CASHIER') {
      logs = (DB.get('transactions') || []).sort((a,b)=>new Date(b.date) - new Date(a.date));
      // Store logs globally for drawer access
      window._historyLogs = logs;
      renderFn = (t, idx) => `
        <div class="list-item" onclick="app.openCashierDrawer(${idx})" style="cursor:pointer;">
          <div class="list-item-content">
            <div class="list-item-title">${t.note || 'Transaction'}</div>
            <div class="list-item-meta">${t.category ? t.category.toUpperCase()+' \u00b7 ' : ''}${new Date(t.date).toLocaleDateString()}</div>
          </div>
          <div class="list-item-right">
            <div style="font-weight:bold; color:${t.type==='IN'?'var(--secondary)':'var(--danger)'}">
              ${t.type==='IN'?'+':'-'}\u20b9${t.amount.toLocaleString()}
            </div>
          </div>
        </div>
      `;
    } else if (role === 'RAW') {
      logs = (DB.get('rawStockHistory') || []).sort((a,b)=>new Date(b.date)-new Date(a.date));
      window._historyLogs = logs;
      renderFn = (l, idx) => {
        const p = (DB.get('rawMaterialsList') || []).find(x=>x.id==l.productId) || {};
        const dateStr = l.date ? new Date(l.date).toLocaleString() : 'N/A';
        return `<div class="list-item" onclick="app.openRawDrawer(${idx})" style="cursor:pointer;">
          <div style="display:flex; align-items:center; gap:10px; width:100%;">
            <img src="${p.image_url||''}" style="width:36px; height:36px; border-radius:6px; object-fit:cover;" onerror="this.src='https://via.placeholder.com/100x60?text=IMG'">
            <div class="list-item-content">
              <div class="list-item-title">${l.quantity > 0 ? '+' : ''}${l.quantity} kg ${p.name||'Unknown'}</div>
              <div class="list-item-meta">${l.grade !== 'NONE' ? l.grade + ' \u00b7 ' : ''}${dateStr}</div>
            </div>
          </div>
        </div>`;
      };
    } else if (role === 'SEMI' || role === 'FINISHED') {
      logs = (DB.get('productionLogs') || []).filter(l => l.type === role).sort((a,b)=>new Date(b.date)-new Date(a.date));
      window._historyLogs = logs;
      renderFn = (l, idx) => {
        const pName = (DB.get('products') || []).find(x=>x.id===l.outputProductId)?.name || (DB.get('rawMaterialsList') || []).find(x=>x.id===l.outputProductId)?.name || l.outputName || 'Unknown Product';
        const dateStr = l.date ? new Date(l.date).toLocaleString() : 'N/A';
        
        const inputList = (l.consumedInputs || []).map(i => {
          const p = (DB.get('rawMaterialsList') || []).find(x=>x.id==i.productId) || (DB.get('products') || []).find(x=>x.id==i.productId) || {};
          return `${i.quantity}kg ${p.name || i.name || 'Material'}`;
        }).join(', ');

        return `<div class="list-item" onclick="app.openProductionDrawer(${idx})" style="cursor:pointer;">
          <div class="list-item-content">
            <div class="list-item-title">Produced ${l.outputQty || 0}kg ${pName} (${l.outputGrade}) ${l.notes ? `\u00b7 Notes: ${l.notes}` : ''}</div>
            <div class="list-item-meta">
              <div style="color:var(--secondary); margin-bottom:4px;">${dateStr}</div>
              <div style="font-size:0.75rem; color:var(--text-muted); line-height:1.2;">Using: ${inputList}</div>
            </div>
          </div>
        </div>`;
      };
    } else if (role === 'SALES') {
      // Combine all sales activities into one timeline
      const orders = (DB.get('orders') || []).map(o => ({...o, _type: 'ORDER', date: o.date}));
      const companies = (DB.get('companies') || []).filter(c=>c.date).map(c => ({...c, _type: 'COMPANY', date: c.date}));
      const transports = (DB.get('transportCompanies') || []).filter(t=>t.date).map(t => ({...t, _type: 'TRANSPORT', date: t.date}));
      logs = [...orders, ...companies, ...transports].sort((a,b) => new Date(b.date) - new Date(a.date));
      
      if (window.salesCategoryFilter) {
        logs = logs.filter(item => {
          if (item._type !== 'ORDER') return false;
          return item.products && item.products.some(p => {
            const prod = DB.get('products').find(x => x.id == p.productId);
            return prod && prod.type === window.salesCategoryFilter;
          });
        });
      }
      window._historyLogs = logs;
      renderFn = (item, idx) => {
        if (item._type === 'ORDER') {
          const compName = (DB.get('companies') || []).find(c=>c.id===item.companyId)?.name || 'Unknown';
          return `<div class="list-item" onclick="app.openSalesDrawer(${idx})" style="cursor:pointer;">
            <div class="list-item-content">
              <div class="list-item-title">#${String(item.id).toUpperCase()} — ${compName}</div>
              <div class="list-item-meta"><span class="badge badge-open" style="font-size:0.6rem;">ORDER</span> · ${new Date(item.date).toLocaleDateString()} · <span class="badge ${item.status==='OPEN'?'badge-open':'badge-closed'}" style="font-size:0.6rem;">${item.status}</span></div>
            </div>
            <div class="list-item-right">
              <div style="font-weight:bold; color:var(--secondary);">₹${(item.total||0).toLocaleString()}</div>
            </div>
          </div>`;
        } else if (item._type === 'COMPANY') {
          return `<div class="list-item" onclick="app.openSalesCompanyDrawer(${idx})" style="cursor:pointer;">
            <div class="list-item-content">
              <div class="list-item-title">${item.name}</div>
              <div class="list-item-meta"><span class="badge badge-pending" style="font-size:0.6rem;">COMPANY</span> · ${new Date(item.date).toLocaleDateString()}</div>
            </div>
          </div>`;
        } else {
          return `<div class="list-item" onclick="app.openSalesTransportDrawer(${idx})" style="cursor:pointer;">
            <div class="list-item-content">
              <div class="list-item-title">${item.name}</div>
              <div class="list-item-meta"><span class="badge badge-done" style="font-size:0.6rem;">TRANSPORT</span> · ${new Date(item.date).toLocaleDateString()}</div>
            </div>
          </div>`;
        }
      };
    } else if (role === 'DISPATCH') {
      logs = (DB.get('dispatchLogs') || []).sort((a,b)=>new Date(b.date)-new Date(a.date));
      window._historyLogs = logs;
      renderFn = (d, idx) => {
        const lrStatus = d.lrImage ? '<span class="badge badge-done" style="font-size:0.6rem;">LR UPLOADED</span>' : '<span class="badge badge-pending" style="font-size:0.6rem;">LR PENDING</span>';
        return `<div class="list-item" onclick="app.openDispatchDrawer(${idx})" style="cursor:pointer;">
          <div class="list-item-content">
            <div class="list-item-title">Order #${String(d.orderId || '').toUpperCase()}</div>
            <div class="list-item-meta">${lrStatus} · ${new Date(d.date).toLocaleString()}</div>
          </div>
        </div>`;
      };
    } else if (role === 'ATTENDANCE') {
      this.renderAttendanceHistory(container);
      return;
    }

    // Apply filters
    if (logs.length > 0) {
      logs = this.filterByDateRange(logs, window.currentDateRange, window.customStart, window.customEnd);
      
      const q = (window.historySearchQuery||'').toLowerCase();
      if(q) {
        logs = logs.filter(l => JSON.stringify(l).toLowerCase().includes(q));
      }
    }
    
    // Refresh indices logic so drawer keeps pointing to right original index in window._historyLogs?
    // Actually, it's safer to store filtered logs in window._historyLogs for drawer mapping
    window._historyLogs = logs;

    let html = `
      <div class="flex-between mb-1">
        <h2 style="margin:0;">${this.t('History')}</h2>
        ${role === 'CASHIER' ? `
          <button class="btn btn-sm btn-secondary" onclick="app.downloadCashierPdf()" style="width:auto; display:flex; align-items:center; gap:6px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Download PDF
          </button>
        ` : ''}
      </div>
      
      ${this.renderDateFilterControls("app.refreshCurrentView")}
      <div style="display:flex; gap:10px; margin-bottom:1rem;">
        <div class="form-group" style="flex:2; margin:0;">
          <input type="text" placeholder="${this.t('Search history...')}" value="${window.historySearchQuery||''}" oninput="window.historySearchQuery=this.value; app.refreshCurrentView()">
        </div>
        ${role === 'SALES' ? `
          <div class="form-group" style="flex:1; margin:0;">
            <select onchange="window.salesCategoryFilter=this.value; window.currentPage=1; app.refreshCurrentView()" style="padding:0.7rem;">
              <option value="">All Types</option>
              <option value="RAW" ${window.salesCategoryFilter==='RAW'?'selected':''}>Raw</option>
              <option value="SEMI" ${window.salesCategoryFilter==='SEMI'?'selected':''}>Semi</option>
              <option value="FINISHED" ${window.salesCategoryFilter==='FINISHED'?'selected':''}>Finished</option>
            </select>
          </div>
        ` : ''}
      </div>
      
      <div class="responsive-grid">
    `;
    
    if (logs.length > 0 && renderFn) {
      const { paginated, totalPages } = this.paginate(logs, page, 10);
      html += paginated.map((item, idx) => renderFn(item, (page-1)*10 + idx)).join('');
      html += `</div>` + this.renderPaginationControls(page, totalPages);
    } else if (!renderFn) {
      html += `<p class="text-muted text-center card" style="padding:2rem; grid-column: 1/-1;">Full historical audit logs are available in the Admin Panel.</p></div>`;
    } else {
      html += `<p class="card text-center text-muted" style="padding:2rem; grid-column: 1/-1;">No history found for current filters.</p></div>`;
    }
    
    container.innerHTML = html;
  },

  openCashierDrawer(idx) {
    const t = window._historyLogs[idx];
    if(!t) return;
    const color = t.type==='IN' ? 'var(--secondary)' : 'var(--danger)';
    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Transaction Details</h3>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem;">
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Type</div><div style="font-weight:600;">${t.type}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Amount</div><div style="font-weight:700; font-size:1.2rem; color:${color}">\u20b9${t.amount.toLocaleString()}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Category</div><div>${t.category||'General'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Date</div><div>${new Date(t.date).toLocaleString()}</div></div>
      </div>
      <div style="margin-top:1rem;"><div style="color:var(--text-muted); font-size:0.8rem;">Note</div><div>${t.note||'\u2014'}</div></div>
      ${t.ref ? '<div style="margin-top:0.5rem;"><div style="color:var(--text-muted); font-size:0.8rem;">Reference</div><div>'+t.ref+'</div></div>' : ''}
      <button class="btn btn-secondary mt-2" onclick="app.closeDrawer()" style="width:100%;">Close</button>
    `);
  },

  openRawDrawer(idx) {
    const l = window._historyLogs[idx];
    if(!l) return;
    const p = DB.get('rawMaterialsList').find(x=>x.id===l.productId) || {};
    this.openDrawer(`
      <div style="text-align:center;">
        <img src="${p.image||''}" style="width:100%; max-height:200px; object-fit:cover; border-radius:12px; margin-bottom:1rem;">
        <h3>${p.name||'Unknown'}</h3>
        <div style="font-size:1.5rem; font-weight:700; color:var(--secondary); margin:0.5rem 0;">+ ${l.quantity} ${p.unit||'kg'}</div>
        <div style="color:var(--text-muted); font-size:0.85rem;">${new Date(l.date).toLocaleString()}</div>
      </div>
      <button class="btn btn-secondary mt-2" onclick="app.closeDrawer()" style="width:100%;">Close</button>
    `);
  },

  openProductionDrawer(idx) {
    const l = window._historyLogs[idx];
    if(!l) return;
    const pName = DB.get('products').find(x=>x.id===l.outputProductId)?.name || DB.get('rawMaterialsList').find(x=>x.id===l.outputProductId)?.name;
    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Production Details</h3>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem;">
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Product</div><div style="font-weight:600;">${pName}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Grade</div><div>${l.outputGrade}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Total Quantity</div><div style="font-weight:700; font-size:1.2rem; color:var(--secondary);">${l.outputQty} kg</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Date</div><div>${new Date(l.date).toLocaleString()}</div></div>
        ${l.notes ? `
          <div><div style="color:var(--text-muted); font-size:0.8rem;">Notes</div><div style="font-weight:600;">${l.notes}</div></div>
        ` : ''}
      </div>
      <div style="margin-top:1.5rem; margin-bottom:1rem;">
        <div style="font-size:0.9rem; font-weight:600; color:var(--primary-light); margin-bottom:0.5rem; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:4px;">Consumed Materials</div>
        <div style="display:flex; flex-direction:column; gap:8px;">
          ${(l.consumedInputs || []).map(i => {
            const p = (DB.get('rawMaterialsList') || []).find(x=>x.id==i.productId) || (DB.get('products') || []).find(x=>x.id==i.productId) || {};
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
    const comp = DB.get('companies').find(c=>c.id===o.companyId) || {};
    const trans = DB.get('transportCompanies').find(t=>t.id===o.transportId) || {};
    const products = DB.get('products');
    const rmList = DB.get('rawMaterialsList') || [];
    
    const prodRows = (o.items || []).map(p => {
      const prod = DB.get('products').find(x => x.id == p.productId);
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
      <button class="btn btn-secondary" onclick="app.closeDrawer()" style="width:100%;">Close</button>
    `);
  },

  openSalesCompanyDrawer(idx) {
    const c = window._historyLogs[idx];
    if(!c) return;
    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Company Added</h3>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem; margin-bottom:1rem;">
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Name</div><div style="font-weight:600;">${c.name}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">GST</div><div>${c.gst||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Contact</div><div>${c.contact||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Date</div><div>${c.date ? new Date(c.date).toLocaleString() : 'N/A'}</div></div>
      </div>
      ${c.address ? '<div style="margin-bottom:1rem;"><div style="color:var(--text-muted); font-size:0.8rem;">Address</div><div>'+c.address+'</div></div>' : ''}
      <button class="btn btn-secondary" onclick="app.closeDrawer()" style="width:100%;">Close</button>
    `);
  },

  openSalesTransportDrawer(idx) {
    const t = window._historyLogs[idx];
    if(!t) return;
    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Transport Added</h3>
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
    const order = DB.get('orders').find(o=>o.id == d.orderId) || {};
    const comp = DB.get('companies').find(c=>c.id==order.companyId) || {};
    const trans = DB.get('transportCompanies').find(t=>t.id==(d.transportId||order.transportId)) || {};
    const user = DB.get('users').find(u=>u.id==d.userId) || {};
    
    const itemsHtml = (d.items && d.items.length > 0) ? `
      <div style="margin-bottom:1rem; background:rgba(0,0,0,0.2); border-radius:8px; padding:12px; border-left:3px solid var(--secondary);">
        <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; margin-bottom:8px; font-weight:bold;">Items Dispatched in this Round</div>
        ${d.items.map(i => `
          <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.9rem;">
            <span>${i.productName || 'Unknown'} <span style="color:var(--text-muted); font-size:0.75rem;">(${i.grade})</span></span>
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
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Company</div><div>${d.companyName||comp.name||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Transport</div><div>${d.transportName||trans.name||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Dispatched By</div><div>${d.dispatchedBy||user.name||'System'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Order Value</div><div style="font-weight:700; color:var(--secondary);">\u20b9${(d.orderTotal||order.total||0).toLocaleString()}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">LR Status</div><div>${d.lrImage ? '<span class="badge badge-done">UPLOADED</span>' : '<span class="badge badge-pending">PENDING</span>'}</div></div>
      </div>
      ${itemsHtml}
      ${d.lrImage ? `
        <div style="margin-bottom:1rem;">
          <div style="color:var(--text-muted); font-size:0.8rem; margin-bottom:0.5rem;">LR Copy</div>
          <img src="${d.lrImage}" style="width:100%; border-radius:10px; max-height:200px; object-fit:contain; cursor:pointer;" onclick="app.viewImage(this.src)">
          <button class="btn btn-sm btn-secondary mt-1" style="width:100%; font-size:0.7rem;" onclick="document.getElementById('late-lr-input').click()">Update LR Copy</button>
        </div>
      ` : `
        <div style="margin-bottom:1rem; padding:1.5rem; background:rgba(255,165,0,0.05); border:1px dashed rgba(255,165,0,0.3); border-radius:12px; text-align:center;">
          <div style="color:var(--warning); font-weight:600; font-size:0.9rem; margin-bottom:10px;">LR Copy Pending</div>
          <button class="btn btn-secondary" style="width:100%;" onclick="document.getElementById('late-lr-input').click()">Upload LR Now</button>
        </div>
      `}
      <input type="file" id="late-lr-input" accept="image/*" style="display:none;" onchange="app.handleLateLRUpload(event, ${d.id}, ${idx})">
      <button class="btn btn-secondary" onclick="app.closeDrawer()" style="width:100%;">Close</button>
    `);
  },

  // --- ADMIN DESKTOP PANEL ---
  adminActiveTab(element) {
    if(!element.classList.contains('active')) window.currentPage = 1;
    document.querySelectorAll('.admin-sidebar .nav-item').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
  },

  renderAdminDashboard(el) {
    this.adminActiveTab(el);
    const container = document.getElementById('content-area');
    
    const rStock = this.getAggregatedStock('rawStock').reduce((s,i)=>s+i.quantity, 0);
    const sStock = this.getAggregatedStock('semiStock').reduce((s,i)=>s+i.quantity, 0);
    const fStock = this.getAggregatedStock('finishedStock').reduce((s,i)=>s+i.quantity, 0);
    const orders = DB.get('orders');
    const revenue = orders.reduce((s,o)=>s+(o.total||0), 0);
    
    container.innerHTML = `
      <h2 style="margin-bottom: 1.5rem;">System Overview</h2>
      <div class="dashboard-grid">
        <div class="stat-card">
          <div style="color:var(--primary-light)">Total Raw Stock</div>
          <div class="stat-value">${rStock.toLocaleString()}</div>
        </div>
        <div class="stat-card">
          <div style="color:var(--warning)">Total Semi Stock</div>
          <div class="stat-value">${sStock.toLocaleString()}</div>
        </div>
        <div class="stat-card">
          <div style="color:var(--secondary)">Total Finished Stock</div>
          <div class="stat-value">${fStock.toLocaleString()}</div>
        </div>
        <div class="stat-card">
          <div style="color:var(--info)">Sales Orders</div>
          <div class="stat-value">${orders.length}</div>
        </div>
        <div class="stat-card" style="grid-column: 1 / -1; background:var(--dark-panel);">
          <div style="color:var(--text-muted)">Est. Order Value</div>
          <div class="stat-value" style="color:var(--secondary)">₹${revenue.toLocaleString()}</div>
        </div>
      </div>
    `;
  },

  renderAdminUsers(el) {
    if(el) this.adminActiveTab(el);
    const users = DB.get('users');
    const container = document.getElementById('content-area');
    const page = window.currentPage || 1;
    const { paginated, totalPages } = this.paginate(users, page, 10);
    
    container.innerHTML = `
      <div class="flex-between mb-1" style="flex-wrap: wrap; gap: 10px;">
        <h2 style="margin:0;">User Management</h2>
        <button class="btn btn-sm btn-secondary" onclick="app.openUserModal()" style="width: auto;">+ Create User</button>
      </div>
      <div class="table-container">
        <table>
          <thead><tr><th>Name</th><th>Role</th><th>Manager</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            ${paginated.map(u => `
              <tr>
                <td><strong>${u.name}</strong></td>
                <td><span class="badge badge-pending">${u.role}</span></td>
                <td>${u.parentId ? (users.find(x=>x.id===u.parentId)?.name || u.parentId) : 'N/A'}</td>
                <td>
                  ${u.id == window.authUser?.id ? '<span class="badge badge-done">You</span>' : `
                    <label class="switch">
                      <input type="checkbox" ${u.status==='ACTIVE'?'checked':''} onchange="app.toggleUserStatus('${u.id}')">
                      <span class="slider"></span>
                    </label>
                  `}
                </td>
                <td>
                  <div class="action-btns">
                    <button class="btn-icon edit" onclick="app.openUserModal('${u.id}')" title="Edit">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                    </button>
                    ${u.id == window.authUser?.id ? '' : `
                      <button class="btn-icon delete" onclick="app.deleteUser('${u.id}')" title="Delete">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                      </button>
                    `}
                  </div>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
      ${this.renderPaginationControls(page, totalPages)}
    `;
  },

  openUserModal(userId = null) {
    const users = DB.get('users');
    const u = userId ? users.find(x => x.id === userId) : null;
    const salesManagers = users.filter(x => x.role === 'SALES' || x.role === 'ADMIN');
    
    this.openModal(`
      <h3 style="margin-bottom:1rem;">${u ? 'Edit User' : 'Create User'}</h3>
      <div class="form-group">
        <label>Name</label>
        <input type="text" id="user-name" value="${u ? u.name : ''}">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="text" id="user-password" value="${u ? (u.password || '') : ''}" placeholder="Enter user password">
      </div>
      <div class="form-group">
        <label>Role</label>
        <select id="user-role" onchange="document.getElementById('user-parent-group').style.display = this.value === 'SALES' ? 'block' : 'none'">
          <option value="ADMIN" ${u?.role==='ADMIN'?'selected':''}>ADMIN</option>
          <option value="RAW" ${u?.role==='RAW'?'selected':''}>RAW</option>
          <option value="SEMI" ${u?.role==='SEMI'?'selected':''}>SEMI</option>
          <option value="FINISHED" ${u?.role==='FINISHED'?'selected':''}>FINISHED</option>
          <option value="SALES" ${u?.role==='SALES'?'selected':''}>SALES</option>
          <option value="DISPATCH" ${u?.role==='DISPATCH'?'selected':''}>DISPATCH</option>
          <option value="CASHIER" ${u?.role==='CASHIER'?'selected':''}>CASHIER</option>
        </select>
      </div>
      <div class="form-group" id="user-parent-group" style="display:${u?.role==='SALES'?'block':'none'}">
        <label>Sales Manager (Parent)</label>
        <select id="user-parent">
          <option value="">-- None --</option>
          ${salesManagers.map(m => `<option value="${m.id}" ${u?.parentId===m.id?'selected':''}>${m.name} (${m.role})</option>`).join('')}
        </select>
      </div>
      <button class="btn mt-1" onclick="app.submitUser('${userId || ''}')">Save User</button>
      <button class="btn btn-secondary mt-1" onclick="app.closeModal()">Cancel</button>
    `);
  },

  submitUser(userId) {
    const name = document.getElementById('user-name').value;
    const password = document.getElementById('user-password').value;
    const role = document.getElementById('user-role').value;
    const parentId = document.getElementById('user-parent').value;
    
    if(!name) return this.toast('Name required', 'error');
    if(role === 'SALES' && !parentId) return this.toast('Parent required for sales', 'error');
    
    const payload = { user_id: userId, name, password, role, parent_id: parentId };

    fetch('/admin/users/store', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        this.toast(res.message);
        this.closeModal();
        this.renderAdminUsers();
      } else {
        this.toast(res.message || 'Error saving user', 'error');
      }
    });
  },

  toggleUserStatus(id) {
    fetch('/admin/users/toggle', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ user_id: id })
    }).then(r => r.json()).then(d => {
      if (d.success) { this.toast(d.message); this.renderAdminUsers(); }
      else this.toast(d.message, 'error');
    });
  },

  deleteUser(id) {
    Swal.fire({
      title: 'Delete User?',
      text: "This action cannot be undone!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete!'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(`/admin/users/${id}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(r => r.json()).then(d => {
          if (d.success) { this.toast(d.message); this.renderAdminUsers(); }
          else this.toast(d.message, 'error');
        });
      }
    });
  },

  deleteProduct(id) {
    Swal.fire({
      title: 'Delete Product?',
      text: "Stock history will be lost!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(`/admin/products/${id}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(r => r.json()).then(d => {
          if (d.success) { this.toast(d.message); this.renderAdminProducts(); }
          else this.toast(d.message, 'error');
        });
      }
    });
  },

  toggleProductStatus(id) {
    fetch(`/admin/products/toggle/${id}`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken }
    }).then(r => r.json()).then(d => {
      if (d.success) { this.renderAdminProducts(); }
    });
  },

  renderAdminProducts(el) {
    if(el) this.adminActiveTab(el);
    const prods = [...(DB.get('rawMaterialsList')||[]), ...DB.get('products')];
    const container = document.getElementById('content-area');
    const page = window.currentPage || 1;
    const { paginated, totalPages } = this.paginate(prods, page, 10);
    
    container.innerHTML = `
      <div class="flex-between mb-1" style="flex-wrap: wrap; gap: 10px;">
        <h2 style="margin:0;">Products Master</h2>
        <button class="btn btn-sm btn-secondary" onclick="app.openAddProductModal()" style="width: auto;">+ Add Product</button>
      </div>
      <div class="table-container">
        <table>
          <thead><tr><th>Image</th><th>Name</th><th>Type</th><th>Active</th><th>Actions</th></tr></thead>
          <tbody>
            ${paginated.map(p => `
              <tr>
                <td>
                  <img src="${p.image || 'https://image.pollinations.ai/prompt/'+encodeURIComponent(p.name)+'?width=100&height=100&nologo=true'}" 
                       onclick="app.viewImage(this.src)"
                       style="width:40px; height:40px; object-fit:cover; border-radius:6px; cursor:pointer; border:1px solid rgba(255,255,255,0.1);">
                </td>
                <td><strong>${p.name}</strong></td>
                <td><span class="badge ${p.type==='RAW'?'badge-open':(p.type==='SEMI'?'badge-pending':'badge-done')}">${p.type}</span></td>
                <td>
                  <label class="switch">
                    <input type="checkbox" ${p.is_active?'checked':''} onchange="app.toggleProductStatus('${p.id}')">
                    <span class="slider"></span>
                  </label>
                </td>
                <td>
                  <div class="action-btns">
                    <button class="btn-icon edit" onclick="app.openAddProductModal('${p.id}')" title="Edit">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                    </button>
                    <button class="btn-icon delete" onclick="app.deleteProduct('${p.id}')" title="Delete">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                  </div>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
      ${this.renderPaginationControls(page, totalPages)}
    `;
  },

  viewImage(src) {
    this.openModal(`
      <div style="text-align:center; position:relative;">
        <img src="${src}" style="width:100%; max-height:80vh; object-fit:contain; border-radius:12px;">
      </div>
    `);
  },

  openAddProductModal(prodId = null) {
    const allProds = [...(DB.get('rawMaterialsList')||[]), ...DB.get('products')];
    const p = prodId ? allProds.find(x => x.id === prodId) : null;
    window.currentProductImage = p ? p.image : null;
    
    this.openModal(`
      <h3 style="margin-bottom:1rem;">${p ? 'Edit Product' : 'Add New Product'}</h3>
      <div class="form-group">
        <div class="image-upload-wrapper" onclick="document.getElementById('new-prod-image').click()" style="padding:1rem;">
          <img id="prod-img-preview" src="${p?.image || ''}" style="width:100px; height:100px; object-fit:cover; border-radius:8px; display:${p?.image ? 'block':'none'}; margin:0 auto;">
          <div style="font-size:0.8rem; color:var(--text-muted); margin-top:5px;">Click to upload Image</div>
          <input type="file" id="new-prod-image" accept="image/*" style="display:none;" onchange="app.previewProductImage(event)">
        </div>
      </div>
      <div class="form-group">
        <label>Product Name</label>
        <input type="text" id="new-prod-name" value="${p ? p.name : ''}">
      </div>
      <div class="form-group">
        <label>Product Type</label>
        <select id="new-prod-type" ${p ? 'disabled' : ''}>
          <option value="RAW" ${p?.type==='RAW'?'selected':''}>Raw Material</option>
          <option value="SEMI" ${p?.type==='SEMI'?'selected':''}>Semi Finished</option>
          <option value="FINISHED" ${p?.type==='FINISHED'?'selected':''}>Finished Good / For Sale</option>
        </select>
      </div>
      <button class="btn mt-1" onclick="app.submitNewProduct('${prodId || ''}')">Save Product</button>
      <button class="btn btn-secondary mt-1" onclick="app.closeModal()">Cancel</button>
    `);
  },

  previewProductImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
      const img = document.getElementById('prod-img-preview');
      img.src = reader.result;
      img.style.display = 'block';
      window.currentProductImage = reader.result;
    };
    if(event.target.files[0]) reader.readAsDataURL(event.target.files[0]);
  },

  submitNewProduct(prodId) {
    const name = document.getElementById('new-prod-name').value;
    const type = document.getElementById('new-prod-type').value;
    const image = window.currentProductImage || `https://image.pollinations.ai/prompt/${encodeURIComponent(name)}?width=200&height=200&nologo=true`;
    
    if(!name) return this.toast('Name is required', 'error');
    
    const payload = {
      product_id: prodId,
      name,
      type,
      image_url: image,
      unit: 'kg'
    };

    fetch('/admin/products/store', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        this.toast(res.message);
        this.closeModal();
        this.navigate('admin/products');
      }
    });
  },

  renderAdminStock(el) {
    this.adminActiveTab(el);
    const container = document.getElementById('content-area');
    const allStock = [
      ...this.getAggregatedStock('rawStock').map(s=>({...s, type:'RAW'})),
      ...this.getAggregatedStock('semiStock').map(s=>({...s, type:'SEMI'})),
      ...this.getAggregatedStock('finishedStock').map(s=>({...s, type:'FINISHED'}))
    ].filter(s => s.quantity !== 0);
    
    const page = window.currentPage || 1;
    const { paginated, totalPages } = this.paginate(allStock, page, 10);
    
    container.innerHTML = `
      <h2>Live Stock Inventory</h2>
      <div class="table-container mt-1">
        <table>
          <thead><tr><th>Product Name</th><th>Type</th><th>Grade</th><th>Available Qty</th><th>Actions</th></tr></thead>
          <tbody>
            ${paginated.map(s => `
              <tr>
                <td><strong>${s.name}</strong></td>
                <td><span class="badge ${s.type==='RAW'?'badge-open':(s.type==='SEMI'?'badge-pending':'badge-done')}">${s.type}</span></td>
                <td><span class="badge badge-pending">${s.grade}</span></td>
                <td style="font-weight:bold; color:var(--secondary); font-size:1.1rem;">${Number(s.quantity).toFixed(2)} kg</td>
                <td>
                  <div class="action-btns">
                    <button class="btn-icon edit" onclick="app.adjustStock('${s.productId}', '${s.stage}', '${s.grade}')" title="Adjust">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                    </button>
                  </div>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
      ${this.renderPaginationControls(page, totalPages)}
    `;
  },

  renderAdminPurchaseOrders(el) {
    if(el) this.adminActiveTab(el);
    const container = document.getElementById('content-area');
    const pos = DB.get('purchaseOrders') || [];
    pos.sort((a,b)=>new Date(b.date)-new Date(a.date));
    
    const page = window.currentPage || 1;
    const { paginated, totalPages } = this.paginate(pos, page, 10);
    const rmList = DB.get('rawMaterialsList') || [];
    const users = DB.get('users');

    container.innerHTML = `
      <h2 class="mb-1">Purchase Orders (Requests)</h2>
      <div class="table-container">
        <table>
          <thead><tr><th>Date</th><th>Requested By</th><th>Material</th><th>Qty</th><th>Note</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            ${paginated.map(p => {
              const mat = rmList.find(x=>x.id===p.materialId)?.name||'Unknown';
              const u = users.find(x=>x.id===p.userId);
              return `<tr>
                <td style="font-size:0.8rem;">${new Date(p.date).toLocaleString()}</td>
                <td><strong>${u ? u.name : 'Unknown'}</strong> <br><span style="font-size:0.7rem; color:var(--text-muted);">${u ? u.role : ''}</span></td>
                <td>${mat}</td>
                <td style="font-weight:bold; color:var(--secondary);">${p.quantity} kg</td>
                <td style="font-size:0.85rem; color:var(--text-muted); max-width:150px; text-overflow:ellipsis; overflow:hidden;">${p.note||'\u2014'}</td>
                <td><span class="badge ${p.status==='DONE'?'badge-done':'badge-pending'}">${p.status}</span></td>
                <td>
                  <div class="action-btns">
                    ${p.status === 'PENDING' ? `
                      <button class="btn btn-sm" onclick="app.approvePO('${p.id}')" style="width:auto; padding:0.3rem 0.8rem; font-size:0.75rem;">Approve</button>
                    ` : ''}
                    <button class="btn-icon delete" onclick="app.deletePO('${p.id}')" title="Delete">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                  </div>
                </td>
              </tr>`;
            }).join('') || '<tr><td colspan="7" class="text-center text-muted">No purchase orders found.</td></tr>'}
          </tbody>
        </table>
      </div>
      ${this.renderPaginationControls(page, totalPages)}
    `;
  },

  deletePO(id) {
    Swal.fire({
      title: 'Delete Order?',
      text: "Remove this purchase request?",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Delete'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(`/admin/po/${id}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(r => r.json()).then(d => {
          if (d.success) { this.toast(d.message); this.renderAdminPurchaseOrders(); }
        });
      }
    });
  },

  deleteGrade(id) {
    Swal.fire({
      title: 'Delete Grade?',
      text: "This will remove it from all products!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Delete'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(`/admin/grades/${id}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(r => r.json()).then(d => {
          if (d.success) { this.toast(d.message); this.renderAdminGrades(); }
        });
      }
    });
  },

  approvePO(poId) {
    fetch('/admin/po/approve', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ po_id: poId })
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        this.toast(res.message);
        this.navigate('admin/po');
      }
    });
  },

  renderAdminLogs(el) {
    if(el) this.adminActiveTab(el);
    const container = document.getElementById('content-area');
    
    // Filters
    const catFilter  = window.logCatFilter || '';
    const userFilter = window.logUserFilter || '';
    const dateFilter = window.logDateFilter || '';

    const allLogs = DB.get('logs') || [];
    const users   = DB.get('users') || [];
    
    let filtered = allLogs;
    if(catFilter)  filtered = filtered.filter(l => l.category === catFilter);
    if(userFilter) filtered = filtered.filter(l => l.by === userFilter);
    if(dateFilter) filtered = filtered.filter(l => l.date.split('T')[0] === dateFilter);

    const page = window.currentPage || 1;
    const { paginated, totalPages } = this.paginate(filtered, page, 20);

    container.innerHTML = `
      <div class="flex-between mb-1" style="flex-wrap: wrap; gap: 10px;">
        <h2 style="margin:0;">System Activity Logs</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <select class="btn-sm" style="background:var(--glass-bg); color:white; border:1px solid var(--glass-border); padding:5px 10px;" onchange="window.logCatFilter=this.value; window.currentPage=1; app.renderAdminLogs()">
            <option value="">All Categories</option>
            <option value="Production" ${catFilter==='Production'?'selected':''}>Production</option>
            <option value="Sales" ${catFilter==='Sales'?'selected':''}>Sales</option>
            <option value="Dispatch" ${catFilter==='Dispatch'?'selected':''}>Dispatch</option>
            <option value="Purchase" ${catFilter==='Purchase'?'selected':''}>Purchase</option>
            <option value="Inventory" ${catFilter==='Inventory'?'selected':''}>Inventory</option>
          </select>
          <select class="btn-sm" style="background:var(--glass-bg); color:white; border:1px solid var(--glass-border); padding:5px 10px;" onchange="window.logUserFilter=this.value; window.currentPage=1; app.renderAdminLogs()">
            <option value="">All Users</option>
            ${users.map(u => `<option value="${u.name}" ${userFilter===u.name?'selected':''}>${u.name} (${u.role})</option>`).join('')}
          </select>
          <input type="date" class="btn-sm" value="${dateFilter}" style="background:var(--glass-bg); color:white; border:1px solid var(--glass-border); padding:5px 10px;" onchange="window.logDateFilter=this.value; window.currentPage=1; app.renderAdminLogs()">
          <button class="btn btn-sm btn-secondary" onclick="window.logCatFilter=''; window.logUserFilter=''; window.logDateFilter=''; window.currentPage=1; app.renderAdminLogs()">Reset</button>
        </div>
      </div>
      
      <div class="table-container mt-1">
        <table>
          <thead>
            <tr>
              <th>Date & Time</th>
              <th>Category</th>
              <th>Activity Description</th>
              <th>Performed By</th>
            </tr>
          </thead>
          <tbody>
            ${paginated.map(l => `
              <tr>
                <td style="font-size:0.85rem; font-family:monospace; color:var(--text-muted);">${new Date(l.date).toLocaleString()}</td>
                <td>
                  <span class="badge ${l.category==='Production'?'badge-pending':(l.category==='Sales'?'badge-open':(l.category==='Inventory'?'badge-closed':'badge-done'))}" style="font-size:0.7rem;">
                    ${l.category}
                  </span>
                </td>
                <td style="font-size:0.9rem; max-width:300px;">
                  <div style="font-weight:600; color:var(--text-main);">${l.description}</div>
                </td>
                <td>
                  <div style="font-weight:bold;">${l.by || 'System'}</div>
                  <div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">${l.role || ''}</div>
                </td>
              </tr>
            `).join('') || '<tr><td colspan="4" class="text-center text-muted">No logs found matching your filters.</td></tr>'}
          </tbody>
        </table>
      </div>
      ${this.renderPaginationControls(page, totalPages)}
    `;
  },

  renderAdminGrades(el) {
    if(el) this.adminActiveTab(el);
    const grades = DB.get('grades') || [];
    const container = document.getElementById('content-area');
    const page = window.currentPage || 1;
    const { paginated, totalPages } = this.paginate(grades, page, 10);

    container.innerHTML = `
      <div class="flex-between mb-1" style="flex-wrap: wrap; gap: 10px;">
        <h2 style="margin:0;">Grades Master</h2>
        <button class="btn btn-sm btn-secondary" onclick="app.openGradeModal()" style="width: auto;">+ Add Grade</button>
      </div>
      <div class="table-container">
        <table>
          <thead><tr><th>Grade Name</th><th>Active</th><th>Actions</th></tr></thead>
          <tbody>
            ${paginated.map(g => `
              <tr>
                <td><strong>${g.name}</strong></td>
                <td>
                  <label class="switch">
                    <input type="checkbox" ${g.is_active?'checked':''} onchange="app.toggleGrade('${g.id}')">
                    <span class="slider"></span>
                  </label>
                </td>
                <td>
                  <div class="action-btns">
                    <button class="btn-icon edit" onclick="app.openGradeModal('${g.id}')" title="Edit">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                    </button>
                    <button class="btn-icon delete" onclick="app.deleteGrade('${g.id}')" title="Delete">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                  </div>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
      ${this.renderPaginationControls(page, totalPages)}
    `;
  },

  openGradeModal(id = null) {
    const g = id ? DB.get('grades').find(x=>x.id == id) : null;
    this.openModal(`
      <h3 style="margin-bottom:1rem;">${g ? 'Edit Grade' : 'Add Grade'}</h3>
      <div class="form-group">
        <label>Grade Name</label>
        <input type="text" id="grade-name" value="${g ? g.name : ''}" placeholder="e.g. PPF or Premium">
      </div>
      <button class="btn mt-1" onclick="app.submitGrade('${id||''}')">Save Grade</button>
    `);
  },

  submitGrade(id) {
    const name = document.getElementById('grade-name').value;
    if(!name) return this.toast('Name required', 'error');
    fetch('/admin/grades', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ grade_id: id, name })
    }).then(r => r.json()).then(d => {
      if (d.success) { this.toast(d.message); this.closeModal(); this.renderAdminGrades(); }
      else this.toast(d.message, 'error');
    });
  },

  adjustStock(productId, stage, grade) {
    const qty = prompt('Enter new absolute quantity (kg):');
    if(qty === null || isNaN(qty)) return;
    fetch('/admin/stock/adjust', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ product_id: productId, stage, grade, quantity: qty })
    }).then(r => r.json()).then(d => {
      if (d.success) { this.toast(d.message); this.renderAdminStock(); }
    });
  },
  // --- ROLE: ATTENDANCE ---
  renderAttendanceHome(container) {
    fetch('/attendance/api/daily', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(data => {
        const workers = data.workers || [];
        const present = workers.filter(w => w.status !== 'ABSENT').length;
        const total = workers.length;
        const ot = workers.reduce((acc, w) => acc + (w.overtime_hours || 0), 0);

        container.innerHTML = `
          <div class="flex-between mb-1">
            <h2 style="margin:0;">Attendance Dashboard</h2>
            <button class="btn btn-sm btn-secondary" style="width:auto;" onclick="app.openWorkerDrawer()">+ Add Worker</button>
          </div>
          
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:1.5rem;">
            <div class="card clickable-card" role="button" tabindex="0" onclick="app.navigate('action')" style="padding:1rem; text-align:center;">
              <div style="font-size:0.8rem; color:var(--text-muted);">Present Today</div>
              <div style="font-size:1.5rem; font-weight:bold; color:var(--secondary);">${present} / ${total}</div>
            </div>
            <div class="card clickable-card" role="button" tabindex="0" onclick="app.navigate('history')" style="padding:1rem; text-align:center;">
              <div style="font-size:0.8rem; color:var(--text-muted);">Overtime (Hrs)</div>
              <div style="font-size:1.5rem; font-weight:bold; color:var(--warning);">${ot.toFixed(1)}</div>
            </div>
          </div>

          <div class="card">
            <div class="card-title">Quick Actions</div>
            <button class="btn mb-1" style="background:var(--primary-light); color:var(--dark);" onclick="app.navigate('action')">📝 Daily Punch Sheet</button>
            <button class="btn mb-1" style="background:rgba(255,255,255,0.05);" onclick="app.navigate('history')">📊 Monthly Reports</button>
            <button class="btn" style="background:rgba(255,255,255,0.05);" onclick="app.openWorkerDrawer()">👥 Manage Workers</button>
          </div>
        `;
      });
  },

  renderAttendanceAction(container) {
    const date = window._attDate || new Date().toISOString().split('T')[0];
    const dateChanged = window._attLastFetchDate !== date;
    window._attDate = date;

    if (!window._attWorkers || dateChanged) {
      window._attLastFetchDate = date;
      fetch(`/attendance/api/daily?date=${date}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
          window._attWorkers = data.workers || [];
          this.renderAttendanceActionUI(container);
        });
    } else {
      this.renderAttendanceActionUI(container);
    }
  },

  renderAttendanceActionUI(container) {
    const date = window._attDate;
    const workers = window._attWorkers || [];
    const q = (window._attSearch || '').toLowerCase();

    const filtered = workers.filter(w => 
      w.name.toLowerCase().includes(q) || 
      w.department.toLowerCase().includes(q)
    );

    container.innerHTML = `
      <div class="flex-between mb-1">
        <h2 style="margin:0;">Daily Punch</h2>
        <input type="date" value="${date}" onchange="window._attDate=this.value; window._attWorkers=null; app.refreshCurrentView()" style="width:auto; padding:0.4rem; font-size:0.85rem;">
      </div>

          <div class="form-group">
            <input type="text" placeholder="Search worker or department..." value="${window._attSearch || ''}" 
              oninput="window._attSearch=this.value; app.refreshCurrentView()">
          </div>

          <div style="margin-bottom:1rem; display:flex; gap:10px;">
            <button class="btn btn-sm btn-secondary" style="flex:1;" onclick="app.markAllPresent()">Mark All Present</button>
            <button class="btn btn-sm" style="flex:1;" onclick="app.submitAttendance()">Save All</button>
          </div>

          ${filtered.map((w, idx) => {
            const hasIn = !!w.in_time;
            const hasBIn = !!w.break_in;
            const hasBOut = !!w.break_out;
            const hasOut = !!w.out_time;
            
            let statusBadge = '<span class="badge badge-danger">Absent</span>';
            let liveStatus = 'Not Arrived';
            if(w.status !== 'ABSENT') {
              statusBadge = `<span class="badge ${w.status==='PRESENT'?'badge-done':'badge-info'}">${w.status}</span>`;
              if(!hasIn) liveStatus = 'Not Arrived';
              else if(hasIn && !hasBIn && !hasOut) liveStatus = '<span style="color:var(--secondary);">⏺ Inside Factory</span>';
              else if(hasBIn && !hasBOut && !hasOut) liveStatus = '<span style="color:var(--info);">⏸ On Break</span>';
              else if(hasBOut && !hasOut) liveStatus = '<span style="color:var(--secondary);">⏺ Back / Working</span>';
              else if(hasOut) liveStatus = '<span style="color:var(--text-muted);">⏹ Finished</span>';
            }

            return `
            <div class="card" style="padding:1rem; margin-bottom:1rem; border-left:4px solid ${w.status==='PRESENT'?'var(--secondary)':(w.status==='HALF_DAY'?'var(--warning)':'var(--danger)')};">
              <div class="flex-between" style="margin-bottom:0.8rem;">
                <div>
                  <div style="font-weight:600; font-size:1.05rem;">${w.name}</div>
                  <div style="font-size:0.75rem; color:var(--text-muted);">${w.department} \u00b7 ${w.shift_type}</div>
                </div>
                <div style="text-align:right;">
                  <div style="margin-bottom:4px;">${statusBadge}</div>
                  <div style="font-size:0.7rem; font-weight:600;">${liveStatus}</div>
                </div>
              </div>
              
              <div class="flex-between mb-1" style="background:rgba(255,255,255,0.03); padding:0.5rem; border-radius:8px;">
                <div style="display:flex; gap:8px; align-items:center;">
                  <select onchange="app.updateAttStatus(${w.worker_id}, this.value)" style="width:auto; font-size:0.8rem; padding:0.3rem 0.6rem; background:var(--glass-bg); border:1px solid rgba(255,255,255,0.1);">
                    <option value="PRESENT" ${w.status==='PRESENT'?'selected':''}>Present</option>
                    <option value="HALF_DAY" ${w.status==='HALF_DAY'?'selected':''}>Half Day</option>
                    <option value="ABSENT" ${w.status==='ABSENT'?'selected':''}>Absent</option>
                  </select>
                  ${w.status !== 'ABSENT' ? `<button class="btn btn-sm" style="width:auto; padding:0.2rem 0.5rem; font-size:0.65rem; background:var(--info);" onclick="app.setAttDefaults(${w.worker_id})">⚡ Default 9-6</button>` : ''}
                </div>
                <div style="font-size:0.8rem; font-weight:bold; color:var(--primary-light);">
                  ${w.total_hours > 0 ? `Total: ${w.total_hours.toFixed(1)} hrs` : ''}
                </div>
              </div>

              ${w.status !== 'ABSENT' ? `
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
                  <div class="form-group" style="margin:0;">
                    <label style="font-size:0.65rem; color:var(--text-muted);">Punch In</label>
                    <div style="display:flex; gap:4px;">
                      <input type="time" value="${w.in_time || ''}" onchange="app.updateAttTime(${w.worker_id}, 'in_time', this.value)" style="padding:0.3rem; font-size:0.8rem;">
                      <button class="btn btn-sm ${hasIn?'btn-secondary':''}" style="padding:0; width:30px;" onclick="app.punchNow(${w.worker_id}, 'in_time')" title="Punch In">🎬</button>
                    </div>
                  </div>
                  <div class="form-group" style="margin:0;">
                    <label style="font-size:0.65rem; color:var(--text-muted);">Punch Out</label>
                    <div style="display:flex; gap:4px;">
                      <input type="time" value="${w.out_time || ''}" onchange="app.updateAttTime(${w.worker_id}, 'out_time', this.value)" style="padding:0.3rem; font-size:0.8rem;">
                      <button class="btn btn-sm ${hasOut?'btn-secondary':''}" style="padding:0; width:30px;" onclick="app.punchNow(${w.worker_id}, 'out_time')" title="Punch Out">🏁</button>
                    </div>
                  </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                  <div class="form-group" style="margin:0;">
                    <label style="font-size:0.65rem; color:var(--text-muted);">On Break</label>
                    <div style="display:flex; gap:4px;">
                      <input type="time" value="${w.break_in || ''}" onchange="app.updateAttTime(${w.worker_id}, 'break_in', this.value)" style="padding:0.3rem; font-size:0.8rem;">
                      <button class="btn btn-sm ${hasBIn?'btn-secondary':''}" style="padding:0; width:30px;" onclick="app.punchNow(${w.worker_id}, 'break_in')" title="Start Break">☕</button>
                    </div>
                  </div>
                  <div class="form-group" style="margin:0;">
                    <label style="font-size:0.65rem; color:var(--text-muted);">Back to Work</label>
                    <div style="display:flex; gap:4px;">
                      <input type="time" value="${w.break_out || ''}" onchange="app.updateAttTime(${w.worker_id}, 'break_out', this.value)" style="padding:0.3rem; font-size:0.8rem;">
                      <button class="btn btn-sm ${hasBOut?'btn-secondary':''}" style="padding:0; width:30px;" onclick="app.punchNow(${w.worker_id}, 'break_out')" title="Back to Work">🛠️</button>
                    </div>
                  </div>
                </div>
              ` : ''}
            </div>
          `}).join('') || '<p class="text-center text-muted">No workers found.</p>'}
          
          <button class="btn mt-1" onclick="app.submitAttendance()" style="margin-bottom:2rem;">Save All Changes</button>
        `;
  },

  updateAttStatus(workerId, status) {
    const w = window._attWorkers.find(x => x.worker_id == workerId);
    if (!w) return;
    w.status = status;
    if (status === 'PRESENT' && !w.in_time) {
      this.setAttDefaults(workerId, false);
    }
    app.refreshCurrentView();
  },

  setAttDefaults(workerId, refresh = true) {
    const w = window._attWorkers.find(x => x.worker_id == workerId);
    if (!w) return;
    w.status = 'PRESENT';
    w.in_time = '09:00';
    w.out_time = '18:00';
    w.break_in = '13:00';
    w.break_out = '14:00';
    if (refresh) app.refreshCurrentView();
  },

  updateAttTime(workerId, field, val) {
    const w = window._attWorkers.find(x => x.worker_id == workerId);
    if (w) w[field] = val;
  },

  punchNow(workerId, field) {
    const w = window._attWorkers.find(x => x.worker_id == workerId);
    if (!w) return;
    const now = new Date();
    const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    w[field] = time;
    app.refreshCurrentView();
  },

  markAllPresent() {
    window._attWorkers.forEach(w => {
      this.setAttDefaults(w.worker_id, false);
    });
    app.refreshCurrentView();
  },

  submitAttendance() {
    Swal.fire({ title: 'Saving...', didOpen: () => Swal.showLoading() });
    fetch('/attendance/daily', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({
        date: window._attDate,
        attendances: window._attWorkers.map(w => ({
          worker_id: w.worker_id,
          status: w.status,
          in_time: w.in_time,
          out_time: w.out_time,
          break_in: w.break_in,
          break_out: w.break_out
        }))
      })
    }).then(r => r.json()).then(d => {
      if (d.success) {
        Swal.fire('Saved!', d.message, 'success');
        app.refreshCurrentView();
      } else Swal.fire('Error', d.message, 'error');
    });
  },

  renderAttendanceHistory(container) {
    const month = window._attMonth || new Date().toISOString().slice(0, 7);
    window._attMonth = month;

    fetch(`/attendance/reports?month=${month}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.text())
      .then(html => {
        // Extract the table from the blade-rendered HTML for simplicity
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const content = doc.querySelector('.card').innerHTML;

        container.innerHTML = `
          <div class="flex-between mb-1">
            <h2 style="margin:0;">Monthly Reports</h2>
            <input type="month" value="${month}" onchange="window._attMonth=this.value; app.refreshCurrentView()" style="width:auto; padding:0.4rem; font-size:0.85rem;">
          </div>
          <div class="card">
            ${content}
          </div>
          <button class="btn btn-secondary mt-1" onclick="window.print()">Print Report</button>
        `;
      });
  },

  openWorkerDrawer(workerId = null) {
    Promise.all([
      fetch('/attendance/api/departments', { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()),
      workerId ? fetch(`/attendance/api/workers`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()) : Promise.resolve({ workers: [] })
    ]).then(([deptData, workerData]) => {
      const depts = deptData.departments || [];
      const worker = workerId ? workerData.workers.find(w => w.id == workerId) : null;

      this.openDrawer(`
        <h3 style="margin-bottom:1rem;">${worker ? 'Edit Worker' : 'Add New Worker'}</h3>
        <div class="form-group">
          <label>Worker Name *</label>
          <input type="text" id="w-name" value="${worker?.name || ''}" placeholder="Enter full name">
        </div>
        <div class="form-group">
          <label>Department *</label>
          <select id="w-dept">
            <option value="">-- Select --</option>
            ${depts.map(d => `<option value="${d.id}" ${worker?.department_id == d.id ? 'selected' : ''}>${d.name}</option>`).join('')}
          </select>
        </div>
        <div class="form-group">
          <label>Daily Salary (₹) *</label>
          <input type="number" id="w-salary" value="${worker?.daily_salary || 500}" placeholder="e.g. 500">
        </div>
        <div class="form-group">
          <label>Shift Type</label>
          <select id="w-shift">
            <option value="DAY" ${worker?.shift_type === 'DAY' ? 'selected' : ''}>Day Shift</option>
            <option value="NIGHT" ${worker?.shift_type === 'NIGHT' ? 'selected' : ''}>Night Shift</option>
            <option value="CUSTOM" ${worker?.shift_type === 'CUSTOM' ? 'selected' : ''}>Custom</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="w-status">
            <option value="ACTIVE" ${worker?.status === 'ACTIVE' ? 'selected' : ''}>Active</option>
            <option value="INACTIVE" ${worker?.status === 'INACTIVE' ? 'selected' : ''}>Inactive</option>
          </select>
        </div>
        <button class="btn mt-1" onclick="app.saveWorker(${workerId})">Save Worker Details</button>
      `);
    });
  },

  saveWorker(workerId) {
    const data = {
      worker_id: workerId,
      name: document.getElementById('w-name').value,
      department_id: document.getElementById('w-dept').value,
      daily_salary: document.getElementById('w-salary').value,
      shift_type: document.getElementById('w-shift').value,
      status: document.getElementById('w-status').value
    };

    if(!data.name || !data.department_id) return this.toast('Name and Department are required', 'error');

    fetch('/attendance/workers', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
      if(d.success) {
        this.toast(d.message);
        this.closeDrawer();
        this.refreshCurrentView();
      } else {
        this.toast(d.message, 'error');
      }
    });
  },

  renderAttendanceTeam(container) {
    fetch('/attendance/team', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(data => {
        const workers = data.workers || [];
        const depts = data.departments || [];
        
        container.innerHTML = `
          <div class="flex-between mb-1">
            <h2 style="margin:0;">Team Management</h2>
          </div>

          <div style="display:flex; gap:10px; margin-bottom:1.5rem;">
            <button class="btn btn-sm" style="flex:1; background:var(--primary-light); color:var(--dark);" onclick="app.openWorkerDrawer()">+ Add Worker</button>
            <button class="btn btn-sm btn-secondary" style="flex:1;" onclick="app.openDeptDrawer()">+ Add Dept</button>
          </div>

          <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-title">🏢 Departments (${depts.length})</div>
            <div style="max-height:200px; overflow-y:auto;">
              ${depts.map(d => `
                <div class="flex-between" style="padding:0.6rem 0; border-bottom:1px solid rgba(255,255,255,0.05);">
                  <div>
                    <div style="font-weight:600;">${d.name}</div>
                    <div style="font-size:0.75rem; color:var(--text-muted);">${d.workers_count || 0} Workers</div>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>

          <div class="card">
            <div class="card-title">👥 Active Workers (${workers.length})</div>
            <div style="max-height:400px; overflow-y:auto;">
              ${workers.map(w => `
                <div class="flex-between" style="padding:0.8rem 0; border-bottom:1px solid rgba(255,255,255,0.05);">
                  <div>
                    <div style="font-weight:600;">${w.name}</div>
                    <div style="font-size:0.75rem; color:var(--text-muted);">${w.department?.name || 'No Dept'} \u00b7 ${w.shift_type}</div>
                  </div>
                  <button class="btn btn-sm btn-secondary" style="width:auto; padding:0.2rem 0.6rem;" onclick="app.openWorkerDrawer(${w.id})">Edit</button>
                </div>
              `).join('')}
            </div>
          </div>
        `;
      });
  },

  openDeptDrawer() {
    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Add New Department</h3>
      <div class="form-group">
        <label>Department Name *</label>
        <input type="text" id="new-dept-name" placeholder="e.g. Production, Packing">
      </div>
      <button class="btn mt-1" onclick="app.saveDepartment()">Save Department</button>
    `);
  },

  saveDepartment() {
    const name = document.getElementById('new-dept-name').value;
    if (!name) return this.toast('Name is required', 'error');

    fetch('/attendance/departments', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ name })
    }).then(r=>r.json()).then(d=>{
      if(d.success) { this.toast(d.message); this.closeDrawer(); this.refreshCurrentView(); }
      else this.toast(d.message, 'error');
    });
  },

  renderRecentPOs() {
    const pos = (DB.get('purchaseOrders') || []).sort((a,b) => new Date(b.date) - new Date(a.date)).slice(0, 3);
    if (pos.length === 0) return '';
    const rmList = DB.get('rawMaterialsList') || [];
    const prodList = DB.get('products') || [];
    
    return `
      <div class="card mt-1" style="background:rgba(255,255,255,0.03);">
        <div class="card-title" style="font-size:0.9rem;">Recent Purchase Requests</div>
        ${pos.map(p => {
          const mat = rmList.find(x => x.id == p.materialId)?.name || prodList.find(x => x.id == p.materialId)?.name || 'Material';
          return `
            <div class="flex-between" style="padding:0.5rem 0; border-bottom:1px solid rgba(255,255,255,0.05);">
              <div>
                <div style="font-size:0.85rem; font-weight:600;">${mat}</div>
                <div style="font-size:0.7rem; color:var(--text-muted);">${new Date(p.date).toLocaleDateString()} \u00b7 ${p.quantity} kg</div>
              </div>
              <span class="badge ${p.status==='DONE'?'badge-done':'badge-pending'}" style="font-size:0.65rem; padding:0.2rem 0.5rem;">${p.status}</span>
            </div>
          `;
        }).join('')}
      </div>
    `;
  },

  registerServiceWorker() {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
      return navigator.serviceWorker.register('/sw.js')
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
      
      // Try to send to server if already logged in
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
  // Let Blade handle initial data setup then boot JS logic
  setTimeout(() => app.init(), 10);
};
