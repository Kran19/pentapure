(function () {
  const MONTH_MAP = {
    jan: 0, feb: 1, mar: 2, apr: 3, may: 4, jun: 5,
    jul: 6, aug: 7, sep: 8, oct: 9, nov: 10, dec: 11,
  };

  function parseValue(value) {
    if (!value) return '';

    const trimmed = value.trim();
    const number = parseFloat(trimmed.replace(/,/g, ''));
    if (!Number.isNaN(number) && /[0-9]/.test(trimmed)) {
      return number;
    }

    const dateMatch = trimmed.match(/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/);
    if (dateMatch) {
      const [, day, month, year] = dateMatch;
      const monthIndex = MONTH_MAP[month.substring(0, 3).toLowerCase()] ?? 0;
      return new Date(Number(year), monthIndex, Number(day)).getTime();
    }

    return trimmed.toLowerCase();
  }

  function getCellValue(row, columnIndex) {
    const cell = row.children[columnIndex];
    return cell ? cell.textContent.trim() : '';
  }

  function sortTable(table, columnIndex, order) {
    const tbody = table.tBodies[0];
    if (!tbody) return;

    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.sort((a, b) => {
      const aValue = parseValue(getCellValue(a, columnIndex));
      const bValue = parseValue(getCellValue(b, columnIndex));

      if (aValue < bValue) return order === 'asc' ? -1 : 1;
      if (aValue > bValue) return order === 'asc' ? 1 : -1;
      return 0;
    });

    rows.forEach((row) => tbody.appendChild(row));
  }

  function attachSortableHeaders(table) {
    if (table.dataset.sortAttached === 'true') return;
    const headers = Array.from(table.querySelectorAll('thead th'));
    if (!headers.length) return;

    headers.forEach((th) => {
      th.dataset.sortOrder = 'asc';
      th.classList.add('sortable');
      th.addEventListener('click', () => {
        const order = th.dataset.sortOrder === 'asc' ? 'desc' : 'asc';
        const headerRow = th.closest('thead');
        const headerCells = Array.from(headerRow.querySelectorAll('th'));

        headerCells.forEach((cell) => {
          cell.classList.remove('sorted-asc', 'sorted-desc');
        });

        th.classList.add(order === 'asc' ? 'sorted-asc' : 'sorted-desc');
        th.dataset.sortOrder = order;

        const columnIndex = headerCells.indexOf(th);
        sortTable(table, columnIndex, order);
      });
    });

    table.dataset.sortAttached = 'true';
  }

  function scanTables(root = document) {
    const tables = Array.from(root.querySelectorAll('table:not([data-tabulator="true"])'));
    tables.forEach((table) => {
      if (table.dataset.sortable === 'false') return;
      attachSortableHeaders(table);
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

  function init() {
    scanTables();
    observeMutation();
  }

  document.addEventListener('DOMContentLoaded', init);
})();
