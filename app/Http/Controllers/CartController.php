<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(): View
    {
        return view('cart.show', [
            'items' => Cart::items(),
            'subtotal' => Cart::subtotal(),
        ]);
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active && $product->stock_quantity > 0, 404);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        Cart::add($product, (int) ($data['quantity'] ?? 1));

        return back()->with('success', 'Produit ajouté au panier.');
    }

    public function buyNow(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active && $product->stock_quantity > 0, 404);

        Cart::clear();
        Cart::add($product, max(1, (int) $request->integer('quantity', 1)));

        return redirect()->route('checkout.show');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        Cart::replace($product, (int) $data['quantity']);

        return back()->with('success', 'Panier mis à jour.');
    }

    public function remove(Product $product): RedirectResponse
    {
        Cart::remove($product);

        return back()->with('success', 'Produit retiré du panier.');
    }
}
