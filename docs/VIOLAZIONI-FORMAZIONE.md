<!-- REGISTRO VIOLAZIONI · 18/11/2026 F013 · aperti 0 · totale 2 · ultimi 7 giorni 2 · primo libero V-3 -->
<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# Registro VIOLAZIONI della Formazione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Creato in Formazione F009, 18/11/2026, secondo `spec/SPEC-REGISTRI-DI-CHAT-FAMIGLIA-RELDOX-v2.md` del nodo (ordine dell owner 17/11/2026 18:35, chat RelDox CRD S714).

Ci va ogni volta che la chat **non rispetta una regola scritta** (`CLAUDE.md`, specifiche di famiglia, decisioni dell owner), anche senza danno (SPEC-REGISTRI v2 § 1). Scheda: V-n · data e sessione · regola violata · che cosa è successo · chi l ha vista · rimedio · classe · ripetizione. Alla terza della stessa classe si apre un fallimento.

Si legge all apertura di ogni sessione (§ 5), si scrive subito, nello stesso commit del primo gesto; nessuna scheda si cancella.

## Schede

Nessuna scheda al 18/11/2026: nelle sessioni F001-F008 non risultano scritti difetti né errori di metodo (verificato sulle aperture e sui registri di `docs/`). Niente schede inventate per riempire (§ 6.1).

### V-1 · 18/11/2026 · Formazione F012
- **Regola violata**: APERTURA F012 § 11 e § 01 punto 1 — «dopo ogni file il banco: G0 e G1 restano verdi»; si pubblica solo col banco verde.
- **Che cosa è successo**: copiando `public/js/rdfood-icona.js` il banco è stato lanciato nella stessa catena del commit e del push; G1 era ROSSO (due `/food/` nei commenti, righe 2 e 8) e il commit `b0684b5` è andato su `main` lo stesso, perché il comando che mostrava l esito non fermava la catena.
- **Chi l ha vista**: Claude, leggendo l esito subito dopo il push.
- **Rimedio**: i due commenti corretti in `/academy/`, banco G0 e G1 verdi, commit di correzione con questa scheda. Da qui il banco si lancia **da solo**, si legge l esito e solo dopo si pubblica.
- **Classe**: banco non rispettato prima della pubblicazione.
- **Ripetizione**: prima della classe.

### V-2 · 18/11/2026 · Formazione F013
- **Regola violata**: il kit a quattro buste in un solo ZIP per ogni consegna di codice (REGOLE-FAMIGLIA v4 § 10 «Kit di trasferimento = UN SOLO ZIP con quattro buste» e § 9 riga 9 «kit a quattro buste»; SPEC-KIT-DI-CONSEGNA v1 § 2), che vale per tutte le chat che generano codice, allineate col capo famiglia.
- **Che cosa è successo**: ho consegnato all owner RelDoxAcademy 0.1.01A e poi 0.1.02A come zip del plugin sciolto con il suo MD5, senza il kit: niente buste, niente manifesto, niente prova di lettura, niente progressivo K.
- **Chi l ha vista**: l owner, 18/11/2026 15:07, chat Formazione F013: «La regola imposta è sempre delle 4 buste allineati con il capo famiglia le sorelle che genero codice devo rispettare questa regola»; «segnala come violazione».
- **Rimedio**: la 0.1.02A si riconsegna come kit `KIT-RELDOXACADEMY-K0001-0_1_02A-F014.zip` (quattro buste, manifesto e prova di lettura nell APERTURA F014), composto e verificato da uno script del banco che va rosso se manca una busta; da qui ogni consegna di RelDoxAcademy è un kit.
- **Classe**: forma della consegna non rispettata.
- **Ripetizione**: prima della classe.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
