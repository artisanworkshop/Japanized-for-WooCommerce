<?php
/**
 * Settings page: feed status, end-of-life versions and manual runs.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Admin
 */
class Artws_Sec_Admin {

	/**
	 * Settings page slug (Settings > Security Hub).
	 */
	const PAGE = 'artws-security-hub';

	/**
	 * Settings API group of the EOL option.
	 */
	const SETTINGS_GROUP = 'artws_sec_feed';

	/**
	 * Registers the admin hooks.
	 */
	public static function register_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_artws_sec_run_update', array( __CLASS__, 'handle_run_update' ) );
		add_action( 'admin_post_artws_sec_accept_drop', array( __CLASS__, 'handle_accept_drop' ) );
	}

	/**
	 * Adds the page under Settings.
	 */
	public static function add_page() {
		add_options_page(
			__( 'Artws Security Hub', 'artws-security-hub' ),
			__( 'Security Hub', 'artws-security-hub' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Registers the EOL option.
	 */
	public static function register_settings() {
		register_setting(
			self::SETTINGS_GROUP,
			Artws_Sec_Feed_Updater::EOL_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_eol' ),
				'default'           => Artws_Sec_Feed_Updater::DEFAULT_EOL,
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Keeps the submitted versions that fit the manifest and the saved ones otherwise.
	 *
	 * @param mixed $input Submitted value.
	 * @return array{php: string, WordPress: string, woocommerce: string}
	 */
	public static function sanitize_eol( $input ) {
		$eol = Artws_Sec_Feed_Updater::get_eol();
		if ( ! is_array( $input ) ) {
			return $eol;
		}

		foreach ( array_keys( $eol ) as $name ) {
			if ( ! isset( $input[ $name ] ) || ! is_string( $input[ $name ] ) ) {
				continue;
			}
			// Checked against the pattern as typed: digits and dots only, nothing to strip.
			$version = trim( $input[ $name ] );
			if ( Artws_Sec_Feed_Updater::is_eol_version( $version ) ) {
				$eol[ $name ] = $version;
			} else {
				add_settings_error(
					Artws_Sec_Feed_Updater::EOL_OPTION,
					'artws_sec_invalid_' . $name,
					/* translators: %s: PHP, WordPress or WooCommerce. */
					sprintf( __( 'The %s version must be numbers separated by dots, such as 8.2. The previous value was kept.', 'artws-security-hub' ), self::eol_labels()[ $name ] )
				);
			}
		}
		return $eol;
	}

	/**
	 * Schedules a run right away.
	 */
	public static function handle_run_update() {
		self::check_request( 'artws_sec_run_update' );

		// The "manual" argument keeps WordPress from treating it as a duplicate of the daily event.
		wp_schedule_single_event( time(), Artws_Sec_Feed_Updater::UPDATE_HOOK, array( 'manual' ) );
		self::redirect( 'scheduled' );
	}

	/**
	 * Lets the next run publish fewer records than the last one.
	 */
	public static function handle_accept_drop() {
		self::check_request( 'artws_sec_accept_drop' );

		update_option( Artws_Sec_Feed_Updater::ACCEPT_DROP_OPTION, 1, false );
		self::redirect( 'drop_accepted' );
	}

	/**
	 * Renders the settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$state  = Artws_Sec_Feed_Updater::get_state();
		$status = Artws_Sec_Feed_Updater::get_status();
		$eol    = Artws_Sec_Feed_Updater::get_eol();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only selects which message to show after a redirect.
		$notice = isset( $_GET['artws_sec_notice'] ) ? sanitize_key( wp_unslash( $_GET['artws_sec_notice'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Artws Security Hub', 'artws-security-hub' ); ?></h1>

			<?php if ( 'scheduled' === $notice ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'The update is scheduled and runs with the next WP-Cron request.', 'artws-security-hub' ); ?></p></div>
			<?php elseif ( 'drop_accepted' === $notice ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'The next update may publish fewer records than the last one.', 'artws-security-hub' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Vulnerability feed', 'artws-security-hub' ); ?></h2>
			<table class="widefat striped" style="max-width: 60em;">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Directory', 'artws-security-hub' ); ?></th>
						<td><code><?php echo esc_html( Artws_Sec_Feed_Publisher::directory() ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Published version', 'artws-security-hub' ); ?></th>
						<td>
							<?php
							if ( null === $state ) {
								esc_html_e( 'Nothing published yet.', 'artws-security-hub' );
							} else {
								echo esc_html( $state['version'] . ' (' . $state['generated_at'] . ')' );
							}
							?>
						</td>
					</tr>
					<?php if ( null !== $state ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Records', 'artws-security-hub' ); ?></th>
							<td>
								<?php
								foreach ( $state['files'] as $type => $file ) {
									echo esc_html( $type . ': ' . number_format_i18n( (int) $file['count'] ) ) . '<br>';
								}
								?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Stale', 'artws-security-hub' ); ?></th>
							<td><?php echo $state['stale'] ? esc_html__( 'Yes: clients are told the data is out of date.', 'artws-security-hub' ) : esc_html__( 'No', 'artws-security-hub' ); ?></td>
						</tr>
					<?php endif; ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Last run', 'artws-security-hub' ); ?></th>
						<td>
							<?php
							if ( empty( $status['last_attempt'] ) ) {
								esc_html_e( 'Not run yet.', 'artws-security-hub' );
							} else {
								echo esc_html( gmdate( 'Y-m-d H:i:s', (int) $status['last_attempt'] ) . ' UTC: ' . ( $status['result'] ?? '' ) );
								if ( ! empty( $status['message'] ) ) {
									echo '<br>' . esc_html( $status['message'] );
								}
							}
							?>
						</td>
					</tr>
					<?php if ( ! empty( $status['stats'] ) ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Last run counts', 'artws-security-hub' ); ?></th>
							<td>
								<?php
								$stats = $status['stats'];
								printf(
									/* translators: 1: V3 records read, 2: informational records, 3: feed records checked, 4: feed records left out, 5: copyright conflicts. */
									esc_html__( 'Wordfence records: %1$s / informational: %2$s / feed records checked: %3$s / left out: %4$s / copyright conflicts: %5$s', 'artws-security-hub' ),
									esc_html( number_format_i18n( (int) ( $stats['records'] ?? 0 ) ) ),
									esc_html( number_format_i18n( (int) ( $stats['informational'] ?? 0 ) ) ),
									esc_html( number_format_i18n( (int) ( $stats['candidates'] ?? 0 ) ) ),
									esc_html( number_format_i18n( (int) ( $stats['excluded'] ?? 0 ) ) ),
									esc_html( number_format_i18n( (int) ( $stats['copyright_conflicts'] ?? 0 ) ) )
								);
								foreach ( (array) ( $stats['excluded_reasons'] ?? array() ) as $reason => $count ) {
									echo '<br>' . esc_html( $reason . ': ' . number_format_i18n( (int) $count ) );
								}
								?>
							</td>
						</tr>
					<?php endif; ?>
					<?php if ( ! empty( $status['stats']['excluded_samples'] ) ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Left-out examples', 'artws-security-hub' ); ?></th>
							<td>
								<?php
								foreach ( $status['stats']['excluded_samples'] as $sample ) {
									echo esc_html( ( $sample['id'] ?? '' ) . ' ' . ( $sample['slug'] ?? '' ) . ': ' . ( $sample['reason'] ?? '' ) ) . '<br>';
								}
								?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<p>
				<?php self::render_action_button( 'artws_sec_run_update', __( 'Update now', 'artws-security-hub' ), 'primary' ); ?>
				<?php self::render_action_button( 'artws_sec_accept_drop', __( 'Allow fewer records on the next update', 'artws-security-hub' ), 'secondary' ); ?>
			</p>

			<h2><?php esc_html_e( 'End of life', 'artws-security-hub' ); ?></h2>
			<p><?php esc_html_e( 'Sites running a version older than these are reported as end of life (manifest "eol").', 'artws-security-hub' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( self::SETTINGS_GROUP ); ?>
				<table class="form-table" role="presentation">
					<?php
					foreach ( self::eol_labels() as $name => $label ) :
						$field_id = 'artws-sec-eol-' . $name;
						?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label></th>
							<td>
								<input type="text" class="small-text" id="<?php echo esc_attr( $field_id ); ?>"
									name="<?php echo esc_attr( Artws_Sec_Feed_Updater::EOL_OPTION . '[' . $name . ']' ); ?>"
									value="<?php echo esc_attr( $eol[ $name ] ); ?>" pattern="[0-9]+(\.[0-9]+)*" required>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Names of the software in the EOL settings.
	 *
	 * @return array<string, string> Settings key => name.
	 */
	private static function eol_labels() {
		return array(
			'php'         => __( 'PHP', 'artws-security-hub' ),
			'wordpress'   => __( 'WordPress', 'artws-security-hub' ),
			'woocommerce' => __( 'WooCommerce', 'artws-security-hub' ),
		);
	}

	/**
	 * Renders a button that posts to admin-post.php.
	 *
	 * @param string $action admin-post action, also used as the nonce action.
	 * @param string $label  Button text.
	 * @param string $type   Button class: primary or secondary.
	 */
	private static function render_action_button( $action, $label, $type ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block; margin-right: 8px;">
			<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
			<?php wp_nonce_field( $action ); ?>
			<?php submit_button( $label, $type, 'submit', false ); ?>
		</form>
		<?php
	}

	/**
	 * Stops unless the request comes from an administrator with a valid nonce.
	 *
	 * @param string $action Nonce action.
	 */
	private static function check_request( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'artws-security-hub' ), 403 );
		}
		check_admin_referer( $action );
	}

	/**
	 * Returns to the settings page with a message.
	 *
	 * @param string $notice Message key.
	 */
	private static function redirect( $notice ) {
		wp_safe_redirect( add_query_arg( 'artws_sec_notice', $notice, admin_url( 'options-general.php?page=' . self::PAGE ) ) );
		exit;
	}
}
