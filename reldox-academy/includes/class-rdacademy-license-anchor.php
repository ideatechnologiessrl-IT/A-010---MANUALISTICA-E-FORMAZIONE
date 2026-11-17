<?php
/**
 * Rdacademy_License_Anchor — ancora di licenza a livello BASE.
 *
 * Copiato da Reldox_License_Anchor, RelDox CRD 3.55.09G (FASE 0 install, patch 2).
 *
 * Modello (decisione S19 di RelDox): l ancora e SOLO IL DOMINIO
 * (`home_url()` normalizzato). E un DETERRENTE NON-DRM: registra il dominio alla
 * prima attivazione, rileva lo spostamento del sito e ne espone lo stato
 * (`active` | `needs_relicense`, a schermo «attiva» | «da rinnovare»). NESSUN
 * lockout. E la base della futura chiave di funzionamento per gli aggiornamenti
 * (REGOLE-FAMIGLIA § 3.10).
 *
 * Storage: SOLO BASE (`rdacademy_license_activation`), riga singola, by-value,
 * niente FK. Tabella creata da Rdacademy_Schema_License_V1 (in RelDox: dall Activator).
 *
 * CORREZIONI rispetto a RelDox:
 *  - gli istanti in UTC da PHP (gmdate), non current_time( 'mysql' ) (SPEC § 2.3);
 *  - il pacchetto per la colonna VARCHAR(64) con rdacademy_pacchetto_etichetta( 64,
 *    false ): l helper rx_pacchetto_per_colonna di RelDox era solo quella chiamata;
 *  - in RelDox state() e touch_seen() non avevano NESSUN chiamante: lo stato
 *    «da rinnovare» non arrivava mai a schermo. Nell Academy state() lo legge lo
 *    Stato sistema; la pagina Licenza (che chiamava touch_seen) è tolta (analisi ed. 7).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rdacademy_License_Anchor {

	/** Stato: sito sull ancora registrata. */
	const STATE_ACTIVE = 'active';

	/** Stato: sito spostato dall ancora (recuperabile, nessun lockout). */
	const STATE_NEEDS_RELICENSE = 'needs_relicense';

	/**
	 * Normalizza un URL/host a dominio confrontabile:
	 * minuscolo, senza schema, senza `www.`, senza porta, senza path.
	 * Es. "https://WWW.Esempio.it:443/academy/" -> "esempio.it".
	 */
	public static function norm_domain( string $url ): string {
		$url = trim( $url );
		if ( $url === '' ) {
			return '';
		}
		// Assicura uno schema cosi che parse_url isoli l host anche da "esempio.it/base".
		if ( ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $url ) ) {
			$url = 'http://' . $url;
		}
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		if ( $host === '' ) {
			$host = $url;
		}
		$host = strtolower( $host );
		$host = preg_replace( '/^www\./', '', $host );
		return (string) $host;
	}

	/** Dominio corrente del sito (home_url normalizzato). */
	public static function current_domain(): string {
		return self::norm_domain( home_url( '/' ) );
	}

	/**
	 * Riga di attivazione (unica) dal BASE, o null se assente/tabella mancante.
	 * @return array<string,mixed>|null
	 */
	public static function record(): ?array {
		$p = RDACADEMY_TABLE_PREFIX;
		try {
			return Rdacademy_DB::get_row(
				"SELECT * FROM `{$p}license_activation` ORDER BY id ASC LIMIT 1"
			);
		} catch ( \Throwable $e ) {
			// Tabella non ancora creata o BASE non raggiungibile: l ancora non e
			// un requisito di boot, quindi si degrada in silenzio.
			return null;
		}
	}

	/** Dominio d ancora registrato alla prima attivazione, o null. */
	public static function anchor_domain(): ?string {
		$row = self::record();
		if ( ! $row || ! isset( $row['activation_domain'] ) ) {
			return null;
		}
		$d = (string) $row['activation_domain'];
		return $d !== '' ? $d : null;
	}

	/**
	 * True se un ancora e registrata E il dominio corrente e diverso.
	 * Se l ancora non e ancora registrata NON e un mismatch (installazione nuova).
	 */
	public static function is_mismatch(): bool {
		$anchor = self::anchor_domain();
		if ( $anchor === null ) {
			return false;
		}
		return self::current_domain() !== $anchor;
	}

	/**
	 * Stato di licenza calcolato (soft): STATE_NEEDS_RELICENSE su mismatch,
	 * altrimenti STATE_ACTIVE. Il calcolo del dominio ha la priorita su uno
	 * stato persistito.
	 */
	public static function state(): string {
		if ( self::is_mismatch() ) {
			return self::STATE_NEEDS_RELICENSE;
		}
		return self::STATE_ACTIVE;
	}

	/** NUOVO — la parola a schermo dello stato (una sola, per pagina e copia). */
	public static function parola( string $state ): string {
		return $state === self::STATE_NEEDS_RELICENSE ? 'da rinnovare' : 'attiva';
	}

	/**
	 * Registra l ancora alla prima attivazione se la riga non esiste ancora.
	 * Idempotente: non sovrascrive mai un ancora gia registrata (lo spostamento
	 * di dominio si gestisce con state(), non riscrivendo l ancora).
	 * Chiamata da Rdacademy_Schema_License_V1::maybe_rebuild() dopo il CREATE TABLE.
	 */
	public static function ensure_seeded(): void {
		$p = RDACADEMY_TABLE_PREFIX;
		try {
			$existing = Rdacademy_DB::get_row( "SELECT id FROM `{$p}license_activation` LIMIT 1" );
			if ( $existing ) {
				return; // ancora gia presente: non si tocca.
			}
			$now = gmdate( 'Y-m-d H:i:s' );
			Rdacademy_DB::query(
				"INSERT INTO `{$p}license_activation`
				   (activation_domain, activated_at, plugin_version, package, license_state, last_seen_domain, last_check_at)
				 VALUES (?, ?, ?, ?, ?, ?, ?)",
				array(
					self::current_domain(),
					$now,
					defined( 'RDACADEMY_VERSION' ) ? RDACADEMY_VERSION : '',
					rdacademy_pacchetto_etichetta( 64, false ), // BUG-98 di RelDox: la colonna e VARCHAR(64), il limite si dichiara qui
					self::STATE_ACTIVE,
					self::current_domain(),
					$now,
				),
				'sssssss'
			);
		} catch ( \Throwable $e ) {
			error_log( '[' . RDACADEMY_PRODUCT . '] ancora di licenza, seme non riuscito: ' . $e->getMessage() );
		}
	}

	/**
	 * Aggiorna il rilevamento «ultimo dominio visto» e lo stato soft, senza
	 * mai toccare l ancora. Sola scrittura diagnostica; nessun lockout.
	 */
	public static function touch_seen(): void {
		$row = self::record();
		if ( ! $row ) {
			return;
		}
		$p     = RDACADEMY_TABLE_PREFIX;
		$state = self::state();
		try {
			Rdacademy_DB::query(
				"UPDATE `{$p}license_activation`
				    SET last_seen_domain = ?, last_check_at = ?, license_state = ?
				  WHERE id = ?",
				array( self::current_domain(), gmdate( 'Y-m-d H:i:s' ), $state, (int) $row['id'] ),
				'sssi'
			);
		} catch ( \Throwable $e ) {
			error_log( '[' . RDACADEMY_PRODUCT . '] ancora di licenza, controllo non riuscito: ' . $e->getMessage() );
		}
	}
}
