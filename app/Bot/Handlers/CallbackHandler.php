<?php

declare(strict_types=1);

namespace App\Bot\Handlers;

use App\Bot\Callbacks\CallbackAction;
use App\Bot\Callbacks\ParsedCallback;
use App\Bot\Cards\RequestCard;
use App\Bot\Client\MaxApiException;
use App\Bot\Client\MaxClient;
use App\Bot\Updates\Update;
use App\Domain\Requests\Dto\Actor;
use App\Domain\Requests\Enums\ConfirmedBy;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Exceptions\DomainException;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * кнопки работают всегда, независимо от состояния диалога.
 * на callback сразу answerCallback, чтобы кнопки не висели.
 * сценарии заявки (report, em, cat, sub, addr, send).
 */
final class CallbackHandler
{
    public function __construct(
        private readonly BotContext $ctx,
        private readonly MaxClient $client,
        private readonly RequestService $requests,
        private readonly RequestCard $card,
    ) {}

    public function handle(Update $update, User $user): void
    {
        $parsed = CallbackAction::parse($update->callbackPayload() ?? '');
        $this->acknowledge($update);

        if ($parsed === null) {
            $this->ctx->reply($user, 'fallback.hint');

            return;
        }

        try {
            $this->route($parsed, $user);
        } catch (DomainException $e) {
            $this->ctx->replyRaw($user, $e->getMessage(), [[['label' => 'Меню', 'action' => 'menu']]]);
        }
    }

    private function route(ParsedCallback $callback, User $user): void
    {
        match ($callback->action) {
            CallbackAction::Menu => $this->ctx->reply($user, $user->isStaff() ? 'menu.cabinet' : 'menu.main'),
            CallbackAction::Cabinet => $this->ctx->reply($user, 'menu.cabinet'),
            CallbackAction::Contacts => $this->contacts($user),
            CallbackAction::My => $this->myRequests($user),
            CallbackAction::Status => $this->status($user, $callback->intArg(0)),
            CallbackAction::Resolved => $this->confirm($user, $callback->intArg(0), true),
            CallbackAction::NotResolved => $this->confirm($user, $callback->intArg(0), false),
            CallbackAction::Rate => $this->rate($user, $callback->intArg(0), $callback->intArg(1)),
            default => $this->ctx->reply($user, 'wip'),
        };
    }

    private function acknowledge(Update $update): void
    {
        $callbackId = $update->callbackId();
        if ($callbackId === null || ! $this->client->isConfigured()) {
            return;
        }
        try {
            $this->client->answerCallback($callbackId);
        } catch (MaxApiException $e) {
            Log::info('bot.answer_callback_failed', ['error' => $e->getMessage()]);
        }
    }

    private function contacts(User $user): void
    {
        $organization = $user->house->organization ?? $user->organization;
        if ($organization === null) {
            $this->ctx->reply($user, 'contacts.none');

            return;
        }
        $this->ctx->reply($user, 'contacts.card', [
            'name' => $organization->name,
            'phone_ads' => $organization->phone_ads,
            'phone_dispatch' => $organization->phone_dispatch ?? '—',
            'reception' => trim(($organization->reception_hours ?? '').', '.($organization->reception_address ?? ''), ', ') ?: '—',
            'email' => $organization->email ?? '',
        ]);
    }

    private function myRequests(User $user): void
    {
        $requests = $user->requests()->with('category')->latest('id')->limit(5)->get();
        if ($requests->isEmpty()) {
            $this->ctx->reply($user, 'my.empty');

            return;
        }
        $rows = $requests->map(fn (ServiceRequest $r) => [[
            'label' => sprintf('№ %d · %s · %s', $r->id, $r->category->name, $r->status->label()),
            'action' => CallbackAction::Status->payload($r->id),
        ]])->all();
        $this->ctx->replyRaw($user, $this->ctx->texts()->text('my.list'), $rows);
    }

    private function status(User $user, ?int $id): void
    {
        $request = $this->ownRequest($user, $id);
        if ($request === null) {
            return;
        }
        $rows = $request->status === RequestStatus::Done
            ? [[['label' => 'Да, решено', 'action' => CallbackAction::Resolved->payload($request->id)], ['label' => 'Нет, не решено', 'action' => CallbackAction::NotResolved->payload($request->id)]]]
            : [];
        $this->ctx->replyRaw($user, $this->card->created($request)."\nСтатус: ".$request->status->label(), $rows);
    }

    private function confirm(User $user, ?int $id, bool $resolved): void
    {
        $request = $this->ownRequest($user, $id);
        if ($request === null) {
            return;
        }
        if ($resolved) {
            $this->requests->confirm($request, Actor::resident($user), ConfirmedBy::Resident);
        } else {
            $this->requests->returnToWork($request, Actor::resident($user), 'Житель: проблема не решена');
        }
        // сообщения о новом статусе из NotifyResidentOfRequestChange.
    }

    private function rate(User $user, ?int $id, ?int $rating): void
    {
        $request = $this->ownRequest($user, $id);
        if ($request === null) {
            return;
        }
        if ($rating === null) {
            $rows = [array_map(fn (int $n) => ['label' => str_repeat('★', $n), 'action' => CallbackAction::Rate->payload($request->id, $n)], range(1, 5))];
            $this->ctx->replyRaw($user, 'Оцените, как решили заявку № '.$request->id, $rows);

            return;
        }
        $this->requests->rate($request, $user, $rating);
        $this->ctx->replyRaw($user, 'Спасибо за оценку.');
    }

    private function ownRequest(User $user, ?int $id): ?ServiceRequest
    {
        $request = $id === null ? null : ServiceRequest::query()->with(['category', 'house.region'])->find($id);
        if ($request === null || ($request->resident_user_id !== $user->id && ! $request->participants()->where('user_id', $user->id)->exists())) {
            $this->ctx->replyRaw($user, 'Такой заявки у вас нет.', [[['label' => 'Мои заявки', 'action' => 'my']]]);

            return null;
        }

        return $request;
    }
}
