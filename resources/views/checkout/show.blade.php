@extends('layouts.app', ['title' => 'Paiement - Camping Vibes'])

@section('content')
    <section class="page-title">
        <div class="shell">
            <span class="eyebrow">Paiement prototype</span>
            <h1>Finaliser la commande</h1>
            <p>Le panneau de paiement est visuel. La commande est créée dès confirmation, sans transaction bancaire réelle.</p>
        </div>
    </section>

    <section class="section section-soft section-after-title">
        <div class="shell checkout-layout">
            <form class="panel stack" method="POST" action="{{ route('checkout.confirm') }}">
                @csrf
                <h2>Coordonnées</h2>
                <div class="grid grid-2">
                    <div class="form-field">
                        <label for="customer_name">Nom</label>
                        <input class="input" id="customer_name" name="customer_name" value="{{ old('customer_name', auth()->user()->name) }}" required>
                        @error('customer_name') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-field">
                        <label for="customer_email">E-mail</label>
                        <input class="input" id="customer_email" type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email) }}" required>
                        @error('customer_email') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="form-field">
                    <label for="address_line">Adresse</label>
                    <input class="input" id="address_line" name="address_line" value="{{ old('address_line') }}" required>
                    @error('address_line') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-3">
                    <div class="form-field">
                        <label for="postal_code">Code postal</label>
                        <input class="input" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" required>
                        @error('postal_code') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-field">
                        <label for="city">Ville</label>
                        <input class="input" id="city" name="city" value="{{ old('city') }}" required>
                        @error('city') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-field">
                        <label for="country">Pays</label>
                        <input class="input" id="country" name="country" value="{{ old('country', 'Tunisie') }}" required>
                        @error('country') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="form-field">
                    <label for="notes">Note de livraison</label>
                    <textarea class="input" id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
                    @error('notes') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="payment-panel">
                    <strong>Paiement sécurisé simulé</strong>
                    <div class="card-preview">
                        <span>Carte prototype</span>
                        <strong>4242 4242 4242 4242</strong>
                        <span>Exp. 12/30 - CVV 123</span>
                    </div>
                    <p style="margin:0; color:rgba(255,255,255,.72)">Aucune donnée bancaire n'est demandée ni transmise.</p>
                </div>

                <button class="btn btn-primary" type="submit">Confirmer l'achat</button>
            </form>

            <aside class="panel">
                <h2>Votre commande</h2>
                @foreach ($items as $item)
                    @php($product = $item['product'])
                    <div class="line-item" style="grid-template-columns:64px 1fr auto">
                        <img src="{{ $product->mainPhotoUrl() }}" alt="{{ $product->name }}" style="width:64px;height:58px">
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <p class="muted">Qté {{ $item['quantity'] }}</p>
                        </div>
                        <strong>{{ \App\Support\Money::format($item['line_total']) }}</strong>
                    </div>
                @endforeach
                <div class="summary-line">
                    <span>Total</span>
                    <strong>{{ \App\Support\Money::format($subtotal) }}</strong>
                </div>
            </aside>
        </div>
    </section>
@endsection


