(function () {
  'use strict';
  document.documentElement.classList.add('yl-js');

  var header = document.querySelector('[data-yl-header]');
  var toggle = document.querySelector('[data-yl-menu-toggle]');
  var nav = document.querySelector('[data-yl-nav]');
  var filterToggle = document.querySelector('[data-yl-filter-toggle]');
  var filterPanel = document.querySelector('[data-yl-filter-panel]');
  var searchToggle = document.querySelector('[data-yl-search-toggle]');
  var searchPanel = document.querySelector('[data-yl-search-panel]');

  function updateHeader() {
    if (header) header.classList.toggle('is-scrolled', window.scrollY > 12);
  }
  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });

  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Đóng menu' : 'Mở menu');
      document.documentElement.classList.toggle('yl-nav-open', open);
    });
    nav.addEventListener('click', function (event) {
      if (event.target.closest('a')) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Mở menu');
        document.documentElement.classList.remove('yl-nav-open');
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && nav.classList.contains('is-open')) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Mở menu');
        document.documentElement.classList.remove('yl-nav-open');
        toggle.focus();
      }
    });
  }

  if (searchToggle && searchPanel) {
    searchToggle.addEventListener('click', function () {
      var open = searchPanel.hasAttribute('hidden');
      if (open) {
        searchPanel.removeAttribute('hidden');
        var input = searchPanel.querySelector('input[type="search"]');
        if (input) window.setTimeout(function () { input.focus(); }, 0);
      } else {
        searchPanel.setAttribute('hidden', 'hidden');
      }
      searchToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !searchPanel.hasAttribute('hidden')) {
        searchPanel.setAttribute('hidden', 'hidden');
        searchToggle.setAttribute('aria-expanded', 'false');
        searchToggle.focus();
      }
    });
  }

  document.querySelectorAll('[data-yl-qf-form]').forEach(function (form) {
    var school = form.querySelector('input[name="qf_school"]');
    var radius = form.querySelector('select[name="qf_radius"]');
    if (!school || !radius) return;
    function syncRadius() {
      var enabled = !!school.value;
      radius.disabled = !enabled;
      radius.setAttribute('aria-disabled', enabled ? 'false' : 'true');
      if (!enabled) radius.value = '';
    }
    school.addEventListener('change', syncRadius);
    syncRadius();
  });

  if (filterToggle && filterPanel) {
    filterToggle.addEventListener('click', function () {
      var open = filterPanel.classList.toggle('is-open');
      filterToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
})();
