import { Spinner } from '@maxhub/max-ui';
import { texts } from '@/app/texts';

export function FullscreenSpinner({ label = texts.auth.loading }: { label?: string }) {
    return (
        <div
            className="app-shell flex min-h-dvh flex-col items-center justify-center gap-12 p-16 text-muted"
            role="status"
            aria-live="polite"
            aria-busy="true"
        >
            <Spinner size={28} appearance="themed" />
            <span className="text-[15px]">{label}</span>
        </div>
    );
}
