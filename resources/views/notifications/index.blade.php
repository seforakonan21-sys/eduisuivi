@extends('layouts.app')
@section('titre', 'Notifications')

@section('contenu')
<div class="carte">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px">
        <h2 style="margin:0">Notifications</h2>
        @if(auth()->user()->unreadNotifications()->count())
            <form method="POST" action="{{ route('notifications.lues') }}">
                @csrf
                <button class="btn btn-noir" type="submit">Tout marquer comme lu</button>
            </form>
        @endif
    </div>

    @include('structure._messages')

    @forelse($notifications as $n)
        <div style="padding:12px 0; border-bottom:1px solid var(--bordure); {{ $n->read_at ? '' : 'font-weight:600' }}">
            @unless($n->read_at)<span class="badge" style="margin-right:6px">Nouveau</span>@endunless
            {{ $n->data['titre'] ?? 'Notification' }}
            <div style="font-weight:400; color:var(--discret)">{{ $n->data['message'] ?? '' }}</div>
            <small style="color:var(--discret); font-weight:400">{{ $n->created_at->format('d/m/Y à H:i') }}</small>
        </div>
    @empty
        <p>Aucune notification pour le moment.</p>
    @endforelse
</div>
@endsection