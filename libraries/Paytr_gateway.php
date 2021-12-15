<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once(__DIR__ . '/PaytrHelper.php');

/**
 * Class Paytr_gateway
 */
class Paytr_gateway extends App_gateway
{

    /**
     * Paytr_gateway constructor.
     */
    public function __construct()
    {

        parent::__construct();

        /**
         * REQUIRED
         */
        $this->setId('paytr');

        /**
         * Gateway name - REQUIRED
         */
        $this->setName('PayTR Virtual Pos iFrame API');

        /**
         * Add gateway settings
         */
        $this->setSettings(array(
            array(
                'name' => 'paytr_merchant_id',
                'encrypted' => true,
                'label' => 'Merchant ID',
                'type'=>'input',
            ),
            array(
                'name' => 'paytr_merchant_key',
                'encrypted' => true,
                'label' => 'Merchant Key',
                'type'=>'input',
            ),
            array(
                'name' => 'paytr_merchant_salt',
                'encrypted' => true,
                'label' => 'Merchant Salt',
                'type'=>'input',
            ),
            array(
                'name'          => 'paytr_test_mode',
                'type'          => 'yes_no',
                'default_value' => 1,
                'label'         => 'settings_paymentmethod_testing_mode',
            ),
            array(
                'name'          => 'currencies',
                'label'         => 'settings_paymentmethod_currencies',
                'default_value' => 'USD,TRY,EUR,RUB',
            )
        ));

    }

    /**
     * @param $data
     */
    public function process_payment($data)
    {
        $this->ci->load->model('invoice_items_model');
        $this->ci->load->model('paytr_gateway/installment_model');
        $paytr_helper = new PaytrHelper();
        $billing_info = $this->get_billing_info($data['invoice']);
        $paytr_helper->setEmail($billing_info['email_address']);
        $paytr_helper->setUserName($billing_info['name']['given_name'] . ' ' . $billing_info['name']['surname']);
        $paytr_helper->setAddress($billing_info['address']['address_line_1']);
        $paytr_helper->setMerchantId($this->decryptSetting('paytr_merchant_id'));
        $paytr_helper->setMerchantKey($this->decryptSetting('paytr_merchant_key'));
        $paytr_helper->setMerchantSalt($this->decryptSetting('paytr_merchant_salt'));
        $paytr_helper->setMerchantOid($data['invoiceid']);
        $paytr_helper->setPaymentAmount($data['amount']);
        $paytr_helper->setUserBasket($data['invoice']->items);
        $paytr_helper->setInstallment($this->calculateInstallment($data['invoice']->items, $this->ci->invoice_items_model->get_grouped()));
        $paytr_helper->setCurrency($data['invoice']->currency_name);
        $paytr_helper->setTestMode($this->getSetting('paytr_test_mode'));
        $paytr_helper->setLang( "tr");
        $paytr_helper->setPhoneNumber($data['invoice']->client->phonenumber);
        $paytr_helper->setMerchantOkUrl( site_url('paytr_gateway/checkout_module/success?hash='.$data['hash'].'&invoice_id='.$data['invoiceid']));
        $paytr_helper->setMerchantFailUrl( site_url('paytr_gateway/checkout_module/failed?hash='.$data['hash'].'&invoice_id='.$data['invoiceid']));
        redirect($this->endpoint($paytr_helper->progress(), $data['hash'], $data['invoiceid']));
    }

    /**
     * @param $paytr_token
     * @param $hash
     * @param $invoice_id
     * @return string
     */
    private function endpoint($paytr_token, $hash, $invoice_id): string
    {
        return site_url('paytr_gateway/checkout_module?token=' . $paytr_token . '&hash=' . $hash . '&invoice_id=' . $invoice_id);
    }

    /**
     * @param $invoice
     * @return mixed
     */
    private function get_billing_info($invoice)
    {
        $staff = ($this->ci->load->model('staff_model'))->staff_model->get(get_staff_user_id());

        $country = null;
        if ($invoice->billing_country) {
            $country = get_country($invoice->billing_country);
        }
        $admin_area_1 = null;
        if ($country) {
            if ($country->iso2 == 'UK') {
                $admin_area_1 = $invoice->billing_city;
            } elseif ($country->iso2 == 'US') {
                $admin_area_1 = $invoice->billing_state;
            }
        }
        $payer = [];
        $billing_address = [];
        if (!empty($invoice->billing_street)) {
            $billing_address['address_line_1'] = clear_textarea_breaks($invoice->billing_street); // street address
        }
        if (!empty($admin_area_1)) {
            $billing_address['admin_area_1'] = $admin_area_1;
        }
        if (!empty($invoice->billing_city)) {
            $billing_address['admin_area_2'] = $invoice->billing_city; // city
        }
        if (!empty($invoice->billing_zip)) {
            $billing_address['postal_code'] = $invoice->billing_zip; // postal code
        }
        if ($country) {
            $billing_address['country_code'] = $country->iso2; // country code
        }
        $name = [
            'given_name' => (is_client_logged_in() ? $GLOBALS['contact']->firstname : $staff->firstname),
            'surname'    => (is_client_logged_in() ? $GLOBALS['contact']->lastname : $staff->lastname),
        ];
        if (!empty($name['given_name'])) {
            $payer['name'] = $name;
        }
        $email_address = (is_client_logged_in() ? $GLOBALS['contact']->email  : $staff->email);
        if ($email_address) {
            $payer['email_address'] = $email_address;
        }
        if (count($billing_address) > 0) {
            $payer['address'] = $billing_address;
        }
        return hooks()->apply_filters('paytr_checkout_payer_data', $payer, $invoice);
    }

    private function calculateInstallment($items, $groups)
    {
        $this->ci->load->model('paytr_gateway/installment_model');
        $installment = [];
        foreach ($items as $item)
        {
            foreach ($groups as $group)
            {
                if(
                    $group[0]['description'] === $item['description'] &&
                    $group[0]['long_description'] === $item['long_description'] &&
                    $group[0]['rate'] === $item['rate']
                )
                {
                    $installment[] = $this->ci->installment_model->get($group[0]['group_id'])->installment_count;
                }
            }
        }
        return $this->getCurrentInstallment($installment);
    }

    private function getCurrentInstallment($installments): array
    {
        if (in_array('1', $installments)) {
            return [
                'no_installment'    => 1,
                'max_installment'   => 0,
            ];
        } elseif (($key = array_search('0', $installments)) !== false && count($installments) > 1) {
            unset($installments[$key]);
            return [
                'no_installment'    => 0,
                'max_installment'   => min($installments),
            ];
        }
        return [
            'no_installment'    => 0,
            'max_installment'   => count($installments) ? min($installments) : 0,
        ];
    }

}