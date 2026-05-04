@extends('layouts.app', ['title' => 'Inscription - Camping Vibes'])

@section('content')
    <section class="auth-wrap">
        <div class="shell">
            <div class="auth-card">
                <span class="eyebrow">Nouveau client</span>
                <h1>Créer un compte</h1>
                <p class="muted">Commandez plus vite et suivez vos achats depuis votre espace personnel.</p>

                <form class="stack" method="POST" action="{{ route('register.store') }}">
                    @csrf
                    <div class="form-field">
                        <label for="name">Nom complet</label>
                        <input class="input" id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-field">
                        <label for="email">E-mail</label>
                        <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required>
                        @error('email') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-field">
                        <label for="password">Mot de passe</label>
                        <input class="input" id="password" type="password" name="password" required>
                        @error('password') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-field">
                        <label for="password_confirmation">Confirmer le mot de passe</label>
                        <input class="input" id="password_confirmation" type="password" name="password_confirmation" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Créer mon compte</button>
                </form>

                <p class="muted" style="margin-top: 18px">Déjà client ? <a href="{{ route('login') }}" style="color:#111; font-weight:900">Se connecter</a></p>
            </div>
        </div>
    </section>
@endsection

