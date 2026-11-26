<?php
/**
 * Rdacademy_Banchi_Registro — IL REGISTRO DEI BANCHI DELLA MANUTENZIONE.
 *
 * Copiato da Reldox_Banchi_Registro, RelDox CRD 3.55.09G (voce 84, lotto 1 —
 * 3.55.07, S591), e ridotto: nel guscio c e UN banco, il banco comune a ogni
 * prodotto «Ruoli e recupero accesso» (REGOLE-FAMIGLIA § 3.10).
 *
 * CHE COSA DICE: una voce per riquadro (q = il data-q, che e la chiave), il
 * numero, la natura (util / gold / oneshot), l area, il file che lo disegna, la
 * scheda d origine, lo stato (vivo / storico).
 * D-84.1 (owner, in RelDox): i banchi delle schede chiuse vanno nella
 * «Manutenzione storica». D-84.2: un banco e storico quando la scheda per cui
 * e nato e CHIUSA; non c e un pulsante per spostarlo.
 * NIENTE CAMBIA NEGLI STRUMENTI: la pagina li disegna tutti; il registro dice
 * solo quali si vedono dove.
 *
 * TAGLI: le 128 voci dei banchi del CRM; DOPPI_NOTI (numeri usati due volte in
 * RelDox: nel guscio non ce ne sono). In RelDox il file era scritto da uno
 * script che leggeva render(): nel guscio la voce si scrive a mano QUI e la
 * pagina conta da sola dichiarati / disegnati / file presenti.
 *
 * 0.1.02 — TRASLOCATO NEL PLUGIN DI DIAGNOSTICA (SPEC-MANUTENZIONE-E-
 * DIAGNOSTICA-v1, livello 3), stesso percorso relativo. I file delle voci si
 * cercano nella cartella del plugin (RDACADEMY_DIAG_DIR). Il guscio non ha uno
 * script che riscrive il registro (in RelDox: S593-11-registro-nel-plugin.sh):
 * con UNA voce si scrive a mano qui, e la pagina lo verifica da sola
 * (dichiarati / disegnati / file presenti, pastiglia rossa se non tornano).
 *
 * NUOVO:
 *  - AREE: le aree dichiarate accanto alle voci (in RelDox le sezioni erano
 *    scritte nel solo HTML di render(), con il conto a mano: «21» quando i
 *    riquadri erano un altro numero);
 *  - il filtro `rdacademy_banchi_voci`: un prodotto della famiglia aggiunge i suoi
 *    banchi senza riscrivere questo file (e li disegna dall azione
 *    `rdacademy_manutenzione_banchi` di Rdacademy_Maintenance).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rdacademy_Banchi_Registro {

	/** Pagina in disegno: 'vivo' (Manutenzione) o 'storico' (Manutenzione storica). */
	public static string $modo = 'vivo';

	/** Le aree della pagina: chiave => [titolo, descrizione]. */
	const AREE = array(
		'cat-sistema' => array( 'Sistema e accesso', 'Ruoli, recupero dell accesso, stato dell installazione.' ),
		'cat-database' => array( 'Database e sorgenti', 'Console SQL sul BASE, sulle sorgenti IN USO e, in sola lettura, su WordPress.' ),
	);

	const VOCI = array(
		array( 'q' => '1 ruoli recupero accesso utenti amministratore collegamento monouso password', 'n' => 1, 'nat' => 'util', 'cat' => 'cat-sistema', 'file' => 'includes/class-rdacademy-maintenance.php', 'scheda' => '', 'stato' => 'vivo' ),
		array( 'q' => '2 console sql database sorgenti base wordpress select lettura sola lettura maschera registro', 'n' => 2, 'nat' => 'util', 'cat' => 'cat-database', 'file' => 'includes/banchi/class-rdacademy-banco-console-sql.php', 'scheda' => 'F29', 'stato' => 'vivo' ),
	);

	/** Le voci, con quelle che un prodotto aggiunge dal filtro. */
	public static function voci(): array {
		$out = array();
		foreach ( (array) apply_filters( 'rdacademy_banchi_voci', self::VOCI ) as $v ) {
			if ( ! is_array( $v ) || empty( $v['q'] ) ) continue;
			$out[] = array(
				'q'      => (string) $v['q'],
				'n'      => isset( $v['n'] ) ? $v['n'] : null,
				'nat'    => in_array( $v['nat'] ?? '', array( 'util', 'gold', 'oneshot' ), true ) ? $v['nat'] : 'util',
				'cat'    => (string) ( $v['cat'] ?? 'cat-sistema' ),
				'file'   => (string) ( $v['file'] ?? '' ),
				'scheda' => (string) ( $v['scheda'] ?? '' ),
				'stato'  => ( ( $v['stato'] ?? 'vivo' ) === 'storico' ) ? 'storico' : 'vivo',
			);
		}
		return $out;
	}

	/** Le aree, con quelle che un prodotto aggiunge dal filtro. */
	public static function aree(): array {
		return (array) apply_filters( 'rdacademy_banchi_aree', self::AREE );
	}

	/** I conti del registro: dichiarati, vivi, storici, file presenti e file mancanti (per nome). */
	public static function conta(): array {
		$vivi = 0; $storici = 0; $file_ok = 0; $mancano = array();
		$dir = defined( 'RDACADEMY_DIAG_DIR' ) ? RDACADEMY_DIAG_DIR : '';   // 0.1.02: i banchi stanno nel plugin di diagnostica
		$voci = self::voci();
		foreach ( $voci as $v ) {
			if ( $v['stato'] === 'storico' ) { $storici++; } else { $vivi++; }
			if ( $dir !== '' && $v['file'] !== '' && @file_exists( $dir . $v['file'] ) ) { $file_ok++; } else { $mancano[] = $v['n'] !== null ? (string) $v['n'] : $v['q']; }
		}
		return array( 'dichiarati' => count( $voci ), 'vivi' => $vivi, 'storici' => $storici, 'file' => $file_ok, 'file_mancanti' => $mancano );
	}

	public static function titolo(): string {
		return self::$modo === 'storico' ? 'Manutenzione storica' : 'Manutenzione';
	}

	/** Il sottotitolo della testata, in HTML gia sicuro (testo fisso + nome del prodotto con escape). */
	public static function sottotitolo(): string {
		if ( self::$modo === 'storico' ) {
			return 'I banchi nati per un bug o una voce <b>chiusi</b>. Non servono al lavoro di ogni giorno: restano per la regressione e per l assistenza. Ognuno funziona come prima.';
		}
		return 'Strumenti diagnostici e di recupero del prodotto ' . esc_html( RDACADEMY_PRODUCT ) . '. Gli strumenti delle schede chiuse sono in <b>Manutenzione storica</b>.';
	}

	/** La riga dei conti sotto la testata (la pastiglia del registro la completa la pagina, che conta i riquadri disegnati). */
	public static function contatore( string $url_vivi, string $url_storici ): string {
		$c = self::conta();
		if ( self::$modo === 'storico' ) {
			return '<div class="rx-rg-conta"><span id="rxRgConta" data-storici="' . (int) $c['storici'] . '">' . (int) $c['storici'] . ' storici</span><a href="' . esc_url( $url_vivi ) . '">&larr; '
				. (int) $c['vivi'] . ' attivi in Manutenzione</a></div>'
				. '<div class="rx-rg-nota">Un banco diventa storico da solo quando la scheda per cui &egrave; nato passa in PUNTI-CHIUSI (D-84.2). '
				. 'Non c e un pulsante per spostarlo: lo decide il registro.</div>';
		}
		return '<div class="rx-rg-conta"><span id="rxRgConta" class="ok" data-dich="' . (int) $c['dichiarati'] . '" data-file="' . (int) $c['file']
			. '" data-fmanca="' . esc_attr( implode( ', ', $c['file_mancanti'] ) ) . '">Registro: ' . (int) $c['dichiarati'] . ' dichiarati</span><span>'
			. (int) $c['vivi'] . ' attivi</span><a href="' . esc_url( $url_storici ) . '">' . (int) $c['storici'] . ' storici &rarr; Manutenzione storica</a></div>';
	}

	public static function css(): string {
		return '<style>.rx-rg-conta{display:flex;gap:6px;flex-wrap:wrap;margin:10px 0 0}.rx-rg-conta span{background:#fff;border:1px solid #E5E7EB;border-radius:999px;padding:4px 10px;font-size:12.5px;font-weight:600;color:#374151}'
			. '.rx-rg-conta span.ok{border-color:#bbf7d0;background:#DCFCE7;color:#137a37}.rx-rg-conta span.ko{border-color:#fecaca;background:#fee2e2;color:#991b1b}'
			. '.rx-rg-conta a{font-size:12.5px;font-weight:600;color:#2E75B6;text-decoration:none;padding:4px;align-self:center}'
			. '.rx-rg-nota{background:#f6f7f7;border:1px solid #dcdcde;border-radius:8px;padding:10px 14px;margin:12px 0 4px;font-size:13px;color:#374151}'
			. '.rx-modo-storico .rx-maint-plane,.rx-modo-storico .rx-mchip[data-nat=util]{display:none}.rx-modo-storico .rx-maint-h-ic{background:linear-gradient(135deg,#6B7280,#374151)}'
			. '.rx-tw[data-st].rx-st-chiuso>:not(.rx-st-row){display:none}.rx-st-row{display:grid;grid-template-columns:52px minmax(0,1fr) auto auto;gap:12px;align-items:center;'
			. 'background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:10px 14px;margin:0 0 6px}.rx-tw:not(.rx-st-chiuso)>.rx-st-row{border-radius:10px 10px 0 0;margin:0}'
			. '.rx-st-row .n{font:700 13px "SFMono-Regular",Consolas,Menlo,monospace;color:#6B7280}.rx-st-row .t{font-weight:600;font-size:14px;min-width:0}'
			. '.rx-st-row .o{font-size:12px;font-weight:600;padding:3px 9px;border-radius:999px;background:#DCFCE7;color:#137a37;white-space:nowrap}'
			. '.rx-st-row button{font:inherit;font-size:13px;font-weight:600;padding:6px 12px;border-radius:8px;border:1px solid #D1D5DB;background:#fff;cursor:pointer;white-space:nowrap}'
			. '@media (max-width:720px){.rx-st-row{grid-template-columns:44px auto minmax(0,1fr);row-gap:6px}.rx-st-row .t{grid-column:2/-1}.rx-st-row .o{grid-column:2}.rx-st-row button{grid-column:3;justify-self:start}}</style>';
	}

	/** Lo script della pagina: segna lo stato di ogni riquadro, conta i disegnati, e nella storica disegna le righe con «Apri». */
	public static function script(): string {
		$mappa = array();
		foreach ( self::voci() as $v ) {
			$mappa[ $v['q'] ] = array( $v['n'], $v['scheda'], $v['stato'] );
		}
		// Copiato da RelDox; le spunte ✓/⚠ diventano parole (REGOLE § 4: niente simboli al posto delle parole).
		$js = 'var R=' . wp_json_encode( $mappa ) . ',M=' . wp_json_encode( self::$modo ) . ';'
			. 'var w=document.querySelector(".rx-maint");if(w&&M==="storico")w.classList.add("rx-modo-storico");'
			. 'var visti={},disegnati=0;'
			. 'document.querySelectorAll(".rx-cat > .rx-tw").forEach(function(tw){var q=tw.dataset.q||"",e=R[q];'
			. 'if(!e){tw.dataset.st="vivo";return;}visti[q]=1;disegnati++;tw.dataset.st=e[2];if(e[1])tw.dataset.q=q+" "+e[1].toLowerCase();'
			. 'if(M==="storico"&&e[2]==="storico"){var h=tw.querySelector("h2,h3"),r=document.createElement("div");r.className="rx-st-row";'
			. 'var n=document.createElement("span");n.className="n";n.textContent=e[0]===null?"—":e[0];'
			. 'var t=document.createElement("span");t.className="t";t.textContent=h?h.textContent.trim():q;'
			. 'var o=document.createElement("span");o.className="o";o.textContent=e[1]+" · chiusa";'
			. 'var b=document.createElement("button");b.type="button";b.textContent="Apri";b.setAttribute("aria-expanded","false");'
			. 'b.addEventListener("click",function(){var aperto=tw.classList.toggle("rx-st-chiuso")===false;b.textContent=aperto?"Chiudi":"Apri";b.setAttribute("aria-expanded",aperto?"true":"false");});'
			. 'r.appendChild(n);r.appendChild(t);r.appendChild(o);r.appendChild(b);tw.insertBefore(r,tw.firstChild);tw.classList.add("rx-st-chiuso");}});'
			. 'var p=document.getElementById("rxRgConta");if(p&&M!=="storico"){var d=+p.dataset.dich,f=+p.dataset.file,manca=[];'
			. 'Object.keys(R).forEach(function(q){if(!visti[q])manca.push(R[q][0]===null?q:R[q][0]);});'
			. 'var ok=disegnati===d&&f===d;p.className=ok?"ok":"ko";p.dataset.disegnati=disegnati;'
			. 'p.textContent="Registro: "+d+" dichiarati · "+disegnati+" disegnati · "+f+" file presenti"'
			. '+(manca.length?" — manca "+(manca.length===1?"il ":"")+manca.join(", "):"")+(p.dataset.fmanca?" — file mancante: "+p.dataset.fmanca:"")+(ok?"":" — registro e pagina non dicono la stessa cosa");}';
		return '<script>(function(){' . $js . '})();</script>';
	}
}
