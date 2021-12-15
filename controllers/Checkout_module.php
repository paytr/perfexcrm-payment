<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Class Checkout_module
 */
class Checkout_module extends ClientsController
{

    /**
     * @var string
     */
    public function index(){
        $data['token']  =   $this->input->get('token');
        $this->load->view('paytr_iframe', $data);
    }

    public function success(){
        $invoiceid = $this->input->get('invoice_id');
        $hash      = $this->input->get('hash');
        if(is_admin()){
            redirect('admin/invoices/list_invoices/'.$invoiceid);
        }
        check_invoice_restrictions($invoiceid, $hash);
        $this->db->where('id', $invoiceid);
        $invoice = $this->db->get(db_prefix() . 'invoices')->row();
        if ($invoice) {
            set_alert('success', _l('online_payment_recorded_success'));
        } else {
            set_alert('danger', '');
        }
        redirect(site_url('invoice/' . $invoice->id . '/' . $invoice->hash));
    }

    public function failed(){
        log_activity(_l('payment_getaway_token_not_found'));
        set_alert('danger', _l('payment_getaway_token_not_found'));
        redirect(site_url('invoice/' . $this->input->get('invoice_id') . '/' . $this->input->get('hash')));
    }

    public function notify(){
        if($this->input->post('status') && $this->input->post('status') === 'success')
        {
            $hash = base64_encode( hash_hmac('sha256', $this->input->post('merchant_oid').$this->paytr_gateway->decryptSetting('paytr_merchant_salt').$this->input->post('status').$this->input->post('total_amount'), $this->paytr_gateway->decryptSetting('paytr_merchant_key'), true) );
            if($hash != $this->input->post('hash')){
                print_r('PAYTR notification failed: bad hash');
                log_activity('PAYTR notification failed: bad hash: ' . var_export($_POST, true));
                return false;
            }else if($hash === $this->input->post('hash') && $this->input->post('status') === 'success'){
                //Normalize Invoice ID
                $invoiceid = $this->input->post('merchant_oid');
                $invoiceid = explode('PCRM', $invoiceid);
                $invoiceid = explode('SP', $invoiceid[0])[1];
                if(!is_numeric($invoiceid)){
                    print_r('PAYTR invoice id not found');
                    log_activity('PAYTR invoice id not found: ' . var_export($_POST, true));
                    return false;
                }
                // Load Invoice
                $this->load->model('invoices_model');
                $invoice = $this->invoices_model->get($invoiceid);
                if($invoice){
                    // Add Payment
                    $this->paytr_gateway->addPayment(array(
                        'amount'         =>  $this->input->post('total_amount') / 100,
                        'invoiceid'      =>  $invoiceid,
                        'transactionid'  =>  $this->input->post('merchant_oid'),
                        'paymentmethod'  =>  'paytr_gateway',
                        'note'           =>  'Ödeme işlemi başarılı. PayTR müşteri panelinizden '. $this->input->post('merchant_oid') . ' sipariş numarasıyla sorgulama yapabilirsiniz.',
                    ));
                }else{
                    log_activity('PAYTR notification failed: invoice error: ' . var_export($_POST, true));
                }
            }
        }
        echo 'OK';
        return true;
    }

}