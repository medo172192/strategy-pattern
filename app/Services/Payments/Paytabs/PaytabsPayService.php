<?php

namespace App\Services\Payments\Paytabs;

use App\Traits\LifeCycle;
use App\Interfaces\LifeCycle\LifeCycleInterface;
use MyFatoorah\Library\PaymentMyfatoorahApiV2;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

/**
 * @see Avalible Configrations "mode" , "server_key" , "profile_id"
 * -  mode        : you can select payment mode
 * -  server_key  : you can select payment server_key from your paytabs account 
 * -  profile_id  : you can select payment profile_id from your paytabs account 
 * - if you wanna set config you can use setConfig with Paytabs instance
 * ```
 * ->setConfig( string key,  string value )
 * ``` 
 * ### Data
 * - int|string amount 
 * - string name 
 * - string email 
 */

class PaytabsPayService implements LifeCycleInterface
{
    use LifeCycle;

    private static string $redirectUrl;
    private static $response;
    
    public  static function connect() :self
    {

        if(self::hasErrors()) return self::next();

        try {

            $payment_mode = self::hasConfig("mode") ? self::getConfig("mode") : config("paytabs.mode");
            $url = config("paytabs.".$payment_mode.".domain_url");
            $headers = [
                "authorization" => self::hasConfig("server_key") ? self::getConfig("server_key") : config("paytabs.server_key"),
                "Content-Type"  => "application/json",
            ];
            $connect = Http::withHeaders($headers)->post($url,self::getCollection());
            $response = $connect->object();
            if(!isset($response->redirect_url)){
                self::setError($response->message);
            }
            self::$response = $response;

        } catch (\Exception $e) {
            self::setError($e->getMessage());
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
        $request->validate([
            "amount"=> ["required"],
            "name"  => ["required"],
            "email" => ["required"],
        ]);
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
        // set payment request to global system 
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

        try {
            $response = self::$response;
            if(isset($response->tran_ref)){
                session()->put('paytabs_tran_ref', $response->tran_ref);
            }
            self::$redirectUrl = $response->redirect_url;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
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
            return  back()->with("errors",self::getErrors());
        }
        return redirect()->away(self::$redirectUrl);
    }

    private static function getCollection()  :array
    {

        $request = self::$request;
        $callback = route("paytabs.callback");
        if(self::hasConfig("currency")){
            $currency = self::getConfig("currency");
        }else{
            $currency = (isset($request->currency) && !empty($request->currency)) ? $request->currency : config("paytabs.currency");
        }
        
        return [
            "tran_type"         => "sale",
            "tran_class"        => "ecom",
            "cart_description"  => "Description of the items/services",
            "callback"          => $callback,
            "return"            => $callback,
            "customer_details"  => [
                "name"  => $request->name,
                "email" => $request->email,
            ],
            "payment_methods"   => (isset($request->payment_methods) && !empty($request->payment_methods)) ? [$request->payment_methods] :['all'],
            "hide_shipping"     => true,
            "hide_billing"      => true,
            "cart_currency"     => $currency,
            "cart_amount"       => $request->amount,
            "profile_id"        => self::hasConfig("profile_id") ? self::getConfig("profile_id") : config("paytabs.profile_id"),
            "currency"          => $currency,
            "cart_id"           => strval(rand(0,1000000)) ,
        ];
        
    }

}