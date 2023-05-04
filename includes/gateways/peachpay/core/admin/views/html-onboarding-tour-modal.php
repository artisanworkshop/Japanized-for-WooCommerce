<?php
/**
 * PeachPay Admin settings onboarding tour modal.
 *
 * @var array $todo_items Array of todo item structures:
 *  @var array $todo_item The entire item with the following content:
 * - @var bool $checked If this to-do item has been completed.
 * - @var bool $hidden If the given element should not be displayed.
 * - @var string $id (OPTIONAL) The id of this element for the HTML.
 * - @var string $title The title of this to-do item.
 * - @var string $description The description of this to-do item.
 * - @var string|null $href (OPTIONAL) Provide a link to the specific link.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;
?>

<div id='pp-onboarding-tour-modal'>
	<div class='pp-onboarding-tour-title'>
		<h4><?php echo esc_html( $todo_items['meta']['onboarding_tour_title'] ); ?></h4>
		<span id='onboarding-tour-modal-toggle' class='onboarding-tour-modal-toggle'><?php require PeachPay::get_plugin_path() . 'public/img/chevron-down-solid.svg'; ?></span>
	</div>
	<div class='pp-onboarding-tour-description'>
		<p><?php echo esc_html( $todo_items['meta']['onboarding_tour_description'] ); ?></p>
	</div>
	<div class='pp-onboarding-tour-body'>
		<?php
		foreach ( $todo_items as $todo_key => $todo_item ) {
			if ( 'meta' === $todo_key ) {
				continue;
			}

			$checked     = $todo_item['checked'];
			$hidden      = array_key_exists( 'hidden', $todo_item ) ? $todo_item['hidden'] : false;
			$todo_id     = array_key_exists( 'id', $todo_item ) ? $todo_item['id'] : null;
			$todo_title  = $todo_item['title'];
			$description = $todo_item['description'];
			$href        = array_key_exists( 'href', $todo_item ) ? $todo_item['href'] : null;

			?>
				<a <?php echo $todo_id ? 'id=\'' . esc_html( $todo_id ) . '\'' : ''; ?> class='pp-onboarding-tour-todo'
					<?php echo $href ? 'href=\'' . esc_html( $href ) . '\'' : ''; ?>
					<?php echo $hidden ? 'style=\'display:none\'' : ''; ?>>
					<div class='pp-onboarding-tour-todo-header'>
						<div class='pp-onboarding-tour-todo-checked <?php echo $checked ? 'checked' : ''; ?>'>
							<?php $checked ? require PeachPay::get_plugin_path() . '/public/img/checkmark-square-solid.svg' : ''; ?>
						</div>
						<div class='pp-onboarding-tour-todo-title'><?php echo esc_html( $todo_title ); ?></div>
					</div>
					<div class='pp-onboarding-tour-todo-description'>
						<p><?php echo esc_html( $description ); ?></p>
					</div>
				</a>
			<?php
		}
		?>
		<div id='pp-onboarding-tour-end'><a><?php echo esc_html_e( 'Don\'t show this again', 'peachpay-for-woocommerce' ); ?></a></div>
	</div>
</div>
<script style="display:none">
	const hasLinkedPaymentMethod = <?php echo( $todo_items['connect-payment']['checked'] ? 'true' : 'false' ); ?>;
	document.addEventListener('DOMContentLoaded', () => {
		const $onboardingModal = document.querySelector('#pp-onboarding-tour-modal');

		if (!hasLinkedPaymentMethod) {
			$onboardingModal.querySelector('#pp-onboarding-tour-manage-first-gateway').style.display = 'none';
		}

		$onboardingModal.addEventListener('scroll', function () {
			if (this.scrollTop > 8 ) {
				$onboardingModal.querySelector('.pp-onboarding-tour-title').classList.add('scrolled');
			} else {
				$onboardingModal.querySelector('.pp-onboarding-tour-title').classList.remove('scrolled');
			}
		});
		$onboardingModal.querySelector('.pp-onboarding-tour-title').addEventListener('click', () => {
			$onboardingModal.classList.toggle('open');
		});

		let url = new URL(window.location.href);
		url.searchParams.append('onboarding', 'force_complete');
		$onboardingModal.querySelector('#pp-onboarding-tour-end a').setAttribute('href', url.href);
	})
</script>
<?php
