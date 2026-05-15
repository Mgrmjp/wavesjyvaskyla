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

  /* ─── Organic hero wave deformation ─── */
  var wavePaths = document.querySelectorAll('.wave-divider path');
  if (wavePaths.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var waveLayers = [];
    var configs = [
      { freqs: [0.05, 0.1, 0.2], amps: [1, 0.6, 0.3], phaseMults: [0.8, 1.4, 0.3] },
      { freqs: [0.05, 0.15, 0.25], amps: [0.7, 0.5, 0.3], phaseMults: [1.1, 0.7, 1.6] },
      { freqs: [0.1, 0.15, 0.2], amps: [0.6, 0.4, 0.2], phaseMults: [0.5, 1.2, 0.9] }
    ];
    var surfIdxs = [1, 7, 13];
    var cpIdxs = [3, 5, 9, 11, 15, 17];

    wavePaths.forEach(function (el, i) {
      var nums = el.getAttribute('d').match(/[\d.]+/g).map(Number);
      var cfg = configs[i % configs.length];
      waveLayers.push({ el: el, nums: nums, cfg: cfg });
    });

    var waveT0 = performance.now();

    function offsetFor(idx, nums, freqs, amps, phaseMults, t) {
      var x = nums[idx - 1];
      var o = 0;
      for (var k = 0; k < 3; k++) {
        o += amps[k] * Math.sin(2 * Math.PI * freqs[k] * t + x * phaseMults[k] * Math.PI / 720);
      }
      return o;
    }

    function updateWaves() {
      var t = (performance.now() - waveT0) / 1000;

      waveLayers.forEach(function (l) {
        var n = l.nums.slice();
        var c = l.cfg;
        var f = c.freqs, a = c.amps, p = c.phaseMults;

        n[1] += offsetFor(1, n, f, a, p, t);
        surfIdxs.forEach(function (si) { n[si] += offsetFor(si, n, f, a, p, t); });
        n[19] = n[1];

        cpIdxs.forEach(function (ci) { n[ci] += offsetFor(ci, n, f, a, p, t); });

        var d = 'M' + n[0].toFixed(1) + ',' + n[1].toFixed(1);
        for (var j = 2; j < 20; j += 6) {
          d += 'C' + n[j].toFixed(1) + ',' + n[j + 1].toFixed(1) + ' ' +
               n[j + 2].toFixed(1) + ',' + n[j + 3].toFixed(1) + ' ' +
               n[j + 4].toFixed(1) + ',' + n[j + 5].toFixed(1);
        }
        d += 'L1440,120L0,120Z';
        l.el.setAttribute('d', d);
      });

      requestAnimationFrame(updateWaves);
    }

    updateWaves();
  }

})();
