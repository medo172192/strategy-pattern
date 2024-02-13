<?php

namespace App\Services\Payments\Hyperpay;

use Illuminate\Http\Request;
use App\Traits\LifeCycle;
use App\Interfaces\LifeCycle\LifeCycleInterface;
use Illuminate\Support\Facades\Validator;
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
class HyperpayPayService implements LifeCycleInterface
{
    use LifeCycle;
    use ManageUserTransactions;

    private static $connect;
    private static $responseHyper , $data;
    private static array $collection = [];
    private static string $redirectUrl;
    private static bool $status;

    public static function connect() :self
    {
        $request = self::$request;

        try {
            
            $trackable = self::getCollection();
            $user = auth()->user() ?? auth()->guard("site")->user();
            $amount = $request->amount;
            $brand = $request->payment_method ?? 'VISA' ;// MASTER OR MADA  VISA

            $response = LaravelHyperpay::config( self::$config )->addRedirectUrl( route("hyperpay.callback") )
                        ->checkout($trackable, $user, $amount, $brand, $request);

            self::$responseHyper = $response;

        } catch (\Exception $e) {
            self::setError("Hyperpay connection is failed");
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
        $check = Validator::make($request->all(),[
            "payment_method" => ["required"],
            "amount"         => ["required"],
        ]);
        if( $check->fails() ){
            self::setError($check->errors());
        }
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
        $data = self::$responseHyper->getData();
        self::$data = $data;
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
        $data = self::$data;
        return view("payments.hyperpay.dopayment",[
            "script_url"        => $data->script_url,
            "shopperResultUrl"  => $data->shopperResultUrl,
            "methods"           => collect(self::hasConfig("payment_methods") ? self::getConfig("payment_methods") : config("hyperpay.methods"))
        ]);
        
    }

    /**
     * 
     * @param int|string $orderId
     * @return array
     */
    private static function getCollection() :array
    {
        return [
            'product_id'=> 'bc842310-371f-49d1-b479-ad4b387f6630',
            'product_type' => 't-shirt'
        ];
    }
   
}