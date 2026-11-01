<?php
/**
 * Tests that the bundled translation files agree with each other.
 *
 * A Japanese site reads PHP strings from the bundled .mo and the strings of
 * each script from the bundled JSON named after the md5 of the script's path,
 * which WordPress uses instead of the language pack's JSON, never together
 * with it. The .po once lacked 19 strings of the .pot, and the settings
 * screen's JSON lacked 6 strings the .po translated, so they showed in English;
 * 21 JSON files were for scripts no page loads (issue #308).
 *
 * The .pot is made from the shipped files with `npm run make-pot`, which CI
 * cannot run, so these tests check the committed files against each other.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Translation_Files_Test
 */
class JP4WC_Translation_Files_Test extends WP_UnitTestCase {

	/**
	 * Template, relative to the plugin directory.
	 */
	const POT = 'i18n/woocommerce-for-japan.pot';

	/**
	 * Japanese translation, relative to the plugin directory.
	 */
	const PO = 'i18n/woocommerce-for-japan-ja.po';

	/**
	 * Compiled Japanese translation, relative to the plugin directory.
	 */
	const MO = 'i18n/woocommerce-for-japan-ja.mo';

	/**
	 * Prefix of the Japanese script translation files, relative to the plugin directory.
	 */
	const JSON_PREFIX = 'i18n/woocommerce-for-japan-ja-';

	/**
	 * Reads a .pot or .po file of the plugin.
	 *
	 * @param string $file Path relative to the plugin directory.
	 * @return PO
	 */
	private function read_po( $file ) {
		require_once ABSPATH . WPINC . '/pomo/po.php';
		$po = new PO();
		$this->assertTrue( $po->import_from_file( JP4WC_ABSPATH . $file ), $file );
		$this->assertNotEmpty( $po->entries, $file );
		return $po;
	}

	/**
	 * Returns the file path of a `#:` reference, without the line number.
	 *
	 * @param string $reference Reference such as `assets/js/build/admin/settings.js:1`.
	 * @return string
	 */
	private static function reference_path( $reference ) {
		return preg_replace( '/:\d+$/', '', $reference );
	}

	/**
	 * Describes entry keys for a failure message (context and text are joined by EOT).
	 *
	 * @param array $keys Entry keys.
	 * @return string
	 */
	private static function describe( array $keys ) {
		return implode( "\n", str_replace( chr( 4 ), ' | ', $keys ) );
	}

	/**
	 * The .po has the strings of the .pot, all translated, and no others.
	 */
	public function test_po_translates_every_string_of_the_pot() {
		$pot = $this->read_po( self::POT );
		$po  = $this->read_po( self::PO );

		$missing = array_diff( array_keys( $pot->entries ), array_keys( $po->entries ) );
		$this->assertSame( array(), array_values( $missing ), "Strings of the .pot missing from the .po:\n" . self::describe( $missing ) );

		$unused = array_diff( array_keys( $po->entries ), array_keys( $pot->entries ) );
		$this->assertSame( array(), array_values( $unused ), "Strings of the .po that are not in the .pot:\n" . self::describe( $unused ) );

		$untranslated = array();
		foreach ( $po->entries as $key => $entry ) {
			if ( in_array( 'fuzzy', $entry->flags, true ) || empty( $entry->translations ) || in_array( '', $entry->translations, true ) ) {
				$untranslated[] = $key;
			}
		}
		$this->assertSame( array(), $untranslated, "Strings without a translation (or fuzzy) in the .po:\n" . self::describe( $untranslated ) );
	}

	/**
	 * The .mo holds the translations of the .po.
	 */
	public function test_mo_holds_the_translations_of_the_po() {
		$po = $this->read_po( self::PO );
		$mo = new MO();
		$this->assertTrue( $mo->import_from_file( JP4WC_ABSPATH . self::MO ) );

		$expected = array();
		foreach ( $po->entries as $key => $entry ) {
			if ( ! in_array( 'fuzzy', $entry->flags, true ) && ! empty( $entry->translations ) && ! in_array( '', $entry->translations, true ) ) {
				$expected[ $key ] = $entry->translations;
			}
		}
		$actual = array();
		foreach ( $mo->entries as $key => $entry ) {
			$actual[ $key ] = $entry->translations;
		}
		ksort( $expected );
		ksort( $actual );

		$this->assertSame( $expected, $actual, 'Rebuild the .mo from the .po with msgfmt.' );
	}

	/**
	 * Every reference of the .pot is a shipped file, as WordPress.org sees them.
	 */
	public function test_pot_references_shipped_files_only() {
		$pot      = $this->read_po( self::POT );
		$patterns = array();
		foreach ( file( JP4WC_ABSPATH . '.distignore', FILE_IGNORE_NEW_LINES ) as $line ) {
			$line = trim( $line );
			if ( '' !== $line && '#' !== $line[0] ) {
				$patterns[] = $line;
			}
		}
		$this->assertNotEmpty( $patterns );

		$bad = array();
		foreach ( $pot->entries as $entry ) {
			foreach ( $entry->references as $reference ) {
				$path = self::reference_path( $reference );
				if ( ! is_file( JP4WC_ABSPATH . $path ) ) {
					$bad[ $path ] = 'missing';
					continue;
				}
				foreach ( $patterns as $pattern ) {
					if ( '/' === $pattern[0] ) {
						// Anchored at the plugin directory: the path itself or anything below it.
						$anchored = substr( $pattern, 1 );
						$matches  = fnmatch( $anchored, $path, FNM_PATHNAME ) || 0 === strpos( $path, $anchored . '/' );
					} else {
						// Any file or directory of that name.
						$matches = false;
						foreach ( explode( '/', $path ) as $part ) {
							$matches = $matches || fnmatch( $pattern, $part );
						}
					}
					if ( $matches ) {
						$bad[ $path ] = 'excluded by .distignore ' . $pattern;
					}
				}
			}
		}

		$this->assertSame( array(), $bad, 'Make the .pot with `npm run make-pot`, which scans the shipped files only.' );
	}

	/**
	 * Each script the .pot references has a JSON with the translations of the .po, and no JSON is for another file.
	 */
	public function test_script_translations_match_the_po() {
		$pot = $this->read_po( self::POT );
		$po  = $this->read_po( self::PO );

		$expected = array();
		foreach ( $pot->entries as $key => $entry ) {
			foreach ( $entry->references as $reference ) {
				$path = self::reference_path( $reference );
				if ( '.js' !== substr( $path, -3 ) ) {
					continue;
				}
				$expected[ $path ][ $key ] = isset( $po->entries[ $key ] ) ? $po->entries[ $key ]->translations : null;
			}
		}
		$this->assertNotEmpty( $expected );

		$actual = array();
		foreach ( glob( JP4WC_ABSPATH . self::JSON_PREFIX . '*.json' ) as $file ) {
			$data = json_decode( file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local file.
			$name = substr( $file, strlen( JP4WC_ABSPATH ) );
			$this->assertIsArray( $data, $name );
			$this->assertTrue( isset( $data['source'], $data['locale_data']['messages'] ), $name );
			$this->assertSame( JP4WC_ABSPATH . self::JSON_PREFIX . md5( $data['source'] ) . '.json', $file, 'The name of a script translation file is the md5 of its source.' );

			$messages = $data['locale_data']['messages'];
			unset( $messages[''] );
			$actual[ $data['source'] ] = $messages;
		}

		$extra = array_diff( array_keys( $actual ), array_keys( $expected ) );
		$this->assertSame( array(), array_values( $extra ), 'Script translation files for scripts the .pot does not reference.' );
		$missing = array_diff( array_keys( $expected ), array_keys( $actual ) );
		$this->assertSame( array(), array_values( $missing ), 'Scripts the .pot references without a script translation file.' );

		foreach ( $expected as $path => $messages ) {
			ksort( $messages );
			ksort( $actual[ $path ] );
			$this->assertSame( $messages, $actual[ $path ], 'Translations of ' . $path . ' differ from the .po: run `npm run make-json`.' );
		}
	}
}
