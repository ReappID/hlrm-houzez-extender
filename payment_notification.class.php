<?php
require_once ABSPATH . 'vendor/autoload.php';

class Payment_Notification
{
    protected $notif;
    public function __construct()
    {
        
    }
    public function activate_package($order_id)
    {
        $invoiceMeta = get_post_meta($order_id);
        // print_r($invoiceMeta);exit;
        if(!isset($invoiceMeta['HOUZEZ_invoice_buyer'][0])){
            return false;
        }
        if(isset($invoiceMeta['HLRM_invoice_payment_gateway_uses'][0]) && $invoiceMeta['HLRM_invoice_payment_gateway_uses'][0] == 'midtrans'){
            return $this->processMidtransPay();
        }else if(isset($invoiceMeta['HLRM_invoice_payment_gateway_uses'][0]) && $invoiceMeta['HLRM_invoice_payment_gateway_uses'][0] == 'xendit'){
            return $this->processXenditPay();
        }
    }

    // private function processXenditPay(){}
    private function processMidtransPay()
    {
        $this->notif = new \Midtrans\Notification();
        $transaction = $this->notif->transaction_status;
        $fraud = $this->notif->fraud_status;

        if ($transaction == 'capture') {
            if ($fraud == 'challenge') {
              // TODO Set payment status in merchant's database to 'challenge'
            }
            else if ($fraud == 'accept') {
              // TODO Set payment status in merchant's database to 'success'
            }
        }
        else if ($transaction == 'cancel') {
            if ($fraud == 'challenge') {
              // TODO Set payment status in merchant's database to 'failure'
            }
            else if ($fraud == 'accept') {
              // TODO Set payment status in merchant's database to 'failure'
            }
        }else if($transaction == 'settlement'){
            update_post_meta($this->notif->order_id, 'invoice_payment_status', 1);
            update_post_meta($this->notif->order_id, 'HLRM_invoice_payment_gateway_status', $transaction);
        }

        // deny

        // -----
        return 'using midtrans';
    }

    private function processXenditPay()
    {
        return 'using midtrans';
    }
}