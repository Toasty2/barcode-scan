<?php

use App\Http\Controllers\ProductLookupController;
use App\Http\Controllers\TripController;
use App\Models\Shop;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/scan', function () {
    return view('scan', ['shops' => Shop::orderBy('name')->get()]);
});

Route::get('/products/{upc}', [ProductLookupController::class, 'show']);
Route::post('/trips', [TripController::class, 'store']);
