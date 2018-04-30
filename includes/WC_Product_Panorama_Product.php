<?php

/**
 * Class WC_Product_Panorama_Product
 */
class WC_Product_Panorama_Product extends WC_Product {

	/**
	 * WC_Product_Panorama_Product constructor.
	 *
	 * @param WC_Product $product WooCommerce Product.
	 */
	function __construct( $product ) {
	    $this->product_type = 'panorama_product';
		parent::__construct( $product );
	}

	static function init() {


		add_action( 'admin_footer', array( 'WC_Product_Panorama_Product', 'panorama_product_custom_js') );
		add_action( 'admin_head', array( 'WC_Product_Panorama_Product', 'panorama_settings_icon' ) );
		add_action( 'woocommerce_product_data_panels',  array( 'WC_Product_Panorama_Product', 'panorama_product_settings' ) );
		add_action( 'woocommerce_process_product_meta', array( 'WC_Product_Panorama_Product', 'save_panorama_project' ) );
		// add_action( 'woocommerce_order_status_completed', array( 'WC_Product_Panorama_Product', 'order_completed' ) );
		add_action( 'woocommerce_thankyou', array( 'WC_Product_Panorama_Product', 'auto_complete_order' ) );
		add_action( 'woocommerce_panorama_product_add_to_cart', array( 'WC_Product_Panorama_Product', 'show_add_to_cart' )) ;

		add_action( 'woocommerce_thankyou', array( 'WC_Product_Panorama_Product', 'custom_order_text' ), 999, 1 );
		add_action( 'woocommerce_email_after_order_table', array( 'WC_Product_Panorama_Product', 'custom_order_email_text' ), 999, 4 );

		add_filter( 'woocommerce_product_data_tabs', array( 'WC_Product_Panorama_Product', 'panorama_product_tabs' ) );
		add_filter( 'product_type_selector', array( 'WC_Product_Panorama_Product', 'add_panorama_product_type' ) );
		add_filter( 'woocommerce_product_class', array( 'WC_Product_Panorama_Product', 'woocommerce_product_class' ), 10, 2 );

		add_action( 'post_submitbox_misc_actions', array( 'WC_Product_Panorama_Product', 'panorama_woocommerce_template_metabox' ) );
		add_action( 'save_post', array( 'WC_Product_Panorama_Product', 'panorama_woocommerce_save_meta' ) );

		add_action( 'woocommerce_checkout_init', array( 'WC_Product_Panorama_Product', 'force_login' ), 10, 1 );

		add_action( 'psp_woo_duplicate_post', array( 'WC_Product_Panorama_Product', 'panorama_copy_post_meta_info' ), 10, 2 );
		add_action( 'psp_woo_duplicate_page', array( 'WC_Product_Panorama_Product', 'panorama_copy_post_meta_info' ), 10, 2 );

	}

	/**
     * Handles the `admin_footer` action
     *
     * Adds custom JS to hide/show tabs for Panorama project products
	 *
	 * @return void
	 */
	function panorama_product_custom_js() {

	    global $post;

		if ( 'product' !== get_post_type() ) {
			return;
		}
		?>
		<script type='text/javascript'>

            jQuery(function($) {

                if ('panorama_product' == $('#product-type').val()) {
                    $('.product_data_tabs .general_tab').show();
                    $('#general_product_data .pricing').show();
                    $('.product_data_tabs li').removeClass('active');
                    $('.woocommerce_options_panel').hide();
                    $('#panorama_product').show();
                    $('.product_data_tabs .panorama_tab').addClass('active');
                }

                $('#product-type').on('change', function() {

                    if ('panorama_product' == $('#product-type').val()) {
                        $('.product_data_tabs li').removeClass('active');
                        $('.product_data_tabs .panorama_tab').addClass('active');
                        $('.product_data_tabs .general_tab').show();
                        $('#general_product_data .pricing').show();
                        $('.woocommerce_options_panel').hide();
                        $('#panorama_product').show();
                    }
                });
            });

		</script>
		<?php
	}

	/**
     * Handles `woocommerce_product_data_panels` action
     *
	 * Panorama Project settings for Panorama Product type on product edit page
	 *
	 * @return void
	 */
	function panorama_product_settings() {

		$projects       = array();
		$projects_query = get_posts(
			array(
				'post_type'      => 'psp_projects',
				'posts_per_page' => -1,
				'meta_key'		 => '_psp_woocommerce_template',
				'meta_value'     => 'yes'
			)
		);

		foreach ( $projects_query as $project ) {
			$projects[ $project->ID ] = $project->post_title;
		}

		$user_roles = apply_filters( 'psp_woo_available_user_roles', array(
			'subscriber'			=>	__( 'Default', 'psp-woocommerce' ),
			'subscriber'			=>	__( 'Subscriber', 'psp-woocommerce' ),
			'psp_project_owner'		=>	__( 'Project Owner', 'psp-woocommerce' ),
			'psp_project_creator'	=>	__( 'Project Creator', 'psp-woocommerce' ),
			'psp_project_manager'	=>	__( 'Project Manager', 'psp-woocommerce' )
		) );

		if ( count( $projects ) > 0 ) {
			echo '<div id="panorama_product" class="panel woocommerce_options_panel"><div class="options_group">';

			woocommerce_wp_select(
				array(
					'id'      => 'psp_woocommerce_panorama_project',
					'label'   => __( 'Panorama project', 'psp-woocommerce' ),
					'value'   => get_post_meta( get_the_id(), '_panorama_project_id', true ),
					'options' => $projects,
				)
			);

			woocommerce_wp_select(
				array(
					'id'		=>	'psp_woocommerce_user_role',
					'label'		=>	__( 'Customer User Role', 'psp-woocommerce' ),
					'value' 	=>  get_post_meta( get_the_id(), '_psp_user_role', true ),
					'options' 	=>	$user_roles
				)
			);

			echo '</div></div>';
		}
		else {
			printf( __( 'You must <a href="%s">create a project</a> first.', 'psp-woocommerce' ), admin_url( 'post-new.php?post_type=psp_projects' ) );
		}



	}

	/**
     * Handles `woocommerce_process_product_meta` action
	 * Panorama Project settings for Panorama Product type on product edit page
	 *
     * @param int $post_id The WC_Product being saved.
	 * @return void
	 */
	function save_panorama_project( $post_id ) {

	    // check if project ID is saved.
		$project_id = isset( $_POST['psp_woocommerce_panorama_project'] ) && ! empty( $_POST['psp_woocommerce_panorama_project'] ) ? intval( $_POST['psp_woocommerce_panorama_project'] ) : 0;

		// Check if a use role is saved
		$user_role = isset( $_POST['psp_woocommerce_user_role'] ) && ! empty( $_POST['psp_woocommerce_user_role'] ) ? $_POST['psp_woocommerce_user_role'] : '';

		if ( ! $project_id ) {
			return;
		}

		$product = wc_get_product( $post_id );

		if ( 'panorama_product' === $product->get_type() ) {

			if ( method_exists( $product, 'update_meta_data' ) ) {
				// WooCommerce 3.x.
				$product->update_meta_data( '_panorama_project_id', $project_id );
				$product->update_meta_data( '_psp_user_role', $user_role );
				$product->save();
			} else {
				// WooCommerce 2.6.
				update_post_meta( $post_id, '_panorama_project_id', $project_id );
				update_post_meta( $post_id, '_psp_user_role', $user_role );
			}
		}
	}

	/**
	 * Handles `woocommerce_product_data_tabs` filter
     *
     * Adds the Panorama tab for Panorama project products
     *
     * @param array $tabs Current list of Product tabs
	 * @return array
	 */
	function panorama_product_tabs( $tabs) {

		$tabs['panorama'] = array(
			'label'  => __( 'Panorama', 'psp-woocommerce' ),
			'target' => 'panorama_product',
			'class'  => array( 'show_if_panorama_product' ),
		);

		$tabs['shipping']['class'][]       = 'hide_if_panorama_product';
		$tabs['linked_product']['class'][] = 'hide_if_panorama_product';
		$tabs['attribute']['class'][]      = 'hide_if_panorama_product';

		return $tabs;
	}

	/**
	 * Handles `product_type_selector` filter
	 *
	 * Adds the Panorama product to the product type selector
	 *
	 * @param array $types Current list of Product types
	 * @return array
	 */
    function add_panorama_product_type( $types ) {
	    $types[ 'panorama_product' ] = __( 'Panorama Product', 'psp-woocommerce' );
	    return $types;
    }

	/**
	 * Handles `woocommerce_product_class` filter
	 *
	 * Adds the Panorama product to the product type selector
	 *
	 * @param string $classname    The current classname
     * @param string $product_type The type of product
	 * @return string
	 */
	function woocommerce_product_class( $classname, $product_type ) {

		if ( 'panorama_product' === $product_type ) {
			$classname = 'WC_Product_Panorama_Product';
		}

		return $classname;
	}

	/**
	 * Handles `admin_head` action
	 *
	 * Adds the Panorama icon to the Panorama product tab
	 *
	 * @return void
	 */
	function panorama_settings_icon() {
		?>
        <style>
            #woocommerce-product-data ul.wc-tabs li.panorama_options a:before {
                content: "\f183";
                display: inline-block;
                -webkit-font-smoothing: antialiased;
                font: normal 15px/1 'dashicons';
                vertical-align: middle;
            }
        </style>
		<?php
	}

	function panorama_process_order( $order_id ) {

		$result 		= false;

		$user_id       = get_post_meta( $order_id, '_customer_user', true );
		$user_roles	   = array();
		$order         = wc_get_order( $order_id );
		$items         = $order->get_items();
		$project_items = array();

		foreach ( $items as $item ) {

			$product_id = method_exists( $item, 'get_product_id' ) ? $item->get_product_id() : $item['product_id'];
			$product    = wc_get_product( $product_id );

			$user_role = get_post_meta( $product_id, '_psp_user_role', true );
			if( $user_role ) {
				$user_roles[] = $user_role;
			}

			if ( 'panorama_product' === $product->get_type() ) {

				$project_id = method_exists( $product, 'get_meta' ) ? $product->get_meta( '_panorama_project_id' ) : get_post_meta( $product_id, '_panorama_project_id', true );
				$new_id 	= WC_Product_Panorama_Product::duplicate_project( $project_id, $user_id );

				add_post_meta( $order_id, '_purchased_psp_project', $new_id );

				$result = true;

			}

		}

		// Check to see if there is a user role set
		if( !empty($user_roles) ) {

			$user = new WP_User( $user_id );

			foreach( $user_roles as $new_role ) {

				$user->add_role( $new_role );

				if( $new_role == 'subscriber' || $new_role = '' ) {
					continue;
				}

				// If this is a PM, PO or PC give access to dashboard
				$user->add_cap( 'view_admin_dashboard' );

			}

		}

		return $result;

	}

	/**
	 * Handles `woocommerce_thankyou` action.
	 *
	 * @param int $order_id The order being set to complete.
	 * @return void
	 */
	function auto_complete_order( $order_id ) {

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( $order && $order->status != 'completed' ) {

			$result = WC_Product_Panorama_Product::panorama_process_order( $order_id );

			if( $result ) {
				$order->update_status( 'completed' );
			}

		}

	}

	/**
	 * Handles the `woocommerce_panorama_product_add_to_cart` action
     *
     * Shows the add to cart button
	 *
	 * @return void
	 */
	function show_add_to_cart() {
		wc_get_template( 'single-product/add-to-cart/simple.php' );
	}

    /**
     * Get internal type.
     * Needed for WooCommerce 3.0 Compatibility
     *
     * @return string
     */
    public function get_type() {
        return 'panorama_product';
	}

	function create_duplicate( $post, $status = null, $new_post_author = null ) {

		// We don't want to clone revisions
		if ($post->post_type == 'revision') return;

		if ($post->post_type != 'attachment'){
			$prefix = get_option('duplicate_post_title_prefix');
			$suffix = get_option('duplicate_post_title_suffix');
			if (!empty($prefix)) $prefix.= " ";
			if (!empty($suffix)) $suffix = " ".$suffix;
			if (get_option('duplicate_post_copystatus') == 0) $status = 'publish';
		}

		if( !$new_post_author ) {
			$new_post_author = wp_get_current_user();
		}

		$new_post = array(
			'menu_order' 		=> $post->menu_order,
			'comment_status' 	=> $post->comment_status,
			'ping_status' 		=> $post->ping_status,
			'post_author' 		=> $new_post_author->ID,
			'post_content' 		=> $post->post_content,
			'post_excerpt' 		=> (get_option('duplicate_post_copyexcerpt') == '1') ? $post->post_excerpt : "",
			'post_mime_type' 	=> $post->post_mime_type,
			'post_parent' 		=> $new_post_parent = empty($parent_id)? $post->post_parent : $parent_id,
			'post_password' 	=> $post->post_password,
			'post_status' 		=> $new_post_status = (empty($status))? $post->post_status: $status,
			'post_title' 		=> $prefix.$post->post_title.$suffix,
			'post_type' 		=> $post->post_type,
		);

		if(get_option('duplicate_post_copydate') == 1){
			$new_post['post_date'] = $new_post_date =  $post->post_date ;
			$new_post['post_date_gmt'] = get_gmt_from_date($new_post_date);
		}

		$new_post_id = wp_insert_post($new_post);

		// If you have written a plugin which uses non-WP database tables to save
		// information about a post you can hook this action to dupe that data.
		if ($post->post_type == 'page' || (function_exists('is_post_type_hierarchical') && is_post_type_hierarchical( $post->post_type )))
		do_action( 'psp_woo_duplicate_page', $new_post_id, $post );
		else
		do_action( 'psp_woo_duplicate_post', $new_post_id, $post );

		delete_post_meta($new_post_id, '_dp_original');
		delete_post_meta($new_post_id, '_psp_fe_global_template' );
		add_post_meta($new_post_id, '_dp_original', $post->ID);

		// If the copy is published or scheduled, we have to set a proper slug.
		if ($new_post_status == 'publish' || $new_post_status == 'future'){
			$post_name = wp_unique_post_slug($post->post_name, $new_post_id, $new_post_status, $post->post_type, $new_post_parent);

			$new_post = array();
			$new_post['ID'] = $new_post_id;
			$new_post['post_name'] = $post_name;

			// Update the post into the database
			wp_update_post( $new_post );
		}

		return $new_post_id;

	}

	/**
	 * Duplicates a project for a user and makes them active.
     *
	 * @static
     * @param int $project_id The ID of the project being duplicated.
     * @param int $user_id    The user that purchased the project.
	 * @return void
	 */
	static function duplicate_project( $project_id , $user_id ) {

		require_once( PROJECT_PANORAMA_DIR . '/lib/vendor/clone/duplicate-post-admin.php' );

		$post      = get_post( $project_id );
		$user      = get_user_by( 'id', $user_id );
		$new_id    = WC_Product_Panorama_Product::create_duplicate( $post, 'publish', $user );

		if ( 0 !== $new_id ) {

			update_post_meta( $new_id, '_psp_assigned_users', array( $user_id ) );
			update_post_meta( $new_id, 'allowed_users_0_user', $user_id );
			update_post_meta( $new_id, 'allowed_users', 1 );
			update_post_meta( $project_id, '_psp_cloned', 1 );
			update_post_meta( $new_id, 'client', $user->first_name . ' ' . $user->last_name );

			update_field( 'restrict_access_to_specific_users', array( 'Yes' ), $new_id );

			$new_project = array(
				'ID'          	=> $new_id,
				'post_status' 	=> 'publish',
				'post_title'	=> $user->first_name . ' ' . $user->last_name . ': ' . get_the_title($new_id),
				'post_name'		=>	''
			);

			wp_update_post( $new_project );

			return $new_id;

		}
	}

	function panorama_copy_post_meta_info( $new_id, $post ) {

		$post_meta_keys = get_post_custom_keys($post->ID);

		if (empty($post_meta_keys)) return;

		foreach ($post_meta_keys as $meta_key) {
			$meta_values = get_post_custom_values($meta_key, $post->ID);
			foreach ($meta_values as $meta_value) {
				$meta_value = maybe_unserialize($meta_value);
				add_post_meta($new_id, $meta_key, $meta_value);
			}
		}

	}

	/**
	 * Adds a metabox to projects to designate it as a product template
     *
	 * @return void
	 */
	function panorama_woocommerce_template_metabox() {

		global $post;

		if ( 'psp_projects' != get_post_type($post ) ) {
			return;
		}

		$value = get_post_meta( $post->ID, '_psp_woocommerce_template', true ); ?>

		<div class="misc-pub-section misc-pub-section-last" style="border-top: 1px solid #eee;">
			<?php wp_nonce_field( plugin_basename( __FILE__ ), 'psp-woocommerce' ); ?>
			<input type="checkbox" name="psp-woocommerce-template" value="yes" <?php checked( 'yes', $value ); ?> />
			<label for="psp-woocommerce-template">
				<?php esc_html_e( 'WooCommerce Template', 'psp_projects' ); ?>
			</label>
		</div>

		<?php
	}

	/**
	 * Saves the meta value from panorama_woocommerce_template_metabox
	 *
	 * @return void
	 */
	function panorama_woocommerce_save_meta( $post_id ) {

		if( 'psp_projects' != get_post_type($post_id) ) {
			return;
		}

		if( isset($_POST['psp-woocommerce-template']) && $_POST['psp-woocommerce-template'] == 'yes' ) {
			update_post_meta( $post_id, '_psp_woocommerce_template', 'yes' );
		} else {
			delete_post_meta( $post_id, '_psp_woocommerce_template' );
		}

	}

	/**
	 * Adds link to Panorama dashboard to WooCommerce reciept
	 * @return [type] [description]
	 */
	function custom_order_text( $order_id  ) {

		$projects = get_post_meta( $order_id, '_purchased_psp_project', false );

		if( empty($projects) ) {
			return;
		} ?>

		<h2><?php echo esc_html_e( 'Purchased Projects', 'psp-woocommerce' ); ?></h2>
		<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Project', 'psp-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach( $projects as $post_id ): ?>
					<tr>
						<td><a target="_new" href="<?php echo esc_url(get_the_permalink($post_id)); ?>"><?php echo esc_html( get_the_title($post_id) ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php
	}

	function custom_order_email_text( $order, $sent_to_admin, $plain_text, $email ) {

		$order_id = $order->get_id();

		$projects = get_post_meta( $order_id, '_purchased_psp_project', false );

		if( empty($projects) ) {
			return;
		} ?>

		<h2><?php echo esc_html_e( 'Purchased Projects', 'psp-woocommerce' ); ?></h2>
		<table style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; color: #636363; border: 1px solid #e5e5e5;">
			<tbody>
				<?php foreach( $projects as $post_id ): ?>
					<tr>
						<td style="text-align: left; vertical-align: middle; border: 1px solid #eee; word-wrap: break-word; color: #636363; padding: 12px;"><a target="_new" href="<?php echo esc_url(get_the_permalink($post_id)); ?>"><?php echo esc_html( get_the_title($post_id) ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php
	}

	function panorama_check_required_account() {

		$require_account = false;

		global $woocommerce;

		// loop through order items to get Robly sublist IDs
	   foreach ( $woocommerce->cart->cart_contents as $item ) {

		   $product = wc_get_product( $item['product_id'] );

		   if ( 'panorama_product' === $product->get_type() ) {
			   $require_account = true;
		   }

	   }

	   return $require_account;

	}

	function panorama_require_checkout_registration( $checkout = '' ) {

		// If the user is logged in or doesn't have a panorama project we don't need this
		if( is_user_logged_in() || !WC_Product_Panorama_Product::panorama_check_required_account() ) {
			return;
		}

		global $signup_option_changed, $guest_checkout_option_changed, $woocommerce;

		// ensure users can sign up
        if ( false === $checkout->enable_signup ) {
            $checkout->enable_signup = true;
            $signup_option_changed = true;
        }

        // ensure users are required to register an account
        if ( true === $checkout->enable_guest_checkout ) {
            $checkout->enable_guest_checkout = false;
            $guest_checkout_option_changed = true;

            if ( ! is_user_logged_in() ) {
                $checkout->must_create_account = true;
            }
        }

	}

	function panorama_require_checkout_account_fields( $checkout_fields ) {

		if( is_user_logged_in() || !WC_Product_Panorama_Product::panorama_check_required_account() ) {
			return $checkout_fields;
		}

		$account_fields = array(
            'account_username',
            'account_password',
            'account_password-2',
        );

        foreach ( $account_fields as $account_field ) {
            if ( isset( $checkout_fields['account'][ $account_field ] ) ) {
                $checkout_fields['account'][ $account_field ]['required'] = true;
            }
        }

		return $checkout_fields;

	}

	function panorama_restore_checkout_registration_settings( $checkout = '' ) {

		global $signup_option_changed, $guest_checkout_option_changed;

	    if ( $signup_option_changed ) {
	        $checkout->enable_signup = false;
	    }

	    if ( $guest_checkout_option_changed ) {
	        $checkout->enable_guest_checkout = true;
	        if ( ! is_user_logged_in() ) { // Also changed must_create_account
	            $checkout->must_create_account = false;
	        }
	    }

	}

	function force_login( $checkout ) {

		$cart_items = WC()->cart->cart_contents;

		foreach ( $cart_items as $key => $item ) {

			$product = wc_get_product( $item['product_id'] );

			 if ( 'panorama_product' === $product->get_type() ) {
					WC_Product_Panorama_Product::add_front_scripts();
			 }
		}

	}

	public function add_front_scripts() {
		wp_enqueue_script( 'pano_woo_front', plugins_url( '/front.js', __FILE__ ), array( 'jquery' ) );
	}

}
