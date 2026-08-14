<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use App\Models\Trip;
use Brick\Money\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'discount' => ['nullable', 'integer', 'min:0'],
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'new_shop' => ['nullable', 'array'],
            'new_shop.name' => ['required_with:new_shop', 'string', 'max:255'],
            'new_shop.colour' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.upc' => ['nullable', 'string', 'max:32'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.price' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.entry_type' => ['required', 'in:scan'],
        ]);

        [$trip, $newShop] = DB::transaction(function () use ($validated) {
            $currency = config('money.default_currency');
            $shopId = $validated['shop_id'] ?? null;
            $newShop = null;

            if ($shopId === null && ! empty($validated['new_shop']['name'] ?? null)) {
                $newShop = Shop::create([
                    'name' => $validated['new_shop']['name'],
                    'colour' => $validated['new_shop']['colour'] ?? null,
                    'is_default' => false,
                ]);
                $shopId = $newShop->id;
            }

            $trip = Trip::create([
                'shopped_on' => now()->toDateString(),
                'shop_id' => $shopId,
                'discount' => Money::ofMinor($validated['discount'] ?? 0, $currency),
            ]);

            foreach ($validated['items'] as $item) {
                $product = null;

                if (! empty($item['upc'])) {
                    $product = Product::updateOrCreate(
                        ['upc' => $item['upc']],
                        [
                            'product_name' => $item['product_name'],
                            'price' => Money::ofMinor($item['price'], $currency),
                            'last_confirmed' => now(),
                        ]
                    );
                }

                $trip->purchases()->create([
                    'product_id' => $product?->id,
                    'product_name' => $item['product_name'],
                    'entry_type' => $item['entry_type'],
                    'quantity' => $item['quantity'],
                    'unit_price' => Money::ofMinor($item['price'], $currency),
                ]);
            }

            return [$trip, $newShop];
        });

        return response()->json([
            'success' => true,
            'trip_id' => $trip->id,
            'shop' => $newShop ? ['id' => $newShop->id, 'name' => $newShop->name] : null,
        ]);
    }
}
