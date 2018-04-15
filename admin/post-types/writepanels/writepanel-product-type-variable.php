<?php
/**
 * WooCommerce Measurement Price Calculator
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Measurement Price Calculator to newer
 * versions in the future. If you wish to customize WooCommerce Measurement Price Calculator for your
 * needs please refer to http://docs.woocommerce.com/document/measurement-price-calculator/ for more information.
 *
 * @package   WC-Measurement-Price-Calculator/Admin/Write-Panels
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Product Data Panel - Product Variations
 *
 * Functions to modify the Product Data Panel - Variations panels to add the
 * measurement price calculator area/volume fields
 */

add_action( 'woocommerce_variation_options_dimensions', 'wc_price_calculator_product_after_variable_attributes', 10, 3 );

/**
 * Display our custom product Area/Volume meta fields in the product
 * variation form
 *
 * @param int $loop the loop index
 * @param array $variation_data the variation data
 * @param WP_Post $variation_post the variation post object
 */
function wc_price_calculator_product_after_variable_attributes( $loop, $variation_data, $variation_post ) {
	global $post;

	$parent_product = wc_get_product( $post );

	// add meta data to $variation_data array
	if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {
		$variation_product = wc_get_product( $variation_post );
		$variation_data    = $variation_product ? array_merge( $variation_product->get_meta_data(), $variation_data ) : $variation_data;
	} else {
		$variation_data = array_merge( get_post_meta( $variation_post->ID ), $variation_data );
	}

	// will use the parent area/volume (if set) as the placeholder
	$parent_data = array(
		'area'   => $post ? SV_WC_Product_Compatibility::get_meta( $parent_product, '_area', true )   : null,
		'volume' => $post ? SV_WC_Product_Compatibility::get_meta( $parent_product, '_volume', true ) : null,
	);

	// default placeholders
	if ( ! $parent_data['area'] )   $parent_data['area']   = '0.00';
	if ( ! $parent_data['volume'] ) $parent_data['volume'] = '0';

	?>
		<p class="form-row form-row-first hide_if_variation_virtual">
			<label>
				<?php echo esc_html( 'Area', 'woocommerce-measurement-price-calculator' ) . ' (' . esc_html( get_option( 'woocommerce_area_unit' ) ) . '):'; ?>
				<?php echo wc_help_tip( __( 'Overrides the area calculated from the width/length dimensions for the Measurements Price Calculator.', 'woocommerce-measurement-price-calculator' ) ); ?>
			</label>
			<input type="number" size="5" name="variable_area[<?php echo $loop; ?>]" value="<?php if ( isset( $variation_data['_area'][0] ) ) echo esc_attr( $variation_data['_area'][0] ); ?>" placeholder="<?php echo $parent_data['area']; ?>" step="any" min="0" />
		</p>
		<p class="form-row form-row-last hide_if_variation_virtual">
			<label>
				<?php echo esc_html( 'Volume', 'woocommerce-measurement-price-calculator' ) . ' (' . esc_html( get_option( 'woocommerce_volume_unit' ) ) . '):'; ?>
				<?php echo wc_help_tip( __( 'Overrides the volume calculated from the width/length/height dimensions for the Measurements Price Calculator.', 'woocommerce-measurement-price-calculator' ) ); ?>
			</label>
			<input type="number" size="5" name="variable_volume[<?php echo $loop; ?>]" value="<?php if ( isset( $variation_data['_volume'][0] ) ) echo esc_attr( $variation_data['_volume'][0] ); ?>" placeholder="<?php echo $parent_data['volume']; ?>" step="any" min="0" />
		</p>
	<?php
}


add_action( 'woocommerce_process_product_meta_variable', 'wc_measurement_price_calculator_process_product_meta_variable' );
add_action( 'woocommerce_ajax_save_product_variations',  'wc_measurement_price_calculator_process_product_meta_variable' );

/**
 * Save the variable product options.
 *
 * @param mixed $post_id the post identifier
 */
function wc_measurement_price_calculator_process_product_meta_variable( $post_id ) {

	if ( isset( $_POST['variable_sku'] ) ) {

		$variable_post_id = isset( $_POST['variable_post_id'] ) ? $_POST['variable_post_id'] : array();
		$variable_area    = isset( $_POST['variable_area'] )    ? $_POST['variable_area'] : '';
		$variable_volume  = isset( $_POST['variable_volume'] )  ? $_POST['variable_volume'] : '';

		// bail if $variable_post_id is not as expected
		if ( ! is_array( $variable_post_id ) ) {
			return;
		}

		$max_loop = max( array_keys( $variable_post_id ) );

		for ( $i = 0; $i <= $max_loop; $i++ ) {

			if ( ! isset( $variable_post_id[ $i ] ) ) continue;

			$variation_id = (int) $variable_post_id[ $i ];
			$variation    = wc_get_product( $variation_id );

			// Update area post meta
			if ( empty( $variable_area[ $i ] ) ) {
				SV_WC_Product_Compatibility::delete_meta_data( $variation, '_area' );
			} else {
				SV_WC_Product_Compatibility::update_meta_data( $variation, '_area', $variable_area[ $i ] );
			}

			// Update volume post meta
			if ( empty( $variable_volume[ $i ] ) ) {
				SV_WC_Product_Compatibility::delete_meta_data( $variation, '_volume' );
			} else {
				SV_WC_Product_Compatibility::update_meta_data( $variation, '_volume', $variable_volume[ $i ] );
			}
		}
	}
}
