<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductLookupController extends Controller
{
    private const STALE_DAYS = 90;

    public function show(string $upc): JsonResponse
    {
        $product = Product::where('upc', $upc)->first();

        if (! $product) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'product_name' => $product->product_name,
            'price' => $product->price->minorUnits,
            'last_confirmed' => $product->last_confirmed,
            'stale' => $product->last_confirmed->lt(now()->subDays(self::STALE_DAYS)),
        ]);
    }
}
