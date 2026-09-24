import { Typography } from '@maxhub/max-ui';
import { describeError } from '@/lib/describeError';

export function MutationError({ error, failureCount }: { error: unknown; failureCount?: number }) {
    if (!error) return null;

    return (
        <Typography.Body variant="small" className="px-16 text-negative" role="alert">
            {typeof error === 'string' ? error : describeError(error, failureCount)}
        </Typography.Body>
    );
}
