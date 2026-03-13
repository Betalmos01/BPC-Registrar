(() => {
  const searchInput = document.querySelector('.search input');
  const tables = document.querySelectorAll('table');

  if (searchInput && tables.length) {
    let searchTimer;
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = window.setTimeout(() => {
        const query = searchInput.value.toLowerCase();
        tables.forEach((table) => {
          const rows = table.querySelectorAll('tbody tr');
          rows.forEach((row) => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
          });
        });
      }, 50);
    });
  }

  const profileMenu = document.querySelector('.profile-menu');
  if (profileMenu) {
    const toggle = profileMenu.querySelector('.profile-trigger');
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

  const notifMenu = document.querySelector('.notif-menu');
  if (notifMenu) {
    const trigger = notifMenu.querySelector('.notif-trigger');
    const dropdown = notifMenu.querySelector('.notif-dropdown');
    const markAll = notifMenu.querySelector('.js-notif-markall');
    const baseUrl = window.APP_BASE_URL || '';

    const closeMenu = () => {
      notifMenu.classList.remove('open');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
      if (dropdown) dropdown.setAttribute('aria-hidden', 'true');
    };

    const openMenu = () => {
      notifMenu.classList.add('open');
      if (trigger) trigger.setAttribute('aria-expanded', 'true');
      if (dropdown) dropdown.setAttribute('aria-hidden', 'false');
    };

    if (trigger) {
      trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        if (notifMenu.classList.contains('open')) {
          closeMenu();
        } else {
          openMenu();
        }
      });
    }

    if (dropdown) {
      dropdown.addEventListener('click', async (event) => {
        const item = event.target.closest('.notif-item');
        if (item && item.dataset.unread === '1' && window.RegistrarApi) {
          try {
            await window.RegistrarApi.post(`${baseUrl}/api/notifications.php`, { action: 'mark_read', id: item.dataset.id });
            item.dataset.unread = '0';
            item.classList.remove('unread');
          } catch (e) {
            // Non-blocking UX.
          }
        }
      });
    }

    if (markAll) {
      markAll.addEventListener('click', async () => {
        if (!window.RegistrarApi) return;
        try {
          markAll.disabled = true;
          await window.RegistrarApi.post(`${baseUrl}/api/notifications.php`, { action: 'mark_all_read' });
          dropdown?.querySelectorAll('.notif-item.unread').forEach((el) => {
            el.classList.remove('unread');
            el.dataset.unread = '0';
          });
          const badge = notifMenu.querySelector('.notif-badge');
          if (badge) badge.remove();
        } catch (e) {
          markAll.disabled = false;
        }
      });
    }

    document.addEventListener('click', (event) => {
      if (!notifMenu.contains(event.target)) {
        closeMenu();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeMenu();
      }
    });
  }

  const modal = document.getElementById('app-modal');
  if (modal) {
    const title = modal.querySelector('.modal-title');
    const body = modal.querySelector('.modal-body');
    const submit = modal.querySelector('.modal-submit');

    let submitHandler = null;

    const closeModal = () => {
      document.body.classList.remove('modal-open');
      modal.setAttribute('aria-hidden', 'true');
      if (title) title.textContent = '';
      if (body) body.innerHTML = '';
      if (submit) {
        submit.textContent = 'Save';
        submit.classList.remove('danger', 'primary');
        submit.classList.add('primary');
        submit.disabled = false;
      }
      submitHandler = null;
    };

    const openModal = (opts) => {
      const options = opts || {};
      document.body.classList.add('modal-open');
      modal.setAttribute('aria-hidden', 'false');

      if (title) title.textContent = options.title || '';
      if (body) body.innerHTML = options.body || '';

      if (submit) {
        submit.textContent = options.submitText || 'Save';
        submit.classList.remove('danger', 'primary', 'secondary');
        const cls = (options.submitClass || 'primary').split(/\s+/).filter(Boolean);
        if (cls.length) submit.classList.add(...cls);
        submit.disabled = false;
      }

      submitHandler = typeof options.onSubmit === 'function' ? options.onSubmit : null;
    };

    modal.addEventListener('click', (event) => {
      const close = event.target.closest('[data-modal-close]');
      if (close) {
        closeModal();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && document.body.classList.contains('modal-open')) {
        closeModal();
      }
    });

    if (submit) {
      submit.addEventListener('click', async () => {
        if (submitHandler) {
          await submitHandler({ modal, close: closeModal, submit, body });
          return;
        }

        const form = modal.querySelector('form');
        if (form) {
          form.requestSubmit();
        }
      });
    }

    window.RegistrarModal = { open: openModal, close: closeModal };
  }

  window.RegistrarApi = {
    post: async (url, formDataOrObject) => {
      const body =
        formDataOrObject instanceof FormData
          ? formDataOrObject
          : new URLSearchParams(formDataOrObject || {});

      const response = await fetch(url, {
        method: 'POST',
        headers: { Accept: 'application/json' },
        body,
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok || payload.ok === false) {
        const message = payload.error || payload.message || 'Request failed.';
        throw new Error(message);
      }
      return payload;
    },
  };
})();

