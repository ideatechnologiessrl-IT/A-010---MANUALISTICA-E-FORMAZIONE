<?php
/**
 * Rdacademy_Utenti — LE AZIONI DELLA GESTIONE UTENTI (canale dell app).
 * =============================================================================
 * Copiate da Reldox_Router, RelDox CRD 3.55.09G: ajax_get_user (r.4149),
 * identificativi_liberi + ajax_save_user (r.6395-6784), ajax_delete_user
 * (r.6786), ajax_reset_user_pwd (r.6825), invita_utente + ajax_user_invite
 * (r.6896-6976), role_is_admin (r.3360). L azione get_access_log e del nucleo
 * (lotto A) e non si ripete qui.
 *
 * Ogni metodo riceve $user DOPO che il router ha gia verificato nonce,
 * permesso (`admin`, dichiarato in carico-c.php) e freno: la guardia interna
 * resta (difesa in profondita, come in RelDox) e legge la STESSA autorita.
 *
 * CORREZIONI RISPETTO A RELDOX, DICHIARATE:
 *  - ⛔ QUERY PREPARATE OVUNQUE (REGOLE § 5): in RelDox get_user, save_user e
 *    delete_user componevano l SQL con real_escape_string e interi interpolati.
 *  - ⛔ IL RUOLO DI SERVIZIO NON SI PERDE AL SALVA: in RelDox il Salva cancella
 *    TUTTI i ruoli dell utente e rimette quelli della griglia, che non mostra
 *    il Service Account (is_backdoor = 0): salvare la scheda dell utente di
 *    servizio gli toglieva in silenzio il ruolo di servizio. Qui si cancellano
 *    e si riscrivono solo i ruoli che la griglia mostra.
 *  - NUOVO: protezione dell ultimo amministratore anche quando si toglie il
 *    ruolo a UN ALTRO o lo si elimina (in RelDox valeva solo su se stessi:
 *    SAFEGUARD A); niente sospensione di se stessi; l utente di servizio non
 *    si elimina dalla lista (il suo rientro passa da wp-admin, lotto B).
 *  - NUOVO: campo `tipo` (interno | esterno), REGOLE § 3.3.
 *  - La valvola della password provvisoria NON promette piu «all ingresso
 *    dovra cambiarla»: in RelDox nessun codice lo imponeva (difetto nel
 *    referto). Il testo dice cio che succede davvero.
 * RelDoxAcademy (Formazione F009, 18/11/2026, analisi ed. 8) — RIDOTTA: tolti il controllo dello
 * username sulla tabella della mensa (senza condizione: errore DB), il ramo dell email condivisa e
 * quello del nome utente della scheda. Restano tutte le azioni della Gestione utenti.
 * TAGLI: caselle email (user_email_accounts, IMAP/SMTP, firme per figura,
 * firma grafica), prefisso di protocollo, telefono di casella — tutto CRM.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Utenti {

	const STATI = array( 'active', 'suspended', 'disabled' );
	const TIPI  = array( 'interno', 'esterno' );

	/** Copiato da Reldox_Router::role_is_admin(). */
	public static function role_is_admin( array $role ): bool {
		if ( ! empty( $role['is_backdoor'] ) ) return true;
		$perm = $role['permissions'] ?? '';
		if ( $perm === '' || $perm === null ) return false;
		$decoded = json_decode( (string) $perm, true );
		if ( is_array( $decoded ) && isset( $decoded['all'] ) ) {
			$all = $decoded['all'];
			if ( $all === true || $all === 1 || $all === '1' || $all === 'true' ) return true;
		}
		return false;
	}

	/** Copiato da Reldox_Router::tech_admin_user_count(): utenti ATTIVI con almeno un ruolo admin. */
	public static function admin_count( int $escludi_uid = 0, array $escludi_ruoli = array() ): int {
		$p    = RDACADEMY_TABLE_PREFIX;
		$rows = Rdacademy_DB::get_results(
			"SELECT ur.user_id, r.id AS role_id, r.is_backdoor, r.permissions
			   FROM `{$p}user_roles` ur
			   JOIN `{$p}roles` r ON r.id = ur.role_id
			   JOIN `{$p}users` u ON u.id = ur.user_id
			  WHERE u.status = 'active'"
		) ?: array();
		$set = array();
		foreach ( $rows as $r ) {
			if ( (int) $r['user_id'] === $escludi_uid && ( ! $escludi_ruoli || in_array( (int) $r['role_id'], $escludi_ruoli, true ) ) ) continue;
			if ( self::role_is_admin( $r ) ) $set[ (int) $r['user_id'] ] = true;
		}
		return count( $set );
	}

	private static function guardia( ?array $cur ): array {
		if ( ! $cur || ! Rdacademy_Router::user_can_admin( $cur ) ) wp_send_json_error( 'Accesso negato', 403 );
		return $cur;
	}

	/* ===================================================================== *
	 *  get_user — copiato (r.4149), preparato
	 * ===================================================================== */

	public static function ajax_get_user( ?array $cur ): void {
		self::guardia( $cur );
		$p   = RDACADEMY_TABLE_PREFIX;
		$uid = (int) ( $_POST['user_id'] ?? 0 );
		$u   = Rdacademy_DB::get_row( "SELECT * FROM `{$p}users` WHERE id = ? LIMIT 1", array( $uid ), 'i' );
		if ( ! $u ) wp_send_json_error( 'Utente non trovato' );

		$u['role_ids'] = array();
		foreach ( Rdacademy_DB::get_results( "SELECT role_id FROM `{$p}user_roles` WHERE user_id = ?", array( $uid ), 'i' ) as $r ) {
			$u['role_ids'][] = (int) $r['role_id'];
		}

		// v3.16.196 (S257) di RelDox — cio che la scheda deve sapere sull accesso.
		// ⛔ L IMPRONTA NON ESCE DI QUI: stato_di() ne ricava tre parole.
		$u['pwd'] = array( 'stato' => array( 'tipo' => 'nessuno', 'vivo' => false, 'parola' => '' ), 'ultimo_cambio' => '' );
		$u['pwd']['stato'] = Rdacademy_Password_Token::stato_di( $u['reset_token'] ?? null, $u['reset_expiry'] ?? null );
		$ric = Rdacademy_DB::get_row(
			"SELECT created_at FROM `{$p}activity_log`
			  WHERE user_id = ? AND entity_type = 'auth' AND action = 'password_set'
			  ORDER BY id DESC LIMIT 1",
			array( $uid ), 'i' );
		if ( ! empty( $ric['created_at'] ) ) {
			$u['pwd']['ultimo_cambio'] = wp_date( 'd/m/Y', strtotime( (string) $ric['created_at'] . ' UTC' ) );
		}
		$u['pwd']['servizio']   = Rdacademy_Password_Mail::configurata();
		$u['pwd']['mai_entrato'] = empty( $u['last_login'] );

		// ⛔ Gli istanti in colonna sono UTC (SPEC § 2.3): al browser escono nel fuso del sito.
		$u['locked_until_locale'] = ( ! empty( $u['locked_until'] ) && strtotime( $u['locked_until'] . ' UTC' ) > time() )
			? wp_date( 'd/m/Y H:i', strtotime( $u['locked_until'] . ' UTC' ) ) : '';
		$u['id']            = (int) $u['id'];
		$u['failed_logins'] = (int) $u['failed_logins'];

		unset( $u['password'], $u['reset_token'], $u['reset_expiry'] );  // il segreto non esce nemmeno come impronta
		wp_send_json_success( $u );
	}

	/* ===================================================================== *
	 *  identificativi_liberi — copiato senza modifiche di logica (r.6395)
	 * ===================================================================== */

	private static function identificativi_liberi( int $uid, string $email, string $login ): string {
		$p     = RDACADEMY_TABLE_PREFIX;
		$email = strtolower( trim( $email ) );
		$login = trim( $login );

		// BUG-84 di RelDox — lo username non e' un indirizzo
		if ( $login !== '' && ( strpos( $login, '@' ) !== false || is_email( $login ) ) ) {
			return 'Lo username non puo essere un indirizzo email: per entrare con l\'email '
				 . 'si usa gia il campo Email. Scegli un nome utente senza «@» (es. mario.rossi).';
		}

		$chi = function ( $r ) {
			$n = trim( (string) ( $r['first_name'] ?? '' ) . ' ' . (string) ( $r['last_name'] ?? '' ) );
			return $n !== '' ? $n : (string) ( $r['email'] ?? ( '#' . (int) ( $r['id'] ?? 0 ) ) );
		};

		if ( $email !== '' ) {
			$r = Rdacademy_DB::get_row(
				"SELECT id, first_name, last_name, email, login FROM `{$p}users`
				  WHERE id <> ? AND (LOWER(email) = ? OR LOWER(login) = ?) ORDER BY id LIMIT 1",
				array( $uid, $email, $email ), 'iss'
			);
			if ( $r ) {
				$dove = ( strtolower( (string) $r['email'] ) === $email ) ? 'come email' : 'come username';
				return 'L\'indirizzo «' . $email . '» e gia in uso ' . $dove . ' da ' . $chi( $r )
					 . '. Un valore appartiene a una persona sola.';
			}
		}

		return self::login_libero( $uid, $login, $chi );
	}

	/** 0.11.1A — il controllo dello username, separato per riusarlo dopo la regola dell email condivisa. */
	private static function login_libero( int $uid, string $login, callable $chi ): string {
		$p = RDACADEMY_TABLE_PREFIX;
		if ( $login !== '' ) {
			$lb = strtolower( $login );
			$r = Rdacademy_DB::get_row(
				"SELECT id, first_name, last_name, email, login FROM `{$p}users`
				  WHERE id <> ? AND (LOWER(login) = ? OR LOWER(email) = ?) ORDER BY id LIMIT 1",
				array( $uid, $lb, $lb ), 'iss'
			);
			if ( $r ) {
				$dove = ( strtolower( (string) $r['login'] ) === $lb ) ? 'come username' : 'come email';
				return 'Lo username «' . $login . '» e gia in uso ' . $dove . ' da ' . $chi( $r )
					 . '. Un valore appartiene a una persona sola.';
			}
		}

		return '';
	}

	/** Avatar: copiato dal ramo UPDATE/INSERT di save_user (jpg/png/webp, 2 MB, cartella uploads). */
	private static function salva_avatar( int $uid ): string {
		if ( empty( $_FILES['avatar']['tmp_name'] ) ) return '';
		$up     = wp_upload_dir();
		$av_dir = $up['basedir'] . '/rdacademy-avatars/';
		if ( ! is_dir( $av_dir ) ) wp_mkdir_p( $av_dir );
		$ext = strtolower( pathinfo( (string) $_FILES['avatar']['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp' ), true ) || (int) $_FILES['avatar']['size'] > 2 * 1024 * 1024 ) return '';
		// NUOVO: il tipo si legge dal contenuto, non dal nome (REGOLE § 3.9).
		if ( @getimagesize( (string) $_FILES['avatar']['tmp_name'] ) === false ) return '';
		$filename = 'avatar_' . $uid . '.' . $ext;
		return move_uploaded_file( (string) $_FILES['avatar']['tmp_name'], $av_dir . $filename ) ? $filename : '';
	}

	/* ===================================================================== *
	 *  save_user — copiato (r.6443), preparato e ridotto
	 * ===================================================================== */

	public static function ajax_save_user( ?array $cur ): void {
		$cur = self::guardia( $cur );
		try {
			$p   = RDACADEMY_TABLE_PREFIX;
			$uid = (int) ( $_POST['user_id'] ?? 0 );

			$t = static function ( string $k ): string {
				return trim( sanitize_text_field( wp_unslash( (string) ( $_POST[ $k ] ?? '' ) ) ) );
			};
			$fname    = $t( 'first_name' );
			$lname    = $t( 'last_name' );
			$email    = sanitize_email( wp_unslash( (string) ( $_POST['email'] ?? '' ) ) );
			$login    = $t( 'login' );
			$phone    = $t( 'phone' );
			$mobile   = $t( 'mobile' );
			$dept     = $t( 'department' );
			$empcode  = $t( 'employee_code' );
			$jobtitle = $t( 'job_title' );
			$manager  = $t( 'manager_name' );
			$status   = sanitize_key( (string) ( $_POST['status'] ?? 'active' ) );
			$tipo     = sanitize_key( (string) ( $_POST['tipo'] ?? 'interno' ) );
			if ( ! in_array( $status, self::STATI, true ) ) $status = 'active';
			if ( ! in_array( $tipo, self::TIPI, true ) )    $tipo   = 'interno';
			$pwd_exp  = (string) ( $_POST['password_expires_at'] ?? '' );
			$pwd_exp  = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $pwd_exp ) ? $pwd_exp : null;
			// P1.5.5 BUG-G di RelDox: la UNIQUE ammette N NULL e rifiuta N stringhe vuote.
			$empcode_v = ( $empcode === '' ) ? null : $empcode;

			if ( ! $fname || ! $lname || ! $email ) wp_send_json_error( 'Nome, cognome ed email obbligatori.' );

			// BUG-79 + BUG-84 di RelDox — un valore sta in una casa sola, controllato PRIMA di scrivere.
			$rx_collisione = self::identificativi_liberi( $uid, $email, $login );
			if ( $rx_collisione !== '' ) wp_send_json_error( $rx_collisione );

			// NUOVO — non ci si sospende da soli: si resterebbe fuori al prossimo accesso.
			if ( $uid && $uid === (int) $cur['id'] && $status !== 'active' ) {
				wp_send_json_error( 'Non puoi sospendere o disabilitare te stesso: resteresti fuori. Chiedi a un altro amministratore.' );
			}

			$pwd = (string) wp_unslash( $_POST['password'] ?? '' );

			// v3.16.196 (S257) — LA REGOLA DELLA PASSWORD E UNA SOLA (copiato).
			if ( $pwd !== '' ) {
				$ver = Rdacademy_Password_Policy::verifica( $pwd, array(
					'first_name' => $fname, 'last_name' => $lname, 'login' => $login, 'email' => $email,
				) );
				if ( empty( $ver['ok'] ) ) {
					wp_send_json_error( (string) ( $ver['errore'] ?? 'La password non rispetta la regola.' ) );
				}
			}

			$rx_invito_possibile = Rdacademy_Password_Mail::configurata();
			$rx_manda_invito     = false;

			// Ruoli (copiato): solo interi positivi, deduplicati.
			$roles = json_decode( wp_unslash( (string) ( $_POST['roles'] ?? '[]' ) ), true ) ?: array();
			$roles = array_values( array_unique( array_filter( array_map( 'intval', (array) $roles ) ) ) );
			// ⛔ CORREZIONE: la griglia mostra solo i ruoli non di servizio; un id di
			// servizio arrivato dal POST non si assegna da qui (si assegna da wp-admin).
			$visibili = array();
			foreach ( Rdacademy_DB::get_results( "SELECT id, is_backdoor, permissions FROM `{$p}roles` WHERE is_backdoor = 0" ) as $rr ) {
				$visibili[ (int) $rr['id'] ] = $rr;
			}
			$roles = array_values( array_filter( $roles, static function ( $r ) use ( $visibili ) { return isset( $visibili[ $r ] ); } ) );

			if ( $uid ) {
				$esiste = Rdacademy_DB::get_row( "SELECT id, status FROM `{$p}users` WHERE id = ?", array( $uid ), 'i' );
				if ( ! $esiste ) wp_send_json_error( 'Utente non trovato.' );

				// ═══ SAFEGUARD RUOLI (copiato, pkg-saveuser-roles-safeguard v3.10.24) ═══
				$had_roles = false; $had_admin = false; $admin_rids = array();
				foreach ( Rdacademy_DB::get_results(
					"SELECT r.id, r.is_backdoor, r.permissions FROM `{$p}user_roles` ur
					   JOIN `{$p}roles` r ON r.id = ur.role_id WHERE ur.user_id = ? AND r.is_backdoor = 0",
					array( $uid ), 'i' ) as $rr ) {
					$had_roles = true;
					if ( self::role_is_admin( $rr ) ) { $had_admin = true; $admin_rids[] = (int) $rr['id']; }
				}
				// SAFEGUARD B — mai lasciare un utente esistente senza ruoli.
				if ( $had_roles && empty( $roles ) ) {
					wp_send_json_error( 'Operazione annullata: nessun ruolo selezionato. Per non lasciare l\'utente senza ruoli (possibile griglia ruoli non ancora caricata) seleziona almeno un ruolo e riprova.' );
				}
				$new_has_admin = false;
				foreach ( $roles as $rid ) { if ( self::role_is_admin( $visibili[ $rid ] ) ) { $new_has_admin = true; break; } }
				// SAFEGUARD A — auto-protezione dell utente collegato (copiato).
				if ( $uid === (int) $cur['id'] && $had_admin && ! $new_has_admin ) {
					wp_send_json_error( 'Operazione annullata: non puoi rimuovere a te stesso l\'ultimo ruolo con privilegi amministrativi. Chiedi a un altro amministratore, oppure usa wp-admin › ' . RDACADEMY_PRODUCT . ' › Primo accesso.' );
				}
				// NUOVO — l ultimo amministratore non si toglie nemmeno a un altro, ne lo si sospende.
				$perde_admin = $had_admin && ( ! $new_has_admin || $status !== 'active' );
				if ( $perde_admin && (string) $esiste['status'] === 'active' && self::admin_count( $uid, $admin_rids ) === 0
					&& ! Rdacademy_Security::is_backdoor( $uid ) ) {
					wp_send_json_error( 'Operazione annullata: è l\'ultimo amministratore attivo del sistema. Togliergli il ruolo o sospenderlo bloccherebbe l\'accesso a tutti.' );
				}

				Rdacademy_DB::clear_last_error();
				Rdacademy_DB::execute(
					"UPDATE `{$p}users` SET first_name = ?, last_name = ?, email = ?, login = ?, tipo = ?,
					        phone = ?, mobile = ?, status = ?, department = ?, employee_code = ?,
					        job_title = ?, manager_name = ?, password_expires_at = ?
					  WHERE id = ?",
					array( $fname, $lname, $email, ( $login === '' ? null : $login ), $tipo, $phone, $mobile, $status,
					       $dept, $empcode_v, $jobtitle, $manager, $pwd_exp, $uid ),
					'sssssssssssssi'
				);
				if ( Rdacademy_DB::last_error() !== '' ) wp_send_json_error( 'Errore DB nel salvataggio utente: ' . Rdacademy_DB::last_error() );
				$av = self::salva_avatar( $uid );
				if ( $av !== '' ) {
					Rdacademy_DB::execute( "UPDATE `{$p}users` SET avatar_path = ? WHERE id = ?", array( $av, $uid ), 'si' );
				}
				if ( $pwd !== '' ) {
					// La valvola anche su un esistente (copiato: `if ($pwd) $sql .= ", password=..."`).
					Rdacademy_DB::execute( "UPDATE `{$p}users` SET password = ? WHERE id = ?", array( password_hash( $pwd, PASSWORD_DEFAULT ), $uid ), 'si' );
					Rdacademy_DB::execute( "DELETE FROM `{$p}sessions` WHERE user_id = ?", array( $uid ), 'i' ); // RP-CAMBIO-PASSWORD-CHIUDE-LE-SESSIONI
				}
				// Se uno stato non attivo: le sessioni aperte cadono (la porta e chiusa, NUOVO).
				if ( $status !== 'active' ) {
					Rdacademy_DB::execute( "DELETE FROM `{$p}sessions` WHERE user_id = ?", array( $uid ), 'i' );
				}
				Rdacademy_Auth::log_activity( (int) $cur['id'], 'user', 'user_update', 'Scheda utente #' . $uid . ' salvata' );
			} else {
				// Nuovo utente — v3.16.196 (S257) di RelDox: L INVITO E LA VIA (copiato).
				if ( $pwd === '' && ! $rx_invito_possibile ) {
					wp_send_json_error(
						'Nessuna casella di servizio configurata: senza di essa le credenziali non '
						. 'potranno essere spedite a nessuno, nemmeno dopo. Configurala in '
						. 'Impostazioni -> Email, oppure imposta qui una password provvisoria e '
						. 'comunicala all\'utente per un\'altra via.'
					);
				}
				if ( $pwd === '' ) {
					$pwd_hash        = password_hash( bin2hex( random_bytes( 32 ) ), PASSWORD_DEFAULT );
					$rx_manda_invito = true;
				} else {
					$pwd_hash = password_hash( $pwd, PASSWORD_DEFAULT );
				}
				$uid = (int) Rdacademy_DB::insert(
					"INSERT INTO `{$p}users`
					    (first_name, last_name, email, login, password, tipo, status, phone, mobile,
					     department, employee_code, job_title, manager_name, password_expires_at)
					 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array( $fname, $lname, $email, ( $login === '' ? null : $login ), $pwd_hash, $tipo, $status, $phone, $mobile,
					       $dept, $empcode_v, $jobtitle, $manager, $pwd_exp ),
					'ssssssssssssss'
				);
				if ( ! $uid ) wp_send_json_error( 'Errore creazione utente: ' . Rdacademy_DB::last_error() );
				$av = self::salva_avatar( $uid );
				if ( $av !== '' ) {
					Rdacademy_DB::execute( "UPDATE `{$p}users` SET avatar_path = ? WHERE id = ?", array( $av, $uid ), 'si' );
				}
				Rdacademy_Auth::log_activity( (int) $cur['id'], 'user', 'user_create', 'Utente #' . $uid . ' creato (' . $tipo . ')' );
			}

			// ⛔ CORREZIONE: si cancellano e si riscrivono SOLO i ruoli visibili. Il ruolo di
			// servizio (is_backdoor = 1) resta dove stava.
			Rdacademy_DB::execute(
				"DELETE ur FROM `{$p}user_roles` ur JOIN `{$p}roles` r ON r.id = ur.role_id
				  WHERE ur.user_id = ? AND r.is_backdoor = 0",
				array( $uid ), 'i'
			);
			foreach ( $roles as $rid ) {
				Rdacademy_DB::execute(
					"INSERT IGNORE INTO `{$p}user_roles` (user_id, role_id, assigned_by) VALUES (?, ?, ?)",
					array( $uid, (int) $rid, (int) $cur['id'] ), 'iii'
				);
			}
			Rdacademy_Security::invalidate_role_cache( $uid );

			// BUG-82 di RelDox — IL SALVATAGGIO NON SPEDISCE PIU NIENTE (copiato).
			$rx_msg_invito = $rx_manda_invito
				? 'Utente creato. Le credenziali NON sono state spedite: quando la scheda e completa '
				  . 'usa «Invia credenziali nuovo utente» dalla lista.'
				: '';

			wp_send_json_success( array( 'user_id' => $uid, 'invito' => $rx_msg_invito ) );
		} catch ( mysqli_sql_exception $e ) {
			error_log( '[RelDoxAcademy Utenti] save_user: mysqli ha lanciato, errno ' . $e->getCode() . ': ' . $e->getMessage() );
			wp_send_json_error( 'Errore DB nel salvataggio utente: ' . $e->getMessage() );
		}
	}

	/* ===================================================================== *
	 *  delete_user — copiato (r.6786), preparato
	 * ===================================================================== */

	public static function ajax_delete_user( ?array $cur ): void {
		$cur = self::guardia( $cur );
		$p   = RDACADEMY_TABLE_PREFIX;
		$uid = (int) ( $_POST['user_id'] ?? 0 );
		if ( $uid <= 0 ) wp_send_json_error( 'Utente non indicato.', 400 );
		if ( $uid === (int) $cur['id'] ) wp_send_json_error( 'Non puoi eliminare te stesso.' );
		if ( Rdacademy_Security::is_backdoor( $uid ) ) {
			wp_send_json_error( 'L\'utente di servizio non si elimina da qui: è la via di rientro di emergenza (wp-admin › ' . RDACADEMY_PRODUCT . ' › Primo accesso).' );
		}
		$ha_admin = false; $rids = array();
		foreach ( Rdacademy_DB::get_results(
			"SELECT r.id, r.is_backdoor, r.permissions FROM `{$p}user_roles` ur JOIN `{$p}roles` r ON r.id = ur.role_id WHERE ur.user_id = ?",
			array( $uid ), 'i' ) as $rr ) {
			if ( self::role_is_admin( $rr ) ) { $ha_admin = true; $rids[] = (int) $rr['id']; }
		}
		if ( $ha_admin && self::admin_count( $uid, $rids ) === 0 ) {
			wp_send_json_error( 'E l\'ultimo amministratore attivo del sistema: eliminarlo bloccherebbe l\'accesso a tutti.' );
		}
		Rdacademy_DB::execute( "DELETE FROM `{$p}user_roles` WHERE user_id = ?", array( $uid ), 'i' );
		Rdacademy_DB::execute( "DELETE FROM `{$p}sessions`   WHERE user_id = ?", array( $uid ), 'i' );
		Rdacademy_DB::execute( "DELETE FROM `{$p}user_prefs` WHERE user_id = ?", array( $uid ), 'i' );
		Rdacademy_DB::execute( "DELETE FROM `{$p}users`      WHERE id = ?",      array( $uid ), 'i' );
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'user', 'user_delete', 'Utente #' . $uid . ' eliminato' );
		wp_send_json_success( array( 'deleted_id' => $uid ) );
	}

	/* ===================================================================== *
	 *  reset_user_pwd — copiato (r.6825)
	 * ===================================================================== */

	public static function ajax_reset_user_pwd( ?array $cur ): void {
		$cur = self::guardia( $cur );
		$uid = (int) ( $_POST['user_id'] ?? 0 );
		if ( $uid <= 0 ) wp_send_json_error( 'Utente non indicato.', 400 );

		$p  = RDACADEMY_TABLE_PREFIX;
		$ut = Rdacademy_DB::get_row( "SELECT * FROM `{$p}users` WHERE id = ? LIMIT 1", array( $uid ), 'i' );
		if ( ! $ut ) wp_send_json_error( 'Utente non trovato.', 404 );
		if ( (string) ( $ut['status'] ?? '' ) !== 'active' ) {
			wp_send_json_error( 'L\'utente non è attivo: riattivalo prima di inviargli un collegamento.' );
		}
		if ( trim( (string) ( $ut['email'] ?? '' ) ) === '' ) {
			wp_send_json_error( 'L\'utente non ha un indirizzo email: il collegamento non ha dove arrivare.' );
		}

		$stato = Rdacademy_Password_Mail::stato();
		if ( empty( $stato['configurata'] ) ) {
			// S316 PUNTO 7 di RelDox — il motivo esiste gia e si dice a chi amministra.
			$rx_motivo = trim( (string) ( $stato['motivo'] ?? '' ) );
			if ( $rx_motivo === '' ) { $rx_motivo = 'Nessuna casella di servizio configurata.'; }
			if ( class_exists( 'Rdacademy_Spedizioni' ) ) Rdacademy_Spedizioni::non_partita( 'reimpostazione', $ut, $rx_motivo );   // 1.4.4B
			wp_send_json_error( $rx_motivo . ' Il collegamento non può essere spedito. Impostazioni -> Email.' );
		}

		$token = Rdacademy_Password_Token::emetti( $uid, Rdacademy_Password_Token::SCOPO_REIMPOSTA );
		if ( $token === null ) { if ( class_exists( 'Rdacademy_Spedizioni' ) ) Rdacademy_Spedizioni::non_partita( 'reimpostazione', $ut, 'collegamento non emesso' ); wp_send_json_error( 'Non è stato possibile emettere il collegamento.' ); }

		$inv = Rdacademy_Password_Mail::invia_reimpostazione( $ut, $token );
		unset( $token ); // il segreto non sopravvive alla riga che lo ha speso

		if ( empty( $inv['success'] ) ) {
			Rdacademy_Password_Token::ritira( $uid ); // 0.6.10A (RF-BUG-018)
			wp_send_json_error( 'Collegamento NON spedito: ' . ( $inv['error'] ?? 'errore SMTP' ) );
		}

		Rdacademy_Auth::log_activity( (int) $cur['id'], 'auth', 'pwd_reset_send',
			'Collegamento di reimpostazione inviato all\'utente #' . $uid );

		wp_send_json_success( array(
			'message' => 'Collegamento di reimpostazione inviato a ' . (string) $ut['email'] . '. Vale '
				. Rdacademy_Password_Token::MINUTI_REIMPOSTA . ' minuti e si usa una volta sola.' . self::in_lingua( $uid ),
			'avviso'  => (string) Rdacademy_Password_Mail::$ultimo_avviso,
		) );
	}

	/* ===================================================================== *
	 *  invita_utente + user_invite — copiati (r.6896, r.6943)
	 * ===================================================================== */

	private static function invita_utente( array $ut, int $attore, bool $reinvio ): array {
		$esito = array( 'success' => false, 'error' => null,
		                'email'   => (string) ( $ut['email'] ?? '' ),
		                'giorni'  => (int) round( Rdacademy_Password_Token::MINUTI_INVITO / 1440 ) );

		$uid = (int) ( $ut['id'] ?? 0 );
		if ( $uid <= 0 ) { $esito['error'] = 'Utente non indicato.'; return $esito; }
		if ( trim( (string) ( $ut['email'] ?? '' ) ) === '' ) {
			$esito['error'] = 'L\'utente non ha un indirizzo email: l\'invito non ha dove arrivare.';
			return $esito;
		}
		if ( ! Rdacademy_Password_Mail::configurata() ) {
			$s = Rdacademy_Password_Mail::stato();
			$esito['error'] = (string) ( $s['motivo'] ?? 'Nessuna casella di servizio configurata.' );
			if ( class_exists( 'Rdacademy_Spedizioni' ) ) Rdacademy_Spedizioni::non_partita( $reinvio ? 'invito_reinvio' : 'invito', $ut, $esito['error'] );   // 1.4.4B
			return $esito;
		}

		$token = Rdacademy_Password_Token::emetti( $uid, Rdacademy_Password_Token::SCOPO_INVITO );
		if ( $token === null ) { $esito['error'] = 'Non è stato possibile emettere l\'invito.'; if ( class_exists( 'Rdacademy_Spedizioni' ) ) Rdacademy_Spedizioni::non_partita( $reinvio ? 'invito_reinvio' : 'invito', $ut, 'invito non emesso' ); return $esito; }

		$inv = Rdacademy_Password_Mail::invia_invito( $ut, $token, $reinvio );
		unset( $token );

		if ( empty( $inv['success'] ) ) {
			// 0.6.10A (RF-BUG-018) — non spedito = nessun invito: il collegamento si ritira,
			// altrimenti la riga direbbe «in sospeso» per un messaggio che nessuno ha ricevuto.
			Rdacademy_Password_Token::ritira( $uid );
			$esito['error'] = 'Invito NON spedito: ' . ( $inv['error'] ?? 'errore SMTP' );
			return $esito;
		}

		Rdacademy_Auth::log_activity(
			$attore, 'auth', $reinvio ? 'user_invite_resend' : 'user_invite',
			( $reinvio ? 'Invito rimandato a ' : 'Invito inviato a ' ) . (string) $ut['email'] . ' (utente #' . $uid . ')'
		);

		$esito['success'] = true;
		return $esito;
	}

	/** 0.8.2A — l esito dice in che lingua è partito il messaggio (quella della scheda della persona). */
	private static function in_lingua( int $uid ): string {
		$l = Rdacademy_Lingua::di_utente( $uid );
		return ' Messaggio in ' . Rdacademy_Lingua::di( array( 'it' => 'italiano', 'en' => 'inglese', 'de' => 'tedesco' ), $l ) . '.';   // 1.17.0L — amministrazione: in italiano
	}

	public static function ajax_user_invite( ?array $cur ): void {
		$cur = self::guardia( $cur );
		$uid = (int) ( $_POST['user_id'] ?? 0 );
		if ( $uid <= 0 ) wp_send_json_error( 'Utente non indicato.', 400 );

		$p  = RDACADEMY_TABLE_PREFIX;
		$ut = Rdacademy_DB::get_row( "SELECT * FROM `{$p}users` WHERE id = ? LIMIT 1", array( $uid ), 'i' );
		if ( ! $ut ) wp_send_json_error( 'Utente non trovato.', 404 );
		if ( (string) ( $ut['status'] ?? '' ) !== 'active' ) {
			wp_send_json_error( 'L\'utente non è attivo: riattivalo prima di mandargli l\'invito.' );
		}

		$stato_pre = Rdacademy_Password_Token::stato_di( $ut['reset_token'] ?? null, $ut['reset_expiry'] ?? null );

		// BUG-82 di RelDox — il flag `reinvio` si LEGGE dai fatti, non dal pulsante.
		$mai_entrato = empty( $ut['last_login'] );
		$reinvio     = ! ( $mai_entrato && ! $stato_pre['vivo'] );

		$esito = self::invita_utente( $ut, (int) $cur['id'], $reinvio );
		if ( empty( $esito['success'] ) ) wp_send_json_error( (string) $esito['error'] );

		// 0.6.09A (RF-BUG-016) — la riga si aggiorna da sola: il server dice che cosa scriverci.
		$dopo = Rdacademy_DB::get_row( "SELECT reset_token, reset_expiry FROM `{$p}users` WHERE id = ? LIMIT 1", array( $uid ), 'i' );
		wp_send_json_success( array(
			'message' => ( $reinvio ? 'Invito rimandato a ' : 'Credenziali inviate a ' ) . $esito['email']
				. ' — il collegamento vale ' . $esito['giorni'] . ' giorni e si usa una volta sola.'
				. ( $stato_pre['vivo'] ? ' Il collegamento precedente non è più valido.' : '' )
				. self::in_lingua( $uid ),
			'pil'     => Rdacademy_Password_Token::pillola_invito( $dopo['reset_token'] ?? null, $dopo['reset_expiry'] ?? null ),
			'avviso'  => (string) Rdacademy_Password_Mail::$ultimo_avviso,
		) );
	}

	/* ===================================================================== *
	 *  0.8.6A — «INVIO LOGIN» (owner 08/10 08:37-08:39, SPEC-CASSA § 0 punto 28)
	 * ===================================================================== */

	/**
	 * Manda SOLO il collegamento d accesso, senza toccare la password.
	 * 0.8.7A (RF-BUG-024, owner 08/10 09:47 «SI») — SEMPRE E SOLO L ACCESSO: mai il gettone, mai «scegli la password», per
	 * nessun account. Il passaggio automatico alle credenziali della 0.8.6A (deciso su `last_login` vuoto, un dato che
	 * non misura «ha la password»: una password può averla impostata l amministratore senza che la persona sia mai
	 * entrata) è ritirato. Le credenziali restano al loro pulsante. Nessun gettone emesso o ritirato: un invito vivo
	 * resta valido com è.
	 */
	public static function ajax_user_login_link( ?array $cur ): void {
		$cur = self::guardia( $cur );
		$uid = (int) ( $_POST['user_id'] ?? 0 );
		if ( $uid <= 0 ) wp_send_json_error( 'Utente non indicato.', 400 );
		$p  = RDACADEMY_TABLE_PREFIX;
		$ut = Rdacademy_DB::get_row( "SELECT * FROM `{$p}users` WHERE id = ? LIMIT 1", array( $uid ), 'i' );
		if ( ! $ut ) wp_send_json_error( 'Utente non trovato.', 404 );
		if ( (string) ( $ut['status'] ?? '' ) !== 'active' ) {
			wp_send_json_error( 'L\'utente non è attivo: riattivalo prima di mandargli l\'accesso.' );
		}
		if ( trim( (string) ( $ut['email'] ?? '' ) ) === '' ) {
			wp_send_json_error( 'L\'utente non ha un indirizzo email: l\'accesso non ha dove arrivare.' );
		}
		if ( ! Rdacademy_Password_Mail::configurata() ) {
			$s = Rdacademy_Password_Mail::stato();
			if ( class_exists( 'Rdacademy_Spedizioni' ) ) Rdacademy_Spedizioni::non_partita( 'accesso', $ut, (string) ( $s['motivo'] ?? 'Nessuna casella di servizio configurata.' ) );   // 1.4.4B
			wp_send_json_error( (string) ( $s['motivo'] ?? 'Nessuna casella di servizio configurata.' ) );
		}
		$inv = Rdacademy_Password_Mail::invia_accesso( $ut );
		if ( empty( $inv['success'] ) ) wp_send_json_error( 'Accesso NON spedito: ' . ( $inv['error'] ?? 'errore SMTP' ) );
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'auth', 'user_login_link', 'Collegamento d\'accesso inviato a ' . (string) $ut['email'] . ' (utente #' . $uid . '), password invariata' );
		wp_send_json_success( array(
			'tipo'    => 'accesso',
			'message' => 'Collegamento d\'accesso inviato a ' . (string) $ut['email'] . ' — la password non cambia.' . self::in_lingua( $uid ),
			'avviso'  => (string) Rdacademy_Password_Mail::$ultimo_avviso,
		) );
	}
}
