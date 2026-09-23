import { CellList } from '@maxhub/max-ui';
import type { ReactNode } from 'react';

export function Section({
    title,
    children,
    className = '',
}: {
    title?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className="request-block flex min-w-0 flex-col gap-8">
            {title ? <h3 className="request-block-title">{title}</h3> : null}
            <CellList mode="island" filled className={className}>
                {children}
            </CellList>
        </div>
    );
}
