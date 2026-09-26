<?php

declare(strict_types=1);

namespace App\Domain\Users;

use App\Domain\Requests\Enums\AttachmentKind;
use App\Domain\Requests\Models\Attachment;
use App\Domain\Users\Enums\Role;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class UserService
{
    private const FALLBACK_NAME = 'Житель';

    private const ANONYMIZED_NAME = 'Житель (удалён)';

    /** @param  array<string,mixed>  $maxUser */
    public function upsertFromMax(array $maxUser, bool $startedBot = false): User
    {
        $maxUserId = (int) ($maxUser['user_id'] ?? $maxUser['id'] ?? 0);
        if ($maxUserId <= 0) {
            throw new \InvalidArgumentException('Нет идентификатора пользователя MAX');
        }

        $name = trim(($maxUser['first_name'] ?? '').' '.($maxUser['last_name'] ?? ''));

        $user = User::query()->firstOrNew(['max_user_id' => $maxUserId]);
        if (! $user->exists) {
            $user->role = Role::Resident;
        }
        if ($user->anonymized_at === null) {
            if ($name !== '') {
                $user->name = $name;
            }
            $user->username = $maxUser['username'] ?? $user->username;
        }
        if (($user->name ?? '') === '') {
            $user->name = self::FALLBACK_NAME;
        }
        if ($startedBot && $user->bot_started_at === null) {
            $user->bot_started_at = CarbonImmutable::now();
        }
        $user->save();

        return $user;
    }

    public function stopBot(int $maxUserId): void
    {
        User::query()->where('max_user_id', $maxUserId)->update(['bot_started_at' => null]);
    }

    public function anonymize(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->name = self::ANONYMIZED_NAME;
            $user->username = null;
            $user->phone = null;
            $user->flat = null;
            $user->entrance = null;
            $user->bot_started_at = null;
            $user->anonymized_at = CarbonImmutable::now();
            $user->save();

            $user->tokens()->delete();

            $attachments = Attachment::query()
                ->where('kind', AttachmentKind::Resident->value)
                ->where(fn (Builder $query) => $query
                    ->whereIn('request_id', $user->requests()->select('id'))
                    ->orWhere('uploaded_by', $user->id))
                ->lockForUpdate()
                ->get();

            Attachment::query()->whereKey($attachments->modelKeys())->delete();

            DB::afterCommit(function () use ($attachments): void {
                foreach ($attachments as $attachment) {
                    if ($attachment->path !== '') {
                        Storage::disk($attachment->disk)->delete($attachment->path);
                    }
                }
            });
        });
    }
}
