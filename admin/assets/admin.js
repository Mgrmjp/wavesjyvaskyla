(function () {
  /* === Mobile Sidebar Toggle === */
  var menuBtn = document.getElementById('menu-toggle');
  var sidebar = document.getElementById('admin-sidebar');
  var overlay = document.getElementById('sidebar-overlay');
  if (menuBtn && sidebar && overlay) {
    function closeSidebar() {
      sidebar.classList.remove('is-open');
      overlay.classList.remove('is-visible');
    }
    menuBtn.addEventListener('click', function () {
      sidebar.classList.toggle('is-open');
      overlay.classList.toggle('is-visible');
    });
    overlay.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeSidebar();
    });
  }

  /* === Dirty State Tracking === */
  var forms = document.querySelectorAll('form');
  forms.forEach(function (form) {
    var saveBar = form.querySelector('.admin-sticky-save');
    if (!saveBar) return;
    var info = saveBar.querySelector('.admin-sticky-save__info');
    var initialData = new FormData(form);

    function checkDirty() {
      var current = new FormData(form);
      var dirty = false;
      for (var key of initialData.keys()) {
        if (initialData.get(key) !== current.get(key)) {
          dirty = true;
          break;
        }
      }
      if (!dirty) {
        for (var key of current.keys()) {
          if (!initialData.has(key) || initialData.get(key) !== current.get(key)) {
            dirty = true;
            break;
          }
        }
      }
      saveBar.classList.toggle('admin-sticky-save--dirty', dirty);
      if (info) {
        if (dirty) {
          if (!info.dataset.originalText) info.dataset.originalText = info.textContent;
          info.textContent = 'Tallentamattomia muutoksia';
        } else if (info.dataset.originalText) {
          info.textContent = info.dataset.originalText;
        }
      }
    }

    form.querySelectorAll('input, textarea, select').forEach(function (el) {
      el.addEventListener('change', checkDirty);
      el.addEventListener('input', checkDirty);
    });

    form.addEventListener('submit', function () {
      saveBar.classList.remove('admin-sticky-save--dirty');
      if (info && info.dataset.originalText) info.textContent = info.dataset.originalText;
    });
  });

  /* === Shared Editor Logic === */
  function updateSortState(container) {
    var items = Array.from(container.querySelectorAll('[data-sort-item]'));
    items.forEach(function (item, index) {
      var order = item.querySelector('[data-sort-index]');
      if (order) order.textContent = String(index + 1);
      var up = item.querySelector('[data-move="up"]');
      var down = item.querySelector('[data-move="down"]');
      if (up) up.disabled = index === 0;
      if (down) down.disabled = index === items.length - 1;
    });
  }

  function initToggle() {
    document.querySelectorAll('[data-toggle-details]').forEach(function (button) {
      button.addEventListener('click', function () {
        var item = button.closest('[data-sort-item]');
        var details = item.querySelector('.editor-list-item__details');
        var willOpen = details.hasAttribute('hidden');
        item.parentElement.querySelectorAll('.editor-list-item__details').forEach(function (panel) {
          panel.setAttribute('hidden', '');
        });
        item.parentElement.querySelectorAll('[data-toggle-details]').forEach(function (toggle) {
          toggle.setAttribute('aria-expanded', 'false');
          if (toggle.textContent.trim() === 'Sulje') toggle.textContent = 'Muokkaa';
        });
        if (willOpen) {
          details.removeAttribute('hidden');
          button.setAttribute('aria-expanded', 'true');
          button.textContent = 'Sulje';
        }
      });
    });
  }

  function initReorder() {
    document.querySelectorAll('[data-move]').forEach(function (button) {
      button.addEventListener('click', function () {
        var item = button.closest('[data-sort-item]');
        var container = item.parentElement;
        var direction = button.dataset.move;
        var sibling = direction === 'up' ? item.previousElementSibling : item.nextElementSibling;
        if (!sibling) return;
        if (direction === 'up') {
          container.insertBefore(item, sibling);
        } else {
          container.insertBefore(sibling, item);
        }
        updateSortState(container);
      });
    });
  }

  function initSortState() {
    document.querySelectorAll('.editor-items-stack').forEach(function (container) {
      updateSortState(container);
    });
  }

  function initDragAndDrop() {
    var containers = document.querySelectorAll('.editor-items-stack');
    containers.forEach(function (container) {
      var draggedItem = null;
      container.querySelectorAll('[data-sort-item]').forEach(function (item) {
        var handle = item.querySelector('.editor-drag-handle');
        if (!handle) return;
        handle.addEventListener('mousedown', function () { item.setAttribute('draggable', 'true'); });
        handle.addEventListener('mouseup', function () { item.setAttribute('draggable', 'false'); });
        item.addEventListener('dragstart', function (e) {
          draggedItem = item;
          item.classList.add('dragging');
          e.dataTransfer.effectAllowed = 'move';
          e.dataTransfer.setData('text/plain', '');
        });
        item.addEventListener('dragend', function () {
          item.classList.remove('dragging');
          item.setAttribute('draggable', 'false');
          draggedItem = null;
          container.querySelectorAll('.editor-list-item').forEach(function (i) { i.classList.remove('drag-over'); });
          updateSortState(container);
          var newCategorySlug = item.closest('[data-category-slug]')?.dataset.categorySlug;
          if (newCategorySlug) {
            var catSelect = item.querySelector('select[name^="item_category"]');
            if (catSelect && catSelect.value !== newCategorySlug) {
              catSelect.value = newCategorySlug;
              catSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
          }
        });
        item.addEventListener('dragover', function (e) {
          e.preventDefault();
          e.dataTransfer.dropEffect = 'move';
          if (item !== draggedItem) item.classList.add('drag-over');
        });
        item.addEventListener('dragleave', function () { item.classList.remove('drag-over'); });
        item.addEventListener('drop', function (e) {
          e.preventDefault();
          item.classList.remove('drag-over');
          if (!draggedItem || draggedItem === item) return;
          var rect = item.getBoundingClientRect();
          var midpoint = rect.top + rect.height / 2;
          if (e.clientY < midpoint) {
            container.insertBefore(draggedItem, item);
          } else {
            container.insertBefore(draggedItem, item.nextElementSibling);
          }
        });
      });
    });
  }

  function initSocialRows() {
    var container = document.getElementById('social-links');
    if (!container) return;
    var template = container.querySelector('.settings-social-row[data-template]');
    if (!template) return;
    var addBtn = document.getElementById('add-social-link');
    if (!addBtn) return;
    addBtn.addEventListener('click', function () {
      var clone = template.cloneNode(true);
      clone.removeAttribute('data-template');
      clone.removeAttribute('hidden');
      clone.querySelectorAll('input, select').forEach(function (el) { el.value = ''; el.selectedIndex = 0; });
      container.appendChild(clone);
      bindRemoveButtons();
    });
    function bindRemoveButtons() {
      container.querySelectorAll('.social-remove-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var row = btn.closest('.settings-social-row');
          if (row && !row.hasAttribute('data-template')) row.remove();
        });
      });
    }
    bindRemoveButtons();
  }

  function initFileUploads() {
    document.querySelectorAll('.admin-dropzone').forEach(function (zone) {
      var input = zone.querySelector('input[type="file"]');
      var preview = zone.querySelector('.admin-dropzone__preview');
      if (!input) return;
      zone.addEventListener('click', function (e) { if (e.target !== input) input.click(); });
      zone.addEventListener('dragover', function (e) { e.preventDefault(); zone.classList.add('drag-active'); });
      zone.addEventListener('dragleave', function () { zone.classList.remove('drag-active'); });
      zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('drag-active');
        if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; updatePreview(input.files[0]); }
      });
      input.addEventListener('change', function () { if (input.files.length) updatePreview(input.files[0]); });
      function updatePreview(file) {
        if (!preview) return;
        var reader = new FileReader();
        reader.onload = function (e) {
          var isImage = file.type.startsWith('image/');
          preview.innerHTML = (isImage ? '<img src="' + e.target.result + '" alt="">' : '') + '<span class="admin-dropzone__preview-name">' + escapeHtml(file.name) + '</span>';
          preview.style.display = 'flex';
        };
        reader.readAsDataURL(file);
      }
    });
  }

  function escapeHtml(text) { var d = document.createElement('div'); d.textContent = text; return d.innerHTML; }

  function initPasswordValidation() {
    var form = document.querySelector('.password-section form') || document.querySelector('form');
    if (!form) return;
    var pw1 = form.querySelector('input[name="admin_password"]');
    var pw2 = form.querySelector('input[name="admin_password_confirm"]');
    var error = form.querySelector('.password-mismatch');
    if (!pw1 || !pw2) return;
    function checkMatch() {
      if (pw2.value && pw1.value !== pw2.value) {
        error.textContent = 'Salasanat eivät täsmää';
        error.style.display = 'block';
        pw2.style.borderColor = 'var(--admin-danger)';
      } else {
        error.style.display = 'none';
        pw2.style.borderColor = '';
      }
    }
    pw1.addEventListener('input', checkMatch);
    pw2.addEventListener('input', checkMatch);
    form.addEventListener('submit', function (e) {
      if (pw1.value !== pw2.value) { e.preventDefault(); checkMatch(); pw2.focus(); }
    });
  }

  window.WavesAdmin = { updateSortState: updateSortState };

  document.addEventListener('DOMContentLoaded', function () {
    initToggle();
    initReorder();
    initSortState();
    initDragAndDrop();
    initSocialRows();
    initFileUploads();
    initPasswordValidation();

    /* === Lunch Accordion Add Buttons === */
    document.querySelectorAll('.day-card__add-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var dayCard = btn.closest('.day-card--collapsible');
        if (!dayCard) return;
        if (!dayCard.open) dayCard.open = true;
        var newEntry = dayCard.querySelector('.lunch-entry--new');
        if (newEntry) {
          newEntry.hidden = false;
          var firstInput = newEntry.querySelector('input[type="text"]');
          if (firstInput) firstInput.focus();
        }
      });
    });

    /* === Hours Closed Toggle === */
    document.querySelectorAll('.day-card input[type="checkbox"][name^="oh_closed"]').forEach(function (cb) {
      cb.closest('.day-card').querySelector('.hours-fields').classList.toggle('is-disabled', cb.checked);
    });

    var backLink = document.querySelector('.back-to-top');
    if (backLink) {
      function checkScroll() {
        backLink.classList.toggle('is-visible', window.scrollY > 600);
      }
      window.addEventListener('scroll', checkScroll, { passive: true });
      checkScroll();
    }
  });
})();
