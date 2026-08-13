<?php

namespace App\Models;

use App\Casts\PriceCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'upc';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'upc',
        'product_name',
        'price',
        'last_confirmed',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'price' => PriceCast::class,
            'last_confirmed' => 'datetime',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'upc', 'upc');
    }
}
