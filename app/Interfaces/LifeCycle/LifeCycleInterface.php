<?php

namespace App\Interfaces\LifeCycle;

interface LifeCycleInterface
{
    // get request data and make new instance of lifecycle trait
    public static function request( Request $request )  :self;
    // return lifecycle response
    public static function response();
    // append error to Errors List
    public static function setError( $error )  :self;
    // get all errors from Errors List
    public static function getErrors() :array;
    // check for Errors 
    public static function hasErrors() :bool;
    // make new instance 
    public static function next() :self;
    // reset payment process when errors
    public static function reset() :self;
    
}