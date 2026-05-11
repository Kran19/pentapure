(function () {
  const DATE_HEADER_PATTERN = /(date|created|updated|time|at)/i;
  const NUMBER_HEADER_PATTERN = /(qty|quantity|amount|price|cost|total|kg|#|no|count)/i;
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
    input.style.background = 'var(--glass-bg)';
    input.style.color = 'white';
    input.style.border = '1px solid var(--glass-border)';
    input.style.padding = '5px 10px';
    input.style.minWidth = '120px';
    return input;
  }

  function createSelect(name, options) {
    const select = document.createElement('select');
    select.name = name;
    select.className = 'btn-sm filter-input';
    select.style.background = 'var(--glass-bg)';
    select.style.color = 'white';
    select.style.border = '1px solid var(--glass-border)';
    select.style.padding = '5px 10px';
    select.style.minWidth = '120px';

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

  function applyFilters(table, controls) {
    const rows = Array.from(table.tBodies[0].querySelectorAll('tr'));
    rows.forEach((row) => {
      const visible = controls.every((control) => {
        if (!control.element.value) return true;
        const cell = row.children[control.index];
        return matchesFilter(cell ? cell.textContent : '', control.element.value, control.type);
      });
      row.style.display = visible ? '' : 'none';
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
    filterBar.className = 'filter-bar';
    filterBar.style.flexWrap = 'wrap';
    filterBar.style.gap = '0.75rem';
    filterBar.style.marginBottom = '1rem';
    filterBar.style.padding = '0.75rem';
    filterBar.style.background = 'rgba(0,0,0,0.15)';
    filterBar.style.borderRadius = '0.75rem';

    headerCells.forEach((th, index) => {
      const label = normalizeText(th.textContent) || `Column ${index + 1}`;
      const values = rows.map((row) => normalizeText((row.children[index] || {}).textContent));
      const unique = uniqueValues(values).sort((a, b) => a.localeCompare(b));
      const isDate = DATE_HEADER_PATTERN.test(label) || values.every((val) => DATE_VALUE_PATTERN.test(val));
      const isNumber = NUMBER_HEADER_PATTERN.test(label) && values.every((val) => val === '' || !Number.isNaN(Number(val.replace(/,/g, ''))));

      let controlElement;
      let controlType = 'text';

      if (isDate) {
        controlElement = createInput('date', label, label);
        controlType = 'date';
      } else if (unique.length > 1 && unique.length <= 10 && !isNumber) {
        controlElement = createSelect(label, unique);
        controlType = 'text';
      } else {
        controlElement = createInput('text', label, label);
      }

      controlElement.addEventListener('input', () => applyFilters(table, controls));
      controlElement.addEventListener('change', () => applyFilters(table, controls));

      controls.push({ index, element: controlElement, type: controlType });
      filterBar.appendChild(controlElement);
    });

    const resetButton = createResetButton();
    resetButton.addEventListener('click', () => {
      controls.forEach((control) => {
        control.element.value = '';
      });
      applyFilters(table, controls);
    });
    filterBar.appendChild(resetButton);

    table.parentNode.insertBefore(filterBar, table);
    table.dataset.filterAttached = 'true';
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
