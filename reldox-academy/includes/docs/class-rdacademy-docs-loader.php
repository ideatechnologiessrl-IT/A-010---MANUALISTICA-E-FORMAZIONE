<?php
/**
 * Rdacademy_Docs_Loader — scansiona docs-content/ e carica i file MD.
 *
 * Copiato da Reldox_Docs_Loader, RelDox CRD 3.55.09G, senza tagli: e generico.
 * Aggiungere documentazione = aggiungere un file NN-slug.md (REGOLE § 3.10).
 *
 * Pattern numerico nei filename (10-, 20-, 30-) fornisce ordinamento
 * naturale lessicografico senza bisogno di leggere il frontmatter.
 * Step di 10 per inserimenti futuri senza rinominazione di massa.
 *
 * @package RelDox_Base
 * @since   3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_Docs_Loader {

	private string $content_dir;
	private Rdacademy_Markdown $parser;

	public function __construct( string $content_dir, Rdacademy_Markdown $parser ) {
		$this->content_dir = rtrim( $content_dir, '/\\' );
		$this->parser      = $parser;
	}

	/**
	 * Carica tutti i file MD nella content dir, ordinati per nome file.
	 *
	 * @return array<int, array{
	 *     filename: string,
	 *     slug: string,
	 *     title: string,
	 *     frontmatter: array<string, string>,
	 *     body_html: string
	 * }>
	 */
	public function load_all(): array {
		$result = array();

		if ( ! is_dir( $this->content_dir ) ) {
			return $result;
		}

		// Pattern: NN-slug.md  es. "10-configurazione-database.md"
		$files = glob( $this->content_dir . '/[0-9]*.md' );
		if ( $files === false ) {
			return $result;
		}

		// Ordinamento naturale (10- < 20- < 30-)
		sort( $files, SORT_NATURAL );

		foreach ( $files as $file ) {
			$raw    = @file_get_contents( $file );
			if ( $raw === false ) continue;

			$parsed = $this->parser->parse_file( $raw );
			$fm     = $parsed['frontmatter'];

			$slug = $fm['slug'] ?? $this->derive_slug_from_filename( basename( $file ) );

			$result[] = array(
				'filename'    => basename( $file ),
				'slug'        => $slug,
				'title'       => $fm['title'] ?? $slug,
				'frontmatter' => $fm,
				'body_html'   => $this->parser->render( $parsed['body'] ),
			);
		}

		return $result;
	}

	/**
	 * Estrae uno slug dal filename "NN-slug.md" → "slug".
	 */
	private function derive_slug_from_filename( string $filename ): string {
		$name = preg_replace( '/^\d+-/', '', $filename );
		$name = preg_replace( '/\.md$/', '', $name );
		return $name;
	}
}
