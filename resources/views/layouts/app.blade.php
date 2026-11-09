<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre', 'EduSuivi')</title>
    <style>
        :root {
            --rouge:#C8102E; --rouge-fonce:#9E0C24; --rouge-pale:#FDECEF;
            --noir:#111111; --noir-doux:#1E1E1E;
            --fond:#F4F5F7; --bordure:#E3E5E8; --texte:#1F2328; --discret:#6B7280; --blanc:#FFFFFF;
        }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Segoe UI", system-ui, -apple-system, Arial, sans-serif;
               font-size:15px; line-height:1.5; background:var(--fond); color:var(--texte); }
        a { color:var(--rouge); }
        h1, h2, h3 { margin:0 0 12px; line-height:1.25; }

        /* Menu latéral */
        .lateral { position:fixed; inset:0 auto 0 0; width:250px; background:var(--noir); color:var(--blanc);
                   display:flex; flex-direction:column; overflow-y:auto; border-right:4px solid var(--rouge); }
        .logo { padding:22px 20px; font-size:1.5rem; font-weight:800; letter-spacing:.3px; }
        .logo span { color:#FF5A6E; }
        .lateral nav { flex:1; padding:0 12px 12px; }
        .lateral nav a { display:block; padding:10px 14px; margin-bottom:2px; border-radius:8px;
                         color:#D1D5DB; text-decoration:none; font-weight:500; }
        .lateral nav a:hover { background:var(--noir-doux); color:var(--blanc); }
        .lateral nav a.actif { background:var(--rouge); color:var(--blanc); font-weight:600; }
        .groupe { margin:18px 14px 6px; font-size:.72rem; text-transform:uppercase;
                  letter-spacing:.08em; color:#8B8F97; }
        .utilisateur { padding:16px; border-top:1px solid #2A2A2A; font-size:.9rem; }
        .utilisateur small { display:block; color:#9CA3AF; margin-bottom:10px; }

        /* Zone principale */
        .avec-menu .principal { margin-left:250px; }
        .haut { background:var(--blanc); border-bottom:1px solid var(--bordure); padding:16px 28px;
                display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
        .haut h1 { margin:0; font-size:1.15rem; }
        .haut .etab { color:var(--discret); font-size:.9rem; }
        main { max-width:1100px; margin:0 auto; padding:28px; }
        .sans-menu main { max-width:460px; padding-top:70px; }

        /* Cartes et indicateurs */
        .carte { background:var(--blanc); border:1px solid var(--bordure); border-radius:12px; padding:24px;
                 box-shadow:0 1px 3px rgba(0,0,0,.05); margin-bottom:20px; overflow-x:auto; }
        .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:20px; }
        .stat { background:var(--blanc); border:1px solid var(--bordure); border-top:4px solid var(--rouge);
                border-radius:12px; padding:18px; }
        .stat .valeur { font-size:2rem; font-weight:800; line-height:1.1; }
        .stat .libelle { color:var(--discret); font-size:.9rem; margin-top:4px; }

        /* Formulaires */
        label { display:block; margin:0 0 6px; font-weight:600; font-size:.88rem; }
        input[type=text], input[type=email], input[type=password], input[type=date],
        input[type=number], input[type=file], select {
            width:100%; padding:10px 12px; border:1px solid #CBD0D6; border-radius:8px;
            background:var(--blanc); font:inherit; color:var(--texte); }
        input:focus, select:focus { outline:2px solid var(--rouge); outline-offset:1px; border-color:var(--rouge); }
        .grille { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;
                  align-items:end; margin-bottom:22px; }

        /* Boutons */
        .btn { display:inline-block; background:var(--rouge); color:var(--blanc); border:0; padding:10px 18px;
               border-radius:8px; cursor:pointer; font:inherit; font-weight:600; text-decoration:none; }
        .btn:hover { background:var(--rouge-fonce); }
        .btn-noir { background:var(--noir); }
        .btn-noir:hover { background:#333; }
        .btn-clair { background:transparent; border:1px solid #444; color:var(--blanc); }
        .btn-clair:hover { background:var(--noir-doux); }
        table .btn { padding:6px 12px; font-size:.85rem; }

        /* Tableaux */
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:12px 10px; border-bottom:1px solid var(--bordure); }
        th { background:#F9FAFB; color:var(--discret); font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; }
        tr:hover td { background:#FCFCFD; }

        /* Messages */
        .badge { display:inline-block; background:var(--rouge); color:var(--blanc); padding:3px 10px;
                 border-radius:20px; font-size:.8rem; font-weight:600; }
        .succes { background:#E8F5E9; border-left:4px solid #2E7D32; padding:10px 14px; border-radius:8px; margin-bottom:14px; }
        .erreur { background:var(--rouge-pale); border-left:4px solid var(--rouge); color:#7A0A1D;
                  padding:10px 14px; border-radius:8px; }
        .alerte { background:#FFF8E1; border-left:4px solid #F9A825; padding:12px 14px; border-radius:8px; margin-bottom:14px; }
        .photo { width:90px; height:90px; border-radius:50%; object-fit:cover; background:#DDD; }

        /* Téléphone et petites tablettes */
        @media (max-width:860px) {
            .lateral { position:static; width:100%; border-right:0; border-bottom:4px solid var(--rouge); }
            .lateral nav { display:flex; flex-wrap:wrap; gap:4px; }
            .lateral nav a { margin:0; }
            .groupe { display:none; }
            .avec-menu .principal { margin-left:0; }
            main { padding:16px; }
        }
    </style>
</head>
<body>
@php $u = auth()->user(); @endphp
<div class="app {{ $u ? 'avec-menu' : 'sans-menu' }}">

    @auth
    @php
        $actif = fn (string $motif) => request()->routeIs($motif) ? 'actif' : '';
        $gestion = $u->aLeRole(\App\Enums\Role::Directeur, \App\Enums\Role::Administratif);
    @endphp
    <aside class="lateral">
        <div class="logo">Edu<span>Suivi</span></div>

        <nav>
            <a href="{{ route('dashboard') }}" class="{{ $actif('dashboard') }}">Tableau de bord</a>

            @if($u->aLeRole(\App\Enums\Role::AdminGeneral))
                <a href="{{ route('administration') }}" class="{{ $actif('administration') }}">Administration</a>
            @endif

            @if($gestion)
                <div class="groupe">Élèves et familles</div>
                <a href="{{ route('eleves.index') }}" class="{{ $actif('eleves.*') }}">Élèves</a>
                <a href="{{ route('parents.index') }}" class="{{ $actif('parents.*') }}">Parents</a>

                <div class="groupe">Structure scolaire</div>
                <a href="{{ route('structure.annees.index') }}" class="{{ $actif('structure.annees.*') }}">Années scolaires</a>
                <a href="{{ route('structure.periodes.index') }}" class="{{ $actif('structure.periodes.*') }}">Périodes</a>
                <a href="{{ route('structure.niveaux.index') }}" class="{{ $actif('structure.niveaux.*') }}">Niveaux</a>
                <a href="{{ route('structure.classes.index') }}" class="{{ $actif('structure.classes.*') }}">Classes</a>
                <a href="{{ route('structure.matieres.index') }}" class="{{ $actif('structure.matieres.*') }}">Matières</a>
                <a href="{{ route('structure.affectations.index') }}" class="{{ $actif('structure.affectations.*') }}">Affectations</a>
            @endif
        </nav>

        <div class="utilisateur">
            <strong>{{ $u->name }}</strong>
            <small>{{ $u->role->label() }}</small>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-clair" type="submit" style="width:100%">Déconnexion</button>
            </form>
        </div>
    </aside>
    @endauth

    <div class="principal">
        @auth
        <header class="haut">
            <h1>@yield('titre', 'EduSuivi')</h1>
            <span class="etab">{{ $u->etablissement->nom ?? 'Vue globale de la plateforme' }}</span>
        </header>
        @endauth

        <main>@yield('contenu')</main>
    </div>
</div>
</body>
</html>