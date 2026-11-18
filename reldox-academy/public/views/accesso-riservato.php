<?php
/**
 * La pagina che il router mostra quando la mappa dei permessi delle pagine non ammette l utente collegato.
 * Stessa grammatica di coming-soon.php (vista CONTENT-ONLY dentro layout.php). Stato HTTP 403.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="rx-empty-state rdacademy-stato-vuoto" role="alert">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48" aria-hidden="true">
        <rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/>
    </svg>
    <h2>Pagina riservata</h2>
    <p>Questa pagina non è disponibile per il tuo profilo. Se pensi che ti serva, chiedi all’amministratore del sistema.</p>
    <a href="<?php echo esc_url( home_url( '/' . RDACADEMY_APP_SLUG . '/' ) ); ?>" class="rx-btn rx-btn-secondary">← Torna al Pannello</a>
</div>
