/* RelDoxAcademy — «METTI L'ICONA SUL TELEFONO».
 *  - Registra il service worker minimo (/food/sw.js): senza, Chrome per Android non offre «Installa».
 *  - Android/Chrome: se il telefono offre l'installazione (beforeinstallprompt) il tocco apre direttamente la finestra del
 *    sistema; altrimenti si apre il riquadro con la spiegazione giusta per il telefono (iPhone in Safari, iPhone in un altro
 *    browser, Android, computer).
 *  - App già aperta dall'icona (display-mode standalone, navigator.standalone): la riga non c'è.
 *  - Con data-solo-installa: il pulsante compare solo quando l'installazione diretta è possibile.
 *  - /food/#icona (voce «Icona sul telefono» del menu) apre il riquadro.
 * Nessuna dipendenza. */
(function () {
    'use strict';
    var me = document.currentScript;
    var box = document.getElementById('rdacademy-icona');
    var installata = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
    if ('serviceWorker' in navigator && me && me.getAttribute('data-sw')) {
        try { navigator.serviceWorker.register(me.getAttribute('data-sw'), { scope: me.getAttribute('data-scope') }).catch(function () {}); } catch (e) {}
    }
    if (!box) return;
    if (installata) { box.remove(); return; }
    var btn = document.getElementById('rdacademy-icona-btn'), pan = document.getElementById('rdacademy-icona-box');
    var x = document.getElementById('rdacademy-icona-x'), esito = document.getElementById('rdacademy-icona-esito');
    var solo = box.getAttribute('data-solo-installa') === '1';
    var invito = null;
    var ua = navigator.userAgent || '';
    var ios = /iPhone|iPad|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var dove = ios ? (/CriOS|FxiOS|EdgiOS|OPiOS|GSA\//.test(ua) ? 'ios-altro' : 'ios') : (/Android/.test(ua) ? 'android' : 'pc');
    /* Dal computer l'icona non si installa e il pulsante confonde → non c'è, nemmeno il
       «Installa» della Guida (Chrome sul computer può offrire l'installazione, ma non è l'icona sul telefono). */
    if (dove === 'pc') { box.remove(); var mv = document.getElementById('rdacademyMIcona'); if (mv) mv.remove(); window.rdacademyIcona = { dove: dove, haInvito: function () { return false; } }; return; }
    if (pan) pan.setAttribute('data-dove', dove);
    box.setAttribute('data-dove', dove);

    function dici(t) { if (esito) esito.textContent = t || ''; }
    function apri() { if (!pan) return; pan.hidden = false; btn.setAttribute('aria-expanded', 'true'); }
    function chiudi() { if (!pan) return; pan.hidden = true; btn.setAttribute('aria-expanded', 'false'); btn.focus(); }

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault(); invito = e;
        if (solo) box.hidden = false;
    });
    window.addEventListener('appinstalled', function () { invito = null; dici(box.getAttribute('data-fatto') || ''); });

    btn.addEventListener('click', function () {
        if (invito) {
            var ev = invito; invito = null;
            ev.prompt();
            ev.userChoice.then(function (r) {
                dici(box.getAttribute(r && r.outcome === 'accepted' ? 'data-fatto' : 'data-no') || '');
                if (r && r.outcome === 'accepted' && solo) btn.hidden = true;
            }, function () {});
            return;
        }
        if (solo) return;
        if (pan && !pan.hidden) chiudi(); else apri();
    });
    if (x) x.addEventListener('click', chiudi);
    function daHash() { if (location.hash === '#icona' && !solo) { apri(); box.scrollIntoView({ block: 'center' }); } }
    window.addEventListener('hashchange', daHash);
    /* la voce del menu sulla stessa pagina: l'indirizzo non cambia, il riquadro si apre lo stesso */
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('#rdacademyMIcona');
        if (!a || solo || a.pathname !== location.pathname) return;
        e.preventDefault(); history.replaceState(null, '', '#icona'); apri(); box.scrollIntoView({ block: 'center' });
    });
    daHash();
    /* per il banco */
    window.rdacademyIcona = { dove: dove, haInvito: function () { return !!invito; } };
})();
