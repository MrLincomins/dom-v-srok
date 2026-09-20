import { texts } from '@/app/texts';
import { LineLoader } from './LineLoader';

export function FullscreenSpinner({ label = texts.auth.loading }: { label?: string }) {
    return (
        <div
            className="app-shell flex min-h-dvh flex-col items-center justify-center gap-12 p-16 text-muted"
            role="status"
            aria-live="polite"
            aria-busy="true"
        >
            <LineLoader />
            <span className="text-[15px]">{label}</span>
        </div>
    );
}
