import { Button, Typography } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { describeError } from '@/lib/describeError';

interface Props {
    error?: unknown;
    message?: string;
    failureCount?: number;
    onRetry?: () => void;
    fullscreen?: boolean;
}

export function ErrorState({ error, message, failureCount, onRetry, fullscreen = false }: Props) {
    const text = message ?? describeError(error, failureCount);
    return (
        <div
            className={`flex flex-col items-center justify-center gap-16 px-16 py-48 text-center ${fullscreen ? 'app-shell min-h-dvh' : ''}`}
            role="alert"
        >
            <Typography.Title variant="small-strong">{text}</Typography.Title>
            {onRetry && (
                <Button type="button" variant="secondary" size="large" onClick={onRetry}>
                    {texts.queue.retry}
                </Button>
            )}
        </div>
    );
}
