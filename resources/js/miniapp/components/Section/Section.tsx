import { CellHeader, CellList } from '@maxhub/max-ui';
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
            <CellList
                mode="island"
                filled
                className={className}
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
