<?php

namespace App\Http\Gateways\Myfatoorah;

use App\Interfaces\Payments\PaymentProcessInterface;
use Illuminate\Http\Request;
use App\Services\Payments\Myfatoorah\MyfatoorahPayService as MyfatoorahPay;
use App\Services\Payments\Myfatoorah\MyfatoorahCallbackService as MyfatoorahCallback;
use App\Services\Payments\PaymentListener as Listener;

/**
 * # myfatoorah payment gateway
 *  ```help
 *  -  Expected data
 *      name , email , amount , payment_method
 *  ```
*/

class MyfatoorahGateway implements PaymentProcessInterface
{

    public function __construct() {

        Listener::payment("myfatoorah");
        MyfatoorahPay::setConfig("mode", Listener::handler("mode") == 'live'  ? false : true)
        ->setConfig("api_key", Listener::handler("api_key"))
        ->setConfig("currency", Listener::handler("currency"))
        ->setConfig("country_iso", "KWT");

    }

    /**
     * - myfatoorah payment process
     */
    public function pay( Request $request ){
       
        return MyfatoorahPay::request( $request )
        ->connect()
        ->validation()
        ->prepare()
        ->pay()
        ->response();

    }

    /**
     * - myfatoorah payment callback status
    */
    public function callback(){

        return MyfatoorahCallback::makeStatus()
        ->callback()
        ->response();
        
    }
 
}