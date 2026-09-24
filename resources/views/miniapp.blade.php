<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="demo-login" content="{{ config('demo.accounts_enabled') ? '1' : '0' }}">
    <meta name="app-name" content="{{ config('app.name') }}">
    <title>{{ config('app.name') }} — кабинет</title>
    {{-- В обычном браузере bridge не загрузит сессию, и приложение покажет демо-вход. --}}
    <script src="https://st.max.ru/js/max-web-app.js"></script>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/miniapp/main.tsx'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
