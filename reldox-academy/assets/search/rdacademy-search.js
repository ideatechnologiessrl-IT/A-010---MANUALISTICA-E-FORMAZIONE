/* rdacademy-search.js — RelDoxAcademy · campo di ricerca riusabile · 0.1.04A · Formazione F016 · 19/11/2026
   (BUG-2: era chiamato dal codice e assente dal pacchetto). Nessuna finestra del browser (SPEC-GRAFICA G8).
   window.RdacademySearch.Input({ monta, voci, testo, alCambio }) filtra un elenco di elementi per testo.
   Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale. */
(function () {
  'use strict';
  var T = window.RdacademySearchI18n || { placeholder: 'Cerca…', noResults: 'Nessun risultato', countOf: '%1$d di %2$d' };
  var LENTE = '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>';
  function norm(s) { return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }
  function Input(o) {
    if (!o || !o.monta) return null;
    var box = document.createElement('div'); box.className = 'rdacademy-search';
    box.innerHTML = LENTE + '<input type="search" autocomplete="off"><span class="rdacademy-search-conta" aria-live="polite"></span>';
    var inp = box.querySelector('input'), conta = box.querySelector('.rdacademy-search-conta');
    inp.placeholder = o.placeholder || T.placeholder; inp.setAttribute('aria-label', inp.placeholder);
    var vuoto = document.createElement('p'); vuoto.className = 'rdacademy-search-vuoto'; vuoto.hidden = true; vuoto.textContent = o.vuoto || T.noResults;
    o.monta.insertBefore(box, o.monta.firstChild); o.monta.appendChild(vuoto);
    function filtra() {
      var q = norm(inp.value.trim()), voci = o.voci(), n = 0;
      voci.forEach(function (el) { var si = !q || norm(o.testo ? o.testo(el) : el.textContent).indexOf(q) !== -1; el.hidden = !si; if (si) n++; });
      conta.textContent = q ? T.countOf.replace('%1$d', n).replace('%2$d', voci.length) : '';
      vuoto.hidden = !(q && n === 0);
      if (o.alCambio) o.alCambio(q, n);
    }
    inp.addEventListener('input', filtra);
    inp.addEventListener('keydown', function (e) { if (e.key === 'Escape') { inp.value = ''; filtra(); } });
    return { campo: inp, filtra: filtra };
  }
  window.RdacademySearch = { Input: Input };
})();
