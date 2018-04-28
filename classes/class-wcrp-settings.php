<?php declare( strict_types=1 );
/**
 * Copyright (c) 2017.  |  Andrew Gunn
 * http://andrewgunn.org  |   https://github.com/amg262
 * andrewmgunn26@gmail.com
 *
 */


if ( ! is_admin() ) {
	//wp_die( 'You must be an admin to view this.' );
}

/**
 * Class WC_Bom_Settings
 *
 * @package WooBom
 */
class WC_RP_Settings {//implements WC_Abstract_Settings {

	/**
	 * @var null
	 */
	protected static $instance;

	protected $wcrp_data = [];

	protected $plugin_options = [];


	/**
	 * WC_Bom constructor.
	 */
	private function __construct() {

		$this->init();
	}

	/**
	 *
	 */
	protected function init() {

		add_action( 'admin_menu', [ $this, 'wc_bom_menu' ], 99 );
		add_action( 'admin_init', [ $this, 'page_init' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'wco_admin' ] );
		add_action( 'wp_ajax_wco_ajax', [ $this, 'wco_ajax' ] );

		//add_action( 'wp_ajax_nopriv_wco_ajax', [ $this, 'wco_ajax' ] );
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
	 * /**
	 * Add options page
	 */
	public function wc_bom_menu() {

		add_submenu_page(
			'woocommerce',
			'Related Products',
			'Related Products',
			'manage_options',
			'wc-rp',
			[ $this, 'settings_page' ]//,
		);

	}

	/**
	 * Register and add settings
	 */
	public function page_init() {

		register_setting(
			'wc_bom_settings_group', // Option group
			'wc_bom_settings', // Option name
			[ $this, 'sanitize' ] // Sanitize
		);

		add_settings_section(
			'wc_bom_settings_section', // ID
			'', // Title
			[ $this, 'settings_info' ], // Callback
			'wc-bom-settings-admin' // Page
		);

		add_settings_section(
			'wc_bom_setting', // ID
			'', // Title
			[ $this, 'settings_callback' ], // Callback
			'wc-bom-settings-admin' // Page
		);

	}

	/**
	 * Print the Section text
	 */
	public function settings_info() { ?>
        <div id="plugin-info-header" class="plugin-info header">
            <div class="plugin-info content">
            </div>
        </div>
	<?php }


	/**
	 * Options page callback
	 */
	public function settings_page() {

		global $wc_bom_settings;
		$wc_bom_settings = get_option( WC_BOM_SETTINGS );

		$active_tab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : 'all';

		wp_enqueue_media(); ?>

        <div class="wrap">
            <div class="wc-bom settings-page">
                <h2><?php esc_html_e( the_title(), 'wc-bom' ); ?></h2>
                <div id="icon-themes" class="icon32">&nbps;</div>
				<?php ?>
                <h2 class="nav-tab-wrapper">
                    <a id="wcrp-nav-all" href="#all" class="nav-tab
                    <?php echo $active_tab === 'all' ? 'nav-tab-active' : ''; ?>">All</a>

                    <a id="wcrp-nav-settings" href="#settings" class="nav-tab
                    <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
                </h2>
				<?php ?>
                <form method="post" action="options.php">
                    <div id="poststuff">

                        <div id="post-body" class="metabox-holder columns-2">
							<?php if ( $active_tab === 'all' || $active_tab === null ) {
								settings_fields( 'wc_bom_settings_group' );
								do_settings_sections( 'wc-bom-settings-admin' );
								submit_button( 'Save Settings' );

							} elseif ( $active_tab === 'settings' ) {
								settings_fields( 'wc_bom_settings_group' );
								do_settings_sections( 'wc-bom-settings-admin' );
								submit_button( 'Save Settings' );
							}
							?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
		<?php
	}

	/**
	 *
	 */
	public function wco_admin() {


		$ajax_data = $this->get_data();

		$ajax_object = [
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'ajax_nonce' ),
			'ajax_data' => $ajax_data,
			'action'    => [ $this, 'wco_ajax' ], //'options'  => 'wc_bom_option[opt]',
			//'product'   => $this->get_data(),
		];
		wp_localize_script( 'bom_adm_js', 'ajax_object', $ajax_object );
	}


	public function get_data() {

		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'orderby'        => 'title',
			'order'          => 'ASC',

		];

		$out   = [];
		$posts = get_posts( $args );
		foreach ( $posts as $p ) {
			$out[] = [ 'id' => $p->ID, 'text' => $p->post_title ];
		}
		$json = json_encode( $out );

		return $out;
	}

	/**
	 *
	 */
	public function wco_ajax() {

		//global $wpdb;
		check_ajax_referer( 'ajax_nonce', 'security' );
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		}

		$prod = $_POST['product'];

		var_dump( $_POST );

		//var_dump( $_POST );
		$args = [
			'post_type'   => 'product',
			'post_title'  => $prod,
			'post_status' => 'publish',
		];

		$prod = get_posts( $args );
		$i    = 0;
		foreach ( $prod as $p ) {

			$e = get_post_meta( $p->ID, '_related_ids' );

			if ( count( $e ) > 0 ) {
				$j = json_encode( $e );
				echo $j;
			}
		}


		wp_die( 'Ajax finished.' );
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 *
	 * @return array
	 */
	public function sanitize( $input ) {

		//$new_input = [];
		//if ( isset( $input['license_key'] ) ) {
		//$new_input['license_key'] = sanitize_text_field( $input['license_key'] );
		//}

		//if ( isset( $input[ 'title' ] ) ) {
		//	$new_input[ 'title' ] = sanitize_text_field( $input[ 'title' ] );
		//}
		return $input;
	}


	/**
	 * Get the settings option array and print one of its values
	 */
	public function settings_callback() {

		global $wc_bom_settings;
		$wc_bom_settings = get_option( 'wc_bom_settings' );


		// Enqueue Media Library Use
		wp_enqueue_media();
		var_dump( $wc_bom_settings ); ?>
        <div id="postbox-container-1" class="postbox-container">

            <div id="normal-sortables" class="meta-box-sortables">

                <div id="postbox" class="postbox">

                    <button type="button" class="handlediv button-link" aria-expanded="true">
                        <span class="screen-reader-text">Toggle panel: General</span>
                        <span class="toggle-indicator" aria-hidden="true"></span>
                    </button>
                    <h2 class='hndle'><span>General</span></h2>

                    <div class="inside ">
                        Update the fields
                    </div>
                    <div id="major-publishing-actions">
                        <div id="publishing-action">
                            <span class="spinner"></span>
                            <input type="submit" accesskey="p" value="Update"
                                   class="button button-primary button-large"
                                   id="publish" name="publish">
                            <button class="button button-secondary button-large">
                                Reset
                            </button>
                        </div>
                        <div class="clear"></div>
                    </div>
                </div>
            </div>
        </div>
        <div id="postbox-container-2" class="postbox-container">
            <div id="normal-sortables" class="meta-box-sortables">
                <div id="postbox" class="postbox">
                    <button type="button" class="handlediv button-link" aria-expanded="true">
                        <span class="screen-reader-text">Toggle panel: General</span>
                        <span class="toggle-indicator" aria-hidden="true"></span>
                    </button>
                    <h2 class='hndle'><span>General</span></h2>
                    <div class="inside acf-fields -left">
						<?php settings_errors(); ?>
						<?php $this->settings_fields(); ?>
                    </div>
                </div>
            </div>
        </div>
	<?php }

	/**
	 * @return string
	 */
	protected function settings_fields() {
		global $wc_bom_settings;

		$wc_bom_settings = get_option( 'wc_bom_settings' ); ?>

        <div id="wcrp-related">
            <table class="form-table">
                <tbody>

				<?php /*$label = 'Show random related products by category if none selected. ("Yes" or "No")' ?>
					<?php $key = 'show_random_related'; ?>
					<?php $opt = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td><input type="text"
                               title="wc_bom_settings[<?php _e( $key ); ?>]"
                               id="wc_bom_settings[<?php _e( $key ); ?>]"
                               name="wc_bom_settings[<?php _e( $key ); ?>]"
                               value="<?php echo $wc_bom_settings[ $key ]; */ ?>

                <tr>
					<?php $label = 'Show Related'; ?>
					<?php $key = $this->format_key( $label ); ?>
					<?php $opt = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td><input type="checkbox" id="wc_bom_settings[<?php _e( $key ); ?>]"
                               title="wc_bom_settings[<?php _e( $key ); ?>]"
                               name="wc_bom_settings[<?php _e( $key ); ?>]"
                               value="1"<?php checked( 1, $wc_bom_settings[ $key ], true ); ?> /></td>
                </tr>
                <tr><?php $label = 'Related Total';
					$key         = $this->format_key( $label );
					$obj         = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td><input type="number"
                               title="wc_bom_settings[<?php _e( $key ); ?>]"
                               id="wc_bom_settings[<?php _e( $key ); ?>]"
                               name="wc_bom_settings[<?php _e( $key ); ?>]"
                               value="<?php echo $wc_bom_settings[ $key ]; ?>"/>
                    </td>
                </tr>
                <tr><?php $label = 'Related Columns';
					$key         = $this->format_key( $label );
					$obj         = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td><input type="number"
                               title="wc_bom_settings[<?php _e( $key ); ?>]"
                               id="wc_bom_settings[<?php _e( $key ); ?>]"
                               name="wc_bom_settings[<?php _e( $key ); ?>]"
                               value="<?php echo $wc_bom_settings[ $key ]; ?>"/>
                    </td>
                </tr>

                <tr><?php $label = 'Related Priority';
					$key         = $this->format_key( $label );
					$obj         = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td><input type="number"
                               title="wc_bom_settings[<?php _e( $key ); ?>]"
                               id="wc_bom_settings[<?php _e( $key ); ?>]"
                               name="wc_bom_settings[<?php _e( $key ); ?>]"
                               value="<?php echo $wc_bom_settings[ $key ]; ?>"/>
                    </td>
                </tr>

                <tr>
					<?php $label = 'Show UpSells'; ?>
					<?php $key = $this->format_key( $label ); ?>
					<?php $opt = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td><input type="checkbox" id="wc_bom_settings[<?php _e( $key ); ?>]"
                               title="wc_bom_settings[<?php _e( $key ); ?>]"
                               name="wc_bom_settings[<?php _e( $key ); ?>]"
                               value="1"<?php checked( 1, $wc_bom_settings[ $key ], true ); ?> /></td>
                </tr>
                <tr><?php $label = 'UpSell Total';
					$key         = $this->format_key( $label );
					$obj         = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td><input type="number"
                               title="wc_bom_settings[<?php _e( $key ); ?>]"
                               id="wc_bom_settings[<?php _e( $key ); ?>]"
                               name="wc_bom_settings[<?php _e( $key ); ?>]"
                               value="<?php echo $wc_bom_settings[ $key ]; ?>"/>
                    </td>
                </tr>
                <tr><?php $label = 'UpSell Columns';
					$key         = $this->format_key( $label );
					$obj         = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td><input type="number"
                               title="wc_bom_settings[<?php _e( $key ); ?>]"
                               id="wc_bom_settings[<?php _e( $key ); ?>]"
                               name="wc_bom_settings[<?php _e( $key ); ?>]"
                               value="<?php echo $wc_bom_settings[ $key ]; ?>"/>
                    </td>
                </tr>

                <tr><?php $label = 'UpSell Priority';
					$key         = $this->format_key( $label );
					$obj         = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td><input type="number"
                               title="wc_bom_settings[<?php _e( $key ); ?>]"
                               id="wc_bom_settings[<?php _e( $key ); ?>]"
                               name="wc_bom_settings[<?php _e( $key ); ?>]"
                               value="<?php echo $wc_bom_settings[ $key ]; ?>"/>
                    </td>
                </tr>

                <tr>
					<?php $label = 'Show CrossSells'; ?>
					<?php $key = $this->format_key( $label ); ?>
					<?php $opt = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td><input type="checkbox" id="wc_bom_settings[<?php _e( $key ); ?>]"
                               title="wc_bom_settings[<?php _e( $key ); ?>]"
                               name="wc_bom_settings[<?php _e( $key ); ?>]"
                               value="1"<?php checked( 1, $wc_bom_settings[ $key ], true ); ?> /></td>
                </tr>
                <tr><?php $label = 'CrossSell Total';
					$key         = $this->format_key( $label );
					$obj         = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td><input type="number"
                               title="wc_bom_settings[<?php _e( $key ); ?>]"
                               id="wc_bom_settings[<?php _e( $key ); ?>]"
                               name="wc_bom_settings[<?php _e( $key ); ?>]"
                               value="<?php echo $wc_bom_settings[ $key ]; ?>"/>
                    </td>
                </tr>
                <tr><?php $label = 'CrossSell Columns';
					$key         = $this->format_key( $label );
					$obj         = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td><input type="number"
                               title="wc_bom_settings[<?php _e( $key ); ?>]"
                               id="wc_bom_settings[<?php _e( $key ); ?>]"
                               name="wc_bom_settings[<?php _e( $key ); ?>]"
                               value="<?php echo $wc_bom_settings[ $key ]; ?>"/>
                    </td>
                </tr>
                <tr><?php $label = 'CrossSell Priority';
					$key         = $this->format_key( $label );
					$obj         = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td><input type="number"
                               title="wc_bom_settings[<?php _e( $key ); ?>]"
                               id="wc_bom_settings[<?php _e( $key ); ?>]"
                               name="wc_bom_settings[<?php _e( $key ); ?>]"
                               value="<?php echo $wc_bom_settings[ $key ]; ?>"/>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div id="wcrp-settings">
            <table class="form-table">
                <tbody>
                <tr><?php $label = 'Copy Product Data';
					$key         = $this->format_key( $label );
					$obj         = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td>
                        <select id="wc_bom_settings[<?php _e( $key ); ?>]" name="wc_bom_settings[<?php _e( $key ); ?>]"
                                data-placeholder="wc_bom_settings[<?php _e( $key ); ?>]"
                                class="prod-select chosen-select">
							<?php _e( $this->build_select_options( $wc_bom_settings[ $key ] ), 'wc-related-products' ); ?>
                        </select>
                    </td>
                    <th>

                    </th>
                    <td>
                        <div id="p_out" name="p_out"><span id="p_it" name="p_it">Hello there!</span></div>
                    </td>
                </tr>

                <tr><?php $label = 'Paste Data Product';
					$key         = $this->format_key( $label );
					$obj         = $wc_bom_settings[ $key ]; ?>
                    <th scope="row"><label for="<?php _e( $key ); ?>"><?php _e( $label ); ?></label></th>
                    <td>
                        <select id="wc_bom_settings[<?php _e( $key ); ?>]" name="wc_bom_settings[<?php _e( $key ); ?>]"
                                data-placeholder="wc_bom_settings[<?php _e( $key ); ?>]"
                                class="prod-select chosen-select">
							<?php _e( $this->build_select_options( $wc_bom_settings[ $key ] ), 'wc-related-products' ); ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>

                    </th>
                    <td>
                        <span class="button primary" id="button_hit" name="button_hit">Generate</span>
                    </td>
                </tr>
                <tr><?php ?>
                    <th scope="row"></th>
                    <td><span id="prod_output" name="prod_output"><strong>Prod</strong></span></td>
                </tr>
                </tbody>
            </table>
        </div>

		<?php return 'hi';
	}

	/**
	 * @param $text
	 *
	 * @return string
	 */
	protected function format_key( $text ) {
		$str = str_replace( [ '-', ' ' ], '_', $text );

		return strtolower( $str );
	}

	protected function build_select_options( $data ) {
		$option = '';

		foreach ( $this->get_data() as $arr ) {
			if ( $data === $arr['text'] ) {
				$selected = 'selected="selected"';
			} else {
				$selected = '';
			}
			$option .= '<option id="' . $arr['id'] . '" value="' . $arr['text'] . '" ' . $selected . '">'
			           . '<strong>' . $arr['id'] . '</strong>&nbsp;:&nbsp;' . substr( $arr['text'], 0, 40 ) . '</option>';
		}

		return $option;
	}
}
