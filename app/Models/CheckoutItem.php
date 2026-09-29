<?php

namespace App\Models;

use Database\Factories\CheckoutItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutItem extends Model
{
    /** @use HasFactory<CheckoutItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'checkout_id',
        'product_id',
        'quantity',
        'unit_purchase_rate',
        'unit_sale_rate',
        'unit_other_rate',
        'unit_profit',
        'subtotal_sale',
        'subtotal_profit',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_purchase_rate' => 'decimal:2',
        'unit_sale_rate' => 'decimal:2',
        'unit_other_rate' => 'decimal:2',
        'unit_profit' => 'decimal:2',
        'subtotal_sale' => 'decimal:2',
        'subtotal_profit' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Checkout, $this>
     */
    public function checkout(): BelongsTo
    {
        return $this->belongsTo(Checkout::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
