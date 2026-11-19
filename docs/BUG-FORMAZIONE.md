<!-- REGISTRO BUG · 18/11/2026 F013 · aperti 1 · cornice 1/5 · primo libero BUG-2 -->
<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# Registro BUG della Formazione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Creato in Formazione F009, 18/11/2026, secondo `spec/SPEC-REGISTRI-DI-CHAT-FAMIGLIA-RELDOX-v2.md` del nodo (ordine dell owner 17/11/2026 18:35, chat RelDox CRD S714).

Ci va ciò che **è rotto** nei materiali, negli strumenti, nei banchi o nel codice di RelDoxAcademy (SPEC-REGISTRI v2 § 1). Stati: aperta → in lavorazione → consegnata (versione) → chiusa | respinta | diventata voce n. Si chiude solo con l esito verde sul vero e la citazione dell owner. Cornice: 5 bug aperti.

Si legge all apertura di ogni sessione (§ 5), si scrive subito, nello stesso commit del primo gesto; nessuna scheda si cancella.

## Schede

Nessuna scheda al 18/11/2026: nelle sessioni F001-F008 non risultano scritti difetti né errori di metodo (verificato sulle aperture e sui registri di `docs/`). Niente schede inventate per riempire (§ 6.1).

### BUG-1 — ★★★★★ — 18/11/2026 — dopo il wizard il plugin non parte su un WordPress in italiano
- **Stato**: consegnata (0.1.02A), da chiudere con l esito dell owner sul sito.
- **Visto**: owner 18/11/2026 14:37, chat Formazione F013, sul sito di RelDoxAcademy con la 0.1.01A: dopo «Scrivi» del wizard, «Avvia il prodotto» porta a una pagina inesistente e «Primo accesso» dice «Non hai il permesso di accedere a questa pagina».
- **Causa (misurata)**: il wizard cerca in `wp-config.php` la riga inglese «That's all, stop editing»; nel `wp-config` italiano c è «Finito, interrompere le modifiche!», quindi il blocco del database finisce in coda, **dopo** `require_once ABSPATH . 'wp-settings.php'`. WordPress avvia i plugin prima di leggere le credenziali e RelDoxAcademy resta in «solo installazione»: niente `/academy/`, niente «Primo accesso». Rifacendo il wizard, il blocco c è già e la risposta è «già configurato»: il guasto resta. I permalink non c entrano: con la correzione funziona con «Semplice», «Data e nome» e «Nome articolo», senza toccarli.
- **Rimedio (0.1.02A)**: il blocco va prima della riga italiana o inglese, o comunque prima della riga di `wp-settings.php`; un blocco scritto dopo si riconosce e si sposta da solo; la verifica dopo la scrittura controlla anche la posizione; il testo del metodo manuale dice dove incollare in italiano.
- **Banco**: `banco/F013-banco-wpconfig.php`: ROSSO sulla 0.1.01A (3), VERDE sulla 0.1.02A. Prova dallo zip sul WordPress di prova con `wp-config` italiano: 0.1.01A → `/academy/` 404 e «Primo accesso» 403, come sul sito; 0.1.02A → tutto 200, password scelta, dentro.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
