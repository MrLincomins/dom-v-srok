<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Requests\Models\ServiceRequest;
use App\Policies\RequestPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // политики руками, модели не в App\Models и автопоиск их не найдёт
        Gate::policy(ServiceRequest::class, RequestPolicy::class);

        // время immutable, чтобы addHours не портил исходный объект
        Date::use(CarbonImmutable::class);

        // ловим опечатки в атрибутах и молча выкинутые поля, только не в проде
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());

        // апи: 120 запросов в минуту на токен или ip
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->getAuthIdentifier() ?: (string) $request->ip()));

        // тестовый вход, защита от перебора
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(30)->by((string) $request->ip()));

        // лимиты маха: 2 сообщения в сек на диалог, ~30 запросов в сек всего
        RateLimiter::for('max-target', fn (object $job) => Limit::perSecond((int) config('max.rate_per_target'))
            ->by(method_exists($job, 'targetKey') ? $job->targetKey() : 'global'));
        RateLimiter::for('max-global', fn () => Limit::perSecond((int) config('max.rate_global')));
    }
}
