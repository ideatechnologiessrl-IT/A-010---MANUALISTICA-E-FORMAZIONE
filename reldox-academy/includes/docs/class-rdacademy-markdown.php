<?php
/**
 * Rdacademy_Markdown — parser Markdown minimale per la documentazione interna.
 *
 * Copiato da Reldox_Markdown, RelDox CRD 3.55.09G, senza tagli: e generico.
 * CORRETTO: i collegamenti e le immagini accettano solo indirizzi http(s),
 * relativi o ancore. In RelDox `[testo](javascript:...)` diventava un href
 * eseguibile: il testo arriva da file del plugin, ma un parser che si dichiara
 * «sicuro by design» non deve dipendere da chi scrive il file.
 *
 * Decisione D-pkg-docs-03 (analisi v1.13 §19bis): in luogo di Parsedown
 * (vendor 2019, manutenzione inattiva) o league/commonmark (richiede
 * Composer, non installabile su shared hosting senza tooling), pkg-docs
 * usa un parser custom minimale che copre ESATTAMENTE il sottoinsieme
 * Markdown utilizzato dalle sezioni della documentazione:
 *
 * Block:
 *   - Heading H1-H4 (# ## ### ####)
 *   - Paragrafi (testo separato da blank line)
 *   - Liste non ordinate (- item)
 *   - Liste ordinate (1. item, 2. item ...)
 *   - Code fenced (```lang ... ```)
 *   - Horizontal rule (---)
 *
 * Inline:
 *   - **bold** e *italic*
 *   - `code inline`
 *   - [link](url)
 *   - ![alt](path/img.svg) — rewrite path via callback
 *
 * Frontmatter:
 *   - --- ... --- YAML-like K:V semplice, no nesting, no array
 *
 * Sicurezza: TUTTO il testo viene escape-d via htmlspecialchars prima del
 * parsing inline; l'HTML inline arbitrario NON è supportato by design
 * (a differenza di Parsedown che richiede setSafeMode per disabilitarlo).
 *
 * @package RelDox_Base
 * @since   3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_Markdown {

	/**
	 * Callback applicata ai path delle immagini per riscriverli in URL plugin.
	 * Firma: function( string $relative_path ): string $absolute_url
	 */
	private $image_url_rewriter = null;

	/**
	 * Imposta il callback per riscrivere i path immagine.
	 */
	public function set_image_url_rewriter( callable $cb ): void {
		$this->image_url_rewriter = $cb;
	}

	/**
	 * Parse frontmatter YAML-like + body Markdown.
	 *
	 * @param string $raw Contenuto file MD completo.
	 * @return array{frontmatter: array<string, string>, body: string}
	 */
	public function parse_file( string $raw ): array {
		$frontmatter = array();
		$body        = $raw;

		// Frontmatter pattern: ---\n...\n---\n
		if ( preg_match( '/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $raw, $matches ) ) {
			$fm_block = $matches[1];
			$body     = $matches[2];

			foreach ( explode( "\n", $fm_block ) as $line ) {
				$line = trim( $line );
				if ( $line === '' || $line[0] === '#' ) continue;

				if ( strpos( $line, ':' ) === false ) continue;

				list( $k, $v ) = explode( ':', $line, 2 );
				$frontmatter[ trim( $k ) ] = trim( $v, " \t\"'" );
			}
		}

		return array(
			'frontmatter' => $frontmatter,
			'body'        => $body,
		);
	}

	/**
	 * Converte body Markdown in HTML.
	 *
	 * @param string $markdown Body Markdown (senza frontmatter).
	 * @return string HTML risultante.
	 */
	public function render( string $markdown ): string {
		// Normalizza line endings
		$markdown = str_replace( array( "\r\n", "\r" ), "\n", $markdown );

		// Estrai blocchi code fenced PRIMA del resto (placeholder pattern)
		// per non far interferire il parsing inline al loro interno.
		// Uso un placeholder stampabile e univoco invece di \x00 perché i
		// caratteri NULL possono essere strippati dal blocco di splitting.
		$code_blocks  = array();
		$placeholder  = '@@RDACADEMY_CODE_BLOCK_%d@@';

		$markdown = preg_replace_callback(
			'/^```([a-zA-Z0-9_-]*)\n(.*?)\n```$/ms',
			function ( $m ) use ( &$code_blocks, $placeholder ) {
				$idx = count( $code_blocks );
				$lang = $m[1] !== '' ? ' class="language-' . htmlspecialchars( $m[1], ENT_QUOTES, 'UTF-8' ) . '"' : '';
				$code = htmlspecialchars( $m[2], ENT_QUOTES, 'UTF-8' );
				$code_blocks[ $idx ] = '<pre><code' . $lang . '>' . $code . '</code></pre>';
				return sprintf( $placeholder, $idx );
			},
			$markdown
		);

		// Splitta in blocchi separati da blank line(s)
		$blocks = preg_split( "/\n\n+/", trim( $markdown ) );
		$html   = array();

		foreach ( $blocks as $block ) {
			$block = trim( $block );
			if ( $block === '' ) continue;

			// Placeholder code block → restituisci come è
			if ( preg_match( '/^@@RDACADEMY_CODE_BLOCK_(\d+)@@$/', $block, $m ) ) {
				$html[] = $code_blocks[ (int) $m[1] ];
				continue;
			}

			// HR
			if ( preg_match( '/^---+$/', $block ) ) {
				$html[] = '<hr>';
				continue;
			}

			// Heading
			if ( preg_match( '/^(#{1,4})\s+(.+)$/', $block, $m ) ) {
				$level = strlen( $m[1] );
				$text  = $this->render_inline( $m[2] );
				$slug  = $this->slugify( $m[2] );
				$html[] = sprintf( '<h%1$d id="%2$s">%3$s</h%1$d>', $level, $slug, $text );
				continue;
			}

			// Lista ordinata: tutte le righe sono "N. testo"
			if ( preg_match( '/^(\d+)\.\s+/', $block ) ) {
				$items = preg_split( "/\n(?=\d+\.\s)/", $block );
				$html[] = '<ol>' . implode( '', array_map(
					function ( $it ) {
						$it = preg_replace( '/^\d+\.\s+/', '', $it );
						return '<li>' . $this->render_inline( $it ) . '</li>';
					},
					$items
				) ) . '</ol>';
				continue;
			}

			// Lista non ordinata: tutte le righe iniziano con "- "
			if ( preg_match( '/^-\s+/', $block ) ) {
				$items = preg_split( "/\n(?=-\s)/", $block );
				$html[] = '<ul>' . implode( '', array_map(
					function ( $it ) {
						$it = preg_replace( '/^-\s+/', '', $it );
						return '<li>' . $this->render_inline( $it ) . '</li>';
					},
					$items
				) ) . '</ul>';
				continue;
			}

			// Default: paragrafo
			$html[] = '<p>' . $this->render_inline( $block ) . '</p>';
		}

		return implode( "\n", $html );
	}

	/**
	 * Render inline elements: bold, italic, code, link, image.
	 * Esegue htmlspecialchars sul testo grezzo prima di applicare le regex
	 * di markup, così l'HTML arbitrario è disabilitato by design.
	 */
	private function render_inline( string $text ): string {
		// Estrai inline code PRIMA dell'escape per non rovinare contenuto < >
		// Placeholder stampabile (stessa logica del code fenced)
		$inline_codes = array();
		$text = preg_replace_callback(
			'/`([^`]+)`/',
			function ( $m ) use ( &$inline_codes ) {
				$idx = count( $inline_codes );
				$inline_codes[ $idx ] = '<code>' . htmlspecialchars( $m[1], ENT_QUOTES, 'UTF-8' ) . '</code>';
				return "@@RDACADEMY_IC_{$idx}@@";
			},
			$text
		);

		// Escape generale
		$text = htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );

		// Image: ![alt](path)
		$text = preg_replace_callback(
			'/!\[([^\]]*)\]\(([^)]+)\)/',
			function ( $m ) {
				$alt  = $m[1];
				$src  = $m[2];
				if ( ! self::url_ammesso( html_entity_decode( $src, ENT_QUOTES, 'UTF-8' ) ) ) {
					return $alt; // indirizzo non ammesso: resta il testo alternativo
				}
				if ( $this->image_url_rewriter !== null ) {
					$src = call_user_func( $this->image_url_rewriter, $src );
				}
				return '<img src="' . $src . '" alt="' . $alt . '">';
			},
			$text
		);

		// Link: [testo](url)
		$text = preg_replace_callback(
			'/\[([^\]]+)\]\(([^)]+)\)/',
			static function ( $m ) {
				if ( ! self::url_ammesso( html_entity_decode( $m[2], ENT_QUOTES, 'UTF-8' ) ) ) {
					return $m[1]; // indirizzo non ammesso: resta il testo, senza collegamento
				}
				return '<a href="' . $m[2] . '">' . $m[1] . '</a>';
			},
			$text
		);

		// Bold **testo**
		$text = preg_replace( '/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text );

		// Italic *testo* (dopo bold per evitare conflitti)
		$text = preg_replace( '/(?<!\*)\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $text );

		// Reinserisci inline codes
		$text = preg_replace_callback(
			'/@@RDACADEMY_IC_(\d+)@@/',
			function ( $m ) use ( $inline_codes ) {
				return $inline_codes[ (int) $m[1] ];
			},
			$text
		);

		return $text;
	}

	/**
	 * NUOVO — un indirizzo di collegamento o d immagine e ammesso se e http(s),
	 * relativo (senza schema) o un ancora. Ogni altro schema (javascript:,
	 * data:, vbscript:…) si rifiuta.
	 */
	public static function url_ammesso( string $url ): bool {
		$u = trim( $url );
		if ( $u === '' ) return false;
		if ( preg_match( '#^https?://#i', $u ) ) return true;
		// Uno schema qualunque (lettere seguite da «:» prima di / ? #) non e ammesso.
		return ! preg_match( '#^[a-z][a-z0-9+.\-]*:#i', $u );
	}

	/**
	 * Slugify per anchor di heading (uso interno).
	 */
	public function slugify( string $text ): string {
		$slug = function_exists( 'mb_strtolower' )
			? mb_strtolower( $text, 'UTF-8' )
			: strtolower( $text );
		// Translittera caratteri accentati comuni italiani
		$slug = str_replace(
			array( 'à', 'è', 'é', 'ì', 'ò', 'ù', 'À', 'È', 'É', 'Ì', 'Ò', 'Ù' ),
			array( 'a', 'e', 'e', 'i', 'o', 'u', 'a', 'e', 'e', 'i', 'o', 'u' ),
			$slug
		);
		$slug = preg_replace( '/[^a-z0-9]+/', '-', $slug );
		$slug = trim( $slug, '-' );
		return $slug;
	}
}
