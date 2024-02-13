<?php

namespace App\Services\Payments\Myfatoorah;

use Illuminate\Http\Request;
use App\Traits\LifeCycle;
use App\Interfaces\LifeCycle\LifeCycleInterface;
use MyFatoorah\Library\PaymentMyfatoorahApiV2;
use App\Interfaces\LifeCycle\CallbackLifeCycleInterface as CallbackLifeCycle;


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


class MyfatoorahCallbackService implements LifeCycleInterface , CallbackLifeCycle
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

            try {
                $connect = new PaymentMyfatoorahApiV2(
                    self::hasConfig("api_key")      ? self::getConfig("api_key")     : config('myfatoorah.api_key'), 
                    self::hasConfig("country_iso")  ? self::getConfig("country_iso") : config('myfatoorah.country_iso'), 
                    self::hasConfig("mode")         ? self::getConfig("mode")        : config('myfatoorah.test_mode')
                );
                if (!request()->paymentId) self::setError(__("the payment id not found plaese check from transaction"));
                
                $data = $connect->getPaymentStatus(request()->paymentId, 'PaymentId');
                self::$status = $data;

            } catch (\Exception $e) {
                self::setError($e->getMessage());
            }
          
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
        if ($data->InvoiceStatus == 'Paid') {
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
