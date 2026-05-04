<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Cart::items()->isEmpty()) {
            return redirect()->route('products.index')->with('error', 'Votre panier est vide.');
        }

        return view('checkout.show', [
            'items' => Cart::items(),
            'subtotal' => Cart::subtotal(),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $items = Cart::items();

        if ($items->isEmpty()) {
            return redirect()->route('products.index')->with('error', 'Votre panier est vide.');
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'address_line' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = DB::transaction(function () use ($items, $data, $request): Order {
            $subtotal = round((float) $items->sum('line_total'), 2);

            $order = Order::create([
                'user_id' => $request->user()->id,
                'status' => 'payee',
                'subtotal' => $subtotal,
                'total' => $subtotal,
                ...$data,
            ]);

            foreach ($items as $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];
                $unitPrice = (float) $product->price;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => round($unitPrice * $quantity, 2),
                ]);

                $product->decrement('stock_quantity', $quantity);
            }

            return $order;
        });

        Cart::clear();

        return redirect()
            ->route('account.orders.show', $order)
            ->with('success', 'Commande confirmée. Aucun paiement réel n’a été effectué pour ce prototype.');
    }
}
