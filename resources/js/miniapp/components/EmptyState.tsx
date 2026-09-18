import { Typography } from '@maxhub/max-ui';
import type { ReactNode } from 'react';

export function EmptyState({ text, action }: { text: string; action?: ReactNode }) {
    return (
        <div className="app-card enter flex flex-col items-center gap-12 px-24 py-40 text-center">
            <Typography.Body variant="medium" className="text-muted">
                {text}
            </Typography.Body>
            {action}
        </div>
    );
}
