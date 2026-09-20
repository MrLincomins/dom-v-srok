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
        <CellList
            mode="island"
            filled
            className={className}
            header={title ? <CellHeader titleStyle="caps">{title}</CellHeader> : undefined}
        >
            {children}
        </CellList>
    );
}
