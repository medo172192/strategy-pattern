<?php

 namespace App\Http\Gateways\Paypal;


 use App\Interfaces\Payments\PaymentProcessInterface;
 use Illuminate\Http\Request;
 use App\Services\Payments\Paypal\PaypalPayService as PaypalPay;
 use App\Services\Payments\Paypal\PaypalCallbackService as PaypalCallback;
 use App\Services\Payments\PaymentListener as Listener;


 class PaypalGateway implements PaymentProcessInterface
 {

    public function __construct() {

        Listener::payment("paypal");
        PaypalPay::setConfig("mode",Listener::handler("mode"))
        ->setConfig("client_id",Listener::handler("api_key"))
        ->setConfig("secret_key",Listener::handler("secret_key"))
        ->setConfig("currency",Listener::handler("currency"));

    }
    
   /**
    *  - Paypal payment process
    */
    public function pay( Request $request ){

      return PaypalPay::request( $request )
      ->connect()
      ->validation()
      ->prepare()
      ->pay()
      ->response();

    }

    /**
     * - get callback of paypal process
     */
    public function callback(){

      return PaypalCallback::makeStatus()
      ->callback()
      ->response();
        
    }

 }