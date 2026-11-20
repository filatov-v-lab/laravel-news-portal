<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Сайт') — Новостной портал</title>
    {{-- Vue + Echo. Подключается, только если выполнена сборка: npm run build --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/js/app.js')
    @endif
    <style>
        :root {
            --bg: #f4f1ea;
            --paper: #fffdf8;
            --ink: #1f1b16;
            --muted: #6b6258;
            --accent: #2b4c7e;
            --accent-hover: #1f3a63;
            --line: #e4dcd0;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { color: var(--accent-hover); text-decoration: underline; }
        .site-header, .site-footer {
            background: var(--paper);
            border-bottom: 1px solid var(--line);
        }
        .site-footer { border-bottom: 0; border-top: 1px solid var(--line); margin-top: auto; }
        .container { width: min(960px, calc(100% - 32px)); margin: 0 auto; }
        .site-header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 0;
        }
        .logo { font-weight: 700; color: var(--ink); letter-spacing: .02em; }
        nav { display: flex; gap: 8px; flex-wrap: wrap; }
        nav a {
            padding: 8px 12px;
            border-radius: 8px;
            color: var(--ink);
            text-decoration: none;
        }
        nav a:hover, nav a.active {
            background: #e8eef7;
            color: var(--accent);
            text-decoration: none;
        }
        main { padding: 32px 0 48px; }
        .card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 8px 24px rgba(31, 27, 22, .04);
        }
        h1 { margin: 0 0 12px; font-size: 2rem; }
        p { line-height: 1.6; color: var(--muted); }
        .contacts { list-style: none; padding: 0; margin: 20px 0 0; }
        .contacts li {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 12px;
            padding: 12px 0;
            border-top: 1px solid var(--line);
        }
        .contacts strong { color: var(--ink); }
        .site-footer .container { padding: 18px 0; color: var(--muted); font-size: .95rem; }
        @media (max-width: 640px) {
            .contacts li { grid-template-columns: 1fr; gap: 4px; }
        }
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }
        .news-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }
        .news-card img { width: 100%; height: 180px; object-fit: cover; display: block; }
        .news-body { padding: 14px; }
        .news-body time { color: var(--muted); font-size: .85rem; }
        .news-body h2 { margin: 6px 0 8px; font-size: 1.1rem; color: var(--ink); }
        .full-image { width: 100%; max-height: 520px; object-fit: cover; border-radius: 12px; margin: 16px 0; }
        .auth-form { display: grid; gap: 14px; max-width: 420px; margin-top: 20px; }
        .auth-form label { display: grid; gap: 6px; color: var(--ink); font-weight: 600; }
        .auth-form input {
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            font: inherit;
        }
        .auth-form button {
            margin-top: 8px;
            padding: 10px 14px;
            border: 0;
            border-radius: 8px;
            background: var(--accent);
            color: #fff;
            font: inherit;
            cursor: pointer;
        }
        .auth-form button:hover { background: var(--accent-hover); }
        .error { color: #b42318; font-weight: 400; font-size: .9rem; }
        .article-form { max-width: 640px; }
        .article-form textarea {
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            font: inherit;
            resize: vertical;
        }
        .btn {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 8px;
            background: var(--accent);
            color: #fff !important;
            text-decoration: none !important;
            border: 0;
            font: inherit;
            cursor: pointer;
        }
        .btn-danger { background: #b42318; }
        .crud-actions { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .crud-actions form { margin: 0; }
        .linkish {
            background: none;
            border: 0;
            padding: 0;
            color: #b42318;
            cursor: pointer;
            font: inherit;
            text-decoration: underline;
        }
        .flash {
            background: #e8eef7;
            border: 1px solid #c9d7ec;
            color: var(--accent);
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .pagination-wrap { margin-top: 24px; }
        .pagination-wrap nav, .pagination-wrap ul { display: flex; gap: 8px; flex-wrap: wrap; list-style: none; padding: 0; }
        .notifications { position: relative; }
        .notifications summary { cursor: pointer; list-style: none; padding: 8px 12px; border-radius: 8px; }
        .notifications summary::-webkit-details-marker { display: none; }
        .notifications .badge { background: #c0392b; color: #fff; border-radius: 999px; padding: 1px 7px; font-size: .8rem; margin-left: 4px; }
        .notifications ul { position: absolute; right: 0; top: 100%; z-index: 50; min-width: 260px; margin: 6px 0 0; padding: 8px; list-style: none; background: var(--paper); border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.12); }
        .notifications li a { display: block; padding: 8px 10px; border-radius: 6px; }
        .notifications li.empty { padding: 8px 10px; color: var(--muted); }
        .pagination-wrap .current { background: var(--accent); color: #fff; border-color: var(--accent); }
        .pagination-wrap a, .pagination-wrap span { padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line); }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <a class="logo" href="{{ route('home') }}">Новостной портал</a>
            <nav>
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Главная</a>
                <a href="{{ route('articles.index') }}" class="{{ request()->routeIs('articles.*') ? 'active' : '' }}">Все статьи</a>
                @can('create', App\Models\Article::class)
                    <a href="{{ route('articles.create') }}" class="{{ request()->routeIs('articles.create') ? 'active' : '' }}">Создать новость</a>
                @endcan
                @can('moderate', App\Models\Comment::class)
                    <a href="{{ route('comments.moderate') }}" class="{{ request()->routeIs('comments.moderate') ? 'active' : '' }}">Модерация</a>
                @endcan
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">О нас</a>
                <a href="{{ route('contacts') }}" class="{{ request()->routeIs('contacts') ? 'active' : '' }}">Контакты</a>
                @auth
                    @php($unread = auth()->user()->unreadNotifications)
                    <details class="notifications">
                        <summary>
                            Уведомления
                            @if ($unread->count() > 0)
                                <span class="badge">{{ $unread->count() }}</span>
                            @endif
                        </summary>
                        <ul>
                            @forelse ($unread as $notification)
                                <li>
                                    <a href="{{ route('articles.show', ['article' => $notification->data['article_id'], 'notification' => $notification->id]) }}">
                                        {{ $notification->data['name'] }}
                                    </a>
                                </li>
                            @empty
                                <li class="empty">Новых уведомлений нет</li>
                            @endforelse
                        </ul>
                    </details>
                    <span>{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="linkish">Выход</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'active' : '' }}">Вход</a>
                    <a href="{{ route('signin') }}" class="{{ request()->routeIs('signin') ? 'active' : '' }}">Регистрация</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        {{-- сюда Vue монтирует push-уведомление о новой статье --}}
        <div id="app"></div>

        <div class="container">
            <div class="card">
                @if (session('success'))
                    <div class="flash">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="flash" style="background:#fdecea;color:#7f1d1d;border-color:#f5c2c7;">{{ session('error') }}</div>
                @endif
                @yield('content')
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            Филатов Владимир Андреевич, 257-321
        </div>
    </footer>
</body>
</html>
