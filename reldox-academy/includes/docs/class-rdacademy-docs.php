<?php
/**
 * Rdacademy_Docs — manuale interno nella pagina wp-admin «Documentazione».
 *
 * Copiato da Reldox_Docs, RelDox CRD 3.55.09G (pkg-docs, v3.7.0).
 * Pagina protetta da manage_options, sotto la voce del prodotto. Ogni voce e un
 * file Markdown nella cartella docs-content/ del plugin: aggiungere
 * documentazione = aggiungere un file (REGOLE-FAMIGLIA § 3.10).
 *
 * TAGLI rispetto a RelDox:
 *  - la dipendenza da Reldox_Search (classe che caricava il componente su TUTTE
 *    le pagine del prodotto, admin e app): qui la pagina registra da se la copia
 *    rinominata del componente (assets/search/rdacademy-search.*), e solo per se;
 *  - le stringhe della demo TCF (CRM);
 *  - il filtro `reldox_docs_db_url` (D-pkg-docs-05): puntava l avviso «credenziali
 *    mancanti» a questa pagina, ma nel guscio senza credenziali compare
 *    l installazione guidata e il lotto B non viene nemmeno caricato.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_Docs {

	const VERSION    = '1.0.0';
	const MENU_SLUG  = 'rdacademy-docs';
	const HANDLE_CSS = 'rdacademy-docs';
	const HANDLE_JS  = 'rdacademy-docs';
	const SEARCH     = 'rdacademy-search';

	/** Bootstrap: registra hook menu ed enqueue. */
	public static function init(): void {
		add_action( 'admin_menu',            array( __CLASS__, 'register_menu' ), 12 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/** La voce di menu, sotto la voce di primo livello del lotto A. */
	public static function register_menu(): void {
		add_submenu_page(
			Rdacademy_Admin::SLUG,
			'Documentazione',
			'Documentazione',
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/** CSS e JS della pagina SOLO su quella pagina. */
	public static function enqueue_assets( string $hook ): void {
		// Hook formato "{parent}_page_{submenu_slug}".
		if ( strpos( $hook, '_page_' . self::MENU_SLUG ) === false ) {
			return;
		}

		wp_enqueue_style( self::SEARCH, RDACADEMY_PLUGIN_URL . 'assets/search/rdacademy-search.css', array(), RDACADEMY_VERSION );
		wp_enqueue_script( self::SEARCH, RDACADEMY_PLUGIN_URL . 'assets/search/rdacademy-search.js', array(), RDACADEMY_VERSION, true );
		wp_localize_script( self::SEARCH, 'RdacademySearchI18n', array(
			'placeholder' => 'Cerca…',
			'noResults'   => 'Nessun risultato',
			'reset'       => 'Reimposta',
			'countOf'     => '%1$d di %2$d',
		) );

		wp_enqueue_style( self::HANDLE_CSS, RDACADEMY_PLUGIN_URL . 'assets/docs/docs.css', array(), RDACADEMY_VERSION );
		wp_enqueue_script( self::HANDLE_JS, RDACADEMY_PLUGIN_URL . 'assets/docs/docs.js', array( self::SEARCH ), RDACADEMY_VERSION, true );
		wp_localize_script( self::HANDLE_JS, 'RdacademyDocsI18n', array(
			'tocFilterPlaceholder' => 'Filtra le voci dell\'indice…',
			'tocFilterEmpty'       => 'Nessuna voce corrisponde',
			'tocFilterMissing'     => 'Filtro non disponibile: il componente di ricerca non si e caricato.',
		) );
	}

	/** Le sezioni, lette dalla cartella del plugin. Pubblico: lo usa anche il banco. */
	public static function sections(): array {
		$markdown = new Rdacademy_Markdown();
		$markdown->set_image_url_rewriter( function( $rel_path ) {
			// Percorsi relativi a docs-content/ → URL assoluto del plugin
			$clean = ltrim( $rel_path, './' );
			return RDACADEMY_PLUGIN_URL . 'docs-content/' . $clean;
		} );
		$loader = new Rdacademy_Docs_Loader( RDACADEMY_PLUGIN_DIR . 'docs-content', $markdown );
		return $loader->load_all();
	}

	/** Resa della pagina Documentazione. */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Accesso negato.', 'Errore', array( 'response' => 403 ) );
		}
		$sections = self::sections();
		include RDACADEMY_PLUGIN_DIR . 'includes/docs/views/docs-page.php';
	}
}
