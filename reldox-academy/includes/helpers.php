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

if ( ! function_exists( 'rdacademy_icona' ) ) {
    /**
     * 0.1.04A — UN SOLO REGISTRO DELLE ICONE (SPEC-GRAFICA-RELDOX-v5 § 14): il disegno viene da
     * public/data/reldox-icone.json, copia identica del pezzo comune del nodo (comune/grafica/, v1.0).
     * ⛔ Un icona che manca non si disegna qui: si chiede al capo famiglia.
     */
    function rdacademy_icona( string $nome, string $classe = 'ico' ): string {
        static $reg = null;
        if ( $reg === null ) {
            $f   = RDACADEMY_PLUGIN_DIR . 'public/data/reldox-icone.json';
            $reg = is_readable( $f ) ? (array) json_decode( (string) file_get_contents( $f ), true ) : array();
        }
        if ( empty( $reg['icone'][ $nome ] ) || empty( $reg['svg'] ) ) return '';
        $svg = str_replace( '{interno}', $reg['icone'][ $nome ], $reg['svg'] );
        return str_replace( '<svg ', '<svg class="' . esc_attr( $classe ) . '" aria-hidden="true" focusable="false" ', $svg );
    }
}

if ( ! function_exists( 'rdacademy_nav_icon' ) ) {
    /** Le voci della cornice: il nome di casa → il nome del registro di famiglia. */
    function rdacademy_nav_icon( string $name ): string {
        $mappa = array(
            'dashboard' => 'griglia', 'home' => 'casa', 'log' => 'documento', 'users' => 'persone', 'user' => 'persona',
            'settings' => 'ingranaggio', 'table' => 'tabella', 'logout' => 'uscita', 'trash' => 'cestino', 'key' => 'chiave',
            'manuali' => 'guida', 'database' => 'cilindro',
        );
        $nome  = (string) apply_filters( 'rdacademy_nav_icon_nome', $mappa[ $name ] ?? $name, $name );
        return rdacademy_icona( $nome );
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
	   ma il suo logo vero». A sinistra il LOGO APPROVATO (kit del logo del prodotto: simbolo, wordmark e payoff),
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
