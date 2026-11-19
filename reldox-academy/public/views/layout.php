<?php
/**
 * Layout principale — LA CORNICE (REGOLE-FAMIGLIA § 3.2, SPEC § 3).
 *
 * Copiato da public/views/layout.php di RelDox CRD 3.55.09G e ridotto.
 * TAGLI: viste pinnate del rail e striscia «+N» (Reldox_User_Rail, viste
 * del CRM), hub SFA col suo flyout, gate dei moduli (Reldox_Modules), voci
 * Albero/Preferiti/Approvazioni/Calendario/Portafoglio/Esplora/Gestione viste/
 * Pratica, comandi Modelli e Cestino in topbar, «✉ Posta» e «✦ Claude», gli
 * script AI/posta/richtext/tabella/sezioni in coda.
 * NUOVO: voce «Pannello» (pagina iniziale), filtro del rail per permesso E per
 * tipo di utente, rail Setup col gruppo SISTEMA della SPEC, marchio di
 * prodotto «simbolo + nome» (il lockup di RelDox dice «RelDox», non il prodotto),
 * logo dell azienda con segnaposto, piede da costanti RDACADEMY_PRODUCT / RDACADEMY_VENDOR.
 *
 * INNESTI PER IL LOTTO C (marcati «INNESTO LOTTO-C»): filtri `rdacademy_nav_items`
 * (rail operativo), `rdacademy_setup_personale_items`, `rdacademy_setup_sistema_items`
 * (rail Setup) e `rdacademy_setup_pages`.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$current_user = Rdacademy_Auth::get_current_user();
// FAIL-SOFT (copiato): sessione non risolta → array vuoto, utente non admin, rail vuoto.
if ( ! is_array( $current_user ) ) { $current_user = array(); }
$page         = (string) get_query_var( 'rdacademy_page', 'dashboard' );
if ( $page === '' ) { $page = 'dashboard'; }
$nonce        = wp_create_nonce( 'rdacademy_ajax' );
$ajax_url     = admin_url( 'admin-ajax.php' );
$app_url      = home_url( '/' . RDACADEMY_APP_SLUG );
// RelDox v3.16.195 (S256) — il guscio pubblico non e piu una pagina sola: il
// ramo non autenticato include il template che il router ha gia risolto.
$rx_pagine_pubbliche = array( 'login', 'recupera', 'imposta-password', 'c', 'attiva' );   // 1.4.0B: «attiva» = il codice del foglio stampato   // 0.7.0A: «c» = la pagina del QR, sempre nuda
$is_login     = in_array( $page, $rx_pagine_pubbliche, true ) || empty( $current_user );
$rx_e_accesso = ( $page === 'login' );

// ── pkg5b-finiture-ui di RelDox: branding configurabile (logo + titolo d accesso) ──
// Letti dalla tabella rdacademy_impostazioni con ripiego ai default.
$rx_brand_logo_url    = '';
$rx_logo_barra        = '';
$rx_logo_mob          = '';
$rx_logo_mob_bar      = '';
$rx_brand_login_title = RDACADEMY_PRODUCT;
$rx_brand_p = RDACADEMY_TABLE_PREFIX;
$rx_brand_rows = Rdacademy_DB::get_results(
    "SELECT chiave, valore FROM `{$rx_brand_p}impostazioni`
     WHERE chiave IN ('branding_logo_url','branding_login_title','ragione_sociale','branding_logo_barra_url','branding_logo_mobile_url','branding_logo_mobile_barra_url')"
) ?: [];
$rx_brand_azienda = '';
foreach ( $rx_brand_rows as $rx_brand_r ) {
    if ( $rx_brand_r['chiave'] === 'branding_logo_url'    && ! empty( $rx_brand_r['valore'] ) ) $rx_brand_logo_url    = $rx_brand_r['valore'];
    if ( $rx_brand_r['chiave'] === 'branding_login_title' && ! empty( $rx_brand_r['valore'] ) ) $rx_brand_login_title = $rx_brand_r['valore'];
    if ( $rx_brand_r['chiave'] === 'ragione_sociale'      && ! empty( $rx_brand_r['valore'] ) ) $rx_brand_azienda     = $rx_brand_r['valore'];
    // 0.7.0A — la copia del logo ottimizzata per la barra del telefono (Impostazioni → Branding).
    if ( $rx_brand_r['chiave'] === 'branding_logo_barra_url' && ! empty( $rx_brand_r['valore'] ) ) $rx_logo_barra = (string) $rx_brand_r['valore'];
    // 0.8.0A — il logo per il telefono (quadrato) e la sua copia per la barra.
    if ( $rx_brand_r['chiave'] === 'branding_logo_mobile_url'       && ! empty( $rx_brand_r['valore'] ) ) $rx_logo_mob     = (string) $rx_brand_r['valore'];
    if ( $rx_brand_r['chiave'] === 'branding_logo_mobile_barra_url' && ! empty( $rx_brand_r['valore'] ) ) $rx_logo_mob_bar = (string) $rx_brand_r['valore'];
}
/* 0.8.0A (owner 17:07) — DUE LOGHI, CHI VA DOVE. Se ne manca uno si usa l altro.
 *  - colonna laterale (quadrato 30 px): telefono → computer
 *  - barra del telefono: copia del telefono → telefono → copia del computer → computer
 *  - barra in alto sopra i 768 px: copia del computer → computer → copia del telefono → telefono
 *  - porte d accesso: computer → telefono (0.8.7A: lo decide rdacademy_loghi(), la stessa funzione della testata delle mail) */
$rx_logo_comp  = $rx_brand_logo_url;
$rx_logo_tel_bar  = $rx_logo_mob_bar !== '' ? $rx_logo_mob_bar : ( $rx_logo_mob !== '' ? $rx_logo_mob : ( $rx_logo_barra !== '' ? $rx_logo_barra : $rx_logo_comp ) );
$rx_logo_comp_bar = $rx_logo_barra !== '' ? $rx_logo_barra : ( $rx_logo_comp !== '' ? $rx_logo_comp : ( $rx_logo_mob_bar !== '' ? $rx_logo_mob_bar : $rx_logo_mob ) );
$rx_logo_porta = rdacademy_loghi()['cliente'];   // 0.8.7A (RF-BUG-023) — un posto solo per porte e mail: computer → telefono
// Logo dell AZIENDA in testa al rail (REGOLE § 3.2). ⛔ In RelDox il ripiego era
// il logo di Idea Technologies: nel guscio e un segnaposto neutro (NUOVO).
$rx_logo_is_placeholder = ( $rx_brand_logo_url === '' && $rx_logo_mob === '' );
// 0.1.04A (SPEC-GRAFICA-RELDOX-v5 § 3.3, G16): senza logo caricato il predefinito è quello di Idea Technologies.
$rx_logo_src = $rx_logo_is_placeholder
    ? RDACADEMY_PLUGIN_URL . 'public/img/logo/idea-technologies.png'
    : ( $rx_logo_mob !== '' ? $rx_logo_mob : $rx_brand_logo_url );   // 0.8.0A: nella colonna il quadrato
$rx_logo_alt = $rx_brand_azienda !== '' ? $rx_brand_azienda : ( $rx_logo_is_placeholder ? 'Idea Technologies' : 'Logo dell’azienda' );

// Autorita UNICA su chi e amministratore: la stessa del canale AJAX (§ 3.4).
$rx_is_admin   = ! empty( $current_user ) && Rdacademy_Router::user_can_admin( $current_user );
$rx_tipo       = ( ( $current_user['tipo'] ?? 'interno' ) === 'esterno' ) ? 'esterno' : 'interno';
// 1.13.0B (SV-43, mockup approvato 04/11 11:45) — l Integratore: niente colonna laterale; al computer le sue tre pagine in alto
// (Novità, Documentazione, Preferenze), al telefono il menu ☰ con le sole tre voci ed «Esci». Le pagine le chiude il router.
$rx_integr     = ! empty( $current_user ) && class_exists( 'Rdacademy_Router' ) && Rdacademy_Router::e_integratore( $current_user );

/* ── RelDox v3.11.171 Workspace B2 — BARRA SUPERIORE + MODALITA SETUP ───────
 * SETUP = STATO DEL RAIL calcolato da $page. Le pagine di setup rendono il
 * RAIL SETUP con «← Torna»; tutte le altre rendono il RAIL OPERATIVO.
 * RP-SETUP-CONTEXT: «← Torna» ripristina lo stato operativo esatto (URL
 * completa salvata in sessionStorage 'rxw_ops_return', vedi JS). */
// INNESTO LOTTO-C: una pagina di setup nuova si dichiara qui.
$rxw_setup_pages = (array) apply_filters( 'rdacademy_setup_pages', Rdacademy_Router::SETUP_PAGES );
$rxw_is_setup    = in_array( $page, $rxw_setup_pages, true );
// NUOVO: a chi la pagina e negata (403, accesso-riservato.php) si mostra il rail
// operativo, non un rail Setup vuoto col solo «← Torna».
if ( basename( (string) ( $GLOBALS['rdacademy_template'] ?? '' ) ) === 'accesso-riservato.php' ) { $rxw_is_setup = false; }

/* RAIL OPERATIVO — NUOVO: ogni voce dichiara la pagina e i TIPI che la vedono.
 * Il permesso lo decide la mappa delle pagine del router (Rdacademy_Router::user_passes):
 * una voce che porta a una pagina che l utente non puo aprire NON si mostra
 * (REGOLE § 3.2: «si vedono solo le funzioni che l utente puo usare»). */
// 0.8.2A — LA LINGUA DELLA PAGINA: le porte d accesso nella lingua scelta (bandierine, poi browser); dentro l app la
// persona (operatore, ospite) nella lingua della sua scheda; il pannello di chi amministra resta in italiano
// (decisione chiusa: pannello e referti solo in italiano).
$rx_lin = ( $is_login || ! $rx_is_admin ) ? Rdacademy_Lingua::corrente() : 'it';
// «scegli la password»: la lingua della persona del collegamento (chi la apre non è ancora collegato).
if ( $page === 'imposta-password' ) $rx_lin = Rdacademy_Lingua::del_collegamento() ?? $rx_lin;
$rx_t   = static function ( string $k ) use ( $rx_lin ): string { return Rdacademy_Lingua::t( $k, $rx_lin ); };
$nav_items = [
    'dashboard' => [ 'icon' => 'home', 'label' => $rx_t( 'menu.pannello' ), 'url' => $app_url . '/', 'match' => [ 'dashboard' ], 'tipi' => [ 'interno', 'esterno' ] ],
];
// INNESTO LOTTO-C: le voci operative del prodotto (es. Questionari) entrano qui.
$nav_items = (array) apply_filters( 'rdacademy_nav_items', $nav_items, $app_url );
foreach ( $nav_items as $rx_nk => $rx_ni ) {
    $rx_tipi = (array) ( $rx_ni['tipi'] ?? [ 'interno' ] );
    $rx_pag  = (string) ( $rx_ni['page'] ?? $rx_nk );
    $rx_vede = in_array( $rx_tipo, $rx_tipi, true ) || $rx_is_admin;
    if ( ! $rx_vede || ! Rdacademy_Router::user_passes( $current_user ?: null, Rdacademy_Router::page_level( $rx_pag ) ) ) {
        unset( $nav_items[ $rx_nk ] );
    }
}

// SETUP → zona PERSONALE (tutti gli utenti). Vuota nel guscio, come in RelDox:
// alle Preferenze si arriva dal proprio nome in topbar.
// INNESTO LOTTO-C
$setup_personale_items = (array) apply_filters( 'rdacademy_setup_personale_items', [], $app_url );
// SETUP → gruppo SISTEMA (solo amministratore) — SPEC § 3: Utenti, Tabelle,
// Log accessi, Impostazioni, Area tecnica. TAGLIO: Push viste e Import (CRM).
$setup_sistema_items = [
    'utenti'       => [ 'icon' => 'users',    'label' => 'Utenti',       'url' => $app_url . '/utenti',       'show' => $rx_is_admin ],
    'tabelle'      => [ 'icon' => 'table',    'label' => 'Tabelle',      'url' => $app_url . '/tabelle',      'show' => $rx_is_admin ],
    'log-accessi'  => [ 'icon' => 'log',      'label' => 'Log accessi',  'url' => $app_url . '/log-accessi',  'show' => $rx_is_admin ],
    'impostazioni' => [ 'icon' => 'settings', 'label' => 'Impostazioni', 'url' => $app_url . '/impostazioni', 'show' => $rx_is_admin ],
    'area-tecnica' => [ 'icon' => 'key',      'label' => 'Area tecnica', 'url' => $app_url . '/area-tecnica', 'show' => $rx_is_admin ],
];
// INNESTO LOTTO-C
$setup_sistema_items = (array) apply_filters( 'rdacademy_setup_sistema_items', $setup_sistema_items, $app_url, $rx_is_admin );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( $rx_lin ); ?>">
<head>
    <meta charset="UTF-8">
    <?php /* 1.4.3B (RF-BUG-028) — la guardia della pagina per prima (una copia rigirata da una cache si ricarica col suo indirizzo)
             e la POST che rinnova la sessione appena la pagina è aperta: una GET non scrive mai cookie. */ ?>
    <?php if ( class_exists( 'Rdacademy_Cache_Guardia' ) ) echo Rdacademy_Cache_Guardia::testa_html(); // phpcs:ignore -- composto con wp_json_encode ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php
        echo esc_html( RDACADEMY_PRODUCT );
        if ( $rx_e_accesso )                     echo ' — ' . esc_html( $rx_t( 'login.accedi' ) );
        elseif ( $page === 'recupera' )          echo ' — ' . esc_html( $rx_t( 'rec.titolo' ) );
        elseif ( $page === 'imposta-password' )  echo ' — ' . esc_html( $rx_t( 'ip.nuova_titolo' ) );
        elseif ( $page === 'manuali' || strpos( (string) $page, 'manuale-' ) === 0 ) echo ' — ' . esc_html( $rx_t( 'manuali.titolo' ) );   // 1.9.0B
    ?></title>
    <link rel="icon" href="<?php echo esc_url( RDACADEMY_PLUGIN_URL . 'public/img/logo/reldox-favicon.svg' ); ?>" type="image/svg+xml">
    <?php /* 0.8.9A (SPEC-GALLERIA-CASSA-MENU § 10) — l'app installabile: manifesto, icona del cliente per iPhone, colore della barra. */ ?>
    <?php if ( class_exists( 'Rdacademy_App_Install' ) ) echo Rdacademy_App_Install::testata_html(); // phpcs:ignore -- composto con esc_* ?>
    <?php /* 0.1.04A — SPEC-GRAFICA-RELDOX-v5 § 14: il foglio comune di famiglia per PRIMO, identico a quello del nodo; poi i fogli del prodotto. */ ?>
    <link rel="stylesheet" href="<?php echo esc_url( RDACADEMY_PLUGIN_URL . 'public/css/reldox-grafica.css' ); ?>?v=<?php echo esc_attr( RDACADEMY_VERSION ); ?>">
    <link rel="stylesheet" href="<?php echo esc_url( RDACADEMY_PLUGIN_URL . 'public/css/app.css' ); ?>?v=<?php echo esc_attr( RDACADEMY_VERSION ); ?>">
    <link rel="stylesheet" href="<?php echo esc_url( RDACADEMY_PLUGIN_URL . 'public/css/rdacademy-cornice.css' ); ?>?v=<?php echo esc_attr( RDACADEMY_VERSION ); ?>">

    <style>
        /* RelDox v3.10.1.5.6 (1C.2.k): anti-FOUC per lo spostamento del breadcrumb
           nella topbar. Finche non e stato spostato, lo nascondiamo. */
        body:not(.rx-bc-mounted) .rx-breadcrumb { visibility: hidden; }
        #rx-breadcrumb-slot .rx-breadcrumb { margin: 0; padding: 0; flex-wrap: nowrap; }
    </style>

    <?php
    /**
     * Hook 'rdacademy_enqueue_frontend_assets' — copiato da RelDox (v1.12 §21.2.2):
     * permette ai sotto-componenti del plugin di emettere i propri <link> e
     * <script> nel layout frontend.
     */
    do_action( 'rdacademy_enqueue_frontend_assets' );
    ?>
    <script>
        window.RDACADEMY = {
            ajaxUrl: '<?php echo esc_js( $ajax_url ); ?>',
            nonce:   '<?php echo esc_js( $nonce ); ?>',
            appUrl:  '<?php echo esc_js( $app_url ); ?>',
            version: '<?php echo esc_js( RDACADEMY_VERSION ); ?>',
            page:    '<?php echo esc_js( $page ); ?>',
            user:    <?php
                $rx_js_first = trim( (string) ( $current_user['first_name'] ?? '' ) );
                $rx_js_last  = trim( (string) ( $current_user['last_name']  ?? '' ) );
                $rx_js_email = (string) ( $current_user['email'] ?? '' );
                $rx_js_name  = ( $rx_js_first !== '' && $rx_js_last !== '' )
                    ? ( $rx_js_first . ' ' . $rx_js_last )
                    : $rx_js_email;
                echo $current_user ? wp_json_encode([
                    'id'          => (int) ( $current_user['id'] ?? 0 ),
                    'name'        => $rx_js_name,
                    'email'       => $rx_js_email,
                    'tipo'        => $rx_tipo,
                    'is_backdoor' => ! empty( $current_user['is_backdoor'] ),
                    'is_admin'    => $rx_is_admin,
                ]) : 'null';
            ?>
        };
    </script>
</head>
<body class="reldox-app<?php echo $is_login ? ' reldox-login-page' : ''; ?>">

<?php if ( ! $is_login && $current_user ) : ?>

<div class="rx-layout">

    <?php if ( ! $rx_integr ) : /* 1.13.0B — l Integratore non ha la colonna */ ?>
    <!-- RelDox v3.11.167 Workspace B1 — RAIL (.rxw-*) -->
    <nav class="rail" id="rxwRail" role="navigation" aria-label="Funzioni">

        <?php /* 0.1.04A — S-1 confermata dall owner (19/11/2026 15:46, chat Formazione F016): in cima nessun logo,
                 un blocco vuoto di 48px senza collegamento (SPEC-GRAFICA-RELDOX-v5 § 13); il logo sta nella barra in alto. */ ?>
        <div class="rdacademy-rail-cima" aria-hidden="true"></div>

<?php if ( ! $rxw_is_setup ) : ?>
        <!-- ════════════ RAIL OPERATIVO ════════════ -->
        <?php foreach ( $nav_items as $key => $item ) :
            $is_active = in_array( $page, (array) ( $item['match'] ?? [ $key ] ), true );
        ?>
        <a href="<?php echo esc_url( $item['url'] ); ?>"
           class="it"
           data-here="<?php echo esc_attr( $item['label'] ); ?>"
           aria-label="<?php echo esc_attr( $item['label'] ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
            <?php echo rdacademy_nav_icon( (string) $item['icon'] ); ?>
            <span class="lab"><?php echo esc_html( $item['label'] ); ?></span>
        </a>
        <?php endforeach; ?>

<?php else : ?>
        <!-- ════════════ RAIL SETUP (contesto pieno) ════════════ -->

        <!-- «← Torna»: ripristina lo stato operativo esatto (RP-SETUP-CONTEXT) -->
        <button type="button" class="it rdacademy-it-torna" id="rxwBack"
                aria-label="Torna allo stato operativo" data-here="Torna">
            <?php echo rdacademy_icona( 'indietro' ); ?>
            <span class="lab">Torna</span>
        </button>
        <?php if ( ! empty( $setup_personale_items ) ) : ?>
        <div class="sep" aria-hidden="true"></div>
        <?php foreach ( $setup_personale_items as $key => $item ) :
            if ( empty( $item['show'] ) ) continue;
            $is_active = ( $page === $key );
        ?>
        <a href="<?php echo esc_url( $item['url'] ); ?>"
           class="it"
           data-here="<?php echo esc_attr( $item['label'] ); ?>"
           aria-label="<?php echo esc_attr( $item['label'] ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
            <?php echo rdacademy_nav_icon( (string) $item['icon'] ); ?>
            <span class="lab"><?php echo esc_html( $item['label'] ); ?></span>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if ( $rx_is_admin ) : ?>
        <!-- Gruppo SISTEMA (solo amministratore) -->
        <div class="sep" aria-hidden="true"></div>
        <?php foreach ( $setup_sistema_items as $key => $item ) :
            if ( empty( $item['show'] ) ) continue;
            $is_active = ( $page === $key );
        ?>
        <a href="<?php echo esc_url( $item['url'] ); ?>"
           class="it"
           data-here="<?php echo esc_attr( $item['label'] ); ?>"
           aria-label="<?php echo esc_attr( $item['label'] ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
            <?php echo rdacademy_nav_icon( (string) $item['icon'] ); ?>
            <span class="lab"><?php echo esc_html( $item['label'] ); ?></span>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>

<?php endif; ?>

    </nav>
    <?php endif; ?>

    <?php
    /* 0.6.13A — IL MENU DEL TELEFONO. Le stesse voci della colonna laterale, tutte insieme (funzioni, personale,
       sistema), più «Il mio profilo» ed «Esci». Si vedono solo le voci che la persona può aprire: gli elenchi
       sono quelli già filtrati qui sopra ($nav_items per tipo e permesso, sistema solo all amministratore). */
    // 1.9.0B — la voce dei manuali sta nella colonna del computer; al telefono la mette il gruppo «Tu» qui sotto (niente doppioni).
    $rdacademy_mgruppi = array( array( $rx_t( 'menu.funzioni' ), array_filter( $nav_items, static function ( $i ) { return empty( $i['solo_colonna'] ); } ) ) );
    $rdacademy_mpers   = array_filter( (array) $setup_personale_items, static function ( $i ) { return ! empty( $i['show'] ); } );
    if ( $rdacademy_mpers ) $rdacademy_mgruppi[] = array( $rx_t( 'menu.personale' ), $rdacademy_mpers );
    if ( $rx_is_admin ) {
        $rdacademy_msis = array_filter( (array) $setup_sistema_items, static function ( $i ) { return ! empty( $i['show'] ); } );
        if ( $rdacademy_msis ) $rdacademy_mgruppi[] = array( 'Sistema', $rdacademy_msis );
    }
    if ( $rx_integr ) {   // 1.13.0B — le sole pagine dell Integratore
        $rdacademy_mgruppi = array( array( 'Pagine', array(
            'novita'         => array( 'icon' => 'home', 'label' => 'Novità', 'url' => $app_url . '/novita', 'match' => array( 'novita' ) ),
            'documentazione' => array( 'icon' => 'manuali', 'label' => 'Documentazione', 'url' => $app_url . '/novita#documentazione', 'match' => array( 'manuale-amministratore' ) ),
        ) ) );
    }
    $rdacademy_mio = rdacademy_identita_utente( $current_user );
    ?>
    <div class="rdacademy-mdrawer" id="rdacademyMDrawer" hidden>
        <div class="rdacademy-mdrawer-velo" data-chiudi></div>
        <div class="rdacademy-mdrawer-pannello" role="dialog" aria-modal="true" aria-label="Menu">
            <div class="rdacademy-mdrawer-testa">
                <div class="rdacademy-mdrawer-chi"><b><?php echo esc_html( $rdacademy_mio['nome'] ); ?></b><span><?php echo esc_html( $rdacademy_mio['email'] ); ?></span></div>
                <button type="button" class="rdacademy-mdrawer-x" data-chiudi aria-label="<?php echo esc_attr( $rx_t( 'menu.chiudi' ) ); ?>">
                    <?php echo rdacademy_icona( 'chiudi' ); ?>
                </button>
            </div>
            <nav class="rdacademy-mdrawer-voci" aria-label="Funzioni">
                <?php foreach ( $rdacademy_mgruppi as $rdacademy_g ) : ?>
                <div class="rdacademy-mdrawer-gruppo"><?php echo esc_html( $rdacademy_g[0] ); ?></div>
                <?php foreach ( $rdacademy_g[1] as $rdacademy_k => $rdacademy_i ) :
                    $rdacademy_att = in_array( $page, (array) ( $rdacademy_i['match'] ?? array( $rdacademy_k ) ), true );
                ?>
                <a href="<?php echo esc_url( $rdacademy_i['url'] ); ?>" class="rdacademy-mvoce<?php echo $rdacademy_att ? ' attiva' : ''; ?>"<?php echo $rdacademy_att ? ' aria-current="page"' : ''; ?>>
                    <?php echo rdacademy_nav_icon( (string) $rdacademy_i['icon'] ); ?><span><?php echo esc_html( $rdacademy_i['label'] ); ?></span>
                </a>
                <?php endforeach; endforeach; ?>
                <div class="rdacademy-mdrawer-gruppo"><?php echo esc_html( $rx_t( 'menu.tu' ) ); ?></div>
                <?php /* 1.9.0B (SPEC-MANUALI-IN-LINEA § 4.2) — «Manuale» (o «Manuali» per la Reception) nel gruppo «Tu», sopra il profilo.
                   L amministratore la trova nel gruppo Sistema. */
                if ( ! $rx_is_admin && ! $rx_integr && class_exists( 'Rdacademy_Manuali' ) ) :
                    $rdacademy_mv = Rdacademy_Manuali::voce( $current_user, $app_url );
                    $rdacademy_ma = in_array( $page, $rdacademy_mv['match'], true ); ?>
                <a href="<?php echo esc_url( $rdacademy_mv['url'] ); ?>" class="rdacademy-mvoce<?php echo $rdacademy_ma ? ' attiva' : ''; ?>"<?php echo $rdacademy_ma ? ' aria-current="page"' : ''; ?>>
                    <?php echo rdacademy_nav_icon( 'manuali' ); ?><span><?php echo esc_html( $rdacademy_mv['label'] ); ?></span>
                </a>
                <?php endif; ?>
                <a href="<?php echo esc_url( $app_url . '/preferenze' ); ?>" class="rdacademy-mvoce<?php echo $page === 'preferenze' ? ' attiva' : ''; ?>">
                    <?php echo rdacademy_icona( 'persona' ); ?><span><?php echo esc_html( $rx_t( 'menu.profilo' ) ); ?></span>
                </a>
                <button type="button" class="rdacademy-mvoce rdacademy-mesci" id="rdacademyMEsci">
                    <?php echo rdacademy_nav_icon( 'logout' ); ?><span><?php echo esc_html( $rx_t( 'menu.esci' ) ); ?></span>
                </button>
            </nav>
        </div>
    </div>
    <script>
    /* 0.6.13A — apre e chiude il menu del telefono: ☰, il velo, la ✕, il tasto Esc, una voce scelta. «Esci» usa
       lo stesso comando della barra (#rx-logout, initLogout di app.js): una sola uscita. */
    (function () {
        function init() {
            var b = document.getElementById('rdacademyMBurger'), d = document.getElementById('rdacademyMDrawer');
            if (!b || !d) return;
            function apri() { d.hidden = false; b.setAttribute('aria-expanded', 'true'); document.body.classList.add('rdacademy-mmenu-aperto');
                var p = d.querySelector('.rdacademy-mvoce'); if (p) p.focus(); }
            function chiudi() { d.hidden = true; b.setAttribute('aria-expanded', 'false'); document.body.classList.remove('rdacademy-mmenu-aperto'); }
            b.addEventListener('click', function () { if (d.hidden) apri(); else chiudi(); });
            d.addEventListener('click', function (e) {
                if (e.target.closest('[data-chiudi]')) { chiudi(); b.focus(); return; }
                if (e.target.closest('a.rdacademy-mvoce')) chiudi();
            });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !d.hidden) { chiudi(); b.focus(); } });
            var esci = document.getElementById('rdacademyMEsci'), lo = document.getElementById('rx-logout');
            if (esci && lo) esci.addEventListener('click', function () { chiudi(); lo.click(); });
            /* Tornando al computer (finestra allargata) il menu non resta aperto sopra la colonna. */
            window.addEventListener('resize', function () { if (window.innerWidth >= 768 && !d.hidden) chiudi(); });
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    })();
    </script>

    <script>
    /* RelDox v3.11.172 Workspace B2 fix1 — barra superiore + SETUP (copiato).
       L ingresso (ingranaggio) salva l URL operativa corrente; «← Torna» la ripristina
       ESATTA (RP-SETUP-CONTEXT). Il wiring gira su DOMContentLoaded perche
       #rxwGear vive nella topbar, emessa DOPO questo script. Tooltip label a
       comparsa su hover (rail compatto), agganciato al <body>. */
    (function(){
        function init(){
            var KEY = 'rxw_ops_return';
            var app = (window.RDACADEMY && window.RDACADEMY.appUrl) ? window.RDACADEMY.appUrl : '/';
            function appHome(){ return app.replace(/\/+$/, '') + '/'; }

            /* RelDox 3.17.02 — DUE COMANDI DIVERSI: l ingranaggio e il SETUP
               dell applicativo, il nome e il profilo personale. In comune hanno
               solo il «dove tornare»; la destinazione la porta il chiamante. */
            function entraInSetup(dove){
                try { sessionStorage.setItem(KEY, window.location.href); } catch(e){}
                window.location.href = appHome() + dove;
            }

            /* ⚙ → /academy/impostazioni (l elemento esiste nel DOM solo per gli admin). */
            var gear = document.getElementById('rxwGear');
            if (gear){ gear.addEventListener('click', function(){ entraInSetup('impostazioni'); }); }

            /* Il nome utente → Preferenze personali, per QUALUNQUE utente. */
            var uprof = document.getElementById('rxUserProfile');
            if (uprof){ uprof.addEventListener('click', function(){ entraInSetup('preferenze'); }); }

            // «← Torna» (modalita setup) → ripristina lo stato operativo salvato.
            var back = document.getElementById('rxwBack');
            if (back){
                back.addEventListener('click', function(){
                    var url = null;
                    try { url = sessionStorage.getItem(KEY); } catch(e){}
                    window.location.href = (url && url.length) ? url : appHome();
                });
            }

            // Tooltip label su hover — agganciato al body (esce dal clip del rail).
            var tip = null;
            function showTip(el){
                var lab = el.getAttribute('data-here');
                if (!lab){ var s = el.querySelector('.lab'); lab = s ? s.textContent : ''; }
                if (!lab) return;
                if (!tip){ tip = document.createElement('div'); tip.className = 'tip'; tip.setAttribute('role', 'tooltip'); document.body.appendChild(tip); }
                tip.textContent = lab;
                var r = el.getBoundingClientRect();
                tip.style.top  = Math.round(r.top + r.height / 2) + 'px';
                tip.style.left = Math.round(r.right + 8) + 'px';
                tip.classList.add('show');
            }
            function hideTip(){ if (tip) tip.classList.remove('show'); }
            document.querySelectorAll('.rail .it').forEach(function(el){
                el.addEventListener('mouseenter', function(){ if (window.innerWidth > 700) showTip(el); });
                el.addEventListener('focus', function(){ if (window.innerWidth > 700) showTip(el); });
                el.addEventListener('mouseleave', hideTip);
                el.addEventListener('blur', hideTip);
                el.addEventListener('click', hideTip);
            });
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
        else init();
    })();
    </script>

    <!-- Main -->
    <main class="rx-main" id="rx-main" role="main">

        <div class="rx-topbar">
            <div class="rx-topbar-left">
                <?php /* 0.6.13A — CORNICE AL TELEFONO (owner 06/10 13:45): sotto i 768 px la colonna laterale sparisce;
                         a sinistra il pulsante del menu apre il menu con le voci che la persona può vedere. */ ?>
                <button type="button" class="rdacademy-mburger" id="rdacademyMBurger" aria-controls="rdacademyMDrawer" aria-expanded="false" aria-label="<?php echo esc_attr( $rx_t( 'menu.apri' ) ); ?>">
                    <?php echo rdacademy_icona( 'griglia' ); ?>
                </button>
                <!-- RelDox v3.16.187 BUG-74 variante A: il marchio di PRODOTTO in testa
                     alla topbar, PRIMA del breadcrumb. Il logo del CLIENTE resta sul
                     rail: il marchio di prodotto si AGGIUNGE, non sostituisce.
                     NUOVO: simbolo del kit + nome di prodotto in teal (REGOLE § 4). -->
                <a href="<?php echo esc_url( $app_url ); ?>/" class="rx-topbar-brand" aria-label="<?php echo esc_attr( RDACADEMY_PRODUCT ); ?> — vai al Pannello">
                    <img class="rdacademy-brand-sole"
                         src="<?php echo esc_url( RDACADEMY_PLUGIN_URL . 'public/img/logo/reldox-symbol-color.svg' ); ?>?v=<?php echo esc_attr( RDACADEMY_VERSION ); ?>"
                         alt="" width="30" height="30" decoding="async">
                    <span class="rdacademy-brand-nome"><?php echo esc_html( RDACADEMY_PRODUCT ); ?></span>
                </a>
                <?php /* 0.8.0A (owner 17:07) — sopra i 768 px il logo del cliente per tablet e computer, accanto al marchio. */ ?>
                <?php /* 0.8.1A (RF-BUG-021): sempre nella pagina, nascosto se non c'è logo, così Impostazioni lo aggiorna senza ricaricare. */ ?>
                <span class="rx-topbar-brand-sep rdacademy-dsep" aria-hidden="true"></span>
                <img class="rdacademy-dcliente" src="<?php echo esc_url( $rx_logo_is_placeholder ? $rx_logo_src : $rx_logo_comp_bar ); ?>" alt="<?php echo esc_attr( $rx_logo_alt ); ?>" height="32" decoding="async">
                <span class="rx-topbar-brand-sep" aria-hidden="true"></span>
                <div id="rx-breadcrumb-slot"></div>
            </div>
            <div class="rx-topbar-right">
                <?php if ( ! $rxw_is_setup && $rx_is_admin ) : ?>
                <!-- RelDox 3.17.00 BUG-86 — l ingranaggio solo all amministratore. -->
                <button type="button" class="rxw-gear" id="rxwGear" title="Impostazioni" aria-label="Impostazioni">
                    <?php echo rdacademy_nav_icon( 'settings' ); ?>
                </button>
                <?php endif; ?>
                <?php /* RelDox 3.39.05 (S467) — Esci: UNO solo, in Setup e fuori. L id #rx-logout aggancia initLogout() di app.js.
                   NUOVO: il separatore separa qualcosa — senza ingranaggio (Setup, non admin) non c e. */ ?>
                <?php if ( ! $rxw_is_setup && $rx_is_admin ) : ?><span class="rxw-tbsep" aria-hidden="true"></span><?php endif; ?>
                <?php if ( $rx_integr ) : /* 1.13.0B — le tre pagine dell Integratore, al computer e al tablet */ ?>
                <nav class="rdacademy-ilink" aria-label="Pagine">
                    <a href="<?php echo esc_url( $app_url . '/novita' ); ?>"<?php echo $page === 'novita' ? ' class="att" aria-current="page"' : ''; ?>>Novità</a>
                    <a href="<?php echo esc_url( $app_url . '/novita#documentazione' ); ?>"<?php echo $page === 'manuale-amministratore' ? ' class="att" aria-current="page"' : ''; ?>>Documentazione</a>
                    <a href="<?php echo esc_url( $app_url . '/preferenze' ); ?>"<?php echo $page === 'preferenze' ? ' class="att" aria-current="page"' : ''; ?>>Preferenze</a>
                </nav>
                <style>.rdacademy-ilink{display:flex;gap:4px;align-items:center;margin-right:8px}.rdacademy-ilink a{font-size:15px;font-weight:600;color:#1F2937;text-decoration:none;padding:8px 12px;border-radius:8px;min-height:40px;display:inline-flex;align-items:center}.rdacademy-ilink a:hover{background:#F3F4F6}.rdacademy-ilink a.att{background:#E6F2F5;color:#0F5E6E}@media (max-width:767.98px){.rdacademy-ilink{display:none}}</style>
                <?php endif; ?>
                <button type="button" class="rxw-gear rxw-tbexit" id="rx-logout" title="<?php echo esc_attr( $rx_t( 'menu.esci' ) ); ?>" aria-label="<?php echo esc_attr( $rx_t( 'menu.esci' ) ); ?>">
                    <?php echo rdacademy_nav_icon( 'logout' ); ?>
                </button>
                <div class="rx-user-badge">
                    <?php
                    /* RelDox 3.17.12 (S275) BUG-92 — la regola dell identita e una porta:
                       rdacademy_identita_utente() di helpers.php (RP-UNA-MANO-SOLA-SUL-COMANDO). */
                    $rx_io            = rdacademy_identita_utente( $current_user );
                    $rx_email         = $rx_io['email'];
                    $rx_visible_name  = $rx_io['nome'];
                    $rx_avatar_url    = $rx_io['avatar_url'];
                    $rx_initials      = $rx_io['iniziali'];
                    ?>
                    <div class="rx-avatar" aria-hidden="true" style="overflow:hidden">
                        <?php if ( $rx_avatar_url !== '' ) : ?>
                            <img src="<?php echo esc_url( $rx_avatar_url ); ?>"
                                 alt="<?php echo esc_attr( $rx_visible_name ); ?>"
                                 style="width:100%;height:100%;object-fit:cover;border-radius:50%">
                        <?php else : ?>
                            <?php echo esc_html( $rx_initials ); ?>
                        <?php endif; ?>
                    </div>
                    <?php /* RelDox 3.17.00 BUG-77 — il nome utente e il comando che porta alle Preferenze. */ ?>
                    <button type="button"
                            class="rx-user-name rx-user-link"
                            id="rxUserProfile"
                            title="<?php echo esc_attr( $rx_t( 'menu.profilo' ) ); ?> · <?php echo esc_attr( $rx_email ); ?>"
                            aria-label="<?php echo esc_attr( $rx_t( 'menu.profilo' ) ); ?>"><?php echo esc_html( $rx_visible_name ); ?></button>
                    <?php if ( ! empty( $current_user['is_backdoor'] ) ) : ?>
                        <span class="rx-role-chip rx-role-backdoor" title="Account riservato sviluppo">Service Account</span>
                    <?php else :
                        // RelDox v3.10.38 — la pillola mostra il ruolo per PRIVILEGIO: prima
                        // un ruolo admin (permissions.all), altrimenti il primo ruolo.
                        $rx_pill_role = null;
                        foreach ( (array) ( $current_user['roles'] ?? [] ) as $r ) {
                            if ( empty( $r['is_backdoor'] ) ) {
                                $perm = json_decode( (string) ( $r['permissions'] ?? '' ), true );
                                $is_all = is_array( $perm ) && array_key_exists( 'all', $perm )
                                          && ( $perm['all'] === true || $perm['all'] === 1 || $perm['all'] === '1'
                                               || ( is_string( $perm['all'] ) && strtolower( $perm['all'] ) === 'true' ) );
                                if ( $is_all ) { $rx_pill_role = $r; break; }
                            }
                        }
                        if ( $rx_pill_role === null ) {
                            foreach ( (array) ( $current_user['roles'] ?? [] ) as $r ) {
                                if ( empty( $r['is_backdoor'] ) ) { $rx_pill_role = $r; break; }
                            }
                        }
                        if ( $rx_pill_role ) : ?>
                            <span class="rx-role-chip" style="background:<?php echo esc_attr( $rx_pill_role['color'] ); ?>22;color:<?php echo esc_attr( $rx_pill_role['color'] ); ?>"><?php echo esc_html( $rx_pill_role['name'] ); ?></span>
                        <?php elseif ( $rx_integr ) : /* 1.13.0B */ ?>
                            <span class="rx-role-chip" style="background:#E6F2F5;color:#0F5E6E">Integratore</span>
                        <?php elseif ( $rx_tipo === 'esterno' ) : ?>
                            <span class="rx-role-chip rdacademy-chip-esterno">Esterno</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php /* 0.7.0A (owner 14:56) — al telefono il logo del cliente sta DI FIANCO al marchio di RelDoxAcademy
                         (prima, 0.6.13A, era sotto la barra): la copia ottimizzata per la barra, se c'è. */ ?>
                <img class="rdacademy-mcliente" src="<?php echo esc_url( $rx_logo_is_placeholder ? $rx_logo_src : $rx_logo_tel_bar ); ?>" alt="<?php echo esc_attr( $rx_logo_alt ); ?>" height="32" decoding="async">
                <span class="rdacademy-msep" aria-hidden="true"></span>
                <?php /* 0.6.13A — al telefono, a destra, il marchio di RelDoxAcademy. */ ?>
                <a href="<?php echo esc_url( $app_url ); ?>/" class="rdacademy-mbrand" aria-label="<?php echo esc_attr( RDACADEMY_PRODUCT ); ?> — vai al Pannello">
                    <img src="<?php echo esc_url( RDACADEMY_PLUGIN_URL . 'public/img/logo/reldox-symbol-color.svg' ); ?>?v=<?php echo esc_attr( RDACADEMY_VERSION ); ?>" alt="" width="28" height="28" decoding="async">
                    <span><?php echo esc_html( RDACADEMY_PRODUCT ); ?></span>
                </a>
            </div>
        </div>

        <div class="rx-page-content">
            <?php
            $tpl = RDACADEMY_PLUGIN_DIR . 'public/views/' . basename( (string) ( $GLOBALS['rdacademy_template'] ?? 'dashboard.php' ) );
            if ( file_exists( $tpl ) ) {
                include $tpl;
            } else {
                echo '<div class="rx-empty-state"><h2>Pagina non trovata</h2></div>';
            }
            ?>
        </div>

        <!-- Piede applicativo: firma del produttore e versione, solo qui (REGOLE § 3.2). -->
        <footer class="rx-app-footer" role="contentinfo">
            <a class="rx-app-footer-firma" href="<?php echo esc_url( RDACADEMY_VENDOR_URL ); ?>" target="_blank" rel="noopener"><?php echo esc_html( RDACADEMY_PRODUCT ); ?> &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> &mdash; un prodotto di <?php echo esc_html( RDACADEMY_VENDOR ); ?></a>
            <span class="rx-app-footer-sep" aria-hidden="true">·</span>
            <span class="rx-app-footer-ver">v<?php echo esc_html( RDACADEMY_VERSION_LABEL ); ?></span>
        </footer>

    </main>
</div>

<?php else : ?>
<?php
// RelDox v3.16.195 (S256) — si include il template RISOLTO DAL ROUTER, con il
// ripiego storico su login.php.
$rx_tpl_pub = basename( (string) ( $GLOBALS['rdacademy_template'] ?? 'login.php' ) );
$tpl = RDACADEMY_PLUGIN_DIR . 'public/views/' . $rx_tpl_pub;
if ( ! file_exists( $tpl ) ) $tpl = RDACADEMY_PLUGIN_DIR . 'public/views/login.php';
if ( file_exists( $tpl ) ) include $tpl;
?>
<?php endif; ?>

<script>
/* RelDox v3.10.1.5.6 (1C.2.k) — Breadcrumb DOM move pre-paint: il primo
   .rx-breadcrumb emesso da una vista entra in #rx-breadcrumb-slot della topbar. */
(function(){
    var slot = document.getElementById('rx-breadcrumb-slot');
    if (slot) {
        var bc = document.querySelector('.rx-main .rx-breadcrumb');
        if (bc && bc.parentNode !== slot) {
            slot.appendChild(bc);
        }
    }
    document.body.classList.add('rx-bc-mounted');
})();
</script>
<script src="<?php echo esc_url( RDACADEMY_PLUGIN_URL . 'public/js/app.js' ); ?>?v=<?php echo esc_attr( RDACADEMY_VERSION ); ?>"></script>
</body>
</html>
