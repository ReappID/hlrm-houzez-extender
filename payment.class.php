<?php
require_once ABSPATH . 'vendor/autoload.php';

class Payment
{
    protected $code, $xendit, $midtrans, $method, $key, $result, $payment_gateway, $xendit_method;
    public function __construct($cd = false, $method = false, $usingMidtransSnap = false, $usingXenditSnap = false)
    {
        
        $this->result = array(
            'payment_number' => '',
            'payment_amount' => 0,
            'payment_status' => 'pending',
            'payment_vendor' => '',
            'payment_bank_vendor' => '',
            'message' => ''
        );
        $this->xendit_method = [
            'payment_2' => ["BNI", "BSI", "BRI", "MANDIRI", "PERMATA"],
            'payment_4' => ["OVO","DANA"],
            'payment_3' => ["CREDIT_CARD"]
        ];
        $mtd = array(
            'payment_1' => 'gopay',
            'payment_2' => 'va_account',
            'payment_3' => 'card',
            'payment_4' => 'ewallet'
        );
        if(!$usingMidtransSnap && !$usingXenditSnap && !$cd){
            $this->result['message'] = 'Masukan kode bank!';
            return $this->result;
        }
        $this->key = array(
            'midtrans' => array(
                'server_key' => MIDTRANS_SERVER_KEY,
                'client_key' => MIDTRANS_CLIENT_KEY,
                'merchant_id' => MIDTRANS_MERCHANT_ID
            ),
            'xendit' => array(
                'api_key' => XENDIT_KEY
            )
        );
        $this->code = $cd;
        $this->method = $method == false ? 'bank_transfer' : $method;
        $this->payment_gateway = [
            'midtrans_snap' => $usingMidtransSnap,
            'xendit_snap' => $usingXenditSnap,
            'bca' => array(
                'bank_transfer' => array(
                    'code' => 'bca_va',
                    'using' => 'midtrans',
                    'display_bank_name' => 'BCA'
                )
            ),
            'bni' => array(
                'bank_transfer' => array(
                    'code' => 'bni_va',
                    'using' => 'midtrans',
                    'display_bank_name' => 'BNI'
                )
            ),
            'bri' => array(
                'bank_transfer' => array(
                    'code' => 'bri_va',
                    'using' => 'midtrans',
                    'display_bank_name' => 'BRI'
                )
            ),
            'mandiri' => array(
                'bank_transfer' => array(
                    'code' => 'MANDIRI',
                    'using' => 'xendit',
                    'display_bank_name' => 'MANDIRI'
                )
            ),
        ];
        if ($usingMidtransSnap) {
            $this->result['payment_vendor'] = 'midtrans';
        }else if($usingXenditSnap){
            $this->result['payment_vendor'] = 'xendit';
        }
        $this->result['payment_bank_vendor'] = ($usingXenditSnap || $usingMidtransSnap) && !$cd  ? 'snap' : $this->payment_gateway[$this->code][$this->method]['display_bank_name'];
    }

    public function doPayment($user, $amount, $desc, $order_id = null, $extra = false)
    {

        $params = array(
            'user_email' => $user->user_email,
            'user_name' => $user->display_name,
            'total_amount' => $amount,
            'desc' => $desc,
            'order_id' => $order_id,
            'extra' => $extra
        );
        if ($params['order_id'] == null) {
            $params['order_id'] = Ramsey\Uuid\Uuid::uuid4()->toString();
        }
        if (($this->code && $this->payment_gateway[$this->code][$this->method]['using'] == 'midtrans') || $this->payment_gateway['midtrans_snap']) {
            return $this->midtransPayment($params, $user);
        } else if (($this->code && $this->payment_gateway[$this->code][$this->method]['using'] == 'xendit') || $this->payment_gateway['xendit_snap']) {
            return $this->xenditPayment($params, $user);
        }

        return false;
    }

    private function midtransPayment($payload, $userdata = null)
    {
        \Midtrans\Config::$serverKey = $this->key['midtrans']['server_key'];
        \Midtrans\Config::$isProduction = getenv_docker('MIDTRANS_IS_PRODUCTION', 0) == 1 ? true : false;
        \Midtrans\Config::$appendNotifUrl = getenv_docker('NOTIF_URL_MIDTRANS_WEB', 'https://deimos.halorumah.id/wp-json/hlrm/v1/payment/midtrans');

        $mobile = '';
        if($userdata != null){
            $mobile = get_user_meta($userdata->ID, 'fave_author_mobile', true);
        }
        if($mobile != ''){
            $mobile = ltrim($mobile,'0');
            $mobile = '62'.$mobile;
        }
    
        $transaction_details = array(
            'order_id'    => $payload['order_id'],
            'gross_amount'  => $payload['total_amount']
        );
        $customer_details = array(
            'first_name'       => $payload['user_name'],
            'last_name'        => "-",
            'email'            => $payload['user_email'],
            'phone' => $mobile
        );
        $items = [];
        if (isset($payload['extra']) && isset($payload['extra']['package'])) {
            $package = $payload['extra']['package'];
            $items = [
                array(
                    'id' => $package['ID'],
                    'name' => $package['package_name'],
                    'price' => $package['package_price'],
                    'category' => isset($package['package_category']) ? $package['package_category'] : 'Membership Package',
                    'quantity' => isset($package['package_quantity']) ? $package['package_quantity'] : 1
                )
            ];
        }
        // jika menggunakan snap
        if($this->payment_gateway['midtrans_snap']){
            $trans_data = array(
                'transaction_details' => $transaction_details,
                'item_details'        => $items,
                'customer_details'    => $customer_details
            );

            $response = \Midtrans\Snap::getSnapToken($trans_data);
            $this->result['payment_number'] = $response;
            $this->result['payment_amount'] = $payload['total_amount'];
            $this->result['payment_status'] = 'pending';
            $this->result['payment_bank_vendor'] = 'snap';
            return $this->result;
        }
        // -----------------------
        $credit_card_opt = array();
        if ($this->method == 'credit_card') {
            $credit_card_opt =  array(
                'token_id'      => $payload['credit_card']['token_id'],
                'authentication' => true,
                'bank'          => $payload['credit_card']['bank'], // optional to set acquiring bank
                'save_token_id' => isset($payload['credit_card']['save_token_id']) ? $payload['credit_card']['save_token_id'] : false   // optional for one/two clicks feature
            );
        }
        $trans_data = array(
            'payment_type' => $this->method,
            'credit_card'  => $credit_card_opt,
            'transaction_details' => $transaction_details,
            'item_details'        => $items,
            'customer_details'    => $customer_details
        );
        if ($this->method == 'bank_transfer') {
            $trans_data['bank_transfer'] = array(
                'bank' => $this->payment_gateway[$this->code][$this->method]['code']
            );
        }
        //   print_r($trans_data);exit;
        $response = \Midtrans\CoreApi::charge($trans_data);
        if ($response->status_code == 201 && $response->transaction_status == 'pending' && $response->payment_type == 'bank_transfer') {
            $this->result['payment_number'] = $response->permata_va_number;
            $this->result['payment_amount'] = $response->gross_amount;
            $this->result['payment_status'] = $response->transaction_status;
        }
        return $this->result;
    }

    private function xenditPayment($payload, $userdata = null)
    {
        $payment_method = '';
        if(isset($payload['extra']) && isset($payload['extra']['payment_method'])){
            $payment_method = $payload['extra']['payment_method'];
        }
        // print_r($payment_method);exit;
        \Xendit\Xendit::setApiKey($this->key['xendit']['api_key']);
        $mobile = '';
        if($userdata != null){
            $mobile = get_user_meta($userdata->ID, 'fave_author_mobile', true);
        }
        if($mobile != ''){
            $mobile = ltrim($mobile,'0');
            $mobile = '62'.$mobile;
        }
        $items = [];
        if (isset($payload['extra']) && isset($payload['extra']['package'])) {
            $package = $payload['extra']['package'];
            $items = [
                array(
                    'name' => $package['package_name'],
                    'price' => $package['package_price'],
                    'category' => isset($package['package_category']) ? $package['package_category'] : 'Membership Package',
                    'quantity' => isset($package['package_quantity']) ? $package['package_quantity'] : 1
                )
            ];
        }
        $params = [ 
            'external_id' => $payload['order_id'],
            'amount' => $payload['total_amount'],
            'description' => $payload['desc'],
            'payer_email' => $userdata->user_email,
            'success_redirect_url' => getenv_docker('REDIRECT_SUCCESS_PAYMENT', 'https://halorumah.id'),
            'failure_redirect_url' => getenv_docker('REDIRECT_FAIL_PAYMENT', 'https://halorumah.id'),
            'currency' => 'IDR',
            'items' => $items
          ];
          if($payment_method != '' && $payment_method === 'payment_2' || $payment_method === 'payment_3' || $payment_method == 'payment_4'){
            $params['payment_methods'] = $this->xendit_method[$payment_method];
          }
        //   print_r($params);exit;
          try {
            $createInvoice = \Xendit\Invoice::create($params);
          
                 $this->result['payment_number'] = $createInvoice['invoice_url'];
                 $this->result['payment_amount'] = $createInvoice['amount'];
                 $this->result['payment_status'] = $createInvoice['status'];
                 $this->result['message'] = $createInvoice['description'];
            return $this->result;
          } catch (\Throwable $th) {
              print_r($th);wp_die();exit;
          }
    }
}
