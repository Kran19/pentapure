@extends('layouts.app')

@section('content')
<style>
/* Remove number input spinner arrows */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button,
.no-spinners::-webkit-outer-spin-button,
.no-spinners::-webkit-inner-spin-button {
  -webkit-appearance: none !important;
  margin: 0 !important;
}

input[type="number"],
.no-spinners {
  -moz-appearance: textfield !important;
}

#transaction-rows {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin-top: 1.2rem;
  margin-bottom: 2rem;
}

.cashier-tx-row {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  width: 100%;
}

.cashier-tx-row .form-group {
  margin-bottom: 0 !important;
}

.cashier-tx-row label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--text-muted);
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.cashier-tx-row select,
.cashier-tx-row input[type="text"],
.cashier-tx-row input[type="number"] {
  width: 100%;
  padding: 0.8rem 1rem;
  border-radius: 8px;
  border: 1px solid var(--border-soft, #DDCFAF);
  background: var(--input-bg, transparent);
  color: var(--text-main, #333);
  font-size: 0.95rem;
  box-sizing: border-box;
}

.cashier-tx-row input[type="file"] {
  width: 100%;
  padding: 0.6rem 0.8rem;
  border-radius: 8px;
  border: 1px dashed var(--border-soft, #DDCFAF);
  background: var(--input-bg, transparent);
  color: var(--text-muted);
  font-size: 0.85rem;
  box-sizing: border-box;
}
</style>

<div class="card" style="padding:2rem;">
  <div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:1.5rem;">
    <h2 style="margin:0; font-size:1.4rem;">💰 New Transactions</h2>
    <button class="btn btn-sm" onclick="addCategoryPrompt()" style="padding:0.5rem 1.2rem; font-weight:600;">+ Add Category</button>
  </div>
  
  <div id="transaction-rows">
    <!-- Rows injected here -->
  </div>

  <div style="display:flex; gap:16px; flex-wrap:wrap; margin-top:2.2rem;">
    <button class="btn btn-secondary" onclick="addTransactionRow()" style="flex:1; padding:0.9rem; font-weight:600; font-size:1rem;">+ Add Row</button>
    <button class="btn" onclick="saveTransactions(this)" style="flex:2; padding:0.9rem; font-weight:700; font-size:1rem; letter-spacing:0.5px;">Save Transactions</button>
  </div>
</div>

<script>
  // Global category list populated from PHP
  window.expenseCategories = @json($pageData['categories'] ?? []);

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
            const slugVal = result.value.toLowerCase().replace(/ /g, '_');
            window.expenseCategories.push({ value: slugVal, label: result.value.toUpperCase() });
            
            window.expenseCategories.sort((a, b) => {
              if (a.label === 'NONE' || a.label === 'N/A') return -1;
              if (b.label === 'NONE' || b.label === 'N/A') return 1;
              return a.label.localeCompare(b.label);
            });
            
            document.querySelectorAll('.tx-category').forEach(select => {
              const currentVal = select.value;
              select.innerHTML = window.expenseCategories.map(c => `<option value="${c.value}">${c.label}</option>`).join('');
              select.value = currentVal;
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
      hr.style.cssText = 'border:0; border-top:1px dotted var(--glass-border, rgba(0,0,0,0.25)); margin:2.2rem 0; opacity:0.6;';
      wrapper.appendChild(hr);
    }

    const div = document.createElement('div');
    div.className = 'cashier-tx-row';
    
    div.innerHTML = `
      <!-- Line 1: Type, Category, Amount, and Delete button -->
      <div style="display:flex; gap:16px; align-items:flex-end;">
        <div class="form-group" style="flex:1 1 120px;">
          <label>Type</label>
          <select class="tx-type">
            <option value="OUT">EXPENSE (OUT)</option>
            <option value="IN">INCOME (IN)</option>
          </select>
        </div>
        
        <div class="form-group" style="flex:2.5 1 220px;">
          <label>Category</label>
          <select class="tx-category">
            ${categories.map(c => `<option value="${c.value}">${c.label}</option>`).join('')}
          </select>
        </div>

        <div class="form-group" style="flex:1.2 1 140px;">
          <label>Amount (₹)</label>
          <input type="number" class="tx-amount no-spinners" placeholder="0.00" step="0.01" min="0.01" required>
        </div>

        <button type="button" class="btn btn-danger" style="flex:0 0 42px; width:42px; height:42px; padding:0; border-radius:8px; display:flex; align-items:center; justify-content:center; background:#e11d48; color:#fff; border:none; cursor:pointer;" onclick="this.closest('.row-wrapper').remove()" title="Remove Row">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>

      <!-- Line 2: Note, Reference, Bill file -->
      <div style="display:flex; gap:16px; align-items:flex-end;">
        <div class="form-group" style="flex:2 1 250px;">
          <label>Particulars / Note</label>
          <input type="text" class="tx-note" placeholder="Description of transaction">
        </div>

        <div class="form-group" style="flex:1 1 160px;">
          <label>Reference / Bill No. (optional)</label>
          <input type="text" class="tx-ref" placeholder="e.g. INV-001">
        </div>

        <div class="form-group" style="flex:1 1 180px;">
          <label>Attach Bill (optional)</label>
          <input type="file" class="tx-bill" accept="image/jpeg,image/png,application/pdf">
        </div>
      </div>
    `;
    wrapper.appendChild(div);
    container.appendChild(wrapper);
  }

  function saveTransactions(btn) {
    const rows = document.querySelectorAll('#transaction-rows .cashier-tx-row');
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
    window.serverPageData = window.serverPageData || {};
    window.serverPageData.categories = window.expenseCategories;
    
    addTransactionRow();
  });
</script>
@endsection