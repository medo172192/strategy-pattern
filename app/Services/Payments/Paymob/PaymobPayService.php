<?php

namespace App\Services\Payments\Paymob;

use App\Traits\LifeCycle;
use App\Interfaces\LifeCycle\LifeCycleInterface;
use App\Services\Payments\Paymob\PaymobConnectionService as PaymobConnection;

/**
 * @see Avalible Configrations  "api_key" , "username" , "password", "iframe" , "identifier" 
 * -  mode        : you can select payment mode
 * -  api_key     : you can select payment api_key  
 * -  country_iso : you can select payment country_iso as a default country code
 * - if you wanna set config you can use setConfig with Paytabs instance
 * ```
 * ->setConfig( string key,  string value )
 * ``` 
 * ### Data
 * - string amount 
 * - string currency 
 * - string email 
 * - string payment_method
 * 
 * ### Payments Services 
 * - WALLET
 * - AGGREGATOR
 * - CASH
 * - Card Payments in case you need to iframe id
 */

class PaymobPayService implements LifeCycleInterface
{
    
    use LifeCycle;

    private static string  $amount, $currency, $PaymobToken, $returnUrl ;
    private static array $billing_data;
    private static string $token , $username ,$password ;


    public static function validation() :self
    {
        if( self::hasErrors() ) return self::next();

        return new self;
    }

    public static function prepare() :self
    {
        if( self::hasErrors() ) return self::next();
        $request  =self::$request;
        
        $amount = $request->amount;
        $currency = $request->currency??config("paymob.currency");
        $billing_data = self::getCollection();

        $token    = self::hasConfig("api_key")    ? self::getConfig("api_key")    : config("paymob.auth.token");
        $username = self::hasConfig("username")   ? self::getConfig("username")   : config("paymob.auth.username");
        $password = self::hasConfig("password")   ? self::getConfig("password")   : config("paymob.auth.password");

        self::$token = $token;
        self::$username = $username;
        self::$password = $password;
        self::$amount = $amount;
        self::$currency = $currency;
        self::$billing_data = $billing_data;
        // set payment request to global system 
        setPaymentRequest( $request->all() );

        return new self;
    }

    public static function connection() :self
    {

        if( self::hasErrors() ) return self::next();

            try {
                $integration_id = self::hasConfig("integration") ? self::getConfig("integration") : config("paymob.integration");
                $PaymobToken = PaymobConnection::Authentication( self::$token  , self::$username ,  self::$password )
                ->OrderRegistrationAPI( self::$amount  , self::$currency )
                ->PaymentKeyRequest( self::$billing_data , $integration_id )
                ->getToken();

                if ( $PaymobToken['status'] == 'error' ){
                    self::setError($PaymobToken);
                    return new self;
                }
                self::$PaymobToken = $PaymobToken['token'] ;

            } catch (\Exception $e) {
                self::setError($e->getMessage());
            }

        return new self;

    }

    public static function pay() :self
    {

        if( self::hasErrors() ) return self::next();
        
        try {
            
            $request = self::$request;
            $iframe = self::hasConfig("iframe") ? self::getConfig("iframe") : config("paymob.iframe");

            $response = PaymobConnection::doPayment( $request->payment_method , self::sourceConfig() , self::$PaymobToken  , $iframe );
            if(!isset($response->redirect_url) || empty($response->redirect_url)){
                self::setError($response);
                return new self;
            }

            self::$returnUrl = $response->redirect_url;

        } catch (\Exception $e) {
            self::setError($e->getMessage());
        }
        
        return new self;

    }

    public static function response()
    {
        if( self::hasErrors() ){
            self::reset();
            return back()->with("error", self::getErrors());
        }
        return redirect()->away(self::$returnUrl);
    }

    public static function getCollection()  :array
    {
        $request = self::$request;

        return [
            "apartment"=> "803",
            "email"=> "ppick177@gmail.com",
            "floor"=> "42",
            "first_name"=> "Clifford",
            "street"=> "Ethan Land",
            "building"=> "8028",
            "phone_number"=> "01096618954",
            "shipping_method"=> "PKG",
            "postal_code"=> "01898",
            "city"=> "Jaskolskiburgh",
            "country"=> "CR",
            "last_name"=> "Nicolas",
            "state"=> "Utah"
        ];
    }

    private static function sourceConfig() :array
    {
        $request = self::$request;
        return [
            "identifier"    => self::hasConfig("identifier") ? self::getConfig("identifier") :  config("paymob.identifier"),
            "subtype"       => strtoupper($request->payment_method),
        ];
    }

}

 