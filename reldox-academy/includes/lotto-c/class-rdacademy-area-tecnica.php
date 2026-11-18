<?php
/**
 * RelDoxAcademy — AREA TECNICA DI PRIMO LIVELLO.
 * ---------------------------------------------------------------------------
 * Copiato da Reldox_Area_Tecnica, RelDox CRD 3.55.09G (voce 85, contratto
 * MOCKUP-AREA-TECNICA-v2), e dalle azioni tech_check, tech_roles,
 * tech_role_add/remove/restore, tech_opcache di Reldox_Router (r.14403-14698).
 *
 * L amministratore dell azienda controlla da solo che il prodotto funzioni,
 * senza wp-admin. Ogni controllo dice VERDE o ROSSO in italiano e, se rosso,
 * che cosa fare.
 *
 * I CONTROLLI DEL GUSCIO (SPEC § 4): permessi · cache · file · sorgenti.
 * TAGLI: posta (caselle IMAP/365 degli utenti), claude, web (VIES, Web Risk):
 * sono del CRM. Il registro resta estendibile dal filtro
 * `rdacademy_area_tecnica_controlli` (il lotto D aggiunge le sorgenti 3-5, un
 * prodotto i suoi): NUOVO.
 *
 * ⛔ LEGGONO E BASTA (copiato): nessun dato cambiato. L unica scrittura e la
 *    prova di scrittura della cartella del prodotto: un file temporaneo che si
 *    cancella subito.
 * ⛔ NESSUN SEGRETO ESCE: password del BASE, password della casella di
 *    servizio non compaiono mai nell esito, nel dettaglio o nel referto.
 *
 * DIFFERENZE DICHIARATE:
 *  - permessi: la cartella privata degli allegati (wp-content/rdacademy-private) e
 *    del lotto D; finche non c e, il controllo prova le cartelle che il guscio
 *    usa davvero (uploads/rdacademy-branding e uploads/rdacademy-avatars) e dice della
 *    privata che «nasce con gli allegati».
 *  - cache: lo svuotamento automatico nel guscio e SEMPRE attivo (file
 *    principale, aggancio 2): non c e l interruttore opcache_autoreset di RelDox.
 *  - sorgenti: il BASE con Rdacademy_DB::try_connect(); le sorgenti 3-5 sono del lotto D.
 *  - Ruoli: il catalogo e le chip NON mostrano il ruolo di servizio
 *    (is_backdoor), che resta «nascosto da ogni UI» come dice l Activator; il
 *    conteggio degli amministratori conta gli utenti ATTIVI (un amministratore
 *    sospeso non puo entrare: contarlo farebbe passare la rimozione dell
 *    ultimo che entra davvero). Registro operazioni su Rdacademy_Option
 *    (in RelDox reldox_maint_audit): la chiave e una costante condivisa e si
 *    legge da Rdacademy_Accesso_Emergenza, che la dichiara (0.1.02, R5 e R6).
 *
 * Forma di ogni esito (copiata): { k, titolo, stato: verde|rosso, esito, nota, rimedio, dettaglio: [ [voce, valore, ok|ko|info], ... ] }.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// R5 (SPEC-MANUTENZIONE-E-DIAGNOSTICA § 6): chi legge una costante di un altra classe carica il suo file.
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-accesso-emergenza.php';

final class Rdacademy_Area_Tecnica {

	/** I controlli, nell ordine del mockup (ridotto). */
	const CONTROLLI = array(
		'permessi' => 'Permessi e cartelle',
		'cache'    => 'Cache del codice',
		'file'     => 'Versione e file',
		'sorgenti' => 'Sorgenti del database',
	);

	/** L elenco dei file del pacchetto (md5 per file), scritto dal generatore del pacchetto. */
	const MANIFESTO = 'includes/manifest-file.php';

	const MAX_NOMI = 8;

	// R6 — il registro operazioni e lo stesso del Primo accesso: costante del prodotto, una sola.
	const AUDIT_OPT = Rdacademy_Accesso_Emergenza::AUDIT_OPT;
	const AUDIT_MAX = Rdacademy_Accesso_Emergenza::AUDIT_MAX;

	public static function controlli(): array {
		return (array) apply_filters( 'rdacademy_area_tecnica_controlli', self::CONTROLLI );
	}

	/** Esegue un controllo. Mai un eccezione verso l alto: un controllo che si rompe e un ROSSO che lo dice. */
	public static function esegui( string $k ): array {
		$tutti = self::controlli();
		if ( ! isset( $tutti[ $k ] ) ) {
			return self::esito( $k, 'rosso', 'Controllo sconosciuto', '', 'Ricarica la pagina.', array() );
		}
		try {
			switch ( $k ) {
				case 'permessi': return self::permessi();
				case 'cache':    return self::cache();
				case 'file':     return self::file();
				case 'sorgenti': return self::sorgenti();
				default:
					$e = apply_filters( 'rdacademy_area_tecnica_esegui_' . $k, null );
					return is_array( $e ) ? $e : self::esito( $k, 'rosso', 'Il controllo non ha risposto', '', 'Chiama l’assistenza e incolla «Copia esito».', array() );
			}
		} catch ( Throwable $e ) {
			return self::esito( $k, 'rosso', 'Il controllo non è riuscito', '', 'Chiama l’assistenza e incolla «Copia esito».',
				array( array( 'Errore', $e->getMessage(), 'ko' ) ) );
		}
	}

	/** Il testo di «Copia esito» (copiato). */
	public static function referto( array $esiti, string $testata = '' ): string {
		$o = $testata !== '' ? rtrim( $testata ) . "\n\n" : '';
		foreach ( $esiti as $e ) {
			if ( ! is_array( $e ) || ! isset( $e['titolo'] ) ) continue;
			$o .= ( ( $e['stato'] ?? '' ) === 'verde' ? '[VERDE] ' : '[ROSSO] ' ) . $e['titolo'] . ': ' . ( $e['esito'] ?? '' )
				. ( ! empty( $e['nota'] ) ? ' — ' . $e['nota'] : '' ) . "\n";
			if ( ( $e['stato'] ?? '' ) !== 'verde' && ! empty( $e['rimedio'] ) ) $o .= '    Che cosa fare: ' . $e['rimedio'] . "\n";
			foreach ( (array) ( $e['dettaglio'] ?? array() ) as $d ) $o .= '    · ' . $d[0] . ': ' . $d[1] . "\n";
		}
		return $o;
	}

	public static function esito( string $k, string $stato, string $esito, string $nota, string $rimedio, array $dettaglio ): array {
		$tutti = self::controlli();
		return array( 'k' => $k, 'titolo' => $tutti[ $k ] ?? $k, 'stato' => $stato === 'verde' ? 'verde' : 'rosso',
			'esito' => $esito, 'nota' => $nota, 'rimedio' => $stato === 'verde' ? '' : $rimedio, 'dettaglio' => array_values( $dettaglio ) );
	}

	/* ===================================================================== *
	 *  1 · PERMESSI E CARTELLE
	 * ===================================================================== */

	/** La prova di scrittura (copiata): un file temporaneo, letto e cancellato subito. */
	private static function prova( string $dir ): bool {
		$tmp  = rtrim( $dir, '/' ) . '/.rdacademy-area-tecnica-' . substr( md5( uniqid( '', true ) ), 0, 12 ) . '.tmp';
		$dato = RDACADEMY_PRODUCT . ' ' . uniqid( '', true );
		$scritto = @file_put_contents( $tmp, $dato ) !== false;
		$letto   = $scritto && @file_get_contents( $tmp ) === $dato;
		if ( $scritto ) @unlink( $tmp );
		return $letto && ! file_exists( $tmp );
	}

	private static function permessi(): array {
		$righe = array(); $ko = array(); $rim = array();
		$up = wp_upload_dir();
		foreach ( array( 'rdacademy-branding' => 'Cartella del logo dell’azienda', 'rdacademy-avatars' => 'Cartella delle foto degli utenti' ) as $sub => $lab ) {
			$dir = trailingslashit( (string) $up['basedir'] ) . $sub;
			if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
				$righe[] = array( $lab, 'non si crea', 'ko' );
				$ko[] = 'una cartella del prodotto non si crea';
				$rim[] = 'Chiedi al fornitore dell’hosting di permettere al sito di scrivere in wp-content/uploads';
				continue;
			}
			$ok = self::prova( $dir );
			$righe[] = array( $lab . ' (scrittura e lettura di prova)', $ok ? 'riuscita' : 'non riuscita', $ok ? 'ok' : 'ko' );
			if ( ! $ok ) { $ko[] = 'la cartella ' . $sub . ' non si scrive'; $rim[] = 'Chiedi al fornitore dell’hosting il permesso di scrittura su wp-content/uploads/' . $sub; }
		}
		$priv = trailingslashit( WP_CONTENT_DIR ) . 'rdacademy-private';
		if ( ! is_dir( $priv ) ) {
			$righe[] = array( 'Cartella privata degli allegati', 'non ancora presente: nasce con gli allegati', 'info' );
		} else {
			$righe[] = array( 'Cartella privata degli allegati', 'presente', 'ok' );
			$ok = self::prova( $priv );
			$righe[] = array( 'Scrittura e lettura di prova (allegati)', $ok ? 'riuscita' : 'non riuscita', $ok ? 'ok' : 'ko' );
			if ( ! $ok ) { $ko[] = 'la cartella degli allegati non si scrive'; $rim[] = 'Chiedi al fornitore dell’hosting di dare al sito il permesso di scrittura sulla cartella wp-content/rdacademy-private'; }
			$prot = is_file( $priv . '/.htaccess' );
			$righe[] = array( 'Protezione dal web (.htaccess)', $prot ? 'presente' : 'manca: i file potrebbero essere scaricabili da internet', $prot ? 'ok' : 'ko' );
			if ( ! $prot ) { $ko[] = 'manca la protezione dal web'; $rim[] = 'Chiama subito l’assistenza e incolla «Copia esito»: la cartella degli allegati non è protetta'; }
		}
		if ( ! $ko ) return self::esito( 'permessi', 'verde', 'Le cartelle del prodotto si scrivono e si leggono', '', '', $righe );
		return self::esito( 'permessi', 'rosso', ucfirst( implode( ' · ', $ko ) ), '', implode( '. ', $rim ) . '.', $righe );
	}

	/* ===================================================================== *
	 *  2 · CACHE DEL CODICE (RP-DEPLOY-OPCACHE) — copiato
	 * ===================================================================== */

	private static function cache(): array {
		$inst   = defined( 'RDACADEMY_VERSION_LABEL' ) ? RDACADEMY_VERSION_LABEL : RDACADEMY_VERSION;
		$booted = (string) get_option( 'rdacademy_booted_version', '' ); // chiave di avvio, ESENTI_DAL_1
		$opc    = function_exists( 'opcache_get_status' );
		$usa    = $booted === '' ? '' : ( $booted === RDACADEMY_VERSION ? $inst : $booted );
		$righe  = array(
			array( 'Versione installata', $inst, 'ok' ),
			array( 'Versione che il server sta usando', $usa !== '' ? $usa : 'non ancora registrata', $booted === '' ? 'info' : ( $booted === RDACADEMY_VERSION ? 'ok' : 'ko' ) ),
			array( 'Svuotamento automatico a ogni versione', 'sempre attivo', 'ok' ),
			array( 'Cache del codice sul server (OPcache)', $opc ? 'presente' : 'non presente', 'info' ),
		);
		if ( $booted !== '' && $booted !== RDACADEMY_VERSION ) {
			return self::esito( 'cache', 'rosso', 'Il server sta ancora usando la versione ' . $booted, '',
				'Premi «Svuota la cache ora» qui sotto, ricarica la pagina e premi «Esegui i controlli».', $righe );
		}
		return self::esito( 'cache', 'verde', 'Il server usa la versione installata (' . $inst . ')',
			$booted === '' ? 'il server registra la versione al prossimo caricamento' : '', '', $righe );
	}

	/* ===================================================================== *
	 *  3 · VERSIONE E FILE — copiato
	 * ===================================================================== */

	private static function file(): array {
		$rad  = trailingslashit( RDACADEMY_PLUGIN_DIR );
		$inst = defined( 'RDACADEMY_VERSION_LABEL' ) ? RDACADEMY_VERSION_LABEL : RDACADEMY_VERSION;
		$rim  = 'Reinstalla il pacchetto ' . $inst . ': Plugin › Aggiungi nuovo › Carica plugin, e scegli «Sostituisci quello installato».';
		$man  = is_file( $rad . self::MANIFESTO ) ? include $rad . self::MANIFESTO : null;
		if ( ! is_array( $man ) || empty( $man['file'] ) || ! is_array( $man['file'] ) ) {
			return self::esito( 'file', 'rosso', 'Manca l’elenco dei file del pacchetto', 'l’elenco lo scrive il generatore del pacchetto', $rim, array( array( 'Elenco dei file', 'assente', 'ko' ) ) );
		}
		$attesi = $man['file']; $n = count( $attesi );
		$mancanti = array(); $cambiati = array();
		foreach ( $attesi as $rel => $md5 ) {
			$f = $rad . $rel;
			if ( ! is_file( $f ) ) { $mancanti[] = $rel; continue; }
			if ( md5_file( $f ) !== $md5 ) $cambiati[] = $rel;
		}
		$in_piu = array();
		$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $rad, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $f ) {
			if ( ! $f->isFile() ) continue;
			$rel = str_replace( '\\', '/', substr( $f->getPathname(), strlen( $rad ) ) );
			if ( $rel !== self::MANIFESTO && ! isset( $attesi[ $rel ] ) ) $in_piu[] = $rel;
		}
		$pacc = (string) ( $man['pacchetto'] ?? '' );
		$righe = array(
			array( 'Pacchetto', $inst . ( ! empty( $man['creato'] ) ? ' · costruito il ' . $man['creato'] : '' ), $pacc === RDACADEMY_VERSION ? 'ok' : 'ko' ),
			array( 'File mancanti', $mancanti ? count( $mancanti ) . ': ' . self::nomi( $mancanti ) : '0', $mancanti ? 'ko' : 'ok' ),
			array( 'File cambiati rispetto al pacchetto', $cambiati ? count( $cambiati ) . ': ' . self::nomi( $cambiati ) : '0', $cambiati ? 'ko' : 'ok' ),
		);
		if ( $in_piu ) $righe[] = array( 'File in più', count( $in_piu ) . ': ' . self::nomi( $in_piu ), 'info' );
		$nota = $in_piu ? count( $in_piu ) . ' file in più, non del pacchetto' : '';
		if ( $pacc !== RDACADEMY_VERSION ) {
			return self::esito( 'file', 'rosso', 'L’elenco dei file è del pacchetto ' . ( $pacc !== '' ? $pacc : '?' ) . ', non della versione installata', $nota, $rim, $righe );
		}
		if ( $mancanti || $cambiati ) {
			$cosa = array();
			if ( $cambiati ) $cosa[] = count( $cambiati ) . ' ' . ( count( $cambiati ) === 1 ? 'file cambiato' : 'file cambiati' );
			if ( $mancanti ) $cosa[] = count( $mancanti ) . ' ' . ( count( $mancanti ) === 1 ? 'mancante' : 'mancanti' );
			return self::esito( 'file', 'rosso', $inst . ' · ' . implode( ', ', $cosa ) . ' su ' . $n, $nota, $rim, $righe );
		}
		return self::esito( 'file', 'verde', $inst . ' · ' . $n . ' file attesi, ' . $n . ' trovati, nessuno cambiato', $nota, '', $righe );
	}

	/* ===================================================================== *
	 *  4 · SORGENTI DEL DATABASE — il BASE (le sorgenti 3-5 sono del lotto D)
	 * ===================================================================== */

	private static function sorgenti(): array {
		$righe = array();
		$b = Rdacademy_DB::try_connect();
		if ( isset( $b['connection'] ) && $b['connection'] instanceof mysqli ) { @$b['connection']->close(); }
		if ( empty( $b['ok'] ) ) {
			$cfg = Rdacademy_DB_Config::get();
			$msg = (string) ( $b['error_message'] ?? '' );
			if ( ! empty( $cfg['pass'] ) ) $msg = str_replace( $cfg['pass'], '[REDACTED]', $msg ); // nessun segreto esce
			$righe[] = array( 'BASE (verità)', 'non raggiungibile: ' . self::corto( $msg ), 'ko' );
			return self::esito( 'sorgenti', 'rosso', 'Il BASE non risponde', '', 'Chiedi al fornitore dell’hosting se il database è attivo.', $righe );
		}
		$righe[] = array( 'BASE (verità)', 'collegata · ' . (int) round( (float) $b['latency_ms'] ) . ' ms', 'ok' );
		$righe[] = array( 'Database di WordPress', 'separato dal BASE', ( defined( 'DB_NAME' ) && DB_NAME !== RDACADEMY_DB_NAME ) ? 'ok' : 'ko' );
		$righe = (array) apply_filters( 'rdacademy_area_tecnica_sorgenti_righe', $righe );
		$righe[] = array( 'Sorgenti 3-5', 'non ancora attive in questa versione: tutto vive sul BASE', 'info' );
		return self::esito( 'sorgenti', 'verde', 'BASE collegata', '', '', $righe );
	}

	private static function corto( string $m ): string {
		$m = trim( (string) preg_replace( '/\s+/', ' ', $m ) );
		if ( $m === '' ) return 'nessun motivo dal server';
		return function_exists( 'mb_strimwidth' ) ? mb_strimwidth( $m, 0, 160, '…', 'UTF-8' ) : substr( $m, 0, 160 );
	}

	private static function nomi( array $l ): string {
		return implode( ', ', array_slice( $l, 0, self::MAX_NOMI ) ) . ( count( $l ) > self::MAX_NOMI ? ' e altri ' . ( count( $l ) - self::MAX_NOMI ) : '' );
	}

	/* ===================================================================== *
	 *  LE AZIONI DEL ROUTER — copiate da Reldox_Router (r.14403-14698)
	 * ===================================================================== */

	private static function require_admin( ?array $cur ): array {
		if ( ! $cur ) wp_send_json_error( 'Non autenticato', 401 );
		if ( ! Rdacademy_Router::user_can_admin( $cur ) ) wp_send_json_error( 'Riservato agli amministratori.', 403 );
		return $cur;
	}

	public static function ajax_tech_check( ?array $cur ): void {
		$cur = self::require_admin( $cur );
		if ( ! rdacademy_freno( 'tech_check_' . (int) ( $cur['id'] ?? 0 ), 60, 600 ) ) {
			wp_send_json_error( 'Troppi controlli di seguito: riprova fra qualche minuto.', 429 );
		}
		wp_send_json_success( self::esegui( sanitize_key( (string) ( $_POST['k'] ?? '' ) ) ) );
	}

	/** Tutti i ruoli NON di servizio (il ruolo di servizio e nascosto da ogni UI). */
	private static function all_roles(): array {
		$p = RDACADEMY_TABLE_PREFIX;
		return Rdacademy_DB::get_results(
			"SELECT id, name, color, is_backdoor, permissions, is_system FROM `{$p}roles` WHERE is_backdoor = 0 ORDER BY name ASC"
		) ?: array();
	}

	private static function users_with_roles(): array {
		$p = RDACADEMY_TABLE_PREFIX;
		$users = Rdacademy_DB::get_results( "SELECT id, first_name, last_name, email, status FROM `{$p}users` ORDER BY id ASC" ) ?: array();
		$rolesByUser = array();
		foreach ( Rdacademy_DB::get_results(
			"SELECT ur.user_id, r.id AS role_id, r.name, r.is_backdoor, r.permissions
			   FROM `{$p}user_roles` ur JOIN `{$p}roles` r ON r.id = ur.role_id
			  WHERE r.is_backdoor = 0 ORDER BY r.name ASC" ) ?: array() as $row ) {
			$rolesByUser[ (int) $row['user_id'] ][] = $row;
		}
		foreach ( $users as &$u ) { $u['roles'] = $rolesByUser[ (int) $u['id'] ] ?? array(); }
		unset( $u );
		return $users;
	}

	/**
	 * La rimozione del ruolo $rid dall utente $uid sarebbe bloccata? (copiato)
	 * Verita lato server, anche se l UI disegna la chip bloccata.
	 */
	public static function block_reason_remove( int $uid, int $rid ): string {
		$p    = RDACADEMY_TABLE_PREFIX;
		$rows = Rdacademy_DB::get_results(
			"SELECT r.id, r.is_backdoor, r.permissions FROM `{$p}user_roles` ur JOIN `{$p}roles` r ON r.id = ur.role_id WHERE ur.user_id = ?",
			array( $uid ), 'i'
		) ?: array();
		if ( count( $rows ) <= 1 ) {
			return "È l'ultimo ruolo dell'utente: rimuoverlo lo lascerebbe senza alcun ruolo.";
		}
		$target = null; $remaining_admin = 0;
		foreach ( $rows as $r ) {
			if ( (int) $r['id'] === $rid ) { $target = $r; continue; }
			if ( Rdacademy_Utenti::role_is_admin( $r ) ) $remaining_admin++;
		}
		if ( $target === null ) return '';
		if ( Rdacademy_Utenti::role_is_admin( $target ) && $remaining_admin === 0 ) {
			if ( Rdacademy_Utenti::admin_count( $uid ) === 0 ) {
				return "È l'ultimo amministratore del sistema: rimuoverlo bloccherebbe l'accesso a tutti.";
			}
		}
		return '';
	}

	private static function role_label( int $rid ): string {
		$p  = RDACADEMY_TABLE_PREFIX;
		$rr = Rdacademy_DB::get_row( "SELECT name FROM `{$p}roles` WHERE id = ? LIMIT 1", array( $rid ), 'i' );
		return $rr ? (string) $rr['name'] : ( 'ruolo #' . $rid );
	}

	private static function user_label( int $uid ): string {
		$p  = RDACADEMY_TABLE_PREFIX;
		$uu = Rdacademy_DB::get_row( "SELECT first_name, last_name, email FROM `{$p}users` WHERE id = ? LIMIT 1", array( $uid ), 'i' );
		if ( ! $uu ) return 'utente #' . $uid;
		$name = trim( ( $uu['first_name'] ?? '' ) . ' ' . ( $uu['last_name'] ?? '' ) );
		return $name !== '' ? $name : (string) ( $uu['email'] ?? ( 'utente #' . $uid ) );
	}

	/** Registro operazioni (copiato): ring-buffer sul BASE + error_log. Ora UTC (§ 2.3). */
	public static function audit_write( array $cur, string $kind, string $text ): void {
		$log = Rdacademy_Option::bridge_get( self::AUDIT_OPT, array() );
		if ( ! is_array( $log ) ) $log = array();
		$who = Rdacademy_Auth::nome_visibile( $cur );
		if ( $who === '' ) $who = 'admin #' . (int) ( $cur['id'] ?? 0 );
		array_unshift( $log, array( 'ts' => gmdate( 'Y-m-d H:i:s' ), 'actor' => $who, 'kind' => $kind, 'text' => $text, 'da' => 'area-tecnica' ) );
		if ( count( $log ) > self::AUDIT_MAX ) $log = array_slice( $log, 0, self::AUDIT_MAX );
		Rdacademy_Option::set( self::AUDIT_OPT, $log );
		error_log( '[RelDoxAcademy Tech] ' . $kind . ': ' . $text . ' (by ' . $who . ')' );
	}

	public static function ajax_tech_roles( ?array $cur ): void {
		self::require_admin( $cur );
		$users = array();
		foreach ( self::users_with_roles() as $u ) {
			$name  = trim( ( $u['first_name'] ?? '' ) . ' ' . ( $u['last_name'] ?? '' ) );
			$roles = array();
			foreach ( (array) $u['roles'] as $r ) {
				$roles[] = array( 'id' => (int) $r['role_id'], 'label' => (string) $r['name'], 'adm' => Rdacademy_Utenti::role_is_admin( $r ),
					'lock' => self::block_reason_remove( (int) $u['id'], (int) $r['role_id'] ) !== '' );
			}
			$users[] = array(
				'id'     => (int) $u['id'],
				'name'   => $name !== '' ? $name : (string) ( $u['email'] ?? ( 'utente #' . $u['id'] ) ),
				'email'  => (string) ( $u['email'] ?? '' ),
				'status' => (string) ( $u['status'] ?? '' ),
				'roles'  => $roles,
			);
		}
		$catalog = array(); $admins = array();
		foreach ( self::all_roles() as $r ) {
			$adm = Rdacademy_Utenti::role_is_admin( $r );
			$row = array( 'id' => (int) $r['id'], 'label' => (string) $r['name'], 'adm' => $adm );
			$catalog[] = $row;
			if ( $adm ) $admins[] = $row;
		}
		wp_send_json_success( array(
			'users'       => $users,
			'roles'       => $catalog,
			'admin_roles' => $admins,
			'admin_count' => Rdacademy_Utenti::admin_count(),
		) );
	}

	public static function ajax_tech_role_add( ?array $cur ): void {
		$cur = self::require_admin( $cur );
		$p   = RDACADEMY_TABLE_PREFIX;
		$uid = (int) ( $_POST['user_id'] ?? 0 );
		$rid = (int) ( $_POST['role_id'] ?? 0 );
		if ( $uid <= 0 || $rid <= 0 ) wp_send_json_error( 'Parametri non validi.' );
		$u = Rdacademy_DB::get_row( "SELECT id FROM `{$p}users` WHERE id = ? LIMIT 1", array( $uid ), 'i' );
		$r = Rdacademy_DB::get_row( "SELECT id FROM `{$p}roles` WHERE id = ? AND is_backdoor = 0 LIMIT 1", array( $rid ), 'i' );
		if ( ! $u || ! $r ) wp_send_json_error( 'Utente o ruolo inesistente.' );
		Rdacademy_DB::execute(
			"INSERT IGNORE INTO `{$p}user_roles` (user_id, role_id, assigned_by) VALUES (?, ?, ?)",
			array( $uid, $rid, (int) ( $cur['id'] ?? 0 ) ), 'iii'
		);
		Rdacademy_Security::invalidate_role_cache( $uid );
		self::audit_write( $cur, 'add', 'Ruolo "' . self::role_label( $rid ) . '" aggiunto a ' . self::user_label( $uid ) . '.' );
		wp_send_json_success( array( 'message' => 'Ruolo aggiunto.' ) );
	}

	public static function ajax_tech_role_remove( ?array $cur ): void {
		$cur = self::require_admin( $cur );
		$p   = RDACADEMY_TABLE_PREFIX;
		$uid = (int) ( $_POST['user_id'] ?? 0 );
		$rid = (int) ( $_POST['role_id'] ?? 0 );
		if ( $uid <= 0 || $rid <= 0 ) wp_send_json_error( 'Parametri non validi.' );
		$reason = self::block_reason_remove( $uid, $rid );
		if ( $reason !== '' ) {
			wp_send_json_error( 'Operazione bloccata. ' . $reason );
		}
		Rdacademy_DB::execute(
			"DELETE ur FROM `{$p}user_roles` ur JOIN `{$p}roles` r ON r.id = ur.role_id
			  WHERE ur.user_id = ? AND ur.role_id = ? AND r.is_backdoor = 0",
			array( $uid, $rid ), 'ii'
		);
		Rdacademy_Security::invalidate_role_cache( $uid );
		self::audit_write( $cur, 'remove', 'Ruolo "' . self::role_label( $rid ) . '" rimosso da ' . self::user_label( $uid ) . '.' );
		wp_send_json_success( array( 'message' => 'Ruolo rimosso.' ) );
	}

	public static function ajax_tech_role_restore( ?array $cur ): void {
		$cur = self::require_admin( $cur );
		$p   = RDACADEMY_TABLE_PREFIX;
		$uid = (int) ( $_POST['user_id'] ?? 0 );
		$rid = (int) ( $_POST['role_id'] ?? 0 );
		if ( $uid <= 0 || $rid <= 0 ) wp_send_json_error( 'Parametri non validi.' );
		$u = Rdacademy_DB::get_row( "SELECT id FROM `{$p}users` WHERE id = ? LIMIT 1", array( $uid ), 'i' );
		$r = Rdacademy_DB::get_row( "SELECT id, is_backdoor, permissions FROM `{$p}roles` WHERE id = ? AND is_backdoor = 0 LIMIT 1", array( $rid ), 'i' );
		if ( ! $u || ! $r ) wp_send_json_error( 'Utente o ruolo inesistente.' );
		if ( ! Rdacademy_Utenti::role_is_admin( $r ) ) {
			wp_send_json_error( 'Il ruolo selezionato non conferisce privilegi amministrativi.' );
		}
		Rdacademy_DB::execute(
			"INSERT IGNORE INTO `{$p}user_roles` (user_id, role_id, assigned_by) VALUES (?, ?, ?)",
			array( $uid, $rid, (int) ( $cur['id'] ?? 0 ) ), 'iii'
		);
		Rdacademy_Security::invalidate_role_cache( $uid );
		self::audit_write( $cur, 'restore', 'Accesso ripristinato: ruolo "' . self::role_label( $rid ) . '" riassegnato a ' . self::user_label( $uid ) . '.' );
		wp_send_json_success( array( 'message' => 'Accesso ripristinato.' ) );
	}

	/** Copiato (r.14658). TAGLIO: views_ts (class-reldox-user-views.php, CRM). */
	public static function ajax_tech_opcache( ?array $cur ): void {
		$cur      = self::require_admin( $cur );
		$do_reset = ( (string) ( $_POST['reset'] ?? '' ) === '1' );
		$res = array( 'available' => function_exists( 'opcache_get_status' ), 'enabled' => false, 'scripts' => null, 'mem_used' => null, 'reset' => null, 'note' => '' );
		if ( ! $res['available'] ) {
			$res['note'] = 'OPcache non disponibile su questo PHP: il bytecode non viene cacheato.';
			wp_send_json_success( $res );
		}
		$st = @opcache_get_status( false );
		if ( is_array( $st ) ) {
			$res['enabled']  = ! empty( $st['opcache_enabled'] );
			$res['scripts']  = isset( $st['opcache_statistics']['num_cached_scripts'] ) ? (int) $st['opcache_statistics']['num_cached_scripts'] : null;
			$res['mem_used'] = isset( $st['memory_usage']['used_memory'] ) ? round( $st['memory_usage']['used_memory'] / 1048576, 1 ) : null;
		}
		if ( $do_reset && function_exists( 'opcache_reset' ) ) {
			$res['reset'] = @opcache_reset() ? 'ok' : 'fallito';
			self::audit_write( $cur, 'opcache', 'Reset OPcache: ' . (string) $res['reset'] . '.' );
		}
		wp_send_json_success( $res );
	}
}
