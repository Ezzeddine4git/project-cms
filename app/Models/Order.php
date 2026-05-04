<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const STATUSES = [
        'nouvelle' => 'Nouvelle',
        'payee' => 'Payée',
        'preparation' => 'En préparation',
        'expediee' => 'Expédiée',
        'terminee' => 'Terminée',
        'annulee' => 'Annulée',
    ];

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'total',
        'customer_name',
        'customer_email',
        'address_line',
        'postal_code',
        'city',
        'country',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (blank($order->order_number)) {
                $order->order_number = 'CV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
