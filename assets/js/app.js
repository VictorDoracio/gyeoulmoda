// assets/js/app.js — utilidades pequenas de UI.
(function () {
  'use strict';

  // Confirmação em qualquer link/botão com [data-confirm]
  document.addEventListener('click', function (ev) {
    var el = ev.target.closest('[data-confirm]');
    if (!el) return;
    var msg = el.getAttribute('data-confirm') || 'Tem certeza?';
    if (!window.confirm(msg)) {
      ev.preventDefault();
      ev.stopPropagation();
    }
  });

  // Marca o link ativo na navbar conforme a URL atual
  var path = window.location.pathname.replace(/\/+$/, '') || '/';
  document.querySelectorAll('.navbar .nav-link').forEach(function (a) {
    var href = a.getAttribute('href');
    if (!href) return;
    try {
      var u = new URL(href, window.location.origin);
      var p = u.pathname.replace(/\/+$/, '') || '/';
      if (p === path && !a.classList.contains('dropdown-toggle')) {
        a.classList.add('active');
      }
    } catch (_) { /* href inválido: ignora */ }
  });

  // Envio único em formulários marcados com data-single-submit
  document.querySelectorAll('form[data-single-submit]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (form.dataset.submitted === '1') {
        ev.preventDefault();
        return;
      }
      form.dataset.submitted = '1';
      var btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Processando…';
      }
    });
  });
})();