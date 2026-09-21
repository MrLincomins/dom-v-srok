import { CellList } from '@maxhub/max-ui';
import type { ReactNode } from 'react';

export function RequestSection({
    title,
    children,
    className = '',
}: {
    title?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={`request-block flex min-w-0 flex-col gap-8 ${className}`.trim()}>
            {title ? <h3 className="request-block-title">{title}</h3> : null}
            <CellList mode="island" filled className="request-sheet">
                {children}
            </CellList>
        </div>
    );
}
