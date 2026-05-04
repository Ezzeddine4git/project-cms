<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'photos',
        'price',
        'stock_quantity',
        'is_active',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if (blank($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function mainPhotoUrl(): string
    {
        return $this->photoUrl(collect($this->photos)->filter()->first());
    }

    public function allPhotoUrls(): array
    {
        $urls = collect($this->photos)
            ->filter()
            ->map(fn (string $photo): string => $this->photoUrl($photo))
            ->values()
            ->all();

        return $urls === [] ? [asset('logo.png')] : $urls;
    }

    private function photoUrl(?string $photo): string
    {
        if (blank($photo)) {
            return asset('logo.png');
        }

        if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
            return $photo;
        }

        if (str_starts_with($photo, 'images/')) {
            return asset($photo);
        }

        return Storage::disk('public')->url($photo);
    }
}
