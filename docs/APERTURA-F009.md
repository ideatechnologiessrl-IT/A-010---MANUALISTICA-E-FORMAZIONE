<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F009 — Manualistica e formazione · nona sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F008, 17/11/2026. Ramo di partenza: `main`.

## §00 — DA DOVE SI RIPARTE
F008 (17/11/2026) ha fatto, tutto in `main`:
- **Apertura** sul nodo VERDE; nessun messaggio nuovo per la Formazione.
- **Messaggio a RelDoxFood** `bacheca/formazione/messaggi/2026-11-17-a-reldoxfood-conteggi-gettoni.md` (informativo): `conteggi()` conta i gettoni `f:` fra quelli «in chiaro»; misurato: nella 1.24.11L il metodo **non ha chiamanti** (plugin, diagnostica, banchi), quindi oggi nessun falso allarme.
- **Analisi tecnica ed. 7** (`.md` e `.html`): `includes/carico-b.php` diventa **TIENI, ridotto** (via i cinque `require` della licenza da firmare e l avvio di `License_Admin`: copiato com era sarebbe stato un fatale); scritto nelle «Differenze» del registro.
- **Blocco G**, altri 10 file letti per intero e copiati (35 in tutto, `docs/REGISTRO-LETTURA-BLOCCO-G.md`):
  - `class-rdfood-router.php` → **ridotto**: via l indirizzo `/c/` del QR personale, `$profilo` e «Entra come…», l email condivisa del recupero, lo scopo `f` in `pwd_set` (costante assente nella copia: fatale che `php -l` non vede); liste chiuse dell integratore tenute (finisce sul 403). Con lui, nelle copie già fatte: via `$profilo` dalla firma di `Rdacademy_Auth::login()` e le chiavi `login.scegli`, `profilo.*` dal gestore delle lingue;
  - `carico-b` → **ridotto** (ed. 7); `carico-c`, `carico-d` → TIENI;
  - schema e ancora di licenza → TIENI (nell ancora: commento su chi legge `state()` e l esempio `/academy/`, che **G1 ha visto**);
  - Documentazione interna: `docs-loader`, `docs`, `markdown` → TIENI;
  - `class-rdfood-accesso-emergenza.php` → TIENI.
- **Banchi sulla copia parziale** (`banco/esiti/F008-G-parziale.txt`): **G0 e G1 verdi**, trappola G1 verde; G2 e G10 rossi (manca la diagnostica e il carico completo, atteso).
- Chiusa al 70% del contesto, dichiarato all owner.

## §01 — CHE COSA FARE, IN ORDINE
0. Nodo: `git fetch --depth=200 origin main`, poi `python3 strumenti/apertura-nodo.py --prodotto formazione` (VERDE). Messaggi, risposte, `--segna`. Se RelDoxFood ha risposto sui conteggi, nulla da fare (era informativo).
1. Aggancia `ideatechnologiessrl-IT/RelDoxFood` al commit **5e4a40c** (1.24.11L) **solo in lettura** e continua il blocco G, nell ordine del caricamento (87 file restano, ~33.000 righe):
   - **`includes/class-rdfood-stato-sistema.php`** (441, *ridotto*: tolti i rami della ristorazione; ⛔ la riga «Licenza d uso» legge lo stato della licenza da firmare, tolta: resta solo l ancora di dominio) — ⛔ aprirlo **a inizio sessione**, da solo, commit subito dopo;
   - le dipendenze di `carico-c` (`lotto-c/`: schema-c, utenti, me, impostazioni, tf-registry, tf-admin-rest, area-tecnica) e di `carico-d` (`lotto-d/`, `backup/`, `uploads-js/`);
   - poi `carico-e` (RIFAI), `carico-e-143`, `-144`, `-190`, `lotto-e/` (con `class-rdfood-testi-notifiche.php` *ridotto*: restano `invito.persona`, `accesso.persona`, `reinvio`, `reimpostazione`, `blocco`), le viste (con `preferenze.php` *ridotto*: via anche le 29 `dati.*` dal gestore delle lingue), gli stili e gli script, la diagnostica.
   - ⛔ **Da ricordare leggendo**: `public/js/app.js` ha il ramo «Entra come…» (`scegli`, `profili`): va tolto; `public/views/login.php` (TIENI nell allegato A) va guardato per lo stesso ramo: se c è, è una **differenza** → «Differenze» del registro ed **edizione 8** prima di andare avanti.
   - Ogni file: **letto per intero**, poi `python3 -I strumenti/copia-con-segno.py /home/claude/reldoxfood <percorso> <decisione> "<nota>"`, poi `php -l`; per «ridotto» e «rifai» le modifiche a mano dopo la copia; poi il banco (G0 e G1 devono restare verdi); `grep` dei nomi del cliente nei commenti prima del commit; `/food/` nei commenti diventa `/academy/` (G1 lo trova).
   - Ogni costante d una classe tolta (es. `SCOPO_FOGLIO`) va cercata a mano: **G1 vede le chiamate alle classi tolte, non le costanti di classi tenute che nella copia non esistono più**. Idea per un G1 più fine, da proporre quando la copia è completa.
2. A copia completa: `python3 -I banco/F005-G-banchi-copia.py . /home/claude/reldoxfood` **verde** (G0, G1, G2, G10), più la trappola `--trappola`; G3-G7 sul palco.
3. Lezioni: ferme alla 1.2 (D26).

## §02 — CHE COSA ASPETTO DALLE ALTRE CHAT
- **Sicurezza-Cyber**: il parere sulla SPEC (accesso firmato, conservazione). Serve prima della porta 1, non per la 0.1.01A.
- **RelDox CRD**: la voce 159 e la proposta del pezzo comune «Accesso ai corsi» (roadmap F18).
- **RelDoxChecks, RelDoxPortal**: le risposte all inventario dei manuali (dal 15/11; il sollecito tocca al capo famiglia).

## §11 — PROMPT PER LA SESSIONE F009
> Parti dal repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F009.md`. Aggancia anche `ideatechnologiessrl-IT/RelDox-Famiglia` (prima `git fetch --depth=200 origin main`) ed esegui `python3 strumenti/apertura-nodo.py --prodotto formazione`. Sei la chat Manualistica e formazione, sessione F009. Leggi i messaggi nuovi e rispondi. Poi aggancia `ideatechnologiessrl-IT/RelDoxFood` al commit 5e4a40c (1.24.11L) **solo in lettura** e continua il blocco G dal punto scritto in `docs/REGISTRO-LETTURA-BLOCCO-G.md` (prossimo: `class-rdfood-stato-sistema.php`, ridotto, da solo a inizio sessione): leggi riga per riga ogni file prima di copiarlo (`spec/REGOLE-FAMIGLIA-RELDOX-v4.md` § 2), copialo con `strumenti/copia-con-segno.py`, togli ciò che dice l allegato A dell analisi ed. 7 e i nomi del cliente nei commenti; manuale e codice sempre distinti (D29). Dopo ogni file il banco: G0 e G1 restano verdi. A copia completa i banchi G0, G1, G2, G10 verdi. Solo italiano; una cosa alla volta; al 70% del contesto dichiaralo, al 75% chiudi la sessione con l APERTURA F010.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
