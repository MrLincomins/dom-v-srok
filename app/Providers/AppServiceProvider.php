<?php

declare(strict_types=1);

namespace App\Providers;

use App\Bot\Outbox\OutboxService;
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
        $this->app->singleton(OutboxService::class);
    }

    public function boot(): void
    {
        Gate::policy(ServiceRequest::class, RequestPolicy::class);

        Date::use(CarbonImmutable::class);

        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->getAuthIdentifier() ?: (string) $request->ip()));

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(30)->by((string) $request->ip()));

        RateLimiter::for('demo', fn (Request $request) => Limit::perMinute((int) config('demo.reset_per_minute'))
            ->by($request->user()?->getAuthIdentifier() ?: (string) $request->ip()));

        RateLimiter::for('max-target', fn (object $job) => Limit::perSecond((int) config('max.rate_per_target'))
            ->by(method_exists($job, 'targetKey') ? $job->targetKey() : 'global'));
        RateLimiter::for('max-global', fn () => Limit::perSecond((int) config('max.rate_global')));
    }
}
