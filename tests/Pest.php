<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/** запрос от другого пользователя в том же тесте. guard санктума кэширует юзера, перед сменой токена сбрасываем */
function asToken(string $token): TestCase
{
    app('auth')->forgetGuards();

    return test()->withToken($token);
}
