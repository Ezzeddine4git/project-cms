@extends('layouts.app', ['title' => 'Mon compte - Camping Vibes'])

@section('content')
    <section class="page-title">
        <div class="shell">
            <span class="eyebrow">Espace client</span>
            <h1>Bonjour {{ auth()->user()->name }}</h1>
            <p>Suivez vos commandes et retrouvez les produits prêts pour votre prochaine sortie.</p>
        </div>
    </section>

    <section class="section section-soft section-after-title">
        <div class="shell account-layout">
            <div class="stack">
                <div class="grid grid-2">
                    <div class="panel">
                        <span class="meta">Commandes</span>
                        <h2>{{ $totalOrders }}</h2>
                    </div>
                    <div class="panel">
                        <span class="meta">Total acheté</span>
                        <h2>{{ \App\Support\Money::format($totalSpent) }}</h2>
                    </div>
                </div>

                <div class="panel stack">
                    <h2>Dernières commandes</h2>
                    @forelse ($orders as $order)
                        <div class="order-row">
                            <div>
                                <strong>{{ $order->order_number }}</strong>
                                <p class="muted">{{ $order->created_at->translatedFormat('d F Y') }} · {{ $order->items->sum('quantity') }} article(s)</p>
                            </div>
                            <div style="text-align:right">
                                <span class="badge">{{ $order->statusLabel() }}</span>
                                <p><a href="{{ route('account.orders.show', $order) }}" style="font-weight:900">Voir</a></p>
                            </div>
                        </div>
                    @empty
                        <p class="muted">Aucune commande pour le moment.</p>
                    @endforelse
                </div>
            </div>

            <aside class="panel stack">
                <h2>Actions rapides</h2>
                <a class="btn btn-primary" href="{{ route('products.index') }}">Acheter du matériel</a>
                <a class="btn btn-outline" href="{{ route('account.orders') }}">Toutes mes commandes</a>
            </aside>
        </div>
    </section>
@endsection

