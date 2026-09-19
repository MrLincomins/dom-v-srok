<?php

declare(strict_types=1);

namespace App\Bot\Handlers;

use App\Bot\Callbacks\CallbackAction;
use App\Bot\Callbacks\ParsedCallback;
use App\Bot\Cards\RequestCard;
use App\Bot\Fsm\DialogState;
use App\Bot\Fsm\ReportDraft;
use App\Bot\Fsm\SessionStore;
use App\Bot\Updates\Update;
use App\Domain\Catalog\CatalogService;
use App\Domain\Catalog\DeadlineCalculator;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\ResponsibleResolver;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Enums\RequestOrigin;
use App\Domain\Requests\Exceptions\EmergencyCategory;
use App\Domain\Requests\Models\EmergencySignal;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Models\User;
use App\Jobs\DownloadMaxAttachment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/** «сообщить о проблеме»: авария? - категория - фото или текст - подъезд и квартира - карточка. черновик в bot_sessions, кнопки работают из любого состояния */
final class ReportFlowHandler
{
    private const DESCRIPTION_LIMIT = 1000;

    public function __construct(
        private readonly BotContext $ctx,
        private readonly SessionStore $sessions,
        private readonly CatalogService $catalog,
        private readonly RequestService $requests,
        private readonly ResponsibleResolver $resolver,
        private readonly DeadlineCalculator $deadlines,
        private readonly RequestCard $card,
    ) {}

    public function start(User $user, ?int $repeatOfId = null): void
    {
        $draft = new ReportDraft;
        $repeat = $repeatOfId === null ? null : ServiceRequest::query()->where('resident_user_id', $user->id)->find($repeatOfId);
        if ($repeat !== null) {
            $draft->houseId = $repeat->house_id;
            $draft->categoryId = $repeat->category_id;
            $draft->entrance = $repeat->entrance;
            $draft->flat = $repeat->flat;
            $draft->repeatOfId = $repeat->id;
            $this->askDetails($user, $draft);

            return;
        }
        $this->begin($user, $draft);
    }

    public function text(Update $update, User $user): bool
    {
        $session = $this->sessions->load((int) $user->max_user_id);
        $text = $update->text() ?? '';
        if ($session->state === DialogState::Idle) {
            return $text !== '' && $this->suggest($user, $text);
        }

        $draft = ReportDraft::fromArray($session->payload);
        $photos = $this->photos($update);
        match ($session->state) {
            DialogState::ChooseHouse => $this->searchHouse($user, $draft, $text),
            DialogState::AskEmergency => $this->ctx->reply($user, 'emergency.question'),
            DialogState::AskCategory, DialogState::AskSubcategory => $this->categoryOrSuggest($user, $draft, $text),
            DialogState::AskDetails => $this->details($user, $draft, $text, $photos),
            DialogState::AskAddress => $this->address($user, $draft, $text, $photos),
            DialogState::Preview => $this->preview($user, $draft),
        };

        return true;
    }

    public function callback(ParsedCallback $callback, User $user): void
    {
        $session = $this->sessions->load((int) $user->max_user_id);
        $draft = ReportDraft::fromArray($session->payload);

        match ($callback->action) {
            CallbackAction::Cancel => $this->cancel($user),
            CallbackAction::House => $this->chooseHouse($user, $draft, $callback->intArg(0)),
            CallbackAction::Emergency => $callback->arg(0) === 'yes' ? $this->emergency($user, $draft) : $this->askCategory($user, $draft),
            CallbackAction::Category => $this->category($user, $draft, $callback->intArg(0)),
            CallbackAction::Subcategory => $this->subcategory($user, $draft, $callback->intArg(0)),
            CallbackAction::Address => $callback->arg(0) === 'same' ? $this->sameAddress($user, $draft) : $this->newAddress($user, $draft),
            CallbackAction::Send => $this->send($user, $draft, $session->state, false),
            CallbackAction::Unsure => $this->send($user, $draft, $session->state, true),
            default => $this->ctx->reply($user, 'wip'),
        };
    }

    private function begin(User $user, ReportDraft $draft): void
    {
        $house = $user->house_id === null ? null : $user->house;
        if ($house === null) {
            $this->save($user, DialogState::ChooseHouse, $draft);
            $this->ctx->reply($user, 'report.house');

            return;
        }
        $draft->houseId = $house->id;
        if ($draft->categoryId !== null) {
            $this->askDetails($user, $draft);

            return;
        }
        $this->save($user, DialogState::AskEmergency, $draft);
        $this->ctx->reply($user, 'emergency.question');
    }

    private function suggest(User $user, string $text): bool
    {
        $found = $this->catalog->suggest($text);
        if ($found->isEmpty()) {
            return false;
        }
        $rows = $found->map(fn (Category $category) => [[
            'label' => $category->name,
            'action' => CallbackAction::Subcategory->payload($category->id),
        ]])->values()->all();
        $this->ctx->replyWith($user, 'report.suggest', [], $rows);

        return true;
    }

    private function categoryOrSuggest(User $user, ReportDraft $draft, string $text): void
    {
        if ($text === '' || ! $this->suggest($user, $text)) {
            $this->askCategory($user, $draft);
        }
    }

    private function searchHouse(User $user, ReportDraft $draft, string $text): void
    {
        $needle = trim($text);
        if (mb_strlen($needle) < 3) {
            $this->ctx->reply($user, 'report.house');

            return;
        }
        $houses = House::query()->where('address', 'ilike', '%'.$needle.'%')->orderBy('id')->limit(5)->get();
        if ($houses->isEmpty()) {
            $this->ctx->reply($user, 'report.house_not_found');

            return;
        }
        $rows = $houses->map(fn (House $house) => [[
            'label' => $house->address,
            'action' => CallbackAction::House->payload($house->id),
        ]])->values()->all();
        $this->save($user, DialogState::ChooseHouse, $draft);
        $this->ctx->replyWith($user, 'report.house_choose', [], $rows);
    }

    private function chooseHouse(User $user, ReportDraft $draft, ?int $houseId): void
    {
        $house = $houseId === null ? null : House::query()->find($houseId);
        if ($house === null) {
            $this->save($user, DialogState::ChooseHouse, $draft);
            $this->ctx->reply($user, 'report.house');

            return;
        }
        $user->house_id = $house->id;
        $user->save();
        $user->setRelation('house', $house);
        $this->begin($user, $draft);
    }

    private function emergency(User $user, ReportDraft $draft): void
    {
        $house = $draft->houseId === null ? null : House::query()->with(['organization', 'region'])->find($draft->houseId);
        $organization = $house?->organization;
        $phone = $organization?->phone_ads;

        EmergencySignal::query()->create([
            'user_id' => $user->id,
            'house_id' => $house?->id,
            'organization_id' => $organization?->id,
            'phone_shown' => $phone ?? '112',
        ]);
        $this->sessions->reset((int) $user->max_user_id);

        $timezone = $house?->region->timezone ?? 'Europe/Moscow';
        $rows = $phone === null ? [] : [[['label' => 'Скопировать номер', 'action' => 'copy:'.$phone]]];
        $this->ctx->replyWith($user, 'emergency.call', [
            'phone' => $phone ?? 'телефон на стенде в подъезде, при угрозе жизни 112',
            'time' => CarbonImmutable::now()->setTimezone($timezone)->format('H:i'),
        ], $rows);
    }

    private function askCategory(User $user, ReportDraft $draft): void
    {
        $draft->rootId = null;
        $draft->categoryId = null;
        $rows = $this->catalog->tree()
            ->reject(fn (Category $root) => $root->is_emergency)
            ->map(fn (Category $root) => [['label' => $root->name, 'action' => CallbackAction::Category->payload($root->id)]])
            ->values()
            ->all();
        $this->save($user, DialogState::AskCategory, $draft);
        $this->ctx->replyWith($user, 'report.category', [], $rows);
    }

    private function category(User $user, ReportDraft $draft, ?int $rootId): void
    {
        $root = $rootId === null ? null : Category::query()->active()->with('children')->find($rootId);
        if ($root === null || $root->is_emergency || $root->children->isEmpty()) {
            $this->askCategory($user, $draft);

            return;
        }
        $draft->rootId = $root->id;
        $rows = $root->children
            ->where('is_active', true)
            ->map(fn (Category $leaf) => [['label' => $leaf->name, 'action' => CallbackAction::Subcategory->payload($leaf->id)]])
            ->values()
            ->all();
        $this->save($user, DialogState::AskSubcategory, $draft);
        $this->ctx->replyWith($user, 'report.subcategory', [], $rows);
    }

    private function subcategory(User $user, ReportDraft $draft, ?int $categoryId): void
    {
        $category = $categoryId === null ? null : Category::query()->active()->whereNotNull('parent_id')->find($categoryId);
        if ($category === null) {
            $this->askCategory($user, $draft);

            return;
        }
        $draft->categoryId = $category->id;
        $draft->rootId = $category->parent_id;
        if ($category->is_emergency) {
            $this->emergency($user, $draft);

            return;
        }
        if ($draft->houseId === null) {
            $this->begin($user, $draft);

            return;
        }
        $this->askDetails($user, $draft);
    }

    private function askDetails(User $user, ReportDraft $draft): void
    {
        $this->save($user, DialogState::AskDetails, $draft);
        $this->ctx->reply($user, 'report.details');
    }

    /** @param list<array{token:string|null,url:string|null}> $photos */
    private function details(User $user, ReportDraft $draft, string $text, array $photos): void
    {
        if ($text === '' && $photos === []) {
            $this->ctx->reply($user, 'report.details');

            return;
        }
        if ($text !== '') {
            $draft->description = mb_substr($text, 0, self::DESCRIPTION_LIMIT);
        }
        $draft->addPhotos($photos);
        $this->askAddress($user, $draft);
    }

    private function askAddress(User $user, ReportDraft $draft): void
    {
        if (! $draft->isReady()) {
            $this->begin($user, $draft);

            return;
        }
        $this->save($user, DialogState::AskAddress, $draft);
        if ($user->entrance !== null && $user->flat !== null) {
            $this->ctx->reply($user, 'report.address_same', ['entrance' => $user->entrance, 'flat' => $user->flat]);

            return;
        }
        $this->ctx->reply($user, 'report.address');
    }

    private function sameAddress(User $user, ReportDraft $draft): void
    {
        if (! $draft->isReady()) {
            $this->begin($user, $draft);

            return;
        }
        $draft->entrance = $user->entrance;
        $draft->flat = $user->flat;
        $this->preview($user, $draft);
    }

    private function newAddress(User $user, ReportDraft $draft): void
    {
        if (! $draft->isReady()) {
            $this->begin($user, $draft);

            return;
        }
        $this->save($user, DialogState::AskAddress, $draft);
        $this->ctx->reply($user, 'report.address');
    }

    /** @param list<array{token:string|null,url:string|null}> $photos */
    private function address(User $user, ReportDraft $draft, string $text, array $photos): void
    {
        $draft->addPhotos($photos);
        $parsed = $this->parseAddress($text, $user);
        if ($parsed === null) {
            $this->save($user, DialogState::AskAddress, $draft);
            $this->ctx->reply($user, 'report.address');

            return;
        }
        [$draft->entrance, $draft->flat] = $parsed;
        $this->preview($user, $draft);
    }

    /** @return array{0:int|null,1:string|null}|null */
    private function parseAddress(string $text, User $user): ?array
    {
        preg_match_all('/\d+[а-яё]?/iu', $text, $matches);
        $numbers = $matches[0];
        if ($numbers === []) {
            return null;
        }
        if (count($numbers) >= 2) {
            return [self::entrance($numbers[0]), mb_substr($numbers[1], 0, 10)];
        }
        if (preg_match('/подъезд|под\.|парадн/iu', $text) === 1) {
            return [self::entrance($numbers[0]), null];
        }

        return [$user->entrance, mb_substr($numbers[0], 0, 10)];
    }

    private static function entrance(string $value): ?int
    {
        $number = (int) preg_replace('/\D/u', '', $value);

        return $number > 0 && $number <= 99 ? $number : null;
    }

    private function preview(User $user, ReportDraft $draft): void
    {
        $category = $draft->categoryId === null ? null : Category::query()->find($draft->categoryId);
        $house = $draft->houseId === null ? null : House::query()->with(['organization', 'region'])->find($draft->houseId);
        if ($category === null || $house === null) {
            $this->begin($user, $draft);

            return;
        }
        $responsible = $this->resolver->resolve($category, $house);
        $now = CarbonImmutable::now();
        $timezone = $house->region->timezone;
        $card = $this->card->preview(
            $draft,
            $category,
            $house,
            $responsible,
            $this->deadlines->fixDeadline($category, $now, $timezone),
            $this->deadlines->replyDeadline($category, $now, $timezone),
        );
        $this->save($user, DialogState::Preview, $draft);
        $this->ctx->reply($user, 'report.preview', ['card' => $card]);
    }

    private function send(User $user, ReportDraft $draft, DialogState $state, bool $unsure): void
    {
        if (! $draft->isReady()) {
            $this->begin($user, $draft);

            return;
        }
        if ($state === DialogState::Preview || $unsure) {
            $this->create($user, $draft, $unsure);

            return;
        }
        $this->askAddress($user, $draft);
    }

    private function create(User $user, ReportDraft $draft, bool $unsure): void
    {
        try {
            $request = $this->requests->create(new CreateRequestData(
                houseId: (int) $draft->houseId,
                residentUserId: $user->id,
                categoryId: (int) $draft->categoryId,
                description: $draft->description,
                entrance: $draft->entrance,
                flat: $draft->flat,
                photos: $draft->photoDrafts(),
                origin: RequestOrigin::Direct,
                residentUnsure: $unsure,
                repeatOfId: $draft->repeatOfId,
            ));
        } catch (EmergencyCategory) {
            $this->emergency($user, $draft);

            return;
        } catch (Throwable $e) {
            Log::warning('bot.report_failed', ['error' => $e->getMessage()]);
            $this->ctx->reply($user, 'error.generic');

            return;
        }

        $this->remember($user, $draft);
        $this->downloadPhotos($request, $draft);
        $this->sessions->reset((int) $user->max_user_id);
    }

    private function remember(User $user, ReportDraft $draft): void
    {
        if ($draft->entrance !== null) {
            $user->entrance = $draft->entrance;
        }
        if ($draft->flat !== null) {
            $user->flat = $draft->flat;
        }
        if ($user->isDirty()) {
            $user->save();
        }
    }

    private function downloadPhotos(ServiceRequest $request, ReportDraft $draft): void
    {
        $attachments = $request->attachments()->orderBy('id')->get()->values();
        foreach ($attachments as $index => $attachment) {
            $url = $draft->photos[$index]['url'] ?? null;
            if ($url !== null && $attachment->path === '') {
                DownloadMaxAttachment::dispatch($attachment->id, $url);
            }
        }
    }

    private function cancel(User $user): void
    {
        $this->sessions->reset((int) $user->max_user_id);
        $this->ctx->reply($user, 'report.cancelled');
    }

    /** @return list<array{token:string|null,url:string|null}> */
    private function photos(Update $update): array
    {
        $photos = [];
        foreach ($update->attachments() as $attachment) {
            if (($attachment['type'] ?? null) !== 'image') {
                continue;
            }
            $payload = is_array($attachment['payload'] ?? null) ? $attachment['payload'] : [];
            $token = isset($payload['token']) && is_string($payload['token']) ? $payload['token'] : null;
            $url = isset($payload['url']) && is_string($payload['url']) ? $payload['url'] : null;
            if ($token !== null || $url !== null) {
                $photos[] = ['token' => $token, 'url' => $url];
            }
        }

        return $photos;
    }

    private function save(User $user, DialogState $state, ReportDraft $draft): void
    {
        $this->sessions->save((int) $user->max_user_id, $state, $draft->toArray());
    }
}
