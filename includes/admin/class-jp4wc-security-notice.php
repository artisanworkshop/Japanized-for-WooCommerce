<?php
/**
 * Admin notice of the security check.
 *
 * @package Japanized_For_WooCommerce
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/class-jp4wc-security-feed.php';

/**
 * JP4WC_Security_Notice
 *
 * Tells the users who manage WooCommerce which installed software has known
 * vulnerabilities (and when the vulnerability data is out of date). A user who
 * closes the notice does not see it again until the findings change.
 */
class JP4WC_Security_Notice {

	/**
	 * User meta with the fingerprint of the findings the user closed the notice for.
	 */
	const DISMISSED_META = 'jp4wc_security_notice_dismissed';

	/**
	 * Ajax action and nonce action of closing the notice.
	 */
	const AJAX_ACTION = 'jp4wc_security_notice_dismiss';

	/**
	 * Handle of the inline script and style.
	 */
	const HANDLE = 'jp4wc-security-notice';

	/**
	 * Records listed in the notice; the rest are counted.
	 */
	const MAX_RECORDS = 20;

	/**
	 * Registers the hooks.
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'render' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_dismiss' ) );
	}

	/**
	 * Returns what the notice shows to the current user.
	 *
	 * @return array|null Null when no notice is shown. Otherwise findings (null
	 *                    when no data could be downloaded), stale and fingerprint.
	 */
	public static function get_view() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! JP4WC_Security_Feed::is_enabled() ) {
			return null;
		}
		$findings = JP4WC_Security_Feed::get_findings();
		if ( null === $findings ) {
			// No data yet: say so once the syncs have failed for days, or the check would stay silent.
			if ( ! JP4WC_Security_Feed::is_unavailable() ) {
				return null;
			}
			$fingerprint = sha1( 'unavailable' );
			$stale       = false;
		} else {
			$stale = JP4WC_Security_Feed::is_stale( $findings );
			if ( array() === $findings['items'] && ! $stale ) {
				return null;
			}
			$fingerprint = self::fingerprint( $findings['items'], $stale );
		}

		if ( get_user_meta( get_current_user_id(), self::DISMISSED_META, true ) === $fingerprint ) {
			return null;
		}

		return array(
			'findings'    => $findings,
			'stale'       => $stale,
			'fingerprint' => $fingerprint,
		);
	}

	/**
	 * Identifies what the notice reports: the affected copies, everything it
	 * shows of their records, and whether the data is out of date. A record
	 * whose severity or fix changes shows the closed notice again.
	 *
	 * @param array $items Affected copies.
	 * @param bool  $stale Whether the data is out of date.
	 * @return string SHA-1 in hex.
	 */
	public static function fingerprint( array $items, $stale ) {
		$keys = array();
		foreach ( $items as $item ) {
			foreach ( $item['vulnerabilities'] as $vulnerability ) {
				$keys[] = (string) wp_json_encode(
					array(
						$item['type'],
						$item['slug'],
						$item['name'],
						$item['version'],
						$vulnerability['id'],
						$vulnerability['title'],
						$vulnerability['reference'],
						$vulnerability['rating'],
						$vulnerability['patched'],
						$vulnerability['patched_versions'],
					)
				);
			}
		}
		sort( $keys );
		$keys[] = $stale ? 'stale' : 'fresh';
		return sha1( implode( "\n", $keys ) );
	}

	/**
	 * Adds the script that remembers a closed notice, when the notice is shown.
	 */
	public static function enqueue() {
		if ( null === self::get_view() ) {
			return;
		}

		wp_register_style( self::HANDLE, false, array(), JP4WC_VERSION );
		wp_enqueue_style( self::HANDLE );
		wp_add_inline_style(
			self::HANDLE,
			'.jp4wc-security-notice ul.jp4wc-security-items{list-style:disc;margin-left:2em}'
			. '.jp4wc-security-notice ul.jp4wc-security-items ul{list-style:circle;margin:.25em 0 .5em 1.5em}'
			. '.jp4wc-security-notice details{margin:0 0 .5em}'
		);

		wp_register_script( self::HANDLE, false, array(), JP4WC_VERSION, true );
		wp_enqueue_script( self::HANDLE );
		wp_add_inline_script( self::HANDLE, self::script() );
	}

	/**
	 * Returns the script that sends the fingerprint of a closed notice.
	 *
	 * Static: the values it sends are read from the notice's data attributes.
	 *
	 * @return string
	 */
	private static function script() {
		return <<<'JS'
document.addEventListener( 'click', function ( event ) {
	var button = event.target.closest ? event.target.closest( '.jp4wc-security-notice .notice-dismiss' ) : null;
	if ( ! button || ! window.ajaxurl || ! window.fetch ) {
		return;
	}
	var notice = button.closest( '.jp4wc-security-notice' );
	var body = new URLSearchParams();
	body.append( 'action', notice.getAttribute( 'data-action' ) );
	body.append( 'nonce', notice.getAttribute( 'data-nonce' ) );
	body.append( 'fingerprint', notice.getAttribute( 'data-fingerprint' ) );
	window.fetch( window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } );
} );
JS;
	}

	/**
	 * Prints the notice.
	 */
	public static function render() {
		$view = self::get_view();
		if ( null === $view ) {
			return;
		}
		$findings = $view['findings'];
		$severe   = null !== $findings && JP4WC_Security_Scanner::rating_rank( $findings['max_severity'] ) >= JP4WC_Security_Scanner::rating_rank( 'High' );

		printf(
			'<div class="notice %1$s is-dismissible jp4wc-security-notice" data-action="%2$s" data-nonce="%3$s" data-fingerprint="%4$s">',
			esc_attr( $severe ? 'notice-error' : 'notice-warning' ),
			esc_attr( self::AJAX_ACTION ),
			esc_attr( wp_create_nonce( self::AJAX_ACTION ) ),
			esc_attr( $view['fingerprint'] )
		);
		echo '<p><strong>' . esc_html__( 'Japanized for WooCommerce security check', 'woocommerce-for-japan' ) . '</strong></p>';

		if ( null === $findings ) {
			echo '<p>' . esc_html(
				sprintf(
					/* translators: %s: host name of the vulnerability feed, e.g. wc.artws.info */
					__( 'The vulnerability data could not be downloaded, so the installed plugins, themes and WordPress are not being checked. Make sure this server can connect to %s, or turn the vulnerability check off in the JP4WC settings.', 'woocommerce-for-japan' ),
					(string) wp_parse_url( JP4WC_Security_Feed::base_url(), PHP_URL_HOST )
				)
			) . '</p>';
			self::render_tab_link();
			echo '</div>';
			return;
		}
		if ( array() !== $findings['items'] ) {
			self::render_items( $findings );
		}
		if ( $view['stale'] ) {
			echo '<p>' . esc_html__( 'The vulnerability data has not been updated for a while, so vulnerabilities disclosed since then are not checked.', 'woocommerce-for-japan' ) . '</p>';
		}
		self::render_tab_link();
		self::render_credits( $findings );

		echo '</div>';
	}

	/**
	 * Prints the affected software and its records.
	 *
	 * @param array $findings Findings.
	 */
	private static function render_items( array $findings ) {
		$count = (int) $findings['count'];
		echo '<p>';
		echo esc_html(
			sprintf(
				/* translators: %d: number of vulnerabilities */
				_n(
					'%d known vulnerability was found in the software installed on this site.',
					'%d known vulnerabilities were found in the software installed on this site.',
					$count,
					'woocommerce-for-japan'
				),
				$count
			)
		);
		echo ' ' . esc_html__( 'Update the affected plugins, themes or WordPress to a fixed version, or remove the ones that have no fix.', 'woocommerce-for-japan' );
		echo '</p>';

		$shown = 0;
		echo '<ul class="jp4wc-security-items">';
		foreach ( $findings['items'] as $item ) {
			if ( $shown >= self::MAX_RECORDS ) {
				break;
			}
			printf(
				'<li><a href="%1$s">%2$s</a> %3$s<ul>',
				esc_url( self::tab_url() ),
				esc_html( $item['name'] ),
				esc_html( $item['version'] )
			);
			foreach ( $item['vulnerabilities'] as $vulnerability ) {
				if ( $shown >= self::MAX_RECORDS ) {
					break;
				}
				++$shown;
				// The data license asks for a link to the record wherever it is shown.
				printf(
					'<li><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>%3$s</li>',
					esc_url( $vulnerability['reference'] ),
					esc_html( $vulnerability['title'] ),
					esc_html( self::record_details( $vulnerability ) )
				);
			}
			echo '</ul></li>';
		}
		echo '</ul>';

		if ( $count > $shown ) {
			$more = $count - $shown;
			echo '<p>' . esc_html(
				sprintf(
					/* translators: %d: number of vulnerabilities not listed */
					_n( 'and %d more', 'and %d more', $more, 'woocommerce-for-japan' ),
					$more
				)
			) . '</p>';
		}
	}

	/**
	 * Returns the severity and fixed versions of a record, as text after its title.
	 *
	 * @param array $vulnerability Normalized record.
	 * @return string
	 */
	private static function record_details( array $vulnerability ) {
		$details = array();

		$labels = array(
			'Critical' => _x( 'Critical', 'vulnerability severity', 'woocommerce-for-japan' ),
			'High'     => _x( 'High', 'vulnerability severity', 'woocommerce-for-japan' ),
			'Medium'   => _x( 'Medium', 'vulnerability severity', 'woocommerce-for-japan' ),
			'Low'      => _x( 'Low', 'vulnerability severity', 'woocommerce-for-japan' ),
			'None'     => _x( 'None', 'vulnerability severity', 'woocommerce-for-japan' ),
		);
		if ( isset( $labels[ $vulnerability['rating'] ] ) ) {
			/* translators: %s: severity of a vulnerability, e.g. High */
			$details[] = sprintf( __( 'Severity: %s', 'woocommerce-for-japan' ), $labels[ $vulnerability['rating'] ] );
		}

		if ( ! $vulnerability['patched'] ) {
			$details[] = __( 'No fixed version yet', 'woocommerce-for-japan' );
		} elseif ( array() !== $vulnerability['patched_versions'] ) {
			/* translators: %s: comma-separated versions that fix the vulnerability */
			$details[] = sprintf( __( 'Fixed in %s', 'woocommerce-for-japan' ), implode( ', ', $vulnerability['patched_versions'] ) );
		}

		return array() === $details ? '' : ' (' . implode( ' / ', $details ) . ')';
	}

	/**
	 * Returns the URL of the Security tab of the JP4WC settings, which shows
	 * the findings in full and links each affected copy to where it is updated.
	 *
	 * @return string
	 */
	public static function tab_url() {
		return add_query_arg(
			array(
				'page' => 'jp4wc-settings',
				'tab'  => 'security',
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Prints the link to the Security tab.
	 */
	private static function render_tab_link() {
		printf(
			'<p><a href="%1$s">%2$s</a></p>',
			esc_url( self::tab_url() ),
			esc_html__( 'Open the Security tab', 'woocommerce-for-japan' )
		);
	}

	/**
	 * Prints the credit line and the copyright notices of the data, as its license asks.
	 *
	 * @param array $findings Findings.
	 */
	private static function render_credits( array $findings ) {
		echo '<div class="jp4wc-security-credits">';
		if ( isset( $findings['attribution'] ) && is_string( $findings['attribution'] ) && '' !== $findings['attribution'] ) {
			echo '<p class="description">' . esc_html( $findings['attribution'] ) . '</p>';
		}
		if ( isset( $findings['copyrights'] ) && is_array( $findings['copyrights'] ) ) {
			foreach ( $findings['copyrights'] as $copyright ) {
				printf(
					'<details><summary>%1$s</summary><p>%2$s</p><p><a href="%3$s" target="_blank" rel="noopener noreferrer">%4$s</a></p></details>',
					esc_html( $copyright['notice'] ),
					esc_html( $copyright['license'] ),
					esc_url( $copyright['license_url'] ),
					esc_html__( 'License', 'woocommerce-for-japan' )
				);
			}
		}
		echo '</div>';
	}

	/**
	 * Remembers that the current user closed the notice (Ajax).
	 */
	public static function ajax_dismiss() {
		check_ajax_referer( self::AJAX_ACTION, 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( null, 403 );
		}
		$fingerprint = isset( $_POST['fingerprint'] ) ? sanitize_text_field( wp_unslash( $_POST['fingerprint'] ) ) : '';
		if ( 1 !== preg_match( '/^[0-9a-f]{40}$/D', $fingerprint ) ) {
			wp_send_json_error( null, 400 );
		}
		update_user_meta( get_current_user_id(), self::DISMISSED_META, $fingerprint );
		wp_send_json_success();
	}
}

JP4WC_Security_Notice::init();
