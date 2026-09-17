<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Users\Enums\Role;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** использование: role:dispatcher или role:dispatcher,admin */
final class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowed = array_map(static fn (string $r) => Role::from($r), $roles);

        if ($user === null || ! in_array($user->role, $allowed, true)) {
            throw new AuthorizationException('Действие доступно только роли: '.implode(', ', $roles));
        }

        return $next($request);
    }
}
