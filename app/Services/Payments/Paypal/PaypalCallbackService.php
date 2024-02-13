<?php

namespace App\Services\Payments\Paypal;

use Illuminate\Http\Request;
use App\Traits\LifeCycle;
use App\Interfaces\LifeCycle\LifeCycleInterface;
use PayPal\Api\Payment;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use App\Interfaces\LifeCycle\CallbackLifeCycleInterface as CallbackLifeCycle;


/**
 * @see Avalible Configrations "mode" , "client_id" , "secret_key"
 * -  mode        : you can select payment mode
 * -  client_id   : you can select payment client_id from your paytabs account 
 * -  secret_key  : you can select payment secret_key from your paytabs account 
 * - if you wanna set config you can use setConfig with Paytabs instance
 * ```
 * ->setConfig( string key,  string value )
 * ``` 
 */


class PaypalCallbackService implements LifeCycleInterface , CallbackLifeCycle
{
    use LifeCycle;

    private static $status;
    private static $callback_status;
    private static $apiContext;    
    private static $execution  , $payment;    
    /**
     * @see prepare request data collection
     * @return self
    */
    public static function makeStatus():self
    {
        if(self::hasErrors()) return self::next();
        $request  = request();

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

        $paymentId = $request->input('paymentId');
        $payerId = $request->input('PayerID');

        $payment = Payment::get($paymentId, $apiContext);

        $execution = new \PayPal\Api\PaymentExecution();
        $execution->setPayerId($payerId);

        self::$execution = $execution;
        self::$payment = $payment;
        self::$apiContext = $apiContext;

        
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

            $result = self::$payment->execute( self::$execution, self::$apiContext);
            self::$status = $result;

            if ( $result->getState() == 'approved'){
                self::$callback_status = payment_success();
            }else{
                self::$callback_status = payment_failed();
            }

        } catch (\Exception $e) {
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
            'details'   => self::$status->toArray()
        ]);
    }
 

}
