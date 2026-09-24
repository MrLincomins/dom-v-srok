import { CellHeader, CellList } from '@maxhub/max-ui';
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
            <CellList
                mode="island"
                filled
                className="request-sheet"
                header={
                    title ? (
                        <CellHeader titleStyle="caps" role="heading" aria-level={3}>
                            {title}
                        </CellHeader>
                    ) : undefined
                }
            >
                {children}
            </CellList>
        </div>
    );
}
