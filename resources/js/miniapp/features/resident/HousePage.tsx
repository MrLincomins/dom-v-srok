import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { EmptyState } from '@/components/EmptyState';
import { Screen } from '@/components/Screen';
import { HouseDetails } from './HouseDetails';

export function HousePage() {
    const { user } = useAuth();

    return (
        <Screen title={texts.resident.house} backTo="/" contentClassName="flex min-w-0 flex-col gap-24">
            {user?.house ? <HouseDetails /> : <EmptyState text={texts.resident.noHouse} />}
        </Screen>
    );
}
