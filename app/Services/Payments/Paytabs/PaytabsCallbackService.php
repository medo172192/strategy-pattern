<?php

namespace App\Services\Payments\Paytabs;

use App\Traits\LifeCycle;
use App\Interfaces\LifeCycle\LifeCycleInterface;
use Illuminate\Support\Facades\Http;
use App\Interfaces\LifeCycle\CallbackLifeCycleInterface as CallbackLifeCycle;

/**
 * @see Avalible Configrations "mode" , "server_key" , "profile_id"
 * -  mode        : you can select payment mode
 * -  server_key  : you can select payment server_key from your paytabs account 
 * -  profile_id  : you can select payment profile_id from your paytabs account 
 * - if you wanna set config you can use setConfig with Paytabs instance
 * ```
 *  ->setConfig( string key,  string value )
 * ``` 
*/

class PaytabsCallbackService implements LifeCycleInterface , CallbackLifeCycle
{
    use LifeCycle;

    private static $status;
    private static $callback_status;
    private static $response;    
    /**
     * @see prepare request data collection
     * @return self
    */
    public static function makeStatus():self
    {
        if(self::hasErrors()) return self::next();

        $payment_mode = self::hasConfig("mode") ? self::getConfig("mode") : config("paytabs.mode");
        $url = config("paytabs.".$payment_mode.".status_url");
        $headers = [
            "authorization" => self::hasConfig("server_key") ? self::getConfig("server_key") : config("paytabs.server_key"),
            "Content-Type"  => "application/json",
        ];
        $data = [
            'profile_id'    => self::hasConfig("profile_id") ? self::getConfig("profile_id") : "124003",
            'tran_ref'      => session()->get("paytabs_tran_ref")
        ];
    
        $connect = Http::withHeaders($headers)->post($url,$data);
        $response = $connect->object();
        self::$response = $response;

        return new self;
    }

    /**
     * @see execute payment callback
     * @return self
    */
    public static function callback() :self
    {
        if(self::hasErrors()) return self::next();
        try {
            $response = self::$response;
            // check status code
            if(
                $response->payment_result->response_message == 'Authorised' && 
                $response->payment_result->response_status == 'A'
            ){
                self::$callback_status = payment_success();
            }else{
                self::$callback_status = payment_failed();
            }
            self::$status = $response;

        } catch (\Exception $e) {
            self::$callback_status = payment_failed();
        }

        session()->forget("paytabs_tran_ref");
          
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
