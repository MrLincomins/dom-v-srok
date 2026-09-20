import { CellSimple } from '@maxhub/max-ui';
import { useNavigate } from 'react-router';
import { texts } from '@/app/texts';
import { RequestSection } from '@/components/RequestSection';
import { Screen } from '@/components/Screen';
import { UserContextBar } from '@/components/UserContextBar';

export function ResidentCabinet() {
    const navigate = useNavigate();

    return (
        <Screen title={texts.home.residentTitle} titleLevel={2} contentClassName="flex min-w-0 flex-col gap-24">
            <UserContextBar />
            <RequestSection>
                <CellSimple
                    title={texts.home.createRequest}
                    subtitle={texts.home.createRequestHint}
                    onClick={() => navigate('/new')}
                />
                <CellSimple
                    title={texts.home.myRequests}
                    subtitle={texts.home.myRequestsHint}
                    onClick={() => navigate('/my')}
                />
                <CellSimple
                    title={texts.home.pastRequests}
                    subtitle={texts.home.pastRequestsHint}
                    onClick={() => navigate('/my?tab=past')}
                />
                <CellSimple
                    title={texts.home.house}
                    subtitle={texts.home.houseHint}
                    onClick={() => navigate('/house')}
                />
            </RequestSection>
        </Screen>
    );
}
