@extends('layouts.app')
@section('titre', 'Connexion - EduSuivi')

@section('contenu')
<div class="carte" style="max-width:420px; margin:60px auto;">
    <h1 style="margin-top:0">Edu<span style="color:var(--rouge)">Suivi</span></h1>
    <p>Connectez-vous à votre espace.</p>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <label for="email">Adresse e-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email') <div class="erreur">{{ $message }}</div> @enderror

        <label for="password">Mot de passe</label>
        <input id="password" type="password" name="password" required>

        <label style="font-weight:400"><input type="checkbox" name="remember"> Se souvenir de moi</label>

        <button class="btn" type="submit" style="margin-top:16px; width:100%">Se connecter</button>
    </form>
</div>
@endsection