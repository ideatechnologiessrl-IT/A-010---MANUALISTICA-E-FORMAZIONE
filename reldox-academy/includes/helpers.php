<?php
/**
 * RelDoxAcademy — Helper functions condivise
 * Copiato da includes/helpers.php di RelDox CRD 3.55.09G e ridotto.
 * ⛔ Tutte le funzioni globali portano il prefisso rdacademy_: con RelDox CRD attivo
 * sullo stesso WordPress una funzione con lo stesso nome e un errore fatale
 * (SPEC § 1). TAGLI: rx_uuid_v4 (nessun chiamante nel guscio), rx_view_icon e
 * rx_color_is_light (viste pinnate del CRM), rx_origin_back_url (token corti
 * doc/off/task/contatto/azienda del CRM), rx_pacchetto_per_colonna (ancora di
 * licenza, lotto B), le icone di rail del dominio CRM.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'rdacademy_nav_icon' ) ) {
    function rdacademy_nav_icon( string $name ): string {
        $icons = [
            // TAGLIO: le icone delle funzioni del CRM (albero, offerte, task, forecast, sfa…).
            'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
            'log'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
            'users'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
            'settings'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
            'table'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>',
            'logout'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
            'trash'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>',
            'key'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 4a5.5 5.5 0 1 0-3.9 9.4L10 14l-2 2v2H6v2H4v-2.6l6.6-6.6"/><circle cx="16.5" cy="6.5" r="1"/></svg>',
            // NUOVO nel guscio: «Pannello» (casa).
            'home'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg>',
        ];
        // NUOVO — REGISTRO UNICO DELLE ICONE (REGOLE § 3.1): i lotti aggiungono le loro dal filtro.
        $icons = (array) apply_filters( 'rdacademy_nav_icons', $icons );
        return $icons[ $name ] ?? '';
    }
}

/**
 * ── L IDENTITA DI CHI E COLLEGATO — PORTA UNICA ──────────────────────────────
 *
 * 3.17.12 (S275) — BUG-92. Nasce perche i chiamanti da oggi sono DUE: la topbar
 * di `layout.php`, che disegna la persona dalla 1C, e la testata di
 * `public/views/setup-personale.php`, che fino alla 3.17.11 portava un
 * pittogramma generico identico per chiunque.
 *
 * ⛔ PERCHE UNA PORTA E NON UNA COPIA. La regola non e una riga: e la PRIORITA
 * dell avatar (`avatar_path` canonico, poi `avatar_url` legacy, poi le
 * iniziali), il `file_exists` che evita l immagine rotta quando la colonna e
 * valorizzata ma il file non c e piu, e il ripiego delle iniziali sull email
 * quando nome e cognome non ci sono entrambi. Riscriverla nella vista avrebbe
 * prodotto due verita sulla stessa persona destinate a divergere: e la forma
 * esatta gia pagata da BUG-87 (la barra della posta) e da BUG-89 (le icone).
 *
 * ⛔ NON LEGGE IL DATABASE. Riceve la riga utente che il chiamante ha gia:
 * `layout.php` ha `Rdacademy_Auth::get_current_user()` a r.7 e la vista e inclusa
 * NELLO STESSO SCOPE. Una query qui dentro sarebbe una seconda lettura della
 * stessa identita a ogni pagina.
 *
 * ⛔ NON PRENDE NESSUN IDENTIFICATIVO DA FUORI. Chi la chiama le passa la riga
 * della PROPRIA sessione: nessun `user_id`, nessun `uid`, nessun
 * `target_user_id` (`RP-IL-SELF-SERVICE-NON-TOCCA-UN-ALTRO`, GATE 29).
 *
 * @param array $u riga utente (first_name, last_name, email, avatar_path, avatar_url)
 * @return array{nome:string,iniziali:string,avatar_url:string,email:string,ha_nome:bool}
 */
if ( ! function_exists( 'rdacademy_identita_utente' ) ) {
    function rdacademy_identita_utente( $u ) {
        if ( ! is_array( $u ) ) $u = array();

        $first = trim( (string) ( $u['first_name'] ?? '' ) );
        $last  = trim( (string) ( $u['last_name']  ?? '' ) );
        $email = (string) ( $u['email'] ?? '' );
        $pieno = ( $first !== '' && $last !== '' );

        // Priorita avatar: avatar_path (canonico 1C) -> avatar_url (legacy) -> iniziali.
        $url = '';
        $up  = wp_upload_dir();
        if ( ! empty( $u['avatar_path'] ) ) {
            $file = $up['basedir'] . '/rdacademy-avatars/' . basename( (string) $u['avatar_path'] );
            if ( file_exists( $file ) ) {
                $url = $up['baseurl'] . '/rdacademy-avatars/' . basename( (string) $u['avatar_path'] );
            }
        }
        if ( $url === '' && ! empty( $u['avatar_url'] ) ) {
            $url = (string) $u['avatar_url'];   // ripiego legacy (installazioni pre-1A)
        }

        if ( $pieno ) {
            $ini = strtoupper( substr( $first, 0, 1 ) . substr( $last, 0, 1 ) );
        } else {
            $ini = strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', $email ), 0, 2 ) );
            if ( $ini === '' ) $ini = '?';
        }

        return array(
            'nome'       => $pieno ? ( $first . ' ' . $last ) : $email,
            'iniziali'   => $ini,
            'avatar_url' => $url,
            'email'      => $email,
            'ha_nome'    => $pieno,
        );
    }
}

/**
 * 3.19.01 (S293) — BUG-98. PORTA UNICA dell etichetta di pacchetto.
 *
 * RP-PACCHETTO-ETICHETTA-BREVE-NON-INCREMENTALE (S240): RDACADEMY_PACKAGE e il
 * NOME del pacchetto, non la sua cronaca. La guardia sta A VALLE per scelta
 * dichiarata in quella regola — cosi una violazione si VEDE invece di essere
 * impedita in silenzio — ma fino alla 3.19.00 viveva in UN lettore su CINQUE,
 * e gli altri quattro emettevano la riga intera: 117.132 byte nel copia-testo
 * dello stato sistema, nel referto, nel pie di pagina del percorso di reset e
 * dentro una colonna VARCHAR(64).
 *
 * @param int  $max     tetto di caratteri. 64 = il limite VERO della colonna
 *                      license_activation.package (attivatore r.726).
 * @param bool $segnala se true accoda il motivo, perche un testo tagliato che
 *                      non dice di esserlo e un dato che mente.
 */
function rdacademy_pacchetto_etichetta( int $max = 120, bool $segnala = true ): string {
	$pkg = defined( 'RDACADEMY_PACKAGE' ) ? (string) RDACADEMY_PACKAGE : '';
	$pkg = trim( preg_replace( '/\s+/u', ' ', $pkg ) );
	if ( '' === $pkg ) {
		return '—';
	}
	if ( function_exists( 'mb_strlen' ) ? mb_strlen( $pkg ) <= $max : strlen( $pkg ) <= $max ) {
		return $pkg;
	}
	$corto = function_exists( 'mb_substr' ) ? mb_substr( $pkg, 0, $max ) : substr( $pkg, 0, $max );
	if ( ! $segnala ) {
		return $corto;
	}
	return $corto . '… [etichetta troncata: il pacchetto e un NOME, la cronaca sta nel CHANGELOG]';
}

/**
 * 3.46.04 (S530) BUG-190 — IL FRENO DELLE OPERAZIONI RAVVICINATE, UNA VOLTA SOLA.
 *
 * ⛔ Stava in `Rdacademy_Router::check_rate_limit()`, privato, e serviva quindici
 * chiamanti dentro quel file. L export del Calendario vive in un altra classe e
 * ne aveva bisogno: scriverne un secondo avrebbe voluto dire DUE freni da
 * tenere allineati a mano, e il giorno che si cambia la finestra a uno l altro
 * resta indietro in silenzio (`RP-UNA-REGOLA-SI-SCRIVE-UNA-VOLTA-SOLA`).
 * Il metodo del router e rimasto al suo posto per tutti i suoi chiamanti, ma
 * ora DELEGA qui: il comportamento e identico, la verita e una.
 *
 * @param string $chiave  identifica il contatore (di solito azione + utente)
 * @param int    $max     quante volte si puo, dentro la finestra
 * @param int    $finestra secondi
 * @return bool true se si puo procedere
 */
function rdacademy_freno( string $chiave, int $max, int $finestra ): bool {
	if ( ! function_exists( 'get_transient' ) ) { return true; } // fuori da WP non si frena
	$k = 'rdacademy_rl_' . $chiave; // max 172 char — sotto il tetto di 191 dei transient
	$d = get_transient( $k );
	if ( $d === false ) {
		set_transient( $k, array( 'count' => 1, 'start' => time() ), $finestra );
		return true;
	}
	if ( (int) $d['count'] >= $max ) {
		return false;
	}
	$d['count'] = (int) $d['count'] + 1;
	set_transient( $k, $d, max( 1, $finestra - ( time() - (int) $d['start'] ) ) );
	return true;
}

/**
 * 0.8.7A (RF-BUG-023, owner 08/10 09:17: «nella comunicazione mantenere sempre la stessa linea») — I DUE LOGHI, UN POSTO
 * SOLO. Le porte d accesso (rdacademy_marchio_html, via layout.php) e la testata delle mail (Rdacademy_Password_Mail::testata)
 * leggono da qui QUALE logo di RelDoxAcademy e QUALE logo del cliente si mostrano: a sinistra il logo vero di RelDoxAcademy
 * (il lockup approvato, PNG), a destra il logo del cliente alla stessa altezza, sulla stessa riga.
 *
 * Logo del cliente (Impostazioni → Branding dalla 0.13.1A), lo stesso ordine delle porte: quello per tablet e computer, altrimenti quello
 * per il telefono. Per la posta ($posta = true) un SVG si salta (i client di posta non lo disegnano) e si passa al
 * successivo, fino alle copie per la barra. Vuoto = l azienda non ha caricato un logo: resta il solo RelDoxAcademy.
 *
 * @return array{prodotto:string,prodotto_alt:string,prodotto_w:int,prodotto_h:int,cliente:string,cliente_alt:string}
 */
function rdacademy_loghi( bool $posta = false ): array {
	$letti = array();   // una SELECT piccola a ogni chiamata, nessuna cache: un logo appena caricato vale subito
	if ( class_exists( 'Rdacademy_DB' ) ) {
		try {
			$rows = Rdacademy_DB::get_results(
				"SELECT chiave, valore FROM `" . RDACADEMY_TABLE_PREFIX . "impostazioni`
				  WHERE chiave IN ('branding_logo_url','branding_logo_mobile_url','branding_logo_barra_url','branding_logo_mobile_barra_url','ragione_sociale')"
			);
			foreach ( (array) $rows as $r ) { $letti[ (string) $r['chiave'] ] = trim( (string) $r['valore'] ); }
		} catch ( Throwable $e ) { /* nessun logo del cliente */ }
	}
	$ordine = $posta
		? array( 'branding_logo_url', 'branding_logo_mobile_url', 'branding_logo_barra_url', 'branding_logo_mobile_barra_url' )
		: array( 'branding_logo_url', 'branding_logo_mobile_url' );
	$cliente = '';
	foreach ( $ordine as $k ) {
		$u = (string) ( $letti[ $k ] ?? '' );
		if ( $u === '' ) continue;
		if ( $posta && preg_match( '/\.svgz?(\?|$)/i', $u ) ) continue;
		$cliente = $u;
		break;
	}
	return array(
		// 0.13.2A — per la posta il lockup senza i margini trasparenti ai lati (README-LOGO): la coppia della testata si centra
		// sul bordo visibile del marchio. Le porte restano sul file intero.
		'prodotto'     => RDACADEMY_PLUGIN_URL . 'public/img/logo/' . ( $posta ? 'mail/' : '' ) . 'reldox-academy-lockup.png?v=' . RDACADEMY_VERSION,
		'prodotto_alt' => RDACADEMY_PRODUCT,
		'prodotto_w'   => $posta ? 657 : 720,
		'prodotto_h'   => 182,
		'cliente'      => $cliente,
		'cliente_alt'  => (string) ( $letti['ragione_sociale'] ?? '' ),
	);
}

/**
 * NUOVO — IL MARCHIO DI PRODOTTO DELLE PAGINE D ACCESSO, UNA COPIA SOLA.
 * In RelDox le tre porte (login, recupera, imposta-password) stampavano il
 * lockup «RelDox — illumina il tuo lavoro», che dice il nome del CRM. Nel
 * guscio il marchio e il Sole del kit con il nome che viene da RDACADEMY_PRODUCT
 * (REGOLE § 4: nome di prodotto in teal). Una funzione e non tre <img>:
 * lo script di rinomina cambia la costante e le tre pagine seguono.
 */
function rdacademy_marchio_html( string $logo_installazione = '' ): string {
	/* 0.8.5A (owner 08/10 07:23-07:24, SPEC-CASSA § 0 punto 27) — DUE LOGHI SULLA STESSA RIGA. «Il logo reldox deve andare
	   a sinistra e sulla stessa linea a destra deve andare il logo del cliente» · «non ci deve andare la scritta RelDoxAcademy
	   ma il suo logo vero». A sinistra il LOGO APPROVATO (kit LOGO-RELDOXFOOD-v1 del 04/10: simbolo, wordmark e payoff),
	   rasterizzato a sfondo trasparente perché nelle porte il suo carattere non si carica; un filetto; a destra il logo
	   del cliente alla stessa altezza. Senza logo del cliente resta il logo RelDoxAcademy da solo, centrato. Sostituisce
	   «un simbolo solo» della 0.6.12A (logo del cliente al posto del Sole, sotto la scritta).
	   0.8.7A — il logo di RelDoxAcademy viene da rdacademy_loghi(): lo stesso della testata delle mail. */
	$con_logo = $logo_installazione !== '';
	$L        = rdacademy_loghi();
	return '<div class="rdacademy-login-marchio' . ( $con_logo ? ' con-logo' : '' ) . '">'
		. '<img class="rdacademy-marchio-prodotto" src="' . esc_url( $L['prodotto'] ) . '" alt="' . esc_attr( $L['prodotto_alt'] ) . '" width="' . (int) $L['prodotto_w'] . '" height="' . (int) $L['prodotto_h'] . '" decoding="async">'
		. ( $con_logo
			? '<span class="rdacademy-marchio-sep" aria-hidden="true"></span>'
			. '<img class="rdacademy-marchio-cliente" src="' . esc_url( $logo_installazione ) . '" alt="" decoding="async">'
			: '' )
		. '</div>';
}

/**
 * 0.6.12A — il logo dell installazione per le porte d accesso (login, recupero, password):
 * vuoto se l azienda non ha ancora caricato un logo (il segnaposto non va in porta).
 */
function rdacademy_logo_porta( string $src, bool $segnaposto ): string {
	return ( $segnaposto || $src === '' ) ? '' : $src;
}
