<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Domain\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class CatalogService
{
    /** дерево категорий для бота и апи */
    public function tree(): Collection
    {
        return Category::query()->active()->roots()
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->get();
    }

    public function leafOrFail(int $id): Category
    {
        $category = Category::query()->active()->find($id);

        if ($category === null || ! $category->isLeaf()) {
            throw (new ModelNotFoundException)->setModel(Category::class, [$id]);
        }

        return $category;
    }

    public function bySlug(string $slug): ?Category
    {
        return Category::query()->where('slug', $slug)->first();
    }

    /** подсказка категории по словам жителя, без нейронок */
    public function suggest(string $text, int $limit = 3): Collection
    {
        $needle = mb_strtolower(trim($text));
        if ($needle === '') {
            return new Collection;
        }

        return Category::query()->active()->whereNotNull('parent_id')->get()
            ->filter(function (Category $c) use ($needle): bool {
                foreach ([$c->name, ...$c->synonyms] as $phrase) {
                    if ($phrase !== '' && str_contains($needle, mb_strtolower($phrase))) {
                        return true;
                    }
                }

                return false;
            })
            ->take($limit)
            ->values();
    }
}
