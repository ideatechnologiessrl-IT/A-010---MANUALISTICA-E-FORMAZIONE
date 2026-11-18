<?php
/**
 * Rdacademy_Tabella_Senza_Casa — L ECCEZIONE DELLA NASCITA SENZA CASA.
 * ---------------------------------------------------------------------
 * Copiato da Reldox_Tabella_Senza_Casa (BUG-182 lotto 2), RelDox CRD 3.55.09G.
 *
 * PERCHE UN ECCEZIONE E NON UN RITORNO FALSO (copiato). `Rdacademy_Table_Owner::conn_for()`
 * risponde con una connessione: il suo contratto è «dove nasce questa tabella»
 * e non ha un modo di dire «da nessuna parte» restando dentro il tipo. In
 * LETTURA il ripiego sul BASE è prudente; alla NASCITA fabbrica copie fantasma
 * sul BASE. Un valore di ritorno prudente e uno sbagliato qui coincidono,
 * quindi la risposta esce dal canale del ritorno.
 *
 * Chi la prende nel guscio: Rdacademy_Sorgente_Provisioner::porta_in_uso(), che la
 * registra come RINVIATA e riporta la sorgente a spenta.
 * NON È UN ERRORE DA MOSTRARE ALL UTENTE: è lo stato «questa sorgente non ha
 * (ancora) una casa».
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_Tabella_Senza_Casa extends RuntimeException {

	private string $tabella  = '';
	private string $sorgente = '';

	public function __construct( string $tabella, string $sorgente, string $perche ) {
		$this->tabella  = $tabella;
		$this->sorgente = $sorgente;
		parent::__construct(
			'La tabella «' . $tabella . '» appartiene alla sorgente «' . $sorgente
			. '» e non ha dove nascere: ' . $perche
			. ' Non si ripiega sul BASE alla nascita.'
		);
	}

	public function tabella(): string {
		return $this->tabella;
	}

	public function sorgente(): string {
		return $this->sorgente;
	}
}
