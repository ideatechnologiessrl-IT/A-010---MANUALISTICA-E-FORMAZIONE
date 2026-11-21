<?php
// F023_LICENZA — copiato da RelDox CRD 3.76.03G (`class-reldox-license.php`) con banco/F023-porta-licenza.py: non si cambia a mano.
/**
 * Rdacademy_License — Livello legale: testo EULA, intestatario, registrazione probatoria.
 *
 * Copiato da Rdacademy_License, RelDox CRD 3.55.09G (protezione.md §5, stage Beta).
 *
 * 3.59.09G (S615) — VOCE 97 lotto 97.1: RIENTRA IN RELDOX CRD dal KIT-FIRMA-LICENZA-RELDOX-v1 (RelDoxFood 1.3.2B, adatta.py
 * reldox Reldox RELDOX). Sostituisce il modulo della 3.14.x (EULA v1, accettazione nel pannello). Adattamenti di RelDox CRD:
 * testi e casella da Rdacademy_License_Testi; letture dell accettazione e dello storico col ponte bridge_get (migrazione: le
 * accettazioni EULA v1 restano nello storico, nessuna si perde); col testo nuovo la licenza torna «da accettare».
 *
 * Doppia sottoscrizione ex artt. 1341-1342 c.c. (generale + specifica clausole
 * vessatorie), firmatario + dichiarazione di rappresentanza, intestazione delle
 * parti (Licenziante + Licenziatario/intestatario).
 *
 * REGISTRAZIONE PROBATORIA — ZERO ALTER (decisione tecnica di RelDox 3.14.1):
 * il record vive in due opzioni del PRODOTTO, cioe in `rdacademy_options` sul BASE
 * (Rdacademy_Option), come in RelDox dalla 3.16.58: l accettazione VIGENTE
 * (rdacademy_license_acceptance) e il LOG storico append-only di tutte le
 * accettazioni (rdacademy_license_acceptance_log). Nessuna opzione sul database di
 * WordPress (RP-_1-NON-E-DI-RELDOX).
 *
 * INTESTATARIO — i dati del Licenziatario si leggono dalla tabella
 * rdacademy_impostazioni (chiave/valore), stessa fonte del branding: chiavi
 * license_holder_* = righe additive (ZERO ALTER). Campi mancanti evidenziati.
 *
 * TESTO EULA — BOZZA. Validazione avvocato OBBLIGATORIA prima della Gold
 * (REGOLE-FAMIGLIA § 3.10). Il nome del prodotto e il licenziante vengono da
 * RDACADEMY_PRODUCT e RDACADEMY_VENDOR: lo script di rinomina cambia le costanti e il
 * testo segue (e con lui l impronta, quindi il re-consenso).
 *
 * TAGLI / CORREZIONI rispetto a RelDox:
 *  - letture con Rdacademy_Option::get e non con il ponte bridge_get: il guscio nasce
 *    senza copie sul _1 da migrare (il ponte serviva a portarle via);
 *  - licenziatario(): la lista IN si costruisce con segnaposto preparati, non
 *    con esc_sql() di WordPress dentro una query mysqli;
 *  - ⛔ IL LOG NON SI ACCORCIA PIU. In RelDox lo storico «append-only» era
 *    tagliato alle ultime 200 righe (array_slice): la prova piu vecchia — di
 *    solito la prima accettazione, la piu importante — spariva in silenzio.
 *    Uno storico probatorio non perde righe: qui si appende e basta.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_License {

	/** Versione del TESTO EULA (indipendente da RDACADEMY_VERSION). Il re-consenso
	 *  scatta quando cambia l impronta del testo, non a ogni bump di plugin.
	 *  1.3.0B — EULA v2 (owner 11/10 18:39-18:51, PROPOSTA-EULA-v2): 15 articoli, niente etichette «clausola vessatoria».
	 *  3.65.01 (S636) — EULA v3, voce 105 D-105.4 S636_V105_EULA: art. 6.2 «Utente di servizio» (owner 22/10 06:22-06:40). Nessun cliente
	 *  ha firmato la v2: cambia l impronta, e sull installazione di Idea Technologies RelDox chiede la firma della v3. */
	const EULA_VERSION = '4';   // 3.75.05 (S717) voce 166: EULA v4, il testo completo di Legale (licenza di famiglia v4)

	/** 1.3.0B — gli articoli approvati specificamente (artt. 1341-1342 c.c.): si nominano SOLO nell art. 15 e nel blocco firma del PDF.
	 *  3.75.05 (S717) voce 166: gli articoli e i titoli della licenza v4 § 5.2 (art. 15.2 del testo di Legale). */
	const VEX_ARTICLES = [ '3', '5.4', '5.6', '8', '9.2-9.3', '9.5', '10', '10.8', '12', '14' ];
	const VEX_TITOLI   = [ '3' => 'Limiti d’uso', '5.4' => 'termine di installazione e requisiti', '5.6' => 'cessazione dell’attività',
		'8' => 'Garanzia e limitazione di responsabilità', '9.2-9.3' => 'canone obbligatorio e indicizzazione', '9.5' => 'ritardato pagamento',
		'10' => 'durata legata al canone, recesso, risoluzione, sola consultazione e sua sicurezza, chiave di licenza', '10.8' => 'uso senza pagamento e assistenza',
		'12' => 'Cessione del contratto', '14' => 'Foro competente' ];

	const OPT_ACCEPTANCE = 'rdacademy_license_acceptance';       // record VIGENTE
	const OPT_LOG        = 'rdacademy_license_acceptance_log';    // storico append-only
	// 1.3.0B — il contratto firmato allegato (vigente) e lo storico degli eventi del contratto (PDF scaricato, allegato, trasmesso):
	// append-only come lo storico delle accettazioni, sul BASE (Rdacademy_Option).
	const OPT_FIRMATO    = 'rdacademy_license_firmato';
	const OPT_EVENTI     = 'rdacademy_license_eventi';
	// 1.3.1B — SPEC-FIRMA-LICENZA-RELDOX § 11: il contratto generato vigente, la trasmissione vigente, i contratti superati.
	const OPT_GENERATO   = 'rdacademy_license_generato';
	const OPT_TRASMESSO  = 'rdacademy_license_trasmesso';
	const OPT_STORICO    = 'rdacademy_license_storico_contratti';
	/** L indirizzo postale di Idea Technologies per l originale cartaceo (§ 5 B): lo dà l owner. */
	const INDIRIZZO_LICENZIANTE = 'Via F. Gilera 110, 20862 Arcore (MB), Italia';   // owner 12/10 13:48
	/** 1.3.1B — i dati del Licenziante nel contratto (owner 12/10 13:48). */
	const PIVA_LICENZIANTE  = 'IT02683900969';
	const PEC_LICENZIANTE   = 'ideatechnologiessrl@pec.it';
	const FORMA_LICENZIANTE = 'società uninominale';
	const K_FORO         = 'license_foro';                     // chiave di `impostazioni`: il foro competente (art. 14), lo scrive l owner
	const FORO_VUOTO     = '[da indicare]';
	const EMAIL_LICENZIANTE = 'amministrazione@ideatechnologies.com';
	const CARTELLA       = 'rdacademy-licenza';                   // sotto wp-content/uploads, protetta (.htaccess, index.php)
	const ALLEGATO_MAX   = 10485760;                           // 10 MB
	const ALLEGATO_TIPI  = [ 'pdf' => 'application/pdf', 'p7m' => 'application/pkcs7-mime', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png' ];
	const FIRME          = [ 'digitale' => 'firma digitale', 'autografa' => 'firma autografa' ];   // 1.3.1B — le due modalità che si escludono (§ 5)

	// ─────────────────────────────────────────────────────────────────────
	//  TESTO EULA (fonte unica dell impronta hash — client-independent)
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Articoli dell EULA (v4 dalla 3.75.05) come struttura dati. La resa e della pagina e del PDF; qui vive la SOSTANZA legale, che alimenta
	 * resa e impronta probatoria. L intestazione delle parti non entra nell impronta. Dalla v4 il foro è nel testo (Milano):
	 * il segnaposto {foro} non c è più e articoli_resi() lascia il testo com è. Testo redatto dalla chat Legale (v4).
	 *
	 * @return array<int,array{n:string,title:string,vex:bool,body:string,items?:array<int,string>}>
	 */
	public static function articles(): array {
		// S717_V166 — 3.75.05G voce 166: testo completo della licenza d uso v4 di Legale (A-001-LEGAL, testi/kit-firma/
		// LICENZA-RELDOX-CRD-v4-TESTO-COMPLETO.md, codice di controllo 012823EA5D11D828), generato da banco/S717-3: non si cambia a mano;
		// una frase che non torna si scrive a Legale. Owner 17/11/2026, chat Legale L001.
		return [
			[ 'n' => '1', 'vex' => false, 'title' => 'Definizioni e oggetto',
			  'body' => 'Il contratto disciplina la licenza d’uso del software gestionale «RelDoxAcademy» (il «Software»), di titolarità del Licenziante, installato sul dominio indicato nell’intestazione. «Aggiornamenti» sono le nuove versioni e le correzioni rese disponibili dal Licenziante; «Dati del Licenziatario» sono i dati inseriti o generati dal Licenziatario con il Software.'
				. "\n" . '«Canone» è il corrispettivo annuo dell’art. 9.2, che comprende il diritto d’uso e la manutenzione; «Versione principale» è la versione indicata dal primo numero (es. 3 in 3.73.09).' ],
			[ 'n' => '2', 'vex' => false, 'title' => 'Concessione della licenza',
			  'body' => 'Il Licenziante concede una licenza d’uso non esclusiva e non trasferibile, per uso interno del Licenziatario, limitata al dominio e alle installazioni autorizzate, per la durata dell’art. 10. La licenza non trasferisce la proprietà né diritti di sfruttamento economico.' ],
			[ 'n' => '3', 'vex' => false, 'title' => 'Limiti d’uso',
			  'body' => 'Al Licenziatario non è consentito:',
			  'items' => [
				'copiare, redistribuire, concedere in sublicenza, noleggiare o rendere disponibile il Software a terzi;',
				'eseguire reverse engineering, decompilazione o disassemblaggio, salvo i casi in cui la legge li consente senza possibilità di deroga (artt. 64-bis, 64-ter e 64-quater della legge 633/1941: copia di riserva, studio del funzionamento, interoperabilità);',
				'creare opere derivate, cloni o adattamenti, anche con strumenti automatici o sistemi di intelligenza artificiale, e usare il codice per addestrarli;',
				'rimuovere o alterare avvisi di copyright, marchi o indicazioni di titolarità.',
			  ] ],
			[ 'n' => '4', 'vex' => false, 'title' => 'Titolarità e diritto d’autore',
			  'body' => 'Il Software, il codice sorgente, la documentazione, l’architettura dei dati e ogni componente restano di titolarità esclusiva del Licenziante, protetti dalle norme sul diritto d’autore e sulla proprietà intellettuale. I marchi RelDoxAcademy e RelDoxAcademy non si usano senza consenso scritto.' ],
			[ 'n' => '5', 'vex' => false, 'title' => 'Aggiornamenti, sicurezza e supporto',
			  'body' => '5.1 Il Licenziante mantiene una sola linea di versioni del Software. Le correzioni dei difetti, comprese quelle di sicurezza, sono rese disponibili solo come nuova versione; non sono mantenute versioni precedenti né versioni dedicate a singoli Licenziatari.'
				. "\n" . '5.2 Finché la licenza è in corso, il Licenziatario ha diritto a ogni nuova versione senza costi oltre al Canone. Il Licenziante gestisce le vulnerabilità di ogni versione principale per almeno cinque anni dalla sua messa a disposizione, e comunque finché è in attività (art. 5.6).'
				. "\n" . '5.3 Le vulnerabilità si segnalano a support@ideatechnologies.com, indicato anche nel file security.txt del sito del Licenziante. Il Licenziante informa senza ritardo il Licenziatario, all’indirizzo dell’intestazione, delle vulnerabilità che riguardano la sua installazione, della versione che le corregge e delle misure da adottare nel frattempo; quando la legge lo richiede le notifica alle autorità competenti (Reg. UE 2024/2847).'
				. "\n" . '5.4 Il Licenziatario installa la versione che corregge una vulnerabilità entro quindici giorni dalla comunicazione, o nel termine più breve indicato per le vulnerabilità già sfruttate. Mantiene WordPress, PHP e il database in versioni supportate dai rispettivi produttori e almeno pari ai requisiti minimi indicati nel Software e nell’offerta; custodisce le credenziali dei propri utenti e segnala senza ritardo ogni sospetto di accesso non autorizzato.'
				. "\n" . '5.5 Il Software indica la versione e lo stadio (Alfa, Beta, Live, Gold). Le versioni Alfa e Beta sono fornite per il collaudo: il Licenziatario le usa sapendo che possono contenere difetti.'
				. "\n" . '5.6 Cessazione dell’attività. Gli obblighi del Licenziante su aggiornamenti, supporto e manutenzione valgono finché il Licenziante è in attività. In caso di cessazione dell’attività il Licenziante ne dà avviso ai Licenziatari, all’indirizzo dell’intestazione, prima che la cessazione abbia effetto, e informa le autorità competenti come previsto dal Reg. UE 2024/2847; resta salvo il diritto del Licenziatario di esportare i propri dati (art. 6.1).' ],
			[ 'n' => '6', 'vex' => false, 'title' => 'Dati del Licenziatario e utente di servizio e funzioni di intelligenza artificiale',
			  'body' => '6.1 I Dati del Licenziatario restano suoi. Il Software ne permette in ogni momento l’esportazione in formati aperti (CSV, Excel). Il Licenziante non accede ai dati se non per l’assistenza richiesta e non li usa per altri fini.'
				. "\n" . '6.2 Utente di servizio. Il Software contiene un utente di servizio con poteri di amministratore dell’applicativo, destinato alla manutenzione e all’assistenza. Salvo diversa scelta del Licenziatario, la sua casella è quella del Licenziante (admin@reldox.it). L’utente di servizio opera solo nel Software: non dà accesso al sito WordPress né alle sue credenziali, che restano del Licenziatario. Il Licenziatario può in ogni momento sostituire la casella con una propria; da quel momento il Licenziante accede solo con l’autorizzazione del Licenziatario. Ogni accesso dell’utente di servizio è registrato nel Log accessi del Software.'
				. "\n" . '6.3 Funzioni di intelligenza artificiale. Il Software può offrire funzioni che usano un servizio di intelligenza artificiale di terzi. Il servizio è scelto e contrattualizzato direttamente dal Licenziatario, che lo attiva con una chiave propria: il Licenziante non fornisce chiavi, non è parte del contratto con il fornitore del servizio e non riceve né conserva i dati inviati. Il Software indica quali dati invia al servizio e permette di disattivare queste funzioni in ogni momento. Il Licenziatario decide quali utenti possono usarle, ne sostiene i costi, verifica i contenuti generati prima di usarli, perché possono contenere errori, e resta titolare del trattamento dei dati che invia.' ],
			[ 'n' => '7', 'vex' => false, 'title' => 'Protezione dei dati personali',
			  'body' => 'Per i dati personali trattati con il Software il Licenziatario è titolare del trattamento. Quando presta assistenza con accesso ai dati, il Licenziante agisce come responsabile del trattamento (art. 28 Reg. UE 2016/679) secondo l’accordo allegato, adotta misure di sicurezza adeguate (art. 32) e comunica senza ingiustificato ritardo le violazioni di dati di cui venga a conoscenza. L’informativa agli interessati spetta al Licenziatario.' ],
			[ 'n' => '8', 'vex' => false, 'title' => 'Garanzia e limitazione di responsabilità',
			  'body' => 'Il Licenziante garantisce che il Software funziona in modo sostanzialmente conforme alla documentazione e corregge i difetti segnalati in tempi ragionevoli. Nei limiti di legge, il Licenziante non risponde dei danni indiretti o consequenziali, e la sua responsabilità complessiva non supera i corrispettivi pagati negli ultimi dodici mesi. Restano esclusi da ogni limite il dolo e la colpa grave (art. 1229 c.c.) e la responsabilità per danno da prodotto difettoso prevista dalla legge (Dir. UE 2024/2853 e norme di recepimento).'
				. "\n" . 'Il Licenziante non risponde dei danni causati dalla mancata o tardiva installazione delle versioni che correggono vulnerabilità, dall’uso del Software in un ambiente sotto i requisiti minimi o non più supportato dai rispettivi produttori, da credenziali custodite in modo non diligente dal Licenziatario, o dall’uso del Software dopo la cessazione della licenza.' ],
			[ 'n' => '9', 'vex' => false, 'title' => 'Corrispettivi',
			  'body' => '9.1 Per l’attivazione della licenza il Licenziatario paga una sola volta il costo di attivazione indicato nell’offerta accettata, che fa parte del contratto.'
				. "\n" . '9.2 La licenza comprende obbligatoriamente la manutenzione: il Licenziatario paga ogni anno un Canone, fatturato all’inizio di ogni annualità e pagato nei termini indicati nell’offerta, pari al 20% del costo di attivazione indicato nell’offerta (art. 9.1), dal primo anno, insieme al costo di attivazione. Ogni annualità copre 12 mesi, con le date di inizio e fine scritte nell’offerta e in fattura (dal gg/mm/aaaa al gg/mm/aaaa); la prima decorre dalla data di attivazione. Il Canone comprende il diritto d’uso per l’anno, tutte le nuove versioni (correttive, di sicurezza e funzionali), l’adeguamento del Software alle nuove versioni di WordPress e PHP e la correzione dei difetti del Software segnalati dal Licenziatario, nonché l’accesso degli utenti del Licenziatario ai corsi on line di autoformazione sul Software messi a disposizione dal Licenziante, che non costituiscono assistenza all’uso. Il Canone non comprende l’avvio, la configurazione, l’assistenza all’uso e la messa a punto del Software, che si pagano a parte secondo l’art. 9.4. Il Software non si fornisce senza manutenzione.'
				. "\n" . '9.3 A ogni rinnovo il Canone è aggiornato del 100% della variazione dell’indice ISTAT dei prezzi al consumo per le famiglie di operai e impiegati (FOI) senza tabacchi, riferita ai dodici mesi precedenti; se la variazione è negativa il Canone resta invariato. La variazione si calcola sull’ultimo indice pubblicato dall’ISTAT nel mese che precede la data di rinnovo. Il meccanismo è scritto anche nell’offerta, e in ogni fattura di rinnovo si indicano il canone dell’anno prima, la variazione applicata e il canone nuovo.'
				. "\n" . '9.4 L’avvio, la configurazione, l’assistenza all’uso, la messa a punto, le installazioni presso il Licenziatario, le personalizzazioni, la formazione e ogni intervento non compreso nel Canone si pagano a parte, a monte ore o secondo le tariffe indicate nell’offerta.'
				. "\n" . '9.5 In caso di ritardato pagamento del costo di attivazione o del Canone si applicano l’indennità di ritardato pagamento e le altre conseguenze previste dagli artt. 5.3, 5.6 e 8.2 delle Condizioni Generali di Fornitura di Idea Technologies Srl, nella versione in vigore alla firma dell’offerta. L’uso del Software dopo la risoluzione della licenza ai sensi dell’art. 10.3 costituisce violazione della presente licenza e obbliga il Licenziatario al risarcimento del danno.' ],
			[ 'n' => '10', 'vex' => false, 'title' => 'Durata, recesso e risoluzione',
			  'body' => '10.1 La licenza dura un anno dall’attivazione e si rinnova di anno in anno con il pagamento del Canone. Il diritto d’uso dura finché il Canone è pagato.'
				. "\n" . '10.2 Ciascuna parte può recedere con preavviso scritto di 90 giorni prima della scadenza annuale.'
				. "\n" . '10.3 Il Licenziante può risolvere il contratto (art. 1456 c.c.) se il Licenziatario viola l’art. 3 o non paga il Canone dopo un sollecito rimasto senza esito per 30 giorni.'
				. "\n" . '10.4 Sola consultazione. Alla cessazione della licenza, per qualunque causa, il Software passa in sola consultazione: gli utenti del Licenziatario continuano ad accedere, cercare, leggere ed esportare tutti i propri dati e documenti, ma non possono inserirne di nuovi né modificarli. Cessano le nuove versioni, l’assistenza e gli obblighi dell’art. 5. La sola consultazione resta disponibile finché il Software è installato presso il Licenziatario, senza costi; in ogni momento il Licenziatario può esportare l’intero archivio (dati in CSV o Excel, documenti e messaggi nei formati originali).'
				. "\n" . '10.4 bis Chiave di licenza. Il diritto d’uso è attestato da una chiave di licenza firmata dal Licenziante, che riporta il prodotto, il dominio del sito su cui il Software è installato e l’ultimo giorno di validità. La chiave è verificata dal Software sul sito del Licenziatario, senza collegamenti al Licenziante e senza invio di dati all’esterno. Dai 60 giorni che precedono l’ultimo giorno di validità il Software lo segnala agli amministratori; dal giorno successivo passa in sola consultazione (art. 10.4), senza periodi di tolleranza. Ogni chiave di rinnovo o di riattivazione vale 12 mesi dall’ultimo giorno di validità della chiave precedente, anche se emessa prima di quella data. In caso di cambio di dominio il Licenziante emette una chiave per il nuovo dominio; la chiave precedente resta utilizzabile solo sul vecchio dominio e fino alla sua scadenza.'
				. "\n" . '10.5 Sicurezza della sola consultazione. Poiché la versione installata non riceve più correzioni, in sola consultazione il Software disattiva le funzioni raggiungibili dall’esterno (pagine pubbliche, API, invio di posta, funzioni di intelligenza artificiale) e resta accessibile solo agli utenti del Licenziatario autenticati. Restano attive le sole funzioni che servono alla sicurezza e all’accesso ai dati, nella misura indicata nella documentazione di ciascun prodotto (per esempio il recupero della password, le copie di sicurezza, la sorveglianza delle vulnerabilità, le preferenze di consultazione dei singoli utenti, la sola disattivazione degli utenti). Dalla cessazione la sicurezza dell’installazione è a carico del Licenziatario; il Licenziante raccomanda di esportare l’archivio e, quando la consultazione non serve più, di disinstallare il Software.'
				. "\n" . '10.6 Il Licenziatario può riattivare la licenza in ogni momento pagando un Canone annuo, senza le annualità precedenti non pagate; la chiave di riattivazione vale 12 mesi dall’ultimo giorno di validità della chiave precedente (art. 10.4 bis). Con la riattivazione riceve l’ultima versione. La licenza non prevede sospensioni: i mesi trascorsi in sola consultazione sono compresi nell’annualità pagata alla riattivazione.'
				. "\n" . '10.7 I dati eventualmente custoditi su sistemi del Licenziante (per esempio copie fatte per l’assistenza) sono cancellati entro 30 giorni dalla cessazione.'
				. "\n" . '10.8 Uso senza pagamento e assistenza. L’uso del Software durante un periodo per il quale il Canone non è stato pagato costituisce violazione della presente licenza. In tal caso l’assistenza e i servizi a monte ore riprendono solo dopo il pagamento di tutto quanto dovuto, comprensivo dell’indennità di ritardato pagamento e dei costi di recupero previsti dall’art. 9.5, fermo l’art. 10.3.' ],
			[ 'n' => '11', 'vex' => false, 'title' => 'Riservatezza',
			  'body' => 'Le parti tengono riservate le informazioni tecniche e commerciali ricevute, durante il contratto e per tre anni dopo.' ],
			[ 'n' => '12', 'vex' => false, 'title' => 'Cessione del contratto',
			  'body' => 'Il Licenziatario non può cedere il contratto senza consenso scritto del Licenziante. Il Licenziante può avvalersi di propri fornitori, restando responsabile del loro operato.' ],
			[ 'n' => '13', 'vex' => false, 'title' => 'Comunicazioni',
			  'body' => 'Le comunicazioni si inviano per PEC o all’indirizzo amministrazione@ideatechnologies.com per il Licenziante, e all’indirizzo indicato nell’intestazione per il Licenziatario.' ],
			[ 'n' => '14', 'vex' => false, 'title' => 'Legge applicabile e foro competente',
			  'body' => 'Il contratto è regolato dalla legge italiana. Per ogni controversia è competente in via esclusiva il Foro di Milano.' ],
			[ 'n' => '15', 'vex' => false, 'title' => 'Sottoscrizione e approvazione specifica',
			  'body' => '15.1 Il contratto si sottoscrive sul presente documento consegnato dal Licenziante oppure, quando il Software ne contiene la stessa versione, sul documento PDF generato dal Software; entrambi riportano la versione e il codice di controllo del testo.'
				. "\n" . '15.2 Ai sensi degli artt. 1341 e 1342 c.c. il Licenziatario approva specificamente gli articoli 3 (Limiti d’uso), 5.4 (termine di installazione e requisiti), 5.6 (cessazione dell’attività), 8 (Garanzia e limitazione di responsabilità), 9.2-9.3 (canone obbligatorio e indicizzazione), 9.5 (ritardato pagamento), 10 (durata legata al canone, recesso, risoluzione, sola consultazione e sua sicurezza, chiave di licenza), 10.8 (uso senza pagamento e assistenza), 12 (Cessione del contratto) e 14 (Foro competente).'
				. "\n" . '15.3 Il Contratto è sottoscritto, a scelta del Licenziatario, in uno dei due modi seguenti, che si escludono a vicenda: (a) con firma digitale, apponendo due firme distinte sul documento elettronico, una per la sottoscrizione del Contratto e una per l’approvazione specifica delle clausole indicate al punto 15.2; (b) con firma autografa, apponendo le due firme negli spazi predisposti del documento stampato; in tal caso il Licenziatario anticipa per posta elettronica a amministrazione@ideatechnologies.com la copia per immagine del documento firmato e ne spedisce l’originale cartaceo a Idea Technologies Srl, Via F. Gilera 110, 20862 Arcore (MB), entro quindici giorni dall’invio.'
				. "\n" . '15.4 Il Contratto si intende concluso quando Idea Technologies Srl riceve il documento firmato secondo il punto 15.3; nel caso (b), quando riceve la copia per immagine anticipata per posta elettronica; l’originale cartaceo resta dovuto nel termine indicato.' ],
		];
	}

	/** «3 (Limiti d'uso), 8 (Limitazione di responsabilità), … e 14 (Foro competente)». */
	public static function vex_testo(): string {
		$x = array();
		foreach ( self::VEX_ARTICLES as $n ) $x[] = $n . ' (' . self::VEX_TITOLI[ $n ] . ')';
		$ultimo = array_pop( $x );
		return implode( ', ', $x ) . ' e ' . $ultimo;
	}

	/**
	 * Il foro del contratto in vigore. 3.75.06 (S718) BUG-322: dalla v4 il foro è scritto nel testo (art. 14, «Foro di Milano») e vale quello,
	 * in mail, record ed eventi; il foro salvato dalla pagina (foro_salvato()) vale solo per un testo che ha ancora il segnaposto {foro}.
	 */
	public static function foro(): string {
		$testo = self::foro_del_testo();
		return $testo !== null ? $testo : self::foro_salvato();
	}

	/** Il foro scritto nel testo degli articoli («Foro di <città>»); null se il testo ha il segnaposto {foro} o non lo nomina. */
	public static function foro_del_testo(): ?string {
		$js = (string) wp_json_encode( self::articles() );
		if ( false !== strpos( $js, '{foro}' ) ) return null;
		foreach ( self::articles() as $a ) {
			if ( preg_match( '/\bForo di (\p{Lu}[\p{L}’\' \-]*?)\s*(?:[.,;]|$)/u', (string) $a['body'], $m ) ) return trim( $m[1] );
		}
		return null;
	}

	/** Il foro scritto nella pagina e salvato in `impostazioni` ('' se manca): entra nell impronta dell intestazione, che così non cambia. */
	public static function foro_salvato(): string {
		$r = Rdacademy_DB::get_row( "SELECT valore FROM `" . RDACADEMY_TABLE_PREFIX . "impostazioni` WHERE chiave = ?", array( self::K_FORO ), 's' );
		return trim( (string) ( $r['valore'] ?? '' ) );
	}

	/** Il foro valido? '' = sì. Vuoto ammesso (nel testo «[da indicare]»). */
	public static function foro_valido( string $f ): string {
		$f = trim( $f );
		if ( $f === '' ) return '';
		if ( mb_strlen( $f ) > 60 ) return 'Il foro competente supera 60 caratteri.';
		if ( ! preg_match( "/^[\\p{L}][\\p{L} '’.\\-]*$/u", $f ) ) return 'Il foro competente si scrive col nome della città (lettere, spazi, apostrofo, trattino), es. «Milano».';
		return '';
	}

	/** Gli articoli da mostrare e da stampare: {foro} riempito (o «[da indicare]»). */
	public static function articoli_resi( ?string $foro = null ): array {
		$foro = $foro ?? self::foro();
		$out = array();
		foreach ( self::articles() as $a ) { $a['body'] = str_replace( '{foro}', $foro !== '' ? $foro : self::FORO_VUOTO, $a['body'] ); $out[] = $a; }
		return $out;
	}

	/** Testo giuridico normalizzato (fonte dell impronta hash). 3.75.05 (S717) voce 166: lo stesso testo e lo stesso metodo del kit di firma
	 *  di Legale («Art. n — titolo», commi, punti «- …»; senza «#», spazi ridotti), così il codice di controllo del PDF è quello del kit (art. 15.1). */
	public static function canonical_text(): string {
		$parts = [];
		foreach ( self::articles() as $a ) {
			$parts[] = 'Art. ' . $a['n'] . ' — ' . $a['title'];
			$parts[] = $a['body'];
			if ( ! empty( $a['items'] ) ) {
				foreach ( $a['items'] as $it ) $parts[] = '- ' . $it;
			}
		}
		// normalizzazione whitespace deterministica
		$txt = preg_replace( '/\s+/u', ' ', str_replace( '#', '', implode( "\n", $parts ) ) );
		return trim( (string) $txt );
	}

	/** Impronta sha256 del testo giuridico (probatoria). */
	public static function text_hash(): string {
		return hash( 'sha256', self::canonical_text() );
	}

	/** sha256 breve per la UI (primi/ultimi caratteri). */
	public static function text_hash_short(): string {
		$h = self::text_hash();
		return substr( $h, 0, 4 ) . '…' . substr( $h, -4 );
	}

	// ─────────────────────────────────────────────────────────────────────
	//  INTESTATARIO (Licenziatario) — da rdacademy_impostazioni (ZERO ALTER)
	// ─────────────────────────────────────────────────────────────────────

	/** Chiavi SORGENTE (additive in rdacademy_impostazioni). 'tipo' = azienda|persona_fisica. */
	const HOLDER_MAP = [
		'tipo'          => 'license_holder_tipo',
		'denominazione' => 'license_holder_denominazione',
		'nome'          => 'license_holder_nome',
		'cognome'       => 'license_holder_cognome',
		'piva'          => 'license_holder_piva',
		'cf'            => 'license_holder_cf',
		'via'           => 'license_holder_via',
		'citta'         => 'license_holder_citta',
		'cap'           => 'license_holder_cap',
		'provincia'     => 'license_holder_provincia',
		'nazione'       => 'license_holder_nazione',
		'legale_rappr'  => 'license_holder_legale_rappr',
		'email'         => 'license_holder_email',      // 1.3.1B — email o PEC del Licenziatario (SPEC-FIRMA § 3)
	];

	/**
	 * Dati del Licenziatario per l intestazione. Ogni campo porta un flag
	 * 'missing' true quando la chiave in rdacademy_impostazioni e vuota o assente.
	 *
	 * @return array<string,array{label:string,value:string,missing:bool}>
	 */
	public static function licenziatario(): array {
		$map = self::HOLDER_MAP;
		$labels = [
			'tipo'          => 'Tipo soggetto',
			'denominazione' => 'Ragione sociale',
			'nome'          => 'Nome',
			'cognome'       => 'Cognome',
			'piva'          => 'P.IVA',
			'cf'            => 'Codice Fiscale',
			'via'           => 'Via e civico',
			'citta'         => 'Citta',
			'cap'           => 'CAP',
			'provincia'     => 'Provincia',
			'nazione'       => 'Nazione',
			'legale_rappr'  => 'Legale rappr.',
			'email'         => 'Email o PEC',
			// derivate:
			'piva_cf'       => 'P.IVA / C.F.',
			'sede'          => 'Sede legale',
		];
		$vals = [];
		$p    = RDACADEMY_TABLE_PREFIX;
		$keys = array_values( $map );
		$ph   = implode( ',', array_fill( 0, count( $keys ), '?' ) );
		$rows = Rdacademy_DB::get_results(
			"SELECT chiave, valore FROM `{$p}impostazioni` WHERE chiave IN ({$ph})",
			$keys, str_repeat( 's', count( $keys ) )
		);
		foreach ( $rows as $r ) {
			$vals[ (string) $r['chiave'] ] = trim( (string) $r['valore'] );
		}
		$out = [];
		foreach ( $map as $key => $chiave ) {
			$v = $vals[ $chiave ] ?? '';
			$out[ $key ] = [
				'label'   => $labels[ $key ],
				'value'   => $v,
				'missing' => ( '' === $v ),
			];
		}

		// ── Derivate (consumate dalla pagina e dal form di accettazione). ──
		$tipo = $out['tipo']['value'];

		// denominazione effettiva: ragione sociale (azienda) o "Nome Cognome" (persona fisica).
		if ( 'persona_fisica' === $tipo ) {
			$nome_completo = trim( $out['nome']['value'] . ' ' . $out['cognome']['value'] );
			$out['denominazione'] = [
				'label'   => 'Nome e cognome',
				'value'   => $nome_completo,
				'missing' => ( '' === $nome_completo ),
			];
		}

		// piva_cf derivata: P.IVA (azienda) o Codice Fiscale (persona fisica).
		$piva_cf = ( 'persona_fisica' === $tipo ) ? $out['cf']['value'] : $out['piva']['value'];
		$out['piva_cf'] = [
			'label'   => $labels['piva_cf'],
			'value'   => $piva_cf,
			'missing' => ( '' === $piva_cf ),
		];

		// sede derivata: composta dai 5 campi indirizzo.
		$sede = self::compose_sede(
			$out['via']['value'],
			$out['cap']['value'],
			$out['citta']['value'],
			$out['provincia']['value'],
			$out['nazione']['value']
		);
		$out['sede'] = [
			'label'   => $labels['sede'],
			'value'   => $sede,
			'missing' => ( '' === $sede ),
		];

		return $out;
	}

	/** Compone una sede leggibile dai 5 campi indirizzo (derivata, sola lettura). */
	public static function compose_sede( string $via, string $cap, string $citta, string $prov, string $naz ): string {
		$via   = trim( $via );
		$cap   = trim( $cap );
		$citta = trim( $citta );
		$prov  = trim( $prov );
		$naz   = trim( $naz );

		$riga2 = trim( $cap . ' ' . $citta );
		if ( '' !== $prov ) {
			$riga2 = trim( $riga2 . ' (' . strtoupper( $prov ) . ')' );
		}

		$parti = [];
		if ( '' !== $via )   { $parti[] = $via; }
		if ( '' !== $riga2 ) { $parti[] = $riga2; }
		if ( '' !== $naz )   { $parti[] = $naz; }

		return implode( ', ', $parti );
	}

	/** Licenziante (dati fissi del prodotto, dalle costanti dell identita). */
	public static function licenziante(): array {
		return [
			'denominazione' => RDACADEMY_VENDOR,
			'forma'         => self::FORMA_LICENZIANTE,
			'pec'           => self::PEC_LICENZIANTE,
			'piva_cf'       => self::PIVA_LICENZIANTE,
			'sede'          => self::indirizzo_licenziante(),
			'legale_rappr'  => '',
		];
	}

	// ─────────────────────────────────────────────────────────────────────
	//  STATO ACCETTAZIONE + REGISTRAZIONE PROBATORIA (additivo, ZERO ALTER)
	// ─────────────────────────────────────────────────────────────────────

	/** Record dell accettazione vigente (o null). */
	public static function get_acceptance(): ?array {
		$rec = Rdacademy_Option::bridge_get( self::OPT_ACCEPTANCE, null );   // S615 migrazione: in RelDoxAcademy una copia sul _1 puo esserci ancora (BUG-36), il ponte la porta sul BASE
		return is_array( $rec ) ? $rec : null;
	}

	/** NUOVO (lettura) — lo storico append-only, dal piu recente. */
	public static function get_log(): array {
		$log = Rdacademy_Option::bridge_get( self::OPT_LOG, [] );   // S615 migrazione (come sopra)
		return is_array( $log ) ? array_reverse( $log ) : [];
	}

	/**
	 * Vero se esiste un accettazione VIGENTE che combacia con l impronta del
	 * testo corrente. Se il testo EULA cambia (hash diverso) il consenso decade
	 * (re-consenso, standard premium).
	 */
	public static function is_accepted(): bool {
		// 1.3.1B — vale anche il contratto firmato e TRASMESSO sul testo in vigore (§ 6: col testo nuovo torna «da accettare»).
		$t = Rdacademy_Option::get( self::OPT_TRASMESSO, null );
		if ( is_array( $t ) && hash_equals( self::text_hash(), (string) ( $t['codice_testo'] ?? '' ) ) ) return true;
		$rec = self::get_acceptance();
		if ( ! $rec ) return false;
		return isset( $rec['text_hash'] ) && hash_equals( self::text_hash(), (string) $rec['text_hash'] );
	}

	/**
	 * Registra l accettazione (doppia sottoscrizione ex artt. 1341-1342 c.c.).
	 * Scrive il record VIGENTE e appende al LOG storico (prova durevole).
	 *
	 * @param array $signer  nome, denominazione, rappresentanza(bool), accept_general(bool), accept_vex(bool)
	 * @param array $user    utente WordPress corrente (id, nome)
	 * @return array{ok:bool,error?:string,record?:array}
	 */
	public static function record_acceptance( array $signer, array $user ): array {
		$nome        = trim( (string) ( $signer['nome'] ?? '' ) );
		$denom       = trim( (string) ( $signer['denominazione'] ?? '' ) );
		$rappr       = ! empty( $signer['rappresentanza'] );
		$acc_general = ! empty( $signer['accept_general'] );
		$acc_vex     = ! empty( $signer['accept_vex'] );

		// Tutte le condizioni obbligatorie (nessuna casella pre-spuntata a monte).
		if ( $nome === '' )   return [ 'ok' => false, 'error' => 'Nome e cognome del firmatario obbligatori.' ];
		if ( $denom === '' )  return [ 'ok' => false, 'error' => 'Denominazione rappresentata obbligatoria.' ];
		if ( ! $rappr )       return [ 'ok' => false, 'error' => 'Serve la dichiarazione dei poteri di rappresentanza.' ];
		if ( ! $acc_general ) return [ 'ok' => false, 'error' => 'Serve l\'accettazione generale del contratto.' ];
		if ( ! $acc_vex )     return [ 'ok' => false, 'error' => 'Serve la specifica approvazione degli articoli ' . implode( ', ', self::VEX_ARTICLES ) . ' (artt. 1341-1342 c.c.).' ];

		$now_utc = gmdate( 'Y-m-d\TH:i:s\Z' );
		$ip = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = substr( (string) $_SERVER['REMOTE_ADDR'], 0, 45 );
		}

		$record = [
			'schema'          => 2,
			'firma'           => 'pannello',   // 1.3.0B — prima prova; l approvazione specifica vale con la firma del PDF (art. 15)
			'foro'            => self::foro(),
			'signer_name'     => $nome,
			'denominazione'   => $denom,
			'rappresentanza'  => true,
			'sub_general'     => true,
			'sub_specifica'   => true,
			'vex_articles'    => self::VEX_ARTICLES,
			'user_id'         => (int) ( $user['id'] ?? 0 ),
			'user_name'       => (string) ( $user['nome'] ?? ( $user['name'] ?? '' ) ),
			'accepted_at_utc' => $now_utc,
			'product'         => RDACADEMY_PRODUCT,
			'plugin_version'  => RDACADEMY_VERSION,
			'plugin_stage'    => RDACADEMY_STAGE,
			'eula_version'    => self::EULA_VERSION,
			'text_hash'       => self::text_hash(),
			'domain'          => class_exists( 'Rdacademy_License_Anchor' ) ? Rdacademy_License_Anchor::current_domain() : '',
			'ip'              => $ip,
		];

		// LOG storico append-only PRIMA del vigente: se la scrittura del log
		// fallisce non deve esistere un vigente senza la sua riga di prova.
		$log = Rdacademy_Option::bridge_get( self::OPT_LOG, [] );   // S615 migrazione: lo storico di RelDoxAcademy (EULA v1, schema 1) resta intero e la riga nuova si appende
		if ( ! is_array( $log ) ) $log = [];
		$log[] = $record;
		if ( ! Rdacademy_Option::set( self::OPT_LOG, $log, false ) ) {
			return [ 'ok' => false, 'error' => 'Registrazione non riuscita: lo storico delle accettazioni non e stato scritto sul database. ' . Rdacademy_Option::last_error() ];
		}

		// Record VIGENTE (l ultimo valido per l impronta corrente).
		if ( ! Rdacademy_Option::set( self::OPT_ACCEPTANCE, $record, false ) ) {
			return [ 'ok' => false, 'error' => 'Registrazione non riuscita: l accettazione vigente non e stata scritta sul database. ' . Rdacademy_Option::last_error() ];
		}

		return [ 'ok' => true, 'record' => $record ];
	}

	// ─────────────────────────────────────────────────────────────────────
	//  1.3.0B · 1.3.1B — IL CONTRATTO: GENERATO, FIRMATO, TRASMESSO (SPEC-FIRMA-LICENZA-RELDOX-v1)
	// ─────────────────────────────────────────────────────────────────────

	/** Lo storico degli eventi del contratto, dal più recente (append-only, § 11). */
	public static function get_eventi(): array {
		$e = Rdacademy_Option::get( self::OPT_EVENTI, [] );
		return is_array( $e ) ? array_reverse( $e ) : [];
	}

	/** Appende un evento (mai si accorcia). Tipi (§ 11): pdf · allegato · allegato_rifiutato · allegato_rimosso · allegato_decaduto · trasmesso · trasmissione_ko · riaperto. */
	public static function evento( array $ev ): bool {
		$e = Rdacademy_Option::get( self::OPT_EVENTI, [] );
		if ( ! is_array( $e ) ) $e = [];
		$ev += [ 'quando_utc' => gmdate( 'Y-m-d\TH:i:s\Z' ), 'eula_version' => self::EULA_VERSION, 'text_hash' => self::text_hash(),
			'plugin_version' => RDACADEMY_VERSION, 'domain' => class_exists( 'Rdacademy_License_Anchor' ) ? Rdacademy_License_Anchor::current_domain() : '' ];
		$e[] = $ev;
		return Rdacademy_Option::set( self::OPT_EVENTI, $e, false );
	}

	/** L indirizzo postale di Idea Technologies per l originale cartaceo (§ 5 B); '' finché l owner non lo dà. */
	public static function indirizzo_licenziante(): string {
		return self::INDIRIZZO_LICENZIANTE;
	}

	/** «12/10/2026 alle 11:40» da un istante UTC. */
	public static function quando( string $utc, string $formato = 'd/m/Y \a\l\l\e H:i' ): string {
		$t = strtotime( str_replace( [ 'T', 'Z' ], [ ' ', '' ], $utc ) . ' UTC' );
		return $t ? wp_date( $formato, $t ) : $utc;
	}

	/* ---------- § 3 — L INTESTAZIONE OBBLIGATORIA ---------- */

	/**
	 * I campi dell intestazione che mancano o non vanno (§ 3): [[id del campo nella pagina, nome per la persona], …]. Una lista
	 * sola per la pagina e per il server: il pulsante spento e il rifiuto del server leggono questa.
	 */
	public static function intestazione_mancanti( ?array $h = null, ?string $foro = null ): array {
		$h = $h ?? self::licenziatario();
		$foro = $foro ?? self::foro();
		$V = 'Rdacademy_License_Validate';
		$pf = $h['tipo']['value'] === 'persona_fisica';
		$m = [];
		if ( $pf ) {
			if ( $h['nome']['value'] === '' || $h['cognome']['value'] === '' ) $m[] = [ 'rl_nome', 'nome e cognome' ];
			if ( $h['cf']['value'] === '' || ! $V::codice_fiscale( $h['cf']['value'] )['ok'] ) $m[] = [ 'rl_cf', 'codice fiscale' ];
		} else {
			if ( mb_strlen( $h['denominazione']['value'] ) < 2 ) $m[] = [ 'rl_denom', 'ragione sociale' ];
			if ( $h['piva']['value'] === '' || ! $V::piva( $h['piva']['value'] )['ok'] ) $m[] = [ 'rl_piva', 'partita IVA o codice fiscale' ];
		}
		$it = $V::is_italia( $h['nazione']['value'] !== '' ? $h['nazione']['value'] : 'Italia' );
		if ( $h['via']['value'] === '' || $h['citta']['value'] === ''
			|| ( $it && ( $h['cap']['value'] === '' || ! $V::cap( $h['cap']['value'] )['ok'] || $h['provincia']['value'] === '' || ! $V::provincia( $h['provincia']['value'] )['ok'] ) ) ) {
			$m[] = [ 'rl_via', $pf ? 'residenza' : 'sede legale' ];
		}
		if ( ! $pf && count( preg_split( '/\s+/u', trim( $h['legale_rappr']['value'] ), -1, PREG_SPLIT_NO_EMPTY ) ) < 2 ) $m[] = [ 'rl_legr', 'legale rappresentante' ];
		if ( ! is_email( $h['email']['value'] ) ) $m[] = [ 'rl_email', 'email o PEC' ];
		// 3.75.05 (S717) voce 166: il foro è nel testo della v4 (Milano), non si chiede più.
		return $m;
	}

	/** «Per generare il contratto mancano: sede legale, legale rappresentante.» ('' se non manca nulla). */
	public static function intestazione_motivo( ?array $m = null ): string {
		$m = $m ?? self::intestazione_mancanti();
		if ( ! $m ) return '';
		return 'Per generare il contratto ' . ( count( $m ) === 1 ? 'manca: ' : 'mancano: ' ) . implode( ', ', array_column( $m, 1 ) ) . '.';
	}

	/** L impronta dell intestazione (dati del Licenziatario e foro): dice se il contratto generato porta ancora quelli di oggi. */
	public static function intestazione_sha(): string {
		$h = self::licenziatario(); $x = [];
		foreach ( [ 'tipo', 'denominazione', 'piva_cf', 'sede', 'legale_rappr', 'email' ] as $k ) $x[] = $h[ $k ]['value'];
		$x[] = self::foro_salvato();   // 3.75.06 (S718) BUG-322: il salvato, così la correzione non fa decadere un contratto generato
		return hash( 'sha256', (string) wp_json_encode( $x ) );
	}

	/* ---------- § 11 — I RECORD ---------- */

	public static function get_generato(): ?array { $r = Rdacademy_Option::get( self::OPT_GENERATO, null ); return is_array( $r ) ? $r : null; }
	public static function get_trasmesso(): ?array { $r = Rdacademy_Option::get( self::OPT_TRASMESSO, null ); return is_array( $r ) ? $r : null; }
	public static function get_storico_contratti(): array { $r = Rdacademy_Option::get( self::OPT_STORICO, [] ); return is_array( $r ) ? array_reverse( $r ) : []; }

	/**
	 * Il contratto firmato allegato (o null). Il record della 1.3.0B (`firma`, `caricato_utc`, `nome_originale`) si legge col
	 * nome nuovo (`modalita`, `quando`, `nome`) senza riscriverlo: è di un testo precedente e non porta `id_generato`, quindi
	 * non vale per la trasmissione (§ 14).
	 */
	public static function get_firmato(): ?array {
		$r = Rdacademy_Option::get( self::OPT_FIRMATO, null );
		if ( ! is_array( $r ) ) return null;
		$r += [ 'modalita' => (string) ( $r['firma'] ?? '' ), 'quando' => (string) ( $r['caricato_utc'] ?? '' ), 'nome' => (string) ( $r['nome_originale'] ?? '' ),
			'id_generato' => '', 'riconosciuto' => '', 'firme' => 0, 'pagine' => 0, 'codice_testo' => (string) ( $r['text_hash'] ?? '' ) ];
		return $r;
	}

	/** La cartella protetta dei contratti (creata con .htaccess e index.php). '' se non si crea. */
	public static function cartella(): string {
		$up = wp_upload_dir( null, false );
		$d = rtrim( (string) ( $up['basedir'] ?? '' ), '/' ) . '/' . self::CARTELLA;
		if ( ! is_dir( $d ) ) wp_mkdir_p( $d );
		if ( ! is_dir( $d ) ) return '';
		if ( ! is_file( $d . '/.htaccess' ) ) @file_put_contents( $d . '/.htaccess', "# RelDoxAcademy — contratti: accesso negato dal web.\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n" );
		if ( ! is_file( $d . '/index.php' ) ) @file_put_contents( $d . '/index.php', "<?php\n// Silence is golden.\n" );
		return $d;
	}

	/** Il percorso di un file della cartella protetta, se c è e ha l impronta registrata ('' altrimenti). */
	private static function file_registrato( ?array $r ): string {
		if ( ! $r || empty( $r['file'] ) || basename( (string) $r['file'] ) !== (string) $r['file'] ) return '';
		$d = self::cartella();
		$p = $d . '/' . $r['file'];
		return ( $d !== '' && is_file( $p ) && hash_equals( (string) ( $r['sha256'] ?? '' ), (string) hash_file( 'sha256', $p ) ) ) ? $p : '';
	}

	/** Il file allegato vigente ('' se manca o è cambiato). */
	public static function file_firmato( ?array $f = null ): string { return self::file_registrato( $f ?? self::get_firmato() ); }

	/** Il PDF generato vigente ('' se manca o è cambiato). */
	public static function file_generato( ?array $g = null ): string { return self::file_registrato( $g ?? self::get_generato() ); }

	/** Il tipo del file dai primi byte (non dal nome): pdf | p7m | jpg | png | ''. */
	public static function tipo_da_byte( string $b, string $ext ): string {
		if ( strncmp( $b, '%PDF-', 5 ) === 0 ) return 'pdf';
		if ( strncmp( $b, "\xFF\xD8\xFF", 3 ) === 0 ) return 'jpg';
		if ( strncmp( $b, "\x89PNG\r\n\x1A\n", 8 ) === 0 ) return 'png';
		if ( Rdacademy_License_Firma::e_busta_cms( $b ) ) return 'p7m';
		return '';
	}

	/* ---------- § 2 · § 6 — LO STATO E LA RIAPERTURA ---------- */

	/**
	 * § 6 — Riapertura: il contratto trasmesso è di un testo diverso da quello in vigore (Idea Technologies ha pubblicato un testo
	 * nuovo con un aggiornamento). I record vigenti generato / firmato / trasmesso passano nello storico dei contratti (append-only)
	 * e il percorso riparte da capo. Nessun comando la fa a mano: è una conseguenza del testo nuovo.
	 */
	public static function riapri_se_testo_nuovo(): bool {
		$t = self::get_trasmesso();
		if ( ! $t || hash_equals( self::text_hash(), (string) ( $t['codice_testo'] ?? '' ) ) ) return false;
		$st = Rdacademy_Option::get( self::OPT_STORICO, [] );
		if ( ! is_array( $st ) ) $st = [];
		$st[] = [ 'generato' => self::get_generato(), 'firmato' => self::get_firmato(), 'trasmesso' => $t, 'riaperto_utc' => gmdate( 'Y-m-d\TH:i:s\Z' ), 'codice_nuovo' => self::text_hash() ];
		if ( ! Rdacademy_Option::set( self::OPT_STORICO, $st, false ) ) return false;   // prima lo storico: niente si perde
		self::evento( [ 'tipo' => 'riaperto', 'codice_vecchio' => (string) ( $t['codice_testo'] ?? '' ), 'codice_nuovo' => self::text_hash() ] );
		Rdacademy_Option::del( self::OPT_TRASMESSO );
		Rdacademy_Option::del( self::OPT_FIRMATO );
		Rdacademy_Option::del( self::OPT_GENERATO );
		return true;
	}

	/**
	 * Lo stato del percorso (§ 2), ricavato dai record e mai scritto a parte.
	 * @return array{fase:string,mancanti:array,generato:?array,firmato:?array,trasmesso:?array,generato_ok:bool,firmato_ok:bool,intestazione_cambiata:bool,logo_idea:bool}
	 */
	public static function stato(): array {
		self::riapri_se_testo_nuovo();
		$hash = self::text_hash();
		$g = self::get_generato(); $f = self::get_firmato(); $t = self::get_trasmesso();
		$mancanti = self::intestazione_mancanti();
		$g_ok = $g && hash_equals( $hash, (string) ( $g['codice_testo'] ?? '' ) ) && self::file_generato( $g ) !== '';
		$f_ok = $g_ok && $f && (string) $f['id_generato'] !== '' && hash_equals( (string) $g['id'], (string) $f['id_generato'] ) && self::file_firmato( $f ) !== '';
		$t_ok = $t && hash_equals( $hash, (string) ( $t['codice_testo'] ?? '' ) );
		// 3.60.03G BUG-269 S616_B269 — la verifica vale per QUEL file: esito ok e la stessa impronta del file allegato
		$v_ok = $f_ok && ( (string) ( $f['verifica']['esito'] ?? '' ) ) === 'ok' && hash_equals( (string) ( $f['sha256'] ?? '' ), (string) ( $f['verifica']['sha256'] ?? '' ) );
		$fase = $t_ok ? 'trasmesso' : ( $f_ok ? 'firmato' : ( $g_ok ? 'generato' : ( $mancanti ? 'incompleta' : 'pronto' ) ) );
		return [ 'fase' => $fase, 'mancanti' => $mancanti, 'generato' => $g, 'firmato' => $f, 'trasmesso' => $t_ok ? $t : null,
			'generato_ok' => $g_ok, 'firmato_ok' => $f_ok, 'verificato_ok' => $v_ok, 'intestazione_cambiata' => $g_ok && ! $t_ok && (string) ( $g['intestazione_sha'] ?? '' ) !== self::intestazione_sha(),
			'logo_idea' => Rdacademy_License_Pdf::file_logo_idea() !== '' ];
	}

	/** Il motivo del blocco dopo la trasmissione (§ 6), '' se non è trasmesso. */
	public static function motivo_trasmesso( ?array $s = null ): string {
		$s = $s ?? self::stato();
		if ( $s['fase'] !== 'trasmesso' ) return '';
		return 'Contratto trasmesso il ' . self::quando( (string) $s['trasmesso']['quando'] ) . '. Si potrà generare un nuovo contratto quando ' . rtrim( RDACADEMY_VENDOR, '.' ) . '. pubblicherà una nuova versione del testo.';
	}

	/** Perché «Genera e scarica il contratto» è spento ('' = acceso). Lo stesso controllo per la pagina e per il server. */
	public static function genera_ferma( ?array $s = null ): string {
		$s = $s ?? self::stato();
		if ( $s['fase'] === 'trasmesso' ) return self::motivo_trasmesso( $s );
		if ( $s['mancanti'] ) return self::intestazione_motivo( $s['mancanti'] );
		if ( ! $s['logo_idea'] ) return 'Manca il logo di Idea Technologies nel pacchetto: il contratto non si genera senza.';
		return '';
	}

	/* ---------- § 4 — GENERA E SCARICA ---------- */

	/**
	 * Genera il contratto (PDF dal server), lo conserva nella cartella protetta e scrive il record `generato`. Un allegato firmato
	 * su un PDF precedente DECADE (si rimuove, evento allegato_decaduto). @return array{ok:bool,error?:string,byte?:string,nome?:string,record?:array}
	 */
	public static function genera( array $user ): array {
		$s = self::stato();
		$ferma = self::genera_ferma( $s );
		if ( $ferma !== '' ) return [ 'ok' => false, 'error' => $ferma ];
		$d = self::cartella();
		if ( $d === '' ) return [ 'ok' => false, 'error' => 'La cartella protetta dei contratti non si crea: controlla i permessi di wp-content/uploads.' ];
		$id = bin2hex( random_bytes( 16 ) );
		$p = Rdacademy_License_Pdf::contratto( $id );
		if ( isset( $p['errore'] ) ) return [ 'ok' => false, 'error' => (string) $p['errore'] ];
		$file = 'generato-' . bin2hex( random_bytes( 8 ) ) . '.pdf';
		$h = hash( 'sha256', $p['byte'] );
		if ( @file_put_contents( $d . '/' . $file, $p['byte'] ) !== strlen( $p['byte'] ) || hash_file( 'sha256', $d . '/' . $file ) !== $h ) { @unlink( $d . '/' . $file ); return [ 'ok' => false, 'error' => 'Il contratto non si scrive nella cartella protetta.' ]; }
		$rec = [ 'id' => $id, 'sha256' => $h, 'byte' => strlen( $p['byte'] ), 'pagine' => (int) $p['pagine'], 'codice_testo' => self::text_hash(), 'versione_testo' => self::EULA_VERSION,
			'quando' => gmdate( 'Y-m-d\TH:i:s\Z' ), 'file' => $file, 'nome' => $p['nome'], 'foro' => self::foro(), 'intestazione_sha' => self::intestazione_sha(),
			'user_id' => (int) ( $user['id'] ?? 0 ), 'user_name' => (string) ( $user['nome'] ?? '' ) ];
		$f = self::get_firmato();
		if ( $f ) {
			if ( ! self::evento( [ 'tipo' => 'allegato_decaduto', 'nome' => $f['nome'], 'sha256' => (string) ( $f['sha256'] ?? '' ), 'id_generato' => $f['id_generato'], 'motivo' => 'Contratto generato di nuovo: vale l\'ultimo.' ] + self::utente_ev( $user ) ) )
				return [ 'ok' => false, 'error' => 'Lo storico non si scrive sul database. ' . Rdacademy_Option::last_error() ];
			Rdacademy_Option::del( self::OPT_FIRMATO );
		}
		if ( ! self::evento( [ 'tipo' => 'pdf', 'id_generato' => $id, 'pagine' => $rec['pagine'], 'byte' => $rec['byte'], 'sha256' => $h, 'foro' => $rec['foro'] ] + self::utente_ev( $user ) ) )
			return [ 'ok' => false, 'error' => 'Lo storico non si scrive sul database. ' . Rdacademy_Option::last_error() ];
		if ( ! Rdacademy_Option::set( self::OPT_GENERATO, $rec, false ) ) return [ 'ok' => false, 'error' => 'Il contratto generato non si registra sul database. ' . Rdacademy_Option::last_error() ];
		return [ 'ok' => true, 'byte' => $p['byte'], 'nome' => $p['nome'], 'record' => $rec ];
	}

	private static function utente_ev( array $user ): array {
		return [ 'user_id' => (int) ( $user['id'] ?? 0 ), 'user_name' => (string) ( $user['nome'] ?? '' ) ];
	}

	/* ---------- § 5 — L ALLEGATO ---------- */

	/** Perché «Allega» è spento ('' = acceso). */
	public static function allega_ferma( ?array $s = null ): string {
		$s = $s ?? self::stato();
		if ( $s['fase'] === 'trasmesso' ) return self::motivo_trasmesso( $s );
		if ( $s['fase'] === 'firmato' ) return 'Hai allegato un contratto con ' . self::FIRME[ $s['firmato']['modalita'] ] . ': per cambiare file o modalità rimuovi l\'allegato.';
		if ( ! $s['generato_ok'] ) return 'Prima genera e scarica il contratto: si allega il contratto generato, firmato.';
		return '';
	}

	/**
	 * I controlli del file firmato (§ 5): '' = va bene, altrimenti il motivo scritto. $info riceve riconoscimento, firme, pagine.
	 */
	/**
	 * 3.75.07G S719_B321 — i campi firma predisposti che NON portano una firma digitale leggibile (etichette tra «»; [] = firmati tutti e due).
	 * Legge le buste CMS dei campi con Rdacademy_License_Verifica::firmatari() (lo stesso lettore di «Verifica il documento»).
	 * @return string[]
	 */
	public static function campi_senza_firma_digitale( string $b ): array {
		$f = class_exists( 'Rdacademy_License_Verifica' ) ? Rdacademy_License_Verifica::firmatari( $b ) : array();
		$manca = array();
		foreach ( array( Rdacademy_License_Firma::CAMPO_CONTRATTO => '«Firma del contratto»', Rdacademy_License_Firma::CAMPO_APPROVAZIONE => '«Approvazione specifica»' ) as $c => $etichetta ) {
			if ( empty( $f[ $c ] ) || (string) ( $f[ $c ]['errore'] ?? '' ) !== '' ) $manca[] = $etichetta;
		}
		return $manca;
	}

	public static function controlla( string $b, string $modalita, array $g, ?array &$info = null ): string {
		$info = [ 'riconosciuto' => '', 'firme' => 0, 'pagine' => 0 ];
		if ( strlen( $b ) > self::ALLEGATO_MAX ) return 'Il file supera 10 MB.';
		if ( Rdacademy_License_Firma::e_busta_cms( substr( $b, 0, 16 ) ) ) return 'La busta .p7m firma il file intero una volta sola: serve il PDF con le due firme nei campi predisposti.';
		if ( strncmp( $b, '%PDF-', 5 ) !== 0 ) return $modalita === 'autografa'
			? 'Serve un PDF con tutte le pagine scansionate: le immagini sciolte non bastano, il contratto ha più pagine.'
			: 'Il file non è un PDF: serve il contratto firmato, che resta .pdf.';
		$a = Rdacademy_License_Firma::analizza( $b );
		$info['pagine'] = (int) $a['pagine'];
		$info['firme']  = count( $a['firme'] );
		// 3.60.07G S618B_VIA_ALLEGA — owner 2026-10-14 06:59 «togli i controlli … Lascia solo allega e trasmetti»: si allega il PDF così com è; restano solo le
		// misure per lo storico (pagine, firme, riconoscimento), nessun rifiuto sulle firme, sulle pagine o sul contenuto.
		if ( $modalita === 'autografa' ) { $info['riconosciuto'] = 'autografa'; return ''; }
		// 3.75.07G S719_B321 — owner 2026-11-18 13:24 «verifica l esistenza della presenza delle firme digitali sul doc», 17:01 «Rifiuto con il motivo»:
		// con «firma digitale» ognuno dei due campi predisposti deve portare una firma digitale leggibile; altrimenti il file non si allega e il motivo
		// nomina il campo. Solo la presenza: nessun confronto di persona, pagine o contenuto (i controlli tolti il 14/10 restano tolti).
		$manca = self::campi_senza_firma_digitale( $b );
		if ( $manca ) {
			return 'Con la firma digitale servono due firme digitali, una in ogni campo: manca nel campo ' . implode( ' e nel campo ', $manca ) . '. '
				. 'Una firma disegnata o scritta a mano sul PDF non è una firma digitale: firma il contratto con il tuo dispositivo di firma digitale e allegalo di nuovo, '
				. 'oppure scegli «firma autografa» e allega la scansione di tutte le pagine firmate a mano.';
		}
		$gp0 = self::file_generato( $g ); $gb0 = $gp0 !== '' ? (string) file_get_contents( $gp0 ) : '';
		if ( $gb0 !== '' && strlen( $b ) >= strlen( $gb0 ) && strncmp( $b, $gb0, strlen( $gb0 ) ) === 0 ) $info['riconosciuto'] = 'byte';
		elseif ( $a['id'] !== '' && hash_equals( strtolower( (string) $g['id'] ), $a['id'] ) ) $info['riconosciuto'] = 'id';
		return '';
		if ( $modalita === 'autografa' ) {
			if ( (int) $a['pagine'] !== (int) $g['pagine'] ) return 'La scansione ha ' . (int) $a['pagine'] . ' ' . ( (int) $a['pagine'] === 1 ? 'pagina' : 'pagine' ) . ', il contratto ' . (int) $g['pagine'] . ': scansiona tutte le pagine.';
			$info['riconosciuto'] = 'autografa';
			return '';
		}
		// digitale: 2. almeno due firme, una per campo
		$c1 = ! empty( $a['campi'][ Rdacademy_License_Firma::CAMPO_CONTRATTO ]['firmato'] );
		$c2 = ! empty( $a['campi'][ Rdacademy_License_Firma::CAMPO_APPROVAZIONE ]['firmato'] );
		if ( count( $a['firme'] ) < 2 || ! $c1 || ! $c2 ) {
			$manca = [];
			if ( ! $c1 ) $manca[] = '«Firma del contratto»';
			if ( ! $c2 ) $manca[] = '«Approvazione specifica»';
			return 'Servono due firme digitali, una in ogni campo: ' . ( $manca ? 'manca la firma nel campo ' . implode( ' e nel campo ', $manca ) : 'il file ne porta ' . count( $a['firme'] ) ) . '.';
		}
		// 3. è il nostro contratto
		$gp = self::file_generato( $g );
		$gb = $gp !== '' ? (string) file_get_contents( $gp ) : '';
		if ( $gb !== '' && strlen( $b ) > strlen( $gb ) && strncmp( $b, $gb, strlen( $gb ) ) === 0 ) $info['riconosciuto'] = 'byte';
		elseif ( $a['id'] !== '' && hash_equals( strtolower( (string) $g['id'] ), $a['id'] ) ) $info['riconosciuto'] = 'id';
		else return 'Questo PDF non è il contratto generato da questa installazione: scaricalo di nuovo e firmalo.';
		// 4. nulla dopo l ultima firma
		if ( ! $a['fine_ok'] ) return 'Dopo l\'ultima firma il file è stato modificato: allega il PDF così come esce dal programma di firma.';
		return '';
	}

	/**
	 * Registra il contratto firmato allegato. @return array{ok:bool,error?:string,record?:array}
	 * $tmp = file caricato (is_uploaded_file già controllato da chi chiama, o file locale per il banco), $nome = nome originale.
	 */
	public static function allega( string $tmp, string $nome, string $modalita, array $user ): array {
		$s = self::stato();
		$ferma = self::allega_ferma( $s );
		if ( $ferma !== '' ) return [ 'ok' => false, 'error' => $ferma ];
		if ( ! isset( self::FIRME[ $modalita ] ) ) return [ 'ok' => false, 'error' => 'Scegli come firmi il contratto: firma digitale o firma autografa.' ];
		if ( ! is_file( $tmp ) || (int) filesize( $tmp ) <= 0 ) return [ 'ok' => false, 'error' => 'Nessun file ricevuto.' ];
		$g = $s['generato'];
		$b = (string) file_get_contents( $tmp );
		$motivo = self::controlla( $b, $modalita, $g, $info );
		$nome_s = mb_substr( sanitize_file_name( $nome ), 0, 120 );
		if ( $motivo !== '' ) {
			self::evento( [ 'tipo' => 'allegato_rifiutato', 'modalita' => $modalita, 'nome' => $nome_s, 'byte' => strlen( $b ), 'sha256' => hash( 'sha256', $b ), 'motivo' => $motivo, 'id_generato' => $g['id'] ] + self::utente_ev( $user ) );
			return [ 'ok' => false, 'error' => $motivo ];
		}
		$d = self::cartella();
		if ( $d === '' ) return [ 'ok' => false, 'error' => 'La cartella protetta dei contratti non si crea: controlla i permessi di wp-content/uploads.' ];
		$file = 'firmato-' . bin2hex( random_bytes( 8 ) ) . '.pdf';
		$h = hash( 'sha256', $b );
		if ( @file_put_contents( $d . '/' . $file, $b ) !== strlen( $b ) || hash_file( 'sha256', $d . '/' . $file ) !== $h ) { @unlink( $d . '/' . $file ); return [ 'ok' => false, 'error' => 'Il file non si scrive nella cartella protetta.' ]; }
		$rec = [ 'modalita' => $modalita, 'id_generato' => $g['id'], 'riconosciuto' => $info['riconosciuto'], 'firme' => $info['firme'], 'pagine' => $info['pagine'],
			'sha256' => $h, 'byte' => strlen( $b ), 'nome' => $nome_s, 'file' => $file, 'ext' => 'pdf', 'quando' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'codice_testo' => self::text_hash(), 'eula_version' => self::EULA_VERSION, 'foro' => self::foro() ] + self::utente_ev( $user );
		if ( ! self::evento( [ 'tipo' => 'allegato' ] + $rec ) ) return [ 'ok' => false, 'error' => 'Lo storico non si scrive sul database: allegato non registrato. ' . Rdacademy_Option::last_error() ];
		if ( ! Rdacademy_Option::set( self::OPT_FIRMATO, $rec, false ) ) return [ 'ok' => false, 'error' => 'Allegato non registrato sul database. ' . Rdacademy_Option::last_error() ];
		return [ 'ok' => true, 'record' => $rec ];
	}

	/** «Rimuovi l allegato»: solo prima della trasmissione; riporta a GENERATO. Il file resta nella cartella (lo storico lo nomina). */
	public static function rimuovi( array $user ): array {
		$s = self::stato();
		if ( $s['fase'] === 'trasmesso' ) return [ 'ok' => false, 'error' => self::motivo_trasmesso( $s ) ];
		$f = self::get_firmato();
		if ( ! $f ) return [ 'ok' => false, 'error' => 'Non c\'è nessun allegato da rimuovere.' ];
		if ( ! self::evento( [ 'tipo' => 'allegato_rimosso', 'modalita' => $f['modalita'], 'nome' => $f['nome'], 'sha256' => (string) ( $f['sha256'] ?? '' ), 'id_generato' => $f['id_generato'] ] + self::utente_ev( $user ) ) )
			return [ 'ok' => false, 'error' => 'Lo storico non si scrive sul database. ' . Rdacademy_Option::last_error() ];
		Rdacademy_Option::del( self::OPT_FIRMATO );
		return [ 'ok' => true ];
	}

	/* ---------- § 6 — LA TRASMISSIONE E IL BLOCCO ---------- */

	/** 3.60.03G BUG-269 — il motivo di «Trasmetti» spento finché il contratto firmato non è verificato. */
	const VERIFICA_MANCA = 'Prima premi «Verifica il documento»: RelDoxAcademy controlla che le due firme ci siano e siano della stessa persona.';

	/**
	 * 3.60.03G BUG-269 S616_B269 — «Verifica il documento» (owner 2026-10-13 12:04: «Devi verificare con tasto se il doc è corretto … In caso di
	 * fallimento azzera e il sistema deve dare la possibilità di ricaricare il file»). Controlli: Rdacademy_License_Verifica. Se passa, il record
	 * dell allegato porta la verifica (esito, impronta del file, riassunto, misure); se non passa l allegato si TOGLIE (il file resta nella
	 * cartella protetta, l evento verifica_ko lo nomina col motivo) e «Allega» torna acceso.
	 * @return array{ok:bool,error?:string,misure?:array,riassunto?:string}
	 */
	public static function verifica( array $user ): array {
		$s = self::stato();
		if ( $s['fase'] === 'trasmesso' ) return [ 'ok' => false, 'error' => self::motivo_trasmesso( $s ) ];
		$f = $s['firmato'];
		if ( ! $f || ! $s['firmato_ok'] ) return [ 'ok' => false, 'error' => 'Prima allega il contratto firmato.' ];
		$p = self::file_firmato( $f );
		$gp = self::file_generato( $s['generato'] );
		$motivo = ( $p === '' ) ? 'Il file allegato non c\'è più o è cambiato.' : ( ! class_exists( 'Rdacademy_License_Verifica' ) ? 'Il controllo delle firme non è installato.' : '' );
		$m = [];
		if ( $motivo === '' ) $motivo = Rdacademy_License_Verifica::controlla( (string) file_get_contents( $p ), (string) $f['modalita'], (array) $s['generato'], $gp !== '' ? (string) file_get_contents( $gp ) : '', $m );
		if ( $motivo !== '' ) {
			$ev = [ 'tipo' => 'verifica_ko', 'modalita' => $f['modalita'], 'nome' => $f['nome'], 'sha256' => (string) ( $f['sha256'] ?? '' ), 'id_generato' => $f['id_generato'], 'motivo' => $motivo,
				'misure' => self::misure_brevi( $m ) ] + self::utente_ev( $user );
			if ( ! self::evento( $ev ) ) return [ 'ok' => false, 'error' => 'Lo storico non si scrive sul database. ' . Rdacademy_Option::last_error() ];
			Rdacademy_Option::del( self::OPT_FIRMATO );   // azzera: l allegato si toglie, si può allegare di nuovo
			return [ 'ok' => false, 'error' => $motivo . ' Il file è stato tolto: allega di nuovo il contratto firmato.', 'misure' => $m ];
		}
		$riass = Rdacademy_License_Verifica::riassunto( $m );
		$f['verifica'] = [ 'esito' => 'ok', 'quando' => gmdate( 'Y-m-d\TH:i:s\Z' ), 'sha256' => (string) $f['sha256'], 'riassunto' => $riass, 'misure' => self::misure_brevi( $m ) ] + self::utente_ev( $user );
		if ( ! self::evento( [ 'tipo' => 'verificato', 'modalita' => $f['modalita'], 'nome' => $f['nome'], 'sha256' => (string) $f['sha256'], 'id_generato' => $f['id_generato'], 'riassunto' => $riass ] + self::utente_ev( $user ) ) )
			return [ 'ok' => false, 'error' => 'Lo storico non si scrive sul database. ' . Rdacademy_Option::last_error() ];
		$raw = Rdacademy_Option::get( self::OPT_FIRMATO, null );
		if ( ! is_array( $raw ) ) return [ 'ok' => false, 'error' => 'L\'allegato non c\'è più.' ];
		$raw['verifica'] = $f['verifica'];
		if ( ! Rdacademy_Option::set( self::OPT_FIRMATO, $raw, false ) ) return [ 'ok' => false, 'error' => 'La verifica non si registra sul database. ' . Rdacademy_Option::last_error() ];
		return [ 'ok' => true, 'misure' => $m, 'riassunto' => $riass ];
	}

	/** Le misure della verifica senza le griglie (per lo storico). */
	private static function misure_brevi( array $m ): array {
		unset( $m['griglie'] );
		return $m;
	}

	/** Perché «Trasmetti a Idea Technologies» è spento ('' = acceso). R-68: il motivo si scrive nella pagina. */
	public static function trasmetti_ferma( ?array $s = null ): string {
		$s = $s ?? self::stato();
		if ( $s['fase'] === 'trasmesso' ) return self::motivo_trasmesso( $s );
		$f = $s['firmato'];
		if ( ! $s['generato_ok'] ) return 'Prima genera e scarica il contratto, firmalo e allegalo.';
		if ( ! $f ) return 'Prima allega il contratto firmato.';
		if ( self::file_firmato( $f ) === '' ) return 'Il file allegato non c\'è più o è cambiato: allegalo di nuovo.';
		if ( ! $s['firmato_ok'] ) return 'Il file allegato è di un contratto precedente: rimuovilo e allega quello generato per ultimo, firmato.';
		if ( $s['intestazione_cambiata'] ) return 'L\'intestazione è cambiata dopo la generazione: genera di nuovo il contratto, firmalo e allegalo.';
		// 3.60.07G S618B_VIA_TX — owner 2026-10-14 06:59 «togli i controlli … Lascia solo allega e trasmetti»: nessuna verifica prima della trasmissione
		if ( $f['modalita'] === 'autografa' && self::indirizzo_licenziante() === '' ) return 'Manca l\'indirizzo postale di Idea Technologies per l\'originale cartaceo.';
		$st = Rdacademy_License_Testi::casella(); if ( empty( $st['verificata'] ) ) return 'La casella di servizio non è pronta: ' . (string) $st['motivo'] . ' ' . Rdacademy_License_Testi::DOVE_CASELLA;   // S615: la casella di servizio di RelDoxAcademy
		return '';
	}

	/** Le parole del riconoscimento (§ 5 A). */
	public static function riconoscimento( string $r ): string {
		return [ 'byte' => 'riconosciuto', 'id' => 'riconosciuto (file riscritto dal programma di firma)', 'autografa' => 'scansione del contratto firmato a mano' ][ $r ] ?? '—';
	}

	/**
	 * «Trasmetti a Idea Technologies»: mail dalla casella di servizio a EMAIL_LICENZIANTE col file firmato. Passo per passo.
	 * Riuscita → record `trasmesso`: da qui tutto è bloccato, anche dal server (§ 6).
	 * @return array{ok:bool,passi:array,testo:string}
	 */
	public static function trasmetti( array $user ): array {
		$P = []; $ok = true;
		$s = self::stato();
		$f = $s['firmato']; $g = $s['generato'];
		if ( $s['fase'] === 'trasmesso' ) { $P[] = [ 'esito' => 'ko', 'titolo' => 'Contratto ancora da trasmettere', 'server' => '', 'consiglio' => self::motivo_trasmesso( $s ) ]; $ok = false; }
		elseif ( ! $s['firmato_ok'] ) { $P[] = [ 'esito' => 'ko', 'titolo' => 'Contratto firmato allegato', 'server' => '', 'consiglio' => self::trasmetti_ferma( $s ) ]; $ok = false; }
		else $P[] = [ 'esito' => 'ok', 'titolo' => 'Contratto firmato allegato', 'server' => $f['nome'] . ' · ' . self::FIRME[ $f['modalita'] ] . ' · ' . self::riconoscimento( (string) $f['riconosciuto'] ) . ( $f['modalita'] === 'digitale' ? ' · ' . (int) $f['firme'] . ' firme' : '' ) . ' · sha256 ' . substr( (string) $f['sha256'], 0, 12 ) . '…', 'consiglio' => '' ];
		// 3.60.07G S618B_VIA_PASSO — owner 2026-10-14 06:59: tolto il passo «Contratto firmato verificato» (nessun controllo delle firme in RelDox)
		if ( $ok && $s['intestazione_cambiata'] ) { $P[] = [ 'esito' => 'ko', 'titolo' => 'Intestazione uguale a quella del contratto', 'server' => '', 'consiglio' => self::trasmetti_ferma( $s ) ]; $ok = false; }
		elseif ( $ok ) $P[] = [ 'esito' => 'ok', 'titolo' => 'Testo del contratto in vigore', 'server' => 'EULA v' . self::EULA_VERSION . ' · codice di controllo ' . Rdacademy_License_Pdf::codice_breve( self::text_hash() ) . ' · contratto ' . $g['id'], 'consiglio' => '' ];
		if ( $ok && $f['modalita'] === 'autografa' && self::indirizzo_licenziante() === '' ) { $P[] = [ 'esito' => 'ko', 'titolo' => 'Indirizzo per l\'originale cartaceo', 'server' => '', 'consiglio' => self::trasmetti_ferma( $s ) ]; $ok = false; }
		$st = Rdacademy_License_Testi::casella();   // S615: la casella di servizio di RelDoxAcademy
		if ( empty( $st['verificata'] ) ) { $P[] = [ 'esito' => $ok ? 'ko' : 'saltato', 'titolo' => 'Casella di servizio verificata', 'server' => '', 'consiglio' => (string) ( $st['motivo'] ?? '' ) . ' ' . Rdacademy_License_Testi::DOVE_CASELLA ]; $ok = false; }
		else $P[] = [ 'esito' => 'ok', 'titolo' => 'Casella di servizio verificata', 'server' => '', 'consiglio' => '' ];
		if ( $ok ) {
			$h = self::licenziatario();
			$quando = wp_date( 'd/m/Y H:i' );
			$aut = $f['modalita'] === 'autografa';
			$v = [ 'dominio' => class_exists( 'Rdacademy_License_Anchor' ) ? Rdacademy_License_Anchor::current_domain() : '', 'intestatario' => $h['denominazione']['value'] !== '' ? $h['denominazione']['value'] : '(intestatario non impostato)',
				'piva_cf' => $h['piva_cf']['value'], 'prodotto' => RDACADEMY_PRODUCT, 'versione' => RDACADEMY_VERSION_LABEL, 'eula' => 'v' . self::EULA_VERSION, 'impronta_testo' => self::text_hash(),
				'file' => $f['nome'], 'impronta_file' => (string) $f['sha256'], 'firma' => self::FIRME[ $f['modalita'] ], 'foro' => self::foro() !== '' ? self::foro() : self::FORO_VUOTO,
				'chi' => (string) ( $user['nome'] ?? '' ), 'quando' => $quando,
				// 1.3.1B — i dati della firma (testo licenza.trasmissione_firma)
				'modalita' => self::FIRME[ $f['modalita'] ], 'riconoscimento' => self::riconoscimento( (string) $f['riconosciuto'] ) . ( ! empty( $f['verifica']['riassunto'] ) ? ' · verificato da ' . RDACADEMY_PRODUCT . ': ' . (string) $f['verifica']['riassunto'] : '' ), 'firme' => $aut ? '2 (a mano)' : (string) (int) $f['firme'],
				'id_contratto' => (string) $g['id'], 'codice' => self::text_hash(), 'indirizzo' => self::indirizzo_licenziante(), 'licenziante' => rtrim( RDACADEMY_VENDOR, '.' ) . '.',
				'entro' => wp_date( 'd/m/Y', time() + 15 * DAY_IN_SECONDS ) ];
			$v['originale'] = $aut ? Rdacademy_License_Testi::riempi( (string) Rdacademy_License_Testi::modello( 'licenza.originale', 'it' )['testo'], $v, false ) : '';
			$e = Rdacademy_Password_Mail::invia_contratto( self::EMAIL_LICENZIANTE, $v, [ [ 'contratto-' . sanitize_title( $v['intestatario'] ) . '-eula-v' . self::EULA_VERSION . '-' . $f['modalita'] . '.pdf', (string) file_get_contents( self::file_firmato( $f ) ), 'application/pdf' ] ] );
			if ( ! empty( $e['success'] ) ) $P[] = [ 'esito' => 'ok', 'titolo' => 'Mail spedita a ' . self::EMAIL_LICENZIANTE, 'server' => trim( (string) ( $e['avviso'] ?? '' ) . ( $aut ? ' Originale cartaceo da ricevere.' : '' ) ), 'consiglio' => '' ];
			else { $P[] = [ 'esito' => 'ko', 'titolo' => 'Mail spedita a ' . self::EMAIL_LICENZIANTE, 'server' => (string) ( $e['error'] ?? '' ), 'consiglio' => 'Controlla la casella di servizio (' . Rdacademy_License_Testi::DOVE_CASELLA . ') e riprova.' ]; $ok = false; }
		}
		if ( $ok ) {
			$rec = [ 'quando' => gmdate( 'Y-m-d\TH:i:s\Z' ), 'esito' => 'trasmesso', 'codice_testo' => self::text_hash(), 'id_generato' => (string) $g['id'], 'sha256_firmato' => (string) $f['sha256'],
				'destinatario' => self::EMAIL_LICENZIANTE, 'modalita' => $f['modalita'], 'riconosciuto' => (string) $f['riconosciuto'], 'firme' => (int) $f['firme'],
				'originale_da_ricevere' => $f['modalita'] === 'autografa' ] + self::utente_ev( $user );
			self::evento( [ 'tipo' => 'trasmesso', 'a' => self::EMAIL_LICENZIANTE, 'file' => (string) $f['file'], 'sha256' => (string) $f['sha256'], 'modalita' => $f['modalita'], 'id_generato' => (string) $g['id'] ] + self::utente_ev( $user ) );
			if ( ! Rdacademy_Option::set( self::OPT_TRASMESSO, $rec, false ) ) { $P[] = [ 'esito' => 'avviso', 'titolo' => 'Trasmissione registrata', 'server' => Rdacademy_Option::last_error(), 'consiglio' => 'La mail è partita ma il database non ha scritto il record: avvisa Idea Technologies.' ]; }
		} else {
			$ul = end( $P );
			self::evento( [ 'tipo' => 'trasmissione_ko', 'a' => self::EMAIL_LICENZIANTE, 'file' => (string) ( $f['file'] ?? '' ), 'sha256' => (string) ( $f['sha256'] ?? '' ), 'motivo' => trim( (string) $ul['consiglio'] . ' ' . (string) $ul['server'] ) ] + self::utente_ev( $user ) );
		}
		$S = [ 'ok' => '✓', 'ko' => '✕', 'avviso' => '!', 'saltato' => '–' ];
		$t = [ '«Trasmetti a Idea Technologies» — ' . RDACADEMY_PRODUCT . ' ' . RDACADEMY_VERSION_LABEL . ' — ' . wp_date( 'd/m/Y H:i' ), 'Esito: ' . ( $ok ? 'trasmesso' : 'non trasmesso' ), '' ];
		foreach ( $P as $i => $x ) $t[] = ( $i + 1 ) . '. ' . $S[ $x['esito'] ] . ' ' . $x['titolo'] . ( $x['server'] !== '' ? ' — ' . $x['server'] : '' ) . ( $x['consiglio'] !== '' ? "\n   → " . $x['consiglio'] : '' );
		return [ 'ok' => $ok, 'passi' => $P, 'testo' => implode( "\n", $t ) ];
	}
}
