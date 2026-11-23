<?php
/**
 * Rdacademy_Banco_Console_SQL — banco standard n. 2 «Console SQL su più sorgenti» (area «Database e sorgenti»).
 * ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
 *
 * RelDoxAcademy 0.1.12A (Formazione F027: BUG-6, F42 d ed e; prima 0.1.11A, F026, voce F29). Disegno: mockup MOCKUP-CONSOLE-SQL-v1 (D45, owner 23/11/2026
 * 08:39, chat Formazione F025: «ok approvato»). Forma comune (REGOLE-FAMIGLIA v8 § 3.10): costanti, init(), render(),
 * esegui(), ajax() con la guardia manage_options + nonce; referto [VERDE]/[ROSSO]/[INFO] con la riga finale
 * «N verdi, M rossi» e «Copia esito». Tutte le regole stanno nel motore (Rdacademy_Console_SQL), dal server:
 * il browser disegna e basta. Banco CLI gemello: banco/F026-banco-console-sql.php (con trappola).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Rdacademy_Banco_Console_SQL {

	const N      = 2;
	const NAT    = 'util';
	const AREA   = 'cat-database';
	const SCHEDA = 'F29';
	const Q      = '2 console sql database sorgenti base wordpress select lettura scrittura prova a secco confermo registro';
	const TITOLO = 'Console SQL su più sorgenti';
	const SCOPO  = 'Misure sul database al posto di phpMyAdmin. Sola lettura per difetto. Una istruzione per volta, al massimo 1000 righe.';
	const AZIONE = 'rdacademy_console_sql';

	public static function init(): void {
		add_action( 'wp_ajax_' . self::AZIONE, array( __CLASS__, 'ajax' ) );
		add_action( 'rdacademy_manutenzione_banchi', array( __CLASS__, 'render' ), 10, 1 );
	}

	public static function render( string $cat = '' ): void {
		if ( $cat !== self::AREA || ! self::puo() ) {
			return;
		}
		$S     = Rdacademy_Console_SQL::sorgenti();
		$nonce = wp_create_nonce( self::AZIONE );
		$id    = 'rxSql';
		echo '<div class="rx-tw" data-nat="' . esc_attr( self::NAT ) . '" data-q="' . esc_attr( self::Q ) . '"><span class="rx-nb b-util"><span class="d"></span>Utility</span>';
		echo '<div class="rx-sql" id="' . $id . '" data-nonce="' . esc_attr( $nonce ) . '" data-ajax="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" data-azione="' . esc_attr( self::AZIONE ) . '">';
		echo '<h2 class="rx-sql-t">' . esc_html( self::TITOLO ) . '</h2><p class="rx-sql-s">' . esc_html( self::SCOPO ) . '</p>';
		echo '<label class="rx-sql-l" for="' . $id . 'Src">Sorgente</label><select id="' . $id . 'Src" class="rx-sql-src">';
		foreach ( $S as $k => $s ) {
			$et = $s['etichetta'] . ( $s['db'] !== '' ? ' · ' . $s['db'] : '' );
			if ( $s['stato'] === 'in_uso' && $k !== 'base' ) $et .= ' — IN USO';
			if ( $s['stato'] === 'spenta' ) $et .= ' — spenta: la console la rifiuta';
			if ( $s['stato'] === 'non_configurata' ) $et .= ' — non configurata: la console la rifiuta';
			if ( $s['stato'] === 'solo_lettura' ) $et .= ' · sola lettura sempre';
			echo '<option value="' . esc_attr( $k ) . '" data-scr="' . ( $s['scrivibile'] ? '1' : '0' ) . '" data-at="' . esc_attr( $s['db'] . ' su ' . $s['host'] . ' · utente: ' . $s['utente'] . ' · password: ' . $s['password'] . ( $s['password'] === 'presente' ? ' (mai mostrata)' : '' ) ) . '">' . esc_html( $et ) . '</option>';
		}
		echo '</select><p class="rx-sql-at" id="' . $id . 'At" aria-live="polite"></p>';
		echo '<label class="rx-sql-l" for="' . $id . 'Q">Istruzione</label><textarea id="' . $id . 'Q" class="rx-sql-q" rows="5" spellcheck="false" autocomplete="off"></textarea>';
		echo '<label class="rx-sql-ck"><input type="checkbox" id="' . $id . 'Ck"> Consenti scrittura (solo BASE e sorgenti IN USO)</label>';
		echo '<div class="rx-sql-b"><button type="button" class="button button-primary" data-f="esegui" data-scrittura="no">Esegui</button><button type="button" class="button" data-f="prova" hidden>Prova a secco</button><button type="button" class="button" data-f="copia" data-scrittura="no">Copia</button><button type="button" class="button" data-f="incolla" data-scrittura="no">Incolla</button><button type="button" class="button" data-f="pulisci">Pulisci</button></div>';
		echo '<div class="rx-sql-cf" id="' . $id . 'Cf" hidden><p>Il CONFERMO vale solo per questa istruzione, su questa sorgente, per 5 minuti; al momento di eseguire le righe si ricontano. Registro append-only sul BASE (mai su WordPress): chi, quando, sorgente, database e host, istruzione, righe, esito; anche i rifiuti.</p>'
			. '<label for="' . $id . 'Cv">Conferma</label> <input type="text" id="' . $id . 'Cv" autocomplete="off" placeholder="CONFERMO"> <button type="button" class="button button-primary" data-f="scrivi">Esegui</button></div>';
		echo '<div class="rx-sql-r" id="' . $id . 'R" hidden><div class="rx-sql-tab" id="' . $id . 'T"></div><pre class="rx-sql-ref" id="' . $id . 'Ref"></pre><p class="rx-sql-fine"><span id="' . $id . 'Fine"></span> <button type="button" class="button button-small" data-rx-copia="' . $id . 'Ref">Copia esito</button></p></div>';
		echo '</div></div>';
		self::stili_e_script( $id );
	}

	private static function stili_e_script( string $id ): void {
		echo '<style>.rx-sql{font-size:15px;line-height:1.5}.rx-sql-t{font-size:18px;margin:6px 0 2px}.rx-sql-s{margin:0 0 10px;color:#50575e}.rx-sql-l{display:block;font-weight:600;margin:10px 0 4px}.rx-sql-src{max-width:100%;min-height:40px}.rx-sql-at{font-size:13px;color:#50575e;margin:4px 0}.rx-sql-q{width:100%;font:14px/1.5 ui-monospace,Menlo,Consolas,monospace;min-height:110px}.rx-sql-ck{display:flex;gap:8px;align-items:center;margin:10px 0;min-height:44px}.rx-sql-b{display:flex;flex-wrap:wrap;gap:8px}.rx-sql-b .button,.rx-sql-cf .button{min-height:40px}.rx-sql-cf{margin:12px 0;padding:12px;border:1px solid #dba617;background:#fcf9e8;border-radius:6px}.rx-sql-cf input{min-height:40px;font-size:15px}.rx-sql-tab{overflow:auto;max-height:420px;margin:12px 0}.rx-sql-tab table{border-collapse:collapse;font-size:13px}.rx-sql-tab th,.rx-sql-tab td{border:1px solid #dcdcde;padding:4px 8px;text-align:left;white-space:nowrap}.rx-sql-ref{white-space:pre-wrap;background:#f6f7f7;border:1px solid #dcdcde;padding:10px;font-size:13px;margin:8px 0}.rx-sql-fine{font-weight:600}@media (max-width:600px){.rx-sql-b .button{flex:1 1 45%}}</style>';
		$js = 'var w=document.getElementById("' . $id . '");if(!w)return;var src=w.querySelector(".rx-sql-src"),q=document.getElementById("' . $id . 'Q"),ck=document.getElementById("' . $id . 'Ck"),at=document.getElementById("' . $id . 'At"),cf=document.getElementById("' . $id . 'Cf"),cv=document.getElementById("' . $id . 'Cv"),R=document.getElementById("' . $id . 'R"),T=document.getElementById("' . $id . 'T"),Ref=document.getElementById("' . $id . 'Ref"),Fine=document.getElementById("' . $id . 'Fine"),bE=w.querySelector("[data-f=esegui]"),bP=w.querySelector("[data-f=prova]");'
			. 'function stato(){var o=src.options[src.selectedIndex];at.textContent="Colpisce: "+o.dataset.at;var scr=o.dataset.scr==="1";ck.disabled=!scr;if(!scr)ck.checked=false;bP.hidden=!ck.checked;w.querySelectorAll("[data-scrittura=no]").forEach(function(b){b.hidden=ck.checked;});cf.hidden=true;}'
			. 'src.addEventListener("change",stato);ck.addEventListener("change",stato);q.addEventListener("input",function(){cf.hidden=true;});stato();'
			. 'function mostra(d){R.hidden=false;T.textContent="";if(d.colonne&&d.colonne.length){var t=document.createElement("table"),h=t.insertRow();d.colonne.forEach(function(c){var th=document.createElement("th");th.textContent=c;h.appendChild(th);});(d.dati||[]).forEach(function(r){var tr=t.insertRow();r.forEach(function(v){tr.insertCell().textContent=v===null?"NULL":String(v);});});T.appendChild(t);}Ref.textContent=(d.referto||[]).map(function(l){return "["+l[0]+"] "+l[1];}).join("\n");Fine.textContent=(d.verdi||0)+" verdi, "+(d.rossi||0)+" rossi";cf.hidden=!(typeof d.righe_prova==="number");if(!cf.hidden){w.querySelector("[data-f=scrivi]").textContent="Esegui · "+d.righe_prova+" righe";cv.value="";cv.focus();}}'
			. 'function invia(az,conf){var f=new FormData();f.append("action",w.dataset.azione);f.append("nonce",w.dataset.nonce);f.append("sorgente",src.value);f.append("sql",q.value);f.append("consenti",ck.checked?"1":"0");f.append("azione",az);f.append("conferma",conf||"");fetch(w.dataset.ajax,{method:"POST",credentials:"same-origin",body:f}).then(function(r){return r.json();}).then(function(j){mostra(j.data||{referto:[["ROSSO","Risposta non valida"]],verdi:0,rossi:1});}).catch(function(){mostra({referto:[["ROSSO","Richiesta non riuscita"]],verdi:0,rossi:1});});}'
			. 'w.addEventListener("click",function(e){var b=e.target.closest("[data-f]");if(!b)return;var f=b.dataset.f;if(f==="esegui")invia("esegui");if(f==="prova")invia("prova");if(f==="scrivi")invia("esegui",cv.value);if(f==="pulisci"){q.value="";R.hidden=true;cf.hidden=true;}if(f==="copia"&&navigator.clipboard)navigator.clipboard.writeText(q.value);if(f==="incolla"&&navigator.clipboard&&navigator.clipboard.readText)navigator.clipboard.readText().then(function(t){q.value=t;});});';
		echo '<script>(function(){' . $js . '})();</script>';
	}

	/** La porta del banco: le regole sono del motore. */
	public static function esegui( array $r ): array {
		return Rdacademy_Console_SQL::esegui( $r );
	}

	/** manage_options; su multisite anche super amministratore (F42 e): il server è di tutta la rete. */
	private static function puo(): bool {
		return current_user_can( 'manage_options' ) && ( ! is_multisite() || is_super_admin() );
	}

	public static function ajax(): void {
		if ( ! self::puo() ) {
			wp_send_json_error( array( 'referto' => array( array( 'ROSSO', 'Permesso negato (manage_options; su multisite super amministratore).' ) ) ), 403 );
		}
		check_ajax_referer( self::AZIONE, 'nonce' );
		$u   = wp_get_current_user();
		$out = self::esegui( array(
			'sorgente' => sanitize_key( (string) wp_unslash( $_POST['sorgente'] ?? 'base' ) ),
			'sql'      => (string) wp_unslash( $_POST['sql'] ?? '' ),                       // istruzione grezza: la giudica il motore
			'consenti' => ! empty( $_POST['consenti'] ) && (string) $_POST['consenti'] === '1',
			'azione'   => ( (string) ( $_POST['azione'] ?? '' ) === 'prova' ) ? 'prova' : 'esegui',
			'conferma' => (string) wp_unslash( $_POST['conferma'] ?? '' ),
			'utente'   => (int) $u->ID,
			'login'    => (string) $u->user_login,
		) );
		wp_send_json_success( $out );
	}
}
