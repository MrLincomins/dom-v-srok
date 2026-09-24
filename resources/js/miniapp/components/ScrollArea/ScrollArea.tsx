import type { ReactNode } from 'react';
import { useScrollArea } from './ScrollArea.model';

export function ScrollArea({ children, className = '' }: { children: ReactNode; className?: string }) {
    const { viewportRef, thumb, scrolling } = useScrollArea();

    return (
        <div className={`scroll-area${scrolling ? ' is-scrolling' : ''} ${className}`.trim()}>
            <div ref={viewportRef} className="scroll-area-viewport">
                {children}
            </div>
            {thumb ? (
                <div className="scroll-area-rail" aria-hidden>
                    <div
                        className="scroll-area-thumb"
                        style={{ height: thumb.height, transform: `translateY(${thumb.top}px)` }}
                    />
                </div>
            ) : null}
        </div>
    );
}
