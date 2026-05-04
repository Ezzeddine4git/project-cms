@extends('layouts.app', ['title' => 'Connexion - Camping Vibes'])

@section('content')
    <section class="auth-wrap">
        <div class="shell">
            <div class="auth-card">
                <span class="eyebrow">Compte client</span>
                <h1>Connexion</h1>
                <p class="muted">Retrouvez vos commandes et confirmez vos achats.</p>

                <form class="stack" method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="form-field">
                        <label for="email">E-mail</label>
                        <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                        @error('email') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-field">
                        <label for="password">Mot de passe</label>
                        <input class="input" id="password" type="password" name="password" required>
                        @error('password') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <label style="display:flex; gap:10px; align-items:center; font-weight:800">
                        <input type="checkbox" name="remember" value="1">
                        Rester connecté
                    </label>
                    <button class="btn btn-primary" type="submit">Se connecter</button>
                </form>

                <p class="muted" style="margin-top: 18px">Pas encore de compte ? <a href="{{ route('register') }}" style="color:#111; font-weight:900">Créer un compte</a></p>
            </div>
        </div>
    </section>
@endsection

