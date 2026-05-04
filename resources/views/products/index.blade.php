@extends('layouts.app', ['title' => 'Produits - Camping Vibes'])

@section('content')
    <section class="page-title">
        <div class="shell">
            <span class="eyebrow">Boutique</span>
            <h1>Équipement camping</h1>
            <p>Une sélection courte et exigeante pour dormir mieux, cuisiner dehors et garder votre campement organisé.</p>

            <form class="searchbar searchbar-with-prices" method="GET" action="{{ route('products.index') }}">
                @if ($activeCategory)
                    <input type="hidden" name="categorie" value="{{ $activeCategory }}">
                @endif
                <input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher un produit">
                <input class="input" type="number" min="0" step="1" name="prix_min" value="{{ request('prix_min') }}" placeholder="Prix min.">
                <input class="input" type="number" min="0" step="1" name="prix_max" value="{{ request('prix_max') }}" placeholder="Prix max.">
                <button class="btn btn-primary" type="submit">Rechercher</button>
            </form>
        </div>
    </section>

    <section class="section section-soft products-listing-section">
        <div class="shell">
            <div class="filters">
                <a @class(['filter-link', 'is-active' => blank($activeCategory)]) href="{{ route('products.index', request()->only(['q', 'prix_min', 'prix_max'])) }}">Tous</a>
                @foreach ($categories as $category)
                    <a @class(['filter-link', 'is-active' => $activeCategory === $category->slug]) href="{{ route('products.index', array_filter(['categorie' => $category->slug, 'q' => request('q'), 'prix_min' => request('prix_min'), 'prix_max' => request('prix_max')])) }}">
                        {{ $category->name }}
                    </a>
                @endforeach
                @if (request()->filled('q') || request()->filled('prix_min') || request()->filled('prix_max') || $activeCategory)
                    <a class="filter-link filter-reset" href="{{ route('products.index') }}">Réinitialiser</a>
                @endif
            </div>

            <div class="grid grid-3">
                @forelse ($products as $product)
                    @include('partials.product-card', ['product' => $product])
                @empty
                    <div class="panel">
                        <h2>Aucun produit trouvé</h2>
                        <p class="muted">Essayez une autre recherche ou revenez à toute la collection.</p>
                    </div>
                @endforelse
            </div>

            <div style="margin-top: 28px">
                {{ $products->links() }}
            </div>
        </div>
    </section>
@endsection

