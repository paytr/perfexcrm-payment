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
            ),
            array(
                'name' => 'paytr_default_mail',
                // 'encrypted' => true,
                'label' => 'Default Mail For No Contact Companies',
                'type'=>'input',
            ),
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
        $paytr_helper->setLang( isset($billing_info['language']) && $billing_info['language'] == 'turkish' ? 'tr' : 'english');
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

        /**
         * Varsayılan olarak isim kısmına Şirket Adı
         * Soyadına da Şirket Yetkili ekliyoruz
         * Eposta adresi alanı da ödeme ayarlarında 
         * tanımlanan eposta adresi giriliyor.
         * 
         * Bu ayarlar eğer daha alakalı bir veri bulunursa
         * onunla değiştirilecek. 
         * 
         * Sadece Aktif bir Şirket Kişisi yoksa bu detaylar kullanılacak
         * Çünkü eposta ve isim alanları zorunlu ve eğer kişi oluşturmadan hızlıca
         * Şirket + Fatura oluşturup bağlantısını manuel olarak gönderirseniz.
         * Ödeme alanında müşteriniz sorun yaşayacaktır.
         * 
         * Bu varsayılan ayarlar ile bunun önüne geçiyoruz.
         * 
         * E. Altay KOLUAÇIK - https://evrimaltay.net/perfexcrm-paytr-eklenti-duzeltmeleri/
         * 16/06/2023
         */
        $firstname = $invoice->client->company;
        $lastname = 'Şirket Yetkilisi';
        $email_address = $this->getSetting('paytr_default_mail');

        if(is_client_logged_in()){
            /**
             * Öncelikli olarak giriş yapmış müşteriyi deniyoruz, giriş yapmış ise onun verilerini kullanıyoruz
             * 
             * E. Altay KOLUAÇIK - https://evrimaltay.net/perfexcrm-paytr-eklenti-duzeltmeleri/
             * 16/06/2023
             */
            $firstname = $GLOBALS['contact']->firstname;
            $lastname = $GLOBALS['contact']->lastname;
            $email_address = $GLOBALS['contact']->email;
        }else if(get_staff_user_id() !== false){
            /**
             * Müşteri girişi yok ise giriş yapmış personel var ise onu deniyoruz. Bu sayede yönetim paneline giriş yapmış bir şekilde
             * Ödemeyi test etmek istediğinizde hata almayacaksınız, fakat ödeme yaparsanız PayTR'a sizin bilgileriniz iletilmiş olacak.
             * Daha önceden de bu şekildeydi zaten.
             * 
             * E. Altay KOLUAÇIK - https://evrimaltay.net/perfexcrm-paytr-eklenti-duzeltmeleri/
             * 16/06/2023
             */
            $staff = ($this->ci->load->model('staff_model'))->staff_model->get(get_staff_user_id());
            $firstname = $staff->firstname;
            $lastname = $staff->lastname;
            $email_address = $staff->email;
        }else{
            /**
             * Burada ise işleri biraz değiştiriyoruz.
             * Eğer hiç giriş yapan kullanıcı yoksa ki genelde PerfexCRM bu şekilde kullanılıyor.
             * Sistem o şirkete ait kişileri çekiyor ve ilk kişi adına ödeme alındığını farz ederek PayTR'a bilgileri iletiyor.
             * Ki varsayılan olarak böyle yapılması daha uygun, sonuç olarak fatura bağlantısı özel ve sadece müşteriye iletiliyor.
             * Eğer güvenlik riski taşıdığı düşünülüyorsa rahatlıkla yönetim panelinden giriş yapmamış kişilerin fatura görüntülenmesi kapatılabiliyor.
             * 
             * E. Altay KOLUAÇIK - https://evrimaltay.net/perfexcrm-paytr-eklenti-duzeltmeleri/
             * 16/06/2023
             */
            $contacts = ($this->ci->load->model('clients_model'))->clients_model->get_contacts($invoice->clientid);
        
            if(count($contacts) > 0){
                $contact = $contacts[0];
                $firstname = $contact['firstname'];
                $lastname = $contact['lastname'];
                $email_address = $contact['email'];
            }
        }

        /**
         * Eğer müşteri Türkçe'den farklı bir dil kullanıyorsa 
         * O dili de iletelim.
         * 
         * E. Altay KOLUAÇIK - https://evrimaltay.net/perfexcrm-paytr-eklenti-duzeltmeleri/
         * 16/06/2023
         */
        if($invoice->client->default_language != 'turkish'){
            $payer['language'] = $invoice->client->default_language;
        }
        
        $name = [
            'given_name' => $firstname,
            'surname'    => $lastname,
        ];
        if (!empty($name['given_name'])) {
            $payer['name'] = $name;
        }
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
