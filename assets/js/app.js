(function() {
  var toggle = document.getElementById('menu-toggle');
  var menu = document.getElementById('mobile-menu');
  if (toggle && menu) {
    toggle.addEventListener('click', function() {
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', !expanded);
      menu.classList.toggle('hidden');
    });
    menu.querySelectorAll('a').forEach(function(link) {
      link.addEventListener('click', function() {
        menu.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');
      });
    });
  }
  var form = document.getElementById('contact-form');
  if (form) {
    var jsField = document.getElementById('form-js');
    var tsField = document.getElementById('form-ts');
    form.addEventListener('submit', function() {
      jsField.value = btoa(navigator.languages ? navigator.languages.join(',') : '0');
      tsField.value = Math.round(Date.now() / 1000).toString();
    });
  }
  document.querySelectorAll('[data-dismiss]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var idx = btn.getAttribute('data-dismiss');
      var item = document.querySelector('[data-notice="' + idx + '"]');
      if (item) { item.style.display = 'none'; try { localStorage.setItem('waves_notice_' + idx, 'dismissed'); } catch(e) {} }
      var banner = document.getElementById('notice-banner');
      if (banner && !banner.querySelector('.notice-item:not([style*="display: none"])')) banner.style.display = 'none';
    });
  });
  try {
    document.querySelectorAll('.notice-item').forEach(function(item) {
      if (localStorage.getItem('waves_notice_' + item.getAttribute('data-notice')) === 'dismissed') item.style.display = 'none';
    });
    var banner = document.getElementById('notice-banner');
    if (banner && !banner.querySelector('.notice-item:not([style*="display: none"])')) banner.style.display = 'none';
  } catch(e) {}
  var modal = document.getElementById('demo-modal');
  var okBtn = document.getElementById('demo-modal-ok');
  var closeBtn = modal && modal.querySelector('.modal-close');
  var dismissCb = document.getElementById('demo-modal-dismiss');
  if (modal) {
    try {
      if (localStorage.getItem('waves_demo_dismissed') === '1') {
        modal.style.display = 'none';
      }
    } catch(e) {}
    function closeModal() {
      if (dismissCb && dismissCb.checked) {
        try { localStorage.setItem('waves_demo_dismissed', '1'); } catch(e) {}
      }
      modal.style.display = 'none';
      document.body.style.overflow = '';
    }
    if (okBtn) okBtn.addEventListener('click', closeModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
  }
  var fadeEls = document.querySelectorAll('.fade-in');
  if (fadeEls.length) {
    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });
    fadeEls.forEach(function(el) { observer.observe(el); });
  }
})();