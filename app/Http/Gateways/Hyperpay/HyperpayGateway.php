<?php

namespace App\Http\Gateways\Hyperpay;

use App\Interfaces\Payments\PaymentProcessInterface;
use Illuminate\Http\Request;
use App\Services\Payments\Hyperpay\HyperpayPayService as HyperpayPay;
use App\Services\Payments\Hyperpay\HyperpayCallbackService as HyperpayCallback;
use App\Services\Payments\PaymentListener as Listener;

/**
 * # myfatoorah payment gateway
 *  ```help
 *  -  Expected data
 *      name , email , amount , payment_method
 *  ```
*/

class HyperpayGateway implements PaymentProcessInterface
{

    
    public function __construct() {

        Listener::payment("hyperpay");
        HyperpayPay::setConfig("mode", Listener::handler("mode") == 'live'  ? false : true)  
        ->setConfig("access_token", Listener::handler("api_key"))  
        ->setConfig("entityId", Listener::handler("secret_key"))   
        ->setConfig("mada", Listener::handler("public_key"))  
        ->setConfig("apple_pay", Listener::handler("identifier"))  
        ->setConfig("currency", Listener::handler("currency"))
        ->setConfig("payment_methods", ['VISA' , 'MASTER']);  
        
    }
    

    /**
     * - myfatoorah payment process
     */
    public function pay( Request $request ){
       
        return HyperpayPay::request( $request )
        ->validation()
        ->connect()
        ->prepare()
        ->pay()
        ->response();
    }

    /**
     * - myfatoorah payment callback status
    */
    public function callback(){

        return HyperpayCallback::makeStatus()
        ->callback()
        ->response();
        
    }
 
}