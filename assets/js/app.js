(() => {
  const searchInput = document.querySelector('.search input');
  const tables = document.querySelectorAll('table');

  if (searchInput && tables.length) {
    searchInput.addEventListener('input', () => {
      const query = searchInput.value.toLowerCase();
      tables.forEach((table) => {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach((row) => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(query) ? '' : 'none';
        });
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
})();
