/* =============================================================================
 * rdacademy-pwd-meter.js — IL MISURATORE DELLA PASSWORD, UNA COPIA SOLA
 * v3.16.197 (S258, voce 39 chat 3 di 4) — BKL-MISURATORE-UNA-COPIA-SOLA
 * =============================================================================
 *
 * PERCHE ESISTE. Fino alla 3.16.196 il misuratore viveva in DUE copie:
 * `imposta-password.php` (funzioni `esiti()` e `forza()`) e
 * `gestione-utenti.php` (`checkPwd()`). Non erano nemmeno d accordo fra loro:
 * la prima teneva ancora soglie e parole SCRITTE A MANO (12/16/20,
 * «insufficiente/sufficiente/buona/ottima»), la seconda dalla 3.16.196 leggeva
 * gia `scala` dal descrittore. Due copie che dicono la stessa cosa oggi sono
 * due copie che diranno cose diverse domani: e esattamente cosa era gia
 * successo, e nessuno se ne era accorto perche entrambe SEMBRAVANO giuste.
 *
 * S258 avrebbe scritto la TERZA copia — la card «Il mio accesso» della scena 9.
 * Il PROMPT lo vieta: prima si estrae, poi si scrive. Questa e l estrazione.
 *
 * ⛔ QUI DENTRO NON C E NESSUN NUMERO E NESSUNA PAROLA. Soglie, varieta minima
 * e le quattro parole arrivano da `Rdacademy_Password_Policy::descrittore()`
 * (chiave `scala`). Se un giorno il minimo passasse da 12 a 14, questo file
 * non si tocca. `RP-UNA-REGOLA-DI-PASSWORD-SOLA`.
 *
 * COME SI USA
 *     var f = RxPwdMeter.forza(pwd, REGOLA);   // {l: 0..4, p: 'parola'}
 *     var e = RxPwdMeter.esiti(pwd, REGOLA);   // {lunghezza, personale, banale}
 *     var e = RxPwdMeter.esiti(pwd, REGOLA, parole);  // 3.44.03: parole dai campi
 *     var w = RxPwdMeter.personaliDa({first_name, last_name, login, email}, REGOLA);
 *     var g = RxPwdMeter.genera(REGOLA, parole);       // 3.44.03 (BUG-179)
 *     RxPwdMeter.dipingi(pwd, REGOLA, {mis:'..', forza:'..', conta:'..'});
 *
 * `REGOLA` e il descrittore cosi com e uscito dal server, senza rimaneggiarlo.
 * Se manca o e monco il misuratore resta a zero tacche e non inventa niente:
 * un misuratore che indovina e peggio di un misuratore spento.
 * ========================================================================== */
(function (w) {
    'use strict';

    /* La scala di ripiego NON e una seconda regola: e l assenza di regola.
       Serve solo a non far esplodere il disegno se il descrittore non arriva,
       e in quel caso il livello resta 0 e la parola resta il trattino. */
    function scalaDi(regola) {
        return (regola && regola.scala) ? regola.scala : null;
    }

    function minimoDi(regola, sc) {
        if (regola && regola.minimo) return regola.minimo;
        if (sc && sc.soglie && sc.soglie.length) return sc.soglie[0];
        return 0;
    }

    /* Le quattro tacche. E la trasposizione fedele di
       Rdacademy_Password_Policy::robustezza(), che resta la parola del server. */
    function forza(pwd, regola) {
        var v  = pwd || '';
        var n  = v.length;
        var sc = scalaDi(regola);
        if (!n || !sc) return { l: 0, p: '\u2014' };

        var minimo = minimoDi(regola, sc);
        if (n < minimo) return { l: 1, p: sc.parole[0] || '\u2014' };

        var varieta = 0;
        if (/[a-z]/.test(v))        varieta++;
        if (/[A-Z]/.test(v))        varieta++;
        if (/[0-9]/.test(v))        varieta++;
        if (/[^A-Za-z0-9]/.test(v)) varieta++;

        var punti = 0, k;
        for (k = 0; k < sc.soglie.length; k++) { if (n >= sc.soglie[k]) punti++; }
        if (varieta >= sc.varieta_min) punti++;

        var liv = (punti >= 4) ? 4 : (punti === 3 ? 3 : 2);
        return { l: liv, p: sc.parole[liv - 1] || '\u2014' };
    }

    /* I tre esiti che il browser puo dire da se.
       ⛔ `banale` e sempre vero appena c e del testo: l elenco delle password
       diffuse NON viaggia, e la parola definitiva resta del server, che rifa
       la verifica per intero. Il browser accompagna, non decide. */
    function esiti(pwd, regola, parole) {
        var v    = pwd || '';
        var low  = v.toLowerCase();
        var sc   = scalaDi(regola);
        var min  = minimoDi(regola, sc) || 12;
        // 3.44.03 (S514, BUG-179): chi ha le parole in mano (la scheda utente,
        // che le ricava dai campi) le passa; gli altri usano quelle del server.
        var pers = parole ? parole : ((regola && regola.personali) ? regola.personali : []);
        var contiene = false, i;
        for (i = 0; i < pers.length; i++) {
            if (low && pers[i] && low.indexOf(pers[i]) !== -1) { contiene = true; break; }
        }
        return {
            lunghezza: v.length >= min,
            personale: !!v && !contiene,
            banale:    !!v
        };
    }

    /* Il disegno delle tre parti visibili, per chi non ha niente da aggiungere:
       le quattro tacche, la parola e il conteggio dei caratteri. Gli id si
       passano perche le tre superfici hanno tre marcature diverse e cambiarle
       vorrebbe dire toccare tre CSS per una funzione sola. */
    function dipingi(pwd, regola, id, parole) {
        var f = forza(pwd, regola);
        var n = (pwd || '').length;
        // 3.44.03 (S514, BUG-179) — chi passa le parole (la scheda utente) chiede
        // che la robustezza NON dica «ottima» accanto a una regola rotta: una
        // tacca rossa e la parola del rifiuto, dal descrittore. Senza parole il
        // disegno resta quello di prima (Setup personale non cambia).
        if (parole && pwd) {
            var es = esiti(pwd, regola, parole);
            if ((!es.lunghezza || !es.personale) && regola && regola.parola_rifiuto) {
                f = { l: 1, p: regola.parola_rifiuto };
            }
        }
        var e;
        if (id && id.mis)   { e = document.getElementById(id.mis);
                              if (e) e.className = (id.cls || 'rx-mis') + (f.l ? (' l' + f.l) : ''); }
        if (id && id.forza) { e = document.getElementById(id.forza);
                              if (e) e.textContent = f.p; }
        if (id && id.conta) { e = document.getElementById(id.conta);
                              if (e) e.textContent = n + ' caratteri'; }
        return f;
    }

    /* ── 3.44.03 (S514, BUG-179) — LE PAROLE PERSONALI DAI CAMPI ─────────────
       Specchio di Rdacademy_Password_Policy::parole_personali(): nome, cognome,
       login e la parte dell email prima della @, in minuscolo, sotto la soglia
       scartate, senza doppi. La soglia arriva dal descrittore (`personali_min`):
       se non c e non si inventa, si torna un elenco vuoto. Lo specchio e
       sorvegliato dal banco S514-2 (stessi ingressi, stesse parole). */
    function personaliDa(campi, regola) {
        var min = (regola && regola.personali_min) ? regola.personali_min : 0;
        if (!min || !campi) return [];
        var email = String(campi.email || '');
        var lista = [campi.first_name, campi.last_name, campi.login,
                     email ? email.split('@')[0] : ''];
        var fuori = [], i, c;
        for (i = 0; i < lista.length; i++) {
            c = String(lista[i] || '').trim().toLowerCase();
            if (c.length >= min && fuori.indexOf(c) === -1) fuori.push(c);
        }
        return fuori;
    }

    /* ── 3.44.03 (S514, BUG-179) — IL GENERATORE ────────────────────────────
       Cinque gruppi di quattro, col trattino: 24 caratteri, «ottima» sulla
       scala del server (tre soglie + varieta). Alfabeto senza i caratteri che
       si confondono quando la password si detta o si ricopia (0 O o 1 l I).
       crypto.getRandomValues con SCARTO: il resto della divisione su un byte
       favorirebbe i primi caratteri. Si ripete finche c e almeno una
       maiuscola, una minuscola e una cifra e nessuna parola personale.
       ⛔ Il browser propone, il server giudica: la rifa per intero. */
    var ALFABETO = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';

    function casuale(n) {
        var c = w.crypto || w.msCrypto;
        if (!c || !c.getRandomValues) return null;
        var tetto = 256 - (256 % n), buf = new Uint8Array(1);
        do { c.getRandomValues(buf); } while (buf[0] >= tetto);
        return buf[0] % n;
    }

    function genera(regola, parole) {
        var g, k, x, pwd, low, i, buona;
        for (var giro = 0; giro < 64; giro++) {
            pwd = '';
            for (g = 0; g < 5; g++) {
                if (g) pwd += '-';
                for (k = 0; k < 4; k++) {
                    x = casuale(ALFABETO.length);
                    if (x === null) return '';
                    pwd += ALFABETO.charAt(x);
                }
            }
            buona = /[A-Z]/.test(pwd) && /[a-z]/.test(pwd) && /[0-9]/.test(pwd);
            low = pwd.toLowerCase();
            for (i = 0; buona && parole && i < parole.length; i++) {
                if (parole[i] && low.indexOf(parole[i]) !== -1) buona = false;
            }
            if (buona) return pwd;
        }
        return '';
    }

    w.RxPwdMeter = { forza: forza, esiti: esiti, dipingi: dipingi,
                     personaliDa: personaliDa, genera: genera };
}(window));
