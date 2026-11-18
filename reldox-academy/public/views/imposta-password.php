<?php
/**
 * imposta-password.php — SCENA 3 DEL CONTRATTO — /academy/imposta-password
 * Copiato da RelDox CRD 3.55.09G. Cambiano solo il marchio (rdacademy_marchio_html)
 * e il nome di prodotto, letto da RDACADEMY_PRODUCT.
 * =============================================================================
 * 3.16.195 (S256) — voce 39 `SV-CREDENZIALI-E-RECUPERO`.
 *
 * Il collegamento della mail atterra qui. IL TOKEN SI VERIFICA PRIMA DI
 * DISEGNARE IL MODULO: se non e' buono, il modulo non compare affatto.
 *
 * I CINQUE STATI del contratto, e nessun altro:
 *   A — invito (primo accesso)          «Benvenuto in <prodotto>»
 *   B — reimpostazione                  «Imposta una password nuova» + fascia blu
 *   C — regola non rispettata           pulsante spento, condizioni in rosso
 *   D — collegamento non piu' valido    UN SOLO stato per TRE cause
 *   E — fatto                           si entra direttamente
 *
 * ⛔ STATO D, UN SOLO STATO PER TRE CAUSE. Scaduto, consumato, inventato: la
 * schermata e' la stessa. Distinguerli direbbe a chi prova i token quale dei
 * tre ha sbagliato. `RP-LA-PORTA-NON-DICE-CHI-C-E-DENTRO`.
 *
 * ⛔ IL MISURATORE NON E LA REGOLA. Le tre condizioni e la lunghezza minima
 * arrivano dal server dentro `pwd_token_check` e vengono da
 * `Rdacademy_Password_Policy::descrittore()`: la stessa classe che poi RIFA' la
 * verifica al salvataggio. Qui non c e' nessun numero scritto a mano e nessuna
 * frase riscritta. `RP-UNA-REGOLA-DI-PASSWORD-SOLA`.
 *
 * ⛔ IL TOKEN NON SI STAMPA. Vive nel parametro `?t=` e in una variabile
 * JavaScript; non finisce in nessun campo del DOM leggibile, in nessun log e
 * in nessun testo. `RP-IL-TOKEN-NON-SI-CONSERVA-IN-CHIARO` vale anche a valle.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// 0.8.2A — la pagina parla nella lingua della persona del collegamento (layout.php: Rdacademy_Lingua::del_collegamento).
if ( ! isset( $rx_t ) ) { $rx_l0 = Rdacademy_Lingua::del_collegamento() ?? Rdacademy_Lingua::corrente(); $rx_t = static function ( string $k ) use ( $rx_l0 ): string { return Rdacademy_Lingua::t( $k, $rx_l0 ); }; }
$rx_login  = esc_url( home_url( '/' . RDACADEMY_APP_SLUG . '/login' ) );
$rx_recup  = esc_url( home_url( '/' . RDACADEMY_APP_SLUG . '/recupera' ) );
?>
<div class="rx-login-wrap">
    <div class="rx-login-card">

        <!-- ATTESA — il token si verifica prima di disegnare qualunque campo -->
        <div id="rx-ip-wait">
            <div class="rx-login-header">
                <?php echo rdacademy_marchio_html( rdacademy_logo_porta( $rx_logo_porta ?? '', $rx_logo_is_placeholder ?? true ) ); ?>
                <p class="rx-login-sub"><?php echo esc_html( $rx_t( 'ip.verifica' ) ); ?></p>
            </div>
        </div>

        <!-- STATI A / B / C — il modulo -->
        <div id="rx-ip-form" style="display:none">
            <div class="rx-login-header">
                <?php echo rdacademy_marchio_html( rdacademy_logo_porta( $rx_logo_porta ?? '', $rx_logo_is_placeholder ?? true ) ); ?>
                <p class="rx-pub-title" id="rx-ip-titolo"><?php echo esc_html( $rx_t( 'ip.nuova_titolo' ) ); ?></p>
                <p class="rx-login-sub" id="rx-ip-chi"></p>
            </div>

            <div class="rx-fascia rx-fascia-blu" id="rx-ip-avviso" style="display:none">
                <div><?php echo esc_html( $rx_t( 'ip.sessioni' ) ); ?></div>
            </div>

            <div id="rx-ip-error" class="rx-alert rx-alert-error" style="display:none" role="alert"></div>

            <form id="rx-ip-f" class="rx-login-form" novalidate>
                <div class="rx-field-wrap">
                    <label class="rx-label" for="rx-ip-p1">
                        <?php echo esc_html( $rx_t( 'ip.nuova' ) ); ?>
                        <button type="button" class="rx-link rx-pwd-toggle" data-target="rx-ip-p1" aria-label="<?php echo esc_attr( $rx_t( 'login.mostra_aria' ) ); ?>"><?php echo esc_html( $rx_t( 'login.mostra' ) ); ?></button>
                    </label>
                    <input type="password" id="rx-ip-p1" class="rx-input" autocomplete="new-password" required aria-required="true">
                    <div class="rx-mis" id="rx-ip-mis" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                    <div class="rx-mis-x"><span><?php echo esc_html( $rx_t( 'ip.robustezza' ) ); ?> <b id="rx-ip-forza">&mdash;</b></span><span id="rx-ip-conta">0 <?php echo esc_html( $rx_t( 'ip.caratteri' ) ); ?></span></div>
                    <ul class="rx-reg" id="rx-ip-regole"></ul>
                </div>

                <div class="rx-field-wrap">
                    <label class="rx-label" for="rx-ip-p2"><?php echo esc_html( $rx_t( 'ip.ripeti' ) ); ?></label>
                    <input type="password" id="rx-ip-p2" class="rx-input" autocomplete="new-password" required aria-required="true">
                    <div class="rx-pub-err" id="rx-ip-diff" style="display:none"><?php echo esc_html( $rx_t( 'ip.diverse' ) ); ?></div>
                </div>

                <button type="submit" class="rx-btn rx-btn-primary rx-btn-full" id="rx-ip-go" disabled>
                    <span class="rx-btn-text" id="rx-ip-go-testo"><?php echo esc_html( $rx_t( 'ip.vai' ) ); ?></span>
                    <span class="rx-btn-loader" style="display:none" aria-hidden="true">
                        <svg class="rx-spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg>
                    </span>
                </button>
            </form>

            <p class="rx-login-footer" id="rx-ip-piede"><?php echo esc_html( $rx_t( 'ip.piede' ) ); ?></p>
        </div>

        <!-- STATO D — collegamento scaduto, gia' usato o non valido -->
        <div id="rx-ip-ko" style="display:none">
            <div class="rx-login-header">
                <?php echo rdacademy_marchio_html( rdacademy_logo_porta( $rx_logo_porta ?? '', $rx_logo_is_placeholder ?? true ) ); ?>
                <p class="rx-pub-title"><?php echo esc_html( $rx_t( 'ip.ko_titolo' ) ); ?></p>
            </div>
            <div class="rx-fascia rx-fascia-ambra">
                <div><?php echo wp_kses( $rx_t( 'ip.ko_testo' ), array( 'b' => array() ) ); ?></div>
            </div>
            <a class="rx-btn rx-btn-primary rx-btn-full" style="text-align:center" href="<?php echo $rx_recup; ?>"><?php echo esc_html( $rx_t( 'ip.ko_nuovo' ) ); ?></a>
            <p class="rx-login-footer" style="margin-top:12px">
                <a class="rx-login-recover" href="<?php echo $rx_login; ?>"><?php echo esc_html( $rx_t( 'rec.torna' ) ); ?></a>
            </p>
        </div>

        <!-- STATO E — fatto -->
        <div id="rx-ip-ok" style="display:none">
            <div class="rx-login-header">
                <?php echo rdacademy_marchio_html( rdacademy_logo_porta( $rx_logo_porta ?? '', $rx_logo_is_placeholder ?? true ) ); ?>
                <p class="rx-pub-title"><?php echo esc_html( $rx_t( 'ip.ok_titolo' ) ); ?></p>
            </div>
            <div class="rx-fascia rx-fascia-verde">
                <div><?php echo esc_html( $rx_t( 'ip.ok_testo' ) ); ?></div>
            </div>
            <a class="rx-btn rx-btn-primary rx-btn-full" style="text-align:center" id="rx-ip-entra" href="<?php echo $rx_login; ?>"><?php echo esc_html( sprintf( $rx_t( 'ip.entra' ), RDACADEMY_PRODUCT ) ); ?></a>
        </div>

    </div>
</div>

<script>
(function(){
    var TOKEN = (function(){
        var m = /[?&]t=([0-9a-fA-F]{64})/.exec(window.location.search || '');
        return m ? m[1] : '';
    })();

    var REGOLA = null;      // il descrittore che arriva dal server
    /* 0.8.2A — le parole che il JavaScript scrive, nella lingua della pagina. */
    var L = <?php echo wp_json_encode( array(
        'benvenuto' => sprintf( $rx_t( 'ip.benvenuto' ), RDACADEMY_PRODUCT ), 'nuova' => $rx_t( 'ip.nuova_titolo' ),
        'caratteri' => $rx_t( 'ip.caratteri' ), 'mostra' => $rx_t( 'login.mostra' ), 'nascondi' => $rx_t( 'login.nascondi' ),
        'errore' => $rx_t( 'ip.non_riuscita' ),
    ) ); ?>;
    var SCOPO  = 'r';

    function el(id){ return document.getElementById(id); }
    function mostra(quale){
        ['rx-ip-wait','rx-ip-form','rx-ip-ko','rx-ip-ok'].forEach(function(k){
            var n = el(k); if (n) n.style.display = (k === quale) ? '' : 'none';
        });
    }
    function posta(azione, dati, poi, male){
        var fd = new FormData();
        fd.append('action', 'rdacademy');
        fd.append('nonce', window.RDACADEMY.nonce);
        fd.append('rdacademy_action', azione);
        Object.keys(dati).forEach(function(k){ fd.append(k, dati[k]); });
        fetch(window.RDACADEMY.ajaxUrl, { method:'POST', body: fd, credentials:'same-origin' })
            .then(function(r){ return r.json(); })
            .then(poi)
            .catch(male || function(){ mostra('rx-ip-ko'); });
    }

    /* ── Verifica del collegamento, PRIMA di disegnare il modulo ──────────── */
    if (!TOKEN) { mostra('rx-ip-ko'); return; }

    posta('pwd_token_check', { t: TOKEN }, function(res){
        if (!res || !res.success || !res.data || !res.data.valido) { mostra('rx-ip-ko'); return; }
        REGOLA = res.data.regola || null;
        SCOPO  = res.data.scopo || 'r';

        var titolo = el('rx-ip-titolo');
        var chi    = el('rx-ip-chi');
        var avviso = el('rx-ip-avviso');

        if (SCOPO === 'w' || SCOPO === 'f') {   /* 1.4.0B — il foglio stampato è un primo accesso */
            /* STATO A — invito. Il nome e l'email sotto il titolo, come da contratto. */
            if (titolo) titolo.textContent = L.benvenuto;
            if (chi) chi.textContent = ((res.data.nome || '').trim() ? (res.data.nome + ' · ') : '') + (res.data.email || '');
            if (avviso) avviso.style.display = 'none';
        } else {
            /* STATO B — reimpostazione: due differenze e nessun'altra. */
            if (titolo) titolo.textContent = L.nuova;
            if (chi) chi.textContent = ((res.data.nome || '').trim() ? (res.data.nome + ' · ') : '') + (res.data.email || '');
            if (avviso) avviso.style.display = '';
        }
        disegnaRegole('');
        mostra('rx-ip-form');
        var p1 = el('rx-ip-p1'); if (p1) p1.focus();
    });

    /* ── Il misuratore. UNA COPIA SOLA, in public/js/rdacademy-pwd-meter.js. ───
       ⛔ 3.16.197 (S258) — qui dentro c'erano soglie e parole SCRITTE A MANO
       (12/16/20, «insufficiente/…/ottima») mentre la scheda utente le leggeva
       gia dal descrittore: due copie che sembravano d'accordo e non lo erano
       piu. Estratte prima di scriverne una terza per «Il mio profilo».
       BKL-MISURATORE-UNA-COPIA-SOLA — RP-UNA-REGOLA-DI-PASSWORD-SOLA. */
    function esiti(pwd){ return window.RxPwdMeter.esiti(pwd, REGOLA); }
    function forza(pwd){ return window.RxPwdMeter.forza(pwd, REGOLA); }

    function disegnaRegole(pwd){
        var ul = el('rx-ip-regole');
        if (!ul || !REGOLA) return;
        var e = esiti(pwd);
        var righe = [
            { k:'lunghezza', ok:e.lunghezza },
            { k:'personale', ok:e.personale },
            { k:'banale',    ok:e.banale }
        ];
        ul.innerHTML = '';
        righe.forEach(function(r){
            var li = document.createElement('li');
            li.className = pwd ? (r.ok ? 'si' : 'no') : '';
            li.textContent = REGOLA.testi[r.k] || r.k;
            ul.appendChild(li);
        });
    }

    function aggiorna(){
        var p1 = el('rx-ip-p1'), p2 = el('rx-ip-p2');
        var a = p1 ? p1.value : '', b = p2 ? p2.value : '';
        var e = esiti(a), f = forza(a);

        var mis = el('rx-ip-mis');
        if (mis) mis.className = 'rx-mis' + (f.l ? (' l' + f.l) : '');
        var fz = el('rx-ip-forza'); if (fz) fz.textContent = f.p;
        var ct = el('rx-ip-conta'); if (ct) ct.textContent = a.length + ' ' + L.caratteri;
        disegnaRegole(a);

        var uguali = (a !== '' && a === b);
        var diff = el('rx-ip-diff');
        if (diff) diff.style.display = (b !== '' && !uguali) ? '' : 'none';

        var go = el('rx-ip-go');
        if (go) go.disabled = !(e.lunghezza && e.personale && e.banale && uguali);
    }

    ['rx-ip-p1','rx-ip-p2'].forEach(function(id){
        var n = el(id);
        if (n) n.addEventListener('input', aggiorna);
    });

    document.querySelectorAll('.rx-pwd-toggle').forEach(function(b){
        b.addEventListener('click', function(){
            var t = el(this.getAttribute('data-target'));
            if (!t) return;
            var v = t.type === 'password';
            t.type = v ? 'text' : 'password';
            this.textContent = v ? L.nascondi : L.mostra;
        });
    });

    /* ── Il salvataggio ──────────────────────────────────────────────────── */
    var f = el('rx-ip-f');
    if (f) f.addEventListener('submit', function(ev){
        ev.preventDefault();
        var go = el('rx-ip-go');
        if (!go || go.disabled) return;
        var err = el('rx-ip-error');
        if (err) { err.style.display = 'none'; err.textContent = ''; }

        var t = go.querySelector('.rx-btn-text'), l = go.querySelector('.rx-btn-loader');
        go.disabled = true;
        if (t) t.style.display = 'none';
        if (l) l.style.display = 'inline-flex';

        posta('pwd_set', {
            t: TOKEN,
            password:  el('rx-ip-p1').value,
            password2: el('rx-ip-p2').value
        }, function(res){
            go.disabled = false;
            if (t) t.style.display = '';
            if (l) l.style.display = 'none';

            if (res && res.success) {
                var vai = (res.data && res.data.vai) ? res.data.vai : (window.RDACADEMY.appUrl + '/');
                var entra = el('rx-ip-entra');
                if (entra) entra.setAttribute('href', vai);
                mostra('rx-ip-ok');
                setTimeout(function(){ window.location.href = vai; }, 1400);
                return;
            }
            /* STATO D: il server dichiara che il collegamento non vale piu'. */
            if (res && res.data && res.data.collegamento === false) { mostra('rx-ip-ko'); return; }
            /* STATO C lato server: la regola non e' rispettata. */
            if (err) {
                err.textContent = (res && typeof res.data === 'string') ? res.data : L.errore;
                err.style.display = '';
            }
        });
    });
})();
</script>
