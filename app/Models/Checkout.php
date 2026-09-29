<?php

namespace App\Models;

use Database\Factories\CheckoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checkout extends Model
{
    /** @use HasFactory<CheckoutFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_address',
        'customer_phone',
        'enquiry_from',
        'total_quantity',
        'total_sale_amount',
        'total_purchase_cost',
        'total_other_cost',
        'total_profit',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'total_quantity' => 'integer',
        'total_sale_amount' => 'decimal:2',
        'total_purchase_cost' => 'decimal:2',
        'total_other_cost' => 'decimal:2',
        'total_profit' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Checkout $checkout): void {
            if (empty($checkout->order_number)) {
                $checkout->order_number = 'ORD-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            }
        });
    }

    /**
     * @return HasMany<CheckoutItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CheckoutItem::class);
    }
}
