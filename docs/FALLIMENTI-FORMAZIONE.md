<!-- REGISTRO FALLIMENTI · 18/11/2026 F012 · aperti 2 · primo libero F012-F2 -->
<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# Registro FALLIMENTI della Formazione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Creato in Formazione F009, 18/11/2026, secondo `spec/SPEC-REGISTRI-DI-CHAT-FAMIGLIA-RELDOX-v2.md` del nodo (ordine dell owner 17/11/2026 18:35, chat RelDox CRD S714).

Ci va un errore di metodo **arrivato all owner**, oppure la terza violazione della stessa classe (SPEC-REGISTRI v2 § 1). Scheda: sessione-Fn — data — titolo · che cosa è successo · causa (misurata) · rimedio · regola che ne esce · dove entra (nello stesso commit, nel `CLAUDE.md` o in un controllo automatico).

Si legge all apertura di ogni sessione (§ 5), si scrive subito, nello stesso commit del primo gesto; nessuna scheda si cancella.

## Schede

Nessuna scheda al 18/11/2026: nelle sessioni F001-F008 non risultano scritti difetti né errori di metodo (verificato sulle aperture e sui registri di `docs/`). Niente schede inventate per riempire (§ 6.1).

### F011-F1 — 18/11/2026 — domanda su un dettaglio di un plugin che ancora non funziona
- **Che cosa è successo**: a fine F011 ho chiesto all owner se rendere automatico il controllo delle mail rifiutate, mentre RelDoxAcademy non è ancora installabile.
- **Causa**: ho messo una scelta di dettaglio davanti all obiettivo; la copia non è ancora un plugin da vedere.
- **Rimedio**: tolto il punto dall APERTURA F012; nessuna domanda di dettaglio finché il plugin non si installa.
- **Regola**: *«Serve la funzionalità»* (owner 18/11/2026 10:05, chat Formazione F011): prima un plugin installabile e funzionante; le scelte di dettaglio si annotano nell APERTURA e si portano all owner solo quando c è qualcosa da provare. Rapporti brevi e concreti.
- **Dove entra**: `CLAUDE.md`, «Regole di sempre», nello stesso commit.

### F012-F1 — 18/11/2026 — Dodici sessioni e il plugin non si vede ancora; il prompt non si capisce
- **Che cosa è successo**: owner 18/11/2026 11:42, chat Formazione F012: «non si capisce il prompt per la prossima chat. Comunque continui a scrivere, scrivere senza produrre nulla il plugin non si vede ancora». F012 ha copiato 10 file in ordine di elenco e ha chiuso con un prompt di 20 righe di rimandi interni.
- **Causa (misurata)**: si copia file per file in ordine di allegato A, non in ordine di «che cosa serve perché si attivi». Il controllo dei `require` fatto dopo la chiusura dice che per attivarsi mancano solo 6 file e 4 viste (`layout.php`, `bootstrap-wizard`, `docs-page`, `docs-content/`, due loghi; viste `contatto-form`, `setup-personale`), mentre `app.css` (7451 righe) e le viste grandi possono venire dopo. Qui non c è WordPress (download 403): la prova la può fare solo l owner sul sito.
- **Rimedio**: F013 fa **solo** il pacchetto installabile 0.1.01A: chiude i file mancanti (quelli lunghi ridotti all essenziale), lo zip, e lo dà all owner per l installazione su `reldoxacademy.it`. Il resto della copia riprende dopo, guidato da ciò che si vede sul sito.
- **Regola che ne esce**: prima di copiare un file ci si chiede se serve all installazione; il prompt della sessione dopo sta in 5 righe, senza sigle interne.
- **Dove entra**: `CLAUDE.md` (regola «Prima la funzionalità») e APERTURA F013, nello stesso commit.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
