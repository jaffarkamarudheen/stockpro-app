<?php

namespace App\Models;

use Database\Factories\CheckoutFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checkout extends Model
{
    /** @use HasFactory<CheckoutFactory> */
    use HasFactory;

    public const STATUS_ORDERED = 'ordered';

    public const STATUS_WAITING_FOR_DELIVERY = 'waiting_for_delivery';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'order_number',
        'customer_name',
        'customer_address',
        'customer_phone',
        'enquiry_from',
        'is_promotion',
        'subtotal_amount',
        'discount_amount',
        'status',
        'ordered_at',
        'expected_delivery_date',
        'delivered_at',
        'received_at',
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
        'user_id' => 'integer',
        'is_promotion' => 'boolean',
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'expected_delivery_date' => 'date',
        'delivered_at' => 'datetime',
        'received_at' => 'datetime',
        'total_quantity' => 'integer',
        'total_sale_amount' => 'decimal:2',
        'total_purchase_cost' => 'decimal:2',
        'total_other_cost' => 'decimal:2',
        'total_profit' => 'decimal:2',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'status_label',
        'is_overdue_ordered',
        'is_overdue_delivered',
    ];

    protected static function booted(): void
    {
        static::creating(function (Checkout $checkout): void {
            if (empty($checkout->order_number)) {
                $checkout->order_number = 'ORD-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            }

            if (empty($checkout->status)) {
                $checkout->status = self::STATUS_ORDERED;
            }

            if (empty($checkout->ordered_at)) {
                $checkout->ordered_at = now();
            }

            if (empty($checkout->user_id) && auth()->check()) {
                $checkout->user_id = auth()->id();
                $checkout->user_name = auth()->user()->name;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CheckoutItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CheckoutItem::class);
    }

    /**
     * Overdue reminder: If status is 'ordered' and >= 1 day has passed without moving forward.
     */
    public function isOverdueOrdered(): bool
    {
        if ($this->status !== self::STATUS_ORDERED || ! $this->ordered_at) {
            return false;
        }

        return $this->ordered_at->diffInDays(now()) >= 1;
    }

    public function getIsOverdueOrderedAttribute(): bool
    {
        return $this->isOverdueOrdered();
    }

    /**
     * Overdue reminder: If status is 'delivered' and >= 2 days have passed without marking 'received'.
     */
    public function isOverdueDelivered(): bool
    {
        if ($this->status !== self::STATUS_DELIVERED) {
            return false;
        }

        $date = $this->delivered_at ?: $this->updated_at;

        return $date ? $date->diffInDays(now()) >= 2 : false;
    }

    public function getIsOverdueDeliveredAttribute(): bool
    {
        return $this->isOverdueDelivered();
    }

    public function getNeedsAttentionAttribute(): bool
    {
        return $this->isOverdueOrdered() || $this->isOverdueDelivered();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ORDERED => 'Ordered',
            self::STATUS_WAITING_FOR_DELIVERY => 'Waiting for Delivery',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function getStatusBadgeClasses(): string
    {
        return match ($this->status) {
            self::STATUS_ORDERED => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::STATUS_WAITING_FOR_DELIVERY => 'bg-amber-50 text-amber-700 border-amber-200',
            self::STATUS_DELIVERED => 'bg-purple-50 text-purple-700 border-purple-200',
            self::STATUS_RECEIVED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::STATUS_CANCELLED => 'bg-slate-100 text-slate-500 border-slate-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    /**
     * Scope query to find orders that need attention / reminders.
     *
     * @param  Builder<Checkout>  $query
     * @return Builder<Checkout>
     */
    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where(function (Builder $sub): void {
                $sub->where('status', self::STATUS_ORDERED)
                    ->where('ordered_at', '<=', now()->subDay());
            })->orWhere(function (Builder $sub): void {
                $sub->where('status', self::STATUS_DELIVERED)
                    ->where(function (Builder $d): void {
                        $d->where('delivered_at', '<=', now()->subDays(2))
                            ->orWhere(function (Builder $d2): void {
                                $d2->whereNull('delivered_at')
                                    ->where('updated_at', '<=', now()->subDays(2));
                            });
                    });
            });
        });
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<Checkout>  $query
     * @return Builder<Checkout>
     */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if (! $status) {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Scope to only non-promotional checkouts for financial revenue & profit totals.
     *
     * @param  Builder<Checkout>  $query
     * @return Builder<Checkout>
     */
    public function scopeNonPromotional(Builder $query): Builder
    {
        return $query->where('is_promotion', false);
    }
}
