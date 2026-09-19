import { useEffect, useRef, type ReactNode } from 'react';
import { PageHeader } from './PageHeader';

interface ScreenProps {
    title: string;
    description?: string;
    backTo?: string;
    right?: ReactNode;
    children: ReactNode;
    className?: string;
    contentClassName?: string;
}

export function Screen({
    title,
    description,
    backTo,
    right,
    children,
    className = '',
    contentClassName = '',
}: ScreenProps) {
    const screenRef = useRef<HTMLElement>(null);

    useEffect(() => {
        screenRef.current?.focus({ preventScroll: true });
    }, []);

    return (
        <main ref={screenRef} tabIndex={-1} className={`screen outline-none ${className}`}>
            <PageHeader title={title} backTo={backTo} right={right} />
            <div className={`screen-content ${contentClassName}`}>
                {description && <p className="m-0 text-base leading-relaxed text-muted">{description}</p>}
                {children}
            </div>
        </main>
    );
}
