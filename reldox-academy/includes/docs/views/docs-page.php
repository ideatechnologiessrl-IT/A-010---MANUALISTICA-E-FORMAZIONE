<?php
/**
 * Modello della pagina Documentazione (admin).
 *
 * Copiato da includes/docs/views/docs-page.php, RelDox CRD 3.55.09G.
 * Classi reldox-docs-* -> rdacademy-docs-*; titolo e piede dal nome del prodotto;
 * TAGLIO: l emoji del titolo (REGOLE § 4: icone SVG a tratto, non emoji).
 *
 * Variabili attese in scope (da Rdacademy_Docs::render_page):
 *   - $sections array<int, array{filename, slug, title, frontmatter, body_html}>
 *
 * Semantica HTML5: <aside> per l indice, <main> per il contenuto, <article> per sezione.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $sections ) || ! is_array( $sections ) ) {
	$sections = array();
}
?>
<div class="wrap rdacademy-docs"><hr class="wp-header-end"><?php /* 0.8.10A (RF-BUG-026): gli avvisi di WordPress sopra la testata, non dentro */ ?>

	<a href="#rdacademy-docs-main" class="rdacademy-docs-skip-link">Vai al contenuto</a>

	<h1 style="display:flex;align-items:center;gap:10px">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2z"/><path d="M22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7z"/></svg>
		Documentazione <?php echo esc_html( RDACADEMY_PRODUCT ); ?>
	</h1>

	<?php if ( empty( $sections ) ) : ?>
		<div class="notice notice-warning">
			<p>Nessun contenuto di documentazione disponibile. La cartella <code>docs-content/</code> del plugin è vuota o non leggibile.</p>
		</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div class="rdacademy-docs-layout">

		<aside class="rdacademy-docs-toc" aria-label="Indice della documentazione">

			<details class="rdacademy-docs-toc-accordion" open>
				<summary>Indice <span style="font-weight:400;color:#646970;font-size:12px">(<?php echo count( $sections ); ?>)</span></summary>

				<div class="rdacademy-docs-toc-search" id="rdacademy-docs-toc-search-wrap">
					<?php /* Segnaposto: il componente RdacademySearch.Input monta qui davanti il suo campo */ ?>
				</div>

				<nav>
					<ul class="rdacademy-docs-toc-list" id="rdacademy-docs-toc-list">
						<?php foreach ( $sections as $i => $section ) :
							$is_first = ( $i === 0 ); ?>
							<li class="rdacademy-docs-toc-item">
								<a href="#<?php echo esc_attr( $section['slug'] ); ?>"
								   class="rdacademy-docs-toc-link"
								   data-target="<?php echo esc_attr( $section['slug'] ); ?>"
								   <?php if ( $is_first ) : ?>aria-current="page"<?php endif; ?>><?php echo esc_html( $section['title'] ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			</details>

		</aside>

		<main class="rdacademy-docs-content" id="rdacademy-docs-main" tabindex="-1">

			<?php foreach ( $sections as $i => $section ) :
				$is_first = ( $i === 0 ); ?>
				<article class="rdacademy-docs-section <?php echo $is_first ? 'is-active' : ''; ?>"
				         id="<?php echo esc_attr( $section['slug'] ); ?>-section"
				         data-section-slug="<?php echo esc_attr( $section['slug'] ); ?>"
				         <?php if ( ! $is_first ) : ?>hidden<?php endif; ?>>
					<?php
					/**
					 * I body_html sono gia stati resi da Rdacademy_Markdown con escape
					 * integrato del testo: le tag HTML le emette il parser, lato
					 * server, non un input esterno. Gli SVG sono file statici
					 * richiamati come immagini (niente SVG in linea nel Markdown).
					 */
					echo $section['body_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</article>
			<?php endforeach; ?>

		</main>

	</div>

	<footer class="rdacademy-docs-footer">
		<p>
			<?php
			// RP-VERSIONE-PANNELLO-MINIMO (RelDox 3.15.7): nel piede va lo stadio, non il pacchetto.
			echo esc_html( RDACADEMY_PRODUCT ) . ' <strong>' . esc_html( RDACADEMY_VERSION_LABEL ) . '</strong> — <code>' . esc_html( RDACADEMY_STAGE ) . '</code>';
			?>
		</p>
	</footer>

</div>
