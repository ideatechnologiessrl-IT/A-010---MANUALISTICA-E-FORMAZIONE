<?php
/**
 * Vista Coming Soon — sezione in sviluppo
 * Etichette ridotte alle pagine del guscio.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$page    = get_query_var( 'rdacademy_page' );
$labels  = [
    'impostazioni' => 'Impostazioni',
    'utenti'       => 'Utenti',
    'tabelle'      => 'Tabelle funzionali',
    'area-tecnica' => 'Area tecnica',
    'preferenze'   => 'Preferenze',
];
$label = $labels[ $page ] ?? ucfirst( str_replace( '-', ' ', (string) $page ) );
?>
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:60vh;gap:16px;color:var(--rx-text-muted);text-align:center">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48" style="color:var(--rx-border-mid)">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    <div>
        <div style="font-size:18px;font-weight:600;color:var(--rx-text);margin-bottom:6px"><?php echo esc_html($label); ?></div>
        <div style="font-size:13px">Sezione in sviluppo — disponibile nel prossimo aggiornamento.</div>
    </div>
    <a href="<?php echo esc_url( home_url('/' . RDACADEMY_APP_SLUG . '/') ); ?>" class="rx-btn rx-btn-secondary">← Torna al Pannello</a>
</div>
