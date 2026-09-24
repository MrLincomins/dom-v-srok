import { describeError } from '@/lib/describeError';

export function MutationError({ error, failureCount }: { error: unknown; failureCount?: number }) {
    if (!error) return null;

    return (
        <p className="form-error" role="alert">
            {typeof error === 'string' ? error : describeError(error, failureCount)}
        </p>
    );
}
