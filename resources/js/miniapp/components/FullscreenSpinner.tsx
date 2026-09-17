import { Spinner } from '@maxhub/max-ui';
import { texts } from '@/app/texts';

export function FullscreenSpinner({ label = texts.auth.loading }: { label?: string }) {
    return (
        <div
            className="flex min-h-screen flex-col items-center justify-center gap-3 p-4 text-muted"
            role="status"
        >
            <Spinner size={28} appearance="themed" />
            <span className="text-[15px]">{label}</span>
        </div>
    );
}
