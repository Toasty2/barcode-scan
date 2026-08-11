<?php

use App\Http\Controllers\ProductLookupController;
use App\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/scan', function () {
    return view('scan');
});

Route::get('/products/{upc}', [ProductLookupController::class, 'show']);
Route::post('/trips', [TripController::class, 'store']);
