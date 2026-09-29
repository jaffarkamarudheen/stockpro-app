<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_number',
        'name',
        'photo_path',
        'quality',
        'price',
        'purchase_rate',
        'sale_rate',
        'other_rate',
        'profit_per_unit',
        'stock_quantity',
        'low_stock_threshold',
        'description',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'purchase_rate' => 'decimal:2',
        'sale_rate' => 'decimal:2',
        'other_rate' => 'decimal:2',
        'profit_per_unit' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'photo_url',
        'stock_status',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $purchase = (float) ($product->purchase_rate ?? 0);
            $sale = (float) ($product->sale_rate ?? 0);
            $other = (float) ($product->other_rate ?? 0);
            $product->profit_per_unit = round($sale - $purchase - $other, 2);
        });
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! empty($this->photo_path)) {
            return asset('storage/'.$this->photo_path);
        }

        return null;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity <= 0) {
            return 'out_of_stock';
        }

        if ($this->stock_quantity <= $this->low_stock_threshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * @return HasMany<CheckoutItem, $this>
     */
    public function checkoutItems(): HasMany
    {
        return $this->hasMany(CheckoutItem::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
