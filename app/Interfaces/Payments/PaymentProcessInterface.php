<?php

namespace App\Interfaces\Payments;
use Illuminate\Http\Request;
/**
 * Interface PaymentProcessInterface
 * This interface defines the contract for Payments system.
*/

interface PaymentProcessInterface
{

    // when you wanna execute payment process
    public function pay( Request $request );
    // after execute  payment process get callback
    public function callback();

}
