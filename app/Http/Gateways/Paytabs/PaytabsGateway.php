<?php

 namespace App\Http\Gateways\Paytabs;


 use App\Interfaces\Payments\PaymentProcessInterface;
 use Illuminate\Http\Request;
 use App\Services\Payments\Paytabs\PaytabsPayService as PaytabsPay;
 use App\Services\Payments\Paytabs\PaytabsCallbackService as PaytabsCallback;
 use App\Services\Payments\PaymentListener as Listener;

 class PaytabsGateway implements PaymentProcessInterface
 {
    
    public function __construct() {

      Listener::payment("paytabs");
      PaytabsPay::setConfig("mode", Listener::handler("mode"))
      ->setConfig("server_key", Listener::handler("api_key"))
      ->setConfig("profile_id",Listener::handler("secret_key"))
      ->setConfig("currency", Listener::handler("currency"));

    }

    /**
    *  - Paypal Paytabs process
    */
    public function pay( Request $request ){

      return PaytabsPay::request( $request )
      ->connect()
      ->validation()
      ->prepare()
      ->pay()
      ->response();

    }

    /**
     * - get callback of Paytabs process
     */
    public function callback(){
      
      return PaytabsCallback::makeStatus()
      ->callback()
      ->response();

    }


 }