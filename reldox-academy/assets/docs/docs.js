/* docs.js — RelDoxAcademy · pagina «Documentazione» in wp-admin · 0.1.04A · Formazione F016 · 19/11/2026
   (BUG-2: era chiamato dal codice e assente dal pacchetto). Un capitolo alla volta, indice filtrabile,
   l indirizzo (#capitolo) apre il capitolo giusto. Nessuna finestra del browser (SPEC-GRAFICA G8).
   Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale. */
(function () {
  'use strict';
  var T = window.RdacademyDocsI18n || {};
  function init() {
    var root = document.querySelector('.rdacademy-docs'); if (!root) return;
    var link = [].slice.call(root.querySelectorAll('.rdacademy-docs-toc-link'));
    var sez = [].slice.call(root.querySelectorAll('.rdacademy-docs-section'));
    function apri(slug, fuoco) {
      var ok = sez.some(function (s) { return s.getAttribute('data-section-slug') === slug; });
      if (!ok) return;
      sez.forEach(function (s) { var si = s.getAttribute('data-section-slug') === slug; s.hidden = !si; s.classList.toggle('is-active', si); });
      link.forEach(function (a) { if (a.getAttribute('data-target') === slug) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current'); });
      if (fuoco) { var m = document.getElementById('rdacademy-docs-main'); if (m) m.focus({ preventScroll: true }); }
    }
    link.forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault(); var slug = a.getAttribute('data-target');
        apri(slug, true); if (history.replaceState) history.replaceState(null, '', '#' + slug);
      });
    });
    var h = decodeURIComponent((location.hash || '').slice(1)); if (h) apri(h, false);
    var wrap = document.getElementById('rdacademy-docs-toc-search-wrap');
    if (wrap && window.RdacademySearch) {
      window.RdacademySearch.Input({
        monta: wrap, placeholder: T.tocFilterPlaceholder, vuoto: T.tocFilterEmpty,
        voci: function () { return [].slice.call(root.querySelectorAll('.rdacademy-docs-toc-item')); }
      });
    } else if (wrap) {
      var p = document.createElement('p'); p.className = 'rdacademy-search-vuoto'; p.textContent = T.tocFilterMissing || ''; wrap.appendChild(p);
    }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
