<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Paytr Helper
 */
class PaytrHelper
{

    private $merchant_id;
    private $merchant_key;
    private $merchant_salt;
    private $merchant_oid;
    private $email;
    private $payment_amount;
    private $no_installment;
    private $user_basket;
    private $max_installment;
    private $user_name;
    private $merchant_ok_url;
    private $merchant_fail_url;
    private $currency;
    private $test_mode;
    private $lang;
    private $address;
    private $phone_number;

    /**
     * @param $value
     * @return $this
     */
    public function setMerchantId($value): PaytrHelper
    {
        $this->merchant_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMerchantKey($value): PaytrHelper
    {
        $this->merchant_key = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMerchantSalt($value): PaytrHelper
    {
        $this->merchant_salt = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserIp() {
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        return $ip;
    }


    /**
     * @param $value
     * @return $this
     */
    public function setMerchantOid($value): PaytrHelper
    {
        $this->merchant_oid = 'SP'.$value.'PCRM'.time();
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEmail($value): PaytrHelper
    {
        $this->email = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPaymentAmount($value): PaytrHelper
    {
        $this->payment_amount = $value * 100;
        return $this;
    }

    public function setUserBasket($items): PaytrHelper
    {
        $user_basket = [];
        foreach ($items as $item) {
            $user_basket[] = [
                $item['description'],
                $item['rate'],
                $item['qty']
            ];
        }
        $this->user_basket = base64_encode(json_encode($user_basket));
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInstallment($value): PaytrHelper
    {
        if($value === '1'){
            $this->no_installment   = 1;
            $this->max_installment  = 1;
        }else{
            $this->no_installment   = 0;
            $this->max_installment  = $value;
        }
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserName($value): PaytrHelper
    {
        $this->user_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMerchantOkUrl($value): PaytrHelper
    {
        $this->merchant_ok_url = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMerchantFailUrl($value): PaytrHelper
    {
        $this->merchant_fail_url = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCurrency($value): PaytrHelper
    {
        $this->currency = ($value === 'TRY' ? 'TL' : $value);
        return $this;
    }

    public function setAddress($value): PaytrHelper
    {
        $this->address = $value;
        return $this;
    }

    public function setPhoneNumber($value): PaytrHelper
    {
        $this->phone_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTestMode($value): PaytrHelper
    {
        $this->test_mode = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLang($value): PaytrHelper
    {
        $this->lang = $value === 'english' ? 'en' : 'tr';
        return $this;
    }

    /**
     * @return array
     */
    public function setup(): array
    {
        return [
            $this->merchant_id,
            $this->merchant_key,
            $this->merchant_salt,
        ];
    }

    /**
     * @return string
     */
    public function getPaytrToken(): string
    {
        $hash_str = $this->merchant_id .$this->getUserIp() .$this->merchant_oid .$this->email .$this->payment_amount .$this->user_basket.$this->no_installment.$this->max_installment.$this->currency.$this->test_mode;
        return base64_encode(hash_hmac('sha256',$hash_str.$this->merchant_salt,$this->merchant_key,true));
    }

    public function makePostVariables()
    {
        return [
            'merchant_id'       =>  $this->merchant_id,
            'user_ip'           =>  $this->getUserIp(),
            'merchant_oid'      =>  $this->merchant_oid,
            'email'             =>  $this->email,
            'payment_amount'    =>  $this->payment_amount,
            'paytr_token'       =>  $this->getPaytrToken(),
            'user_basket'       =>  $this->user_basket,
            'user_address'      =>  $this->address,
            'user_phone'        =>  $this->phone_number,
            'debug_on'          =>  1,
            'no_installment'    =>  $this->no_installment,
            'max_installment'   =>  $this->max_installment,
            'user_name'         =>  $this->user_name,
            'merchant_ok_url'   =>  $this->merchant_ok_url,
            'merchant_fail_url' =>  $this->merchant_fail_url,
            'timeout_limit'     =>  30,
            'currency'          =>  $this->currency,
            'test_mode'         =>  $this->test_mode,
            'lang'              =>  $this->lang,
        ];
    }

    /**
     * @return mixed|void
     */
    public function progress(){
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1) ;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->makePostVariables());
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $result = @curl_exec($ch);
        if(curl_errno($ch))
            die("PAYTR IFRAME connection error. err:".curl_error($ch));
        curl_close($ch);
        $result=json_decode($result,1);
        if($result['status']=='success')
            return $result['token'];
        else
            die("PAYTR IFRAME failed. reason:".$result['reason']);
    }

}
