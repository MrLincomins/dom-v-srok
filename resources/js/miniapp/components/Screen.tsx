import { useEffect, useRef, type ReactNode } from 'react';
import { PageHeader } from './PageHeader';

interface ScreenProps {
    title: string;
    backTo?: string;
    right?: ReactNode;
    children: ReactNode;
    className?: string;
    contentClassName?: string;
    titleLevel?: 1 | 2;
}

export function Screen({
    title,
    backTo,
    right,
    children,
    className = '',
    contentClassName = '',
    titleLevel = 1,
}: ScreenProps) {
    const screenRef = useRef<HTMLElement>(null);

    useEffect(() => {
        screenRef.current?.focus({ preventScroll: true });
    }, []);

    return (
        <main ref={screenRef} tabIndex={-1} className={`screen outline-none ${className}`}>
            <div className={`screen-content ${contentClassName}`}>
                <PageHeader title={title} backTo={backTo} right={right} titleLevel={titleLevel} />
                {children}
            </div>
        </main>
    );
}
