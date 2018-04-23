<?php
/*
Plugin Name: WooCommerce Related Products
Description: Select your own related products instead of pulling them in by category.
Version:     1.0
Plugin URI:  http://andrewgunn.net
Author:      amg26
Author URI:  http://andrewgunn.net
*/
/**
 *
 */
const WC_BOM_SETTINGS = 'wc_bom_settings';
/**
 *
 */
const WC_BOM_OPTIONS  = 'wc_bom_options';


/**
 * Class WC_Related_Products
 */
class WC_Related_Products {

	/**
	 * @var null
	 */
	protected static $instance = null;

	/**
	 * WC_Related_Products constructor.
	 */
	protected function __construct() {
		$this->init();
	}

	/**
	 * WC_Related_Products constructor.
	 */
	public function init() {

		include_once __DIR__ . '/classes/class-wc-rp-settings.php';
		$set  = WC_RP_Settings::getInstance();
		$opts = get_option( WC_BOM_SETTINGS );


		$rp_prioirty  = isset( $opts['related_priority'] ) ? (int) $opts['related_priority'] : 15;
		$up_priority  = isset( $opts['upsell_priority'] ) ? (int) $opts['upsell_priority'] : 15;
		$cs_prioirity = isset( $opts['crosssell_priority'] ) ? (int) $opts['crosssell_priority'] : 20;


		add_action( 'init', [ $this, 'load_assets' ] );
		add_action( 'admin_init', [ $this, 'create_options' ] );
		add_action( 'admin_menu', [ $this, 'wc_rp_create_menu' ], 99 );
		add_action( 'woocommerce_after_single_product_summary', [ $this, 'redisplay_related' ], $rp_prioirty );
		//add_action( 'woocommerce_after_single_product_summary', 'replay_upsells', 15 );
		add_filter( 'woocommerce_upsell_display_args', [ $this, 'custom_woocommerce_upsell_display_args' ] );
		add_action( 'woocommerce_after_single_product_summary', [ $this, 'redisplay_cross' ], $cs_prioirity );

		add_filter( 'woocommerce_product_related_posts_query', [ $this, 'wc_rp_filter_related_products' ], 20, 2 );
		add_filter( 'woocommerce_related_products_args', [ $this, 'wc_rp_filter_related_products_legacy' ] );
		add_filter( 'woocommerce_output_related_products_args', [ $this, 'wc_change_number_related_products' ], 15 );
		add_filter( 'woocommerce_cross_sells_columns', [ $this, 'change_cross_sells_columns' ], 20 );


		add_action( 'woocommerce_process_product_meta', [ $this, 'wc_rp_save_related_products' ], 10, 2 );
		add_filter( 'plugin_action_links', [ $this, 'plugin_links' ], 10, 5 );
		add_filter( 'woocommerce_product_related_posts_relate_by_category', [ $this, 'wc_rp_taxonomy_rel', ], 10, 2 );
		add_filter( 'woocommerce_product_related_posts_relate_by_tag', [ $this, 'wc_rp_taxonomy_rel' ], 10, 2 );
		add_filter( 'woocommerce_product_related_posts_force_display', [ $this, 'wc_rp_force_display' ], 10, 2 );
		add_action( 'woocommerce_product_options_related', [ $this, 'wc_rp_select_related_products' ] );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );


	}

	/**
	 * @return null
	 */
	public static function getInstance() {

		if ( static::$instance === null ) {
			static::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * @return mixed
	 */
	public function create_options() {

		if ( ! get_option( WC_BOM_OPTIONS ) ) {
			add_option( WC_BOM_OPTIONS, [ 'init' => 'true' ] );
		}
		if ( ! get_option( WC_BOM_SETTINGS ) ) {
			add_option( WC_BOM_SETTINGS, [ 'init' => 'true' ] );
		}
		//delete_option( 'wc_bom_settings' );
		//delete_option( 'wc_bom_options' );
	}

	/**
	 * Force related products to show if some have been selected.
	 * This is required for WooCommerce 3.0, which will not display products if
	 * There are no categories or tags.
	 *
	 * @param bool $result     Whether or not we should force related posts to display.
	 * @param int  $product_id The ID of the current product.
	 *
	 * @return bool Modified value - should we force related products to display?
	 */
	public function wc_rp_force_display( $result, $product_id ) {
		$related_ids = get_post_meta( $product_id, '_related_ids', true );

		return empty( $related_ids ) ? $result : true;
	}


	/**
	 * Determine whether we want to consider taxonomy terms when selecting related products.
	 * This is required for WooCommerce 3.0.
	 *
	 * @param bool $result     Whether or not we should consider tax terms during selection.
	 * @param int  $product_id The ID of the current product.
	 *
	 * @return bool Modified value - should we consider tax terms during selection?
	 */
	public function wc_rp_taxonomy_rel( $result, $product_id ) {
		$related_ids = get_post_meta( $product_id, '_related_ids', true );
		if ( ! empty( $related_ids ) ) {
			return false;
		}

		return 'none' === get_option( 'wc_rp_empty_behavior' ) ? false : $result;
	}

	/**
	 * Add related products selector to product edit screen
	 */
	public function wc_rp_select_related_products() {
		global $post, $woocommerce;
		$product_ids = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, '_related_ids', true ) ) );
		?>
        <div class="options_group">
			<?php if ( $woocommerce->version >= '3.0' ) : ?>
                <p class="form-field">
                    <label for="related_ids"><?php _e( 'Related Products', 'woocommerce' ); ?></label>
                    <select class="wc-product-search" multiple="multiple" style="width: 50%;" id="related_ids"
                            name="related_ids[]"
                            data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>"
                            data-action="woocommerce_json_search_products_and_variations"
                            data-exclude="<?php echo (int) $post->ID; ?>">
						<?php foreach ( $product_ids as $product_id ) {
							$product = wc_get_product( $product_id );
							if ( is_object( $product ) ) {
								echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
							}
						} ?>
                    </select> <?php echo wc_help_tip( __( 'Related products are displayed on the product detail page.', 'woocommerce' ) ); ?>
                </p>
			<?php elseif ( $woocommerce->version >= '2.3' ) : ?>
                <p class="form-field"><label for="related_ids"><?php _e( 'Related Products', 'woocommerce' ); ?></label>
                    <input type="hidden" class="wc-product-search" style="width: 50%;" id="related_ids"
                           name="related_ids"
                           data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>"
                           data-action="woocommerce_json_search_products" data-multiple="true" data-selected="<?php
					$json_ids = [];
					foreach ( $product_ids as $product_id ) {
						$product = wc_get_product( $product_id );
						if ( is_object( $product ) && is_callable( [ $product, 'get_formatted_name' ] ) ) {
							$json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
						}
					}
					echo esc_attr( json_encode( $json_ids ) ); ?>"
                           value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>"/>
                    <img class="help_tip"
                         data-tip='<?php _e( 'Related products are displayed on the product detail page.', 'woocommerce' ) ?>'
                         src="<?php echo wc()->plugin_url(); ?>/assets/images/help.png" height="16" width="16"/>
                </p>
			<?php else: ?>
                <p class="form-field"><label for="related_ids"><?php _e( 'Related Products', 'woocommerce' ); ?></label>
                    <select id="related_ids" name="related_ids[]" class="ajax_chosen_select_products"
                            multiple="multiple"
                            data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>">
						<?php foreach ( $product_ids as $product_id ) {

							$product = new WC_Product( $product_id );

							if ( $product ) {
								echo '<option value="' . esc_attr( $product_id ) . '" selected="selected">' . esc_html( $product->get_formatted_name() ) . '</option>';
							}
						} ?>
                    </select>
                    <img class="help_tip"
                         data-tip='<?php _e( 'Related products are displayed on the product detail page.', 'woocommerce' ) ?>'
                         src="<?php echo wc()->plugin_url(); ?>/assets/images/help.png" height="16" width="16"/></p>
			<?php endif; ?>
        </div>
		<?php
	}


	/**
	 * Save related products selector on product edit screen.
	 *
	 * @param int $post_id ID of the post to save.
	 * @param obj WP_Post object.
	 */
	public function wc_rp_save_related_products( $post_id, $post ) {
		global $woocommerce;
		if ( isset( $_POST['related_ids'] ) ) {
			// From 2.3 until the release before 3.0 Woocommerce posted these as a comma-separated string.
			// Before and after, they are posted as an array of IDs.
			if ( $woocommerce->version >= '2.3' && $woocommerce->version < '3.0' ) {
				$related = isset( $_POST['related_ids'] ) ? array_filter( array_map( '\intval', explode( ',', $_POST['related_ids'] ) ) ) : [];
			} else {
				$related = [];
				$ids     = $_POST['related_ids'];
				foreach ( $ids as $id ) {
					if ( $id && $id > 0 ) {
						$related[] = absint( $id );
					}
				}
			}
			update_post_meta( $post_id, '_related_ids', $related );
		} else {
			delete_post_meta( $post_id, '_related_ids' );
		}
	}


	/**
	 * Filter the related product query args.
	 * This function works for WooCommerce prior to 3.0.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array Modified query arguments.
	 */
	public function wc_rp_filter_related_products_legacy( $args ) {
		global $post;
		$related = get_post_meta( $post->ID, '_related_ids', true );
		if ( $related ) { // remove category based filtering
			$args['post__in'] = $related;
		} elseif ( get_option( 'wc_rp_empty_behavior' ) === 'none' ) { // don't show any products
			$args['post__in'] = [ 0 ];
		}

		return $args;
	}


	/**
	 * Filter the related product query args.
	 *
	 * @param array $query      Query arguments.
	 * @param int   $product_id The ID of the current product.
	 *
	 * @return array Modified query arguments.
	 */
	public function wc_rp_filter_related_products( $query, $product_id ) {
		$related_ids = get_post_meta( $product_id, '_related_ids', true );
		if ( ! empty( $related_ids ) && is_array( $related_ids ) ) {

			$related_ids    = implode( ',', array_map( 'absint', $related_ids ) );
			$query['where'] .= " AND p.ID IN ( {$related_ids} )";
		}

		return $query;
	}

	/**
	 *
	 */
	public function redisplay_related() {

		woocommerce_output_related_products();

	}

	/**
	 * @param $args
	 *
	 * @return mixed
	 */
	public function wc_change_number_related_products( $args ) {

		$sets = get_option( 'wc_bom_settings' );


		$cols  = ( $sets['related_columns'] > 0 ) ? (int) $sets['related_columns'] : 'fuck';
		$limit = ( $sets['related_limit'] > 0 ) ? (int) $sets['limit'] : 'fuck';

		$args['posts_per_page'] = $limit;
		$args['columns']        = $cols;

		return $args;
	}

	/**
	 * @param $args
	 *
	 * @return mixed
	 */
	public function custom_woocommerce_upsell_display_args( $args ) {

		$opts      = get_option( WC_BOM_SETTINGS );
		$is_active = isset( $opts['show_upsells'] ) ? (bool) $opts['show_upsells'] : false;

		$limit = isset( $opts['upsell_columns'] ) ? (int) $opts['upsell_columns'] : 2;
		$cols  = isset( $opts['upsell_limit'] ) ? (int) $opts['upsell_limit'] : 2;

		$args['posts_per_page'] = $limit;
		$args['columns']        = $cols; //change number of upsells here

		return $args;
	}

	/**
	 *
	 */
	public function redisplay_cross() {


		$opts      = get_option( WC_BOM_SETTINGS );
		$is_active = isset( $opts['show_crosssells'] ) ? (bool) $opts['show_crosssells'] : false;

		if ( $is_active === true ) {
			$cols  = isset( $opts['crosssell_columns'] ) ? (int) $opts['crosssell_columns'] : 2;
			$limit = isset( $opts['crosssell_limit'] ) ? (int) $opts['crosssell_limit'] : 2;

			woocommerce_cross_sell_display( $limit, $cols );
		}

	}

	/**
	 * @param $columns
	 *
	 * @return int
	 */
	public function change_cross_sells_columns( $columns ) {
		$opts = get_option( WC_BOM_SETTINGS );

		$cols  = isset( $opts['crosssell_columns'] ) ? (int) $opts['crosssell_columns'] : 2;
		$limit = isset( $opts['crosssell_limit'] ) ? (int) $opts['crosssell_limit'] : 2;


		return $cols;
	}

	/**
	 * Create the menu item.
	 */
	public function wc_rp_create_menu() {
		add_submenu_page(
			'woocommerce',
			'Related Products',
			'Related Products',
			'manage_options',
			'wc_related_products',
			[ $this, 'wc_rp_settings_page', ]
		);
	}


	/**
	 * Create the settings page.
	 */
	public function wc_rp_settings_page() {
		if ( isset( $_POST['submit_custom_related_products'] ) && current_user_can( 'manage_options' ) ) {
			check_admin_referer( 'wc_related_products', '_custom_related_products_nonce' );
			// save settings
			if ( isset( $_POST['wc_rp_empty_behavior'] ) && $_POST['wc_rp_empty_behavior'] != '' ) {
				update_option( 'wc_rp_empty_behavior', $_POST['wc_rp_empty_behavior'] );
			} else {
				delete_option( 'wc_rp_empty_behavior' );
			}
			echo '<div id="message" class="updated"><p>Settings saved</p></div>';
		} ?>

        <div class="wrap" id="custom-related-products">
            <h2>Custom Related Products</h2>
			<?php $behavior_none_selected = ( get_option( 'wc_rp_empty_behavior' ) === 'none' ) ? 'selected="selected"' : '';
			echo '
		
		<form method="post" action="admin.php?page=wc_related_products">
			' . wp_nonce_field( 'wc_related_products', '_custom_related_products_nonce', true, false ) . '
			<p>If I have not selected related products:
				<select name="wc_rp_empty_behavior">
					<option value="">Select random related products by category</option>
					<option value="none" ' . $behavior_none_selected . '>Don&rsquo;t show any related products</option>
				</select>
			</p>
			<p><input type="submit" name="submit_custom_related_products" value="Save" class="button button-primary" /></p>
		</form>
	'; ?>
        </div>
		<?php
	} // end settings page

	/**
	 * @param $actions
	 * @param $plugin_file
	 *
	 * @return array
	 */
	public function plugin_links( $actions, $plugin_file ) {
		static $plugin;

		if ( $plugin === null ) {
			$plugin = plugin_basename( __FILE__ );
		}
		if ( $plugin === $plugin_file ) {
			$settings = [
				'settings' => '<a href="admin.php?page=wc_related_products">' . __( 'Settings', 'wc-bom' ) . '</a>',
			];
			$actions  = array_merge( $settings, $actions );
		}

		return $actions;
	}


	/**
	 *
	 */
	public function load_assets() {
		$url  = 'assets/scripts/';
		$url2 = 'assets/styles/';
		wp_register_script( 'bom_adm_js', plugins_url( $url . 'wc-bom-admin.js', __FILE__ ), [ 'jquery' ] );
		wp_register_style( 'bom_css', plugins_url( $url2 . 'wc-bom.css', __FILE__ ) );
		wp_register_script( 'chosen_js',
			'https://cdnjs.cloudflare.com/ajax/libs/chosen/1.7.0/chosen.jquery.min.js', [ 'jquery' ] );
		wp_register_style( 'chosen_css',
			'https://cdnjs.cloudflare.com/ajax/libs/chosen/1.7.0/chosen.min.css' );
		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'bom_adm_js' );
		wp_enqueue_script( 'chosen_js' );
		wp_enqueue_style( 'chosen_css' );
		wp_enqueue_style( 'bom_css' );
	}
}

$wcrp                 = WC_Related_Products::getInstance();