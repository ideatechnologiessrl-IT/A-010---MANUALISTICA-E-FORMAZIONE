<?php
/**
 * Rdacademy_Me — IL SELF-SERVICE DELLE PREFERENZE (canale dell app, livello `auth`).
 * =============================================================================
 * Copiato da Reldox_Router, RelDox CRD 3.55.09G: me_uid (r.7336),
 * ajax_me_profilo_get (r.7353) + stato_scadenza (r.7466),
 * ajax_me_password_change (r.7555), ajax_me_accessi_get (r.7705) +
 * dispositivo_leggibile (r.7744).
 *
 * ⛔ L IDENTITA VIENE DALLA SESSIONE E IL MODULO NON LA PORTA
 * (RP-IL-SELF-SERVICE-NON-TOCCA-UN-ALTRO, REGOLE § 3.4): nessun metodo qui
 * legge `user_id`, `uid` o `target_user_id` dal POST. Il $user che il router
 * passa e quello della sessione, e me_uid() lo rilegge comunque da Rdacademy_Auth.
 *
 * TAGLI: caselle (me_caselle_save, me_casella_test), firma (me_firma_save),
 * cartella ARD, aziende dell albero, spesa AI — tutti CRM. Il profilo esce
 * senza caselle e senza firma.
 * CORREZIONE: me_accessi_get non fa piu `SHOW TABLES` concatenato (la tabella
 * nasce con il nucleo); gli istanti si rendono dal fuso UTC della colonna.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Me {

	/** Copiato: l id non arriva dal POST e non ci arrivera mai. */
	private static function me_uid(): int {
		$cur = Rdacademy_Auth::get_current_user();
		if ( ! $cur || empty( $cur['id'] ) ) {
			wp_send_json_error( 'Sessione scaduta. Effettuare nuovamente il login.', 401 );
		}
		return (int) $cur['id'];
	}

	public static function ajax_me_profilo_get( ?array $user ): void {
		$uid = self::me_uid();
		$p   = RDACADEMY_TABLE_PREFIX;
		$u   = Rdacademy_DB::get_row(
			"SELECT id, first_name, last_name, login, email, tipo, password_expires_at, last_login
			   FROM `{$p}users` WHERE id = ? LIMIT 1",
			array( $uid ), 'i'
		);
		if ( ! $u ) wp_send_json_error( 'Utente non trovato.' );
		wp_send_json_success( array(
			'regola'   => Rdacademy_Password_Policy::descrittore( (array) $u ),
			'scadenza' => self::stato_scadenza( (string) ( $u['password_expires_at'] ?? '' ) ),
		) );
	}

	/** Copiato senza modifiche di logica: funzione PURA. */
	private static function stato_scadenza( string $data ): array {
		$data = trim( $data );
		if ( $data === '' || $data === '0000-00-00' ) {
			return array( 'obbligo' => false, 'data' => '', 'giorni' => null, 'scaduta' => false );
		}
		$t = strtotime( $data . ' 23:59:59' );
		if ( ! $t ) {
			return array( 'obbligo' => false, 'data' => '', 'giorni' => null, 'scaduta' => false );
		}
		$giorni = (int) floor( ( $t - time() ) / DAY_IN_SECONDS );
		return array(
			'obbligo' => true,
			'data'    => date_i18n( 'd/m/Y', $t ),
			'giorni'  => $giorni,
			'scaduta' => ( $t < time() ),
		);
	}

	/**
	 * Il mio accesso — cambio password con la VECCHIA richiesta (copiato).
	 * ⛔ LE SESSIONI CADONO, TRANNE LA PROPRIA: si cancellano TUTTE e se ne apre
	 * subito una nuova per il chiamante (RP-CAMBIO-PASSWORD-CHIUDE-LE-SESSIONI).
	 */
	public static function ajax_me_password_change( ?array $user ): void {
		$uid = self::me_uid();
		$p   = RDACADEMY_TABLE_PREFIX;

		// Il freno del router gira SOLO sul ramo `write`: qui il permesso e `auth`, quindi si chiama A MANO.
		if ( ! rdacademy_freno( 'mepwd_' . $uid, 10, 900 ) ) {
			wp_send_json_error( 'Troppi tentativi. Riprova fra qualche minuto.', 429 );
		}

		$attuale = (string) wp_unslash( $_POST['password_attuale'] ?? '' );
		$nuova   = (string) wp_unslash( $_POST['password_nuova']   ?? '' );
		$ripeti  = (string) wp_unslash( $_POST['password_ripeti']  ?? '' );

		if ( $attuale === '' || $nuova === '' ) wp_send_json_error( 'Compila tutti i campi.' );
		if ( $nuova !== $ripeti )               wp_send_json_error( 'Le due password non coincidono.' );

		$u = Rdacademy_DB::get_row(
			"SELECT id, first_name, last_name, login, email, password FROM `{$p}users` WHERE id = ? LIMIT 1",
			array( $uid ), 'i'
		);
		if ( ! $u ) wp_send_json_error( 'Utente non trovato.' );

		if ( ! password_verify( $attuale, (string) $u['password'] ) ) {
			Rdacademy_Auth::log_activity( $uid, 'auth', 'me_password_ko', 'Cambio password rifiutato: password attuale errata' );
			wp_send_json_error( 'La password attuale non è corretta.' );
		}
		if ( password_verify( $nuova, (string) $u['password'] ) ) {
			wp_send_json_error( 'La nuova password deve essere diversa da quella attuale.' );
		}

		$regola = Rdacademy_Password_Policy::verifica( $nuova, (array) $u );
		if ( empty( $regola['ok'] ) ) wp_send_json_error( (string) $regola['errore'] );

		$hash = password_hash( $nuova, PASSWORD_DEFAULT );
		$ok   = Rdacademy_DB::execute(
			"UPDATE `{$p}users`
			    SET password = ?, reset_token = NULL, reset_expiry = NULL,
			        failed_logins = 0, locked_until = NULL, password_expires_at = NULL
			  WHERE id = ?",
			array( $hash, $uid ), 'si'
		);
		if ( $ok <= 0 ) wp_send_json_error( 'Impossibile salvare la password.' );

		Rdacademy_DB::execute( "DELETE FROM `{$p}sessions` WHERE user_id = ?", array( $uid ), 'i' );
		Rdacademy_Auth::open_session_for_user( $uid );

		Rdacademy_Auth::log_activity( $uid, 'auth', 'password_set', 'Password cambiata dal proprio profilo' );
		wp_send_json_success( array(
			'messaggio' => 'Password aggiornata. Le sessioni aperte sugli altri dispositivi sono state chiuse.',
		) );
	}

	/** I MIEI ULTIMI ACCESSI — copiato; query PREPARATA e legata a $uid, cinque righe. */
	public static function ajax_me_accessi_get( ?array $user ): void {
		$uid = self::me_uid();
		$p   = RDACADEMY_TABLE_PREFIX;
		$righe = Rdacademy_DB::get_results(
			"SELECT created_at, result, reason, ip_address, user_agent
			   FROM `{$p}access_log`
			  WHERE user_id = ?
			  ORDER BY created_at DESC, id DESC
			  LIMIT 5",
			array( $uid ), 'i'
		);
		if ( Rdacademy_DB::last_sql_error() !== null ) {
			wp_send_json_error( 'Registro accessi non leggibile.' ); // un errore non si traveste da «nessun accesso»
		}
		$out = array();
		foreach ( (array) $righe as $r ) {
			$t = strtotime( (string) $r['created_at'] . ' UTC' );
			$out[] = array(
				'quando' => $t ? wp_date( 'd/m/Y H:i', $t ) : (string) $r['created_at'],
				'esito'  => (string) $r['result'],
				'motivo' => (string) $r['reason'],
				'ip'     => (string) $r['ip_address'],
				'da'     => self::dispositivo_leggibile( (string) $r['user_agent'] ),
			);
		}
		wp_send_json_success( array( 'righe' => $out, 'registro' => true ) );
	}

	/** Copiato senza modifiche: funzione PURA. */
	private static function dispositivo_leggibile( string $ua ): string {
		if ( trim( $ua ) === '' ) return 'sconosciuto';
		$b = 'browser';
		if     ( stripos( $ua, 'Edg' )     !== false ) $b = 'Edge';
		elseif ( stripos( $ua, 'OPR' )     !== false ) $b = 'Opera';
		elseif ( stripos( $ua, 'Chrome' )  !== false ) $b = 'Chrome';
		elseif ( stripos( $ua, 'Firefox' ) !== false ) $b = 'Firefox';
		elseif ( stripos( $ua, 'Safari' )  !== false ) $b = 'Safari';
		$s = '';
		if     ( stripos( $ua, 'iPhone' )   !== false ) $s = 'iPhone';
		elseif ( stripos( $ua, 'iPad' )     !== false ) $s = 'iPad';
		elseif ( stripos( $ua, 'Android' )  !== false ) $s = 'Android';
		elseif ( stripos( $ua, 'Mac OS' )   !== false ) $s = 'Mac';
		elseif ( stripos( $ua, 'Windows' )  !== false ) $s = 'Windows';
		elseif ( stripos( $ua, 'Linux' )    !== false ) $s = 'Linux';
		return $s ? ( $b . ' su ' . $s ) : $b;
	}
}
