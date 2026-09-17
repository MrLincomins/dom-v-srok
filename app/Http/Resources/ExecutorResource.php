<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Organizations\Models\Executor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Executor */
final class ExecutorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'specialty' => $this->specialty, 'phone' => $this->phone];
    }
}
