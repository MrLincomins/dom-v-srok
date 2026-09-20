import { useEffect } from 'react';

export function Notice({ text, onGone }: { text: string | null; onGone: () => void }) {
    useEffect(() => {
        if (!text) return;
        const timer = window.setTimeout(onGone, 2400);
        return () => window.clearTimeout(timer);
    }, [text, onGone]);

    if (!text) return null;

    return (
        <div className="notice" role="status">
            {text}
        </div>
    );
}
