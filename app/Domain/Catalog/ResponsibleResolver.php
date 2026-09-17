<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Domain\Catalog\Enums\ResponsibleType;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\ResponsibleParty;
use App\Domain\Organizations\Models\Contractor;
use App\Domain\Organizations\Models\House;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Enums\ResponsibleKind;

/** кто отвечает за заявку по категории и дому. если не уверены - isSure false, бот скажет «скорее всего» */
final class ResponsibleResolver
{
    public function resolve(Category $category, House $house): Responsible
    {
        $type = $category->responsible_type ?? ResponsibleType::Uk;
        $organization = $house->organization;

        if ($type === ResponsibleType::Ads) {
            return $this->emergency($organization);
        }

        if ($organization === null) {
            return $this->withoutOrganization($house, $type);
        }

        if ($type === ResponsibleType::Uk) {
            return $this->organization($organization);
        }

        if ($type->isRso()) {
            return $this->resource($organization, $house, $type);
        }

        if ($type->contractorType() !== null) {
            return $this->contractorOrRegional($organization, $house, $type);
        }

        // municipal, capital_repair
        $party = $this->regionalParty($house->region_code, $type);

        return $party !== null
            ? $this->party($party, true, 'Это не к организации дома: обращайтесь напрямую, заявка останется у организации для контроля')
            : new Responsible(ResponsibleKind::Unknown, $type->label(), null, false, 'Контакт уточните в организации дома');
    }

    private function emergency(?Organization $organization): Responsible
    {
        if ($organization === null) {
            return new Responsible(ResponsibleKind::Unknown, 'Аварийная служба', null, false, 'Телефон АДС — на информационном стенде в подъезде или в домовом чате; при угрозе жизни — 112');
        }

        return new Responsible(ResponsibleKind::Organization, 'АДС '.$organization->name, $organization->phone_ads, true, 'Авария: звоните, заявка не нужна');
    }

    private function organization(Organization $organization, bool $isSure = true, ?string $hint = null): Responsible
    {
        return new Responsible(ResponsibleKind::Organization, $organization->name, $organization->phone_dispatch ?? $organization->phone_ads, $isSure, $hint);
    }

    private function party(ResponsibleParty $party, bool $isSure, ?string $hint): Responsible
    {
        return new Responsible(ResponsibleKind::Party, $party->name, $party->phone, $isSure, $hint, $party->id);
    }

    private function withoutOrganization(House $house, ResponsibleType $type): Responsible
    {
        $party = $type === ResponsibleType::Uk ? null : $this->regionalParty($house->region_code, $type);

        if ($party !== null) {
            return $this->party($party, true, 'У дома нет подключённой организации: обращайтесь напрямую, мы напомним проверить в срок');
        }

        return new Responsible(
            ResponsibleKind::Unknown,
            'Управляющая организация вашего дома',
            null,
            false,
            'Организация дома не подключена к сервису; телефон — на информационном стенде или в домовом чате. Мы напомним проверить результат в срок по нормативу',
        );
    }

    private function resource(Organization $organization, House $house, ResponsibleType $type): Responsible
    {
        $party = $this->regionalParty($house->region_code, $type);

        if ($party !== null && $organization->hasDirectContract($type->directContractColumn())) {
            return $this->party($party, false, 'У дома прямой договор с поставщиком. Внутридомовые сети (стояк, подвал) — зона организации: если проблема только у вас, заявку возьмёт '.$organization->name);
        }

        $hint = $party !== null
            ? 'При аварии на магистрали — '.$party->name.($party->phone !== null ? ', '.$party->phone : '')
            : null;

        return $this->organization($organization, false, $hint);
    }

    private function contractorOrRegional(Organization $organization, House $house, ResponsibleType $type): Responsible
    {
        $contractor = Contractor::query()
            ->where('organization_id', $organization->id)
            ->where('type', $type->contractorType())
            ->first();

        if ($contractor !== null) {
            return new Responsible(
                ResponsibleKind::Organization,
                $organization->name.' → '.$contractor->name,
                $contractor->phone ?? $organization->phone_dispatch ?? $organization->phone_ads,
                true,
                'Исполнитель по договору организации: '.$contractor->name,
            );
        }

        $party = $this->regionalParty($house->region_code, $type);

        if ($type === ResponsibleType::Tko && $party !== null) {
            return $this->party($party, true, 'Вывоз отходов — региональный оператор; организация дома отвечает за площадку');
        }

        if ($party !== null) {
            return $this->party($party, false, 'Договор обслуживания заключает организация дома; заявка останется у неё для контроля');
        }

        return $this->organization($organization, true, 'Обслуживание по договору организации');
    }

    private function regionalParty(string $regionCode, ResponsibleType $type): ?ResponsibleParty
    {
        return ResponsibleParty::query()
            ->where('region_code', $regionCode)
            ->where('type', $type->value)
            ->orderBy('id')
            ->first();
    }
}
