(function () {
  const initializedTables = new WeakMap();
  const defaultTabulatorOptions = {
    layout: 'fitColumns',
    movableColumns: true,
    resizableColumns: true,
    responsiveLayout: false,
    headerSort: true,
    pagination: false,
    renderHorizontal: false,
    renderVertical: false,
    columnHeaderVertAlign: 'middle',
    headerSortElement: 'header',
    columnDefaults: {
      formatter: 'html',
      hozAlign: 'left',
      headerSort: true,
      resizable: true,
      minWidth: 40,
    },
    placeholder: '',
  };

  function generateTableId(table) {
    if (table.id) return table.id;
    let id;
    do {
      id = 'tbl-' + Math.random().toString(36).slice(2, 10);
    } while (document.getElementById(id));
    table.id = id;
    return id;
  }

  function isEligibleTable(table) {
    if (!(table instanceof HTMLTableElement)) return false;
    if (table.closest('.tabulator')) return false;
    if (table.dataset.tabulator !== 'true') return false;
    if (!table.tHead || !table.tHead.querySelectorAll('th').length) return false;
    if (table.__tabulatorInitializationFailed) return false;
    return true;
  }

  function normalizeWrapper(table) {
    const wrapper = table.closest('.table-container');
    if (wrapper) {
      wrapper.style.overflowX = wrapper.style.overflowX || 'auto';
      wrapper.style.minWidth = wrapper.style.minWidth || '0';
    }
  }

  function buildOptions(table) {
    const options = Object.assign({}, defaultTabulatorOptions);
    if (table.dataset.pagination === 'true') {
      options.pagination = 'local';
      options.paginationSize = parseInt(table.dataset.pageSize, 10) || 20;
      options.paginationSizeSelector = false;
      options.paginationCounter = false;
    }

    if (table.dataset.minHeight) {
      options.minHeight = table.dataset.minHeight;
    }

    options.columnDefaults = Object.assign({}, defaultTabulatorOptions.columnDefaults);
    return options;
  }

  function initializeTabulator(table) {
    if (!isEligibleTable(table)) return;
    if (initializedTables.has(table)) return;
    if (typeof Tabulator === 'undefined') {
      console.warn('[Tabulator] library not loaded. Skipping table:', table);
      return;
    }

    normalizeWrapper(table);
    const options = buildOptions(table);

    try {
      const instance = new Tabulator(table, options);
      initializedTables.set(table, instance);
      table.dataset.tabulator = 'true';
      if (table.closest('.table-container')) {
        table.closest('.table-container').classList.add('tabulator-ready');
      }
    } catch (error) {
      console.warn('[Tabulator] initialization failed for table', table, error);
      table.__tabulatorInitializationFailed = true;
    }
  }

  function scanTables(root = document) {
    const tables = Array.from(root.querySelectorAll('table[data-tabulator="true"]'));
    tables.forEach((table) => {
      initializeTabulator(table);
    });
  }

  function observeMutation() {
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType !== Node.ELEMENT_NODE) return;
          if (node.matches && node.matches('table')) {
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
    if (typeof Tabulator === 'undefined') {
      console.warn('[Tabulator] not available yet. Waiting for script.');
      return;
    }

    scanTables();
    observeMutation();

    window.TabulatorTableManager = {
      init: scanTables,
      getInstances: () => Array.from(initializedTables.values()),
    };
  }

  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(init, 50);
  });

  window.addEventListener('load', () => {
    setTimeout(init, 200);
  });
})();
