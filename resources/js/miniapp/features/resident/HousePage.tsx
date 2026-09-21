import { CellSimple } from '@maxhub/max-ui';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { EmptyState } from '@/components/EmptyState';
import { RequestSection } from '@/components/RequestSection';
import { Screen } from '@/components/Screen';
import { withoutCity, withoutMarks } from '@/lib/address';

export function HousePage() {
    const { user } = useAuth();
    const house = user?.house;
    const organization = user?.organization;
    const place = house
        ? [
              withoutMarks(house.address),
              user.entrance ? `${texts.resident.entrance} ${user.entrance}` : null,
              user.flat ? `${texts.resident.flat} ${user.flat}` : null,
          ]
              .filter(Boolean)
              .join(', ')
        : '';

    return (
        <Screen title={texts.resident.house} backTo="/" contentClassName="flex min-w-0 flex-col gap-24">
            {!house ? (
                <EmptyState text={texts.resident.noHouse} />
            ) : (
                <>
                    <RequestSection>
                        <CellSimple title={texts.resident.address} subtitle={place} />
                        {house.entrances > 0 && (
                            <CellSimple title={texts.organization.entrances} subtitle={String(house.entrances)} />
                        )}
                    </RequestSection>
                    {organization && (
                        <RequestSection title={organization.name}>
                            <CellSimple
                                title={texts.organization.phoneAds}
                                subtitle={organization.phone_ads}
                            />
                            {organization.phone_dispatch && (
                                <CellSimple
                                    title={texts.organization.phoneDispatch}
                                    subtitle={organization.phone_dispatch}
                                />
                            )}
                            {organization.email && (
                                <CellSimple title={texts.organization.email} subtitle={organization.email} />
                            )}
                            {organization.reception_hours && (
                                <CellSimple
                                    title={texts.organization.hours}
                                    subtitle={organization.reception_hours}
                                />
                            )}
                            {organization.reception_address && (
                                <CellSimple
                                    title={texts.organization.address}
                                    subtitle={withoutCity(organization.reception_address)}
                                />
                            )}
                        </RequestSection>
                    )}
                </>
            )}
        </Screen>
    );
}
