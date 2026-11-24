<!-- REGISTRO FALLIMENTI · 24/11/2026 F033 · aperti 5 · primo libero F033-F2 -->
<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# Registro FALLIMENTI della Formazione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Creato in Formazione F009, 18/11/2026, secondo `spec/SPEC-REGISTRI-DI-CHAT-FAMIGLIA-RELDOX-v2.md` del nodo (ordine dell owner 17/11/2026 18:35, chat RelDox CRD S714).

Ci va un errore di metodo **arrivato all owner**, oppure la terza violazione della stessa classe (SPEC-REGISTRI v2 § 1). Scheda: sessione-Fn — data — titolo · che cosa è successo · causa (misurata) · rimedio · regola che ne esce · dove entra (nello stesso commit, nel `CLAUDE.md` o in un controllo automatico).

Si legge all apertura di ogni sessione (§ 5), si scrive subito, nello stesso commit del primo gesto; nessuna scheda si cancella.

## Schede

Nessuna scheda al 18/11/2026: nelle sessioni F001-F008 non risultano scritti difetti né errori di metodo (verificato sulle aperture e sui registri di `docs/`). Niente schede inventate per riempire (§ 6.1).

### F033-F1 — 24/11/2026 — quasi tre ore e nessuna versione consegnabile: attese lunghe non dichiarate, ordine sbagliato, token bruciati
- **Che cosa è successo**: owner 24/11/2026 16:15, chat Formazione F033: *«pesima chat qusi 3 ore di lavorazione mal impostata e mia hai bruciato un sacco di token per non avere un risultatoconcreto»*; la chat è ferma per almeno due giorni. La F033 ha chiuso con la 0.1.19A sul ramo, **senza controprova e senza dodicesima revisione a freddo**: nulla da installare, la console resta ferma sul sito.
- **Causa (misurata)**: (1) due attese lunghe lanciate senza dirle prima all owner: revisione a freddo 49 minuti (agente da circa 370.000 token) e sabotaggi circa un ora (123 sabotaggi, un banco intero ciascuno); l owner ha chiesto alle 16:00 «a che punto sei» senza averne notizia; (2) ordine sbagliato: i sabotaggi, che non decidono la consegna, sono stati eseguiti **prima** della dodicesima revisione, che la decide; (3) il motore 0.1.19A è stato affidato a un secondo agente pesante mentre la sessione aspettava, senza un tetto di tempo; (4) costo dei due agenti mai dichiarato all owner.
- **Rimedio**: kit K0037 con il lavoro fatto (rimedio degli 8 alti e medi, banco 136/136) e prompt della F034 che mette la dodicesima revisione **prima** e i sabotaggi **per ultimi, una volta sola**, con le durate scritte.
- **Regola che ne esce**: (a) ogni attesa oltre 10 minuti (revisione a freddo, sabotaggi, agente di sviluppo) si dice all owner **prima** di lanciarla, con durata e costo stimati, e lui sceglie se farla ora; (b) in una sessione si fa prima ciò che decide la consegna (revisione a freddo con 0 alti e 0 medi), poi il resto; i sabotaggi una volta sola, in fondo; (c) un solo agente pesante per sessione, se non lo chiede l owner; (d) un rapporto breve all owner almeno ogni 30 minuti di lavoro senza consegne.
- **Dove entra**: `CLAUDE.md` di A-010, «Regole di sempre»; nello stesso commit.

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

### F030-F1 — 23/11/2026 — la riga «Registri» del nodo tenuta a mano: terza violazione della stessa classe
- **Che cosa è successo**: terza violazione della classe «regole di famiglia lette e non applicate» (V-5 e V-6 sulle stelle lette dal cruscotto, V-8 sulla riga «Registri» del nodo ferma a 36 punti aperti mentre i file ne avevano 37); l ha vista l owner dal cruscotto delle sorelle (23/11/2026 18:30, chat Formazione F030).
- **Causa (misurata)**: la riga «Registri» si scriveva a mano e nessun controllo la confrontava coi registri; le sessioni che non scrivono nel nodo (F029) la lasciavano indietro.
- **Rimedio**: `strumenti/riga-registri.py` conta i quattro registri nei file e scrive la riga; riga del nodo riallineata.
- **Regola**: la riga «Registri» del nodo non si scrive a mano: si copia dall uscita di `python3 -I strumenti/riga-registri.py . <nodo>/bacheca/formazione/STATO.md`, che deve essere VERDE a ogni consegna e prima di ogni pubblicazione sul nodo; le voci nate su un ramo di sessione si contano dal ramo, con la nota nella riga.
- **Dove entra**: nell APERTURA di ogni sessione (§ 01 punto 0 e chiusura) e nel `CLAUDE.md` di A-010 («I quattro registri»), nello stesso commit.

### F013-F1 — 18/11/2026 — zip dichiarato installabile, provato solo su un WordPress in inglese
- **Che cosa è successo**: ho consegnato la 0.1.01A come «si installa e funziona», provata da zip sul WordPress di prova; sul sito dell owner, in italiano, il plugin dopo il wizard non parte (BUG-1). Owner 18/11/2026 14:37, chat Formazione F013.
- **Causa (misurata)**: il WordPress di prova aveva il `wp-config` inglese, dove la riga cercata dal wizard c è; non ho provato le condizioni del sito vero (WordPress italiano su Aruba).
- **Rimedio**: 0.1.02A con la correzione; banco `F013-banco-wpconfig.php` con `wp-config` italiano, inglese e senza segnaposto; prova dello zip sul WordPress di prova con `wp-config` italiano.
- **Regola**: lo zip si prova nelle condizioni del sito vero: WordPress in italiano, permalink come sul sito, installazione da zero e sopra un installazione precedente.
- **Dove entra**: nello script di ricostruzione `banco/F013-rifai-0.1.02A.sh`, che va ROSSO e non compone lo zip se il banco del `wp-config` non è verde; nello stesso commit.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
