<?php

declare(strict_types=1);

namespace App\Bot\Handlers;

use App\Bot\Fsm\SessionStore;
use App\Bot\Updates\Update;
use App\Domain\Demo\DemoResetService;
use App\Domain\Organizations\AccessCodeService;
use App\Domain\Users\Models\User;
use App\Domain\Users\UserService;

final class CommandHandler
{
    public function __construct(
        private readonly BotContext $ctx,
        private readonly StartHandler $start,
        private readonly ReportFlowHandler $report,
        private readonly FallbackHandler $fallback,
        private readonly AccessCodeService $accessCodes,
        private readonly UserService $users,
        private readonly DemoResetService $demoReset,
        private readonly SessionStore $sessions,
    ) {}

    public function handle(Update $update, User $user): void
    {
        $text = $update->text() ?? '';
        [$command, $argument] = $this->split($text);

        match ($command) {
            '/start' => $this->start->handle($update, $user, $argument),
            '/menu', 'меню' => $this->menu($user),
            '/dispatcher', '/диспетчер' => $this->dispatcher($user, $argument),
            '/delete_me' => $this->deleteMe($user),
            '/demo_reset' => $this->demoReset($user),
            default => $this->free($update, $user),
        };
    }

    /** @return array{0:string,1:string|null} */
    private function split(string $text): array
    {
        $parts = preg_split('/\s+/', trim($text), 2) ?: [''];
        $command = mb_strtolower($parts[0]);
        $argument = isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : null;

        return [$command, $argument];
    }

    private function free(Update $update, User $user): void
    {
        if (! $this->report->text($update, $user)) {
            $this->fallback->handle($user);
        }
    }

    private function menu(User $user): void
    {
        $this->sessions->reset((int) $user->max_user_id);
        $this->ctx->reply($user, $user->isStaff() ? 'menu.cabinet' : 'menu.main');
    }

    private function dispatcher(User $user, ?string $code): void
    {
        $organization = $code === null ? null : $this->accessCodes->redeem($user, $code);
        if ($organization === null) {
            $this->ctx->reply($user, 'dispatcher.invalid_code');

            return;
        }
        $this->ctx->reply($user, 'dispatcher.granted', ['name' => $organization->name]);
    }

    private function deleteMe(User $user): void
    {
        $this->sessions->reset((int) $user->max_user_id);
        $this->users->anonymize($user);
        $this->ctx->reply($user, 'delete.done');
    }

    private function demoReset(User $user): void
    {
        $organization = $user->organization;
        if (! $user->isStaff() || $organization === null || ! $organization->is_demo) {
            $this->fallback->handle($user);

            return;
        }
        $this->demoReset->reset();
        $this->ctx->reply($user, 'demo.reset_done');
    }
}
