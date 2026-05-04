@extends('layouts.app', ['title' => $product->name . ' - Camping Vibes'])

@section('content')
    <section class="section">
        <div class="shell product-detail">
            <div class="product-gallery">
                @php($photos = $product->allPhotoUrls())
                <div class="product-gallery-main">
                    <img data-gallery-main src="{{ $photos[0] }}" alt="{{ $product->name }}">
                </div>
                @if (count($photos) > 1)
                    <div class="product-thumbnails" aria-label="Photos du produit">
                        @foreach ($photos as $index => $photo)
                            <button class="product-thumbnail {{ $index === 0 ? 'is-active' : '' }}" type="button" data-gallery-thumb="{{ $photo }}" aria-label="Afficher la photo {{ $index + 1 }}">
                                <img src="{{ $photo }}" alt="">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <aside class="product-info">
                <span class="eyebrow">{{ $product->category?->name ?? 'Camping' }}</span>
                <h1>{{ $product->name }}</h1>
                <div class="price-line" style="justify-content: flex-start">
                    <span class="price">{{ \App\Support\Money::format($product->price) }}</span>
                    <span class="badge">{{ $product->stock_quantity > 0 ? $product->stock_quantity . ' en stock' : 'Rupture' }}</span>
                </div>

                <div class="product-description" style="margin-top: 24px">
                    {!! nl2br(e($product->description)) !!}
                </div>

                @if ($product->stock_quantity > 0)
                    <form method="POST" action="{{ route('cart.add', $product) }}">
                        @csrf
                        <div class="quantity-row">
                            <div class="form-field" style="max-width: 140px">
                                <label for="quantity">Quantité</label>
                                <input class="input" id="quantity" type="number" name="quantity" min="1" max="{{ min(20, $product->stock_quantity) }}" value="1">
                            </div>
                            <button class="btn btn-outline" type="submit">Ajouter au panier</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('cart.buy-now', $product) }}">
                        @csrf
                        <input type="hidden" name="quantity" value="1">
                        <button class="btn btn-primary" type="submit" style="width: 100%">Acheter maintenant</button>
                    </form>
                @else
                    <div class="panel" style="margin-top: 24px">
                        <strong>Produit indisponible</strong>
                        <p class="muted">Ce produit reviendra bientôt dans la sélection.</p>
                    </div>
                @endif
            </aside>
        </div>
    </section>

    @if ($relatedProducts->isNotEmpty())
        <section class="section section-soft">
            <div class="shell">
                <div class="section-head">
                    <div>
                        <span class="eyebrow">Compléter le camp</span>
                        <h2>Dans la même ambiance</h2>
                    </div>
                </div>
                <div class="grid grid-3">
                    @foreach ($relatedProducts as $related)
                        @include('partials.product-card', ['product' => $related])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection

