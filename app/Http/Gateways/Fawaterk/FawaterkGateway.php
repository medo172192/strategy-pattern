<?php

namespace App\Http\Gateways\Fawaterk;

use App\Interfaces\Payments\PaymentProcessInterface;
use Illuminate\Http\Request;
use App\Services\Payments\Fawaterk\FawaterkPayService as FawaterkPay;
use App\Services\Payments\Fawaterk\FawaterkCallbackService as FawaterkCallback;
use App\Services\Payments\PaymentListener as Listener;
use Illuminate\Support\Facades\Input;
/**
 * # myfatoorah payment gateway
 *  ```help
 *  -  Expected data
 *      name , email , amount , payment_method
 *  ```
*/

class FawaterkGateway implements PaymentProcessInterface
{

    
    public function __construct() {

        Listener::payment("fawaterk");
        FawaterkPay::setConfig("mode", Listener::handler("mode") == 'live'  ? false : true)  
        ->setConfig("access_token", Listener::handler("api_key"))  
        ->setConfig("currency", Listener::handler("currency"));
        
    }

    /**
     * - myfatoorah payment process
     */
    public function pay( Request $request ){
       
        return FawaterkPay::request( $request )
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

        return FawaterkCallback::makeStatus()
        ->callback()
        ->response();
        
    }
 
}