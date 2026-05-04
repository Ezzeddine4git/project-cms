@extends('layouts.app', ['title' => 'Camping Vibes - Boutique camping premium'])

@section('content')
    @php
        $hero = $sections->get('hero');
        $story = $sections->get('story');
        $promo = $sections->get('promo');
    @endphp

    <section class="hero">
        @if ($hero?->imageUrl())
            <img src="{{ $hero->imageUrl() }}" alt="">
        @endif
        <div class="shell hero-content">
            <span class="eyebrow">Boutique outdoor tunisienne</span>
            <h1>{{ $hero?->title ?? 'Camping Vibes' }}</h1>
            <p>{{ $hero?->subtitle ?? 'Du matériel de camping premium pour préparer des nuits dehors avec style, confort et sérénité.' }}</p>
            <div class="actions">
                <a class="btn btn-primary" href="{{ $hero?->primary_url ?: route('products.index') }}">{{ $hero?->primary_label ?: 'Explorer les produits' }}</a>
                <a class="btn btn-ghost" href="{{ $hero?->secondary_url ?: route('blog.index') }}">{{ $hero?->secondary_label ?: 'Lire le journal' }}</a>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="shell">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Sélection</span>
                    <h2>Les essentiels qui transforment le bivouac.</h2>
                </div>
                <a class="btn btn-outline" href="{{ route('products.index') }}">Voir tous les produits</a>
            </div>
            <div class="grid grid-4">
                @foreach ($featuredProducts as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    </section>

    <section class="section">
        <div class="shell story">
            <div class="story-copy">
                <span class="eyebrow">Notre approche</span>
                <h2>{{ $story?->title ?? 'Un campement plus simple, plus beau, plus fiable.' }}</h2>
                <p>{{ $story?->body ?? 'Camping Vibes réunit des produits choisis pour durer, se monter vite et accompagner aussi bien un week-end en forêt qu’un long road trip en montagne.' }}</p>
                <div class="actions">
                    <a class="btn btn-dark" href="{{ $story?->primary_url ?: route('products.index') }}">{{ $story?->primary_label ?: 'Préparer mon camp' }}</a>
                </div>
            </div>
            @if ($story?->imageUrl())
                <div class="story-media">
                    <img src="{{ $story->imageUrl() }}" alt="">
                </div>
            @endif
        </div>
    </section>

    <section class="section section-dark">
        <div class="shell promo">
            @if ($promo?->imageUrl())
                <div class="promo-media">
                    <img src="{{ $promo->imageUrl() }}" alt="">
                </div>
            @endif
            <div>
                <span class="eyebrow">Offre prototype</span>
                <h2>{{ $promo?->title ?? 'Un paiement simulé, une commande réelle.' }}</h2>
                <p class="muted">{{ $promo?->body ?? 'Le tunnel de paiement est prêt pour présenter l’expérience complète sans intégrer de prestataire bancaire.' }}</p>
                <div class="actions">
                    <a class="btn btn-primary" href="{{ $promo?->primary_url ?: route('products.index') }}">{{ $promo?->primary_label ?: 'Commander maintenant' }}</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="shell">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Journal</span>
                    <h2>Guides et idées pour mieux partir.</h2>
                </div>
                <a class="btn btn-outline" href="{{ route('blog.index') }}">Tous les articles</a>
            </div>
            <div class="grid grid-3">
                @foreach ($blogPosts as $post)
                    @include('partials.article-card', ['post' => $post])
                @endforeach
            </div>
        </div>
    </section>
@endsection

