<?php

namespace App\Services\Payments\Hyperpay;

use Illuminate\Http\Request;
use App\Traits\LifeCycle;
use App\Interfaces\LifeCycle\LifeCycleInterface;
use MyFatoorah\Library\PaymentMyfatoorahApiV2;
use App\Interfaces\LifeCycle\CallbackLifeCycleInterface as CallbackLifeCycle;
use Devinweb\LaravelHyperpay\Facades\LaravelHyperpay;

/**
 * @see Avalible Configrations "mode" , "api_key" , "country_iso"
 * -  mode        : you can select payment mode
 * -  api_key     : you can select payment api_key  
 * -  country_iso : you can select payment country_iso as a default country code
 * - if you wanna set config you can use setConfig with Paytabs instance
 * ```
 * ->setConfig( string key,  string value )
 * ``` 
*/


class HyperpayCallbackService implements LifeCycleInterface , CallbackLifeCycle
{
    use LifeCycle;

    private static $status;
    private static $callback_status;

    /**
     * @see prepare request data collection
     * @return self
    */
    public static function makeStatus():self
    {
        if(self::hasErrors()) return self::next();
        $request = request()->all();
        $resourcePath = $request['resourcePath'];
        $checkout_id = $request['id'];
        $data  = LaravelHyperpay::paymentStatus($resourcePath, $checkout_id)->getData();
       
        self::$status = $data;
          
        return new self;
    }

    /**
     * @see execute payment callback
     * @return self
    */
    public static function callback() :self
    {
        if(self::hasErrors()) return self::next();

        $data = self::$status;
        if ( $data->status == 200 ) {
            self::$callback_status = payment_success();
        }else{
            self::$callback_status = payment_failed();
        }
        session()->forget("myfatoorah_invoiceId");

        return new self;
    }
    
    /**
     * @see get payment response status
     * @return mixed
    */
    public static function response() 
    {
        return redirect()->route(config("app.services.route") , [
            'status'    => self::$callback_status,
            'request'   => getPaymentRequest(),
            'errors'    => self::getErrors(),
            'details'   => self::$status
        ]);
    }
 

}
