<?php
 /**
  * Taxonomy Meta for WooCommerce Attribute. thanks to https://catapultthemes.com/adding-an-image-upload-field-to-categories/
  **/
if ( ! class_exists( 'CT_Woo_Tax_Meta' ) ) {

class CT_Woo_Tax_Meta {

	public function __construct() {
		// nothing to do here
	}
 
	 /**
	  * Initialize the class and start calling our hooks and filters
	  * @since 1.0.0
	  */
	public function init() {
		 
		global $wpdb;
		
		$rows = $wpdb->get_results( "SELECT attribute_name FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_type = 'image'" );
		if($rows){
			foreach($rows as $row){
				$taxonomy = 'pa_' . $row->attribute_name;
				
				add_action( "{$taxonomy}_add_form_fields", array ( $this, 'add_category_image' ) );
				add_action( "created_{$taxonomy}", array ( $this, 'save_category_image' ), 10, 2 );
				add_action( "{$taxonomy}_edit_form_fields", array ( $this, 'update_category_image' ), 10, 2 );
				add_action( "edited_{$taxonomy}", array ( $this, 'updated_category_image' ), 10, 2 );
				add_action( 'admin_footer', array ( $this, 'add_script' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'load_wp_media_files' ));
				
				add_filter( "manage_edit-{$taxonomy}_columns", array( $this, 'taxonomy_columns' ));
				add_filter( "manage_{$taxonomy}_custom_column", array( $this, 'taxonomy_column_content' ), 10, 3);
			}
		}
	}
	
	function taxonomy_columns( $columns ){
		$new_columns = array();
		$new_columns['cb'] = $columns['cb'];
		$new_columns['woo-color-thumb'] = __('Image', 'cactus');

		unset( $columns['cb'] );

		return array_merge( $new_columns, $columns );
	}
	
	function taxonomy_column_content( $columns, $column, $id ) {
		if ( $column == 'woo-color-thumb' ) {
			$image_id = get_term_meta ( $id, 'category-image-id', true );
			if ( $image_id ) {
				echo wp_get_attachment_image ( $image_id, 'color-swatch' );
			}
		}
		
		return $columns;
	}
 
	 /*
	  * Add a form field in the new category page
	  * @since 1.0.0
	 */
	public function add_category_image ( $taxonomy ) {
		
		?>
		<div class="form-field term-group">
			<label for="category-image-id"><?php _e('Image', 'cactus'); ?></label>
			<input type="hidden" id="category-image-id" name="category-image-id" class="custom_media_url" value="">
			<div id="category-image-wrapper"></div>
			<p>
				<input type="button" class="button button-secondary ct_tax_media_button" id="ct_tax_media_button" name="ct_tax_media_button" value="<?php esc_html_e( 'Add Image', 'cactus' ); ?>" />
				<input type="button" class="button button-secondary ct_tax_media_remove" id="ct_tax_media_remove" name="ct_tax_media_remove" value="<?php esc_html_e( 'Remove Image', 'cactus' ); ?>" />
			</p>
		</div>
	 <?php
	}
 
	 /**
	  * Save the form field
	  * @since 1.0.0
	  */
	public function save_category_image ( $term_id, $tt_id ) {
	   if( isset( $_POST['category-image-id'] ) && '' !== $_POST['category-image-id'] ){
		 $image = $_POST['category-image-id'];
		 add_term_meta( $term_id, 'category-image-id', $image, true );
	   }
	 }
	 
	 /**
	  * Edit the form field
	  * @since 1.0.0
	 */
	public function update_category_image ( $term, $taxonomy ) { ?>
		<tr class="form-field term-group-wrap">
			<th scope="row">
			<label for="category-image-id"><?php _e( 'Image', 'cactus' ); ?></label>
		</th>
		<td>
			<?php $image_id = get_term_meta ( $term -> term_id, 'category-image-id', true ); ?>
			<input type="hidden" id="category-image-id" name="category-image-id" value="<?php echo $image_id; ?>">
			<div id="category-image-wrapper">
				<?php if ( $image_id ) { ?>
				<?php echo wp_get_attachment_image ( $image_id, 'color-swatch' ); ?>
				<?php } ?>
			</div>
			<p>
				<input type="button" class="button button-secondary ct_tax_media_button" id="ct_tax_media_button" name="ct_tax_media_button" value="<?php _e( 'Add Image', 'cactus' ); ?>" />
				<input type="button" class="button button-secondary ct_tax_media_remove" id="ct_tax_media_remove" name="ct_tax_media_remove" value="<?php _e( 'Remove Image', 'cactus' ); ?>" />
			</p>
			</td>
		</tr>
	 <?php
	}

	/**
	 * Update the form field value
	 * @since 1.0.0
	 */
	public function updated_category_image ( $term_id, $tt_id ) {
		if( isset( $_POST['category-image-id'] ) && '' !== $_POST['category-image-id'] ){
			$image = $_POST['category-image-id'];
			update_term_meta ( $term_id, 'category-image-id', $image );
		} else {
			update_term_meta ( $term_id, 'category-image-id', '' );
		}
	}

	/**
	 * Enqueue Media lib
	 */
	public function load_wp_media_files() {
		wp_enqueue_media();
	}

	/**
	 * Add script
	 * @since 1.0.0
	 */
	public function add_script() { ?>
		<script>
			jQuery(document).ready( function($) {
				function ct_media_upload(button_class) {
					var _custom_media = true,
					_orig_send_attachment = wp.media.editor.send.attachment;
					$('body').on('click', button_class, function(e) {
						var button_id = '#'+$(this).attr('id');
						var send_attachment_bkp = wp.media.editor.send.attachment;
						var button = $(button_id);
						_custom_media = true;
						wp.media.editor.send.attachment = function(props, attachment){
							if ( _custom_media ) {
							$('#category-image-id').val(attachment.id);
							$('#category-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
							$('#category-image-wrapper .custom_media_image').attr('src', attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.sizes.full.url).css('display','block');
							} else {
								return _orig_send_attachment.apply( button_id, [props, attachment] );
							}
						}
						wp.media.editor.open(button);
					return false;
				});
			}
			
			ct_media_upload('.ct_tax_media_button.button'); 
			
			$('body').on('click','.ct_tax_media_remove',function(){
				$('#category-image-id').val('');
				$('#category-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
			});
			
			// Thanks: http://stackoverflow.com/questions/15281995/wordpress-create-category-ajax-response
			$(document).ajaxComplete(function(event, xhr, settings) {
				var queryStringArr = settings.data ? settings.data.split('&') : '';
				if( $.inArray('action=add-tag', queryStringArr) !== -1 ){
					var xml = xhr.responseXML;
					$response = $(xml).find('term_id').text();
					if($response != ""){
						// Clear the thumb image
						if($('#category-image-wrapper').length > 0){
							$('#category-image-wrapper').html('');
						}
					}
				}
			});
		});
	 </script>
	 <?php }

  }
}