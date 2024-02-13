<?php

namespace App\Interfaces\LifeCycle;

interface CallbackLifeCycleInterface
{
    
    // connect with payment and get status
    public static function makeStatus():self;
    // add status condition
    public static function callback() :self;
    // redirect to system services
    public static function response() ;

}