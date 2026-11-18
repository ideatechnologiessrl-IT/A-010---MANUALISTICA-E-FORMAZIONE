<?php
/**
 * La barra «Fonte del set di partenza» delle Tabelle Funzionali, UNA copia per tutte le viste che la usano.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
/* UNA funzione per tutte le viste. */
if ( ! function_exists( 'rdacademy_tf_barra' ) ) :
function rdacademy_tf_barra( string $tab ): void {
	foreach ( Rdacademy_TF_Registry::per_tab( $tab ) as $v ) {
		$nat  = (string) $v['natura'];
		$rig  = (bool) $v['rigenerabile'];
		$tbl  = (string) $v['tabella'];
		$note = (string) $v['nota'];
		?>
		<div class="rxtfa-bar" data-tabella="<?php echo esc_attr( $tbl ); ?>" data-etichetta="<?php echo esc_attr( (string) $v['etichetta'] ); ?>">
			<div class="rxtfa-src">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
				<span class="rxtfa-src-txt">
					<span class="rxtfa-src-lb">Fonte del set di partenza</span>
					<?php echo esc_html( (string) $v['fonte'] ); ?>
					<span class="rxtfa-nat rxtfa-nat-<?php echo esc_attr( $nat ); ?>"><?php echo esc_html( (string) $v['natura_label'] ); ?></span>
					<?php if ( '' !== $note ) : ?><span class="rxtfa-note"><?php echo esc_html( $note ); ?></span><?php endif; ?>
					<?php if ( ! $rig ) : ?><span class="rxtfa-note"><strong>Nessun generatore disponibile:</strong> una volta azzerata, questa tabella si ricarica a mano.</span><?php endif; ?>
				</span>
			</div>
			<div class="rxtfa-acts">
				<?php if ( $rig ) : ?>
				<button type="button" class="rxtfa-btn" data-rxtfa-act="genera" title="Rilegge la fonte e ricrea le voci mancanti. Non cancella e non sovrascrive le personalizzazioni.">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7"/><polyline points="21 3 21 9 15 9"/></svg>
					Genera dalla fonte
				</button>
				<?php endif; ?>
				<button type="button" class="rxtfa-btn rxtfa-btn-danger" data-rxtfa-act="wipe" title="Rimuove TUTTE le voci di questa tabella.">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
					Cancella tutto
				</button>
			</div>
			<div class="rxtfa-msg" role="status" aria-live="polite" hidden></div>
		</div>
		<?php
	}
}
endif;
