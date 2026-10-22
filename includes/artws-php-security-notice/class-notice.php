<?php
/**
 * Artisan Workshop PHP security notice.
 *
 * Loaded by register.php. Keep this directory identical in every plugin that
 * bundles it, and keep the code runnable on PHP 7.0.
 *
 * @package ArtisanWorkshop\PhpSecurityNotice
 * @version 1.0.0
 */

namespace ArtisanWorkshop\PhpSecurityNotice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ArtisanWorkshop\PhpSecurityNotice\Notice', false ) ) :

	/**
	 * Asks site administrators to update a PHP version with known critical vulnerabilities.
	 *
	 * The notice is a modal dialog on every admin screen except the block editor
	 * and the Customizer, shown to users who can change the PHP version: those
	 * with manage_options, or on multisite those with manage_network_options.
	 * "Don't show again" stores the notice ID and the PHP version in user meta,
	 * so the notice comes back when the PHP version changes but is still
	 * vulnerable, or when NOTICE_ID changes for a new vulnerability.
	 *
	 * The class holds the facts (fixed versions, vulnerability IDs, article URL)
	 * and no words. The plugin that registered the loaded copy passes a callback
	 * that returns these strings, translated with its own text domain:
	 *
	 * - title:          Title of the dialog.
	 * - vulnerable:     A branch with a fix, below its fixed version. %1$s is the
	 *                   site's PHP version, %2$s the fixed version, %3$s the
	 *                   vulnerability IDs.
	 * - support_ending: Added for a branch whose security support ends soon (PHP 8.2).
	 * - vendor_patch:   Added to `vulnerable`: hosting companies can apply the fix
	 *                   without changing the version number.
	 * - unsupported:    A branch older than every branch with a fix. %1$s is the
	 *                   site's PHP version, %2$s the vulnerability IDs.
	 * - prerelease:     A pre-release of a branch whose fix is a pre-release, below
	 *                   that fix. Placeholders as in `vulnerable`.
	 * - learn_more:     Link to the article.
	 * - dismiss:        Button that hides the notice for good.
	 * - close:          Label of the button that closes the dialog on this screen only.
	 *
	 * @since 1.0.0
	 */
	class Notice {

		/**
		 * Version of this copy. Raise it, and the version each plugin registers, on every change.
		 */
		const VERSION = '1.0.0';

		/**
		 * ID of the vulnerability the notice is about. A new ID shows the notice again to users who dismissed it.
		 */
		const NOTICE_ID = 'php2shell-2026-10';

		/**
		 * User meta that records "Don't show again". Shared by every plugin that bundles the notice.
		 */
		const META_KEY = 'artws_php_security_notice_dismissed';

		/**
		 * Ajax action and nonce action of "Don't show again".
		 */
		const AJAX_ACTION = 'artws_php_security_notice_dismiss';

		/**
		 * Handle of the inline style and script.
		 */
		const HANDLE = 'artws-php-security-notice';

		/**
		 * First fixed version of each PHP branch (CVE-2026-109618, CVE-2026-109619).
		 *
		 * Branches older than the oldest one here have no official fix.
		 */
		const FIXED_VERSIONS = array(
			'8.2' => '8.2.35',
			'8.3' => '8.3.36',
			'8.4' => '8.4.27',
			'8.5' => '8.5.12',
			'8.6' => '8.6.0RC4',
		);

		/**
		 * Branches whose security support ends soon (PHP 8.2: 2026-12-31).
		 */
		const ENDING_BRANCHES = array( '8.2' );

		/**
		 * IDs of the vulnerabilities, shown in the notice.
		 */
		const CVE_IDS = 'CVE-2026-109618, CVE-2026-109619';

		/**
		 * Article that explains the vulnerabilities and how to update PHP.
		 */
		const INFO_URL = 'https://wc4jp-pro.work/php2shell-php-update/';

		/**
		 * Keys of the strings the strings callback returns.
		 */
		const STRING_KEYS = array( 'title', 'vulnerable', 'support_ending', 'vendor_patch', 'unsupported', 'prerelease', 'learn_more', 'dismiss', 'close' );

		/**
		 * The single instance.
		 *
		 * @var Notice|null
		 */
		private static $instance = null;

		/**
		 * Returns the strings of the notice.
		 *
		 * @var callable
		 */
		private $strings_callback;

		/**
		 * What the dialog shows on this screen, or null when it is not shown.
		 *
		 * @var array|null
		 */
		private $view = null;

		/**
		 * Creates the notice once and hooks it into the admin.
		 *
		 * @param callable $strings_callback Returns the notice's strings.
		 * @return Notice
		 */
		public static function boot( $strings_callback ) {
			if ( null === self::$instance ) {
				self::$instance = new self( $strings_callback );
				add_action( 'admin_init', array( self::$instance, 'admin_init' ) );
			}
			return self::$instance;
		}

		/**
		 * Returns the instance created by boot().
		 *
		 * @return Notice|null
		 */
		public static function instance() {
			return self::$instance;
		}

		/**
		 * Constructor.
		 *
		 * @param callable $strings_callback Returns the notice's strings.
		 */
		private function __construct( $strings_callback ) {
			$this->strings_callback = $strings_callback;
		}

		/**
		 * Hooks the dismiss request and the dialog.
		 *
		 * @return void
		 */
		public function admin_init() {
			add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'ajax_dismiss' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		}

		/**
		 * Returns the PHP version the notice judges.
		 *
		 * @return string
		 */
		public function php_version() {
			/**
			 * Filters the PHP version the notice judges. For tests.
			 *
			 * @param string $version PHP_VERSION.
			 */
			$version = apply_filters( 'artws_php_security_notice_php_version', PHP_VERSION );
			return is_string( $version ) ? $version : '';
		}

		/**
		 * Returns the first fixed version of each PHP branch.
		 *
		 * @return array Branch ('8.3') => first fixed version ('8.3.36').
		 */
		public function fixed_versions() {
			/**
			 * Filters the first fixed version of each PHP branch.
			 *
			 * @param array $versions Branch ('8.3') => first fixed version ('8.3.36').
			 */
			$versions = apply_filters( 'artws_php_security_notice_fixed_versions', self::FIXED_VERSIONS );
			return is_array( $versions ) ? $versions : self::FIXED_VERSIONS;
		}

		/**
		 * Returns the URL of the article.
		 *
		 * @return string
		 */
		public function info_url() {
			/**
			 * Filters the URL of the article linked from the notice.
			 *
			 * @param string $url Article URL.
			 */
			$url = apply_filters( 'artws_php_security_notice_info_url', self::INFO_URL );
			return is_string( $url ) ? $url : self::INFO_URL;
		}

		/**
		 * Judges a PHP version.
		 *
		 * Only the version number counts: a distribution suffix such as
		 * "-1ubuntu1" is ignored, so a distribution that backported the fix
		 * without changing the number is still reported (the notice asks to
		 * check with the hosting company).
		 *
		 * @param string|null $php_version Version to judge. Defaults to php_version().
		 * @return array|null Null when the version is fixed or cannot be read. Otherwise:
		 *                    type ('vulnerable', 'unsupported' or 'prerelease'),
		 *                    php_version, branch, fixed_version ('' for unsupported)
		 *                    and support_ending (bool).
		 */
		public function get_status( $php_version = null ) {
			if ( null === $php_version ) {
				$php_version = $this->php_version();
			}
			$parsed = self::parse_version( $php_version );
			if ( null === $parsed ) {
				return null;
			}

			$fixed_versions = array();
			foreach ( $this->fixed_versions() as $branch => $fixed_version ) {
				if ( is_string( $fixed_version ) && null !== self::parse_version( $fixed_version ) ) {
					$fixed_versions[ (string) $branch ] = $fixed_version;
				}
			}
			if ( empty( $fixed_versions ) ) {
				return null;
			}

			$branch = $parsed['branch'];
			$status = array(
				'php_version'    => $php_version,
				'branch'         => $branch,
				'fixed_version'  => '',
				'support_ending' => in_array( $branch, self::ENDING_BRANCHES, true ),
			);

			if ( isset( $fixed_versions[ $branch ] ) ) {
				$fixed_version = $fixed_versions[ $branch ];
				if ( version_compare( $parsed['version'], self::parse_version( $fixed_version )['version'], '>=' ) ) {
					return null;
				}
				// A branch whose fix is a pre-release has no stable release yet.
				$status['type']          = '' === self::parse_version( $fixed_version )['stability'] ? 'vulnerable' : 'prerelease';
				$status['fixed_version'] = $fixed_version;
				return $status;
			}

			$branches = array_keys( $fixed_versions );
			usort( $branches, 'version_compare' );
			if ( version_compare( $branch, $branches[0], '<' ) ) {
				$status['type'] = 'unsupported';
				return $status;
			}

			// A branch released after the fix, or one missing from a filtered list.
			return null;
		}

		/**
		 * Reads the numbers and the stability of a PHP version.
		 *
		 * @param string $version Version such as "8.3.6", "8.6.0RC4" or "8.3.6-1ubuntu1".
		 * @return array|null branch ("8.3"), version ("8.3.6", "8.6.0RC4") and
		 *                    stability ("", "RC4"), or null when it cannot be read.
		 */
		private static function parse_version( $version ) {
			if ( ! is_string( $version ) || ! preg_match( '/^(\d+)\.(\d+)(?:\.(\d+))?(?:-?((?:alpha|beta|RC|dev)\d*))?/i', $version, $matches ) ) {
				return null;
			}
			$patch     = isset( $matches[3] ) && '' !== $matches[3] ? $matches[3] : '0';
			$stability = isset( $matches[4] ) ? $matches[4] : '';
			return array(
				'branch'    => $matches[1] . '.' . $matches[2],
				'version'   => $matches[1] . '.' . $matches[2] . '.' . $patch . $stability,
				'stability' => $stability,
			);
		}

		/**
		 * Returns the capability needed to see the notice: the one to change the server's PHP.
		 *
		 * @param bool $multisite Whether the site is a multisite network.
		 * @return string
		 */
		public static function required_capability( $multisite ) {
			return $multisite ? 'manage_network_options' : 'manage_options';
		}

		/**
		 * Whether the current user can change the server's PHP, and so sees and dismisses the notice.
		 *
		 * The only capability check of the notice.
		 *
		 * @return bool
		 */
		public function current_user_can_update_php() {
			return current_user_can( self::required_capability( is_multisite() ) );
		}

		/**
		 * Whether the user dismissed the notice for this PHP version.
		 *
		 * @param int    $user_id     User ID.
		 * @param string $php_version PHP version the notice judges.
		 * @return bool
		 */
		public function is_dismissed( $user_id, $php_version ) {
			$dismissed = get_user_meta( $user_id, self::META_KEY, true );
			return is_array( $dismissed )
				&& isset( $dismissed['notice_id'], $dismissed['php_version'] )
				&& self::NOTICE_ID === $dismissed['notice_id']
				&& $php_version === $dismissed['php_version'];
		}

		/**
		 * Returns what the dialog shows to the current user on the current screen.
		 *
		 * @return array|null Null when the notice is not shown. Otherwise: type,
		 *                    title, messages (paragraphs), url, learn_more,
		 *                    dismiss and close.
		 */
		public function get_view() {
			$status = $this->get_status();
			if ( null === $status ) {
				return null;
			}
			if ( ! $this->current_user_can_update_php() ) {
				return null;
			}
			if ( $this->is_excluded_screen() || $this->is_dismissed( get_current_user_id(), $status['php_version'] ) ) {
				return null;
			}

			$strings = $this->get_strings();
			$type    = $status['type'];
			if ( '' === $strings['title'] || '' === $strings[ $type ] || '' === $strings['dismiss'] ) {
				return null;
			}

			if ( 'unsupported' === $type ) {
				$messages = array( self::format( $strings[ $type ], array( $status['php_version'], self::CVE_IDS ) ) );
			} else {
				$messages = array( self::format( $strings[ $type ], array( $status['php_version'], $status['fixed_version'], self::CVE_IDS ) ) );
			}
			if ( 'vulnerable' === $type ) {
				if ( $status['support_ending'] && '' !== $strings['support_ending'] ) {
					$messages[] = $strings['support_ending'];
				}
				if ( '' !== $strings['vendor_patch'] ) {
					$messages[] = $strings['vendor_patch'];
				}
			}

			return array(
				'type'       => $type,
				'title'      => $strings['title'],
				'messages'   => $messages,
				'url'        => $this->info_url(),
				'learn_more' => $strings['learn_more'],
				'dismiss'    => $strings['dismiss'],
				'close'      => $strings['close'],
			);
		}

		/**
		 * Whether the current screen is one where the notice is not shown.
		 *
		 * @return bool
		 */
		private function is_excluded_screen() {
			if ( defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) {
				return true;
			}
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( ! $screen ) {
				return false;
			}
			if ( 'customize' === $screen->id ) {
				return true;
			}
			return method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor();
		}

		/**
		 * Returns the strings from the registered callback. A missing string is ''.
		 *
		 * @return array
		 */
		private function get_strings() {
			$strings = is_callable( $this->strings_callback ) ? call_user_func( $this->strings_callback ) : array();
			if ( ! is_array( $strings ) ) {
				$strings = array();
			}
			$result = array();
			foreach ( self::STRING_KEYS as $key ) {
				$result[ $key ] = isset( $strings[ $key ] ) && is_string( $strings[ $key ] ) ? $strings[ $key ] : '';
			}
			return $result;
		}

		/**
		 * Fills the placeholders of a translated string.
		 *
		 * A translation with a wrong placeholder must not stop the admin screen.
		 *
		 * @param string $template String with placeholders.
		 * @param array  $args     Values.
		 * @return string
		 */
		private static function format( $template, $args ) {
			try {
				$text = vsprintf( $template, $args );
			} catch ( \Throwable $e ) {
				// PHP 8 throws ValueError or ArgumentCountError for a bad placeholder.
				return $template;
			}
			return is_string( $text ) ? $text : $template;
		}

		/**
		 * Adds the style and script of the dialog when it is shown on this screen.
		 *
		 * @return void
		 */
		public function enqueue() {
			$this->view = $this->get_view();
			if ( null === $this->view ) {
				return;
			}

			wp_register_style( self::HANDLE, false, array(), self::VERSION );
			wp_enqueue_style( self::HANDLE );
			wp_add_inline_style( self::HANDLE, self::css() );

			wp_register_script( self::HANDLE, false, array(), self::VERSION, true );
			wp_enqueue_script( self::HANDLE );
			wp_add_inline_script( self::HANDLE, self::js() );

			add_action( 'admin_footer', array( $this, 'render' ) );
		}

		/**
		 * Prints the dialog.
		 *
		 * @return void
		 */
		public function render() {
			if ( null === $this->view ) {
				return;
			}
			$view = $this->view;
			?>
<dialog id="artws-php-security-notice" class="artws-php-security-notice" aria-labelledby="artws-php-security-notice-title" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php', 'relative' ) ); ?>" data-action="<?php echo esc_attr( self::AJAX_ACTION ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( self::AJAX_ACTION ) ); ?>">
	<button type="button" class="artws-php-security-notice__close" aria-label="<?php echo esc_attr( $view['close'] ); ?>">&times;</button>
	<h2 id="artws-php-security-notice-title"><?php echo esc_html( $view['title'] ); ?></h2>
			<?php foreach ( $view['messages'] as $message ) : ?>
	<p><?php echo esc_html( $message ); ?></p>
			<?php endforeach; ?>
	<p class="artws-php-security-notice__actions">
			<?php if ( '' !== $view['url'] && '' !== $view['learn_more'] ) : ?>
		<a class="button button-primary" href="<?php echo esc_url( $view['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $view['learn_more'] ); ?></a>
			<?php endif; ?>
		<button type="button" class="button artws-php-security-notice__dismiss"><?php echo esc_html( $view['dismiss'] ); ?></button>
	</p>
</dialog>
			<?php
		}

		/**
		 * Records "Don't show again" for the current user and PHP version.
		 *
		 * @return void
		 */
		public function ajax_dismiss() {
			check_ajax_referer( self::AJAX_ACTION, 'nonce' );
			if ( ! $this->current_user_can_update_php() ) {
				wp_send_json_error( null, 403 );
			}
			update_user_meta(
				get_current_user_id(),
				self::META_KEY,
				array(
					'notice_id'   => self::NOTICE_ID,
					'php_version' => $this->php_version(),
					'time'        => time(),
				)
			);
			wp_send_json_success();
		}

		/**
		 * Style of the dialog.
		 *
		 * @return string
		 */
		private static function css() {
			return '.artws-php-security-notice{box-sizing:border-box;width:calc(100% - 32px);max-width:560px;padding:24px;border:0;border-top:4px solid #d63638;border-radius:4px;box-shadow:0 8px 32px rgba(0,0,0,.3);background:#fff;color:#1d2327}'
				. '.artws-php-security-notice::backdrop{background:rgba(0,0,0,.5)}'
				. '.artws-php-security-notice h2{margin:0 32px 12px 0;font-size:18px;line-height:1.4}'
				. '.artws-php-security-notice p{margin:0 0 12px;font-size:14px;line-height:1.7}'
				. '.artws-php-security-notice .artws-php-security-notice__actions{display:flex;flex-wrap:wrap;gap:8px;margin:20px 0 0}'
				. '.artws-php-security-notice__close{position:absolute;top:8px;right:8px;width:32px;height:32px;padding:0;border:0;background:none;color:#50575e;font-size:24px;line-height:1;cursor:pointer}'
				. '.artws-php-security-notice__close:hover,.artws-php-security-notice__close:focus{color:#1d2327}';
		}

		/**
		 * Script of the dialog: opens it, closes it, and sends "Don't show again".
		 *
		 * The close button and Esc only close the dialog on this screen.
		 *
		 * @return string
		 */
		private static function js() {
			return <<<'JS'
(function () {
	function closeDialog( dialog ) {
		if ( typeof dialog.close === 'function' ) {
			dialog.close();
		} else {
			dialog.removeAttribute( 'open' );
		}
	}
	function init() {
		var dialog = document.getElementById( 'artws-php-security-notice' );
		if ( ! dialog ) {
			return;
		}
		var close = dialog.querySelector( '.artws-php-security-notice__close' );
		var dismiss = dialog.querySelector( '.artws-php-security-notice__dismiss' );
		if ( close ) {
			close.addEventListener( 'click', function () {
				closeDialog( dialog );
			} );
		}
		if ( dismiss ) {
			dismiss.addEventListener( 'click', function () {
				if ( window.fetch && window.URLSearchParams ) {
					var body = new URLSearchParams();
					body.append( 'action', dialog.getAttribute( 'data-action' ) );
					body.append( 'nonce', dialog.getAttribute( 'data-nonce' ) );
					window.fetch( dialog.getAttribute( 'data-ajax-url' ), { method: 'POST', credentials: 'same-origin', keepalive: true, body: body } ).catch( function () {} );
				}
				closeDialog( dialog );
			} );
		}
		if ( typeof dialog.showModal === 'function' ) {
			dialog.showModal();
		} else {
			dialog.setAttribute( 'open', '' );
		}
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
JS;
		}
	}

endif;
