<?php

namespace App\Services\Payments;

use App\Models\PaymentConfig;


class PaymentListener
{

    public static  $paymentName , $settings;
    private static $status;

    public static function payment(string $payment_name) 
    {

        self::$paymentName  =   strtolower($payment_name);
        self::$settings     =   PaymentConfig::where("store_id",get_store_id())->where("name",self::$paymentName)->first();
        if( !self::$settings ) throw new \Exception("The payment [".self::$paymentName."] not found", 1);

    }

    public static function handler( string $config ){

        if(auth()->user()){
            $settings = self::$settings;
        }else{
            $settings = get_site_settings();
        } 
        return $settings->$config; 

    }
    

}