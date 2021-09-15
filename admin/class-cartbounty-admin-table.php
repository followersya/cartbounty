<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines how Abandoned cart Table should be displayed
 *
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce
 * @subpackage CartBounty - Save and recover abandoned carts for WooCommerce/admin
 * @author     Streamline.lv
 */
 
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
 
class CartBounty_Table extends WP_List_Table{

   /**
    * Constructor, we override the parent to pass our own arguments
    * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
    *
    * @since    1.0
    */
    function __construct(){
        global $status, $page;

        parent::__construct(array(
            'singular' => 'id',
            'plural' => 'ids',
        ));
    }
	
	/**
     * This method return columns to display in table
     *
     * @since    1.0
     * @return   array
     */
	function get_columns(){
        return $columns = array(
            'cb'            => 		'<input type="checkbox" />',
            'id'            =>		__('ID', 'woo-save-abandoned-carts'),
            'name'          =>		__('Name, Surname', 'woo-save-abandoned-carts'),
            'email'         =>		__('Email', 'woo-save-abandoned-carts'),
            'phone'			=>		__('Phone', 'woo-save-abandoned-carts'),
            'location'      =>      __('Location', 'woo-save-abandoned-carts'),
            'cart_contents' =>		__('Cart contents', 'woo-save-abandoned-carts'),
            'cart_total'    =>		__('Cart total', 'woo-save-abandoned-carts'),
            'time'          =>		__('Time', 'woo-save-abandoned-carts'),
            'status'            =>      __('Status', 'woo-save-abandoned-carts')
        );
	}
	
	/**
     * This method return columns that may be used to sort table
     * all strings in array - is column names
     * True on name column means that its default sort
     *
     * @since    1.0
     * @return   array
     */
	public function get_sortable_columns(){
		return $sortable = array(
			'id'                =>      array('id', true),
            'name'              =>      array('name', true),
            'email'             =>      array('email', true),
            'phone'             =>      array('phone', true),
            'cart_total'        =>      array('cart_total', true),
            'time'              =>      array('time', true)
		);
	}
	
	/**
     * This is a default column renderer
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     * @param    $column_name - string (key)
     */
    function column_default( $item, $column_name ){
        return $item[$column_name];
    }
	
	/**
     * Rendering Name column
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_name( $item ){
        $cart_status = 'all';
        if (isset($_GET['cart-status'])){
            $cart_status = $_GET['cart-status'];
        }

        $actions = array(
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s&cart-status='. $cart_status .'">%s</a>', esc_html($_REQUEST['page']), esc_html($item['id']), __('Delete', 'woo-save-abandoned-carts')),
        );

        $name_array = array();

        if(!empty($item['name'])){
            $name_array[] = $item['name'];
        }

        if(!empty($item['surname'])){
            $name_array[] = $item['surname'];
        }

        $name = implode(' ', $name_array);

        if(get_user_by('id', $item['session_id'])){ //If the user is registered, add link to his profile page
            $name = '<a href="' . add_query_arg( 'user_id', $item['session_id'], self_admin_url( 'user-edit.php')) . '" title="' . __( 'View user profile', 'woo-save-abandoned-carts' ) . '">'. $name .'</a>';
        }

        return sprintf('<svg class="cartbounty-customer-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 450 506"><path d="M225,0A123,123,0,1,0,348,123,123.14,123.14,0,0,0,225,0Z"/><path d="M393,352.2C356,314.67,307,294,255,294H195c-52,0-101,20.67-138,58.2A196.75,196.75,0,0,0,0,491a15,15,0,0,0,15,15H435a15,15,0,0,0,15-15A196.75,196.75,0,0,0,393,352.2Z"/></svg>%s %s',
            $name,
            $this->row_actions($actions)
        );
    }
	
	/**
     * Rendering Email column
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_email( $item ){
        return sprintf('<a href="mailto:%1$s">%1$s</a>',
            esc_html($item['email'])
        );
    }

    /**
     * Rendering Location column
     *
     * @since    4.6
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_location( $item ){
        if(is_serialized($item['location'])){ //Since version 4.6
            $location_data = @unserialize($item['location']);
            $country = $location_data['country'];
            $city = $location_data['city'];
            $postcode = $location_data['postcode'];

        }else{ //Prior version 4.6. Will be removed in future releases
            $parts = explode(',', $item['location']); //Splits the Location field into parts where there are commas
            if (count($parts) > 1) {
                $country = $parts[0];
                $city = trim($parts[1]); //Trim removes white space before and after the string
            }
            else{
                $country = $parts[0];
                $city = '';
            }

            $postcode = '';
            if(is_serialized($item['other_fields'])){
                $other_fields = @unserialize($item['other_fields']);
                if(isset($other_fields['cartbounty_billing_postcode'])){
                    $postcode = $other_fields['cartbounty_billing_postcode'];
                }
            }
        }

        if($country && class_exists('WooCommerce')){ //In case WooCommerce is active and we have Country data, we can add abbreviation to it with a full country name
            $country = '<abbr class="cartbounty-country" title="' . WC()->countries->countries[ $country ] . '">' . esc_html($country) . '</abbr>';
        }

        $location = $country;
        if(!empty($city)){
             $location .= ', ' . $city;
        }
        if(!empty($postcode)){
             $location .= ', ' . $postcode;
        }

        return sprintf('%s',
            $location
        );
    }
	
	/**
     * Rendering Cart Contents column
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_cart_contents( $item ){
        if(!is_serialized($item['cart_contents'])){
            return;
        }

        $admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
        $product_array = @unserialize($item['cart_contents']); //Retrieving array from database column cart_contents
        $output = '';
        
        if($product_array){
            if(get_option('cartbounty_hide_images')){ //Outputing Cart contents as a list
                $output = '<ul class="cartbounty-product-list">';
                foreach($product_array as $product){
                    if(is_array($product)){
                        if(isset($product['product_title'])){
                            $product_title = esc_html($product['product_title']);
                            $quantity = " (". $product['quantity'] .")"; //Enclose product quantity in brackets
                            $edit_product_link = get_edit_post_link( $product['product_id'], '&' ); //Get product link by product ID
                            $product_price = $admin->get_product_price( $product );
                            $price = ', ' . $admin->format_price( $product_price, esc_html($item['currency']));
                            if($edit_product_link){ //If link exists (meaning the product hasn't been deleted)
                                $output .= '<li><a href="'. $edit_product_link .'" title="'. $product_title .'" target="_blank">'. $product_title . $price . $quantity .'</a></li>';
                            }else{
                                $output .= '<li>'. $product_title . $price . $quantity .'</li>';
                            }
                        }
                    }
                }
                $output .= '</ul>';

            }else{ //Displaying cart contents with thumbnails
                foreach($product_array as $product){
                    if(is_array($product)){
                        if(isset($product['product_title'])){
                            //Checking product image
                            if(!empty($product['product_variation_id'])){ //In case of a variable product
                                $image = get_the_post_thumbnail_url($product['product_variation_id'], 'thumbnail');
                                if(empty($image)){ //If variation didn't have an image set
                                    $image = get_the_post_thumbnail_url($product['product_id'], 'thumbnail');
                                }
                            }else{ //In case of a simple product
                                 $image = get_the_post_thumbnail_url($product['product_id'], 'thumbnail');
                            }

                            if(empty($image) && class_exists('WooCommerce')){ //In case WooCommerce is active and product has no image, output default WooCommerce image
                                $image = wc_placeholder_img_src('thumbnail');
                            }

                            $product_title = esc_html($product['product_title']);
                            $quantity = " (". $product['quantity'] .")"; //Enclose product quantity in brackets
                            $edit_product_link = get_edit_post_link( $product['product_id'], '&' ); //Get product link by product ID
                            $product_price = $admin->get_product_price( $product );
                            $price = ', ' . $admin->format_price( $product_price, esc_html($item['currency']));
                            if($edit_product_link){ //If link exists (meaning the product hasn't been deleted)
                                $output .= '<div class="cartbounty-abandoned-product"><span class="cartbounty-tooltip">'. $product_title . $price . $quantity .'</span><a href="'. $edit_product_link .'" title="'. $product_title .'" target="_blank"><img src="'. $image .'" title="'. $product_title .'" alt ="'. $product_title .'" /></a></div>';
                            }else{
                                $output .= '<div class="cartbounty-abandoned-product"><span class="cartbounty-tooltip">'. $product_title . $price . $quantity .'</span><img src="'. $image .'" title="'. $product_title .'" alt ="'. $product_title .'" /></div>';
                            }
                        }
                    }
                }
            }
        }
        return sprintf('%s', $output );
    }
	
	/**
     * Rendering Cart Total column
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_cart_total( $item ){
        return sprintf('%0.2f %s',
            esc_html($item['cart_total']),
            esc_html($item['currency'])
        );
    }
	
	/**
     * Render Time column 
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     */
	function column_time( $item ){
		$time = new DateTime($item['time']);
		$date_iso = $time->format('c');
        $date_title = $time->format('M d, Y H:i:s');
        $utc_time = $time->format('U');

        if($utc_time > strtotime( '-1 day', current_time( 'timestamp' ))){ //In case the abandoned cart is newly captued
             $friendly_time = sprintf( 
                /* translators: %1$s - Time, e.g. 1 minute, 5 hours */
                __( '%1$s ago', 'woo-save-abandoned-carts' ),
                human_time_diff( $utc_time,
                current_time( 'timestamp' ))
            );
        }else{ //In case the abandoned cart is older tahn 24 hours
            $friendly_time = $time->format('M d, Y');
        }

		return sprintf( '<svg class="cartbounty-time-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 31.18 31.18"><path d="M15.59,31.18A15.59,15.59,0,1,1,31.18,15.59,15.6,15.6,0,0,1,15.59,31.18Zm0-27.34A11.75,11.75,0,1,0,27.34,15.59,11.76,11.76,0,0,0,15.59,3.84Z"/><path d="M20.39,20.06c-1.16-.55-6-3-6.36-3.19s-.46-.76-.46-1.18V7.79a1.75,1.75,0,1,1,3.5,0v6.88s4,2.06,4.8,2.52a1.6,1.6,0,0,1,.69,2.16A1.63,1.63,0,0,1,20.39,20.06Z"/></svg><time datetime="%s" title="%s">%s</time>', esc_html($date_iso), esc_html($date_title), esc_html($friendly_time));
	}

    /**
     * Rendering Status column
     *
     * @since    7.0
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_status( $item ){
        $admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
        $cart_time = strtotime($item['time']);
        $date = date_create(current_time( 'mysql', false ));
        $current_time = strtotime(date_format($date, 'Y-m-d H:i:s'));
        $status = '';

        if($item['type'] == 1){
            $status .= sprintf('<span class="status recovered">%s</span>', __('Recovered', 'woo-save-abandoned-carts'));
        }

        if($cart_time > $current_time - $admin->get_waiting_time() * 60 && $item['type'] != 1){ //Checking time if user is still shopping or might return - we add shopping label
            $status .= sprintf('<span class="status shopping">%s</span>', __('Shopping', 'woo-save-abandoned-carts'));

        }else{
            if($cart_time > ($current_time - CARTBOUNTY_NEW_NOTICE * 60 )){ //Checking time if user has not gone through with the checkout after the specified time we add new label
                $status_description = __('Recently abandoned', 'woo-save-abandoned-carts');
                if($item['type'] == 1){
                     $status_description = __('Recently recovered', 'woo-save-abandoned-carts');
                }
                $status .= sprintf('<div class="status-item-container"><span class="cartbounty-tooltip">%s</span><span class="status new">%s</span></div>', $status_description, __('New', 'woo-save-abandoned-carts'));
            }

            if($item['type'] != 1){ //In case if the cart has not been recovered - output synced information
                if($item['wp_steps_completed']){
                    $wordpress = new CartBounty_WordPress();
                    $email_history = $wordpress->display_email_history( $item['id'] ); //Getting email history of current cart
                    $status .= sprintf('<div class="status-item-container email-history"><span class="cartbounty-tooltip">%s%s</span><span class="status synced wordpress">%s</span></div>', __('Sent via WordPress', 'woo-save-abandoned-carts'), $email_history, __('WP', 'woo-save-abandoned-carts'));
                }
            }
        }
        return $status;
    }

	/**
     * Rendering checkbox column
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     */
	function column_cb( $item ){
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			esc_html($item['id'])
		);
	}
	
	/**
     * Return array of bulk actions if we have any
     *
     * @since    1.0
     * @return   array
     */
	 function get_bulk_actions(){
        $actions = array(
            'delete' => __('Delete', 'woo-save-abandoned-carts')
        );
        return $actions;
    }

    /**
     * This method processes bulk actions
     *
     * @since    1.0
     */
    function process_bulk_action(){
        global $wpdb;
        $cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
        $footer_bulk_delete = false;

        if( isset( $_GET['action2'] ) && $_GET['action2'] == 'delete' ){ //Check if bottom Bulk delete action fired
             $footer_bulk_delete = true;
        }

        if ('delete' === $this->current_action() || $footer_bulk_delete ) {
            if(empty($_REQUEST['id'])){ //Exit in case no row selected
                return;
            }
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (!empty($ids)){
                if(is_array($ids)){ //Bulk abandoned cart deletion
                    foreach ($ids as $key => $id){
                        $wpdb->query(
                            $wpdb->prepare(
                                "DELETE FROM $cart_table
                                WHERE id = %d",
                                intval($id)
                            )
                        );
                    }
                }else{ //Single abandoned cart deletion
                    $id = $ids;
                    $wpdb->query(
                        $wpdb->prepare(
                            "DELETE FROM $cart_table
                            WHERE id = %d",
                            intval($id)
                        )
                    );
                }
            }
        }
    }

	/**
     * Method that renders the table
     *
     * @since    1.0
     */
	function prepare_items(){
        global $wpdb;
        $cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

        $cart_status = 'all';
        if (isset($_GET['cart-status'])){
            $cart_status = $_GET['cart-status'];
        }

        $screen = get_current_screen();
        $user = get_current_user_id();
        $option = $screen->get_option('per_page', 'option');
        $per_page = get_user_meta($user, $option, true);

        //How much records will be shown per page, if the user has not saved any custom values under Screen options, then default amount of 10 rows will be shown
        if ( is_array($per_page) || $per_page < 1 ) {
            $per_page = 10;
        }

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable); // here we configure table headers, defined in our methods
        $this->process_bulk_action(); // process bulk action if any
        $admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
        $total_items = $admin->get_cart_count($cart_status);

        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'time';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';

        // configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));

        $where_sentence = $admin->get_where_sentence($cart_status);
        $this->items = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $cart_table
            WHERE cart_contents != '' AND
            type != 2
            $where_sentence
            ORDER BY $orderby $order
            LIMIT %d OFFSET %d",
        $per_page, $paged * $per_page), ARRAY_A);
    }
}