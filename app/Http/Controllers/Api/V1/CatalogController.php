<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\CatalogService;
use App\Domain\Catalog\DeadlineCalculator;
use App\Domain\Catalog\ResponsibleResolver;
use App\Domain\Organizations\Models\House;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ResponsibleRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ResponsibleResource;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CatalogController extends Controller
{
    public function categories(CatalogService $catalog): AnonymousResourceCollection
    {
        return CategoryResource::collection($catalog->tree());
    }

    public function responsible(ResponsibleRequest $request, CatalogService $catalog, ResponsibleResolver $resolver, DeadlineCalculator $deadlines): ResponsibleResource
    {
        /** @var User $user */
        $user = $request->user();
        $category = $catalog->leafOrFail($request->categoryId());
        $house = House::query()->with(['organization', 'region'])->find($request->houseId());
        if ($house === null || ! self::canSee($user, $house)) {
            throw new NotFoundHttpException;
        }

        $now = CarbonImmutable::now();
        $timezone = $house->region->timezone;
        $emergency = $category->is_emergency;

        return new ResponsibleResource(
            $category,
            $house,
            $resolver->resolve($category, $house),
            $emergency ? null : $deadlines->fixDeadline($category, $now, $timezone),
            $emergency ? null : $deadlines->replyDeadline($category, $now, $timezone),
        );
    }

    private static function canSee(User $user, House $house): bool
    {
        if ($user->isStaff()) {
            return $user->organization_id !== null && $user->organization_id === $house->organization_id;
        }

        return $user->house_id === $house->id;
    }
}
