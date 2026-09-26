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

final class ResponsibleResolver
{
    private const UNVERIFIED_NAME_MARKERS = ['уточнить', 'сверить'];

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

        if ($type === ResponsibleType::Tko) {
            return $this->waste($organization, $house);
        }

        if ($type->isRso()) {
            return $this->resource($organization, $house, $type);
        }

        if ($type->contractorType() !== null) {
            return $this->contractorOrRegional($organization, $house, $type);
        }

        $party = $this->regionalParty($house->region_code, $type);

        return $party !== null
            ? $this->party($party, true, 'Это не к организации дома: обращайтесь напрямую, заявка останется у организации для контроля', $organization)
            : new Responsible(ResponsibleKind::Unknown, $type->label(), null, false, 'Контакт уточните в организации дома: '.$organization->phone_ads);
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

    private function party(ResponsibleParty $party, bool $isSure, ?string $hint, ?Organization $organization): Responsible
    {
        if ($party->phone === null && $organization !== null) {
            $hint = ($hint === null ? '' : $hint.'. ').'Телефон уточните в организации дома: '.$organization->phone_ads;
        }

        return new Responsible(ResponsibleKind::Party, $this->partyName($party), $party->phone, $isSure, $hint, $party->id);
    }

    private function partyName(ResponsibleParty $party): string
    {
        foreach (self::UNVERIFIED_NAME_MARKERS as $marker) {
            if (mb_stripos($party->name, $marker) !== false) {
                return $party->type->label();
            }
        }

        return $party->name;
    }

    private function withoutOrganization(House $house, ResponsibleType $type): Responsible
    {
        $party = $type === ResponsibleType::Uk ? null : $this->regionalParty($house->region_code, $type);

        if ($party !== null) {
            return $this->party($party, true, 'У дома нет подключённой организации: обращайтесь напрямую, мы напомним проверить в срок', null);
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

        if ($organization->hasDirectContract($type->directContractColumn())) {
            return $party !== null
                ? $this->party($party, false, 'У дома прямой договор с поставщиком. Внутридомовые сети (стояк, подвал) — зона организации: если проблема только у вас, заявку возьмёт '.$organization->name, $organization)
                : $this->organization($organization, false, 'У дома прямой договор с поставщиком, но в справочнике его пока нет: организация дома уточнит поставщика и передаст заявку');
        }

        $hint = $party !== null
            ? 'При аварии на магистрали — '.$this->partyName($party).($party->phone !== null ? ', '.$party->phone : '')
            : null;

        return $this->organization($organization, false, $hint);
    }

    private function waste(Organization $organization, House $house): Responsible
    {
        $party = $this->regionalParty($house->region_code, ResponsibleType::Tko);

        if ($organization->hasDirectContract(ResponsibleType::Tko->directContractColumn())) {
            return $party !== null
                ? $this->party($party, true, 'У дома прямой договор с региональным оператором: вывоз отходов — его зона, организация дома отвечает за площадку', $organization)
                : $this->organization($organization, false, 'У дома прямой договор с региональным оператором ТКО, но в справочнике его пока нет: организация дома уточнит оператора и передаст заявку');
        }

        $contractor = $this->contractor($organization, ResponsibleType::Tko);
        if ($contractor !== null) {
            return $this->viaContractor($organization, $contractor);
        }

        $operator = $party !== null ? ': '.$this->partyName($party).($party->phone !== null ? ', '.$party->phone : '') : ' ТКО';

        return $this->organization($organization, false, 'Вывоз отходов может вести региональный оператор'.$operator.'. Организация дома уточнит и передаст заявку');
    }

    private function contractorOrRegional(Organization $organization, House $house, ResponsibleType $type): Responsible
    {
        $contractor = $this->contractor($organization, $type);
        if ($contractor !== null) {
            return $this->viaContractor($organization, $contractor);
        }

        $party = $this->regionalParty($house->region_code, $type);

        if ($party !== null) {
            return $this->party($party, false, 'Договор обслуживания заключает организация дома; заявка останется у неё для контроля', $organization);
        }

        return $this->organization($organization, true, 'Обслуживание по договору организации');
    }

    private function contractor(Organization $organization, ResponsibleType $type): ?Contractor
    {
        return Contractor::query()
            ->where('organization_id', $organization->id)
            ->where('type', $type->contractorType())
            ->orderBy('id')
            ->first();
    }

    private function viaContractor(Organization $organization, Contractor $contractor): Responsible
    {
        return new Responsible(
            ResponsibleKind::Organization,
            $organization->name.' → '.$contractor->name,
            $contractor->phone ?? $organization->phone_dispatch ?? $organization->phone_ads,
            true,
            'Исполнитель по договору организации: '.$contractor->name,
        );
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
