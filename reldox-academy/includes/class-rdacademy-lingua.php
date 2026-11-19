<?php
/**
 * Rdacademy_Lingua — LA LINGUA DELLA PERSONA (0.8.2A, owner 07/10 08:48, SPEC-CASSA § 0 punto 21).
 * =============================================================================
 * Tre lingue, italiano, inglese e tedesco (1.17.0L, SPEC-LINGUA-TEDESCA ed. 1; decisione
 * chiusa: frontend IT/EN/DE, pannello di amministrazione e referti solo in italiano).
 * ⛔ L ELENCO DELLE LINGUE STA SOLO QUI (LINGUE, NOMI): API, importazione, preferenze,
 * scheda e bandierine lo leggono. Una quarta lingua = una voce qui + i testi.
 *
 * DA DOVE SI LEGGE (in quest ordine, il primo che risponde vince):
 *  1. la SCHEDA della persona collegata (`persone.lingua`), se c e una sessione;
 *  2. la scelta fatta con le bandierine (cookie `rdacademy_lingua`, un anno);
 *  3. la lingua del browser: italiano se la prima lingua dichiarata e l italiano,
 *     tedesco se e il tedesco, inglese altrimenti.
 * La pagina «scegli la password» non passa di qui: usa la lingua della scheda
 * del collegamento (Rdacademy_Lingua::di_utente), perche chi la apre non e ancora
 * collegato e il collegamento dice gia chi e.
 *
 * ⛔ UN TESTO, TRE LINGUE, UN POSTO. Le frasi stanno in TESTI, chiave → [it, en, de]
 * (nell ordine di LINGUE): il banco conta che ogni chiave abbia le tre lingue (parita
 * chiavi × lingue, REGOLE di verifica). Una frase non si scrive due volte.
 *
 * ⛔ I testi delle notifiche (mail) NON stanno qui (0.8.7A, RF-BUG-025): vivono nella
 * Tabella Funzionale «Testi delle notifiche» (Rdacademy_Testi_Notifiche), una voce per
 * messaggio e lingua. Qui c e solo il loro SET DI PARTENZA (NOTIFICHE) e le frasi
 * fisse dell interfaccia e della sicurezza dei messaggi (pulsante, durata, piede).
 *
 * @package RelDoxAcademy
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Lingua {

	const LINGUE     = array( 'it', 'en', 'de' );
	const PREDEFINITA = 'it';
	const COOKIE     = 'rdacademy_lingua';

	/** Il nome che si legge nelle tendine e accanto alle bandierine. */
	const NOMI = array( 'it' => 'Italiano', 'en' => 'English', 'de' => 'Deutsch' );   // 0.1.04A: niente emoji (SPEC-GRAFICA v5 G6)

	/* ===================================================================== *
	 *  0.8.7A (RF-BUG-025, owner 08/10 09:29-09:38) — IL SET DI PARTENZA DEI TESTI DELLE NOTIFICHE
	 *  ⛔ Qui sta SOLO il set di partenza: i testi che arrivano alle persone vivono nella Tabella Funzionale
	 *  «Testi delle notifiche» (Rdacademy_Testi_Notifiche, /academy/tabelle/), una voce per messaggio e lingua.
	 *  Questa lista la legge la nascita della tabella e «Genera dalla fonte» (ricrea le voci che mancano), e la
	 *  mail la usa solo se la tabella non ha una voce attiva né nella lingua della scheda né in italiano.
	 *  Codice => lingua => [oggetto, testo]. Nel testo una riga vuota separa i paragrafi; {pulsante} è il punto
	 *  in cui va il pulsante (se manca, il pulsante va in fondo).
	 *  Copia ridotta: restano solo le notifiche dell account (invito, reinvio, reimpostazione, accesso, blocco).
	 * ===================================================================== */
	const NOTIFICHE = array(
		'invito.persona' => array(
			'it' => array( 'Benvenuto in {prodotto} — attiva il tuo accesso',
				"Ciao {nome},\n\nè stato creato per te un accesso a {prodotto}. Il tuo nome utente è {utente}.\n\nPer cominciare, scegli la tua password:\n\n{pulsante}" ),
			'en' => array( 'Welcome to {prodotto} — activate your access',
				"Hi {nome},\n\nan access to {prodotto} has been created for you. Your username is {utente}.\n\nTo get started, choose your password:\n\n{pulsante}" ),
			'de' => array( 'Willkommen bei {prodotto} — aktivieren Sie Ihren Zugang',
				"Guten Tag {nome},\n\nfür Sie wurde ein Zugang zu {prodotto} angelegt. Ihr Benutzername lautet {utente}.\n\nWählen Sie zunächst Ihr Passwort:\n\n{pulsante}" ),
		),
		'reinvio' => array(
			'it' => array( '{prodotto} — il tuo invito, di nuovo', 'Ti rimandiamo il collegamento: il precedente non è più valido, vale questo.' ),
			'en' => array( '{prodotto} — your invitation, again', 'We are sending you the link again: the previous one is no longer valid, use this one.' ),
			'de' => array( '{prodotto} — Ihre Einladung, erneut',
				"Wir senden Ihnen den Link erneut: Der vorherige ist nicht mehr gültig, es gilt dieser." ),
		),
		'reimpostazione' => array(
			'it' => array( '{prodotto} — reimposta la tua password',
				"Ciao {nome},\n\nabbiamo ricevuto una richiesta di reimpostazione della password del tuo accesso a {prodotto}. Se sei stato tu, imposta la password nuova da qui:\n\n{pulsante}\n\nSe non sei stato tu, non devi fare niente: la tua password attuale resta valida e questo collegamento scadrà da solo. Se ricevi messaggi come questo senza averli chiesti, avvisa l’amministratore del sistema." ),
			'en' => array( '{prodotto} — reset your password',
				"Hi {nome},\n\nwe received a request to reset the password of your {prodotto} access. If it was you, set your new password here:\n\n{pulsante}\n\nIf it was not you, you do not need to do anything: your current password stays valid and this link will expire on its own. If you receive messages like this without asking for them, tell your system administrator." ),
			'de' => array( '{prodotto} — setzen Sie Ihr Passwort zurück',
				"Guten Tag {nome},\n\nwir haben eine Anfrage zum Zurücksetzen des Passworts für Ihren Zugang zu {prodotto} erhalten. Wenn Sie das waren, legen Sie das neue Passwort hier fest:\n\n{pulsante}\n\nWenn Sie das nicht waren, müssen Sie nichts tun: Ihr aktuelles Passwort bleibt gültig, und dieser Link verfällt von selbst. Wenn Sie solche Nachrichten erhalten, ohne sie angefordert zu haben, informieren Sie den Systemadministrator." ),
		),
		'accesso.persona' => array(
			'it' => array( '{prodotto} — il tuo accesso',
				"Ciao {nome},\n\necco il collegamento per entrare in {prodotto}. La password è quella che hai già scelto: non cambia.\n\nNome utente: {utente}\n\n{pulsante}\n\nNon ricordi la password? Nella pagina d’accesso usa «Password dimenticata?»." ),
			'en' => array( '{prodotto} — your access',
				"Hi {nome},\n\nhere is the link to sign in to {prodotto}. Your password is the one you already chose: it does not change.\n\nUsername: {utente}\n\n{pulsante}\n\nDon’t remember your password? On the sign-in page use «Forgot your password?»." ),
			'de' => array( '{prodotto} — Ihr Zugang',
				"Guten Tag {nome},\n\nhier ist der Link zur Anmeldung bei {prodotto}. Das Passwort ist das, das Sie bereits gewählt haben: Es ändert sich nicht.\n\nBenutzername: {utente}\n\n{pulsante}\n\nSie wissen Ihr Passwort nicht mehr? Verwenden Sie auf der Anmeldeseite „Passwort vergessen?“." ),
		),
		'blocco' => array(
			'it' => array( '{prodotto} — account bloccato per tentativi di accesso falliti',
				"L'accesso a {prodotto} di {chi} ({email}) è stato sospeso dopo {soglia} tentativi con password errata. Si riapre da solo alle {ora}.\n\nRichiesta arrivata dalla rete {ip}.\n\nSe è la persona che conosci ad aver dimenticato la password, mandale il collegamento di reimpostazione dalla lista utenti. Se non riconosci né l'utente né la rete, guarda il registro accessi: cinque tentativi da un indirizzo sconosciuto raramente sono una dimenticanza.\n\nQuesto messaggio non riporta nessuna password, né quella corretta né quelle tentate, e non contiene nessun collegamento di sblocco." ),
			'en' => array( '{prodotto} — account locked after failed sign-in attempts',
				"The {prodotto} access of {chi} ({email}) has been suspended after {soglia} attempts with a wrong password. It reopens on its own at {ora}.\n\nThe requests came from the network {ip}.\n\nIf it is someone you know who forgot the password, send them the reset link from the user list. If you recognise neither the user nor the network, look at the access log: five attempts from an unknown address are rarely a slip of memory.\n\nThis message carries no password, neither the right one nor the ones tried, and contains no unlock link." ),
			'de' => array( '{prodotto} — Konto wegen fehlgeschlagener Anmeldeversuche gesperrt',
				"Der Zugang zu {prodotto} von {chi} ({email}) wurde nach {soglia} Versuchen mit falschem Passwort gesperrt. Er öffnet sich um {ora} von selbst wieder.\n\nAnfrage aus dem Netz {ip}.\n\nWenn es die Person ist, die Sie kennen, und sie ihr Passwort vergessen hat, senden Sie ihr den Link zum Zurücksetzen aus der Benutzerliste. Wenn Sie weder den Benutzer noch das Netz erkennen, prüfen Sie das Anmeldeprotokoll: Fünf Versuche von einer unbekannten Adresse sind selten eine Vergesslichkeit.\n\nDiese Nachricht enthält kein Passwort, weder das richtige noch die versuchten, und keinen Link zum Entsperren." ),
		),
	);

	/* ===================================================================== *
	 *  LE FRASI
	 * ===================================================================== */
	const TESTI = array(
		// Copia ridotta: porta d accesso, recupero, scegli la password, regola della password, cornice, icona, mail di sicurezza.
		// Le dati.* servono oggi a preferenze.php: escono con la sua lettura, se quella parte non resta.
		'login.sub'          => array( 'Accedi al tuo spazio di lavoro', 'Sign in to your workspace', 'Melden Sie sich in Ihrem Arbeitsbereich an' ),
		'login.scaduta'      => array( 'La sessione è scaduta dopo 8 ore senza attività: accedi di nuovo.', 'Your session expired after 8 hours of inactivity: please sign in again.', 'Die Sitzung ist nach 8 Stunden ohne Aktivität abgelaufen: Bitte melden Sie sich erneut an.' ),
		'login.ident'        => array( 'Email o nome utente', 'Email or username', 'E-Mail oder Benutzername' ),   // 1.4.0B — si entra anche col nome utente della scheda
		'login.ident_ph'     => array( 'nome@azienda.it', 'name@company.com', 'name@firma.de' ),   // 0.6.12A: il suggerimento intero e corto (resta così)
		'login.password'     => array( 'Password', 'Password', 'Passwort' ),
		'login.mostra'       => array( 'Mostra', 'Show', 'Anzeigen' ),
		'login.nascondi'     => array( 'Nascondi', 'Hide', 'Ausblenden' ),
		'login.mostra_aria'  => array( 'Mostra/nascondi password', 'Show/hide password', 'Passwort anzeigen/ausblenden' ),
		'login.accedi'       => array( 'Accedi', 'Sign in', 'Anmelden' ),
		'login.dimenticata'  => array( 'Password dimenticata?', 'Forgot your password?', 'Passwort vergessen?' ),
		'login.problemi'     => array( 'Hai problemi di accesso? Contatta l’amministratore del sistema.', 'Trouble signing in? Contact your system administrator.', 'Probleme bei der Anmeldung? Wenden Sie sich an den Systemadministrator.' ),
		'login.come_si_usa'  => array( 'Come si usa', 'How it works', 'So funktioniert es' ),
		'catalogo.voce'      => array( 'Catalogo', 'Catalogue', 'Katalog' ),   // F011 — la voce del rail del catalogo dei corsi (carico-e-190)
		'manuali.titolo'     => array( 'Come si usa', 'How it works', 'So funktioniert es' ),
		'manuali.voce'       => array( 'Manuale', 'Manual', 'Handbuch' ),
		'manuali.voce_piu'   => array( 'Manuali', 'Manuals', 'Handbücher' ),
		'manuali.sottotitolo'=> array( 'I manuali d’uso di RelDoxAcademy: da leggere qui o da scaricare in PDF.', 'RelDoxAcademy user manuals: read them here or download the PDF.', 'Die Benutzerhandbücher von RelDoxAcademy: hier lesen oder als PDF herunterladen.' ),
		'manuali.leggi'      => array( 'Leggi', 'Read', 'Lesen' ),
		'manuali.pdf'        => array( 'Scarica il PDF', 'Download the PDF', 'PDF herunterladen' ),
		'manuali.indice'     => array( 'Indice', 'Contents', 'Inhalt' ),
		'manuali.prec'       => array( 'Capitolo precedente', 'Previous chapter', 'Vorheriges Kapitel' ),
		'manuali.succ'       => array( 'Capitolo successivo', 'Next chapter', 'Nächstes Kapitel' ),
		'manuali.capitolo'   => array( 'Capitolo %d', 'Chapter %d', 'Kapitel %d' ),
		'manuali.torna'      => array( 'Torna all’accesso', 'Back to sign in', 'Zurück zur Anmeldung' ),
		'manuali.tutti'      => array( 'Tutti i manuali', 'All manuals', 'Alle Handbücher' ),
		'manuali.solo_it'    => array( 'Il manuale è solo in italiano.', 'This manual is available in Italian only.', 'Das Handbuch ist nur auf Italienisch verfügbar.' ),
		'manuali.vuoto'      => array( 'Il manuale non è presente in questo pacchetto.', 'This manual is not included in this package.', 'Das Handbuch ist in diesem Paket nicht enthalten.' ),
		'manuali.nessuno'    => array( 'Questo capitolo non esiste.', 'This chapter does not exist.', 'Dieses Kapitel existiert nicht.' ),
		'manuali.riservato'  => array( 'Questo manuale è riservato all’amministrazione.', 'This manual is reserved for administrators.', 'Dieses Handbuch ist der Verwaltung vorbehalten.' ),
		'login.vuoti'        => array( 'Inserisci email e password.', 'Enter your email and password.', 'Geben Sie E-Mail und Passwort ein.' ),
		'login.rete'         => array( 'Errore di rete. Riprova.', 'Network error. Please try again.', 'Netzwerkfehler. Bitte versuchen Sie es erneut.' ),
		'login.obbligatori'  => array( 'Email o username e password sono obbligatori', 'Email or username and password are required', 'E-Mail oder Benutzername und Passwort sind Pflichtfelder' ),
		'login.non_valide'   => array( 'Credenziali non valide', 'Invalid credentials', 'Ungültige Anmeldedaten' ),
		'login.bloccato'     => array( 'Accesso sospeso per troppi tentativi falliti.', 'Access suspended after too many failed attempts.', 'Anmeldung wegen zu vieler Fehlversuche gesperrt.' ),
		'login.riprova_alle' => array( 'Riprova dalle %s.', 'Try again from %s.', 'Versuchen Sie es ab %s erneut.' ),
		'login.riprova_poco' => array( 'Riprova fra qualche minuto.', 'Try again in a few minutes.', 'Versuchen Sie es in einigen Minuten erneut.' ),
		'login.blocco_rec'   => array( 'Se non ricordi la password, usa «Password dimenticata»: il blocco non impedisce il recupero.', 'If you don’t remember your password, use «Forgot your password?»: the block does not stop the recovery.', 'Wenn Sie Ihr Passwort nicht mehr wissen, verwenden Sie „Passwort vergessen“: Die Sperre verhindert die Wiederherstellung nicht.' ),
		'login.ambiguo'      => array( 'Questo identificativo risulta assegnato a più di un utente e il sistema non può stabilire chi sei. Avvisa l’amministratore: va reso univoco.', 'This identifier belongs to more than one user and the system cannot tell who you are. Tell the administrator: it must be made unique.', 'Diese Kennung ist mehreren Benutzern zugeordnet, und das System kann nicht feststellen, wer Sie sind. Informieren Sie den Administrator: Sie muss eindeutig gemacht werden.' ),
		'lingua.scegli'      => array( 'Lingua', 'Language', 'Sprache' ),
		'rec.titolo'         => array( 'Recupero credenziali', 'Recover your credentials', 'Zugangsdaten wiederherstellen' ),
		'rec.sub'            => array( 'Ti inviamo un collegamento per impostare una password nuova.', 'We will send you a link to set a new password.', 'Wir senden Ihnen einen Link, mit dem Sie ein neues Passwort festlegen können.' ),
		'rec.invia'          => array( 'Invia il collegamento', 'Send the link', 'Link senden' ),
		'rec.torna'          => array( 'Torna all’accesso', 'Back to sign in', 'Zurück zur Anmeldung' ),
		'rec.controlla'      => array( 'Controlla la tua casella', 'Check your inbox', 'Prüfen Sie Ihr Postfach' ),
		'rec.se_utente'      => array( 'Se l’indirizzo indicato corrisponde a un utente di %s', 'If the address you entered belongs to a %s user', 'Wenn die angegebene Adresse einem Benutzer von %s entspricht' ),
		'rec.riceverai'      => array( ', fra pochi istanti riceverai un messaggio con il collegamento per impostare una password nuova. Il collegamento vale %s e si può usare una volta sola.', ', in a few moments you will receive a message with the link to set a new password. The link is valid for %s and can be used only once.', ', erhalten Sie in wenigen Augenblicken eine Nachricht mit dem Link zum Festlegen eines neuen Passworts. Der Link gilt %s und kann nur einmal verwendet werden.' ),
		'rec.niente'         => array( 'Non hai ricevuto niente? Controlla la posta indesiderata, oppure riprova fra qualche minuto. Se il tuo indirizzo è cambiato, solo l’amministratore può aggiornarlo.', 'Nothing received? Check your spam folder, or try again in a few minutes. If your address has changed, only the administrator can update it.', 'Nichts erhalten? Prüfen Sie den Spam-Ordner oder versuchen Sie es in einigen Minuten erneut. Wenn sich Ihre Adresse geändert hat, kann nur der Administrator sie aktualisieren.' ),
		'ip.verifica'        => array( 'Verifica del collegamento in corso…', 'Checking the link…', 'Link wird geprüft …' ),
		'ip.nuova_titolo'    => array( 'Imposta una password nuova', 'Set a new password', 'Neues Passwort festlegen' ),
		'ip.benvenuto'       => array( 'Benvenuto in %s', 'Welcome to %s', 'Willkommen bei %s' ),
		'ip.sessioni'        => array( 'Al salvataggio tutte le sessioni aperte verranno chiuse: dovrai accedere di nuovo su ogni dispositivo.', 'When you save, all open sessions will be closed: you will have to sign in again on every device.', 'Beim Speichern werden alle offenen Sitzungen beendet: Sie müssen sich auf jedem Gerät erneut anmelden.' ),
		'ip.nuova'           => array( 'Nuova password', 'New password', 'Neues Passwort' ),
		'ip.robustezza'      => array( 'Robustezza:', 'Strength:', 'Stärke:' ),
		'ip.caratteri'       => array( 'caratteri', 'characters', 'Zeichen' ),
		'ip.ripeti'          => array( 'Ripeti la password', 'Repeat the password', 'Passwort wiederholen' ),
		'ip.diverse'         => array( 'Le due password non coincidono.', 'The two passwords do not match.', 'Die beiden Passwörter stimmen nicht überein.' ),
		'ip.vai'             => array( 'Imposta la password ed entra', 'Set the password and sign in', 'Passwort festlegen und anmelden' ),
		'ip.piede'           => array( 'Il collegamento che hai usato scade dopo il primo utilizzo.', 'The link you used expires after its first use.', 'Der von Ihnen verwendete Link verfällt nach der ersten Verwendung.' ),
		'ip.ko_titolo'       => array( 'Collegamento non più valido', 'This link is no longer valid', 'Link nicht mehr gültig' ),
		'ip.ko_testo'        => array( 'Questo collegamento è <b>scaduto</b> oppure è <b>già stato usato</b>. Per sicurezza ogni collegamento vale una volta sola.', 'This link has <b>expired</b> or has <b>already been used</b>. For your security every link works only once.', 'Dieser Link ist <b>abgelaufen</b> oder wurde <b>bereits verwendet</b>. Aus Sicherheitsgründen gilt jeder Link nur einmal.' ),
		'ip.ko_nuovo'        => array( 'Richiedi un collegamento nuovo', 'Request a new link', 'Neuen Link anfordern' ),
		'ip.ok_titolo'       => array( 'Password impostata', 'Password set', 'Passwort festgelegt' ),
		'ip.ok_testo'        => array( 'La tua password è stata aggiornata. Stiamo aprendo il tuo spazio di lavoro…', 'Your password has been updated. We are opening your workspace…', 'Ihr Passwort wurde aktualisiert. Ihr Arbeitsbereich wird geöffnet …' ),
		'ip.entra'           => array( 'Entra in %s', 'Enter %s', '%s öffnen' ),
		'ip.non_riuscita'    => array( 'Non è stato possibile impostare la password.', 'The password could not be set.', 'Das Passwort konnte nicht festgelegt werden.' ),
		'ip.troppi'          => array( 'Troppi tentativi. Riprova fra qualche minuto.', 'Too many attempts. Try again in a few minutes.', 'Zu viele Versuche. Versuchen Sie es in einigen Minuten erneut.' ),
		'pwd.lunghezza'      => array( 'almeno %d caratteri', 'at least %d characters', 'mindestens %d Zeichen' ),
		'pwd.personale'      => array( 'non contiene il tuo nome o la tua email', 'does not contain your name or your email', 'enthält weder Ihren Namen noch Ihre E-Mail-Adresse' ),
		'pwd.banale'         => array( 'non è fra le password più diffuse', 'is not one of the most common passwords', 'gehört nicht zu den gängigsten Passwörtern' ),
		'pwd.rifiuto'        => array( 'non accettata', 'not accepted', 'nicht akzeptiert' ),
		'pwd.altrui'         => array( 'non contiene nome, cognome, login o email dell’utente', 'does not contain the user’s first name, last name, login or email', 'enthält weder Vor- noch Nachname, Anmeldename oder E-Mail-Adresse des Benutzers' ),   // 1.17.0L
		'pwd.scala'          => array( 'insufficiente|sufficiente|buona|ottima', 'too weak|fair|good|excellent', 'unzureichend|ausreichend|gut|sehr gut' ),
		'menu.pannello'      => array( 'Pannello', 'Home', 'Übersicht' ),
		'menu.profilo'       => array( 'Il mio profilo', 'My profile', 'Mein Profil' ),
		'menu.esci'          => array( 'Esci', 'Sign out', 'Abmelden' ),
		'menu.apri'          => array( 'Apri il menu', 'Open the menu', 'Menü öffnen' ),
		'menu.funzioni'      => array( 'Funzioni', 'Menu', 'Funktionen' ),
		'menu.personale'     => array( 'Personale', 'Personal', 'Persönlich' ),
		'menu.tu'            => array( 'Tu', 'You', 'Sie' ),
		'menu.chiudi'        => array( 'Chiudi il menu', 'Close the menu', 'Menü schließen' ),
		'dati.titolo'        => array( 'I miei dati', 'My details', 'Meine Daten' ),
		'dati.sub'           => array( 'Questi recapiti li usa l’azienda per cercarti.', 'The company uses these details to reach you.', 'Diese Kontaktdaten verwendet das Unternehmen, um Sie zu erreichen.' ),
		'dati.modifica'      => array( 'Modifica', 'Edit', 'Bearbeiten' ),
		'dati.salva'         => array( 'Salva', 'Save', 'Speichern' ),
		'dati.annulla'       => array( 'Annulla', 'Cancel', 'Abbrechen' ),
		'dati.cellulare'     => array( 'Cellulare', 'Mobile', 'Mobiltelefon' ),
		'dati.telefono'      => array( 'Telefono', 'Phone', 'Telefon' ),
		'dati.referente'     => array( 'Referente alla cassa', 'Till contact person', 'Ansprechpartner an der Kasse' ),
		'dati.lingua'        => array( 'Lingua', 'Language', 'Sprache' ),
		'dati.contatto'      => array( 'Contatto', 'Contact', 'Kontakt' ),
		'dati.fonte_frase'   => array( 'Alcune voci arrivano da «%s»: si correggono lì e qui si aggiornano al prossimo invio.', 'Some details come from “%s”: they are corrected there and updated here at the next transfer.', 'Einige Angaben kommen aus „%s“: Sie werden dort korrigiert und hier bei der nächsten Übertragung aktualisiert.' ),
		'dati.fonte_bloccato' => array( 'Il cellulare arriva da «%s»: si corregge lì, non da questa pagina.', 'The mobile number comes from “%s”: it is corrected there, not on this page.', 'Die Mobilnummer kommt aus „%s“: Sie wird dort korrigiert, nicht auf dieser Seite.' ),
		'dati.lingua_it'     => array( 'Italiano', 'Italiano', 'Italiano' ),
		'dati.lingua_en'     => array( 'English', 'English', 'English' ),
		'dati.lingua_de'     => array( 'Deutsch', 'Deutsch', 'Deutsch' ),
		'dati.azienda'       => array( 'Li cambia l’azienda', 'Changed by the company', 'Werden vom Unternehmen geändert' ),
		'dati.motivo'        => array( 'Valgono per le fatture, per il gestionale e per il tuo accesso.', 'They are used for invoices, for the company system and for your login.', 'Sie gelten für die Rechnungen, die Verwaltungssoftware und Ihre Anmeldung.' ),
		'dati.rs'            => array( 'Ragione sociale', 'Company name', 'Firmenname' ),
		'dati.piva'          => array( 'Partita IVA', 'VAT number', 'USt-IdNr.' ),
		'dati.erp'           => array( 'Codice del gestionale', 'Supplier code', 'Code in der Verwaltungssoftware' ),
		'dati.convenzione'   => array( 'Convenzione', 'Agreement', 'Vertrag' ),
		'dati.conv_dal_al'   => array( 'dal %1$s al %2$s', 'from %1$s to %2$s', 'vom %1$s bis %2$s' ),
		'dati.conv_dal'      => array( 'dal %s', 'from %s', 'ab %s' ),
		'dati.email'         => array( 'Email d’accesso', 'Login email', 'Anmelde-E-Mail' ),
		'dati.correggi'      => array( 'Per correggerli scrivi a %s', 'To correct them, write to %s', 'Zur Korrektur schreiben Sie an %s' ),
		'dati.correggi_tel'  => array( 'Per correggerli chiama %s', 'To correct them, call %s', 'Zur Korrektur rufen Sie %s an' ),
		'dati.correggi_2'    => array( 'Per correggerli scrivi a %1$s o chiama %2$s', 'To correct them, write to %1$s or call %2$s', 'Zur Korrektur schreiben Sie an %1$s oder rufen Sie %2$s an' ),
		'dati.niente'        => array( 'Non hai cambiato niente.', 'You have not changed anything.', 'Sie haben nichts geändert.' ),
		'dati.salvato'       => array( 'Salvato alle %s.', 'Saved at %s.', 'Gespeichert um %s.' ),
		'dati.cambiata'      => array( 'L’azienda ha appena cambiato la tua scheda: ricarica la pagina e rifai la modifica.', 'The company has just changed your record: reload the page and make the change again.', 'Das Unternehmen hat Ihr Profil gerade geändert: Laden Sie die Seite neu und nehmen Sie die Änderung erneut vor.' ),
		'dati.errore'        => array( 'Non è stato possibile salvare. Riprova tra poco.', 'It could not be saved. Please try again shortly.', 'Speichern nicht möglich. Versuchen Sie es in Kürze erneut.' ),
		'dati.non_salvate'   => array( 'Ci sono modifiche non salvate.', 'There are unsaved changes.', 'Es gibt nicht gespeicherte Änderungen.' ),
		'icona.btn'          => array( 'Metti l’icona sul telefono', 'Put the icon on your phone', 'Symbol auf dem Telefon anlegen' ),
		'icona.menu'         => array( 'Icona sul telefono', 'Icon on your phone', 'Symbol auf dem Telefon' ),
		'icona.installa'     => array( 'Installa l’icona sul telefono', 'Install the icon on your phone', 'Symbol auf dem Telefon installieren' ),
		'icona.chiudi'       => array( 'Chiudi', 'Close', 'Schließen' ),
		'icona.ios'          => array( 'iPhone: in Safari tocca Condividi (il quadrato con la freccia in su), poi «Aggiungi alla schermata Home» e «Aggiungi».', 'iPhone: in Safari tap Share (the square with the arrow), then «Add to Home Screen» and «Add».', 'iPhone: Tippen Sie in Safari auf Teilen (das Quadrat mit dem Pfeil nach oben), dann auf „Zum Home-Bildschirm“ und „Hinzufügen“.' ),
		'icona.ios_safari'   => array( 'Su iPhone l’icona si mette solo da Safari: apri questa pagina in Safari e ripeti.', 'On iPhone the icon can only be added from Safari: open this page in Safari and try again.', 'Auf dem iPhone lässt sich das Symbol nur über Safari anlegen: Öffnen Sie diese Seite in Safari und wiederholen Sie den Vorgang.' ),
		'icona.and'          => array( 'Android: in Chrome tocca ⋮ in alto a destra, poi «Installa app» (o «Aggiungi a schermata Home»).', 'Android: in Chrome tap ⋮ at the top right, then «Install app» (or «Add to Home screen»).', 'Android: Tippen Sie in Chrome oben rechts auf ⋮, dann auf „App installieren“ (oder „Zum Startbildschirm hinzufügen“).' ),
		'icona.pc'           => array( 'Apri questa pagina dal telefono e tocca «Metti l’icona sul telefono».', 'Open this page on your phone and tap «Put the icon on your phone».', 'Öffnen Sie diese Seite auf dem Telefon und tippen Sie auf „Symbol auf dem Telefon anlegen“.' ),
		'icona.dopo'         => array( 'Da quel momento il tuo codice si apre dall’icona, come un’app.', 'From then on your code opens from the icon, like an app.', 'Ab dann öffnet sich Ihr Code über das Symbol, wie eine App.' ),
		'icona.fatto'        => array( 'Fatto: l’icona è sulla schermata Home.', 'Done: the icon is on your Home screen.', 'Erledigt: Das Symbol ist auf dem Home-Bildschirm.' ),
		'icona.no'           => array( 'Installazione annullata.', 'Installation cancelled.', 'Installation abgebrochen.' ),
		'mail.blocco_tit'    => array( 'Un account è stato bloccato per tentativi falliti.', 'An account has been locked after failed attempts.', 'Ein Konto wurde wegen fehlgeschlagener Versuche gesperrt.' ),
		'mail.non_reg'       => array( 'non registrata', 'not recorded', 'nicht erfasst' ),
		'mail.fra_poco'      => array( 'fra pochi minuti', 'in a few minutes', 'in wenigen Minuten' ),
		'accesso.btn'        => array( 'Entra in %s', 'Sign in to %s', 'Bei %s anmelden' ),
		'mail.attiva'        => array( 'Attiva il tuo accesso', 'Activate your access', 'Aktivieren Sie Ihren Zugang' ),
		'mail.vale_giorni'   => array( 'Il collegamento vale <b>%d giorni</b> e si può usare <b>una volta sola</b>.', 'The link is valid for <b>%d days</b> and can be used <b>only once</b>.', 'Der Link gilt <b>%d Tage</b> und kann <b>nur einmal</b> verwendet werden.' ),
		'mail.giorni'        => array( '%d giorni', '%d days', '%d Tage' ),
		'mail.minuti'        => array( '%d minuti', '%d minutes', '%d Minuten' ),
		'mail.non_funziona'  => array( 'Se il pulsante non funziona, copia questo indirizzo nel browser:', 'If the button does not work, copy this address into your browser:', 'Falls die Schaltfläche nicht funktioniert, kopieren Sie diese Adresse in den Browser:' ),
		'mail.automatico'    => array( 'Messaggio automatico di %s: non rispondere a questo indirizzo.', 'Automatic message from %s: please do not reply to this address.', 'Automatische Nachricht von %s: Bitte antworten Sie nicht an diese Adresse.' ),
		'mail.mai_password'  => array( '%s non chiede mai la password per email, per telefono o via chat.', '%s never asks for your password by email, phone or chat.', '%s fragt niemals per E-Mail, Telefon oder Chat nach Ihrem Passwort.' ),
		'mail.non_aspettavi' => array( 'Se non aspettavi questo messaggio, ignoralo e avvisa il mittente.', 'If you were not expecting this message, ignore it and tell the sender.', 'Wenn Sie diese Nachricht nicht erwartet haben, ignorieren Sie sie und informieren Sie den Absender.' ),
		'mail.reimp_btn'     => array( 'Imposta una password nuova', 'Set a new password', 'Neues Passwort festlegen' ),
		'mail.vale_minuti'   => array( 'Il collegamento vale <b>%d minuti</b> e si può usare <b>una volta sola</b>.', 'The link is valid for <b>%d minutes</b> and can be used <b>only once</b>.', 'Der Link gilt <b>%d Minuten</b> und kann <b>nur einmal</b> verwendet werden.' ),
		'porta.apertura'     => array( 'Apertura in corso…', 'Opening…', 'Wird geöffnet …' ),
		'porta.continua'     => array( 'Continua', 'Continue', 'Weiter' ),
		'porta.errore'       => array( 'La pagina non si è aperta: controlla la connessione e riprova.', 'The page did not open: check your connection and try again.', 'Die Seite hat sich nicht geöffnet: Prüfen Sie die Verbindung und versuchen Sie es erneut.' ),
		'porta.riprova'      => array( 'Riprova', 'Try again', 'Erneut versuchen' ),
	);

	/* ===================================================================== */

	public static function valida( $l ): string {
		$l = strtolower( substr( trim( (string) $l ), 0, 2 ) );
		return in_array( $l, self::LINGUE, true ) ? $l : self::PREDEFINITA;
	}

	/** La frase nella lingua chiesta (o in quella corrente). Una chiave sconosciuta torna se stessa: si vede, non si nasconde. */
	/** 1.21.0L — l elenco delle chiavi dei testi (il banco della parità delle lingue le scorre per prefisso). */
	public static function chiavi(): array {
		return array_keys( self::TESTI );
	}

	public static function t( string $chiave, ?string $lingua = null ): string {
		$l = $lingua === null ? self::corrente() : self::valida( $lingua );
		$r = self::TESTI[ $chiave ] ?? null;
		if ( ! $r ) return $chiave;
		// 1.17.0L — lettura per posizione della lingua in LINGUE; una voce mancante ricade sull italiano (il banco non la fa passare).
		return (string) ( $r[ self::indice( $l ) ] ?? $r[0] );
	}

	/** 1.17.0L — la posizione della lingua in LINGUE (0 = italiano): la colonna di TESTI e di ogni tabella a tre voci. */
	public static function indice( string $l ): int {
		$i = array_search( $l, self::LINGUE, true );
		return $i === false ? 0 : (int) $i;
	}

	/** 1.17.0L — la voce di una tabella per lingua (array( 'it' => …, 'en' => …, 'de' => … )): quella della lingua, altrimenti l italiano. */
	public static function di( array $tabella, string $l ) {
		return $tabella[ self::valida( $l ) ] ?? ( $tabella[ self::PREDEFINITA ] ?? '' );
	}

	/** 1.17.0L — l elenco delle lingue da leggere in un messaggio: «it», «en» e «de». */
	public static function elenco_testo(): string {
		$q = array_map( static function ( $l ) { return '«' . $l . '»'; }, self::LINGUE );
		$u = array_pop( $q );
		return $q ? implode( ', ', $q ) . ' e ' . $u : $u;
	}

	/** Con i segnaposto di sprintf. */
	public static function f( string $chiave, ...$args ): string {
		return vsprintf( self::t( $chiave ), $args );
	}

	/** Come f(), con la lingua esplicita (le mail: la lingua del destinatario, non di chi preme). */
	public static function fl( string $lingua, string $chiave, ...$args ): string {
		return vsprintf( self::t( $chiave, $lingua ), $args );
	}

	private static $cache = null;

	/** La lingua della pagina che si sta disegnando (vedi testa del file). */
	public static function corrente(): string {
		if ( self::$cache !== null ) return self::$cache;
		$l = null;
		if ( class_exists( 'Rdacademy_Auth' ) && Rdacademy_Auth::is_logged_in() ) {
			$u = Rdacademy_Auth::get_current_user();
			if ( $u && ! empty( $u['id'] ) ) $l = self::della_scheda( (int) $u['id'] );
		}
		if ( $l === null ) $l = self::scelta();
		if ( $l === null ) $l = self::dal_browser();
		return self::$cache = $l;
	}

	/** Per i banchi e per chi cambia contesto nella stessa richiesta. */
	public static function azzera(): void { self::$cache = null; }

	/** La scelta delle bandierine, se c e. */
	public static function scelta(): ?string {
		$c = (string) ( $_COOKIE[ self::COOKIE ] ?? '' );
		return in_array( $c, self::LINGUE, true ) ? $c : null;
	}

	/** «parte dalla lingua del browser»: la prima lingua dichiarata se è fra le nostre (italiano, tedesco), inglese altrimenti. */
	public static function dal_browser(): string {
		$h = strtolower( trim( (string) ( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '' ) ) );
		if ( $h === '' ) return self::PREDEFINITA;
		$prima = substr( $h, 0, 2 );
		return in_array( $prima, self::LINGUE, true ) ? $prima : 'en';
	}

	/** La lingua scritta sulla scheda dell account (null = nessuna scheda: amministratori, account senza persona). */
	public static function della_scheda( int $uid ): ?string {
		if ( $uid <= 0 || ! class_exists( 'Rdacademy_Persone' ) ) return null;
		try {
			$r = Rdacademy_DB::get_row( "SELECT lingua FROM `" . RDACADEMY_TABLE_PREFIX . "persone` WHERE user_id = ? LIMIT 1", array( $uid ), 'i' );
		} catch ( Throwable $e ) {
			return null;
		}
		return ( $r && isset( $r['lingua'] ) ) ? self::valida( $r['lingua'] ) : null;
	}

	/** La lingua delle mail e della pagina del collegamento: la scheda, altrimenti l italiano. */
	public static function di_utente( int $uid ): string {
		return self::della_scheda( $uid ) ?? self::PREDEFINITA;
	}

	/** La lingua della persona del collegamento «scegli la password» (?t=…), se il collegamento vale. Non lo consuma. */
	public static function del_collegamento(): ?string {
		$t = (string) ( $_GET['t'] ?? '' );
		if ( ! preg_match( '/^[0-9a-fA-F]{64}$/', $t ) || ! class_exists( 'Rdacademy_Password_Token' ) ) return null;
		try {
			$v = Rdacademy_Password_Token::verifica( strtolower( $t ) );
		} catch ( Throwable $e ) {
			return null;
		}
		return $v ? self::di_utente( (int) ( $v['utente']['id'] ?? 0 ) ) : null;
	}

	/** Le bandierine IT | EN | DE delle porte d accesso. Cambiano la lingua col cookie e ricaricano la pagina. */
	public static function bandierine(): string {
		$cur = self::corrente();
		$h   = '<nav class="rdacademy-lingue" aria-label="' . esc_attr( self::t( 'lingua.scegli' ) ) . '">';
		foreach ( self::LINGUE as $i => $l ) {
			if ( $i ) $h .= '<span class="rdacademy-lingue-sep" aria-hidden="true">|</span>';
			$h .= '<button type="button" class="rdacademy-lingua' . ( $l === $cur ? ' on' : '' ) . '" data-lingua="' . esc_attr( $l ) . '"'
				. ( $l === $cur ? ' aria-current="true"' : '' ) . ' lang="' . esc_attr( $l ) . '">' . esc_html( self::NOMI[ $l ] ) . '</button>';
		}
		$h .= '</nav>';
		$h .= '<script>(function(){document.querySelectorAll(".rdacademy-lingua").forEach(function(b){b.addEventListener("click",function(){'
			. 'document.cookie="' . self::COOKIE . '="+b.getAttribute("data-lingua")+";path=/;max-age=31536000;SameSite=Lax";location.reload();});});})();</script>';
		return $h;
	}

	/** Registra la lingua (dopo l accesso: la porta la ricorda per la volta dopo). */
	public static function ricorda( string $l ): void {
		$l = self::valida( $l );
		if ( headers_sent() ) return;
		setcookie( self::COOKIE, $l, array( 'expires' => time() + YEAR_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => false, 'samesite' => 'Lax' ) );
	}

	/* ===================================================================== *
	 *  IL SET DI PARTENZA DI UNA NOTIFICA (0.8.7A)
	 * ===================================================================== */

	/** @return array{oggetto:string,testo:string} il set di partenza (con i segnaposto); '' se il codice non esiste. */
	public static function partenza( string $codice, string $lingua ): array {
		$v = self::NOTIFICHE[ $codice ][ self::valida( $lingua ) ] ?? null;
		return array( 'oggetto' => (string) ( $v[0] ?? '' ), 'testo' => (string) ( $v[1] ?? '' ) );
	}

	/**
	 * 0.14.0A (owner 11/10 08:14: «1 persone» nel giornaliero) — IL SINGOLARE. Quando {persone} o {righe} valgono 1, la parola
	 * che li segue nel testo passa al singolare (persone → persona, people → person, righe → riga, rows → row). Lavora sul
	 * testo del modello, quindi vale anche per i testi che l azienda ha cambiato in Tabelle Funzionali, senza scriverci.
	 */
	public static function singolari( string $modello, array $v ): string {
		$coppie = array(
			'persone' => array( 'persone' => 'persona', 'people' => 'person', 'persons' => 'person' ),
			'righe'   => array( 'righe' => 'riga', 'rows' => 'row' ),
		);
		foreach ( $coppie as $k => $parole ) {
			if ( (string) ( $v[ $k ] ?? '' ) !== '1' ) continue;
			foreach ( $parole as $plur => $sing ) {
				$modello = (string) preg_replace( '/\{' . $k . '\}(\s+)' . $plur . '\b/u', '{' . $k . '}$1' . $sing, $modello );
			}
		}
		return $modello;
	}

	/**
	 * Riempie i segnaposto. In HTML ($html = true) il testo si ripulisce PRIMA e i valori entrano gia ripuliti:
	 * nome utente e prodotto in grassetto, i paragrafi sulle righe vuote, gli a capo semplici restano a capo.
	 */
	public static function riempi( string $modello, array $v, bool $html ): string {
		$modello = self::singolari( $modello, $v );
		$mappa = array(
			'{nome}'         => (string) ( $v['nome'] ?? '' ),
			'{utente}'       => (string) ( $v['utente'] ?? '' ),
			'{prodotto}'     => (string) ( $v['prodotto'] ?? RDACADEMY_PRODUCT ),
			'{scadenza}'     => (string) ( $v['scadenza'] ?? '' ),
			'{collegamento}' => (string) ( $v['collegamento'] ?? '' ),
			// 0.8.7A — i due nomi brevi della tabella «Testi delle notifiche» ({link} = {collegamento}, {giorni} = {scadenza})
			// e i dati dell avviso di blocco
			'{link}'           => (string) ( $v['collegamento'] ?? '' ),
			'{giorni}'         => (string) ( $v['scadenza'] ?? '' ),
			'{chi}'            => (string) ( $v['chi'] ?? '' ),
			'{email}'          => (string) ( $v['email'] ?? '' ),
			'{ip}'             => (string) ( $v['ip'] ?? '' ),
			'{ora}'            => (string) ( $v['ora'] ?? '' ),
			'{soglia}'         => (string) ( $v['soglia'] ?? '' ),
		);
		if ( ! $html ) {
			$mappa['{pulsante}'] = '';
			$s = strtr( $modello, $mappa );
			return trim( preg_replace( '/[ \t]+([,.;:!?])/u', '$1', $s ) );
		}
		$esc = array();
		foreach ( $mappa as $k => $val ) {
			$e = esc_html( $val );
			if ( in_array( $k, array( '{utente}', '{prodotto}', '{chi}', '{ora}', '{ip}' ), true ) ) $e = $val === '' ? '' : '<b>' . $e . '</b>';
			if ( ( $k === '{collegamento}' || $k === '{link}' ) && $val !== '' ) $e = '<a href="' . esc_url( $val ) . '" style="color:#2E75B6;word-break:break-all">' . esc_html( $val ) . '</a>';
			$esc[ esc_html( $k ) ] = $e;
		}
		// 0.8.7A — {pulsante}: il blocco del pulsante (HTML gia composto da chi spedisce) prende il posto del segnaposto;
		// il testo che gli sta accanto nello stesso paragrafo resta un paragrafo suo, prima o dopo.
		$puls  = (string) ( $v['pulsante_html'] ?? '' );
		$testo = str_replace( array( "\r\n", "\r" ), "\n", trim( $modello ) );
		$par   = preg_split( "/\n[ \t]*\n+/", $testo );
		$out   = '';
		$messo = false;
		foreach ( $par as $p ) {
			$pezzi = explode( '{pulsante}', $p );
			foreach ( $pezzi as $i => $pz ) {
				if ( $i > 0 ) { $out .= $messo ? '' : $puls; $messo = true; }
				$pz = trim( $pz );
				if ( $pz === '' ) continue;
				$pz = strtr( esc_html( $pz ), $esc );
				$pz = preg_replace( '/[ \t]+([,.;:!?])/u', '$1', $pz );
				$out .= '<p style="margin:0 0 14px">' . nl2br( $pz, false ) . '</p>';
			}
		}
		if ( ! $messo ) $out .= $puls;   // senza {pulsante} il pulsante va in fondo al testo
		return $out;
	}
}
