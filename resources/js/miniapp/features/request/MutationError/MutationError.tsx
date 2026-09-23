import { Typography } from '@maxhub/max-ui';
import { describeError } from '@/lib/describeError';

export function MutationError({ error }: { error: unknown }) {
    if (!error) return null;

    return (
        <Typography.Body variant="small" className="px-16 text-negative" role="alert">
            {typeof error === 'string' ? error : describeError(error)}
        </Typography.Body>
    );
}
