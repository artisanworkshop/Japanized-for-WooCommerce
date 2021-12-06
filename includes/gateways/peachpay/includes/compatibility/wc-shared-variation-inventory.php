<?php
/**
 * Support for the Shared variation inventory for Woocommerce.
 * Plugin: https://woocommerce.com/products/variation-shared-inventory-for-woocommerce/
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function peachpay_wcvsi_variation_is_managed( $variation_id ) {
	if ( 0 !== $variation_id ) {
		$parent_id = peachpay_product_parent_id( $variation_id );
		$product   = wc_get_product( $variation_id );

		// If backorders are allowed then stock can go negative. Thus never out of stock.
		if ( ! $product->backorders_allowed() ) {

			$shared_variation = get_post_meta( $parent_id, 'shared_variation_inventory', true );
			$group_id         = get_post_meta( $variation_id, 'wsvi_group_id', true );

			if ( 'yes' === $shared_variation && $group_id ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Gets the overridden product variation group stock status if the product is managed by SVI.
 *
 * @param string $stock_status The stock status.
 * @param int    $variation_id The product id or variation id of the product to get the quantity for.
 */
function peachpay_wcsvi_product_stock_status( $stock_status, $variation_id ) {
	$variation = wc_get_product( $variation_id );
	if ( peachpay_wcvsi_variation_is_managed( $variation_id ) && false === $variation->get_manage_stock() ) {
		$obj = new PeachPay_WSVI_Cart_Validation();
		if ( $obj->shared_inventory_cart_validation_helper( true, peachpay_product_parent_id( $variation_id ), 1, $variation_id ) ) {
			return 'instock';
		} else {
			return 'outofstock';
		}
	}
	return $stock_status;
}
add_filter( 'peachpay_product_stock_status', 'peachpay_wcsvi_product_stock_status', 10, 2 );



/**
 * Copy of the VSI Cart Validation class because I did not want to rerun the filters after experiencing double notices. Also slightly modified to avoid any negative side effects
 */
class PeachPay_WSVI_Cart_Validation {

	public function __construct() { }

	/**
	 * Group cart items sharing quantities to easily validate
	 *
	 * @internal
	 *
	 * @since 1.0
	 */
	private function get_shared_group_data( $parent_id, $group_id ) {

		if ( WC()->cart->is_empty() ) {
			return array();
		}

		$group = array();
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item_values ) {
			$product = $cart_item_values['data'];

			if ( 'product_variation' == $product->post_type ) {
				$item_parent_id    = $cart_item_values['product_id'];
				$item_variation_id = $cart_item_values['variation_id'];

				$item_shared_variation = get_post_meta( $item_parent_id, 'shared_variation_inventory', true );
				$item_group_id         = get_post_meta( $item_variation_id, 'wsvi_group_id', true );

				if ( $item_shared_variation && $item_parent_id == $parent_id && $item_group_id == $group_id ) {
					if ( ! empty( $group ) ) {
						$group['variations_data'][ $item_variation_id ] = array(
							'quantity'   => $cart_item_values['quantity'],
							'multiplier' => get_post_meta( $item_variation_id, 'wsvi_multiplier', true ),
						);
					} else {
						$wsvi_inventory           = get_post_meta( $item_parent_id, 'wsvi_inventory', true );
						$total_available_quantity = $wsvi_inventory[ $item_group_id ]['quantity'];

						$group['total']                                 = $total_available_quantity;
						$group['variations_data'][ $item_variation_id ] = array(
							'quantity'   => $cart_item_values['quantity'],
							'multiplier' => get_post_meta( $item_variation_id, 'wsvi_multiplier', true ),
						);
					}
				}
			}
		}

		return $group;

	}


	/**
	 * Helper function to map add-to-cart validation to update_cart validation
	 *
	 * @since 1.0
	 */
	public function shared_inventory_cart_validation_helper( $pass, $product_id, $quantity, $variation_id = 0, $variations = array() ) {
		if ( $variation_id ) {
			$values = array(
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
				'add_to_cart'  => true,
			);
			return $this->shared_inventory_cart_validation( $pass, 0, $values, $quantity );
		}

		return $pass;

	}


	/**
	 * Validate
	 *
	 * @since 1.0
	 */
	public function shared_inventory_cart_validation( $pass, $cart_item_key, $values, $quantity ) {
		if ( 0 != $values['variation_id'] ) {

			$parent_id    = $values['product_id'];
			$variation_id = $values['variation_id'];

			$product = get_product( $values['variation_id'] );

			if ( ! $product->backorders_allowed() ) {

				$shared_variation = get_post_meta( $parent_id, 'shared_variation_inventory', true );
				$group_id         = get_post_meta( $variation_id, 'wsvi_group_id', true );

				if ( 'yes' == $shared_variation && $group_id ) {
					$group = $this->get_shared_group_data( $parent_id, $group_id );

					if ( ! empty( $group ) ) {
						$variations = $group['variations_data'];
						$count      = count( $variations );

						$group_cart_quantity_total = 0;
						foreach ( $variations as $id => $variation_data ) {

							// If quantity is updated in cart, ignore this as $quantity is an updated value.
							if ( ! $values['add_to_cart'] && $variation_id == $id ) {
								continue;
							}

							$group_cart_quantity_total += ( $variation_data['quantity'] * $variation_data['multiplier'] );
						}

						$current_variation_multiplier = get_post_meta( $variation_id, 'wsvi_multiplier', true );
						$group_cart_quantity_total   += ( $current_variation_multiplier * $quantity );

					} else {
						$current_variation_multiplier = get_post_meta( $variation_id, 'wsvi_multiplier', true );
						$group_cart_quantity_total    = ( $current_variation_multiplier * $quantity );
						$variations                   = array();

						$wsvi_inventory = get_post_meta( $parent_id, 'wsvi_inventory', true );
						$group          = array(
							'total' => $wsvi_inventory[ $group_id ]['quantity'],
						);
					}

					if ( $group_cart_quantity_total > $group['total'] ) {
						return false;
					}
				}
			}
		}
		return $pass;
	}

}
