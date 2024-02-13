<?php

namespace App\Http\Gateways\Paymob;

use App\Interfaces\Payments\PaymentProcessInterface;
use Illuminate\Http\Request;
use App\Services\Payments\Paymob\PaymobPayService as PaymobPay;
use App\Services\Payments\Paymob\PaymobCallbackService as PaymobCallback;
use App\Services\Payments\PaymentListener as Listener;


class PaymobGateway implements PaymentProcessInterface
{

    public function __construct() {

        Listener::payment("paymob");
        PaymobPay::setConfig("mode", Listener::handler("mode") == 'live' ? false : true)
        ->setConfig("api_key", Listener::handler("api_key"))
        ->setConfig("username",Listener::handler("secret_key"))
        ->setConfig("password", Listener::handler("public_key"))
        ->setConfig("iframe", Listener::handler("iframe"))
        ->setConfig("identifier", Listener::handler("identifier"))
        ->setConfig("integration", Listener::handler("integration"))
        ->setConfig("currency", Listener::handler("currency"));
  
    }

    /**
     * - Paymob payment process
    */
    public function pay( Request $request ){

        return PaymobPay::request( $request )
        ->validation()
        ->prepare()
        ->connection()
        ->pay()
        ->response();

    }

    /**
     * - get callback of paymob proecess
    */
    public function callback(){
        
        return PaymobCallback::makeStatus()
        ->callback()
        ->response();

    }
}