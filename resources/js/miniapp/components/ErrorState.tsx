import { Button, Typography } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { describeError } from '@/lib/describeError';

interface Props {
    error?: unknown;
    message?: string;
    onRetry?: () => void;
    fullscreen?: boolean;
}

export function ErrorState({ error, message, onRetry, fullscreen = false }: Props) {
    const text = message ?? describeError(error);
    return (
        <div
            className={`enter flex flex-col items-center justify-center gap-12 rounded-card px-24 py-40 text-center ${fullscreen ? 'app-shell min-h-dvh' : 'border border-divider bg-surface shadow-card'}`}
            role="alert"
        >
            <Typography.Title variant="small-strong">{text}</Typography.Title>
            {onRetry && (
                <Button variant="secondary" size="medium" onClick={onRetry}>
                    {texts.queue.retry}
                </Button>
            )}
        </div>
    );
}
