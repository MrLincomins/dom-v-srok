import { Button, Typography } from '@maxhub/max-ui';
import { ApiError } from '@/api/client';
import { texts } from '@/app/texts';

interface Props {
    error?: unknown;
    message?: string;
    onRetry?: () => void;
    fullscreen?: boolean;
}

/** ошибка объясняет причину и даёт кнопку повторить */
export function ErrorState({ error, message, onRetry, fullscreen = false }: Props) {
    const text = message ?? describe(error);
    return (
        <div
            className={`flex flex-col items-center justify-center gap-3 px-4 py-10 text-center ${fullscreen ? 'min-h-screen' : ''}`}
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

export function describe(error: unknown): string {
    if (error instanceof ApiError) {
        if (error.isNetwork) return texts.errors.network;
        if (error.isForbidden) return texts.errors.forbidden;
        return error.message;
    }
    return texts.errors.generic;
}
