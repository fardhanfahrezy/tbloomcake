<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'product_id',
    'size_label',
    'price',
    'max_daily_quota',
    'is_active',
])]
class ProductVariant extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'max_daily_quota' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Produk induk dari varian ini.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Data kuota harian untuk varian ini.
     *
     * @return HasMany<ProductAvailability, $this>
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(ProductAvailability::class);
    }

    /**
     * Order items yang mereferensikan varian ini.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
