<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>{{ config('app.name') }}</title>
    <style>
        body { margin: 0; font-family: -apple-system, 'SF Pro', Roboto, 'Segoe UI', sans-serif; color: #16181d; background: #f5f6f8; }
        main { max-width: 640px; margin: 0 auto; padding: 48px 16px; }
        h1 { font-size: 28px; margin: 0 0 12px; }
        p { font-size: 17px; line-height: 1.5; margin: 0 0 16px; }
        a { color: #2d5bff; }
        .demo { padding: 12px 16px; background: #fff; border-radius: 12px; }
    </style>
</head>
<body>
<main>
    <h1>{{ config('app.name') }}</h1>
    <p>Бот и мини-приложение в MAX: заявка жителя сразу получает ответственного и срок по нормативу, а ТСЖ и малая УК — очередь с таймером и журнал.</p>
    @if (config('max.bot_username'))
        <p>Бот: <a href="https://max.ru/{{ config('max.bot_username') }}">max.ru/{{ config('max.bot_username') }}</a></p>
    @endif
    <p class="demo">Порядок проверки и тестовые учётные записи — в README репозитория и на служебном слайде презентации.</p>
</main>
</body>
</html>
