<?php

declare(strict_types=1);

namespace App\Domain\Organizations;

use App\Domain\Organizations\Dto\ContractorData;
use App\Domain\Organizations\Dto\ExecutorData;
use App\Domain\Organizations\Dto\HouseData;
use App\Domain\Organizations\Dto\HouseSettingsData;
use App\Domain\Organizations\Dto\OrganizationProfileData;
use App\Domain\Organizations\Models\Contractor;
use App\Domain\Organizations\Models\Executor;
use App\Domain\Organizations\Models\House;
use App\Domain\Organizations\Models\Organization;
use Illuminate\Support\Facades\Log;

final class OrganizationService
{
    public function updateProfile(Organization $organization, OrganizationProfileData $data): Organization
    {
        $organization->fill($data->attributes());
        $fields = array_keys($organization->getDirty());
        $organization->save();
        Log::info('organization.updated', ['organization_id' => $organization->id, 'fields' => $fields]);

        return $organization;
    }

    public function addHouse(Organization $organization, HouseData $data): House
    {
        $house = $organization->houses()->create([
            'region_code' => $organization->region_code,
            'address' => $data->address,
            'entrances' => $data->entrances,
            'chat_keywords_enabled' => $data->chatKeywordsEnabled,
            'is_demo' => false,
        ]);
        Log::info('house.created', ['organization_id' => $organization->id, 'house_id' => $house->id]);

        return $house;
    }

    public function updateHouse(House $house, HouseSettingsData $data): House
    {
        $house->fill(['entrances' => $data->entrances, 'chat_keywords_enabled' => $data->chatKeywordsEnabled]);
        $fields = array_keys($house->getDirty());
        $house->save();
        Log::info('house.updated', ['house_id' => $house->id, 'fields' => $fields]);

        return $house;
    }

    public function addExecutor(Organization $organization, ExecutorData $data): Executor
    {
        $executor = $organization->executors()->create([
            'name' => $data->name,
            'phone' => $data->phone,
            'specialty' => $data->specialty,
            'is_active' => true,
        ]);
        Log::info('executor.created', ['organization_id' => $organization->id, 'executor_id' => $executor->id]);

        return $executor;
    }

    public function updateExecutor(Executor $executor, ExecutorData $data): Executor
    {
        $executor->fill(['name' => $data->name, 'phone' => $data->phone, 'specialty' => $data->specialty]);
        $executor->save();
        Log::info('executor.updated', ['executor_id' => $executor->id]);

        return $executor;
    }

    public function archiveExecutor(Executor $executor): void
    {
        $executor->is_active = false;
        $executor->save();
        Log::info('executor.archived', ['executor_id' => $executor->id]);
    }

    public function addContractor(Organization $organization, ContractorData $data): Contractor
    {
        $contractor = $organization->contractors()->create([
            'type' => $data->type,
            'name' => $data->name,
            'phone' => $data->phone,
        ]);
        Log::info('contractor.created', ['organization_id' => $organization->id, 'contractor_id' => $contractor->id, 'type' => $data->type->value]);

        return $contractor;
    }

    public function removeContractor(Contractor $contractor): void
    {
        $contractor->delete();
        Log::info('contractor.removed', ['organization_id' => $contractor->organization_id, 'contractor_id' => $contractor->id]);
    }
}
