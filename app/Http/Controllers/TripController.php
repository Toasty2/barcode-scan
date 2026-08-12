<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Trip;
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.upc' => ['nullable', 'string', 'max:32'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.price' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.entry_type' => ['required', 'in:scan'],
        ]);

        $trip = DB::transaction(function () use ($validated) {
            $trip = Trip::create([
                'shopped_on' => now()->toDateString(),
                'shop_id' => $validated['shop_id'] ?? null,
                'discount' => $validated['discount'] ?? 0,
            ]);

            foreach ($validated['items'] as $item) {
                if (! empty($item['upc'])) {
                    Product::updateOrCreate(
                        ['upc' => $item['upc']],
                        [
                            'product_name' => $item['product_name'],
                            'price' => $item['price'],
                            'last_confirmed' => now(),
                        ]
                    );
                }

                $trip->purchases()->create([
                    'upc' => $item['upc'] ?? null,
                    'product_name' => $item['product_name'],
                    'entry_type' => $item['entry_type'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                ]);
            }

            return $trip;
        });

        return response()->json(['success' => true, 'trip_id' => $trip->id]);
    }
}
