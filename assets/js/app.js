(function () {
  /* ─── Mobile menu toggle ─── */
  var toggle = document.getElementById('menu-toggle');
  var menu = document.getElementById('mobile-menu');
  if (toggle && menu) {
    toggle.addEventListener('click', function () {
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', !expanded);
      menu.classList.toggle('hidden');
    });
    menu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        menu.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  /* ─── Active nav link on scroll (menu jump nav) ─── */
  var jumpLinks = document.querySelectorAll('.menu-jump-link');
  var sections = document.querySelectorAll('.menu-section[id]');
  if (jumpLinks.length && sections.length) {
    var headerHeight =
      parseInt(
        getComputedStyle(document.documentElement).getPropertyValue('--site-header-height')
      ) || 76;
    var jumpNavHeight = 46;
    var offset = headerHeight + jumpNavHeight + 20;

    function updateActiveLink() {
      var scrollY = window.scrollY;
      var activeId = null;
      sections.forEach(function (sec) {
        var top = sec.getBoundingClientRect().top + scrollY;
        if (scrollY >= top - offset) {
          activeId = sec.id;
        }
      });
      jumpLinks.forEach(function (link) {
        var href = link.getAttribute('href');
        if (href && '#' + activeId === href) {
          link.setAttribute('aria-current', 'true');
          link.style.borderBottomColor = 'rgba(200, 216, 107, 0.7)';
        } else {
          link.removeAttribute('aria-current');
          link.style.borderBottomColor = 'transparent';
        }
      });
    }

    var scrollTicking = false;
    window.addEventListener(
      'scroll',
      function () {
        if (!scrollTicking) {
          requestAnimationFrame(function () {
            updateActiveLink();
            scrollTicking = false;
          });
          scrollTicking = true;
        }
      },
      { passive: true }
    );
    updateActiveLink();
  }

  /* ─── Contact form ─── */
  var form = document.getElementById('contact-form');
  if (form) {
    var jsField = document.getElementById('form-js');
    var tsField = document.getElementById('form-ts');
    form.addEventListener('submit', function () {
      jsField.value = btoa(navigator.languages ? navigator.languages.join(',') : '0');
      tsField.value = Math.round(Date.now() / 1000).toString();
    });
  }

  /* ─── Notice dismissal ─── */
  document.querySelectorAll('[data-dismiss]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var idx = btn.getAttribute('data-dismiss');
      var item = document.querySelector('[data-notice="' + idx + '"]');
      if (item) {
        item.style.display = 'none';
        try {
          localStorage.setItem('waves_notice_' + idx, 'dismissed');
        } catch (e) {}
      }
      var banner = document.getElementById('notice-banner');
      if (banner && !banner.querySelector('.notice-item:not([style*="display: none"])'))
        banner.style.display = 'none';
    });
  });
  try {
    document.querySelectorAll('.notice-item').forEach(function (item) {
      if (localStorage.getItem('waves_notice_' + item.getAttribute('data-notice')) === 'dismissed')
        item.style.display = 'none';
    });
    var banner = document.getElementById('notice-banner');
    if (banner && !banner.querySelector('.notice-item:not([style*="display: none"])'))
      banner.style.display = 'none';
  } catch (e) {}

  /* ─── Fade-in on scroll ─── */
  var fadeEls = document.querySelectorAll('.fade-in');
  if (fadeEls.length) {
    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15 }
    );
    fadeEls.forEach(function (el) {
      observer.observe(el);
    });
  }

  /* ─── Demo disclaimer dismissal ─── */
  var demoDisclaimer = document.getElementById('demo-disclaimer');
  if (demoDisclaimer) {
    var demoClose = demoDisclaimer.querySelector('.demo-disclaimer__close');
    var hideDemoDisclaimer = function () {
      demoDisclaimer.style.display = 'none';
    };
    try {
      if (localStorage.getItem('waves_demo_disclaimer') === 'dismissed') {
        hideDemoDisclaimer();
      }
    } catch (e) {}
    if (demoClose) {
      demoClose.addEventListener('click', function () {
        hideDemoDisclaimer();
        try {
          localStorage.setItem('waves_demo_disclaimer', 'dismissed');
        } catch (e) {}
      });
    }
  }
})();
