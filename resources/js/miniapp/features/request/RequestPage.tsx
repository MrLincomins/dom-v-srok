import { useParams } from 'react-router';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { DelayedSkeleton } from '@/components/DelayedSkeleton';
import { ErrorState } from '@/components/ErrorState';
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
    const backTo = '/';

    if (!Number.isFinite(id) || id <= 0) {
        return (
            <Screen title={texts.request.invalidTitle} backTo={backTo}>
                <ErrorState message={texts.request.notFound} />
            </Screen>
        );
    }

    return (
        <Screen
            title={texts.request.title(id)}
            backTo={backTo}
            contentClassName="flex min-w-0 flex-col gap-16"
        >
            <DelayedSkeleton loading={request.isPending} rows={4} />
            {request.isError && (
                <ErrorState error={request.error} onRetry={() => void request.refetch()} />
            )}
            {request.data && (
                <>
                    <RequestSummary card={request.data} />
                    <ResponsibleSection card={request.data} />
                    <PhotoGallery attachments={request.data.attachments} />
                    <RequestActions card={request.data} isStaff={user?.role !== 'resident'} />
                    <EventTimeline events={request.data.events} />
                </>
            )}
        </Screen>
    );
}
