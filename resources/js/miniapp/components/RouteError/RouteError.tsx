import { texts } from '@/app/texts';
import { ErrorState } from '@/components/ErrorState';

export function RouteError() {
    return (
        <ErrorState
            fullscreen
            message={texts.errors.page}
            onRetry={() => window.location.reload()}
        />
    );
}
