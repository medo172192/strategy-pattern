<?php

namespace App\Services\Payments\Paymob;

use Illuminate\Support\Facades\Http;

/**
 * # Paymob Payment
 * - this's Payment connection service
*/

class PaymobConnectionService 
{

    private static $token , $username ,$password ;
    private static $auth_token;
    private static $soft_token , $auth_id;
    private static $instance  ;
    private static $latesToken  ;
    private static $currency , $amount  ;

    private static $errorList = [];

    // 1. Authentication Request=>
    // The Authentication request is an elementary step you should do before dealing with any of Accept's APIs.
    // It is a post request with a JSON object which contains your api_key found in your dashboard - Profile tab.
    public static function Authentication( string $token ,  string $username ,  string $password) : self
    {
        try {

            self::$token    = $token;
            self::$username = $username;
            self::$password = $password;

            $url  ="https://accept.paymob.com/api/auth/tokens";
            $data = [
                "api_key"     =>    $token,
                "username"    =>    $username,
                "password"    =>    $password,
            ];
            $headers= [
                "Content-Type"=>"application/json",
            ];

            $connect = Http::withHeaders($headers)->post($url,$data);
            if(!isset($connect->object()->token)){
                array_push(self::$errorList,$connect->object());
                return new self;
            }
            self::$auth_token = $connect->object()->token;

        } catch (\Exception $e) {
            array_push(self::$errorList,$e->getMessage());
        }
        return new self;
    }

    // 2=>Order Registration API
    // At this step, you will register an order to Accept's database, so that you can pay for it later using a transaction.
    // Order ID will be the identifier that you will use to link the transaction(s) performed to your system, as one order can have more than one transaction.
    public static function OrderRegistrationAPI( string $amount , string $currency ) : self
    {
        if(count(self::$errorList)>0) return new self;
      
        try {

            self::$currency = $currency;
            self::$amount = $amount;
    
            $url  ="https://accept.paymob.com/api/ecommerce/orders";
            $data = [
                "auth_token"         => self::$auth_token,
                "delivery_needed"    => "false",
                "amount_cents"       => $amount,
                "currency"           => $currency,
                "items"              => []
            ];
            $headers= [
                "Content-Type"=>"application/json",
            ];
    
            $connect = Http::withHeaders($headers)->post($url,$data);
            if(!isset($connect->object()->token)){
                array_push(self::$errorList,$connect->object());
                return new self;
            }
            self::$soft_token = $connect->object()->token;
            self::$auth_id    = $connect->object()->id;

        } catch (\Exception $e) {
            array_push(self::$errorList,$e->getMessage());
        }

        return new self;
    }

    // 3. Payment Key Request
    // At this step, you will obtain a payment_key token. This key will be used to authenticate your payment request. It will be also used for verifying your transaction request metadata.
    public static function PaymentKeyRequest( array $billing_data , int $integration_id ) : self
    {
        if(count(self::$errorList)>0) return new self;

        try {

            $url  ="https://accept.paymob.com/api/acceptance/payment_keys";
            $data = [
                "auth_token"=> self::$auth_token,
                "amount_cents"=> self::$amount,
                "expiration"=> 3600,
                "order_id"=> self::$auth_id,
                "billing_data"=> $billing_data ,
                "currency"=> self::$currency,
                "integration_id"=> $integration_id,
                "lock_order_when_paid"=> "false"
            ];
            $headers= [
                "Content-Type"=>"application/json",
            ];

            $connect = Http::withHeaders($headers)->post($url,$data);
            if(!isset($connect->object()->token)){
                array_push(self::$errorList,$connect->object());
                return new self;
            }
            self::$latesToken = $connect->object()->token;

        } catch(\Exception $e) {
            array_push(self::$errorList,$e->getMessage());
        }
       return new self;
    }

    public static function getToken() 
    {
        if(count(self::$errorList)>0) {
            $errors = json_decode(json_encode(self::$errorList),true);
            $errors['status'] = 'error';
            return $errors;
        }   
        return [
            "status"=> "success",
            "token" => self::$latesToken,
        ];
    }

    public static function doPayment( string $payment_method ,  array $source , string $payment_token , $iframe=null )
    {

        if ( in_array(strtoupper($payment_method),self::payMethods()) ) {
            return self::payServices( $source , $payment_token );
        }

        $url    = "https://accept.paymobsolutions.com/api/acceptance/iframes/$iframe?payment_token=$payment_token";
        $response = [
            "redirect_url" =>$url 
        ];

        return (object) $response;

    }

    public static function payServices( array $source , string $payment_token )
    {

        $url    = "https://accept.paymob.com/api/acceptance/payments/pay";
        $data   = [
            "source"        =>  $source,
            "payment_token" =>  $payment_token
        ];
        $headers= [
            "Content-Type"=>"application/json"
        ];
        // Step 1 : Login to Paymob
        $connect    = Http::withHeaders($headers)->post($url,$data);

        $response   = $connect->object();
        return $response;

    }


    public static function payMethods(){

        return [
            "WALLET",
            // "AGGREGATOR",
            // "CASH",
        ];

    }

}
