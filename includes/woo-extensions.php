<?php
/**
 * Welcome Page for the theme
 *
 * @package cactus
 */

class CT_Woo_Extension_Color_Swatch
{
	private static $instance;

	public static function getInstance()
	{
		if (null == self::$instance) {
			self::$instance = new CT_Woo_Extension_Color_Swatch();
		}

		return self::$instance;
	}

	public static $page_slug = 'cactus-woocommerce-template';

	public function setup()
	{
		/**
		 * extends product attributes. If WooCommerce add-on is used (https://woocommerce.com/products/variation-swatches-and-photos/), this will not needed
		 */
		add_filter('woocommerce_dropdown_variation_attribute_options_html', array( $this, 'woocommerce_dropdown_variation_attribute_options_html'), 10, 2);
		add_filter('product_attributes_type_selector', array( $this, 'product_attributes_type') );
		add_action('woocommerce_product_option_terms', array( $this, 'product_option_terms'), 10, 2 );
	}
	
	/**
	 * extends HTML product attribute box for 'image' attribute type
	 */
	function product_option_terms( $attribute_taxonomy, $i ){
		
		if ( 'image' === $attribute_taxonomy->attribute_type ) : 
			
			$taxonomy = 'pa_' . $attribute_taxonomy->attribute_name;
			
			?>

			<select multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select terms', 'cactus' ); ?>" class="multiselect attribute_values wc-enhanced-select" name="attribute_values[<?php echo esc_attr($i); ?>][]">
				<?php
				$args = array(
					'orderby'    => 'name',
					'hide_empty' => 0
				);
				$all_terms = get_terms( $taxonomy, apply_filters( 'woocommerce_product_attribute_terms', $args ) );
				if ( $all_terms ) {
					foreach ( $all_terms as $term ) {
						echo '<option value="' . esc_attr( $term->term_id ) . '" ' . selected( has_term( absint( $term->term_id ), $taxonomy, $thepostid ), true, false ) . '>' . esc_attr( apply_filters( 'woocommerce_product_attribute_term_name', $term->name, $term ) ) . '</option>';
					}
				}
				?>
			</select>
			<button class="button plus select_all_attributes"><?php esc_html_e( 'Select all', 'cactus' ); ?></button>
			<button class="button minus select_no_attributes"><?php esc_html_e( 'Select none', 'cactus' ); ?></button>
			<button class="button fr plus add_new_attribute"><?php esc_html_e( 'Add new', 'cactus' ); ?></button>
		<?php
		
		endif;
	}
	
	/**
	 * Add more product attritube type
	 */
	function product_attributes_type( $types ){
		$types = array_merge( $types, array('image' => esc_html__('Image', 'cactus')) );

		return $types;
	}
	
	/**
	 * Change product variations from SELECT to Image Switcher
	 */
	function woocommerce_dropdown_variation_attribute_options_html( $html, $args ){

		$product               = $args['product'];
		$attribute             = $args['attribute'];
		
		$att_name = str_replace('pa_', '', $attribute);
		
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare("SELECT attribute_type FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name = %s", $att_name ) );
		if($row){
			$attribute_type = $row->attribute_type;
		} else {
			$attribute_type = '';
		}

		if($product && taxonomy_exists( $attribute ) && $attribute_type == 'image'){
			$options               = $args['options'];
			$name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
			$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
			$class                 = $args['class'];
			$show_option_none      = $args['show_option_none'] ? true : false;
			$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : esc_html__( 'Choose an option', 'cactus' ); // We'll do our best to hide the placeholder, but we'll need to show something when resetting options.

			if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
				$attributes = $product->get_variation_attributes();
				$options    = $attributes[ $attribute ];
			}
			
			$html = '<div id="picker_' . esc_attr($args['attribute']) . '" class="ct-woo-extension select swatch-control">';

			$html .= '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '"' . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
			$html .= '<option value="">' . esc_html( $show_option_none_text ) . '</option>';
			
			$image_item = '';

			if ( ! empty( $options ) ) {
				if ( $product && taxonomy_exists( $attribute ) ) {
					// Get terms if this is a taxonomy - ordered. We need the names too.
					$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );

					foreach ( $terms as $term ) {
						if ( in_array( $term->slug, $options ) ) {
							$html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) ) . '</option>';
							
							$background = '';
							$image_id = get_term_meta($term->term_id, 'category-image-id', true);
							$background = wp_get_attachment_image($image_id, 'color-swatch');
							
							if($background != ''){
								$image_item .= '<div class="select-option swatch-wrapper ' . (sanitize_title( $args['selected'] ) == $term->slug ? 'active' : '') . '" data-attribute="' . esc_attr($args['attribute']) . '" data-value="' . esc_attr( $term->slug ) . '">' . $background . '</div>';
							} else {
								$image_item .= '<div class="select-option swatch-wrapper ' . (sanitize_title( $args['selected'] ) == $term->slug ? 'active' : '') . '" data-attribute="' . esc_attr($args['attribute']) . '" data-value="' . esc_attr( $term->slug ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) ) . '</div>';
							}
							
						}
					}
				} else {
					foreach ( $options as $option ) {
						// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
						$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
						$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
					}
				}
			}

			$html .= '</select>';
			
			$html .= $image_item;
			
			$html .= '</div>';
		}
		
		return $html;
	}
}