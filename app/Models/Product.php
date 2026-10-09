<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'user_name',
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
        'user_id' => 'integer',
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
        static::creating(function (Product $product): void {
            if (empty($product->user_id) && auth()->check()) {
                $product->user_id = auth()->id();
                $product->user_name = auth()->user()->name;
            }
        });

        static::saving(function (Product $product): void {
            $purchase = (float) ($product->purchase_rate ?? 0);
            $sale = (float) ($product->sale_rate ?? 0);
            $other = (float) ($product->other_rate ?? 0);
            $product->profit_per_unit = round($sale - $purchase - $other, 2);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! empty($this->photo_path)) {
            if (str_starts_with($this->photo_path, 'data:') || str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://')) {
                return $this->photo_path;
            }

            if (Storage::disk('public')->exists($this->photo_path)) {
                return asset('storage/'.$this->photo_path);
            }

            return null;
        }

        return null;
    }

    /**
     * Compress and convert uploaded image into a compact Base64 Data URL
     * so it persists permanently in the PostgreSQL database across deployments.
     */
    public static function processImage(UploadedFile $file, int $maxDimension = 800, int $quality = 80): string
    {
        $realPath = $file->getRealPath();
        $mime = $file->getMimeType() ?: 'image/jpeg';
        $contents = file_get_contents($realPath);

        if (! function_exists('imagecreatefromstring')) {
            return 'data:'.$mime.';base64,'.base64_encode($contents);
        }

        try {
            $src = @imagecreatefromstring($contents);
            if (! $src) {
                return 'data:'.$mime.';base64,'.base64_encode($contents);
            }

            // Correct orientation from mobile phone cameras (EXIF)
            if (function_exists('exif_read_data') && str_contains($mime, 'jpeg')) {
                $exif = @exif_read_data($realPath);
                if (! empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3:
                            $src = imagerotate($src, 180, 0);
                            break;
                        case 6:
                            $src = imagerotate($src, -90, 0);
                            break;
                        case 8:
                            $src = imagerotate($src, 90, 0);
                            break;
                    }
                }
            }

            $width = imagesx($src);
            $height = imagesy($src);

            if ($width > $maxDimension || $height > $maxDimension) {
                if ($width >= $height) {
                    $newWidth = $maxDimension;
                    $newHeight = (int) round(($height / $width) * $maxDimension);
                } else {
                    $newHeight = $maxDimension;
                    $newWidth = (int) round(($width / $height) * $maxDimension);
                }
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }

            $dst = imagecreatetruecolor($newWidth, $newHeight);

            // Fill white background for transparent images converted to JPEG
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $white);

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            ob_start();
            imagejpeg($dst, null, $quality);
            $imageData = ob_get_clean();

            imagedestroy($src);
            imagedestroy($dst);

            if ($imageData) {
                return 'data:image/jpeg;base64,'.base64_encode($imageData);
            }
        } catch (\Throwable $e) {
            // fallback if GD encounters unsupported formats
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
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
