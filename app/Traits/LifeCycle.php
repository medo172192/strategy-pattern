<?php

namespace App\Traits;

trait LifeCycle
{
    public static $request ;
    public static array $error= [];
    private static array $config = [];


    // get request data and make new instance of lifecycle trait
    public static function request( $request )  :self
    {
        self::$request = $request;
        return new self;
    }

    // append error to Errors List
    public static function setError( $error )  :self
    {
        array_push(self::$error ,$error );
        return new self;
    }

    // get all errors from Errors List
    public static function getErrors() :array
    {
        return self::$error;
    }

    // check for Errors 
    public static function hasErrors() :bool
    {
      if (count(self::$error)>0)  return true;
      return false;
    }

    // make new instance 
    public static function next() :self
    {
      return new self;
    }

    // if you wanna made reset of current payment process
    public static function reset() :self
    {
        removePaymentRequest();
        return new self;
    }

    // if you wanna override of avalible configration
    public static function setConfig( string $config , $value )  {
      self::$config[$config] = $value;
      return new self;
    }

    // if you wanna check on specifice configration
    public static function hasConfig( string $config)  
    {
      return (isset(self::$config[$config]));
    }

    // if you wanna get specifice configration
    public static function getConfig( string $config)  {
      return self::$config[$config];
    }
}