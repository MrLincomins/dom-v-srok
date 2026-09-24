<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="demo-login" content="{{ config('demo.accounts_enabled') ? '1' : '0' }}">
    <meta name="app-name" content="{{ config('app.name') }}">
    <title>{{ config('app.name') }} — кабинет</title>
    {{-- Тему ставим сразу, не дожидаясь MAX: иначе страница мелькает белым. --}}
    <script>
        document.documentElement.dataset.colorScheme =
            window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        function applyMaxColorScheme() {
            var scheme = window.WebApp && window.WebApp.colorScheme;
            if (scheme === 'light' || scheme === 'dark') {
                document.documentElement.dataset.colorScheme = scheme;
            }
        }
    </script>
    {{-- В обычном браузере bridge не загрузит сессию, и приложение покажет демо-вход. --}}
    <script defer src="https://st.max.ru/js/max-web-app.js" onload="applyMaxColorScheme()"></script>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/miniapp/main.tsx'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
