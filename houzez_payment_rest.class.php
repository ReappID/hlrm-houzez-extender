<?php

require_once 'payment.class.php';

class Houzez_Payment_Rest extends WP_REST_Controller
{


  /**
   * Register the routes for the objects of the controller.
   */
  public function register_routes()
  {
    $version = '1';
    $namespace = 'houzez/v' . $version;
    $base = 'payment';
    register_rest_route($namespace, '/' . $base . '/midtrans/snap_token', array(
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => array($this, 'getMidtransSnap'),
      'permission_callback' => function () {
        return true;
      }
    ));
    register_rest_route('hlrm/v1', '/' . $base . '/midtrans', array(
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => array($this, 'midtrans_callback'),
      'permission_callback' => function () {
        return true;
      }
    ));
    register_rest_route('hlrm/v1', '/' . $base . '/xendit', array(
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => array($this, 'xendit_callback'),
      'permission_callback' => function () {
        return true;
      }
    ));
    register_rest_route($namespace, '/' . $base, array(
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => array($this, 'create_item'),
      'permission_callback' => function () {
        return true;
      }
    ));
    register_rest_route($namespace, '/' . $base . '/activate', array(
      'methods'             => WP_REST_Server::EDITABLE,
      'callback'            => array($this, 'activation_purchase'),
      'permission_callback' => $this->cek_auth()
    ));
  }

  public function cek_auth()
  {
    if (!is_user_logged_in()) {
      return false;
    }
    if (!is_admin()) {
      return false;
    }
    return true;
  }
  /**
   * Create one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function create_item($request)
  {
    global $current_user;

    $current_user = wp_get_current_user();

    if (!is_user_logged_in()) {
      return new WP_Error('cant-create', __('message', 'text-domain'), array('status' => 401));
    }
    $total_taxes = 0;
    // $item = $this->prepare_item_for_database( $request, $current_user );
    $userID = $current_user->ID;
    $user_email = $current_user->user_email;
    $selected_pack = intval($request['selected_package']);
    $total_price = get_post_meta($selected_pack, 'fave_package_price', true);
    $currency = esc_html(houzez_option('currency_symbol'));
    $where_currency = esc_html(houzez_option('currency_position'));
    $wire_payment_instruction = houzez_option('direct_payment_instruction');
    $is_featured = 0;
    $is_upgrade = 0;
    $paypal_tax_id = '';
    $paymentMethod = 'Direct Bank Transfer';
    $time = time();
    $date = date('Y-m-d H:i:s', $time);


    $pack_tax = floatval(get_post_meta($selected_pack, 'fave_package_tax', true));
    if (!empty($pack_tax) && !empty($total_price)) {
      $total_taxes = floatval($pack_tax) / 100 * floatval($total_price);
      $total_taxes = round($total_taxes, 2);
    }
    $total_price = $total_price + $total_taxes;

    if ($total_price != 0) {
      if ($where_currency == 'before') {
        $total_price = $currency . ' ' . $total_price;
      } else {
        $total_price = $total_price . ' ' . $currency;
      }
    }

    // insert invoice
    $invoiceID = houzez_generate_invoice('package', 'one_time', $selected_pack, $date, $userID, $is_featured, $is_upgrade, $paypal_tax_id, $paymentMethod, 1);


    if (function_exists('icl_translate')) {
      $mes_wire = strip_tags($wire_payment_instruction);
      $payment_details = icl_translate('houzez', 'houzez_wire_payment_instruction_text', $mes_wire);
    } else {
      $payment_details = strip_tags($wire_payment_instruction);
    }

    update_post_meta($invoiceID, 'invoice_payment_status', 0);
    $admin_email      =  get_bloginfo('admin_email');

    $args = array(
      'invoice_no'      =>  $invoiceID,
      'total_price'     =>  $total_price,
      'payment_details' =>  $payment_details,
    );

    /*
     * Send email
     * */
    houzez_email_type($user_email, 'new_wire_transfer', $args);
    houzez_email_type($admin_email, 'admin_new_wire_transfer', $args);

    if (true) {
      return new WP_REST_Response(array('message' => 'success', 'data' => $args), 200);
      exit;
    }

    return new WP_Error('cant-create', __('message', 'text-domain'), array('status' => 500));
  }

  public function activation_purchase($request)
  {

    try {
      $args = $this->activatenow($request);
      // if($args['status'] == 'success')
      return new WP_REST_Response(array('message' => $args['status'], 'data' => $args), 200);
      exit;
    } catch (\Throwable $th) {
      //throw $th;
      return new WP_Error('cant-create', __('message', 'text-domain'), array('status' => 500));
    }
  }

  private function activatenow($payload)
  {
    // $packID = intval($payload['item_id']);
    $invoiceID = intval($payload['invoice_id']);
    $packID = get_post_meta($invoiceID, 'HOUZEZ_invoice_item_id', true);
    $userID = get_post_meta($invoiceID, 'HOUZEZ_invoice_buyer', true);

    // print_r($payload['invoice_id']);exit;

    $user           =   get_user_by('id', $userID);
    $user_email     =   $user->user_email;
    $phone = get_user_meta($userID, 'fave_author_mobile', true);
    if($phone == ''){
      $phone = get_user_meta($userID, 'fave_author_whatsapp', true);
    }else if(str_starts_with($phone, '0')){
      $phone = ltrim($phone, '0');
      $phone = "62".$phone;
    }

    houzez_save_user_packages_record($userID, $packID);
    if (houzez_check_user_existing_package_status($userID, $packID)) {
      houzez_downgrade_package($userID, $packID);
      houzez_update_membership_package($userID, $packID);
    } else {
      houzez_update_membership_package($userID, $packID);
    }

    update_post_meta($invoiceID, 'invoice_payment_status', 1);
    $args = ['status' => 'failed'];
    if(get_post_meta($invoiceID, 'invoice_payment_status', true) == 1){
      $args = array(
        'status' => 'success',
        'phone' => $phone,
        'email' => $user_email,
        'name' => $user->nicename
      );
  
     
    }else{
      $args = array(
        'status' => 'failed',
        'phone' => $phone,
        'email' => $user_email,
        'name' => $user->nicename
      );
    }
    houzez_email_type($user_email, 'purchase_activated_pack', $args);
  
    return $args;
   
  }

  public function getMidtransSnap($request)
  {
    global $current_user;

    $current_user = wp_get_current_user();

    if (!is_user_logged_in() && $request['user_email'] == null) {
      return new WP_Error('cant-create', __('message', 'text-domain'), array('status' => 401));
    }
    $py = new Payment(false, false, true, false);

    $total = get_post_meta(intval($request['selected_package']), 'fave_package_price', true);
    $package = get_post(intval($request['selected_package']));

    $snapToken = $py->doPayment($current_user, $total, 'Pembelian paket ' . $package->post_title . ' di Halorumah.id', null, false);

    if ($snapToken) {
      return new WP_REST_Response(array('message' => 'success', 'data' => ['snap_token' => $snapToken]), 200);
      exit;
    }
  }

  public function midtrans_callback()
  {
    \Midtrans\Config::$serverKey = getenv_docker('MIDTRANS_SERVER_KEY', '');
    \Midtrans\Config::$isProduction = getenv_docker('MIDTRANS_IS_PRODUCTION', 0) == 1 ? true : false;
    $notif = new \Midtrans\Notification();
    $transaction = $notif->transaction_status;
    $fraud = $notif->fraud_status;

    if($transaction == 'settlement'){
      $res = $this->activatenow(array(
        'invoice_id' => $notif->order_id
      ));

      if ($res) {
        return new WP_REST_Response(array('message' => 'success', 'data' => $res), 200);
        exit;
      }
    }

    if ($transaction == 'capture') {
      if ($fraud == 'challenge') {
        // TODO Set payment status in merchant's database to 'challenge'
      } else if ($fraud == 'accept') {
      }
    } else if ($transaction == 'cancel') {
      if ($fraud == 'challenge') {
        // TODO Set payment status in merchant's database to 'failure'
      } else if ($fraud == 'accept') {
        // TODO Set payment status in merchant's database to 'failure'
      }
    } else if ($transaction == 'deny') {
      // TODO Set payment status in merchant's database to 'failure'
    }
  }

  public function xendit_callback($request)
  {
    // \Xendit\Xendit::setApiKey(XENDIT_KEY);
    
    if($request['status'] == 'PAID'){
      $res = $this->activatenow(array(
        'invoice_id' => $request['external_id']
      ));

      if ($res) {
        return new WP_REST_Response(array('message' => 'success', 'data' => $res), 200);
        exit;
      }
    }else{
      return new WP_REST_Response(array('message' => 'success', 'data' => $request), 200);
      exit;
    }

    
  }
}
