@extends('layouts.app', ['title' => $order->order_number . ' - Camping Vibes'])

@section('content')
    <section class="page-title">
        <div class="shell">
            <span class="eyebrow">Commande</span>
            <h1>{{ $order->order_number }}</h1>
            <p>{{ $order->created_at->translatedFormat('d F Y') }} · Statut : {{ $order->statusLabel() }}</p>
        </div>
    </section>

    <section class="section section-soft section-after-title">
        <div class="shell checkout-layout">
            <div class="panel">
                <h2>Articles</h2>
                @foreach ($order->items as $item)
                    <div class="line-item">
                        <img src="{{ $item->product?->mainPhotoUrl() ?? asset('logo.png') }}" alt="{{ $item->product_name }}">
                        <div>
                            <strong>{{ $item->product_name }}</strong>
                            <p class="muted">Quantité {{ $item->quantity }} · {{ \App\Support\Money::format($item->unit_price) }}</p>
                        </div>
                        <strong>{{ \App\Support\Money::format($item->total) }}</strong>
                    </div>
                @endforeach
            </div>

            <aside class="panel">
                <h2>Résumé</h2>
                <div class="summary-line">
                    <span>Sous-total</span>
                    <strong>{{ \App\Support\Money::format($order->subtotal) }}</strong>
                </div>
                <div class="summary-line">
                    <span>Total</span>
                    <strong>{{ \App\Support\Money::format($order->total) }}</strong>
                </div>
                <p class="muted">{{ $order->address_line }}, {{ $order->postal_code }} {{ $order->city }}, {{ $order->country }}</p>
                <a class="btn btn-outline" style="width:100%" href="{{ route('account.orders') }}">Retour aux commandes</a>
            </aside>
        </div>
    </section>
@endsection

