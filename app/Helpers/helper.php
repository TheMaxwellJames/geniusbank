<?php

use App\Models\Currency;
use App\Models\Generalsetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

    if(!function_exists('globalCurrency')){
        function globalCurrency(){
            $currency = Session::get('currency') ?  DB::table('currencies')->where('id','=',Session::get('currency'))->first() : DB::table('currencies')->where('is_default','=',1)->first();
            return $currency;
        }
    }

    if(!function_exists('showPrice')){
        function showPrice($price,$currency){
            $gs = Generalsetting::first();

            $price = round(($price) * $currency->value,2);
            if($gs->currency_format == 0){
                return $currency->sign. $price;
            }
            else{
                return $price. $currency->sign;
            }
        }
    }

    if(!function_exists('showNameAmount')){
        function showNameAmount($amount){
            $gs = Generalsetting::first();
            $currency = globalCurrency();

            $price = round(($amount) * $currency->value,2);
            if($gs->currency_format == 0){
                return $currency->name.' '. $price;
            }
            else{
                return $price.' '. $currency->name;
            }
        }
    }

    if(!function_exists('showAmountSign')){
        function showAmountSign($amount){
            $gs = Generalsetting::first();
            $currency = globalCurrency();

            $price = round(($amount) * $currency->value,2);
            if($gs->currency_format == 0){
                return $currency->name.' '. $price;
            }
            else{
                return $price.' '. $currency->sign;
            }
        }
    }

    if(!function_exists('convertedAmount')){
        function convertedAmount($price){
            $currency = globalCurrency();

            $price = round(($price) * $currency->value,2);
            return $price;
        }
    }

    if(!function_exists('baseCurrencyAmount')){
        function baseCurrencyAmount($amount){
            $currency = globalCurrency();
            return $amount/$currency->value;
          }
      }

    if(!function_exists('convertedPrice')){
        function convertedPrice($price,$currency){
        return $price = $price * $currency->value;
        }
    }

    if(!function_exists('defaultCurr')){
        function defaultCurr(){
        return Currency::where('is_default','=',1)->first();
        }
    }



// Code to mask the account number
function maskAccountNumber($accountNumber)
{
    $length = strlen($accountNumber);

    // Keep the first two digits visible, mask the middle digits with asterisks, and keep the last two digits visible
    $visibleDigits = 2;
    $maskedAccount = substr($accountNumber, 0, $visibleDigits) . str_repeat('*', $length - 2*$visibleDigits) . substr($accountNumber, -$visibleDigits);

    return $maskedAccount;
}


?>
