import { CellHeader } from '@maxhub/max-ui';
import type { ReactNode } from 'react';

export function CellHeading({
    children,
    after,
    onClick,
    expanded,
}: {
    children: ReactNode;
    after?: ReactNode;
    onClick?: () => void;
    expanded?: boolean;
}) {
    const header = (
        <CellHeader titleStyle="caps" after={after} role="heading" aria-level={3}>
            {children}
        </CellHeader>
    );

    if (!onClick) return header;

    return (
        <button
            type="button"
            className="cell-heading-open"
            aria-expanded={expanded}
            onClick={onClick}
        >
            {header}
        </button>
    );
}
