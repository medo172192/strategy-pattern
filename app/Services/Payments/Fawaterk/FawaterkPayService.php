<?php

namespace App\Services\Payments\Fawaterk;

use Illuminate\Http\Request;
use App\Traits\LifeCycle;
use App\Interfaces\LifeCycle\LifeCycleInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Devinweb\LaravelHyperpay\Traits\ManageUserTransactions;
use Devinweb\LaravelHyperpay\Facades\LaravelHyperpay;
use App\User;
/**
 * @see Avalible Configrations "mode" , "api_key" , "country_iso" , "currency"
 * -  mode        : you can select payment mode
 * -  api_key     : you can select payment api_key  
 * -  country_iso : you can select payment country_iso as a default country code
 * - if you wanna set config you can use setConfig with Paytabs instance
 * ```
 * ->setConfig( string key,  string value )
 * ``` 
 * ### Data
 * - string name 
 * - string email 
 * - string amount 
 * - int|string payment_method
 */
class FawaterkPayService implements LifeCycleInterface
{
    use LifeCycle;
    use ManageUserTransactions;

    private static $connect;
    private static array $collection = [];
    private static string $redirectUrl;
    private static bool $status;

    public static function connect() :self
    {
        $request = self::$request;
        try {
          
            $accessToken    = self::hasConfig("access_token") ? self::getConfig("access_token") : config("fawaterak.access_token");
            $url = 'https://staging.fawaterk.com/api/v2/invoiceInitPay';
            $data = self::getCollection();
            $headers = [
                "Authorization" => "Bearer $accessToken",
                "Content-type"  => "application/json"
            ];

            $response = Http::withHeaders($headers)->post( $url , $data )->collect()->toArray();
          
            self::$connect = $response;

        } catch (\Exception $e) {
            self::setError("the payment not works");

        }

        return new self;
    }

    /**
     * @see validation request data
     * @return self
    */
    public static function validation() :self
    {

        if(self::hasErrors()) return self::next();
        self::reset();
        // check required fields in the request
        $request = self::$request;
        
        return new self;
    }

    /**
     * @see prepare request data collection
     * @return self
    */
    public static function prepare():self
    {
        if(self::hasErrors()) return self::next();
        $request = self::$request;
        setPaymentRequest( $request->all() );
        return new self;
    }

    /**
     * @see execute payment process
     * @return self
    */
    public static function pay() :self
    {
        if(self::hasErrors()) return self::next();
        if(isset(self::$connect['status']) && self::$connect['status'] == 'success'){
            session()->put("payment_connect",json_encode(self::$connect));
            self::$redirectUrl  = self::$connect['data']['payment_data']['redirectTo'];
        }else{
            self::setError("the payment not works");
        }
     
        return new self;
    }

    /**
     * @see get payment response status
     * @return mixed
    */
    public static function response() 
    {
        if(self::hasErrors()) {
            self::reset();
            return  redirect()->back()->with("errors",self::getErrors());
        }
        //  return
        return redirect()->away(self::$redirectUrl);
        
    }

    /**
     * 
     * @param int|string $orderId
     * @return array
     */
    private static function getCollection() :array
    {
        $request = self::$request;
        $user    = auth()->user() ?? auth()->guard("site")->user();
        return [
            "payment_method_id" => $request->payment_method,
            "cartTotal"         => $request->amount,
            "currency"          => self::hasConfig("currency") ?  self::getConfig("currency") : config("fawaterak.currency"),
            "customer"          => [
              "first_name"  => $user->name,
              "last_name"   => $user->lastname,
              "email"       => $user->email,
              "phone"       => $user->phone,
              "address"     => $user->address,
            ],
            "redirectionUrls" => [
              "successUrl"  => route("fawaterk.callback","success") ,
              "failUrl"     => route("fawaterk.callback","fail") ,
              "pendingUrl"  => route("fawaterk.callback","pending"),
            ],
            "cartItems" => [[
                "name" => 'product',
                "price" =>  $request->amount,
                "quantity" => '1',
              ],
            ],
        ];
    }
   
}