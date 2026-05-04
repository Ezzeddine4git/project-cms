@extends('layouts.app', ['title' => 'Panier - Camping Vibes'])

@section('content')
    <section class="page-title">
        <div class="shell">
            <span class="eyebrow">Panier</span>
            <h1>Votre sélection</h1>
            <p>Vérifiez les quantités avant de passer au paiement simulé.</p>
        </div>
    </section>

    <section class="section section-soft section-after-title">
        <div class="shell cart-layout">
            <div class="panel">
                @forelse ($items as $item)
                    @php($product = $item['product'])
                    <div class="line-item">
                        <img src="{{ $product->mainPhotoUrl() }}" alt="{{ $product->name }}">
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <p class="muted">{{ \App\Support\Money::format($product->price) }} l'unité</p>
                            <form method="POST" action="{{ route('cart.update', $product) }}" class="quantity-row" style="margin: 10px 0 0">
                                @csrf
                                @method('PATCH')
                                <input class="input" style="max-width: 120px" type="number" name="quantity" min="0" max="{{ min(20, $product->stock_quantity) }}" value="{{ $item['quantity'] }}">
                                <button class="btn btn-outline" type="submit">Mettre à jour</button>
                            </form>
                        </div>
                        <div style="text-align: right">
                            <strong>{{ \App\Support\Money::format($item['line_total']) }}</strong>
                            <form method="POST" action="{{ route('cart.remove', $product) }}" style="margin-top: 10px">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline" type="submit">Retirer</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <h2>Votre panier est vide</h2>
                    <p class="muted">Ajoutez une tente, un sac ou un accessoire pour continuer.</p>
                    <a class="btn btn-primary" href="{{ route('products.index') }}">Découvrir la boutique</a>
                @endforelse
            </div>

            <aside class="panel">
                <h2>Résumé</h2>
                <div class="summary-line">
                    <span>Sous-total</span>
                    <strong>{{ \App\Support\Money::format($subtotal) }}</strong>
                </div>
                <div class="summary-line">
                    <span>Livraison</span>
                    <span>Offerte</span>
                </div>
                <a class="btn btn-primary" style="width: 100%; margin-top: 18px" href="{{ route('checkout.show') }}">Passer au paiement</a>
            </aside>
        </div>
    </section>
@endsection

