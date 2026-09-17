<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\CatalogService;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CatalogController extends Controller
{
    public function categories(CatalogService $catalog): AnonymousResourceCollection
    {
        return CategoryResource::collection($catalog->tree());
    }
}
