@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

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
  gap: 0.8rem;
  margin-top: 1rem;
  margin-bottom: 1.5rem;
}

.cashier-tx-row {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
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
.cashier-tx-row input[type="number"],
.cashier-tx-row input[type="date"] {
  width: 100%;
  padding: 0.8rem 1rem;
  border-radius: 8px;
  border: 1px solid var(--border-soft, #DDCFAF);
  background: var(--input-bg, transparent);
  color: var(--text-main, #333);
  font-size: 0.95rem;
  box-sizing: border-box;
}

.cashier-tx-row input[type="date"]::-webkit-calendar-picker-indicator {
  cursor: pointer;
  opacity: 0.85;
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

/* Media button styling for Cashier Action */
.btn-bill-camera {
  background: #f59e0b !important;
  color: #1e293b !important;
  -webkit-text-fill-color: #1e293b !important;
  border: 1.5px solid #d97706 !important;
  font-weight: 700 !important;
  border-radius: 8px !important;
  padding: 0.55rem 0.95rem !important;
  font-size: 0.85rem !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 6px !important;
  box-shadow: 0 2px 5px rgba(245, 158, 11, 0.25) !important;
  cursor: pointer !important;
  transition: all 0.15s ease !important;
  white-space: nowrap !important;
  width: auto !important;
}
.btn-bill-camera:hover {
  background: #fbbf24 !important;
  transform: translateY(-1px);
}
.btn-bill-camera * {
  color: #1e293b !important;
  -webkit-text-fill-color: #1e293b !important;
}

.btn-bill-gallery {
  background: #ffffff !important;
  color: #1e293b !important;
  -webkit-text-fill-color: #1e293b !important;
  border: 1.5px solid #cbd5e1 !important;
  font-weight: 700 !important;
  border-radius: 8px !important;
  padding: 0.55rem 0.95rem !important;
  font-size: 0.85rem !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 6px !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
  cursor: pointer !important;
  transition: all 0.15s ease !important;
  white-space: nowrap !important;
  width: auto !important;
}
.btn-bill-gallery:hover {
  background: #f1f5f9 !important;
  border-color: #94a3b8 !important;
  transform: translateY(-1px);
}
.btn-bill-gallery * {
  color: #1e293b !important;
  -webkit-text-fill-color: #1e293b !important;
}

@media (max-width: 600px) {
  .bill-attach-container {
    width: 100% !important;
    flex: 1 1 100% !important;
  }
  .bill-attach-container > div {
    display: flex !important;
    width: 100% !important;
    gap: 8px !important;
  }
  .btn-bill-camera,
  .btn-bill-gallery {
    flex: 1 1 calc(50% - 4px) !important;
    width: calc(50% - 4px) !important;
    padding: 0.65rem 0.4rem !important;
    font-size: 0.82rem !important;
    box-sizing: border-box !important;
  }
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

  function triggerRowCamera(btn) {
    const container = btn.closest('.bill-attach-container');
    const camInput = container.querySelector('.tx-bill-camera');
    if (camInput) camInput.click();
  }

  function triggerRowGallery(btn) {
    const container = btn.closest('.bill-attach-container');
    const fileInput = container.querySelector('.tx-bill');
    if (fileInput) fileInput.click();
  }

  function handleBillFileInput(input) {
    const container = input.closest('.bill-attach-container');
    const row = input.closest('.cashier-tx-row');
    const file = input.files && input.files[0];
    if (!file) return;

    if (file.type.startsWith('image/')) {
      row._originalImageFile = file;

      if (window.ImageCropper) {
        window.ImageCropper.open({
          file: file,
          title: '✂️ Crop Bill / Receipt',
          originalName: file.name,
          onDone: function(result) {
            row._attachedFile = result.file;
            row._attachedDataUrl = result.dataUrl;

            updateBillUI(container, {
              isImage: true,
              name: result.name,
              thumbUrl: result.dataUrl
            });
          },
          onCancel: function() {
            input.value = '';
          }
        });
      } else {
        const reader = new FileReader();
        reader.onload = (e) => {
          row._attachedFile = file;
          row._attachedDataUrl = e.target.result;
          updateBillUI(container, {
            isImage: true,
            name: file.name,
            thumbUrl: e.target.result
          });
        };
        reader.readAsDataURL(file);
      }
    } else if (file.type === 'application/pdf') {
      row._attachedFile = file;
      row._attachedDataUrl = null;
      row._originalImageFile = null;

      updateBillUI(container, {
        isImage: false,
        isPdf: true,
        name: file.name
      });
    } else {
      app.toast('Only JPG, PNG, WEBP images or PDF files are allowed.', 'error');
      input.value = '';
    }
  }

  function updateBillUI(container, data) {
    const actions = container.querySelector('.bill-file-actions');
    const nameSpan = container.querySelector('.bill-file-name');
    const thumb = container.querySelector('.bill-thumb-preview');
    const pdfIcon = container.querySelector('.bill-pdf-icon');
    const recropBtn = container.querySelector('.btn-recrop-bill');
    const btnCam = container.querySelector('.btn-bill-camera');
    const btnGal = container.querySelector('.btn-bill-gallery');

    actions.style.display = 'inline-flex';
    nameSpan.textContent = data.name;

    if (btnCam) btnCam.style.display = 'none';
    if (btnGal) btnGal.style.display = 'none';

    if (data.isImage) {
      thumb.src = data.thumbUrl;
      thumb.style.display = 'inline-block';
      pdfIcon.style.display = 'none';
      recropBtn.style.display = 'inline-flex';
    } else {
      thumb.style.display = 'none';
      pdfIcon.style.display = 'inline-block';
      recropBtn.style.display = 'none';
    }
  }

  function removeBillFile(btn) {
    const container = btn.closest('.bill-attach-container');
    const row = btn.closest('.cashier-tx-row');
    row._attachedFile = null;
    row._attachedDataUrl = null;
    row._originalImageFile = null;

    container.querySelectorAll('input[type="file"]').forEach(inp => inp.value = '');
    container.querySelector('.bill-file-actions').style.display = 'none';
    container.querySelector('.bill-thumb-preview').src = '';
    container.querySelector('.bill-file-name').textContent = '';

    const btnCam = container.querySelector('.btn-bill-camera');
    const btnGal = container.querySelector('.btn-bill-gallery');
    if (btnCam) btnCam.style.display = 'inline-flex';
    if (btnGal) btnGal.style.display = 'inline-flex';
  }

  function recropBillFile(btn) {
    const row = btn.closest('.cashier-tx-row');
    const container = btn.closest('.bill-attach-container');
    const fileToCrop = row._originalImageFile || row._attachedFile || row._attachedDataUrl;
    if (!fileToCrop) return;

    if (window.ImageCropper) {
      window.ImageCropper.open({
        file: fileToCrop,
        title: '✂️ Re-crop Bill / Receipt',
        originalName: (row._attachedFile && row._attachedFile.name) || 'bill.jpg',
        onDone: function(result) {
          row._attachedFile = result.file;
          row._attachedDataUrl = result.dataUrl;

          updateBillUI(container, {
            isImage: true,
            name: result.name,
            thumbUrl: result.dataUrl
          });
        }
      });
    }
  }

  function previewBillFile(btn) {
    const row = btn.closest('.cashier-tx-row');
    const file = row._attachedFile || row.querySelector('.tx-bill')?.files[0];
    const dataUrl = row._attachedDataUrl;

    if (dataUrl || (file && file.type && file.type.startsWith('image/'))) {
      const url = dataUrl || URL.createObjectURL(file);
      Swal.fire({
        title: (file && file.name) ? file.name : 'Attached Bill',
        imageUrl: url,
        imageAlt: 'Attached Bill Preview',
        confirmButtonColor: '#f59e0b',
        confirmButtonText: 'Close',
        width: 'min(92vw, 680px)'
      });
    } else if (file && file.type === 'application/pdf') {
      const fileUrl = URL.createObjectURL(file);
      window.open(fileUrl, '_blank');
    } else {
      Swal.fire('File Preview', file ? file.name : 'No file attached', 'info');
    }
  }

  function addTransactionRow() {
    const categories = window.expenseCategories || [];
    const container = document.getElementById('transaction-rows');
    
    const wrapper = document.createElement('div');
    wrapper.className = 'row-wrapper';

    if (container.children.length > 0) {
      const hr = document.createElement('hr');
      hr.style.cssText = 'border:0; border-top:2px solid #f59e0b; margin:1.2rem 0; opacity:0.9;';
      wrapper.appendChild(hr);
    }

    // Default to the previous row's date if set, or today's date
    const existingDateInputs = container.querySelectorAll('.tx-date');
    let defaultDate = '{{ date('Y-m-d') }}';
    if (existingDateInputs.length > 0) {
      const lastDate = existingDateInputs[existingDateInputs.length - 1].value;
      if (lastDate) defaultDate = lastDate;
    }

    const div = document.createElement('div');
    div.className = 'cashier-tx-row';
    
    div.innerHTML = `
      <!-- Line 1: Date, Type, Category, Amount, and Delete button -->
      <div style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
        <div class="form-group" style="flex:1.2 1 150px;">
          <label>Date</label>
          <input type="date" class="tx-date" value="${defaultDate}" required>
        </div>

        <div class="form-group" style="flex:1.1 1 130px;">
          <label>Type</label>
          <select class="tx-type">
            <option value="OUT">EXPENSE (OUT)</option>
            <option value="IN">INCOME (IN)</option>
          </select>
        </div>
        
        <div class="form-group" style="flex:2.2 1 200px;">
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
      <div style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
        <div class="form-group" style="flex:2 1 250px;">
          <label>Particulars / Note</label>
          <input type="text" class="tx-note" placeholder="Description of transaction">
        </div>

        <div class="form-group" style="flex:1 1 160px;">
          <label>Reference / Bill No. (optional)</label>
          <input type="text" class="tx-ref" placeholder="e.g. INV-001">
        </div>

        <div class="form-group bill-attach-container" style="flex:1.8 1 240px;">
          <label>Attach Bill (optional)</label>
          <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; min-height:42px;">
            <!-- Mobile Camera Button -->
            <button type="button" class="btn btn-sm btn-bill-camera" onclick="triggerRowCamera(this)" title="Take photo with camera">
              📷 Camera
            </button>
            <!-- File Gallery Button -->
            <button type="button" class="btn btn-sm btn-bill-gallery" onclick="triggerRowGallery(this)" title="Choose image or PDF from storage">
              📁 Choose File
            </button>

            <!-- Hidden Inputs -->
            <input type="file" class="tx-bill-camera" accept="image/*" capture="environment" style="display:none;" onchange="handleBillFileInput(this)">
            <input type="file" class="tx-bill" accept="image/jpeg,image/png,image/webp,application/pdf" style="display:none;" onchange="handleBillFileInput(this)">

            <!-- Attached preview & actions -->
            <div class="bill-file-actions" style="display:none;">
              <img class="bill-thumb-preview" src="" style="width:34px; height:34px; object-fit:cover; border-radius:6px; border:1.5px solid #f59e0b; display:none; cursor:pointer;" onclick="previewBillFile(this)" title="Click to enlarge">
              <span class="bill-pdf-icon" style="font-size:1.4rem; display:none;">📄</span>
              <span class="bill-file-name" style="max-width:120px; overflow:hidden; text-overflow:ellipsis; display:inline-block; color:#92400e; font-weight:700; white-space:nowrap;"></span>
              <button type="button" onclick="previewBillFile(this)" title="View Attached File" style="background:#f59e0b; color:#1e293b; border:none; border-radius:6px; padding:5px 8px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-weight:bold;">
                👁️
              </button>
              <button type="button" class="btn-recrop-bill" onclick="recropBillFile(this)" title="Re-crop Image" style="background:#0284c7; color:#fff; border:none; border-radius:6px; padding:5px 8px; cursor:pointer; display:none; align-items:center; justify-content:center; font-weight:bold;">
                ✂️
              </button>
              <button type="button" onclick="removeBillFile(this)" title="Delete Attached File" style="background:#ef4444; color:#fff; border:none; border-radius:6px; padding:5px 8px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center;">
                🗑️
              </button>
            </div>
          </div>
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
      const dateEl = row.querySelector('.tx-date');
      const date = dateEl ? dateEl.value : '';
      const type = row.querySelector('.tx-type').value;
      const category = row.querySelector('.tx-category').value;
      const amount = Number(row.querySelector('.tx-amount').value);
      const note = row.querySelector('.tx-note').value;
      const reference = row.querySelector('.tx-ref').value;
      const file = row._attachedFile || row.querySelector('.tx-bill')?.files[0];

      if (!amount || amount <= 0) {
        app.toast(`Enter a valid amount for row ${idx + 1}`, 'error');
        validationFailed = true;
        return;
      }

      if (date) {
        formData.append(`transactions[${idx}][date]`, date);
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

    if (window.isReadOnly) {
      if (typeof Swal !== 'undefined') {
        Swal.fire('View-Only Mode', 'You have View-Only permission for Cashier Entry. Saving transactions is disabled.', 'warning');
      } else {
        alert('You have View-Only permission for Cashier Entry. Saving transactions is disabled.');
      }
      return;
    }

    btn.disabled = true;
    btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin" style="vertical-align: middle; margin-right:5px;"><circle cx="12" cy="12" r="10" opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"></path></svg> Saving...`;

    fetch(window.location.pathname, {
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