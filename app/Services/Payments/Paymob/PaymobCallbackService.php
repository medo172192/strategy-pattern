<?php

namespace App\Services\Payments\Paymob;

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

class PaymobCallbackService implements LifeCycleInterface , CallbackLifeCycle
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
            self::$status = request()->all();
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
        if (isset($data['success']) && $data['success'] == 'true') {
            self::$callback_status = payment_success();
        }else{
            self::$callback_status = payment_failed();
        }
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
