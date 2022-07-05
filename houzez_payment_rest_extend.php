<?php
require_once ABSPATH . 'vendor/autoload.php';
require_once('houzez_payment_rest.class.php');
require_once('payment.class.php');
require_once('fn.php');

add_action('rest_api_init', function () {
    $rest_class = new Houzez_Payment_Rest();
    $rest_class->register_routes();
});

add_action('after_setup_theme', 'extend_houzez_direct_payment', 0);

function extend_houzez_direct_payment()
{
    remove_action('wp_ajax_nopriv_houzez_direct_pay_package', 'houzez_direct_pay_package');
    remove_action('wp_ajax_houzez_direct_pay_package', 'houzez_direct_pay_package');

    // print_r(get_page_template());
    // exit;
   

    add_action('wp_ajax_nopriv_houzez_direct_pay_package', 'halorumah_payment_package');
    add_action('wp_ajax_houzez_direct_pay_package', 'halorumah_payment_package');
}

if (!function_exists('houzez_generate_invoice')) :
    function houzez_generate_invoice($billingFor, $billionType, $packageID, $invoiceDate, $userID, $featured, $upgrade, $paypalTaxID, $paymentMethod, $is_package = 0, $bank = '')
    {
        // header('Location: http://hlrm.stacks.run:12409/membership-info');exit;
        $total_taxes = 0;
        $price_per_submission = houzez_option('price_listing_submission');
        $price_featured_submission = houzez_option('price_featured_listing_submission');

        $price_per_submission      = floatval($price_per_submission);
        $price_featured_submission = floatval($price_featured_submission);

        $args = array(
            'post_title'    => 'Invoice ',
            'post_status'   => 'publish',
            'post_type'     => 'houzez_invoice'
        );
        $inserted_post_id =  wp_insert_post($args);

        if ($billionType != 'one_time') {
            $billionType = __('Recurring', 'houzez');
        } else {
            $billionType = __('One Time', 'houzez');
        }

        if ($is_package == 0) {
            if ($upgrade == 1) {
                $total_price = $price_featured_submission;
            } else {
                if ($featured == 1) {
                    $total_price = intval($price_per_submission) + intval($price_featured_submission);
                } else {
                    $total_price = $price_per_submission;
                }
            }
        } else {
            $pack_price = get_post_meta($packageID, 'fave_package_price', true);
            $pack_tax = get_post_meta($packageID, 'fave_package_tax', true);

            if (!empty($pack_tax) && !empty($pack_price)) {
                $total_taxes = intval($pack_tax) / 100 * $pack_price;
                $total_taxes = round($total_taxes, 2);
            }

            $total_price = $pack_price + $total_taxes;
        }

        $fave_meta = array();

        $fave_meta['invoice_billion_for'] = $billingFor;
        $fave_meta['invoice_billing_type'] = $billionType;
        $fave_meta['invoice_item_id'] = $packageID;
        $fave_meta['invoice_item_price'] = $total_price;
        $fave_meta['invoice_tax'] = $total_taxes;
        $fave_meta['invoice_purchase_date'] = $invoiceDate;
        $fave_meta['invoice_buyer_id'] = $userID;
        $fave_meta['paypal_txn_id'] = $paypalTaxID;
        $fave_meta['invoice_payment_method'] = $paymentMethod;
        $fave_meta['bank'] = $bank;

        update_post_meta($inserted_post_id, 'HOUZEZ_invoice_buyer', $userID);
        update_post_meta($inserted_post_id, 'HOUZEZ_invoice_type', $billionType);
        update_post_meta($inserted_post_id, 'HOUZEZ_invoice_for', $billingFor);
        update_post_meta($inserted_post_id, 'HOUZEZ_invoice_item_id', $packageID);
        update_post_meta($inserted_post_id, 'HOUZEZ_invoice_price', $total_price);
        update_post_meta($inserted_post_id, 'HOUZEZ_invoice_tax', $total_taxes);
        update_post_meta($inserted_post_id, 'HOUZEZ_invoice_date', $invoiceDate);
        update_post_meta($inserted_post_id, 'HOUZEZ_paypal_txn_id', $paypalTaxID);
        update_post_meta($inserted_post_id, 'HOUZEZ_invoice_payment_method',  $fave_meta['invoice_payment_method']);
        update_post_meta($inserted_post_id, 'HLRM_invoice_bank',  $bank);

        update_post_meta($inserted_post_id, '_houzez_invoice_meta', $fave_meta);

        // Update post title
        $update_post = array(
            'ID'         => $inserted_post_id,
            'post_title' => 'Invoice ' . $inserted_post_id,
        );
        wp_update_post($update_post);
        return $inserted_post_id;
    }
endif;

if (!function_exists('halorumah_payment_package')) {

    function halorumah_payment_package()
    {
        // print_r($_POST);exit;
        // ;wp_die();

        global $current_user;
        $source_payment = array(
            'payment_1' => 'Gopay/Midtrans',
            'payment_2' => 'VA_Account/Xendit',
            'payment_3' => 'Cards/Xendit',
            'payment_4' => 'Ewallet/Xendit',
            'payment_5' => 'Manual'
        );
        $total_taxes = 0;
        $current_user = wp_get_current_user();

        if (!is_user_logged_in()) {
            exit('Are you kidding?');
        }

        $userID = $current_user->ID;
        $user_email = $current_user->user_email;
        $selected_pack = intval($_POST['selected_package']);
        $bank = $_POST['bank'];
        // $method = $_POST['method'];
        $total_price = get_post_meta($selected_pack, 'fave_package_price', true);
        $currency = esc_html(houzez_option('currency_symbol'));
        $where_currency = esc_html(houzez_option('currency_position'));
        $wire_payment_instruction = houzez_option('direct_payment_instruction');
        $is_featured = 0;
        $is_upgrade = 0;
        $paypal_tax_id = '';
        $paymentMethod = 'Manual Transfer';

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
        $invoiceID = houzez_generate_invoice('package', 'one_time', $selected_pack, $date, $userID, $is_featured, $is_upgrade, $paypal_tax_id, $paymentMethod, 1, $bank);
        $package = get_package($selected_pack);


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
            'payment_details' =>  $payment_details
        );
        // $package['pack']
        if ($package['package_price'] > 0 && $bank != 'payment_5') {
            $pg = false;
            if($bank == 'payment_1'){
                $pg = new Payment(false,$bank,true,false);    
            }else if($bank == 'payment_2' || $bank == 'payment_3' || $bank == 'payment_4'){
                $pg = new Payment(false,$bank,false,true);
            }else{
                $pg = new Payment($bank);
            }
            
            $extra = array(
                'package' => $package,
                'payment_method' => $bank
            );
            $response = $pg->doPayment($current_user, intval($package['package_price']), 'Payment for ' . $package['package_name'], $invoiceID.'', $extra);
            // print_r($response);exit;
            if ($response) {
                update_post_meta($invoiceID, 'HLRM_invoice_payment_gateway_uses', $response['payment_vendor']);
                update_post_meta($invoiceID, 'HLRM_invoice_payment_gateway_status', $response['payment_status']);
                update_post_meta($invoiceID, 'HLRM_invoice_payment_gateway_va_number', $response['payment_number']);
                update_post_meta($invoiceID, 'HLRM_invoice_payment_gateway_bank', $response['payment_bank_vendor']);

                /*
                * Send email
                * */
                houzez_email_type($user_email, 'new_wire_transfer', $args);
                houzez_email_type($admin_email, 'admin_new_wire_transfer', $args);

                 // send to discord
                 if(DISCORD_ENABLED == 1){
                    $x = send_bg(getenv_docker('NOTIF_URL_NEW_PAYMENT_DISCORD', 'http://localhost:3001/payment/manual'), array(
                        'user_email' => $user_email,
                        'invoice_id' => $invoiceID,
                        'item_id' => isset($package['ID']) ? $package['ID'] : '',
                        'item' => $package,
                        'user' => $current_user,
                        'source' => isset($source_payment[$bank]) ? $source_payment[$bank] : '',
                        'created' => date('d/m/Y') 
                    ), 'POST', array(
                        'Content-Type' => 'application/json',
                        'apiKey'=> getenv_docker('NOTIF_DISCORD_KEY', '')
                    ));
                }

        // if($x){
        //     print_r($x);exit;
        // }

                $thankyou_page_link = houzez_get_template_link('template/template-thankyou.php');

                if (!empty($thankyou_page_link)) {
                    $separator = (parse_url($thankyou_page_link, PHP_URL_QUERY) == NULL) ? '?' : '&';
                    $parameter = 'directy_pay=' . $invoiceID;
                    print $thankyou_page_link . $separator . $parameter;
                }
                wp_die();
            }
        }



        /*
         * Send email
         * */
        houzez_email_type($user_email, 'new_wire_transfer', $args);
        houzez_email_type($admin_email, 'admin_new_wire_transfer', $args);

        // print_r(getenv_docker('NOTIF_URL_NEW_PAYMENT_DISCORD', ''));exit;

        // send to discord
        if(DISCORD_ENABLED == 1){
        $x = send_bg(getenv_docker('NOTIF_URL_NEW_PAYMENT_DISCORD', 'http://localhost:3001/payment/manual'), array(
            'user_email' => $user_email,
            'invoice_id' => $invoiceID,
            'item_id' => isset($package['ID']) ? $package['ID'] : '',
            'item' => $package,
            'user' => $current_user,
            'source' => isset($source_payment[$bank]) ? $source_payment[$bank] : '',
            'created' => date('d/m/Y') 
        ), 'POST', array(
            'Content-Type' => 'application/json',
            'apiKey'=> getenv_docker('NOTIF_DISCORD_KEY', '')
        ));
    }

        // print_r($x);exit;

        $thankyou_page_link = houzez_get_template_link('template/template-thankyou.php');

        if (!empty($thankyou_page_link)) {
            $separator = (parse_url($thankyou_page_link, PHP_URL_QUERY) == NULL) ? '?' : '&';
            $parameter = 'directy_pay=' . $invoiceID;
            print $thankyou_page_link . $separator . $parameter;
        }
        wp_die();
    }
}

function get_package($id)
{
    $u = get_post($id);
    $m = get_post_meta($id, 'fave_package_price', true);
    // $tax = get_post_meta( $id, 'fave_package_tax', true );

    $res = array(
        'ID' => $id,
        'package_name' => $u->post_title,
        'package_price' => intval($m),
        // 'package_tax' => $tax,
        // 'package_total' => (int)$m + (int)$tax,
        'package_category' => 'Membership Package'
    );

    return $res;
}
