<?php

declare(strict_types=1);

namespace App\Bot\Handlers;

use App\Bot\Callbacks\CallbackAction;
use App\Bot\Callbacks\ParsedCallback;
use App\Bot\Cards\RequestCard;
use App\Bot\Client\MaxApiException;
use App\Bot\Client\MaxClient;
use App\Bot\Fsm\SessionStore;
use App\Bot\Models\OutboxMessage;
use App\Bot\Outbox\Events\OutboxMessageSent;
use App\Bot\Outbox\OutboxStatus;
use App\Bot\Updates\Update;
use App\Domain\Requests\Dto\Actor;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Exceptions\DomainException;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

final class CallbackHandler
{
    public function __construct(
        private readonly BotContext $ctx,
        private readonly MaxClient $client,
        private readonly RequestService $requests,
        private readonly RequestCard $card,
        private readonly ReportFlowHandler $report,
        private readonly SessionStore $sessions,
        private readonly ChatHandler $chat,
        private readonly WhoHandler $who,
    ) {}

    public function handle(Update $update, User $user): void
    {
        $parsed = CallbackAction::parse($update->callbackPayload() ?? '');
        if ($parsed?->action === CallbackAction::Join) {
            $this->chat->join($update, $parsed, $user);

            return;
        }

        $inDialog = $update->isPrivate() && $user->max_user_id !== null;
        if ($inDialog) {
            $this->ctx->outbox()->captureNextForUser((int) $user->max_user_id, $update->messageId());
        } else {
            $this->acknowledge($update);
        }

        try {
            if ($parsed === null) {
                $this->ctx->reply($user, 'fallback.hint');

                return;
            }
            $this->route($parsed, $user);
        } catch (DomainException $e) {
            $this->ctx->reply($user, 'error.action', ['message' => $e->getMessage()]);
        } finally {
            if ($inDialog) {
                $this->answerWith($update, $this->ctx->outbox()->takeCaptured());
            }
        }
    }

    private function answerWith(Update $update, ?OutboxMessage $message): void
    {
        if ($message === null) {
            $this->acknowledge($update);

            return;
        }
        $callbackId = $update->callbackId();
        if ($callbackId === null || ! $this->client->isConfigured()) {
            $this->ctx->outbox()->dispatch($message);

            return;
        }
        try {
            $this->client->answerCallback($callbackId, null, $message->body);
        } catch (MaxApiException $e) {
            Log::info('bot.answer_fallback', ['outbox_id' => $message->id, 'error' => $e->getMessage()]);
            $this->ctx->outbox()->dispatch($message);

            return;
        }
        $message->update([
            'status' => OutboxStatus::Sent,
            'sent_at' => CarbonImmutable::now(),
            'attempts' => 1,
            'max_message_id' => $message->edit_message_id,
        ]);
        event(new OutboxMessageSent($message));
    }

    private function route(ParsedCallback $callback, User $user): void
    {
        match ($callback->action) {
            CallbackAction::Menu => $this->menu($user),
            CallbackAction::Cabinet => $this->ctx->reply($user, 'menu.cabinet'),
            CallbackAction::Contacts => $this->contacts($user),
            CallbackAction::My => $this->myRequests($user),
            CallbackAction::Status => $this->status($user, $callback->intArg(0)),
            CallbackAction::Resolved => $this->confirm($user, $callback->intArg(0), true),
            CallbackAction::NotResolved => $this->confirm($user, $callback->intArg(0), false),
            CallbackAction::Rate => $this->rate($user, $callback->intArg(0), $callback->intArg(1)),
            CallbackAction::Report => $this->report->start($user),
            CallbackAction::Again => $this->report->start($user, $callback->intArg(0)),
            CallbackAction::Emergency, CallbackAction::Category, CallbackAction::Subcategory, CallbackAction::House,
            CallbackAction::Address, CallbackAction::Send, CallbackAction::Unsure, CallbackAction::Cancel => $this->report->callback($callback, $user),
            CallbackAction::Join => null,
            CallbackAction::Who => $this->who->handle($user, $callback->intArg(0)),
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

    private function menu(User $user): void
    {
        $this->sessions->reset((int) $user->max_user_id);
        $this->ctx->reply($user, $user->isStaff() ? 'menu.cabinet' : 'menu.main');
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
        $this->ctx->replyWith($user, 'my.list', [], $rows);
    }

    private function status(User $user, ?int $id): void
    {
        $request = $this->ownRequest($user, $id);
        if ($request === null) {
            return;
        }
        $rows = $request->status === RequestStatus::Done && $request->resident_user_id === $user->id
            ? $this->ctx->texts()->buttons('request.status_confirm', ['number' => $request->id])
            : [];
        $this->ctx->replyWith($user, 'request.status_card', [
            'card' => trim($this->card->created($request)),
            'status' => $request->status->label(),
        ], $rows);
    }

    private function confirm(User $user, ?int $id, bool $resolved): void
    {
        $request = $this->ownRequest($user, $id);
        if ($request === null) {
            return;
        }
        if ($request->resident_user_id !== $user->id) {
            $this->ctx->reply($user, 'request.author_only', ['number' => $request->id]);

            return;
        }
        if ($request->status === RequestStatus::Confirmed) {
            $this->ctx->reply($user, 'request.already_confirmed', ['number' => $request->id]);

            return;
        }
        if ($resolved) {
            $this->requests->confirm($request, Actor::resident($user));
        } else {
            $this->requests->returnToWork($request, Actor::resident($user), 'Житель: проблема не решена');
        }
    }

    private function rate(User $user, ?int $id, ?int $rating): void
    {
        $request = $this->ownRequest($user, $id);
        if ($request === null) {
            return;
        }
        if ($rating === null) {
            $rows = [array_map(fn (int $n) => ['label' => str_repeat('★', $n), 'action' => CallbackAction::Rate->payload($request->id, $n)], range(1, 5))];
            $this->ctx->replyWith($user, 'rate.ask', ['number' => $request->id], $rows);

            return;
        }
        $this->requests->rate($request, $user, $rating);
        $this->ctx->reply($user, 'rate.thanks');
    }

    private function ownRequest(User $user, ?int $id): ?ServiceRequest
    {
        $request = $id === null ? null : ServiceRequest::query()->with(['category', 'house.region'])->find($id);
        if ($request === null || ($request->resident_user_id !== $user->id && ! $request->participants()->where('user_id', $user->id)->exists())) {
            $this->ctx->reply($user, 'request.missing');

            return null;
        }

        return $request;
    }
}
