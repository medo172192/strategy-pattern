<?php

namespace App\Services\Payments\Paypal;

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

/**
 * @see Avalible Configrations "mode" , "client_id" , "secret_key" , "currency"
 * -  mode        : you can select payment mode
 * -  client_id   : you can select payment client_id from your paytabs account 
 * -  secret_key  : you can select payment secret_key from your paytabs account 
 * - if you wanna set config you can use setConfig with Paytabs instance
 * ```
 * ->setConfig( string key,  string value )
 * ``` 
 * ### Data
 * - int|string amount 
 */

class PaypalPayService implements LifeCycleInterface
{
    use LifeCycle;

    private static $apiContext , $payment;    
    private static string $redirectUrl;

    public static function connect()  :self
    {

        $config = (object)config('paypal.paypal');
        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                self::hasConfig("client_id")  ? self::getConfig("client_id")  : $config->client_id,
                self::hasConfig("secret_key") ? self::getConfig("secret_key") : $config->secret_key
            )
        );

        $apiContext->setConfig([
            'mode' => self::hasConfig("mode")  ? self::getConfig("mode")  : $config->mode,
        ]);

        self::$apiContext = $apiContext;
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
        $check  = Validator::make($request->all(),[
            "amount"  => ["required"],
        ]);
        if($check->fails()){
            return self::setError($check->errors()->first());
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
        
        if(self::hasConfig("currency")){
            $currency = self::getConfig("currency");
        }else{
            $currency = $request->currency??config("paypal.currency");
        }

        $payer = new Payer();
        $payer->setPaymentMethod("paypal");
        
        $amount = new Amount();
        $amount->setTotal($request->amount);
        $amount->setCurrency($currency);

        $transaction = new Transaction();
        $transaction->setAmount($amount);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(route("paypal.callback"))
            ->setCancelUrl(route("paypal.callback"));

        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setTransactions([$transaction])
            ->setRedirectUrls($redirectUrls);
        self::$payment = $payment;
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
            self::$payment->create(self::$apiContext);
            self::$redirectUrl = self::$payment->getApprovalLink();

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

    
}