<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Camping Vibes' }}</title>
    <link rel="stylesheet" href="{{ asset('css/camping-vibes.css') }}">
    <script src="{{ asset('js/camping-vibes.js') }}" defer></script>
</head>
<body>
    <header class="site-header">
        <div class="shell header-inner">
            <a class="brand" href="{{ route('home') }}" aria-label="Camping Vibes">
                <img src="{{ asset('logo.png') }}" alt="Camping Vibes">
            </a>

            <nav class="nav" aria-label="Navigation principale">
                <a href="{{ route('home') }}" @class(['is-active' => request()->routeIs('home')])>Accueil</a>
                <a href="{{ route('products.index') }}" @class(['is-active' => request()->routeIs('products.*')])>Produits</a>
                <a href="{{ route('blog.index') }}" @class(['is-active' => request()->routeIs('blog.*')])>Journal</a>
                <a class="cart-pill" href="{{ route('cart.show') }}">Panier <span class="cart-count">{{ \App\Support\Cart::count() }}</span></a>
                @auth
                    <a href="{{ route('account.dashboard') }}" @class(['is-active' => request()->routeIs('account.*')])>Compte</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">Déconnexion</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Connexion</a>
                @endauth
            </nav>

            <details class="mobile-nav">
                <summary>Menu</summary>
                <div class="mobile-panel">
                    <a href="{{ route('home') }}">Accueil</a>
                    <a href="{{ route('products.index') }}">Produits</a>
                    <a href="{{ route('blog.index') }}">Journal</a>
                    <a href="{{ route('cart.show') }}">Panier ({{ \App\Support\Cart::count() }})</a>
                    @auth
                        <a href="{{ route('account.dashboard') }}">Compte</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">Déconnexion</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}">Connexion</a>
                    @endauth
                </div>
            </details>
        </div>
    </header>

    @if (session('success'))
        <div class="flash"><div class="shell">{{ session('success') }}</div></div>
    @endif

    @if (session('error'))
        <div class="flash flash-error"><div class="shell">{{ session('error') }}</div></div>
    @endif

    <main class="site-main">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="shell footer-inner">
            <div>
                <img src="{{ asset('logo.png') }}" alt="Camping Vibes">
                <p>Équipement camping premium, pensé pour des escapades simples, belles et fiables.</p>
            </div>
            <div>
                <strong style="color:#111">Camping Vibes</strong>
                <p>Prototype e-commerce avec CMS Filament.</p>
            </div>
        </div>
    </footer>
</body>
</html>

