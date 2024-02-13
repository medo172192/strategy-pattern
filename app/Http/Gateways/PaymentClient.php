<?php

namespace App\Http\Gateways;

use Illuminate\Http\Request;
use App\Interfaces\Payments\PaymentProcessInterface as PaymentProcess;

/**
 * # payment client 
 * - use strategy design pattern 
 *  ```
 *  this payment client works as provider to payments 
 *  it's when create new instance of PaymentClient get one param
 *  for make implementation for target payment and run pay function 
 * ```
 * @see doPayment()
 */
class PaymentClient
{
    
    private $gateway;

    public function __construct(PaymentProcess $gateway) {
        $this->gateway = $gateway;
    }

    /**
     *  - [1] take current payment object instance
     *  - [2] launches the payment function
    */
    public function doPayment( Request $request ) 
    {
        if(auth()->check() || auth()->guard("site")->check()){
            return $this->gateway->pay( $request );
        }
        return redirect("/");
    }

}