<?php

namespace App\Services\Payments\Myfatoorah;

use Illuminate\Http\Request;
use App\Traits\LifeCycle;
use App\Interfaces\LifeCycle\LifeCycleInterface;
use MyFatoorah\Library\PaymentMyfatoorahApiV2;
use Illuminate\Support\Facades\Validator;

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
class MyfatoorahPayService implements LifeCycleInterface
{
    use LifeCycle;

    private static $connect;
    private static int $paymentMethodId = 0;
    private static array $collection = [];
    private static string $redirectUrl;
    private static bool $status;

    public static function connect() :self
    {
        try {
            $connect = new PaymentMyfatoorahApiV2(
                self::hasConfig("api_key")      ? self::getConfig("api_key")     : config('myfatoorah.api_key'), 
                self::hasConfig("country_iso")  ? self::getConfig("country_iso") : config('myfatoorah.country_iso'), 
                self::hasConfig("mode")         ? self::getConfig("mode")        : config('myfatoorah.test_mode')
            );
            self::$connect = $connect;
            
        } catch (\Exception $e) {
            self::setError(__("Myfatoorah Payment there's error when connect"));
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
            "name"           => ["required"],
            "email"          => ["required"],
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
        // set payment method id for this transaction
        $paymentMethodId = $request->payment_method; 
        self::$paymentMethodId = $paymentMethodId;
        // add new item to the collection list
        $collection = self::getCollection();
        self::$collection = $collection ;
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
            $data     = self::$connect->getInvoiceURL(self::$collection, self::$paymentMethodId);
            $response = ['IsSuccess' => 'true', 'Message' => 'Invoice created successfully.', 'data' => $data];
        } catch (\Exception $e) {
            self::setError(__("payment pay has errors"));
            return new self;
        }
        self::$redirectUrl = $response['data']['invoiceURL'];
       
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
        return redirect()->away(self::$redirectUrl);
        
    }

    /**
     * 
     * @param int|string $orderId
     * @return array
     */
    private static function getCollection($orderId = null) :array
    {
        $request        = self::$request;
        $callbackURL    = route('myfatoorah.callback');
        if(self::hasConfig("currency")){
            $currency = self::getConfig("currency");
        }else{
            $currency = ( isset($request->currency) && !empty($request->currency)  ) ? $request->currency : config("myfatoorah.currency");
        }
        
        return [
            'CustomerName'       => $request->name,
            'CustomerEmail'      => $request->email,
            'InvoiceValue'       => $request->amount,
            'DisplayCurrencyIso' => $currency,
            'CallBackUrl'        => $callbackURL,
            'ErrorUrl'           => $callbackURL,
            'Language'           => 'ar',
            'CustomerReference'  => $orderId,
        ];
    }
   
}