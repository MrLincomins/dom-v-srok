import { useParams } from 'react-router';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { ErrorState } from '@/components/ErrorState';
import { FullscreenSpinner } from '@/components/FullscreenSpinner';
import { Screen } from '@/components/Screen';
import { EventTimeline } from '@/features/request/EventTimeline';
import { PhotoGallery } from '@/features/request/PhotoGallery';
import { RequestActions } from '@/features/request/RequestActions';
import { RequestSummary } from '@/features/request/RequestSummary';
import { ResponsibleSection } from '@/features/request/ResponsibleSection';
import { useRequest } from './RequestPage.model';

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

    if (request.isPending) {
        return <FullscreenSpinner />;
    }

    return (
        <Screen
            title={texts.request.title(id)}
            backTo={backTo}
            contentClassName="flex min-w-0 flex-col gap-24"
        >
            {request.isError && (
                <ErrorState
                    error={request.error}
                    failureCount={request.failureCount}
                    onRetry={() => void request.refetch()}
                />
            )}
            {request.data && (
                <div className="request-layout">
                    <RequestSummary
                        card={request.data}
                        audience={user?.role === 'resident' ? 'resident' : 'staff'}
                    />
                    <ResponsibleSection card={request.data} />
                    <PhotoGallery
                        attachments={request.data.attachments}
                        events={request.data.events}
                        description={request.data.description}
                    />
                    <RequestActions card={request.data} isStaff={user?.role !== 'resident'} />
                    <EventTimeline events={request.data.events} attachments={request.data.attachments} />
                </div>
            )}
        </Screen>
    );
}
