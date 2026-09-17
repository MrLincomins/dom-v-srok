import { useParams } from 'react-router';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { ErrorState } from '@/components/ErrorState';
import { ListSkeleton } from '@/components/ListSkeleton';
import { Screen } from '@/components/Screen';
import { EventTimeline } from './EventTimeline';
import { PhotoGallery } from './PhotoGallery';
import { RequestActions } from './RequestActions';
import { RequestSummary } from './RequestSummary';
import { ResponsibleSection } from './ResponsibleSection';
import { useRequest } from './useRequest';

export function RequestPage() {
    const params = useParams();
    const id = Number(params.id);
    const { user } = useAuth();
    const request = useRequest(id);
    const backTo = user?.role === 'resident' ? '/my' : '/queue';

    if (!Number.isFinite(id) || id <= 0) {
        return (
            <Screen title={texts.request.invalidTitle} backTo={backTo}>
                <ErrorState message={texts.request.notFound} />
            </Screen>
        );
    }

    if (request.isPending) {
        return (
            <Screen title={texts.request.title(id)} backTo={backTo}>
                <ListSkeleton rows={4} />
            </Screen>
        );
    }
    if (request.isError) {
        return (
            <Screen title={texts.request.title(id)} backTo={backTo}>
                <ErrorState error={request.error} onRetry={() => void request.refetch()} />
            </Screen>
        );
    }

    return (
        <Screen
            title={texts.request.title(id)}
            backTo={backTo}
            contentClassName="enter-list flex flex-col gap-12"
        >
            <RequestSummary card={request.data} />
            <ResponsibleSection card={request.data} />
            <PhotoGallery attachments={request.data.attachments} />
            <RequestActions card={request.data} isStaff={user?.role !== 'resident'} />
            <EventTimeline events={request.data.events} />
        </Screen>
    );
}
