<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

class Cart
{
    public static function add(Product $product, int $quantity = 1): void
    {
        $cart = self::raw();
        $current = $cart[$product->id] ?? 0;
        $cart[$product->id] = min($product->stock_quantity, max(1, $current + $quantity));

        session(['cart' => $cart]);
    }

    public static function replace(Product $product, int $quantity): void
    {
        $cart = self::raw();

        if ($quantity <= 0) {
            unset($cart[$product->id]);
        } else {
            $cart[$product->id] = min($product->stock_quantity, $quantity);
        }

        session(['cart' => $cart]);
    }

    public static function remove(Product $product): void
    {
        $cart = self::raw();
        unset($cart[$product->id]);

        session(['cart' => $cart]);
    }

    public static function clear(): void
    {
        session()->forget('cart');
    }

    public static function count(): int
    {
        return array_sum(self::raw());
    }

    public static function items(): Collection
    {
        $cart = self::raw();

        if ($cart === []) {
            return collect();
        }

        return Product::query()
            ->active()
            ->whereIn('id', array_keys($cart))
            ->get()
            ->map(function (Product $product) use ($cart): array {
                $quantity = min($product->stock_quantity, (int) ($cart[$product->id] ?? 0));

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => round((float) $product->price * $quantity, 2),
                ];
            })
            ->filter(fn (array $item): bool => $item['quantity'] > 0)
            ->values();
    }

    public static function subtotal(): float
    {
        return (float) self::items()->sum('line_total');
    }

    private static function raw(): array
    {
        return session('cart', []);
    }
}
