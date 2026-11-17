<?php
/**
 * RelDoxAcademy — PRIMO ACCESSO E RIENTRO D EMERGENZA (wp-admin, prodotto).
 * =====================================================================
 * NUOVO in 0.1.02 (SPEC-MANUTENZIONE-E-DIAGNOSTICA-v1 § 6, R4): la logica del
 * «Ripristina accesso admin» e copiata da Reldox_Maintenance::ajax_restore_admin
 * (RelDox CRD 3.55.09G, BUG-78 S260 + S590A_RIENTRO voce 82), che nel guscio
 * 0.1.01 viveva nel pannello degli strumenti di sviluppo.
 *
 * PERCHE E DEL PRODOTTO. L utente di servizio nasce SENZA password nota
 * (D-82): il primo accesso di OGNI installazione passa da qui. E un gesto che
 * serve al cliente anche quando gli strumenti di sviluppo non sono installati,
 * quindi la sua logica, la sua azione admin-ajax e una pagina minima stanno nel
 * prodotto. Gli strumenti di sviluppo, se ci sono, lo ridisegnano chiamando la
 * STESSA azione (AJAX) con lo STESSO nonce (NONCE): mai una copia.
 *
 * RAZIONALE — perche wp-admin e non /academy/ (copiato): wp-admin usa
 * l autenticazione WordPress nativa, un piano indipendente da Rdacademy_Auth. Se i
 * ruoli dell app si rompono, wp-admin resta accessibile.
 *
 * LE COSTANTI CONDIVISE (R6) vivono qui: NONCE, AUDIT_OPT, AUDIT_MAX, AJAX.
 * Chi le legge fa require_once di questo file (R5).
 *
 * VINCOLI: manage_options + nonce dentro l handler; Rdacademy_DB con query
 * preparate; nessuna password nel sorgente; il segreto del collegamento esiste
 * in chiaro SOLO nel ritorno di ripristina() (sul database c e l impronta).
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Rdacademy_Accesso_Emergenza' ) ) :

final class Rdacademy_Accesso_Emergenza {

	/** La pagina wp-admin del prodotto (sottomenu del menu `rdacademy`). */
	const PAGE_SLUG = 'rdacademy-primo-accesso';

	/** L azione admin-ajax del rientro: UNA, del prodotto. */
	const AJAX = 'rdacademy_accesso_ripristina';

	/** Il nonce delle azioni wp-admin sui ruoli e sul rientro (R6: si dichiara nel prodotto). */
	const NONCE = 'rdacademy_accesso_nonce';

	/**
	 * Il registro operazioni (Rdacademy_Option sul BASE, ring buffer). Il nome della
	 * chiave resta quello della 0.1.01, cosi il registro gia scritto continua.
	 */
	const AUDIT_OPT = 'rdacademy_maint_audit';
	const AUDIT_MAX = 50;

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_submenu' ), 12 );
		add_action( 'wp_ajax_' . self::AJAX, array( __CLASS__, 'ajax_ripristina' ) );
	}

	public static function register_submenu(): void {
		add_submenu_page(
			Rdacademy_Admin::SLUG,
			'Primo accesso',
			'Primo accesso',
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/* ================================================================= *
	 *  Dati (copiati da Reldox_Maintenance, resi pubblici: li usa anche chi ridisegna)
	 * ================================================================= */

	/** Una riga-ruolo conferisce privilegi admin? is_backdoor=1 OPPURE permissions.all truthy. */
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

	/** Tutti i ruoli. */
	public static function all_roles(): array {
		$p = RDACADEMY_TABLE_PREFIX;
		return Rdacademy_DB::get_results(
			"SELECT id, name, color, is_backdoor, permissions, is_system
			   FROM `{$p}roles` ORDER BY is_backdoor DESC, name ASC"
		);
	}

	/** Ruoli con privilegi admin. */
	public static function admin_roles(): array {
		$out = array();
		foreach ( self::all_roles() as $r ) {
			if ( self::role_is_admin( $r ) ) $out[] = $r;
		}
		return $out;
	}

	/** Ruolo «consigliato» per il rientro (prima il Service Account). */
	public static function recommended_backdoor_role(): ?array {
		$backdoors = array();
		foreach ( self::all_roles() as $r ) {
			if ( ! empty( $r['is_backdoor'] ) ) $backdoors[] = $r;
		}
		if ( empty( $backdoors ) ) {
			$adm = self::admin_roles();
			return $adm[0] ?? null;
		}
		foreach ( $backdoors as $b ) {
			if ( strtolower( (string) $b['name'] ) === 'service account' ) return $b;
		}
		return $backdoors[0];
	}

	public static function role_label( int $rid ): string {
		$p  = RDACADEMY_TABLE_PREFIX;
		$rr = Rdacademy_DB::get_row( "SELECT name FROM `{$p}roles` WHERE id = ?", array( $rid ), 'i' );
		return $rr ? (string) $rr['name'] : ( 'ruolo #' . $rid );
	}

	public static function user_label( int $uid ): string {
		$p  = RDACADEMY_TABLE_PREFIX;
		$uu = Rdacademy_DB::get_row( "SELECT first_name, last_name, email FROM `{$p}users` WHERE id = ?", array( $uid ), 'i' );
		if ( ! $uu ) return ( 'utente #' . $uid );
		$name = trim( ( $uu['first_name'] ?? '' ) . ' ' . ( $uu['last_name'] ?? '' ) );
		return $name !== '' ? $name : (string) ( $uu['email'] ?? ( 'utente #' . $uid ) );
	}

	/** assigned_by: id dell app corrente se risolvibile (solo sessione del prodotto), altrimenti 0. */
	public static function assigned_by(): int {
		$id = (int) Rdacademy_Security::resolve_current_user_id();
		return $id > 0 ? $id : 0;
	}

	/* ================================================================= *
	 *  Registro operazioni (Rdacademy_Option sul BASE, ring buffer, istanti UTC)
	 * ================================================================= */

	public static function audit_read(): array {
		$log = Rdacademy_Option::get( self::AUDIT_OPT, array() );
		return is_array( $log ) ? $log : array();
	}

	public static function audit_write( string $kind, string $text ): void {
		$log   = self::audit_read();
		$actor = wp_get_current_user();
		$who   = $actor && $actor->exists()
			? ( $actor->display_name ?: $actor->user_login )
			: 'sconosciuto';
		array_unshift( $log, array(
			'ts'    => gmdate( 'Y-m-d H:i:s' ) . ' UTC',
			'actor' => $who,
			'kind'  => $kind,   // add | remove | restore
			'text'  => $text,
		) );
		if ( count( $log ) > self::AUDIT_MAX ) {
			$log = array_slice( $log, 0, self::AUDIT_MAX );
		}
		Rdacademy_Option::set( self::AUDIT_OPT, $log, false );
		error_log( '[' . RDACADEMY_PRODUCT . ' wp-admin] ' . $kind . ': ' . $text . ' (by ' . $who . ')' );
	}

	/* ================================================================= *
	 *  L azione
	 * ================================================================= */

	public static function ajax_ripristina(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Accesso negato (richiesto manage_options).', 403 );
		}
		if ( ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( 'Token di sicurezza non valido. Ricarica la pagina e riprova.', 403 );
		}
		$r = self::ripristina( (int) ( $_POST['user_id'] ?? 0 ), (int) ( $_POST['role_id'] ?? 0 ) );
		if ( empty( $r['ok'] ) ) {
			wp_send_json_error( (string) $r['error'] );
		}
		unset( $r['ok'] );
		wp_send_json_success( $r );
	}

	/**
	 * IL RIENTRO D EMERGENZA (copiato da Reldox_Maintenance::ajax_restore_admin).
	 *
	 * BUG-78 di RelDox — IL NOME PROMETTEVA L ACCESSO, IL CODICE DAVA I RUOLI:
	 * qui le cause di rifiuto si tolgono TUTTE INSIEME (password resa
	 * inutilizzabile, stato attivo, tentativi a zero, blocco e scadenza tolti,
	 * gettone vecchio annullato), le sessioni si chiudono, il ruolo si
	 * garantisce e chi rientra sceglie la password nuova dal collegamento monouso.
	 *
	 * @return array{ok:bool,error?:string,message?:string,link?:string,minuti?:int,avviso?:string}
	 */
	public static function ripristina( int $uid, int $rid ): array {
		$p = RDACADEMY_TABLE_PREFIX;
		if ( $uid <= 0 || $rid <= 0 ) return array( 'ok' => false, 'error' => 'Parametri non validi.' );

		$u = Rdacademy_DB::get_row( "SELECT id FROM `{$p}users` WHERE id = ?", array( $uid ), 'i' );
		$r = Rdacademy_DB::get_row( "SELECT id, is_backdoor, permissions FROM `{$p}roles` WHERE id = ?", array( $rid ), 'i' );
		if ( ! $u || ! $r ) return array( 'ok' => false, 'error' => 'Utente o ruolo inesistente.' );
		if ( ! self::role_is_admin( $r ) ) {
			return array( 'ok' => false, 'error' => 'Il ruolo selezionato non conferisce privilegi amministrativi.' );
		}

		$by = self::assigned_by();

		$impronta = password_hash( bin2hex( random_bytes( 32 ) ), PASSWORD_BCRYPT );   /* nessuno la conosce */
		Rdacademy_DB::clear_last_error();
		$n_cred = Rdacademy_DB::execute(
			"UPDATE `{$p}users`
			    SET password = ?, status = 'active', failed_logins = 0,
			        locked_until = NULL, password_expires_at = NULL,
			        reset_token = NULL, reset_expiry = NULL
			  WHERE id = ?",
			array( $impronta, $uid ), 'si'
		);
		// ⛔ In RelDox il controllo era `$n_cred < 0`, che affected_rows non restituisce
		// mai: un UPDATE fallito passava come riuscito. Qui si legge l errore vero.
		if ( $n_cred < 1 && Rdacademy_DB::last_error() !== '' ) {
			return array( 'ok' => false, 'error' => 'Le credenziali NON sono state scritte: ' . Rdacademy_DB::last_error() );
		}

		$segreto = Rdacademy_Password_Token::emetti( $uid, Rdacademy_Password_Token::SCOPO_REIMPOSTA );
		if ( $segreto === null ) {
			self::audit_write( 'restore', 'RIENTRO D EMERGENZA su ' . self::user_label( $uid )
				. ': stato azzerato, collegamento di rientro NON emesso.' );
			return array( 'ok' => false, 'error' => 'Stato azzerato, ma il collegamento di rientro non è stato emesso. Riprova.' );
		}
		$link = Rdacademy_Password_Mail::link_imposta( $segreto );
		unset( $segreto );

		// Le sessioni vecchie non riparano niente e possono far comparire schermate incoerenti.
		Rdacademy_DB::execute( "DELETE FROM `{$p}sessions` WHERE user_id = ?", array( $uid ), 'i' );

		// Il ruolo: additivo. ⛔ NON FA FALLIRE L OPERAZIONE se era gia assegnato.
		Rdacademy_DB::execute(
			"INSERT IGNORE INTO `{$p}user_roles` (user_id, role_id, assigned_by) VALUES (?, ?, ?)",
			array( $uid, $rid, $by ), 'iii'
		);
		Rdacademy_Security::invalidate_role_cache( $uid );

		$minuti = (int) Rdacademy_Password_Token::MINUTI_REIMPOSTA;
		self::audit_write( 'restore',
			'RIENTRO D EMERGENZA su ' . self::user_label( $uid ) . ': collegamento di rientro emesso '
			. '(vale ' . $minuti . ' minuti, una volta sola; password resa inutilizzabile, stato attivo, '
			. 'blocchi e scadenze azzerati, sessioni chiuse), ruolo "' . self::role_label( $rid ) . '" garantito.' );
		Rdacademy_Auth::log_activity( $uid, 'auth', 'admin_restore',
			'Accesso ripristinato da wp-admin: collegamento di rientro emesso.' );

		return array(
			'ok'      => true,
			'message' => 'Accesso ripristinato.',
			'link'    => $link,
			'minuti'  => $minuti,
			'avviso'  => 'Apri questo collegamento (oppure mandalo a ' . self::user_label( $uid ) . '): si sceglie la '
				. 'password nuova e si entra. Vale ' . $minuti . ' minuti e si usa una volta sola; non lo ritrovi più dopo '
				. 'aver chiuso questa pagina.',
		);
	}

	/* ================================================================= *
	 *  La pagina minima del prodotto: «Primo accesso»
	 * ================================================================= */

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Accesso negato: questa pagina richiede manage_options.', esc_html( RDACADEMY_PRODUCT ), array( 'response' => 403 ) );
		}
		$p      = RDACADEMY_TABLE_PREFIX;
		$users  = Rdacademy_DB::get_results( "SELECT id, first_name, last_name, email, status FROM `{$p}users` ORDER BY id ASC" );
		$roles  = self::admin_roles();
		$rec    = self::recommended_backdoor_role();
		$rec_id = $rec ? (int) $rec['id'] : 0;
		$spento = ! $users ? 'non ci sono utenti.' : ( ! $roles ? 'non esiste un ruolo con privilegi amministrativi.' : '' );
		$audit  = array_values( array_filter( self::audit_read(), static fn( $a ) => ( $a['kind'] ?? '' ) === 'restore' ) );
		$minuti = (int) Rdacademy_Password_Token::MINUTI_REIMPOSTA;
		$app    = '/' . RDACADEMY_APP_SLUG . '/';
		?>
		<div class="wrap rdacademy-pa"><hr class="wp-header-end"><?php /* 0.8.10A (RF-BUG-026): gli avvisi di WordPress sopra la testata, non dentro */ ?>
		<style>
		.rdacademy-pa{max-width:860px}
		.rdacademy-pa [hidden]{display:none!important}
		.rdacademy-pa h1{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:23px;font-weight:700}
		.rdacademy-pa .sub{color:#4b5563;font-size:13.5px;line-height:1.6;max-width:72ch;margin:6px 0 16px}
		.rdacademy-pa .box{background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:18px 20px;margin:0 0 16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
		.rdacademy-pa .grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px}
		.rdacademy-pa label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.3px;color:#374151;margin-bottom:5px}
		.rdacademy-pa select{width:100%;max-width:100%}
		.rdacademy-pa .nota{font-size:12px;color:#4b5563;line-height:1.55;margin:12px 0 0}
		.rdacademy-pa .piede{display:flex;justify-content:flex-end;margin-top:14px}
		.rdacademy-pa .esito{margin:0 0 16px;padding:14px 16px;border:2px solid #B42318;background:#FEF3F2;border-radius:10px;color:#7A271A;font-size:14px;line-height:1.6}
		.rdacademy-pa .esito .t{font-weight:700;margin-bottom:6px}
		.rdacademy-pa .esito .r{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px}
		.rdacademy-pa .esito a{flex:1 1 260px;min-width:0;word-break:break-all;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;color:#7A271A}
		.rdacademy-pa .errore{display:flex;gap:10px;align-items:flex-start;margin:0 0 16px;padding:12px 14px;border:1px solid #fecaca;background:#fee2e2;border-radius:10px;color:#7f1d1d;font-weight:600;user-select:text}
		.rdacademy-pa .errore span{flex:1}
		.rdacademy-pa .errore button{border:0;background:transparent;font-size:18px;line-height:1;color:#7f1d1d;cursor:pointer}
		.rdacademy-pa .reg{font-size:12.5px;color:#374151;margin:0;padding-left:18px}
		.rdacademy-pa .reg li{margin:0 0 6px;overflow-wrap:anywhere}
		@media (max-width:640px){.rdacademy-pa .grid{grid-template-columns:minmax(0,1fr)}.rdacademy-pa .piede .button{width:100%;text-align:center}}
		</style>
		<h1>Primo accesso</h1>
		<p class="sub">L’utente di servizio <code><?php echo esc_html( RDACADEMY_SERVICE_EMAIL ); ?></code> nasce <strong>senza password nota</strong>. Da qui si crea un <strong>collegamento monouso</strong> (vale <?php echo $minuti; ?> minuti, una volta sola) con cui si sceglie la password e si entra in <code><?php echo esc_html( $app ); ?></code>. Serve anche come rientro d’emergenza: se un amministratore ha perso l’accesso, scegli lui.</p>

		<div class="errore" id="rdacademy-pa-err" role="alert" hidden><span id="rdacademy-pa-err-t"></span><button type="button" id="rdacademy-pa-err-x" aria-label="Chiudi">&times;</button></div>
		<div class="esito" id="rdacademy-pa-esito" role="status" hidden>
			<div class="t">Collegamento monouso per la password nuova</div>
			<div id="rdacademy-pa-avviso"></div>
			<div class="r"><a id="rdacademy-pa-link" href="#" target="_blank" rel="noopener"></a><button type="button" class="button" id="rdacademy-pa-copia">Copia</button></div>
		</div>

		<div class="box">
			<div class="grid">
				<div>
					<label for="rdacademy-pa-user">Utente</label>
					<select id="rdacademy-pa-user"<?php disabled( $spento !== '' ); ?>>
						<?php foreach ( $users as $u ) :
							$lbl = trim( ( $u['first_name'] ?? '' ) . ' ' . ( $u['last_name'] ?? '' ) ); ?>
							<option value="<?php echo (int) $u['id']; ?>" data-srv="<?php echo (string) $u['email'] === RDACADEMY_SERVICE_EMAIL ? '1' : '0'; ?>" <?php selected( (string) $u['email'] === RDACADEMY_SERVICE_EMAIL ); ?>><?php echo esc_html( $u['email'] . ( $lbl !== '' ? ' — ' . $lbl : '' ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="rdacademy-pa-role">Ruolo con privilegi da garantire</label>
					<select id="rdacademy-pa-role"<?php disabled( $spento !== '' ); ?>>
						<?php foreach ( $roles as $r ) : $is = ( (int) $r['id'] === $rec_id ); ?>
							<option value="<?php echo (int) $r['id']; ?>" data-bd="<?php echo ! empty( $r['is_backdoor'] ) ? '1' : '0'; ?>" <?php selected( $is ); ?>><?php echo esc_html( $r['name'] . ( ! empty( $r['is_backdoor'] ) ? ' · ruolo di servizio' : '' ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<p class="nota">Che cosa succede: la password attuale dell’utente scelto smette di valere, l’account torna attivo, blocchi e scadenze si azzerano, le sessioni aperte si chiudono e il ruolo si aggiunge senza togliere gli altri. L’operazione si registra qui sotto.</p>
			<div class="piede">
				<button type="button" class="button button-primary" id="rdacademy-pa-go"<?php disabled( $spento !== '' ); ?> title="<?php echo esc_attr( $spento !== '' ? 'Spento: ' . $spento : '' ); ?>">Crea il collegamento di accesso</button>
			</div>
		</div>

		<div class="box">
			<label>Ultimi rientri registrati</label>
			<?php if ( ! $audit ) : ?>
				<p class="nota" style="margin:0">Nessun rientro registrato.</p>
			<?php else : ?>
				<ul class="reg" id="rdacademy-pa-reg">
					<?php foreach ( array_slice( $audit, 0, 5 ) as $a ) : ?>
						<li><?php echo esc_html( (string) ( $a['text'] ?? '' ) ); ?> <em><?php echo esc_html( ( $a['actor'] ?? '—' ) . ' · ' . ( $a['ts'] ?? '' ) ); ?></em></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		</div>
		<script>
		(function(){
		 var AJAX=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, AZIONE=<?php echo wp_json_encode( self::AJAX ); ?>, NONCE=<?php echo wp_json_encode( wp_create_nonce( self::NONCE ) ); ?>;
		 var go=document.getElementById('rdacademy-pa-go'), err=document.getElementById('rdacademy-pa-err'), errT=document.getElementById('rdacademy-pa-err-t');
		 var esito=document.getElementById('rdacademy-pa-esito'), link=document.getElementById('rdacademy-pa-link');
		 function errore(m){ errT.textContent=m; err.hidden=false; }
		 document.getElementById('rdacademy-pa-err-x').addEventListener('click',function(){ err.hidden=true; });
		 document.getElementById('rdacademy-pa-copia').addEventListener('click',function(){ var b=this;
		   if(navigator.clipboard&&window.isSecureContext){ navigator.clipboard.writeText(link.href).then(function(){ b.textContent='Copiato'; },function(){ b.textContent='Copia non riuscita'; }); return; }
		   var x=document.createElement('textarea'); x.value=link.href; document.body.appendChild(x); x.select(); try{ document.execCommand('copy'); b.textContent='Copiato'; }catch(e){ b.textContent='Copia non riuscita'; } x.remove(); });
		 if(!go) return;
		 // Il ruolo proposto segue l utente: quello di servizio all utente di servizio, un ruolo amministrativo normale agli altri.
		 var su=document.getElementById('rdacademy-pa-user'), sr=document.getElementById('rdacademy-pa-role');
		 function proponi(){ var o=su.options[su.selectedIndex]; var srv=!!o&&o.getAttribute('data-srv')==='1';
		   for(var i=0;i<sr.options.length;i++){ if((sr.options[i].getAttribute('data-bd')==='1')===srv){ sr.selectedIndex=i; return; } } }
		 su.addEventListener('change',proponi); proponi();
		 go.addEventListener('click',function(){
		  err.hidden=true; go.disabled=true;
		  var b=new URLSearchParams(); b.set('action',AZIONE); b.set('nonce',NONCE);
		  b.set('user_id',document.getElementById('rdacademy-pa-user').value); b.set('role_id',document.getElementById('rdacademy-pa-role').value);
		  fetch(AJAX,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:b.toString()})
		   .then(function(r){ return r.json(); })
		   .then(function(res){ go.disabled=false;
		     if(res&&res.success&&res.data&&res.data.link){ link.href=res.data.link; link.textContent=res.data.link; document.getElementById('rdacademy-pa-avviso').textContent=res.data.avviso||''; esito.hidden=false; esito.scrollIntoView({block:'center'}); }
		     else{ errore((res&&res.data)?String(res.data):'Operazione non riuscita.'); } })
		   .catch(function(){ go.disabled=false; errore('Errore di rete. Riprova.'); });
		 });
		})();
		</script>
		<?php
	}
}

endif;
