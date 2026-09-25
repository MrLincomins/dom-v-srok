<?php

declare(strict_types=1);

namespace App\Bot\Handlers;

use App\Bot\Callbacks\CallbackAction;
use App\Bot\Cards\RequestCard;
use App\Domain\Catalog\CatalogService;
use App\Domain\Catalog\DeadlineCalculator;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\ResponsibleResolver;
use App\Domain\Organizations\Models\House;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;

final class WhoHandler
{
    public function __construct(
        private readonly BotContext $ctx,
        private readonly CatalogService $catalog,
        private readonly ResponsibleResolver $resolver,
        private readonly DeadlineCalculator $deadlines,
        private readonly RequestCard $card,
    ) {}

    public function handle(User $user, ?int $categoryId): void
    {
        $house = $user->house_id === null ? null : House::query()->with(['organization', 'region'])->find($user->house_id);
        if ($house === null) {
            $this->ctx->reply($user, 'who.house');

            return;
        }
        $category = $categoryId === null ? null : Category::query()->active()
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->find($categoryId);
        if ($category === null) {
            $this->sections($user);

            return;
        }
        if ($category->is_emergency) {
            $this->ctx->reply($user, 'who.emergency');

            return;
        }
        if ($category->parent_id === null) {
            $this->leaves($user, $category);

            return;
        }
        $this->answer($user, $category, $house);
    }

    private function sections(User $user): void
    {
        $rows = $this->catalog->tree()
            ->reject(fn (Category $root) => $root->is_emergency)
            ->map(fn (Category $root) => [['label' => $root->name, 'action' => CallbackAction::Who->payload($root->id)]])
            ->values()
            ->all();
        $this->ctx->replyWith($user, 'who.category', [], $rows);
    }

    private function leaves(User $user, Category $root): void
    {
        if ($root->children->isEmpty()) {
            $this->sections($user);

            return;
        }
        $rows = $root->children
            ->map(fn (Category $leaf) => [['label' => $leaf->name, 'action' => CallbackAction::Who->payload($leaf->id)]])
            ->values()
            ->all();
        $this->ctx->replyWith($user, 'who.subcategory', [], $rows);
    }

    private function answer(User $user, Category $category, House $house): void
    {
        $now = CarbonImmutable::now();
        $timezone = $house->region->timezone;
        $text = $this->card->who(
            $category,
            $house,
            $this->resolver->resolve($category, $house),
            $this->deadlines->fixDeadline($category, $now, $timezone),
            $this->deadlines->replyDeadline($category, $now, $timezone),
        );
        $this->ctx->replyRaw($user, $text, $this->ctx->texts()->buttons('who.card'));
    }
}
