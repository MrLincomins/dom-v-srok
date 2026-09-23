import { Typography } from '@maxhub/max-ui';
import type { ReactNode } from 'react';

export function EmptyState({ text, action }: { text: string; action?: ReactNode }) {
    return (
        <div className="flex flex-col items-center gap-16 px-24 py-48 text-center">
            <Typography.Body variant="medium" className="text-muted">
                {text}
            </Typography.Body>
            {action}
        </div>
    );
}
