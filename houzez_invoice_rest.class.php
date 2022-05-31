<?php
 
class Houzez_Invoice_Rest extends WP_REST_Controller {
 
  private $db;

  public function __construct()
  {
    global $wpdb;
    $this->db = $wpdb;
  }

  // this for refrence postmeta
//  edit	897	2764	invoice_payment_status	1
//  edit	898	2764	HOUZEZ_invoice_date	2016-10-13 18:39:36
//  edit	899	2764	HOUZEZ_paypal_txn_id	
//  edit	900	2764	HOUZEZ_invoice_payment_method	Paypal
//  edit	901	2764	_houzez_invoice_meta	a:8:{s:19:"invoice_billion_for";s:7:"package";s:20:"invoice_billing_type";s:9:"Recurring";s:15:"invoice_item_id";i:1241;s:18:"invoice_item_price";s:4:"9.99";s:21:"invoice_purchase_date";s:19:"2016-10-22 13:04:37";s:16:"invoice_buyer_id";i:235;s:13:"paypal_txn_id";s:0:"";s:22:"invoice_payment_method";s:6:"Stripe";}
//  edit	902	2764	HOUZEZ_invoice_price	9.99
//  edit	903	2764	HOUZEZ_invoice_buyer	1
//  edit	904	2764	HOUZEZ_invoice_type	One Time
//  edit	905	2764	HOUZEZ_invoice_for	package
//  edit	906	2764	HOUZEZ_invoice_item_id	1241


  /**
   * Register the routes for the objects of the controller.
   */
  public function register_routes() {
    $version = '1';
    $namespace = 'houzez/v' . $version;
    $base = 'invoices';
    register_rest_route( $namespace, '/' . $base, array(
      array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => array( $this, 'get_items' ),
        'permission_callback' => array( $this, 'get_items_permissions_check' ),
        'args'                => array(),
      ),
      array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => array( $this, 'create_item' ),
        'permission_callback' => array( $this, 'create_item_permissions_check' ),
        'args'                => $this->get_endpoint_args_for_item_schema( true ),
      ),
    ) );
    
    register_rest_route( $namespace, '/' . $base . '/(?P<id>[\d]+)', array(
      array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => array( $this, 'get_item' ),
        'permission_callback' => array( $this, 'get_item_permissions_check' ),
        'args'                => array(
          'context' => array(
            'default' => 'view',
          ),
        ),
      ),
      array(
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => array( $this, 'update_item' ),
        'permission_callback' => array( $this, 'update_item_permissions_check' ),
        'args'                => $this->get_endpoint_args_for_item_schema( false ),
      ),
      array(
        'methods'             => WP_REST_Server::DELETABLE,
        'callback'            => array( $this, 'delete_item' ),
        'permission_callback' => array( $this, 'delete_item_permissions_check' ),
        'args'                => array(
          'force' => array(
            'default' => false,
          ),
        ),
      ),
    ) );
    register_rest_route( $namespace, '/' . $base . '/schema', array(
      'methods'  => WP_REST_Server::READABLE,
      'callback' => array( $this, 'get_public_item_schema' ),
    ) );
  }
 
  /**
   * Get a collection of items
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function get_items( $request ) {
    print_r($request);exit;
    $items = $this->db->query( 
        $this->db->prepare( 
            "DELETE FROM $this->db->postmeta WHERE post_id = %d AND meta_key = %s",
            13,
            'gargle'
        )
    );

    $data = array();
    foreach( $items as $item ) {
      $itemdata = $this->prepare_item_for_response( $item, $request );
      $data[] = $this->prepare_response_for_collection( $itemdata );
    }
 
    return new WP_REST_Response( $data, 200 );
  }
 
  /**
   * Get one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function get_item( $request ) {
    //get parameters from request
    $params = $request->get_params();
    $item = array();//do a query, call another class, etc
    $data = $this->prepare_item_for_response( $item, $request );
 
    //return a response or error based on some conditional
    if ( 1 == 1 ) {
      return new WP_REST_Response( $data, 200 );
    } else {
      return new WP_Error( 'code', __( 'message', 'text-domain' ) );
    }
  }
 
  /**
   * Create one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function create_item( $request ) {
    // $item = $this->prepare_item_for_database( $request );
 
    // if ( function_exists( 'slug_some_function_to_create_item' ) ) {
    //   $data = slug_some_function_to_create_item( $item );
    //   if ( is_array( $data ) ) {
    //     return new WP_REST_Response( $data, 200 );
    //   }
    // }
 
    return new WP_Error( 'cant-create', __( 'message', 'text-domain' ), array( 'status' => 500 ) );
  }
 
  /**
   * Update one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function update_item( $request ) {
    // $item = $this->prepare_item_for_database( $request );
 
    // if ( function_exists( 'slug_some_function_to_update_item' ) ) {
    //   $data = slug_some_function_to_update_item( $item );
    //   if ( is_array( $data ) ) {
    //     return new WP_REST_Response( $data, 200 );
    //   }
    // }
 
    return new WP_Error( 'cant-update', __( 'message', 'text-domain' ), array( 'status' => 500 ) );
  }
 
  /**
   * Delete one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function delete_item( $request ) {
    // $item = $this->prepare_item_for_database( $request );
 
    // if ( function_exists( 'slug_some_function_to_delete_item' ) ) {
    //   $deleted = slug_some_function_to_delete_item( $item );
    //   if ( $deleted ) {
    //     return new WP_REST_Response( true, 200 );
    //   }
    // }
 
    return new WP_Error( 'cant-delete', __( 'message', 'text-domain' ), array( 'status' => 500 ) );
  }
 
  /**
   * Check if a given request has access to get items
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function get_items_permissions_check( $request ) {
    //return true; <--use to make readable by all
    return current_user_can( 'edit_something' );
  }
 
  /**
   * Check if a given request has access to get a specific item
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function get_item_permissions_check( $request ) {
    return $this->get_items_permissions_check( $request );
  }
 
  /**
   * Check if a given request has access to create items
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function create_item_permissions_check( $request ) {
    return current_user_can( 'edit_something' );
  }
 
  /**
   * Check if a given request has access to update a specific item
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function update_item_permissions_check( $request ) {
    return $this->create_item_permissions_check( $request );
  }
 
  /**
   * Check if a given request has access to delete a specific item
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function delete_item_permissions_check( $request ) {
    return $this->create_item_permissions_check( $request );
  }
 
  /**
   * Prepare the item for create or update operation
   *
   * @param WP_REST_Request $request Request object
   * @return WP_Error|object $prepared_item
   */
  protected function prepare_item_for_database( $request ) {
    return array();
  }
 
  /**
   * Prepare the item for the REST response
   *
   * @param mixed $item WordPress representation of the item.
   * @param WP_REST_Request $request Request object.
   * @return mixed
   */
  public function prepare_item_for_response( $item, $request ) {
    return array();
  }
 
  /**
   * Get the query params for collections
   *
   * @return array
   */
  public function get_collection_params() {
    return array(
      'page'     => array(
        'description'       => 'Current page of the collection.',
        'type'              => 'integer',
        'default'           => 1,
        'sanitize_callback' => 'absint',
      ),
      'per_page' => array(
        'description'       => 'Maximum number of items to be returned in result set.',
        'type'              => 'integer',
        'default'           => 10,
        'sanitize_callback' => 'absint',
      ),
      'search'   => array(
        'description'       => 'Limit results to those matching a string.',
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
      ),
    );
  }
}