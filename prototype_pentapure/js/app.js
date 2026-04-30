const app = {
  currentUser: null,
  currentView: 'home',
  currentLang: 'en',

  translations: {
    'en': {
      'Home': 'Home', 'Action': 'Action', 'History': 'History', 'Profile': 'Profile',
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
      'Home': 'होम', 'Action': 'कार्य', 'History': 'इतिहास', 'Profile': 'प्रोफ़ाइल',
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
      'Home': 'હોમ', 'Action': 'ક્રિયા', 'History': 'ઇતિહાસ', 'Profile': 'પ્રોફાઇલ',
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
      els[3].innerText = this.t('Profile');
    }
  },


  init() {
    // Load setting user pref
    const lang = localStorage.getItem('pentapure_lang');
    if(lang) this.currentLang = lang;

    setTimeout(() => {
      const splash = document.getElementById('splash-screen');
      if (splash) {
        splash.style.opacity = '0';
        setTimeout(() => splash.remove(), 500);
      }
      this.renderLogin();
    }, 2500);
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
    this.currentUser = DB.get('users').find(u => u.id === userId);
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
    this.currentUser = null;
    document.body.classList.remove('admin-mode');
    document.getElementById('admin-sidebar').classList.add('hidden');
    document.getElementById('admin-hamburger').classList.add('hidden');
    this.renderLogin();
    this.toast('Logged out successfully', 'info');
  },

  navigate(view) {
    this.currentView = view;
    window.currentPage = 1;
    // Update bottom nav UI
    document.querySelectorAll('.bottom-nav .nav-item').forEach(el => el.classList.remove('active'));
    const targetEl = Array.from(document.querySelectorAll('.bottom-nav .nav-item')).find(el => el.innerText.toLowerCase() === view);
    if(targetEl) targetEl.classList.add('active');

    const content = document.getElementById('content-area');
    content.innerHTML = '';

    if (view === 'home') this.renderHome(content);
    else if (view === 'add') this.renderAction(content);
    else if (view === 'history') this.renderHistory(content);
    else if (view === 'profile') this.renderProfile(content);
    else if (view === 'po') this.renderPurchaseOrder(content);
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
      else if (this.currentView === 'history') this.renderHistory(content);
    }
  },

  // --- DATA HELPERS ---
  getAggregatedStock(stockType) {
    const items = DB.get(stockType);
    const products = DB.get('products');
    const rmList = DB.get('rawMaterialsList') || [];
    const agg = {};
    const gradeMap = {};

    items.forEach(i => {
      // Aggregate by product ID AND grade
      const key = `${i.productId}_${i.grade || 'NONE'}`;
      agg[key] = (agg[key] || 0) + Number(i.quantity);
      gradeMap[key] = i.grade || 'NONE';
    });

    return Object.keys(agg).map(key => {
      const [id, _] = key.split('_');
      let p;
      if (stockType === 'rawStock') {
         p = rmList.find(prod => prod.id === id);
      }
      if (!p) p = products.find(prod => prod.id === id);
      
      return { id: p.id, name: p.name, qty: agg[key], unit: p.unit, grade: gradeMap[key] !== 'NONE' ? gradeMap[key] : (p.grade || 'NONE') };
    });
  },

  renderPurchaseOrder(container) {
    const role = this.currentUser.role;
    const isProdRole = role === 'RAW' || role === 'SEMI' || role === 'FINISHED';
    
    if (isProdRole) {
      const rmList = DB.get('rawMaterialsList') || [];
      const myPOs = (DB.get('purchaseOrders') || []).filter(p => p.userId === this.currentUser.id).sort((a,b)=>new Date(b.date)-new Date(a.date));
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
            <thead><tr><th>Date</th><th>Material</th><th>Qty</th><th>Status</th></tr></thead>
            <tbody>
              ${myPOs.map(p => {
                const mat = rmList.find(x=>x.id===p.materialId)?.name||'Unknown';
                return `<tr>
                  <td style="font-size:0.8rem;">${new Date(p.date).toLocaleDateString()}</td>
                  <td>${mat}</td>
                  <td>${p.qty} kg</td>
                  <td><span class="badge ${p.status==='DONE'?'badge-done':'badge-pending'}">${p.status}</span></td>
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
    const qty = Number(document.getElementById('po-qty').value);
    const note = document.getElementById('po-note').value;
    
    if(!matId) return this.toast('Select a material', 'error');
    if(!qty || qty<=0) return this.toast('Enter valid quantity', 'error');
    
    const pos = DB.get('purchaseOrders') || [];
    pos.push({
      id: DB.generateId(),
      userId: this.currentUser.id,
      materialId: matId,
      qty,
      note,
      status: 'PENDING',
      date: new Date().toISOString()
    });
    DB.set('purchaseOrders', pos);
    
    this.toast('Request sent to Admin!');
    this.navigate('po');
  },

  // --- PROFILE VIEW ---
  renderProfile(container) {
    container.innerHTML = `
      <div class="card" style="text-align: center; padding: 2rem 1rem;">
        <div style="width:80px; height:80px; background:var(--primary); border-radius:50%; margin:0 auto 1rem; display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:bold;">
          ${this.currentUser.name.charAt(0)}
        </div>
        <h2 style="margin-bottom: 0.5rem;">${this.currentUser.name}</h2>
        <span class="badge" style="background:var(--primary-light); color:var(--dark); margin-bottom: 2rem;">${this.currentUser.role}</span>
        
        <div class="form-group" style="text-align:left; margin-bottom: 2rem;">
          <label>Language Preference</label>
          <select onchange="app.setLanguage(this.value)">
            <option value="en" ${this.currentLang==='en'?'selected':''}>English</option>
            <option value="hi" ${this.currentLang==='hi'?'selected':''}>हिंदी (Hindi)</option>
            <option value="gu" ${this.currentLang==='gu'?'selected':''}>ગુજરાતી (Gujarati)</option>
          </select>
        </div>

        <button class="btn btn-danger" onclick="app.logout()">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          Logout Securely
        </button>
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
    `;

    if (tab === 'stock') {
      const stock = this.getAggregatedStock('rawStock').filter(s => s.qty > 0);
      html += stock.map(s => `
        <div class="card" style="padding: 1rem;">
          <div class="flex-between">
            <div>
              <div style="font-weight:600; font-size:1.1rem;">${s.name}</div>
              <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Grade: <span class="badge badge-info">${s.grade}</span></div>
            </div>
            <div style="text-align:right;">
              <div style="font-size:1.4rem; font-weight:bold; color:var(--primary-light);">${s.qty.toLocaleString()} <span style="font-size:0.9rem; color:var(--text-muted);">${s.unit}</span></div>
            </div>
          </div>
        </div>
      `).join('') || '<div class="card"><p class="text-center text-muted">No raw stock available.</p></div>';
    } else {
      html += this.renderDateFilterControls("app.refreshCurrentView");
      let filtered = this.filterByDateRange(rawStock, window.currentDateRange, window.customStart, window.customEnd);
      
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
      
      html += `<div class="table-container"><table><thead><tr><th>Date</th><th>Product</th><th>Qty</th></tr></thead><tbody>`;
      html += paginated.map(s => {
        const p = rmList.find(x=>x.id===s.productId) || {};
        const qColor = s.quantity > 0 ? 'var(--secondary)' : 'var(--danger)';
        const sign = s.quantity > 0 ? '+' : '';
        return `<tr>
          <td style="font-size:0.8rem;">${new Date(s.date).toLocaleString()}</td>
          <td>${p.name||s.productId}</td>
          <td style="font-weight:bold; color:${qColor}">${sign}${s.quantity} ${p.unit||'kg'}</td>
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
        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:8px; margin-bottom:1rem;">
          ${displayList.map(rm => `
            <div class="rm-card" onclick="app.selectRawMaterial('${rm.id}', this)" 
              style="border:2px solid transparent; border-radius:10px; overflow:hidden; cursor:pointer; background:rgba(255,255,255,0.05); text-align:center; padding-bottom:4px; transition:0.2s;">
              <img src="${rm.image}" style="width:100%; height:60px; object-fit:cover;" onerror="this.src='https://via.placeholder.com/100x60?text=IMG'">
              <div style="font-size:0.7rem; font-weight:600; padding:2px 3px; line-height:1.2;">${rm.name}</div>
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
    const rm = (DB.get('rawMaterialsList') || []).find(x => x.id === id);
    document.getElementById('raw-selected-name').innerText = rm ? `Selected: ${rm.name}` : '';
  },

  submitRawStock() {
    const prodId = document.getElementById('raw-prod').value;
    const qty = Number(document.getElementById('raw-qty').value);
    
    if(!prodId) return this.toast('Please select a material from the grid', 'error');
    if(!qty || qty <= 0) return this.toast('Enter a valid quantity', 'error');

    const stock = DB.get('rawStock');
    stock.push({ id: DB.generateId(), productId: prodId, grade: 'NONE', quantity: qty, date: new Date().toISOString() });
    DB.set('rawStock', stock);
    this.toast('Raw material added successfully!');
    this.navigate('home');
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
      <div class="tabs">
        <div class="tab-btn ${tab==='stock'?'active':''}" onclick="window.prodHomeTab='stock'; window.currentPage=1; app.refreshCurrentView()">${this.t('Stock')}</div>
        <div class="tab-btn ${tab==='inward'?'active':''}" onclick="window.prodHomeTab='inward'; window.currentPage=1; app.refreshCurrentView()">${this.t('Inward')}</div>
        <div class="tab-btn ${tab==='outward'?'active':''}" onclick="window.prodHomeTab='outward'; window.currentPage=1; app.refreshCurrentView()">${this.t('Outward')}</div>
      </div>
    `;

    if (tab === 'stock') {
      const stock = this.getAggregatedStock(stockKey).filter(s => s.qty > 0);
      html += stock.map(s => `
        <div class="list-item">
          <div class="list-item-content">
            <div class="list-item-title">${s.name}</div>
            <div class="list-item-meta">Grade: ${s.grade}</div>
          </div>
          <div class="list-item-right">
            <div style="font-weight:bold; font-size:1.1rem; color:var(--text-main);">${s.qty.toLocaleString()}</div>
            <div style="font-size:0.75rem; color:var(--text-muted);">${s.unit}</div>
          </div>
        </div>
      `).join('') || '<p class="text-center">No stock available.</p>';
    } else {
      // Inward (production of this type) and Outward (usage of this type or dispatch)
      html += this.renderDateFilterControls("app.refreshCurrentView");
      const q = (window.prodSearchQuery||'').toLowerCase();
      html += `<div class="form-group"><input type="text" placeholder="${this.t('Search product...')}" value="${q}" oninput="window.prodSearchQuery=this.value; app.refreshCurrentView()"></div>`;
      
      let filtered = DB.get(stockKey);
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
      
      html += `<div class="table-container"><table><thead><tr><th>Date</th><th>Product</th><th>Qty</th></tr></thead><tbody>`;
      html += paginated.map(s => {
        const p = allProds.find(x=>x.id===s.productId) || {};
        const qColor = s.quantity > 0 ? 'var(--secondary)' : 'var(--danger)';
        const sign = s.quantity > 0 ? '+' : '';
        return `<tr>
          <td style="font-size:0.8rem;">${new Date(s.date).toLocaleString()}</td>
          <td>${p.name||s.productId} <br><span style="font-size:0.7rem; color:var(--text-muted);">${s.grade}</span></td>
          <td style="font-weight:bold; color:${qColor}">${sign}${s.quantity} ${p.unit||'kg'}</td>
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
    
    const availableInputStock = this.getAggregatedStock(inputStockKey).filter(s => s.qty > 0);
    window.currentAvailableInputStock = availableInputStock;
    
    if (outputType === 'FINISHED') {
      container.innerHTML = `
        <div class="card">
          <div class="card-title">Create Finished Goods</div>
          
          <div class="form-group">
            <label>Select Semi-Finished Material to Process</label>
            <select id="finished-input-id" onchange="app.validateFinishedRowStock()">
              <option value="" disabled selected>-- Select Material --</option>
              ${availableInputStock.map(s => `<option value="${s.id}|${s.grade}" data-max="${s.qty}">${s.name} (Grade: ${s.grade})</option>`).join('')}
            </select>
            <div id="finished-stock-hint" style="font-size:0.7rem; color:var(--text-muted); margin-top:4px; min-height:12px;"></div>
          </div>

          <div class="form-group mt-1">
            <label>Consumed Quantity (kg)</label>
            <input type="number" id="finished-in-qty" placeholder="Quantity consumed">
          </div>
          
          <div class="form-group mt-1">
            <label>Expected Output Quantity (boxes/units)</label>
            <input type="number" id="finished-out-qty" placeholder="Quantity produced">
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
              <option value="" disabled selected>-- Select Grade --</option>
              ${allGrades.map(g => `<option value="${g}">${g}</option>`).join('')}
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

  submitFinishedProduction() {
    const val = document.getElementById('finished-input-id').value;
    const inQty = Number(document.getElementById('finished-in-qty').value);
    const outQty = Number(document.getElementById('finished-out-qty').value);
    
    if (!val) return this.toast('Select a semi-finished material', 'error');
    if (!inQty || inQty <= 0) return this.toast('Enter valid consumed quantity', 'error');
    if (!outQty || outQty <= 0) return this.toast('Enter valid output quantity', 'error');

    const [id, grade] = val.split('|');
    const selectEl = document.getElementById('finished-input-id');
    const option = selectEl.options[selectEl.selectedIndex];
    const available = Number(option.dataset.max) || 0;

    if (inQty > available) return this.toast(`Not enough stock. Max: ${available}`, 'error');

    const semiStock = DB.get('semiStock');
    semiStock.push({ id: DB.generateId(), productId: id, grade: grade, quantity: -inQty, date: new Date().toISOString() });
    DB.set('semiStock', semiStock);

    const finStock = DB.get('finishedStock');
    finStock.push({ id: DB.generateId(), productId: id, quantity: outQty, grade: grade, date: new Date().toISOString() });
    DB.set('finishedStock', finStock);

    const logs = DB.get('productionLogs');
    logs.push({
      id: DB.generateId(),
      date: new Date().toISOString(),
      userId: this.currentUser.id,
      type: 'FINISHED',
      outputProductId: id,
      outputGrade: grade,
      outputQty: outQty,
      consumedInputs: [{ productId: id, grade: grade, quantity: inQty }]
    });
    DB.set('productionLogs', logs);

    this.toast('Finished Production logged successfully!');
    this.navigate('home');
  },

  onTargetProductSelected() {
    document.getElementById('grade-selection-group').classList.remove('hidden');
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
    div.style.display = 'grid';
    div.style.gridTemplateColumns = '1fr 100px 40px';
    div.style.gap = '8px';
    div.style.alignItems = 'start';
    div.style.background = 'rgba(255,255,255,0.05)';
    div.style.padding = '12px';
    div.style.borderRadius = '12px';
    
    div.innerHTML = `
      <div class="form-group" style="margin-bottom:0;">
        <label style="font-size:0.75rem; margin-bottom:4px;">Material</label>
        <select class="prod-in-id" onchange="app.validateRowStock(this)" style="padding:0.7rem;">
          <option value="" disabled selected>Select Material</option>
          ${stockItems.map(s => `<option value="${s.id}|${s.grade}" data-max="${s.qty}">${s.name} (Grade: ${s.grade})</option>`).join('')}
        </select>
        <div class="stock-hint" style="font-size:0.7rem; color:var(--text-muted); margin-top:4px; min-height:12px;"></div>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label style="font-size:0.75rem; margin-bottom:4px;">Qty (kg)</label>
        <input type="number" class="prod-in-qty" placeholder="Qty" style="padding:0.7rem;">
      </div>
      <div style="padding-top: 24px;">
        <button class="btn btn-danger" style="padding: 0.7rem; height: 100%; border-radius: 8px;" onclick="this.parentElement.parentElement.remove()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>
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
    
    const outProdName = DB.get('products').find(x=>x.id===outProdId)?.name;
    
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
    if(!data) return;
    
    // Deduct Inputs
    const inStockArray = DB.get(data.inputStockKey);
    data.inputs.forEach(inp => {
      inStockArray.push({ id: DB.generateId(), productId: inp.productId, grade: inp.grade, quantity: -inp.quantity, date: new Date().toISOString() });
    });
    DB.set(data.inputStockKey, inStockArray);

    // Add Output
    const targetStockKey = data.outputType === 'SEMI' ? 'semiStock' : 'finishedStock';
    const targetStockArray = DB.get(targetStockKey);
    targetStockArray.push({ id: DB.generateId(), productId: data.outProdId, quantity: data.outQty, grade: data.outGrade, date: new Date().toISOString() });
    DB.set(targetStockKey, targetStockArray);

    // Log Production
    const logs = DB.get('productionLogs');
    logs.push({
      id: DB.generateId(),
      date: new Date().toISOString(),
      userId: this.currentUser.id,
      type: data.outputType,
      outputProductId: data.outProdId,
      outputGrade: data.outGrade,
      outputQty: data.outQty,
      consumedInputs: data.inputs
    });
    DB.set('productionLogs', logs);

    this.toast(`${data.outputType} Production logged successfully!`);
    window.tempProductionData = null;
    this.closeDrawer();
    this.navigate('home');
  },

  // --- ROLE: SALES ---
  renderSalesHome(container) {
    const orders = DB.get('orders');
    const companies = DB.get('companies');
    const transports = DB.get('transportCompanies');
    
    const totalOrders = orders.length;
    const openOrders = orders.filter(o => o.status === 'OPEN').length;
    const totalValue = orders.reduce((s,o) => s + (o.total || 0), 0);
    const pendingDispatch = orders.filter(o => o.dispatchStatus === 'PENDING').length;

    container.innerHTML = `
      <h2 class="mb-1">Sales Dashboard</h2>
      <div class="dashboard-grid">
        <div class="stat-card">
          <div style="color:var(--primary-light)">Total Orders</div>
          <div class="stat-value">${totalOrders}</div>
        </div>
        <div class="stat-card">
          <div style="color:var(--warning)">Open Orders</div>
          <div class="stat-value">${openOrders}</div>
        </div>
        <div class="stat-card">
          <div style="color:var(--info)">Pending Dispatch</div>
          <div class="stat-value">${pendingDispatch}</div>
        </div>
        <div class="stat-card" style="grid-column: 1 / -1; background:var(--dark-panel);">
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
      window.currentFinProds = DB.get('products').filter(p => p.type !== 'RAW');
      
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
          
          <label style="display:block; margin-top:1.5rem; font-size:0.85rem; color:var(--text-muted); margin-bottom:0.4rem;">Products</label>
          <div id="order-products"></div>
          <button class="btn btn-sm btn-secondary mb-1" onclick="app.addOrderProductRow()" style="padding:0.6rem;">+ Add Product</button>
          
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
      this.addOrderProductRow();
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
    const comp = DB.get('companies').find(c => c.id === id);
    const div = document.getElementById('company-details');
    if(comp) {
      div.style.display = 'block';
      div.innerHTML = `<strong>Address:</strong> ${comp.address||'N/A'}<br><strong>Contact:</strong> ${comp.contact||'N/A'} <br><strong>GST:</strong> ${comp.gst||'N/A'}`;
    } else {
      div.style.display = 'none';
      div.innerHTML = '';
    }
  },

  onSalesTransportSelect(id) {
    const trans = DB.get('transportCompanies').find(t => t.id === id);
    const div = document.getElementById('transport-details');
    if(trans) {
      div.style.display = 'block';
      div.innerHTML = `<strong>Contact:</strong> ${trans.contact||'N/A'} <br><strong>Vehicles:</strong> ${trans.vehicles||'N/A'} <br><strong>GST:</strong> ${trans.gst||'N/A'}`;
    } else {
      div.style.display = 'none';
      div.innerHTML = '';
    }
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
          ${finProds.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
        </select>
      </div>
      <div class="form-group" style="flex:1 1 30%;">
        <select class="o-prod-grade" style="width:100%;">
          <option value="" disabled selected>Grade</option>
          ${allGrades.map(g => `<option value="${g}">${g}</option>`).join('')}
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
    const name = document.getElementById('comp-name').value;
    const gst = document.getElementById('comp-gst').value;
    const address = document.getElementById('comp-address').value;
    const contact = document.getElementById('comp-contact').value;
    if(!name) return this.toast('Name required', 'error');
    const comps = DB.get('companies');
    comps.push({ id: 'c'+Date.now(), name, gst, address, contact, date: new Date().toISOString() });
    DB.set('companies', comps);
    this.toast('Company Added!');
    this.switchSalesTab('order', document.querySelector('.tab-btn'));
  },

  submitTransport() {
    const name = document.getElementById('trans-name').value;
    const gst = document.getElementById('trans-gst').value;
    const contact = document.getElementById('trans-contact').value;
    const vehicles = document.getElementById('trans-vehicles').value;
    if(!name) return this.toast('Name required', 'error');
    const trans = DB.get('transportCompanies');
    trans.push({ id: 't'+Date.now(), name, gst, contact, vehicles, date: new Date().toISOString() });
    DB.set('transportCompanies', trans);
    this.toast('Transport Added!');
    this.switchSalesTab('order', document.querySelector('.tab-btn'));
  },

  submitOrder() {
    const companyId = document.getElementById('order-company').value;
    const transportId = document.getElementById('order-transport').value;
    const notes = document.getElementById('order-notes').value;

    if(!companyId || !transportId) return this.toast('Select Company and Transport', 'error');

    const products = [];
    document.querySelectorAll('#order-products .dynamic-row').forEach(row => {
      const id = row.querySelector('.o-prod-id').value;
      const grade = row.querySelector('.o-prod-grade').value;
      const qty = Number(row.querySelector('.o-prod-qty').value);
      const price = Number(row.querySelector('.o-prod-price').value);
      if(id && grade && qty > 0 && price > 0) products.push({ productId: id, grade, qty, price });
    });

    if(products.length === 0) return this.toast('Add valid products and grades', 'error');

    const total = products.reduce((sum, p) => sum + (p.qty * p.price), 0);

    const orders = DB.get('orders');
    orders.push({
      id: DB.generateId(),
      companyId,
      transportId,
      products,
      notes,
      total,
      status: 'OPEN',
      dispatchStatus: 'PENDING',
      date: new Date().toISOString()
    });
    DB.set('orders', orders);

    this.toast('Order generated successfully!');
    this.navigate('home');
  },

  // --- ROLE: DISPATCH ---
  renderDispatchHome(container) {
    const tab = window.dispatchTab || 'PENDING';
    const allOrders = DB.get('orders');
    
    // Sort all by date descending initially to get newest
    allOrders.sort((a,b)=>new Date(b.date)-new Date(a.date));
    
    const filtered = allOrders.filter(o => tab === 'PENDING' ? o.dispatchStatus === 'PENDING' : o.dispatchStatus === 'DONE');
    
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
      html += paginated.map(o => `
          <div class="card">
            <div class="flex-between mb-1">
              <span style="font-weight:bold;">Order #${o.id.toUpperCase()}</span>
              ${tab === 'PENDING' ? `<button class="btn btn-sm" onclick="app.navigate('add'); setTimeout(()=>document.getElementById('dispatch-order').value='${o.id}', 100);">Dispatch</button>` : `<span class="badge badge-done">Done</span>`}
            </div>
            <div style="font-size:0.85rem; color:var(--text-muted);">
              To: ${DB.get('companies').find(c=>c.id===o.companyId)?.name} <br>
              Via: ${DB.get('transportCompanies').find(t=>t.id===o.transportId)?.name}
            </div>
          </div>
        `).join('');
    }
      
    html += this.renderPaginationControls(page, totalPages);
    container.innerHTML = html;
  },

  renderDispatchAdd(container) {
    const orders = DB.get('orders').filter(o => o.dispatchStatus === 'PENDING');
    container.innerHTML = `
      <div class="card">
        <div class="card-title">Dispatch Order</div>
        <div class="form-group">
          <label>Select Pending Order</label>
          <select id="dispatch-order">
            <option value="" disabled selected>-- Select Order --</option>
            ${orders.map(o => `<option value="${o.id}">#${o.id.toUpperCase()} - ${DB.get('companies').find(c=>c.id===o.companyId)?.name}</option>`).join('')}
          </select>
        </div>
        
        <div class="form-group mt-1">
          <label>Upload Lorry Receipt (LR) Copy</label>
          <div class="image-upload-wrapper" onclick="document.getElementById('dispatch-lr').click()">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-muted); margin-bottom:10px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            <div style="font-size:0.9rem; color:var(--text-muted);">Click to upload LR Image</div>
            <input type="file" id="dispatch-lr" accept="image/*" style="display:none;" onchange="app.previewLR(event)">
            <img id="lr-preview" class="image-preview">
          </div>
        </div>
        
        <button class="btn mt-2" onclick="app.submitDispatch()">Mark as Dispatched</button>
      </div>
    `;
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
    if(!orderId) return this.toast('Select an order', 'error');
    if(!window.currentLRImage) return this.toast('Please upload LR copy', 'error');

    const orders = DB.get('orders');
    const order = orders.find(o => o.id === orderId);
    
    // Deduct Finished Stock
    const finStockAgg = this.getAggregatedStock('finishedStock');
    for (let p of order.products) {
      const avail = finStockAgg.find(s => s.id === p.productId && s.grade === p.grade)?.qty || 0;
      if (p.qty > avail) {
        const pName = DB.get('products').find(x=>x.id===p.productId).name;
        return this.toast(`Cannot dispatch. Insufficient Finished Stock for ${pName} (${p.grade}). Need: ${p.qty}, Have: ${avail}`, 'error');
      }
    }

    const finStockArray = DB.get('finishedStock');
    for (let p of order.products) {
      finStockArray.push({ id: DB.generateId(), productId: p.productId, grade: p.grade, quantity: -p.qty, date: new Date().toISOString() });
    }
    DB.set('finishedStock', finStockArray);

    // Update Order
    order.status = 'CLOSED';
    order.dispatchStatus = 'DONE';
    DB.set('orders', orders);

    // Log Dispatch
    const logs = DB.get('dispatchLogs');
    logs.push({
      id: DB.generateId(),
      date: new Date().toISOString(),
      userId: this.currentUser.id,
      orderId: orderId,
      transportId: order.transportId,
      lrImage: window.currentLRImage
    });
    DB.set('dispatchLogs', logs);
    window.currentLRImage = null;

    this.toast('Order Dispatched successfully!');
    this.navigate('home');
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
    `;
  },

  renderCashierAdd(container) {
    container.innerHTML = `
      <div class="card">
        <div class="card-title">New Transaction</div>
        
        <div class="tabs">
          <div class="tab-btn active" onclick="document.querySelectorAll('.tab-btn').forEach(e=>e.classList.remove('active')); this.classList.add('active'); window.txType='IN';">INCOME (IN)</div>
          <div class="tab-btn" onclick="document.querySelectorAll('.tab-btn').forEach(e=>e.classList.remove('active')); this.classList.add('active'); window.txType='OUT';">EXPENSE (OUT)</div>
        </div>
        
        <div class="form-group mt-1">
          <label>Expense Category</label>
          <select id="tx-category">
            <option value="general">General</option>
            <option value="small">Small Expense (< ₹5,000)</option>
            <option value="big">Big Expense (≥ ₹5,000)</option>
            <option value="salary">Salary</option>
            <option value="transport">Transport</option>
            <option value="maintenance">Maintenance</option>
            <option value="raw_material">Raw Material Purchase</option>
            <option value="utilities">Utilities (Electricity/Water)</option>
          </select>
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

  submitTransaction() {
    const amount = Number(document.getElementById('tx-amount').value);
    const note = document.getElementById('tx-note').value;
    const category = document.getElementById('tx-category').value;
    const ref = document.getElementById('tx-ref').value;
    if(!amount || amount <= 0) return this.toast('Valid amount required', 'error');

    const txs = DB.get('transactions');
    txs.push({
      id: DB.generateId(),
      type: window.txType,
      amount,
      note,
      category,
      ref,
      date: new Date().toISOString()
    });
    DB.set('transactions', txs);
    this.toast(`Transaction (${window.txType}) saved!`);
    this.navigate('home');
  },

  // --- GENERAL HISTORY & ROUTING ---
  renderHome(container) {
    const role = this.currentUser.role;
    if(role === 'RAW') this.renderRawHome(container);
    else if(role === 'SEMI') this.renderProductionHome(container, 'SEMI');
    else if(role === 'FINISHED') this.renderProductionHome(container, 'FINISHED');
    else if(role === 'SALES') this.renderSalesHome(container);
    else if(role === 'DISPATCH') this.renderDispatchHome(container);
    else if(role === 'CASHIER') this.renderCashierHome(container);
  },

  renderAction(container) {
    const role = this.currentUser.role;
    if(role === 'RAW') this.renderRawAdd(container);
    else if(role === 'SEMI') this.renderProductionAdd(container, 'SEMI');
    else if(role === 'FINISHED') this.renderProductionAdd(container, 'FINISHED');
    else if(role === 'SALES') this.renderSalesAdd(container);
    else if(role === 'DISPATCH') this.renderDispatchAdd(container);
    else if(role === 'CASHIER') this.renderCashierAdd(container);
  },

  renderHistory(container) {
    const role = this.currentUser.role;
    const page = window.currentPage || 1;
    let logs = [];
    let renderFn = null;
    
    if(role === 'CASHIER') {
      logs = DB.get('transactions').sort((a,b)=>new Date(b.date) - new Date(a.date));
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
      logs = DB.get('rawStock').sort((a,b)=>new Date(b.date)-new Date(a.date)).filter(l=>l.quantity > 0);
      window._historyLogs = logs;
      renderFn = (l, idx) => {
        const p = DB.get('rawMaterialsList').find(x=>x.id===l.productId) || {};
        return `<div class="list-item" onclick="app.openRawDrawer(${idx})" style="cursor:pointer;">
          <div style="display:flex; align-items:center; gap:10px; width:100%;">
            <img src="${p.image||''}" style="width:36px; height:36px; border-radius:6px; object-fit:cover;">
            <div class="list-item-content">
              <div class="list-item-title">+ ${l.quantity} ${p.unit||'kg'} ${p.name||'Unknown'}</div>
              <div class="list-item-meta">${new Date(l.date).toLocaleString()}</div>
            </div>
          </div>
        </div>`;
      };
    } else if (role === 'SEMI' || role === 'FINISHED') {
      logs = DB.get('productionLogs').filter(l => l.type === role).sort((a,b)=>new Date(b.date)-new Date(a.date));
      window._historyLogs = logs;
      renderFn = (l, idx) => {
        const pName = DB.get('products').find(x=>x.id===l.outputProductId)?.name || DB.get('rawMaterialsList').find(x=>x.id===l.outputProductId)?.name;
        return `<div class="list-item" onclick="app.openProductionDrawer(${idx})" style="cursor:pointer;">
          <div class="list-item-content">
            <div class="list-item-title">Produced ${l.outputQty}kg ${pName} (${l.outputGrade})</div>
            <div class="list-item-meta">${new Date(l.date).toLocaleString()}</div>
          </div>
        </div>`;
      };
    } else if (role === 'SALES') {
      // Combine all sales activities into one timeline
      const orders = DB.get('orders').map(o => ({...o, _type: 'ORDER', date: o.date}));
      const companies = DB.get('companies').filter(c=>c.date).map(c => ({...c, _type: 'COMPANY', date: c.date}));
      const transports = DB.get('transportCompanies').filter(t=>t.date).map(t => ({...t, _type: 'TRANSPORT', date: t.date}));
      logs = [...orders, ...companies, ...transports].sort((a,b) => new Date(b.date) - new Date(a.date));
      window._historyLogs = logs;
      renderFn = (item, idx) => {
        if (item._type === 'ORDER') {
          const compName = DB.get('companies').find(c=>c.id===item.companyId)?.name || 'Unknown';
          return `<div class="list-item" onclick="app.openSalesDrawer(${idx})" style="cursor:pointer;">
            <div class="list-item-content">
              <div class="list-item-title">#${item.id.toUpperCase()} — ${compName}</div>
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
      logs = DB.get('dispatchLogs').sort((a,b)=>new Date(b.date)-new Date(a.date));
      window._historyLogs = logs;
      renderFn = (d, idx) => {
        return `<div class="list-item" onclick="app.openDispatchDrawer(${idx})" style="cursor:pointer;">
          <div class="list-item-content">
            <div class="list-item-title">Order #${(d.orderId||'').toUpperCase()}</div>
            <div class="list-item-meta">${new Date(d.date).toLocaleString()}</div>
          </div>
        </div>`;
      };
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
      </div>
      
      ${this.renderDateFilterControls("app.refreshCurrentView")}
      <div class="form-group">
        <input type="text" placeholder="${this.t('Search history...')}" value="${window.historySearchQuery||''}" oninput="window.historySearchQuery=this.value; app.refreshCurrentView()">
      </div>
      
      <div class="card">
    `;
    
    if (logs.length > 0 && renderFn) {
      const { paginated, totalPages } = this.paginate(logs, page, 10);
      html += paginated.map((item, idx) => renderFn(item, (page-1)*10 + idx)).join('');
      html += `</div>` + this.renderPaginationControls(page, totalPages);
    } else if (!renderFn) {
      html += `<p class="text-muted text-center" style="padding:2rem;">Full historical audit logs are available in the Admin Panel.</p></div>`;
    } else {
      html += `<p>No history found for current filters.</p></div>`;
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
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Quantity</div><div style="font-weight:700; font-size:1.2rem; color:var(--secondary);">${l.outputQty} kg</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Date</div><div>${new Date(l.date).toLocaleString()}</div></div>
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
    
    const prodRows = (o.products || []).map(p => {
      const pName = products.find(x=>x.id===p.productId)?.name || rmList.find(x=>x.id===p.productId)?.name || 'Unknown';
      return `<tr><td>${pName}</td><td>${p.grade}</td><td>${p.qty}</td><td>\u20b9${(p.price||0).toLocaleString()}</td></tr>`;
    }).join('');
    
    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Order #${o.id.toUpperCase()}</h3>
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
    const order = DB.get('orders').find(o=>o.id===d.orderId) || {};
    const comp = DB.get('companies').find(c=>c.id===order.companyId) || {};
    const trans = DB.get('transportCompanies').find(t=>t.id===(d.transportId||order.transportId)) || {};
    const user = DB.get('users').find(u=>u.id===d.userId) || {};
    
    this.openDrawer(`
      <h3 style="margin-bottom:1rem;">Dispatch Details</h3>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem; margin-bottom:1rem;">
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Order</div><div style="font-weight:600;">#${(d.orderId||'').toUpperCase()}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Date</div><div>${new Date(d.date).toLocaleString()}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Company</div><div>${comp.name||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Transport</div><div>${trans.name||'N/A'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Dispatched By</div><div>${user.name||'System'}</div></div>
        <div><div style="color:var(--text-muted); font-size:0.8rem;">Order Value</div><div style="font-weight:700; color:var(--secondary);">\u20b9${(order.total||0).toLocaleString()}</div></div>
      </div>
      ${d.lrImage ? '<div style="margin-bottom:1rem;"><div style="color:var(--text-muted); font-size:0.8rem; margin-bottom:0.5rem;">LR Copy</div><img src="'+d.lrImage+'" style="width:100%; border-radius:10px; max-height:200px; object-fit:contain;"></div>' : ''}
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
    
    const rStock = this.getAggregatedStock('rawStock').reduce((s,i)=>s+i.qty, 0);
    const sStock = this.getAggregatedStock('semiStock').reduce((s,i)=>s+i.qty, 0);
    const fStock = this.getAggregatedStock('finishedStock').reduce((s,i)=>s+i.qty, 0);
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
                <td><span class="badge ${u.status==='BLOCKED'?'badge-closed':'badge-done'}">${u.status || 'ACTIVE'}</span></td>
                <td>
                  <button class="btn btn-sm btn-secondary" onclick="app.openUserModal('${u.id}')" style="margin-right:0.5rem; display:inline-block; width:auto;">Edit</button>
                  <button class="btn btn-sm ${u.status==='BLOCKED'?'btn-success':'btn-danger'}" onclick="app.toggleUserStatus('${u.id}')" style="display:inline-block; width:auto;">
                    ${u.status==='BLOCKED'?'Unblock':'Block'}
                  </button>
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
    
    const users = DB.get('users');
    if (userId) {
      const u = users.find(x => x.id === userId);
      if(u) { 
        u.name = name; 
        u.password = password;
        u.role = role; 
        u.parentId = role === 'SALES' ? parentId : 'u1'; 
      }
    } else {
      users.push({ 
        id: 'u'+Date.now(), 
        name, 
        password,
        role, 
        parentId: role === 'SALES' ? parentId : 'u1', 
        status: 'ACTIVE' 
      });
    }
    DB.set('users', users);
    this.toast('User saved successfully');
    this.closeModal();
    this.refreshCurrentView();
  },

  toggleUserStatus(userId) {
    const users = DB.get('users');
    const u = users.find(x => x.id === userId);
    if(u) {
      u.status = u.status === 'BLOCKED' ? 'ACTIVE' : 'BLOCKED';
      DB.set('users', users);
      this.toast(`User ${u.status === 'BLOCKED' ? 'blocked' : 'unblocked'}`);
      this.refreshCurrentView();
    }
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
          <thead><tr><th>Image</th><th>Name</th><th>Type</th><th>Actions</th></tr></thead>
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
                  <button class="btn btn-sm btn-secondary" onclick="app.openAddProductModal('${p.id}')" style="width:auto;">Edit</button>
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
    
    if (prodId) {
       if (type === 'RAW') {
         const rm = DB.get('rawMaterialsList');
         const obj = rm.find(x => x.id === prodId);
         if(obj) { obj.name = name; obj.image = image; DB.set('rawMaterialsList', rm); }
       } else {
         const pr = DB.get('products');
         const obj = pr.find(x => x.id === prodId);
         if(obj) { obj.name = name; obj.image = image; DB.set('products', pr); }
       }
    } else {
       if(type === 'RAW') {
         const rmList = DB.get('rawMaterialsList') || [];
         rmList.push({ id: 'rm'+Date.now(), name, type: 'RAW', image, unit: 'kg' });
         DB.set('rawMaterialsList', rmList);
       } else {
         const prods = DB.get('products');
         prods.push({ id: 'p'+Date.now(), name, type, image, unit: 'kg' });
         DB.set('products', prods);
       }
    }
    
    this.toast('Product saved successfully!');
    this.closeModal();
    this.refreshCurrentView();
  },

  renderAdminStock(el) {
    this.adminActiveTab(el);
    const container = document.getElementById('content-area');
    const allStock = [
      ...this.getAggregatedStock('rawStock').map(s=>({...s, type:'RAW'})),
      ...this.getAggregatedStock('semiStock').map(s=>({...s, type:'SEMI'})),
      ...this.getAggregatedStock('finishedStock').map(s=>({...s, type:'FINISHED'}))
    ].filter(s => s.qty !== 0);
    
    const page = window.currentPage || 1;
    const { paginated, totalPages } = this.paginate(allStock, page, 10);
    
    container.innerHTML = `
      <h2>Live Stock Inventory</h2>
      <div class="table-container mt-1">
        <table>
          <thead><tr><th>Product Name</th><th>Type</th><th>Grade</th><th>Available Qty</th></tr></thead>
          <tbody>
            ${paginated.map(s => `
              <tr>
                <td><strong>${s.name}</strong></td>
                <td><span class="badge ${s.type==='RAW'?'badge-open':(s.type==='SEMI'?'badge-pending':'badge-done')}">${s.type}</span></td>
                <td>${s.grade}</td>
                <td style="font-size:1.1rem; font-weight:bold; color:var(--primary-light);">${s.qty.toLocaleString()} <span style="font-size:0.8rem; color:var(--text-muted);">${s.unit}</span></td>
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
                <td style="font-weight:bold; color:var(--secondary);">${p.qty} kg</td>
                <td style="font-size:0.85rem; color:var(--text-muted); max-width:150px; text-overflow:ellipsis; overflow:hidden;">${p.note||'\u2014'}</td>
                <td><span class="badge ${p.status==='DONE'?'badge-done':'badge-pending'}">${p.status}</span></td>
                <td>
                  ${p.status === 'PENDING' ? `<button class="btn btn-sm btn-success" style="width:auto;" onclick="app.markPOAsDone('${p.id}')">Mark Arrived</button>` : '\u2014'}
                </td>
              </tr>`;
            }).join('') || '<tr><td colspan="7" class="text-center text-muted">No purchase orders found.</td></tr>'}
          </tbody>
        </table>
      </div>
      ${this.renderPaginationControls(page, totalPages)}
    `;
  },

  markPOAsDone(poId) {
    const pos = DB.get('purchaseOrders') || [];
    const p = pos.find(x => x.id === poId);
    if(p) {
      p.status = 'DONE';
      DB.set('purchaseOrders', pos);
      
      // Auto-inward to Stock Room if desired? 
      // The requirement: "once arrived he will mark this as done that's it"
      // Let's add it to raw stock so it completes the loop automatically if it was raw, or semi if it was semi...
      // The user said: "so purchaes order there will be all the raw items which is listed by the admin" (implies Raw materials only).
      const rs = DB.get('rawStock');
      rs.push({
        id: DB.generateId(),
        productId: p.materialId,
        grade: 'NONE',
        quantity: p.qty,
        date: new Date().toISOString()
      });
      DB.set('rawStock', rs);
      
      this.toast('Purchase marked as Arrived and added to Stock');
      this.refreshCurrentView();
    }
  },

  renderAdminLogs(el) {
    this.adminActiveTab(el);
    const container = document.getElementById('content-area');
    const plogs = DB.get('productionLogs').map(l=>({...l, category:'Production'}));
    const dlogs = DB.get('dispatchLogs').map(l=>({...l, category:'Dispatch'}));
    const allLogs = [...plogs, ...dlogs].sort((a,b)=>new Date(b.date)-new Date(a.date));

    const page = window.currentPage || 1;
    const { paginated, totalPages } = this.paginate(allLogs, page, 10);
    const getUserName = (id) => DB.get('users').find(u=>u.id===id)?.name || 'System';

    container.innerHTML = `
      <h2>System Activity Logs</h2>
      <div class="table-container mt-1">
        <table>
          <thead><tr><th>Date</th><th>Category</th><th>Details</th></tr></thead>
          <tbody>
            ${paginated.map(l => `
              <tr>
                <td style="font-size:0.85rem;">${new Date(l.date).toLocaleString()}</td>
                <td><span class="badge ${l.category==='Production'?'badge-pending':'badge-done'}">${l.category}</span></td>
                <td style="font-size:0.9rem;">
                  ${l.category==='Production' ? 
                    `Produced ${l.outputQty} units of ${DB.get('products').find(p=>p.id===l.outputProductId)?.name || DB.get('rawMaterialsList').find(p=>p.id===l.outputProductId)?.name} (${l.outputGrade}) <br><span style="color:var(--text-muted); font-size:0.8rem;">By: ${getUserName(l.userId)}</span>` : 
                    `Dispatched Order #${l.orderId?.toUpperCase()} <br><span style="color:var(--text-muted); font-size:0.8rem;">By: ${getUserName(l.userId)}</span>`}
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
      ${this.renderPaginationControls(page, totalPages)}
    `;
  }
};

window.onload = () => {
  app.init();
};
