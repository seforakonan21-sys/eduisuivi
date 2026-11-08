<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre', 'EduSuivi')</title>
    
    <style>
        :root { --rouge:#C8102E; --noir:#111111; --blanc:#ffffff; --gris:#f4f4f5; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: system-ui, sans-serif; background:var(--gris); color:var(--noir); }
        header { background:var(--noir); color:var(--blanc); padding:14px 24px; display:flex;
                 justify-content:space-between; align-items:center; border-bottom:4px solid var(--rouge); }
        header strong span { color:var(--rouge); }
        main { max-width:960px; margin:32px auto; padding:0 16px; }
        .carte { background:var(--blanc); border-radius:10px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,.08); }
        label { display:block; margin:14px 0 6px; font-weight:600; }
        input[type=email], input[type=password] { width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; }
        .btn { background:var(--rouge); color:var(--blanc); border:0; padding:10px 18px; border-radius:6px;
               cursor:pointer; font-weight:600; }
        .btn-noir { background:var(--noir); }
        .erreur { color:var(--rouge); font-size:.9rem; margin-top:6px; }
        .badge { background:var(--rouge); color:var(--blanc); padding:3px 10px; border-radius:20px; font-size:.8rem; }

        .photo { width:90px; height:90px; border-radius:50%; object-fit:cover; background:#ddd; }
        .alerte { background:#fff8e1; border-left:4px solid #f9a825; padding:12px; margin-bottom:14px; }
        input[type=file] { width:100%; }
        a.btn { display:inline-block; text-decoration:none; }
    </style>
</head>
<body>
    @auth
    <header>
        <strong>Edu<span>Suivi</span></strong>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <span style="margin-right:12px">{{ auth()->user()->name }}</span>
            <button class="btn" type="submit">Déconnexion</button>
        </form>
    </header>
    @endauth
    <main>@yield('contenu')</main>
</body>
</html>