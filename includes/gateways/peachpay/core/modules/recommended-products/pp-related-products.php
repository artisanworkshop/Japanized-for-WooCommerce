<?php
/**
 * PeachPay Related Product Settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require plugin_dir_path( __FILE__ ) . 'includes/class-peachpay-related-products.php';

add_action(
	'admin_enqueue_scripts',
	function() {
		wp_enqueue_style(
			'peachpay-related-products',
			plugin_dir_url( __FILE__ ) . 'assets/pp-related-products.css',
			array(),
			true
		);
	}
);

/**
 * Begins execution of the plugin.
 */
function run_pp_related_products() {
	$plugin = new Peachpay_Related_Products();
	$plugin->run();
}

/**
 * Callback for selecting the type of related products to be displayed
 */
function peachpay_product_relation_cb() {
	$basedonarray = array(
		'product_cat' => __( 'Product Category', 'peachpay-for-woocommerce' ),
		'product_tag' => __( 'Product TAG', 'peachpay-for-woocommerce' ),
		'attribute'   => __( 'Product Attributes', 'peachpay-for-woocommerce' ),
	);
	?>
	<select
		name="peachpay_express_checkout_product_recommendations[peachpay_product_relation]"
		value='<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_product_relation', 'product_cat' ) ); ?>' 
	>
		<?php
		foreach ( $basedonarray as $basedon_value => $basedon_label ) {
			?>
			<option 
			value="<?php echo esc_html( $basedon_value ); ?>"
			<?php selected( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_product_relation' ), $basedon_value, true ); ?>
			>
				<?php
				echo esc_html( $basedon_label );
				?>
			</option>
			<?php
		}
		?>
	</select>
	<?php
}

/**
 * Callback for Taxonomy ids to be excluded
 */
function peachpay_exclude_id_cb() {
	?>
	<input
	type="text"
	name="peachpay_express_checkout_product_recommendations[peachpay_exclude_id]"
	value="<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_exclude_id' ) ); ?>"
	placeholder="ie 12,45,32 "/>
	<?php
}

/**
 * Displays the related product section into product page
 *
 * @param string $atts Attributes.
 */
function peachpayrpdisplay( $atts ) {
	if ( peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_nproducts' ) === 0 ) {
		return false;
	}
	// needs improvement.
	// will be removed later as it is used only to make easier the transition from 1.x to 2.x.
	$basedonf = esc_attr( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_product_relation' ) );
	if ( 'category' === $basedonf ) {
		$basedonf = 'product_cat';
	}
	if ( 'tag' === $basedonf ) {
		$basedonf = 'product_tag';
	}
	if ( 'attribute' === $basedonf ) {
		peachpayrprr_wc_taxonomy( $atts );
	} else {
		peachpayrprr_wp_taxonomy( $basedonf, $atts );
	}
}

/**
 * Display related products with relation of either category or tag
 *
 * @param string $basedonf Category or Tag relation.
 * @param string $atts Attributes.
 */
function peachpayrprr_wp_taxonomy( $basedonf, $atts ) {
	global $post;
	$started = '';
	$sc      = '';
	$terms   = get_the_terms( $post->ID, $basedonf );
	if ( ! empty( $atts['id'] ) ) {
		$sc    = 'woo-related-shortcode';
		$terms = get_the_terms( $atts['id'], $basedonf );
	} else {
		$sc = '';
	}
	if ( ! empty( $atts['title'] ) ) {
		$no_title = $atts['title'] . '-title';
	} else {
		$no_title = ''; }
	if ( empty( $terms ) ) {
		return false;
	}

	$product_based_id = array();
	foreach ( $terms as $term ) {
		$product_based_id[] = $term->term_id;
	}
	// exlude ids.
	$exclude          = explode( ',', peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_exclude_id' ) );
	$product_based_id = array_diff( $product_based_id, $exclude );

	?>
	<div class="woo-related-products-container <?php echo esc_attr( $sc ); ?>">
	<?php
	$h2title = peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_title' );
	?>
	<h2 class="woorelated-title <?php echo esc_attr( $no_title ); ?>">
									<?php
									if ( strlen( $h2title ) === 0 ) {
										esc_html_e( 'Related Products', 'peachpay-for-woocommerce' );
									} else {
										echo esc_html( peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_title' ) );
									}
									?>
	</h2>
	<?php
	$products_number = peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_nproducts' );
	if ( ! empty( $atts['number'] ) ) {
		$products_number = $atts['number'];
	}
	if ( '' !== $sc ) {
		woocommerce_product_loop_start();
		$started = 'yes';
	}
	if ( ! peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_slider' ) && 'yes' !== $started ) {
		woocommerce_product_loop_start();
		$sc      = '';
		$started = 'yes';
	}
	if ( ! empty( $atts['id'] ) && 'yes' !== $started ) {
		woocommerce_product_loop_start();
		$sc = '';
	}
	if ( peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_slider' ) && 'woo-related-shortcode' !== $sc ) {
		// needs improvement asap
		// $products_number = -1;.
		?>
		<ul id="woorelatedproducts" class="products owl-carousel owl-theme <?php echo esc_attr( $sc ); ?>">
		<?php
	}
	remove_all_filters( 'posts_orderby' );
	$args = array(
		'post_type'      => 'product',
		'post__not_in'   => array( $post->ID ),
		//phpcs:ignore
		'tax_query'      => array(
			array(
				'taxonomy' => $basedonf,
				'field'    => 'id',
				'terms'    => $product_based_id,
			),
		),
		'posts_per_page' => $products_number,
		'orderby'        => 'rand',
		//phpcs:ignore
		'meta_query'     => array(
			array(
				'key'   => '_stock_status',
				'value' => 'instock',
			),
		),
	);
	$loop = new WP_Query( $args );
	while ( $loop->have_posts() ) :
		$loop->the_post();
		if ( function_exists( 'wc_get_template_part' ) ) {
			wc_get_template_part( 'content', 'product' );
		} else {
			woocommerce_get_template_part( 'content', 'product' );
		}
	endwhile;
	if ( ! peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_slider' ) ) {
		woocommerce_product_loop_end();
	} else {
		echo '</ul>';
		echo '<div class="customNavigation">
		<a class="wprr btn prev">Previous</a> - <a class="wprr btn next">Next</a>
	</div>';
	}
	echo '</div>';
	//phpcs:ignore
	wp_reset_query();
}

/**
 * Display related product with relation set to attribute
 */
function peachpayrprr_wc_taxonomy() {
	?>
	<div>
	<?php
	$h2title = peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_title' );
	?>
	<h2>
		<?php
		if ( strlen( $h2title ) === 0 ) {
			esc_attr_e( 'Related Products', 'peachpay-for-woocommerce' );
		} else {
			echo esc_attr( peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_title' ) );
		}
		?>
	</h2>
	<?php
	$products_number = get_option( 'peachpay_related_nproducts' );
	if ( ! peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_slider' ) ) {
		woocommerce_product_loop_start();
	} else {

		// needs improvement asap.

		$products_number = - 1;
		echo "<ul id='woorelatedproducts' class='products owl-carousel owl-theme'>";
	}

	remove_all_filters( 'posts_orderby' );
	global $product,$post;
	$term_ids  = array();
	$term_idsa = array();
	$attr      = array();
	$getatt    = $product->get_attributes( $product->get_id() );
	if ( empty( $getatt ) ) {
		return false;
	}
	foreach ( $getatt as $attribute ) {
		$attr[] = $attribute['name'];
	}
	foreach ( $attr as $att ) {
		$current_term = get_the_terms( $product->get_id(), $att );
		if ( $current_term && ! is_wp_error( $current_term ) ) {
			$term_ids = array();
			foreach ( $current_term as $termid ) {
				$term_ids[] = $termid->term_id;
			}
		}

		$term_idsa[] = $term_ids;
	}
	$term_idsa       = call_user_func_array( 'array_merge', $term_idsa );
	$products_number = peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_nproducts' );
	$args            = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'post__not_in'   => array( $product->get_id() ),
		'posts_per_page' => -1,
		//phpcs:ignore
		'tax_query'      => array( peachpayrprrdtaxo( $attr, $term_idsa ) ),
		'posts_per_page' => $products_number,
		'orderby'        => 'rand',
		//phpcs:ignore
		'meta_query'     => array(
			array(
				'key'   => '_stock_status',
				'value' => 'instock',
			),
		),
	);

	$loop = new WP_Query( $args );
	while ( $loop->have_posts() ) :
		$loop->the_post();
		if ( function_exists( 'wc_get_template_part' ) ) {
			wc_get_template_part( 'content', 'product' );
		} else {
			woocommerce_get_template_part( 'content', 'product' );
		}
	endwhile;
	if ( ! peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_slider' ) ) {
		woocommerce_product_loop_end();
	} else {
		echo '</ul>';
		echo '<div class="customNavigation">
		<a class="wprr btn prev">Previous</a> - <a class="wprr btn next">Next</a>
		</div>';
	}

	echo '</div>';
	//phpcs:ignore
	wp_reset_query();
}

/**
 * Dynamic taxonomy Query build
 *
 * @param object $attr Attributes.
 * @param string $term_idsa Terms.
 */
function peachpayrprrdtaxo( $attr, $term_idsa ) {
	$tax_query = array( 'relation' => 'OR' );
	foreach ( $attr as $attrk ) {
		$tax_query[] = array(
			'taxonomy'         => $attrk,
			'field'            => 'id',
			'terms'            => $term_idsa,
			'include_children' => false,
		);

	}
	return $tax_query;
}

/**
 * Shortcode output
 *
 * @param string $atts Attributes.
 */
function peachpayrprr_shortcode_display( $atts ) {
	remove_action( 'woocommerce_after_single_product', 'peachpayrpdisplay' );
	ob_start();
	peachpayrpdisplay( $atts );
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}

add_filter( 'peachpay_register_feature', 'peachpay_rp_mini_slider_feature_flag', 10, 1 );
add_filter( 'peachpay_dynamic_feature_metadata', 'peachpay_update_rp_data', 10, 2 );

/**
 * Returns up-to-date data of recommended products.
 *
 * @param array  $feature_metadata Peachpay feature metadata.
 * @param string $cart_key The given cart key.
 */
function peachpay_update_rp_data( $feature_metadata, $cart_key ) {
	if ( '0' !== $cart_key ) {
		return $feature_metadata;
	}

	$rp_ids   = peachpay_rp_ids();
	$products = array();
	foreach ( $rp_ids as $id ) {
		$wc_product = wc_get_product( $id );
		if ( $wc_product && ( $wc_product->get_stock_status() === 'instock' || $wc_product->get_stock_status() === 'onbackorder' ) ) {
			$item = array(
				'id'         => $wc_product->get_id(),
				'name'       => $wc_product->get_name(),
				'price'      => peachpay_get_product_price_html( $wc_product ),
				'variable'   => $wc_product->is_type( 'variable' ),
				'bundle'     => $wc_product->is_type( 'bundle' ) ? peachpay_is_variation_bundle( $wc_product ) : false,
				'img_src'    => is_array( peachpay_product_image( $wc_product ) ) ? peachpay_product_image( $wc_product )[0] : '',
				'permalink'  => get_permalink( $id ),
				'attributes' => $wc_product->is_type( 'variable' ) ? peachpay_get_attribute_data( $wc_product ) : false,
				'sale'       => $wc_product->is_on_sale(),
			);
			array_push( $products, $item );
		}
	}

	$feature_metadata['recommended_products'] = array( 'recommended_products' => $products );
	return $feature_metadata;
}

/**
 * Function to add a filter to send available recommended products to checkout modal.
 *
 * @param array $data Peachpay data array.
 */
function peachpay_rp_mini_slider_feature_flag( $data ) {
	$data['recommended_products']['enabled'] = peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_rp_mini_slider' ) ? true : false;
	$data['recommended_products']['version'] = 1;

	$metadata = array(
		'recommended_products' => peachpay_recommended_products(),
		'rp_header'            => peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_rp_mini_slider_header' ),
	);

	$data['recommended_products']['metadata'] = $metadata;

	return $data;
}

/**
 * Sends related product data to the checkout window to be rendered.
 */
function peachpay_related_products_ids() {
	if ( ! peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_rp_mini_slider' ) ) {
		return false;
	}

	// Get the recent added product item in the Woocommerce cart.
	$items      = WC()->cart && ! WC()->cart->is_empty() ? WC()->cart->get_cart() : '';
	$product_id = $items ? end( $items )['product_id'] : '';

	$exclude  = explode( ',', peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_exclude_id' ) );
	$basedonf = esc_attr( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_product_relation' ) );
	if ( 'category' === $basedonf ) {
		$basedonf = 'product_cat';
	} elseif ( 'tag' === $basedonf ) {
		$basedonf = 'product_tag';
	}

	$args = array();
	if ( 'product_cat' === $basedonf || 'product_tag' === $basedonf ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return false;
		}

		$related_products = wc_get_related_products( $product_id, 4, $product->get_upsell_ids() );
		$args             = wc_products_array_orderby( $related_products, 'rand', 'desc' );

	} elseif ( 'attribute' === $basedonf ) {
		$term_ids  = array();
		$term_idsa = array();
		$attr      = array();
		$product   = wc_get_product( $product_id );
		if ( empty( $product ) ) {
			return false;
		}
		$getatt = $product->get_attributes( $product->get_id() );
		if ( empty( $getatt ) ) {
			return false;
		}
		foreach ( $getatt as $attribute ) {
			$attr[] = $attribute['name'];
		}
		foreach ( $attr as $att ) {
			$current_term = get_the_terms( $product->get_id(), $att );
			if ( $current_term && ! is_wp_error( $current_term ) ) {
				$term_ids = array();
				foreach ( $current_term as $termid ) {
					$term_ids[] = $termid->term_id;
				}
			}

			$term_idsa[] = $term_ids;
		}
		$term_idsa = call_user_func_array( 'array_merge', $term_idsa );
		$args      = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'post__not_in'   => array( $product_id ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			//phpcs:ignore
			'tax_query'      => array( peachpayrprrdtaxo( $attr, $term_idsa ) ),
				'orderby'        => 'rand',
			//phpcs:ignore
			'meta_query'     => array(
				array(
					'key'   => '_stock_status',
					'value' => 'instock',
				),
			),
			)
		);
	}

	return $args;
}

// Shortcode registration.
if ( ! shortcode_exists( 'woo-related' ) && peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_enable' ) ) {
	add_shortcode( 'woo-related', 'peachpayrprr_shortcode_display' );
}

if ( peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_enable' ) ) {
	run_pp_related_products();
	add_action( 'woocommerce_after_single_product', 'peachpayrpdisplay' );
	add_filter( 'widget_text', 'do_shortcode' );
}

/**
 * Get all linked products ids from items in the Woocommerce cart.
 */
function peachpay_get_linked_products_ids() {
	if ( ! peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'display_woocommerce_linked_products' ) ) {
		return false;
	}

	$linked_ids = array();
	if ( WC()->cart && ! WC()->cart->is_empty() ) {
		foreach ( WC()->cart->get_cart() as $wc_line_item ) {
			$wc_product     = peachpay_product_from_line_item( $wc_line_item );
			$upsell_ids     = $wc_product->get_upsell_ids();
			$cross_sell_ids = $wc_product->get_cross_sell_ids();
			array_push( $linked_ids, ...$upsell_ids, ...$cross_sell_ids );
		}
	}

	// Remove duplicate ids
	$linked_ids = array_unique( $linked_ids );

	return $linked_ids;
}

/**
 * Merge the three arrays of product ids (linked products, related products, and manually added products) into one,
 * Remove any duplicate ids, then return an array of recommended product ids.
 */
function peachpay_rp_ids() {
	$linked_ids          = peachpay_get_linked_products_ids() ? peachpay_get_linked_products_ids() : array();
	$related_product_ids = peachpay_related_products_ids() ? peachpay_related_products_ids() : array();
	$manual_ids          = peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_recommended_products_manual' ) ? peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_recommended_products_manual' ) : array();

	$recommended_products_ids = array_merge( $manual_ids, $linked_ids, $related_product_ids );
	$recommended_products_ids = array_unique( $recommended_products_ids );

	$products_number = peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_rp_nproducts' );
	// Resize the array if size of array exceeds the limit.
	$products_number && count( $recommended_products_ids ) > $products_number ? array_splice( $recommended_products_ids, $products_number ) : '';

	return $recommended_products_ids;
}

/**
 * Merge the two arrays of product ids (linked products and related products) into one,
 * Remove any duplicate ids, then return an array of recommended product data.
 */
function peachpay_recommended_products() {
	$recommended_products_ids = peachpay_rp_ids();
	$products                 = array();
	foreach ( $recommended_products_ids as $id ) {
		$wc_product = wc_get_product( $id );
		if ( $wc_product && ( $wc_product->get_stock_status() === 'instock' || $wc_product->get_stock_status() === 'onbackorder' ) ) {
			$item = array(
				'id'         => $wc_product->get_id(),
				'name'       => $wc_product->get_name(),
				'price'      => peachpay_get_product_price_html( $wc_product ),
				'variable'   => $wc_product->is_type( 'variable' ),
				'bundle'     => $wc_product->is_type( 'bundle' ) ? peachpay_is_variation_bundle( $wc_product ) : false,
				'img_src'    => is_array( peachpay_product_image( $wc_product ) ) ? peachpay_product_image( $wc_product )[0] : '',
				'permalink'  => get_permalink( $id ),
				'attributes' => $wc_product->is_type( 'variable' ) ? peachpay_get_attribute_data( $wc_product ) : false,
				'sale'       => $wc_product->is_on_sale(),
			);
			array_push( $products, $item );
		}
	}

	return $products;
}
