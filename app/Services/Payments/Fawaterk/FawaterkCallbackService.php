<?php

namespace App\Services\Payments\Fawaterk;

use App\Traits\LifeCycle;
use App\Interfaces\LifeCycle\LifeCycleInterface;
use App\Interfaces\LifeCycle\CallbackLifeCycleInterface as CallbackLifeCycle;
use Illuminate\Support\Facades\Http;

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


class FawaterkCallbackService implements LifeCycleInterface , CallbackLifeCycle
{
    use LifeCycle;

    private static $status;
    private static $callback_status;

    /**
     * @see prepare request data collection
     * @return self
    */
    public static function makeStatus() : self
    {

        if(self::hasErrors()) return self::next();
        $request = request()->all();

        $accessToken    = self::hasConfig("access_token") ? self::getConfig("access_token") : config("fawaterak.access_token");
        $invoice_id      = $request["invoice_id"];
        $url            = "https://staging.fawaterk.com/api/v2/getInvoiceData/$invoice_id";
        $headers        = [
            "Authorization" => "Bearer $accessToken",
            "Content-type"  => "application/json"
        ];

        $response = Http::withHeaders($headers)->get($url)->collect()->toArray();
        self::$status = $response;

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

        if (strtolower($data['data']['status_text']) == 'paid') {
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
