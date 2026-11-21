<?php
// F023_LICENZA — copiato da RelDox CRD 3.76.03G (`class-reldox-license-testi.php`) con banco/F023-porta-licenza.py: non si cambia a mano.
/**
 * Rdacademy_License_Testi — i testi della licenza d uso e la casella che spedisce il contratto.
 * =============================================================================
 * 3.59.09G (S615) — VOCE 97, lotto 97.1 (SPEC-FIRMA-LICENZA-RELDOX-v1 ed. 3, KIT-FIRMA-LICENZA-RELDOX-v1).
 *
 * PERCHE ESISTE. Il codice della famiglia (RelDoxFood 1.3.2B) legge i sette testi `licenza.*` dalla Tabella
 * Funzionale «Testi delle notifiche» (`*_Testi_Notifiche::modello`) e li riempie con `*_Lingua::riempi`; la
 * casella che spedisce e `*_Casella`. RelDox CRD non ha nessuna delle tre classi: come prevede ADATTA.txt
 * del kit, i testi entrano come SET DI PARTENZA IN COSTANTE (italiano: la pagina «Licenza d uso» e la mail a
 * Idea Technologies sono in italiano, SPEC § 15) e la casella e quella di servizio di RelDox CRD
 * (`Rdacademy_Password_Mail`, Impostazioni → Email → Casella di servizio). I testi sono quelli del frammento 2
 * del kit, alla lettera.
 *
 * @package Rdacademy_CRD
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_License_Testi {

	/** Il set di partenza (frammento 2 del kit, italiano): codice => [oggetto, testo]. */
	const TESTI = array(
		'licenza.trasmissione' => array( '{prodotto} — contratto di licenza firmato · {intestatario} · {dominio}', "Buongiorno,\n\nin allegato il contratto di licenza d'uso di {prodotto} (EULA {eula}) firmato da {intestatario} ({piva_cf}) per il dominio {dominio}.\n\nFirma: {firma} · file {file}\nImpronta del file (sha256): {impronta_file}\nImpronta del testo (sha256): {impronta_testo}\nForo competente: {foro}\nVersione del software: {versione}\n\nTrasmesso da {chi} il {quando}." ),
		'licenza.trasmissione_firma' => array( 'Dati della firma', "Modalità di firma: {modalita} · {riconoscimento}\nFirme: {firme}\nIdentificativo del contratto generato: {id_contratto}\nCodice di controllo del testo: {codice}\n{originale}" ),
		'licenza.originale' => array( 'Originale cartaceo: da spedire a {licenziante} — {indirizzo}', 'Copia per immagine anticipata via email: il contratto è concluso. Originale cartaceo da ricevere per posta entro il {entro} ({indirizzo}).' ),
		'licenza.firma_digitale' => array( 'Firma digitale (consigliata)', "Scarica il contratto.\nAprilo col tuo programma di firma digitale.\nFirma due volte: nel campo «Firma del contratto» e nel campo «Approvazione specifica».\nAllega il PDF firmato (resta in formato PDF, non P7M)." ),
		'licenza.firma_autografa' => array( 'Firma autografa', "Scarica e stampa il contratto.\nFirma a mano nei due spazi dell'ultima pagina e scrivi luogo e data.\nScansiona tutte le pagine in un solo PDF, allegalo e trasmettilo: la copia via email anticipa l'originale e conclude il contratto.\nSpedisci per posta l'originale cartaceo a {licenziante} entro 15 giorni: {indirizzo}." ),
		'licenza.pdf_istruzioni' => array( 'Come si firma', "Firma digitale: due firme nei campi predisposti, «Firma del contratto» e «Approvazione specifica»; il file firmato resta in formato PDF (PAdES).\nFirma autografa: firma nei due spazi, scansiona tutte le pagine in un solo PDF e trasmettilo dal Software (la copia via email anticipa l'originale); spedisci per posta l'originale a {licenziante} — {indirizzo} — entro 15 giorni." ),
		'licenza.pdf_nota' => array( 'Codice di controllo del testo', 'Codice di controllo del testo: {codice}. Il codice identifica in modo univoco questa versione del testo del contratto e serve a verificare che il documento firmato non sia stato modificato. Non comporta obblighi per le parti.' ),
	);

	/** I segnaposto che riempi() conosce (frammento 3 del kit + {prodotto} {chi} {file}). */
	const SEGNAPOSTO = array( 'prodotto', 'chi', 'file', 'intestatario', 'piva_cf', 'dominio', 'eula', 'firma', 'impronta_file', 'impronta_testo', 'foro', 'versione', 'quando',
		'modalita', 'riconoscimento', 'firme', 'id_contratto', 'codice', 'originale', 'licenziante', 'indirizzo', 'entro' );

	/** Il testo di un codice: ['oggetto' => …, 'testo' => …]. La lingua e sempre l italiano (SPEC § 15). */
	public static function modello( string $codice, string $lingua = 'it' ): array {
		$t = self::TESTI[ $codice ] ?? array( '', '' );
		return array( 'oggetto' => (string) $t[0], 'testo' => (string) $t[1] );
	}

	/**
	 * Riempie i segnaposto. Con $html i valori si proteggono e gli a capo diventano <br>; senza, testo semplice.
	 * Un segnaposto che non conosce resta com e (si vede, non sparisce in silenzio).
	 */
	public static function riempi( string $tpl, array $v, bool $html = false ): string {
		$mappa = array();
		foreach ( self::SEGNAPOSTO as $k ) {
			$x = (string) ( $v[ $k ] ?? '' );
			$mappa[ '{' . $k . '}' ] = $html ? htmlspecialchars( $x, ENT_QUOTES, 'UTF-8' ) : $x;
		}
		if ( ! $html ) return strtr( $tpl, $mappa );
		return nl2br( strtr( htmlspecialchars( $tpl, ENT_QUOTES, 'UTF-8' ), $mappa ), false );
	}

	/**
	 * La casella che spedisce il contratto: quella di servizio di RelDox CRD. Stessa forma di `*_Casella::stato()`
	 * della famiglia (`verificata`, `motivo`), letta da Rdacademy_Password_Mail::stato().
	 */
	public static function casella(): array {
		if ( ! class_exists( 'Rdacademy_Password_Mail' ) ) return array( 'verificata' => false, 'motivo' => 'La casella di servizio non e disponibile.' );
		$s = Rdacademy_Password_Mail::stato();
		return array( 'verificata' => ! empty( $s['configurata'] ), 'motivo' => (string) ( $s['motivo'] ?? '' ) );
	}

	/** Dove si dichiara la casella, scritto per chi legge la pagina. */
	const DOVE_CASELLA = 'In RelDoxAcademy: Impostazioni → Email → Casella di servizio.';
}
