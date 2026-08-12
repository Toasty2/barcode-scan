<?php

use App\Support\Money\Currencies\GBP;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The Currency implementation used wherever a Price-cast attribute
    | doesn't specify one explicitly. This project only ever uses GBP, but
    | a fork wanting a different currency only needs to change this one
    | value (and provide its own class implementing App\Support\Money\Currency)
    | rather than touching the Price/PriceCast implementation itself.
    |
    */

    'default_currency' => GBP::class,

];
