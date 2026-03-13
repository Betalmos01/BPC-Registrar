(() => {
  const searchInput = document.querySelector('.search input');
  const tables = document.querySelectorAll('table');

  if (searchInput && tables.length) {
    let searchTimer;\n    searchInput.addEventListener('input', () => {\n      clearTimeout(searchTimer);\n      searchTimer = window.setTimeout(() => {\n        const query = searchInput.value.toLowerCase();\n        tables.forEach((table) => {\n          const rows = table.querySelectorAll('tbody tr');\n          rows.forEach((row) => {\n            const text = row.textContent.toLowerCase();\n            row.style.display = text.includes(query) ? '' : 'none';\n          });\n        });\n      }, 50);\n    });
      });
    });
  }

  const profileMenu = document.querySelector('.profile-menu');
  if (profileMenu) {
    const toggle = profileMenu.querySelector('.avatar');
    const dropdown = profileMenu.querySelector('.profile-dropdown');

    const closeMenu = () => {
      profileMenu.classList.remove('open');
      if (toggle) toggle.setAttribute('aria-expanded', 'false');
      if (dropdown) dropdown.setAttribute('aria-hidden', 'true');
    };

    const openMenu = () => {
      profileMenu.classList.add('open');
      if (toggle) toggle.setAttribute('aria-expanded', 'true');
      if (dropdown) dropdown.setAttribute('aria-hidden', 'false');
    };

    if (toggle) {
      toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        if (profileMenu.classList.contains('open')) {
          closeMenu();
        } else {
          openMenu();
        }
      });
    }

    document.addEventListener('click', (event) => {
      if (!profileMenu.contains(event.target)) {
        closeMenu();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeMenu();
      }
    });
  }

  const metricsSection = document.querySelector('.metrics');
  if (metricsSection) {
    document.body.classList.add('is-loading');
    window.setTimeout(() => {
      document.body.classList.remove('is-loading');
    }, 500);
  }

  const sidebarToggle = document.querySelector('.sidebar-toggle');
  const sidebarOverlay = document.querySelector('[data-sidebar-close]');
  const sidebar = document.querySelector('.sidebar');

  const closeSidebar = () => {
    document.body.classList.remove('sidebar-open');
    document.body.classList.remove('sidebar-collapsed');
  };

  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      if (window.innerWidth <= 900) {
        document.body.classList.toggle('sidebar-open');
        document.body.classList.remove('sidebar-collapsed');
      } else {
        document.body.classList.toggle('sidebar-collapsed');
        document.body.classList.remove('sidebar-open');
      }
    });
  }

  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', closeSidebar);
  }

  if (sidebar) {
    sidebar.addEventListener('click', (event) => {
      const link = event.target.closest('a');
      if (link && window.innerWidth <= 900) {
        closeSidebar();
      }
    });
  }
})();

