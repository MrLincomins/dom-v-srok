<?php

declare(strict_types=1);

namespace App\Domain\Users;

use App\Domain\Users\Enums\Role;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

final class UserService
{
    /**
     * пользователь из маха по max_user_id, имя обновляем, роль только из базы
     *
     * @param  array<string,mixed>  $maxUser  объект User из Bot API или initData (user_id/id, first_name, last_name, username)
     */
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
        if ($user->anonymized_at === null && $name !== '') {
            $user->name = $name;
        }
        $user->name = $user->name !== '' ? $user->name : 'Житель';
        $user->username = $maxUser['username'] ?? $user->username;
        if ($startedBot && $user->bot_started_at === null) {
            $user->bot_started_at = CarbonImmutable::now();
        }
        $user->save();

        return $user;
    }

    /** удаление по запросу - обезличиваем, заявки остаются в журнале */
    public function anonymize(User $user): void
    {
        $user->name = 'Житель (удалён)';
        $user->username = null;
        $user->phone = null;
        $user->flat = null;
        $user->entrance = null;
        $user->anonymized_at = CarbonImmutable::now();
        $user->save();

        foreach ($user->requests()->with('attachments')->get() as $request) {
            foreach ($request->attachments as $attachment) {
                if ($attachment->path !== '') {
                    Storage::disk($attachment->disk)->delete($attachment->path);
                }
                $attachment->delete();
            }
        }
    }
}
