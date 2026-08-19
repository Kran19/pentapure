@extends('layouts.app')

@section('content')
<div class="card">
  <div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
    <h2 style="margin:0;">💰 New Transactions</h2>
    <button class="btn btn-sm" onclick="addCategoryPrompt()" style="padding:0.4rem 0.8rem;">+ Add Category</button>
  </div>
  
  <div id="transaction-rows" style="display:flex; flex-direction:column; gap:15px; margin-bottom:1.5rem;">
    <!-- Rows injected here -->
  </div>

  <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:1rem;">
    <button class="btn btn-secondary" onclick="addTransactionRow()" style="flex:1; padding:0.8rem;">+ Add Row</button>
    <button class="btn" onclick="saveTransactions(this)" style="flex:2; padding:0.8rem;">Save Transactions</button>
  </div>
</div>

<script>
  // Global category list populated from PHP
  window.expenseCategories = @json($pageData['categories'] ?? []);

  // Event listener to dynamically update all category selects when a new one is added

  function addCategoryPrompt() {
    Swal.fire({
      title: 'Add New Category',
      input: 'text',
      inputPlaceholder: 'Category Name',
      showCancelButton: true,
      confirmButtonText: 'Save',
      confirmButtonColor: '#f59e0b',
      background: '#ffffff',
      color: '#333333',
      preConfirm: (name) => {
        if (!name) {
          Swal.showValidationMessage('Category name is required');
          return false;
        }
        return name;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(window.baseUrl + '/' + window.userSlug + '/categories', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({ name: result.value })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            Swal.fire({ icon: 'success', title: 'Added', text: data.message, timer: 1000, showConfirmButton: false });
            // Add to global array and update selects
            const slugVal = result.value.toLowerCase().replace(/ /g, '_');
            window.expenseCategories.push({ value: slugVal, label: result.value.toUpperCase() });
            
            // Sort categories
            window.expenseCategories.sort((a, b) => {
              if (a.label === 'NONE' || a.label === 'N/A') return -1;
              if (b.label === 'NONE' || b.label === 'N/A') return 1;
              return a.label.localeCompare(b.label);
            });
            
            // Update all existing dropdowns
            document.querySelectorAll('.tx-category').forEach(select => {
              const currentVal = select.value;
              select.innerHTML = window.expenseCategories.map(c => `<option value="${c.value}">${c.label}</option>`).join('');
              select.value = currentVal; // preserve selected
            });
          } else {
            Swal.fire('Error', data.message || 'Failed to add category', 'error');
          }
        })
        .catch(err => Swal.fire('Error', 'Network error', 'error'));
      }
    });
  }

  function addTransactionRow() {
    const categories = window.expenseCategories || [];
    const container = document.getElementById('transaction-rows');
    
    const wrapper = document.createElement('div');
    wrapper.className = 'row-wrapper';

    if (container.children.length > 0) {
      const hr = document.createElement('hr');
      hr.style.border = '0';
      hr.style.borderTop = '1px dotted #000';
      hr.style.margin = '20px 0';
      hr.style.opacity = '1';
      wrapper.appendChild(hr);
    }

    const div = document.createElement('div');
    div.className = 'dynamic-row';
    div.style.display = 'flex';
    div.style.flexDirection = 'column';
    div.style.gap = '12px';
    div.style.alignItems = 'stretch';
    div.style.position = 'relative';
    div.style.background = 'rgba(255,255,255,0.03)';
    div.style.padding = '1rem';
    div.style.borderRadius = '12px';
    div.style.border = '1px solid rgba(255,255,255,0.06)';
    
    div.innerHTML = `
      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
        <!-- Type -->
        <div class="form-group" style="flex:1 1 120px; margin-bottom:0;">
          <label style="font-size:0.75rem; margin-bottom:4px;">Type</label>
          <select class="tx-type" style="padding:0.6rem; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff; width:100%;">
            <option value="EXPENSE">EXPENSE (OUT)</option>
            <option value="INCOME">INCOME (IN)</option>
          </select>
        </div>
        
        <!-- Category -->
        <div class="form-group" style="flex:2 1 200px; margin-bottom:0;">
          <label style="font-size:0.75rem; margin-bottom:4px;">Category</label>
          <select class="tx-category" style="padding:0.6rem; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff; width:100%;">
            ${categories.map(c => `<option value="${c.value}">${c.label}</option>`).join('')}
          </select>
        </div>

        <!-- Amount -->
        <div class="form-group" style="flex:1 1 120px; margin-bottom:0;">
          <label style="font-size:0.75rem; margin-bottom:4px;">Amount (₹)</label>
          <input type="number" class="tx-amount" placeholder="0.00" step="0.01" min="0.01" required style="padding:0.6rem; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff; width:100%;">
        </div>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <!-- Note -->
        <div class="form-group" style="flex:2 1 250px; margin-bottom:0;">
          <label style="font-size:0.75rem; margin-bottom:4px;">Particulars / Note</label>
          <input type="text" class="tx-note" placeholder="Description of transaction" style="padding:0.6rem; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff; width:100%;">
        </div>

        <!-- Reference -->
        <div class="form-group" style="flex:1 1 150px; margin-bottom:0;">
          <label style="font-size:0.75rem; margin-bottom:4px;">Reference / Bill No. (optional)</label>
          <input type="text" class="tx-ref" placeholder="e.g. INV-001" style="padding:0.6rem; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff; width:100%;">
        </div>

        <!-- Bill file -->
        <div class="form-group" style="flex:1 1 150px; margin-bottom:0;">
          <label style="font-size:0.75rem; margin-bottom:4px;">Attach Bill (optional)</label>
          <input type="file" class="tx-bill" accept="image/jpeg,image/png,application/pdf" style="font-size:0.85rem; padding:0.4rem; background:#0d1117; border:1px dashed #30363d; border-radius:6px; width:100%; color:#8b949e;">
        </div>
      </div>

      <button class="btn btn-danger btn-sm" style="position:absolute; top:8px; right:8px; width:28px; height:28px; padding:0; border-radius:50%; display:flex; align-items:center; justify-content:center;" onclick="this.closest('.row-wrapper').remove()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    `;
    wrapper.appendChild(div);
    container.appendChild(wrapper);
  }

  function saveTransactions(btn) {
    const rows = document.querySelectorAll('#transaction-rows .dynamic-row');
    if (rows.length === 0) {
      app.toast('Add at least one transaction row', 'error');
      return;
    }

    let validationFailed = false;
    const formData = new FormData();

    rows.forEach((row, idx) => {
      const type = row.querySelector('.tx-type').value;
      const category = row.querySelector('.tx-category').value;
      const amount = Number(row.querySelector('.tx-amount').value);
      const note = row.querySelector('.tx-note').value;
      const reference = row.querySelector('.tx-ref').value;
      const file = row.querySelector('.tx-bill').files[0];

      if (!amount || amount <= 0) {
        app.toast(`Enter a valid amount for row ${idx + 1}`, 'error');
        validationFailed = true;
        return;
      }

      formData.append(`transactions[${idx}][type]`, type);
      formData.append(`transactions[${idx}][category]`, category);
      formData.append(`transactions[${idx}][amount]`, amount);
      formData.append(`transactions[${idx}][note]`, note);
      formData.append(`transactions[${idx}][reference]`, reference);
      
      if (file) {
        formData.append(`bills[${idx}]`, file);
      }
    });

    if (validationFailed) return;

    btn.disabled = true;
    btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin" style="vertical-align: middle; margin-right:5px;"><circle cx="12" cy="12" r="10" opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"></path></svg> Saving...`;

    fetch(window.baseUrl + '/' + window.userSlug + '/action', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        app.toast(data.message || 'Transactions saved!');
        setTimeout(() => window.location.reload(), 1000);
      } else {
        app.toast(data.message || 'Failed to save transactions', 'error');
        btn.disabled = false;
        btn.innerHTML = 'Save Transactions';
      }
    })
    .catch(err => {
      app.toast('Network error: ' + err.message, 'error');
      btn.disabled = false;
      btn.innerHTML = 'Save Transactions';
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Populate categories globally
    window.serverPageData = window.serverPageData || {};
    window.serverPageData.categories = window.expenseCategories;
    
    // Add one default row
    addTransactionRow();
  });
</script>
@endsection
