(function () {
  const DATE_VALUE_PATTERN = /^(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}|\d{1,2}\s+[A-Za-z]{3,}\s+\d{4})$/;

  function normalizeText(value) {
    return (value || '').trim();
  }

  function parseDate(value) {
    const normalized = normalizeText(value).replace(/\//g, '-');
    if (!DATE_VALUE_PATTERN.test(normalized)) return null;
    const parsed = new Date(normalized);
    return Number.isNaN(parsed.getTime()) ? null : parsed.getTime();
  }

  function uniqueValues(values) {
    return Array.from(new Set(values.filter((value) => value !== '')));
  }

  function createInput(type, name, placeholder) {
    const input = document.createElement('input');
    input.type = type;
    input.name = name;
    input.placeholder = placeholder || '';
    input.className = 'btn-sm filter-input';
    input.style.background = '#FFFFFF';
    input.style.color = '#2A2A2A';
    input.style.border = '1px solid #DDCFAF';
    input.style.padding = '5px 10px';
    input.style.minWidth = '110px';
    input.style.textTransform = 'uppercase';
    return input;
  }

  function createSelect(name, options) {
    const select = document.createElement('select');
    select.name = name;
    select.className = 'btn-sm filter-input';
    select.style.background = '#FFFFFF';
    select.style.color = '#2A2A2A';
    select.style.border = '1px solid #DDCFAF';
    select.style.padding = '5px 10px';
    select.style.minWidth = '110px';
    select.style.textTransform = 'uppercase';

    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = `All ${name}`;
    select.appendChild(emptyOption);

    options.forEach((value) => {
      const option = document.createElement('option');
      option.value = value;
      option.textContent = value;
      select.appendChild(option);
    });

    return select;
  }

  function createResetButton() {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm btn-secondary';
    btn.textContent = 'Reset';
    btn.style.minWidth = 'auto';
    return btn;
  }

  function createPageSizeSelect() {
    const select = document.createElement('select');
    select.className = 'btn-sm page-size-select';
    select.style.background = '#FFFFFF';
    select.style.color = '#2A2A2A';
    select.style.border = '1px solid #DDCFAF';
    select.style.padding = '5px 10px';
    select.style.fontWeight = '600';
    select.style.marginLeft = 'auto';

    const sizes = [
      { val: '10', label: 'Show 10' },
      { val: '20', label: 'Show 20' },
      { val: '50', label: 'Show 50' },
      { val: '100', label: 'Show 100' },
      { val: 'all', label: 'Show All' }
    ];

    sizes.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.val;
      opt.textContent = s.label;
      select.appendChild(opt);
    });

    return select;
  }

  function matchesFilter(value, filter, type) {
    const normalized = normalizeText(value).toLowerCase();
    const filterValue = normalizeText(filter);
    if (!filterValue) return true;

    if (type === 'date') {
      const dateValue = parseDate(value);
      const dateFilter = parseDate(filterValue);
      if (dateValue === null || dateFilter === null) {
        return normalized.includes(filterValue.toLowerCase());
      }
      return dateValue === dateFilter;
    }

    return normalized.includes(filterValue.toLowerCase());
  }

  function updateTableState(table, controls, paginationState) {
    if (!table.tBodies || !table.tBodies.length) return;
    const allRows = Array.from(table.tBodies[0].querySelectorAll('tr'));
    if (!allRows.length) return;

    // Filter matching rows
    const matchingRows = allRows.filter((row) => {
      return controls.every((control) => {
        if (!control.element.value) return true;
        const cell = row.children[control.index];
        return matchesFilter(cell ? cell.textContent : '', control.element.value, control.type);
      });
    });

    const total = matchingRows.length;
    const sizeVal = paginationState.pageSizeSelect.value;
    const pageSize = sizeVal === 'all' ? total : parseInt(sizeVal, 10);

    const totalPages = pageSize <= 0 || pageSize >= total ? 1 : Math.ceil(total / pageSize);

    if (paginationState.currentPage > totalPages) paginationState.currentPage = totalPages;
    if (paginationState.currentPage < 1) paginationState.currentPage = 1;

    const currentPage = paginationState.currentPage;
    const startIdx = sizeVal === 'all' || total === 0 ? 0 : (currentPage - 1) * pageSize;
    const endIdx = sizeVal === 'all' ? total : Math.min(startIdx + pageSize, total);

    // Hide all rows
    allRows.forEach((row) => { row.style.display = 'none'; });

    // Show visible page rows
    matchingRows.slice(startIdx, endIdx).forEach((row) => {
      row.style.display = '';
    });

    // Render pagination footer info & buttons
    renderPaginationFooter(table, paginationState, total, startIdx, endIdx, totalPages);
  }

  function renderPaginationFooter(table, paginationState, total, startIdx, endIdx, totalPages) {
    let footer = paginationState.footerElement;
    if (!footer) {
      footer = document.createElement('div');
      footer.className = 'table-pagination-footer flex-between';
      footer.style.cssText = 'display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; margin-top:0.75rem; padding:0.5rem 0.25rem; font-size:0.85rem; color:var(--text-muted);';
      const targetWrapper = table.closest('.table-container') || table;
      targetWrapper.parentNode.insertBefore(footer, targetWrapper.nextSibling);
      paginationState.footerElement = footer;
    }

    if (total === 0) {
      footer.innerHTML = `<div>Showing 0 to 0 of 0 entries</div><div></div>`;
      return;
    }

    const showingFrom = startIdx + 1;
    const showingTo = endIdx;
    const currentPage = paginationState.currentPage;

    let buttonsHtml = `<div style="display:flex; align-items:center; gap:4px;">`;
    
    // Prev Button
    buttonsHtml += `<button type="button" class="btn btn-sm btn-secondary pag-prev" ${currentPage <= 1 ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''} style="padding:3px 8px; font-size:0.8rem;">Prev</button>`;

    // Page numbers
    for (let p = 1; p <= totalPages; p++) {
      if (totalPages > 7 && Math.abs(p - currentPage) > 2 && p !== 1 && p !== totalPages) {
        if (p === 2 && currentPage > 4) buttonsHtml += `<span style="padding:0 4px;">...</span>`;
        if (p === totalPages - 1 && currentPage < totalPages - 3) buttonsHtml += `<span style="padding:0 4px;">...</span>`;
        continue;
      }

      const isActive = p === currentPage;
      buttonsHtml += `<button type="button" class="btn btn-sm ${isActive ? 'btn-primary' : 'btn-secondary'} pag-page" data-page="${p}" style="padding:3px 9px; font-size:0.8rem; ${isActive ? 'font-weight:bold;' : ''}">${p}</button>`;
    }

    // Next Button
    buttonsHtml += `<button type="button" class="btn btn-sm btn-secondary pag-next" ${currentPage >= totalPages ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''} style="padding:3px 8px; font-size:0.8rem;">Next</button></div>`;

    footer.innerHTML = `
      <div style="font-weight:500;">Showing ${showingFrom} to ${showingTo} of ${total} entries</div>
      ${buttonsHtml}
    `;

    // Attach event listeners
    const prevBtn = footer.querySelector('.pag-prev');
    if (prevBtn && currentPage > 1) {
      prevBtn.addEventListener('click', () => {
        paginationState.currentPage--;
        updateTableState(table, paginationState.controls, paginationState);
      });
    }

    const nextBtn = footer.querySelector('.pag-next');
    if (nextBtn && currentPage < totalPages) {
      nextBtn.addEventListener('click', () => {
        paginationState.currentPage++;
        updateTableState(table, paginationState.controls, paginationState);
      });
    }

    footer.querySelectorAll('.pag-page').forEach(btn => {
      btn.addEventListener('click', () => {
        paginationState.currentPage = parseInt(btn.getAttribute('data-page'), 10);
        updateTableState(table, paginationState.controls, paginationState);
      });
    });
  }

  function hasExistingFilterBar(table) {
    const card = table.closest('.card');
    return card ? !!card.querySelector('.filter-bar') : false;
  }

  function attachFilterBar(table) {
    if (!table.tHead || !table.tBodies.length) return;
    if (table.dataset.filterable === 'false') return;
    if (hasExistingFilterBar(table)) return;
    if (table.dataset.filterAttached === 'true') return;

    const headerCells = Array.from(table.querySelectorAll('thead th'));
    if (!headerCells.length) return;

    const rows = Array.from(table.tBodies[0].querySelectorAll('tr'));
    const controls = [];
    const filterBar = document.createElement('div');
    filterBar.className = 'filter-bar flex-between';
    filterBar.style.flexWrap = 'wrap';
    filterBar.style.gap = '0.75rem';
    filterBar.style.marginBottom = '1rem';
    filterBar.style.padding = '0.75rem';
    filterBar.style.background = '#fdfbf7';
    filterBar.style.borderRadius = '0.75rem';
    filterBar.style.border = '1px solid #DDCFAF';
    filterBar.style.justifyContent = 'flex-end';

    const pageSizeSelect = createPageSizeSelect();
    filterBar.appendChild(pageSizeSelect);

    const paginationState = {
      currentPage: 1,
      pageSizeSelect: pageSizeSelect,
      controls: controls,
      footerElement: null
    };

    controls.forEach((control) => {
      control.element.addEventListener('input', () => {
        paginationState.currentPage = 1;
        updateTableState(table, controls, paginationState);
      });
      control.element.addEventListener('change', () => {
        paginationState.currentPage = 1;
        updateTableState(table, controls, paginationState);
      });
    });

    resetButton.addEventListener('click', () => {
      controls.forEach((control) => { control.element.value = ''; });
      paginationState.currentPage = 1;
      updateTableState(table, controls, paginationState);
    });

    pageSizeSelect.addEventListener('change', () => {
      paginationState.currentPage = 1;
      updateTableState(table, controls, paginationState);
    });

    const targetContainerSelector = table.dataset.filterContainer;
    let targetContainer = null;
    if (targetContainerSelector) {
        targetContainer = document.querySelector(targetContainerSelector);
    }
    
    if (targetContainer) {
        targetContainer.appendChild(filterBar);
    } else {
        const targetWrapper = table.closest('.table-container') || table;
        targetWrapper.parentNode.insertBefore(filterBar, targetWrapper);
    }
    table.dataset.filterAttached = 'true';

    // Initial state update & rendering
    updateTableState(table, controls, paginationState);
  }

  function scanTables(root = document) {
    const tables = Array.from(root.querySelectorAll('table:not([data-tabulator="true"])'));
    tables.forEach((table) => {
      attachFilterBar(table);
    });
  }

  function observeMutation() {
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType !== Node.ELEMENT_NODE) return;
          if (node.matches('table')) {
            scanTables(node.parentNode || node);
          } else if (node.querySelectorAll) {
            scanTables(node);
          }
        });
      });
    });

    observer.observe(document.documentElement || document.body, {
      childList: true,
      subtree: true,
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    scanTables();
    observeMutation();
  });
})();
