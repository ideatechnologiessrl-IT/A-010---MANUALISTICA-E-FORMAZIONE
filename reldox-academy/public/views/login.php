<?php
/**
 * Vista login: la porta dell applicazione. Titolo e logo dell azienda da Impostazioni › Branding; il marchio di
 * prodotto con rdacademy_marchio_html (nome da RDACADEMY_PRODUCT). Se l azienda non ha ancora un logo, il riquadro
 * non compare.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
// $rx_t e $rx_lin arrivano da layout.php; ripiego difensivo se la vista si include da sola.
if ( ! isset( $rx_t ) ) { $rx_t = static function ( string $k ): string { return Rdacademy_Lingua::t( $k ); }; }

// Le variabili $rx_brand_login_title e $rx_logo_src sono valorizzate da
// layout.php prima dell'inclusione di questa vista. Fallback difensivo:
$login_title_local = $rx_brand_login_title ?? RDACADEMY_PRODUCT;
$logo_src_local    = ( isset( $rx_logo_is_placeholder ) && ! $rx_logo_is_placeholder ) ? (string) $rx_logo_src : '';
?>
<div class="rx-login-wrap">
    <div class="rx-login-card">

        <div class="rx-login-header">
            <?php /* Qui, e solo qui, il marchio intero col payoff, sopra il logo del cliente: il marchio di prodotto si aggiunge,
                     non sostituisce quello del cliente. Un simbolo solo: il logo dell installazione sta dentro il marchio. */ ?>
            <?php echo rdacademy_marchio_html( rdacademy_logo_porta( $rx_logo_porta ?? '', $rx_logo_is_placeholder ?? true ) ); ?>
            <?php /* Niente titolo sotto il marchio: il nome del prodotto lo dice già il marchio. */ ?>
            <p class="rx-login-sub"><?php echo esc_html( $rx_t( 'login.sub' ) ); ?></p>
            <?php /* La porta nella lingua della persona: parte dalla lingua del browser e ricorda la scelta. */ ?>
            <?php echo Rdacademy_Lingua::bandierine(); ?>
        </div>

        <?php /* Se si arriva qui perché la sessione è finita, lo si dice (anche dal rinvio: una GET non scrive cookie). */ ?>
        <?php if ( ! empty( $_COOKIE[ Rdacademy_Auth::COOKIE_SCADUTA ] ) || ! empty( $_GET['scaduta'] ) ) : ?>
        <div class="rx-alert rx-alert-info" id="rx-login-scaduta" role="status"><?php echo esc_html( $rx_t( 'login.scaduta' ) ); ?></div>
        <?php endif; ?>
        <div id="rx-login-error" class="rx-alert rx-alert-error" style="display:none" role="alert"></div>

        <form id="rx-login-form" class="rx-login-form" novalidate data-vuoti="<?php echo esc_attr( $rx_t( 'login.vuoti' ) ); ?>" data-rete="<?php echo esc_attr( $rx_t( 'login.rete' ) ); ?>" data-non-valide="<?php echo esc_attr( $rx_t( 'login.non_valide' ) ); ?>">

            <div class="rx-field-wrap">
                <label class="rx-label" for="rx-email"><?php echo esc_html( $rx_t( 'login.ident' ) ); ?></label>
                <input type="text"
                       id="rx-email"
                       name="email"
                       class="rx-input"
                       placeholder="<?php echo esc_attr( $rx_t( 'login.ident_ph' ) ); ?>"
                       autocomplete="username"
                       required
                       aria-required="true">
            </div>

            <div class="rx-field-wrap">
                <label class="rx-label" for="rx-password">
                    <?php echo esc_html( $rx_t( 'login.password' ) ); ?>
                    <button type="button" class="rx-link rx-pwd-toggle" data-target="rx-password" aria-label="<?php echo esc_attr( $rx_t( 'login.mostra_aria' ) ); ?>" data-mostra="<?php echo esc_attr( $rx_t( 'login.mostra' ) ); ?>" data-nascondi="<?php echo esc_attr( $rx_t( 'login.nascondi' ) ); ?>"><?php echo esc_html( $rx_t( 'login.mostra' ) ); ?></button>
                </label>
                <input type="password"
                       id="rx-password"
                       name="password"
                       class="rx-input"
                       placeholder="••••••••"
                       autocomplete="current-password"
                       required
                       aria-required="true">
            </div>

            <button type="submit" class="rx-btn rx-btn-primary rx-btn-full" id="rx-login-submit">
                <span class="rx-btn-text"><?php echo esc_html( $rx_t( 'login.accedi' ) ); ?></span>
                <span class="rx-btn-loader" style="display:none" aria-hidden="true">
                    <svg class="rx-spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg>
                </span>
            </button>

        </form>

        <?php /* Il piede: prima il recupero della password; la frase sotto resta, perché se il recupero non basta
                  l amministratore è la strada. */ ?>
        <p class="rx-login-footer">
            <a class="rx-login-recover" href="<?php echo esc_url( home_url( '/' . RDACADEMY_APP_SLUG . '/recupera' ) ); ?>"><?php echo esc_html( $rx_t( 'login.dimenticata' ) ); ?></a><br>
            <span class="rx-login-footer-alt"><?php echo esc_html( $rx_t( 'login.problemi' ) ); ?></span>
        </p>

    </div>
</div>
