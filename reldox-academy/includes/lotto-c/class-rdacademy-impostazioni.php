<?php
/**
 * Rdacademy_Impostazioni — LE AZIONI DI IMPOSTAZIONI (canale dell app, livello `admin`).
 * =============================================================================
 * Copiate da Reldox_Router, RelDox CRD 3.55.09G: ajax_impostazioni_get
 * (r.15247), ajax_impostazioni_save (r.15262), ajax_branding_upload_logo
 * (r.15742), ajax_dbconfig_status (r.3609), ajax_dbconfig_test (r.3637),
 * ajax_pwd_service_probe (r.7845), ajax_db_reset (r.16985) con le utilita di
 * Reldox_Wipe_Scope che usa (live_tables, keep_set, residuo_righe,
 * verdetto_misura, may_wipe).
 *
 * CORREZIONI E DIFFERENZE, DICHIARATE:
 *  - ⛔ impostazioni_save PREPARATA (in RelDox real_escape_string concatenato)
 *    e con la chiave validata (^[a-z0-9_]{1,100}$). Le chiavi con PORTA
 *    PROPRIA non passano di qui (stessa forma di `Reldox_AI::e_propria()` in
 *    RelDox): la casella di servizio (mail_servizio_*) e il logo, che si scrive
 *    solo dal caricamento (da qui si puo solo svuotare).
 *  - ⛔ impostazioni_get era `auth` in RelDox e restituiva TUTTE le chiavi a
 *    qualunque utente collegato: qui e `admin` e la password della casella
 *    NON esce mai (ne cifrata ne in chiaro): esce solo presenza e forma.
 *  - NUOVO: mail_servizio_save — la casella di servizio che il lotto A legge
 *    (Rdacademy_Password_Mail::CHIAVI). In RelDox la casella era quella predefinita
 *    di un utente (CRM). La password si cifra a riposo con Rdacademy_Cifra
 *    (enc1:, il metodo delle sorgenti dati di RelDox); campo vuoto = non la cambio.
 *  - db_reset: un solo ambito (il BASE del guscio); niente ampiezza
 *    «anagrafiche», niente archivi del CRM, niente contatore offerte. Restano:
 *    allowlist dinamica (SHOW TABLES − KEEP dal filtro unico
 *    Rdacademy_DB::reset_keep_tables), TRUNCATE + OPTIMIZE, MISURA DI CONFERMA a
 *    tre stati, conferma con la parola dichiarata nel corpo (NUOVO, al posto
 *    delle due window.confirm di RelDox).
 *
 * RelDoxAcademy (Formazione F009, 18/11/2026, analisi ed. 9) — RIDOTTA: tolti i rami del servizio
 * mensa (Santo Patrono, festività), dei recapiti della pagina personale e la porta propria della
 * cartella dati. Restano casella di servizio, logo, configurazione del database e azzeramento.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Impostazioni {

	/** Le chiavi con porta propria: impostazioni_save non le scrive. */
	public static function e_propria( string $k ): bool {
		// 0.6.09A — tutta la famiglia mail_servizio_* (anche IMAP, cartella e attestati):
		// l attestato di verifica non si scrive MAI dal salvataggio generico.
		if ( strpos( $k, 'mail_servizio_' ) === 0 ) return true;
		// Altre porte proprie le dichiara chi le porta, con il filtro.
		foreach ( (array) apply_filters( 'rdacademy_impostazioni_proprie', array() ) as $pre ) { if ( $pre !== '' && strpos( $k, (string) $pre ) === 0 ) return true; }
		return false;
	}

	private static function guardia( ?array $cur ): array {
		if ( ! $cur || ! Rdacademy_Router::user_can_admin( $cur ) ) wp_send_json_error( 'Accesso negato', 403 );
		return $cur;
	}

	/** Lettura di tutte le impostazioni, il segreto ridotto a presenza e forma. */
	public static function leggi_tutte(): array {
		$p   = RDACADEMY_TABLE_PREFIX;
		$out = array();
		foreach ( Rdacademy_DB::get_results( "SELECT chiave, valore FROM `{$p}impostazioni` ORDER BY chiave ASC" ) ?: array() as $r ) {
			$out[ (string) $r['chiave'] ] = (string) $r['valore'];
		}
		$pk = Rdacademy_Password_Mail::CHIAVI['password'];
		$pw = $out[ $pk ] ?? '';
		unset( $out[ $pk ] ); // ⛔ un segreto non esce dalla lettura (REGOLE § 5)
		$out['mail_servizio_password_forma'] = Rdacademy_Cifra::forma( $pw );
		return $out;
	}

	public static function ajax_impostazioni_get( ?array $cur ): void {
		self::guardia( $cur );
		wp_send_json_success( self::leggi_tutte() );
	}

	private static function scrivi( string $k, string $v ): bool {
		$p = RDACADEMY_TABLE_PREFIX;
		Rdacademy_DB::clear_last_error();
		Rdacademy_DB::execute(
			"INSERT INTO `{$p}impostazioni` (chiave, valore) VALUES (?, ?) ON DUPLICATE KEY UPDATE valore = VALUES(valore)",
			array( substr( $k, 0, 100 ), substr( $v, 0, 65000 ) ), 'ss'
		);
		return Rdacademy_DB::last_error() === '';
	}

	public static function ajax_impostazioni_save( ?array $cur ): void {
		$cur  = self::guardia( $cur );
		$data = json_decode( wp_unslash( (string) ( $_POST['data'] ?? '{}' ) ), true );
		if ( ! is_array( $data ) ) wp_send_json_error( 'Dati non leggibili.' );
		$scritte = array();
		foreach ( $data as $chiave => $valore ) {
			$k = (string) $chiave;
			if ( ! preg_match( '/^[a-z0-9_]{1,100}$/', $k ) ) wp_send_json_error( 'Chiave non valida: ' . $k );
			if ( self::e_propria( $k ) ) continue; // porta propria (mail_servizio_save)
			if ( is_array( $valore ) || is_object( $valore ) ) wp_send_json_error( 'Valore non valido per ' . $k );
			$v = sanitize_textarea_field( (string) $valore );
			if ( $k === 'branding_logo_url' && $v !== '' ) continue; // il logo si scrive solo dal caricamento
			if ( $k === 'branding_logo_barra_url' ) continue;          // 0.7.0A — la copia per la barra la scrive solo il caricamento
			// 0.8.0A — il logo per il telefono: come l altro, si scrive solo dal caricamento; vuoto = toglierlo (con la sua copia).
			if ( $k === 'branding_logo_mobile_barra_url' ) continue;
			if ( $k === 'branding_logo_mobile_url' && $v !== '' ) continue;
			if ( $k === 'branding_logo_mobile_url' && $v === '' ) self::scrivi( 'branding_logo_mobile_barra_url', '' );
			// 0.7.0A — togliendo il logo va via anche la sua copia per la barra.
			if ( $k === 'branding_logo_url' && $v === '' ) self::scrivi( 'branding_logo_barra_url', '' );
			if ( $k === 'email' && $v !== '' && ! is_email( $v ) ) wp_send_json_error( 'L\'email dell\'azienda non è valida.' );
			if ( ! self::scrivi( $k, $v ) ) wp_send_json_error( 'Impostazione non salvata: ' . $k );
			$scritte[] = $k;
		}
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'impostazioni', 'save', 'Impostazioni salvate: ' . implode( ', ', $scritte ) );
		wp_send_json_success( array( 'ok' => true, 'scritte' => $scritte ) );
	}

	/**
	 * La casella di servizio — 0.6.09A (RF-BUG-016): il modulo della casella di
	 * RelDox CRD 3.56.06, solo IMAP. Etichetta (= nome mostrato come mittente),
	 * Email (= mittente), Username, Password; IMAP host/porta/SSL; SMTP
	 * host/porta/SSL; archiviazione degli invii.
	 * La password, se arriva vuota, NON si cambia (regola delle caselle del CRD).
	 * ⛔ Il salvataggio non tocca l attestato: lo spegne da solo l impronta,
	 * se i dati della spedizione sono cambiati (Rdacademy_Casella::stato).
	 */
	public static function ajax_mail_servizio_save( ?array $cur ): void {
		$cur = self::guardia( $cur );
		self::salva_casella( $cur );
		$c  = Rdacademy_Casella::leggi();
		wp_send_json_success( self::risposta_casella( $c ) );
	}

	/** Il salvataggio vero: lo usano «Salva» e, prima di provare, «Testa» e «Prova la spedizione». */
	private static function salva_casella( array $cur ): void {
		$K   = Rdacademy_Password_Mail::CHIAVI;
		$KI  = Rdacademy_Casella::CHIAVI_IMAP;
		$in  = static function ( string $k ): string {
			return trim( sanitize_text_field( wp_unslash( (string) ( $_POST[ $k ] ?? '' ) ) ) );
		};
		$sic_ok = static function ( string $v, string $def ): string {
			$v = sanitize_key( $v );
			if ( $v === 'starttls' ) $v = 'tls';
			if ( $v === 'none' ) $v = 'nessuna';
			return in_array( $v, array( 'ssl', 'tls', 'nessuna' ), true ) ? $v : $def;
		};
		$porta_ok = static function ( string $v, string $cosa ): string {
			if ( $v !== '' && ( ! ctype_digit( $v ) || (int) $v < 1 || (int) $v > 65535 ) ) {
				wp_send_json_error( 'La porta ' . $cosa . ' deve essere un numero fra 1 e 65535.' );
			}
			return $v;
		};
		$host  = $in( 'host' );
		$porta = $porta_ok( $in( 'porta' ), 'SMTP' );
		$sic   = $sic_ok( (string) ( $_POST['sicurezza'] ?? 'tls' ), 'tls' );
		$utent = $in( 'utente' );
		$mitt  = sanitize_email( wp_unslash( (string) ( $_POST['mittente'] ?? '' ) ) );
		$nome  = $in( 'nome' );
		$pwd   = (string) wp_unslash( $_POST['password'] ?? '' );
		$svuota_pwd = ! empty( $_POST['svuota_password'] );
		$ihost = $in( 'imap_host' );
		$iport = $porta_ok( $in( 'imap_porta' ), 'IMAP' );
		$isic  = $sic_ok( (string) ( $_POST['imap_sicurezza'] ?? 'ssl' ), 'ssl' );
		$salva_inv = ( (string) ( $_POST['salva_inviati'] ?? '1' ) === '1' ) ? '1' : '0';
		$cartella  = $in( 'cartella' );

		$tutto_vuoto = ( $host === '' && $mitt === '' && $utent === '' && $ihost === '' );
		if ( ! $tutto_vuoto ) {
			if ( $mitt === '' || ! is_email( $mitt ) ) wp_send_json_error( 'Indica un indirizzo email valido per la casella.' );
			if ( $host === '' ) wp_send_json_error( 'Indica il server SMTP della casella (per Aruba: smtps.aruba.it).' );
			if ( $salva_inv === '1' && $ihost === '' ) wp_send_json_error( 'Per salvare i messaggi nella cartella «Inviata» serve il server IMAP (per Aruba: imaps.aruba.it), oppure spegni l\'archiviazione.' );
		}

		$ok = self::scrivi( $K['host'], $host ) && self::scrivi( $K['porta'], $porta ) && self::scrivi( $K['sicurezza'], $sic )
		   && self::scrivi( $K['utente'], $utent ) && self::scrivi( $K['mittente'], $mitt ) && self::scrivi( $K['nome'], $nome )
		   && self::scrivi( $KI['imap_host'], $ihost ) && self::scrivi( $KI['imap_porta'], $iport ) && self::scrivi( $KI['imap_sicurezza'], $isic )
		   && self::scrivi( $KI['salva_inviati'], $salva_inv ) && self::scrivi( $KI['cartella'], $cartella );
		if ( $ok && ( $svuota_pwd || $tutto_vuoto ) ) {
			$ok = self::scrivi( $K['password'], '' );
		} elseif ( $ok && $pwd !== '' ) {
			$chiusa = Rdacademy_Cifra::chiudi( $pwd );
			if ( $chiusa === '' ) wp_send_json_error( 'Cifratura non riuscita: la password non è stata salvata.' );
			$ok = self::scrivi( $K['password'], $chiusa );
		}
		if ( ! $ok ) wp_send_json_error( 'Casella di servizio non salvata: ' . Rdacademy_DB::last_error() );
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'impostazioni', 'mail_servizio',
			'Casella di servizio salvata' . ( $pwd !== '' ? ' (password cambiata)' : '' ) );
	}

	/** Cio che la pagina deve sapere della casella: stato, attestati, forma della password. */
	private static function risposta_casella( array $c ): array {
		$st = Rdacademy_Casella::stato( $c );
		return array(
			'compilata'       => $st['compilata'],
			'verificata'      => $st['verificata'],
			'quando'          => Rdacademy_Casella::in_parole( $st['quando'] ),
			'a'               => $st['a'],
			'imap_verificata' => $st['imap_verificata'],
			'imap_quando'     => Rdacademy_Casella::in_parole( $st['imap_quando'] ),
			'motivo'          => $st['motivo'],
			'password'        => Rdacademy_Cifra::forma( $c['password'] ),
			// compatibilita con la forma di prima
			'configurata'     => $st['verificata'],
		);
	}

	/** «Testa» — salva e prova l IMAP: collegamento, accesso, cartelle. */
	public static function ajax_mail_servizio_prova_imap( ?array $cur ): void {
		$cur = self::guardia( $cur );
		self::salva_casella( $cur );
		$c = Rdacademy_Casella::leggi();
		@set_time_limit( 90 );
		$e = Rdacademy_Casella::prova_imap( $c );
		if ( ! empty( $e['ok'] ) ) {
			self::scrivi( Rdacademy_Casella::CHIAVE_VERIFICA_IMAP, (string) wp_json_encode( array(
				'impronta' => Rdacademy_Casella::impronta_imap( $c ), 'quando' => gmdate( 'Y-m-d H:i:s' ), 'da' => (int) $cur['id'],
			) ) );
			$c = Rdacademy_Casella::leggi();
		}
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'impostazioni', 'mail_servizio_imap', 'Prova IMAP della casella di servizio: ' . ( ! empty( $e['ok'] ) ? 'riuscita' : 'non riuscita' ) );
		wp_send_json_success( array(
			'esito'    => $e,
			'testo'    => Rdacademy_Casella::testo_esito( '«Testa» IMAP della casella di servizio', $c, $e ),
			'aiuto'    => empty( $e['ok'] ) ? Rdacademy_Casella::aiuto( $c['imap_host'], $c['host'] ) : null,
			'casella'  => self::risposta_casella( $c ),
		) );
	}

	/**
	 * «Prova la spedizione» — salva e spedisce DAVVERO un messaggio di prova,
	 * passo per passo. Riuscita = casella VERIFICATA per QUESTI dati (impronta).
	 */
	public static function ajax_mail_servizio_prova_invio( ?array $cur ): void {
		$cur = self::guardia( $cur );
		self::salva_casella( $cur );
		$a = sanitize_email( wp_unslash( (string) ( $_POST['prova_a'] ?? '' ) ) );
		$c = Rdacademy_Casella::leggi();
		@set_time_limit( 120 );
		$e = Rdacademy_Casella::prova_spedizione( $c, $a );
		if ( ! empty( $e['ok'] ) ) {
			self::scrivi( Rdacademy_Casella::CHIAVE_VERIFICA, (string) wp_json_encode( array(
				'impronta' => Rdacademy_Casella::impronta_smtp( $c ), 'quando' => gmdate( 'Y-m-d H:i:s' ), 'a' => $a, 'da' => (int) $cur['id'],
			) ) );
		} else {
			// Una prova fallita spegne l attestato: la casella non e quella che spedisce.
			self::scrivi( Rdacademy_Casella::CHIAVE_VERIFICA, '' );
		}
		$c = Rdacademy_Casella::leggi();
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'impostazioni', 'mail_servizio_prova', 'Prova di spedizione della casella di servizio a ' . $a . ': ' . ( ! empty( $e['ok'] ) ? 'riuscita' : 'non riuscita' ) );
		wp_send_json_success( array(
			'esito'   => $e,
			'testo'   => Rdacademy_Casella::testo_esito( '«Prova la spedizione» della casella di servizio', $c, $e ),
			'aiuto'   => empty( $e['ok'] ) ? Rdacademy_Casella::aiuto( $c['imap_host'], $c['host'] ) : null,
			'casella' => self::risposta_casella( $c ),
		) );
	}

	/** «Rileva» — le cartelle vere della casella salvata, con la proposta per «Inviata». */
	public static function ajax_mail_servizio_rileva( ?array $cur ): void {
		$cur = self::guardia( $cur );
		$c = Rdacademy_Casella::leggi();
		if ( $c['imap_host'] === '' ) wp_send_json_error( 'Indica e salva il server IMAP, poi «Testa», poi «Rileva».' );
		@set_time_limit( 90 );
		$e = Rdacademy_Casella::prova_imap( $c );
		if ( empty( $e['ok'] ) ) {
			$ultimo = end( $e['passi'] );
			wp_send_json_error( 'Collegamento IMAP non riuscito: ' . ( $ultimo ? $ultimo['titolo'] . ' — ' . ( $ultimo['server'] !== '' ? $ultimo['server'] : $ultimo['consiglio'] ) : 'errore' ) . '. Usa «Testa» per il dettaglio.' );
		}
		wp_send_json_success( array( 'proposed' => $e['proposta'], 'folders' => $e['cartelle'] ) );
	}

	/** «Come si configura» — la scheda d aiuto. */
	public static function ajax_mail_servizio_aiuto( ?array $cur ): void {
		self::guardia( $cur );
		$ih = sanitize_text_field( wp_unslash( (string) ( $_POST['imap_host'] ?? '' ) ) );
		$sh = sanitize_text_field( wp_unslash( (string) ( $_POST['host'] ?? '' ) ) );
		wp_send_json_success( array( 'help' => Rdacademy_Casella::aiuto( $ih, $sh ) ) );
	}

	/** Copiato (r.15742): validazione SVG sul file intero, MIME reale per le immagini. */
	public static function ajax_branding_upload_logo( ?array $cur ): void {
		$cur = self::guardia( $cur );
		if ( empty( $_FILES['logo']['tmp_name'] ) || ( $_FILES['logo']['error'] ?? 0 ) !== UPLOAD_ERR_OK ) {
			wp_send_json_error( 'Nessun file ricevuto o errore di upload.' );
		}
		$file = $_FILES['logo'];
		if ( (int) $file['size'] > 2 * 1024 * 1024 ) {
			wp_send_json_error( 'Il file supera 2 MB. Comprimi l\'immagine prima di caricarla.' );
		}
		$ext = strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'png', 'jpg', 'jpeg', 'webp', 'svg' ), true ) ) {
			wp_send_json_error( 'Formato non consentito. Usa PNG, JPG, WEBP o SVG.' );
		}
		if ( $ext === 'svg' ) {
			$content = @file_get_contents( $file['tmp_name'] );
			if ( $content === false || stripos( $content, '<svg' ) === false ) {
				wp_send_json_error( 'Il file non sembra un SVG valido.' );
			}
			$vietati = array(
				'<script', '<foreignobject', '<iframe', '<embed', '<object',
				'<animate', '<set ', '<handler', '<!entity',
				'javascript:', 'data:text/html',
			);
			$basso = strtolower( html_entity_decode( $content, ENT_QUOTES, 'UTF-8' ) );
			foreach ( $vietati as $v ) {
				if ( strpos( $basso, $v ) !== false ) {
					wp_send_json_error( 'SVG non consentito: contiene codice eseguibile o riferimenti attivi.' );
				}
			}
			if ( preg_match( '/\son[a-z]+\s*=/i', $basso ) ) {
				wp_send_json_error( 'SVG con gestori di evento non consentito per ragioni di sicurezza.' );
			}
			if ( preg_match( '#(?:href|xlink:href|src)\s*=\s*["\']?\s*(?:https?:)?//#i', $basso ) ) {
				wp_send_json_error( 'SVG con riferimenti esterni non consentito per ragioni di sicurezza.' );
			}
		} else {
			if ( @getimagesize( $file['tmp_name'] ) === false ) {
				wp_send_json_error( 'Il file non è una immagine valida.' );
			}
		}

		$up = wp_upload_dir();
		if ( empty( $up['basedir'] ) || empty( $up['baseurl'] ) ) {
			wp_send_json_error( 'Cartella uploads di WordPress non disponibile.' );
		}
		$dir = trailingslashit( $up['basedir'] ) . 'rdacademy-branding';
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			wp_send_json_error( 'Impossibile creare la cartella di destinazione.' );
		}
		// 0.8.0A (owner 17:07) — DUE LOGHI: `quale` = computer (tablet e computer, orizzontale; le chiavi di sempre) |
		// mobile (telefono, quadrato). I file hanno un nome per famiglia: la pulizia di uno non tocca l altro.
		$mob      = ( sanitize_key( (string) ( $_POST['quale'] ?? 'computer' ) ) === 'mobile' );
		$radice   = $mob ? 'branding_logomob_' : 'branding_logo_';
		$filename = $radice . time() . '.' . $ext;
		$dest     = trailingslashit( $dir ) . $filename;
		$url      = trailingslashit( $up['baseurl'] ) . 'rdacademy-branding/' . $filename;
		if ( ! move_uploaded_file( $file['tmp_name'], $dest ) ) {
			wp_send_json_error( 'Errore di scrittura del file caricato.' );
		}
		if ( ! self::scrivi( $mob ? 'branding_logo_mobile_url' : 'branding_logo_url', $url ) ) {
			wp_send_json_error( 'Errore DB nel salvataggio del logo.' );
		}
		// 0.7.0A (owner 06/10 14:56) — la copia OTTIMIZZATA per la barra del telefono: alta 96 px (due volte i 32-48
		// della barra, nitida sugli schermi densi), larghezza in proporzione. Un SVG si usa com è; se l editor di
		// immagini di WordPress manca o fallisce, la barra usa il logo intero (la pagina lo rimpicciolisce).
		$barra = self::copia_barra( $dest, $ext, $dir, $up['baseurl'] );
		self::scrivi( $mob ? 'branding_logo_mobile_barra_url' : 'branding_logo_barra_url', $barra['url'] !== '' ? $barra['url'] : $url );
		// la pulizia: solo i file della STESSA famiglia (branding_logo_<numero> o branding_logomob_<numero>)
		foreach ( (array) glob( trailingslashit( $dir ) . $radice . '[0-9]*' ) as $old ) {
			if ( basename( (string) $old ) !== $filename && basename( (string) $old ) !== $barra['file'] ) {
				@unlink( $old );
			}
		}
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'impostazioni', 'branding_logo', $mob ? 'Logo per il telefono caricato' : 'Logo per tablet e computer caricato' );
		wp_send_json_success( array( 'url' => $url, 'filename' => $filename, 'size' => (int) $file['size'], 'barra' => $barra ) );
	}

	/**
	 * 0.7.0A — la copia del logo per la barra: alta 96 px, PNG (trasparenza conservata). Niente ingrandimenti:
	 * un logo già basso si usa com è.
	 * @return array{url:string,file:string,w:int,h:int}
	 */
	public static function copia_barra( string $dest, string $ext, string $dir, string $baseurl ): array {
		$vuoto = array( 'url' => '', 'file' => '', 'w' => 0, 'h' => 0 );
		if ( $ext === 'svg' ) return $vuoto;
		$dim = @getimagesize( $dest );
		if ( ! $dim || (int) $dim[1] <= 0 ) return $vuoto;
		if ( (int) $dim[1] <= 96 ) return $vuoto;
		if ( ! function_exists( 'wp_get_image_editor' ) ) require_once ABSPATH . 'wp-includes/media.php';
		$ed = wp_get_image_editor( $dest );
		if ( is_wp_error( $ed ) ) return $vuoto;
		$w = max( 1, (int) round( (int) $dim[0] * 96 / (int) $dim[1] ) );
		if ( is_wp_error( $ed->resize( $w, 96, false ) ) ) return $vuoto;
		$nome = pathinfo( $dest, PATHINFO_FILENAME ) . '_barra.png';
		$sv   = $ed->save( trailingslashit( $dir ) . $nome, 'image/png' );
		if ( is_wp_error( $sv ) || empty( $sv['path'] ) ) return $vuoto;
		return array( 'url' => trailingslashit( $baseurl ) . 'rdacademy-branding/' . basename( (string) $sv['path'] ), 'file' => basename( (string) $sv['path'] ),
			'w' => (int) ( $sv['width'] ?? $w ), 'h' => (int) ( $sv['height'] ?? 96 ) );
	}

	/** Copiato (r.3609): get_public() rimuove `pass`. */
	public static function ajax_dbconfig_status( ?array $cur ): void {
		self::guardia( $cur );
		wp_send_json_success( Rdacademy_DB_Config::get_public() );
	}

	/** Copiato (r.3637): connessione ISOLATA, SELECT 1, password scrubbata dal messaggio. */
	public static function ajax_dbconfig_test( ?array $cur ): void {
		self::guardia( $cur );
		$result = Rdacademy_DB::try_connect();
		if ( ! $result['ok'] ) {
			$cfg           = Rdacademy_DB_Config::get();
			$error_message = (string) $result['error_message'];
			if ( ! empty( $cfg['pass'] ) ) {
				$error_message = str_replace( $cfg['pass'], '[REDACTED]', $error_message );
			}
			wp_send_json_error( array( 'message' => $error_message, 'latency_ms' => $result['latency_ms'] ), 200 );
		}
		$conn     = $result['connection'];
		$query_ok = false;
		$start_q  = microtime( true );
		try {
			$res = @$conn->query( 'SELECT 1' );
			if ( $res !== false ) {
				$query_ok = true;
				if ( $res instanceof mysqli_result ) $res->free();
			}
		} catch ( mysqli_sql_exception $e ) {
			$query_ok = false;
		}
		$query_ms = round( ( microtime( true ) - $start_q ) * 1000.0, 2 );
		$versione = (string) ( $conn->server_info ?? '' );
		$conn->close();
		if ( ! $query_ok ) {
			wp_send_json_error( array(
				'message'    => 'Connessione aperta ma la query di test (SELECT 1) è fallita. Verifica i permessi dell\'utente DB.',
				'latency_ms' => $result['latency_ms'],
				'query_ms'   => $query_ms,
			), 200 );
		}
		wp_send_json_success( array(
			'message'    => 'Connessione OK',
			'latency_ms' => $result['latency_ms'],
			'query_ms'   => $query_ms,
			'server'     => $versione,
		) );
	}

	/* ===================================================================== *
	 *  PERICOLO — copiato da Reldox_Wipe_Scope + ajax_db_reset, un ambito solo
	 * ===================================================================== */

	const MISURA_CONFERMATO   = 'CONFERMATO';
	const MISURA_RESIDUO      = 'RESIDUO';
	const MISURA_NON_MISURATO = 'NON MISURATO';

	/** La parola che la conferma in pagina chiede di scrivere (NUOVO, al posto di window.confirm). */
	const PAROLA_CONFERMA = 'AZZERA';

	/** Copiato da may_wipe() con CAPABILITY = 'admin': la stessa autorita della mappa e della vista. */
	public static function may_wipe( ?array $user ): bool {
		return is_array( $user ) && ! empty( $user['id'] ) && Rdacademy_Router::user_can_admin( $user );
	}

	/**
	 * Copiato da Reldox_Wipe_Scope::live_tables(): le tabelle del prodotto REALMENTE presenti.
	 * CORREZIONE: query preparata su information_schema (era SHOW TABLES LIKE con
	 * real_escape_string concatenato, e il «_» del prefisso valeva come jolly).
	 */
	public static function live_tables( string &$err = '' ): array {
		$err  = '';
		$like = str_replace( array( '\\', '_', '%' ), array( '\\\\', '\\_', '\\%' ), RDACADEMY_TABLE_PREFIX ) . '%';
		$rows = Rdacademy_DB::get_results(
			"SELECT table_name AS t FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE ? ORDER BY table_name",
			array( $like ), 's'
		);
		$e = Rdacademy_DB::last_sql_error();
		if ( $e !== null ) {
			$err = (string) ( $e['error'] ?? 'interrogazione fallita' );
			return array();
		}
		$out = array_map( static function ( $r ) { return (string) ( $r['t'] ?? reset( $r ) ); }, (array) $rows );
		sort( $out );
		return $out;
	}

	/** Copiato da keep_set(): o la fonte risponde, o il percorso distruttivo si ferma. */
	public static function keep_set(): array {
		$base = Rdacademy_DB::reset_keep_tables();
		if ( $base === array() ) {
			throw new RuntimeException( 'Elenco di preservazione vuoto: si cancellerebbe anche la configurazione.' );
		}
		$out = array();
		foreach ( $base as $b ) { $out[ RDACADEMY_TABLE_PREFIX . (string) $b ] = true; }
		return $out;
	}

	/** Lo split che vista e azione leggono: UNA fonte, due lettori. */
	public static function split( string &$err = '' ): array {
		$live = self::live_tables( $err );
		$keep = self::keep_set();
		$sv = array(); $pr = array();
		foreach ( $live as $t ) { if ( isset( $keep[ $t ] ) ) { $pr[] = $t; } else { $sv[] = $t; } }
		return array( 'svuota' => $sv, 'preserva' => $pr, 'live' => $live );
	}

	/** Copiato da residuo_righe(): SOLO le tabelle con righe residue. */
	public static function residuo_righe( array $tables, string &$err = '' ): array {
		$err  = '';
		$conn = Rdacademy_DB::get_connection();
		$out  = array(); $muti = array();
		foreach ( $tables as $t ) {
			try {
				$res = @$conn->query( 'SELECT COUNT(*) FROM `' . str_replace( '`', '``', (string) $t ) . '`' );
				if ( ! ( $res instanceof mysqli_result ) ) { $muti[] = $t; continue; }
				$row = $res->fetch_row(); $res->free();
				$n = ( is_array( $row ) && isset( $row[0] ) ) ? (int) $row[0] : -1;
				if ( $n < 0 ) { $muti[] = $t; } elseif ( $n > 0 ) { $out[ $t ] = $n; }
			} catch ( Throwable $e ) {
				$muti[] = $t;
			}
		}
		if ( $muti ) $err = 'misura non riuscita su ' . count( $muti ) . ' tabelle: ' . implode( ', ', array_slice( $muti, 0, 8 ) );
		ksort( $out );
		return $out;
	}

	/** Copiato senza modifiche: cieco vince su sporco, sporco vince su pulito. */
	public static function verdetto_misura( int $cieche, int $sporche, int $misurate ): string {
		if ( $cieche > 0 )   return self::MISURA_NON_MISURATO;
		if ( $sporche > 0 )  return self::MISURA_RESIDUO;
		if ( $misurate > 0 ) return self::MISURA_CONFERMATO;
		return self::MISURA_NON_MISURATO;
	}

	public static function ajax_db_reset( ?array $cur ): void {
		if ( ! self::may_wipe( $cur ) ) wp_send_json_error( 'Azzeramento riservato agli amministratori.', 403 );
		// NUOVO: la seconda chiave nel corpo, come il «conferma» della barra delle Tabelle Funzionali.
		if ( strtoupper( trim( (string) ( $_POST['conferma'] ?? '' ) ) ) !== self::PAROLA_CONFERMA ) {
			wp_send_json_error( 'Conferma mancante: scrivi ' . self::PAROLA_CONFERMA . ' per azzerare.', 400 );
		}
		try {
			$err   = '';
			$split = self::split( $err );
		} catch ( Throwable $e ) {
			wp_send_json_error( 'Elenco di preservazione non costruibile, nessuna tabella è stata toccata: ' . $e->getMessage() );
		}
		if ( $err !== '' ) {
			wp_send_json_error( 'Le tabelle del BASE non sono leggibili, nessuna tabella è stata toccata: ' . $err );
		}
		$conn = Rdacademy_DB::get_connection();
		$n = 0; $errs = array();
		do_action( 'rdacademy_db_reset_prima', $split['svuota'] ); // LOTTO-D: i lotti leggono ciò che serve per togliere i FILE (REGOLE § 3.9)
		$conn->query( 'SET FOREIGN_KEY_CHECKS = 0' );
		foreach ( $split['svuota'] as $full ) {
			if ( $conn->query( 'TRUNCATE TABLE `' . str_replace( '`', '``', $full ) . '`' ) ) { $n++; }
			else { $errs[] = $full . ': ' . $conn->error; }
		}
		$conn->query( 'SET FOREIGN_KEY_CHECKS = 1' );
		do_action( 'rdacademy_db_reset_dopo', $split['svuota'] ); // LOTTO-D: «un distruttivo che cancella righe cancella anche i file»
		foreach ( $split['svuota'] as $full ) {
			$r = $conn->query( 'OPTIMIZE TABLE `' . str_replace( '`', '``', $full ) . '`' );
			if ( $r instanceof mysqli_result ) $r->free();
		}
		$mis_err = '';
		$residuo = self::residuo_righe( $split['svuota'], $mis_err );
		$verdetto = self::verdetto_misura( $mis_err !== '' ? 1 : 0, $residuo ? 1 : 0, count( $split['svuota'] ) );
		if ( $errs ) error_log( '[RelDoxAcademy] db_reset errors: ' . implode( '; ', $errs ) );
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'impostazioni', 'db_reset',
			'Azzeramento dati operativi: ' . $n . ' tabelle svuotate, misura ' . $verdetto );
		wp_send_json_success( array(
			'tabelle_svuotate' => $n,
			'tabelle_vive'     => count( $split['live'] ),
			'tabelle_kept'     => count( $split['preserva'] ),
			'svuotate'         => $split['svuota'],
			'errori'           => $errs,
			'misura'           => array(
				'verdetto' => $verdetto,
				'misurate' => count( $split['svuota'] ),
				'residuo'  => $residuo,
				'errore'   => $mis_err,
			),
		) );
	}
}
