<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboItem extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'combo_product_id',
        'item_product_id',
        'quantity',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'combo_product_id' => 'integer',
        'item_product_id' => 'integer',
        'quantity' => 'integer',
    ];

    /**
     * The parent combo product that bundles these items.
     *
     * @return BelongsTo<Product, $this>
     */
    public function comboProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'combo_product_id');
    }

    /**
     * The individual product component bundled inside the combo.
     *
     * @return BelongsTo<Product, $this>
     */
    public function itemProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_product_id');
    }
}
