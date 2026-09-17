import { Typography } from '@maxhub/max-ui';
import { describeError } from '@/lib/describeError';

export function MutationError({ error }: { error: unknown }) {
    if (!error) return null;

    return (
        <Typography.Body variant="small" className="text-negative" role="alert">
            {describeError(error)}
        </Typography.Body>
    );
}
