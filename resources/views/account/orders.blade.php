@extends('layouts.app', ['title' => 'Mes commandes - Camping Vibes'])

@section('content')
    <section class="page-title">
        <div class="shell">
            <span class="eyebrow">Commandes</span>
            <h1>Historique</h1>
            <p>Toutes vos commandes Camping Vibes, avec leur statut et leur montant.</p>
        </div>
    </section>

    <section class="section section-soft section-after-title">
        <div class="shell stack">
            @forelse ($orders as $order)
                <div class="order-row">
                    <div>
                        <strong>{{ $order->order_number }}</strong>
                        <p class="muted">{{ $order->created_at->translatedFormat('d F Y') }} · {{ $order->items_count }} ligne(s)</p>
                    </div>
                    <div style="text-align:right">
                        <strong>{{ \App\Support\Money::format($order->total) }}</strong>
                        <p><span class="badge">{{ $order->statusLabel() }}</span></p>
                        <a style="font-weight:900" href="{{ route('account.orders.show', $order) }}">Détail</a>
                    </div>
                </div>
            @empty
                <div class="panel">
                    <h2>Aucune commande</h2>
                    <p class="muted">Votre premier bivouac commence dans la boutique.</p>
                    <a class="btn btn-primary" href="{{ route('products.index') }}">Voir les produits</a>
                </div>
            @endforelse

            {{ $orders->links() }}
        </div>
    </section>
@endsection

