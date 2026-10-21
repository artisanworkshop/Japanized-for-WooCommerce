<?php
/**
 * Tests that the plugin loads its class files at plugins_loaded.
 *
 * A plugin update replaces the plugin directory while requests are running. A
 * request that loaded the plugin before the update and requires a file later,
 * at init or in a REST request, stops with a fatal error when the directory is
 * missing at that moment (issues #266 and #268).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Load_At_Plugins_Loaded_Test
 */
class JP4WC_Load_At_Plugins_Loaded_Test extends WP_UnitTestCase {

	/**
	 * Class files that hooks after plugins_loaded used to require, and the class each declares.
	 *
	 * @return array
	 */
	public function class_files() {
		return array(
			'product meta, init 20'          => array( 'includes/admin/class-jp4wc-admin-product-meta.php', 'JP4WC_Admin_Product_Meta' ),
			'settings API, rest_api_init'    => array( 'includes/admin/class-jp4wc-settings-api.php', 'JP4WC_Settings_API' ),
			'PHP notice, dashboard'          => array( 'includes/admin/class-jp4wc-admin-php-notice.php', 'JP4WC_Admin_PHP_Notice' ),
			'delivery fields, init 0'        => array( 'includes/blocks/class-jp4wc-delivery-blocks-integration.php', 'JP4WC_Delivery_Blocks_Integration' ),
			'yomigana fields, init 0'        => array( 'includes/blocks/class-jp4wc-yomigana-blocks-integration.php', 'JP4WC_Yomigana_Blocks_Integration' ),
			'post office bank block, init 5' => array( 'includes/blocks/class-wc-payments-postofficebank-blocks-support.php', 'WC_Payments_PostOfficeBank_Blocks_Support' ),
			'bank transfer block, init 5'    => array( 'includes/blocks/class-wc-payments-bank-jp-blocks-support.php', 'WC_Payments_BANK_JP_Blocks_Support' ),
			'pay at store block, init 5'     => array( 'includes/blocks/class-wc-payments-atstore-blocks-support.php', 'WC_Payments_AtStore_Blocks_Support' ),
			'COD block, init 5'              => array( 'includes/blocks/class-wc-payments-cod2-blocks-support.php', 'WC_Payments_Cod2_Blocks_Support' ),
			'Paidy block, init 5'            => array( 'includes/gateways/paidy/class-wc-payments-paidy-blocks-support.php', 'WC_Payments_Paidy_Blocks_Support' ),
		);
	}

	/**
	 * The file is loaded, and its class declared, by the end of plugins_loaded.
	 *
	 * @dataProvider class_files
	 *
	 * @param string $file       Plugin-relative path of the class file.
	 * @param string $class_name Class the file declares.
	 */
	public function test_class_file_is_loaded_at_plugins_loaded( $file, $class_name ) {
		$this->assertArrayHasKey( 'jp4wc_tests_plugins_loaded_state', $GLOBALS, 'tests/bootstrap.php records the state at plugins_loaded.' );
		$state = $GLOBALS['jp4wc_tests_plugins_loaded_state'];

		$this->assertContains( realpath( dirname( __DIR__, 2 ) . '/' . $file ), $state['files'] );
		// A file that returns early before its class (for example on a guard that is
		// always false) is in the list above but declares nothing.
		$this->assertContains( $class_name, $state['classes'] );
	}

	/**
	 * The callbacks that run after plugins_loaded read no files.
	 *
	 * Checked on the source: require_once of a file that is already loaded still
	 * resolves the path on disk, so it fails while the directory is missing.
	 */
	public function test_hook_callbacks_read_no_files() {
		$this->assertTrue( function_exists( 'wc_paidy_plugin' ) );
		$callbacks = array(
			'JP4WC::on_plugins_loaded'                   => new ReflectionMethod( 'JP4WC', 'on_plugins_loaded' ),
			'JP4WC::jp4wc_blocks_support'                => new ReflectionMethod( 'JP4WC', 'jp4wc_blocks_support' ),
			'JP4WC_Admin_Settings::register_rest_routes' => new ReflectionMethod( 'JP4WC_Admin_Settings', 'register_rest_routes' ),
			'JP4WC_Admin_Settings::conditional_includes' => new ReflectionMethod( 'JP4WC_Admin_Settings', 'conditional_includes' ),
			'wc_paidy_plugin'                            => new ReflectionFunction( 'wc_paidy_plugin' ),
		);

		foreach ( $callbacks as $name => $callback ) {
			$this->assertSame( array(), $this->file_reads( $callback ), $name . ' reads a file (lines listed).' );
		}
	}

	/**
	 * Guards pass fully qualified names to class_exists() and its siblings.
	 *
	 * These functions take a string and do not resolve `use` imports, so a short
	 * imported name is always false. A class file loaded at plugins_loaded returns
	 * early on such a guard and never declares its class.
	 */
	public function test_class_exists_guards_use_fully_qualified_names() {
		$root  = realpath( dirname( __DIR__, 2 ) );
		$files = array_merge( glob( $root . '/*.php' ), $this->php_files_in( $root . '/includes' ) );

		$short_names = array();
		foreach ( $files as $file ) {
			foreach ( $this->short_imported_names_in_guards( $file ) as $line => $name ) {
				$short_names[] = substr( $file, strlen( $root ) + 1 ) . ':' . $line . ' ' . $name;
			}
		}
		$this->assertSame( array(), $short_names );
	}

	/**
	 * A file under includes/ loads the class files of the classes it creates.
	 *
	 * During an update, the root files of the previous version can already be
	 * running when this version's includes/ is in place, and then the previous
	 * version's JP4WC::includes() loads the files of this version. A class file
	 * that only this version's includes() loads is never loaded in that request,
	 * and creating the class stops it with a fatal error. So a file loads what it
	 * needs itself, outside any function, when it is loaded. Classes written
	 * through a `use` alias, such as the framework, are not checked: the root
	 * files of every version load the framework.
	 */
	public function test_included_files_load_the_classes_they_create() {
		$root    = realpath( dirname( __DIR__, 2 ) );
		$files   = $this->php_files_in( $root . '/includes' );
		$missing = array();
		foreach ( $files as $file ) {
			$loaded = $this->files_loaded_at_top_level( $file );
			foreach ( $this->created_classes( $file ) as $line => $class_name ) {
				$declared_in = class_exists( $class_name, false ) ? ( new ReflectionClass( $class_name ) )->getFileName() : false;
				if ( ! in_array( $declared_in, $files, true ) || $declared_in === $file || in_array( basename( $declared_in ), $loaded, true ) ) {
					continue;
				}
				$missing[] = substr( $file, strlen( $root ) + 1 ) . ':' . $line . ' new ' . $class_name . ' (' . substr( $declared_in, strlen( $root ) + 1 ) . ')';
			}
		}
		$this->assertSame( array(), $missing );
	}

	/**
	 * PHP files under a directory.
	 *
	 * @param string $dir Directory.
	 * @return string[] Real paths.
	 */
	private function php_files_in( $dir ) {
		$files = array();
		$tree  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $tree as $file ) {
			if ( 'php' === $file->getExtension() ) {
				$files[] = $file->getRealPath();
			}
		}
		$this->assertNotEmpty( $files );
		return $files;
	}

	/**
	 * Tokens of a PHP file without whitespace and comments.
	 *
	 * @param string $file Path of a PHP file.
	 * @return array
	 */
	private function significant_tokens( $file ) {
		return array_values(
			array_filter(
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.
				token_get_all( file_get_contents( $file ) ),
				static function ( $token ) {
					return ! is_array( $token ) || ! in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true );
				}
			)
		);
	}

	/**
	 * Base names of the files a file requires or includes outside any braces.
	 *
	 * @param string $file Path of a PHP file.
	 * @return string[]
	 */
	private function files_loaded_at_top_level( $file ) {
		$tokens = $this->significant_tokens( $file );
		$loaded = array();
		$depth  = 0;
		$count  = count( $tokens );
		for ( $i = 0; $i < $count; $i++ ) {
			$token = $tokens[ $i ];
			if ( '{' === $token || ( is_array( $token ) && in_array( $token[0], array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true ) ) ) {
				++$depth;
			} elseif ( '}' === $token ) {
				--$depth;
			}
			if ( 0 !== $depth || ! is_array( $token ) || ! in_array( $token[0], array( T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE ), true ) ) {
				continue;
			}
			for ( $j = $i + 1; $j < $count && ';' !== $tokens[ $j ]; $j++ ) {
				if ( is_array( $tokens[ $j ] ) && T_CONSTANT_ENCAPSED_STRING === $tokens[ $j ][0] ) {
					$loaded[] = basename( substr( $tokens[ $j ][1], 1, -1 ) );
				}
			}
		}
		return $loaded;
	}

	/**
	 * Classes a file creates with `new` and an unqualified name.
	 *
	 * @param string $file Path of a PHP file.
	 * @return array Line => class name.
	 */
	private function created_classes( $file ) {
		$tokens  = $this->significant_tokens( $file );
		$created = array();
		$count   = count( $tokens );
		for ( $i = 0; $i < $count - 1; $i++ ) {
			if ( is_array( $tokens[ $i ] ) && T_NEW === $tokens[ $i ][0] && is_array( $tokens[ $i + 1 ] ) && T_STRING === $tokens[ $i + 1 ][0] ) {
				$created[ $tokens[ $i ][2] ] = $tokens[ $i + 1 ][1];
			}
		}
		return $created;
	}

	/**
	 * Lines of require and include statements inside a function.
	 *
	 * @param ReflectionFunctionAbstract $callback Function or method.
	 * @return int[]
	 */
	private function file_reads( ReflectionFunctionAbstract $callback ) {
		$reads = array();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.
		foreach ( token_get_all( file_get_contents( $callback->getFileName() ) ) as $token ) {
			if ( ! is_array( $token ) || ! in_array( $token[0], array( T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE ), true ) ) {
				continue;
			}
			if ( $token[2] >= $callback->getStartLine() && $token[2] <= $callback->getEndLine() ) {
				$reads[] = $token[2];
			}
		}
		return $reads;
	}

	/**
	 * Guards in a file that pass a name the file imports with `use`.
	 *
	 * @param string $file Path of a PHP file.
	 * @return array Line => guard, e.g. 12 => "class_exists( 'AbstractPaymentMethodType' )".
	 */
	private function short_imported_names_in_guards( $file ) {
		$tokens = $this->significant_tokens( $file );
		$names  = array( T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED );
		$guards = array( 'class_exists', 'interface_exists', 'trait_exists', 'enum_exists' );

		$imports = array();
		$found   = array();
		$depth   = 0;
		$count   = count( $tokens );
		for ( $i = 0; $i < $count; $i++ ) {
			$token = $tokens[ $i ];
			if ( '{' === $token || ( is_array( $token ) && in_array( $token[0], array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true ) ) ) {
				++$depth;
			} elseif ( '}' === $token ) {
				--$depth;
			}
			if ( ! is_array( $token ) ) {
				continue;
			}

			// `use Foo\Bar;` or `use Foo\Bar as Baz;` outside any class or function.
			if ( T_USE === $token[0] && 0 === $depth && isset( $tokens[ $i + 1 ] ) && is_array( $tokens[ $i + 1 ] ) && in_array( $tokens[ $i + 1 ][0], $names, true ) ) {
				$alias = $tokens[ $i + 1 ][1];
				$alias = substr( $alias, (int) strrpos( '\\' . $alias, '\\' ) );
				if ( isset( $tokens[ $i + 3 ] ) && is_array( $tokens[ $i + 2 ] ) && T_AS === $tokens[ $i + 2 ][0] && is_array( $tokens[ $i + 3 ] ) ) {
					$alias = $tokens[ $i + 3 ][1];
				}
				$imports[ strtolower( $alias ) ] = true;
				continue;
			}

			// class_exists( 'Name' ... ) with a literal first argument.
			if ( ! in_array( $token[0], $names, true ) || ! in_array( strtolower( ltrim( $token[1], '\\' ) ), $guards, true ) ) {
				continue;
			}
			if ( ! isset( $tokens[ $i + 2 ] ) || '(' !== $tokens[ $i + 1 ] || ! is_array( $tokens[ $i + 2 ] ) || T_CONSTANT_ENCAPSED_STRING !== $tokens[ $i + 2 ][0] ) {
				continue;
			}
			$name = substr( $tokens[ $i + 2 ][1], 1, -1 );
			if ( false === strpos( $name, '\\' ) && isset( $imports[ strtolower( $name ) ] ) ) {
				$found[ $token[2] ] = $token[1] . "( '" . $name . "' )";
			}
		}
		return $found;
	}
}
