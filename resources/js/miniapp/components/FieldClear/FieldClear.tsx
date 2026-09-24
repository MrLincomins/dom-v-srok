import type { MouseEvent } from 'react';
import { texts } from '@/app/texts';

export function FieldClear({ onClear }: { onClear: () => void }) {
    const click = (event: MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        event.stopPropagation();
        onClear();
    };

    return (
        <button type="button" className="field-clear" onClick={click} aria-label={texts.app.clear}>
            <ClearMark />
        </button>
    );
}

export function ClearMark() {
    return (
        <svg width="10" height="10" viewBox="0 0 12 12" aria-hidden>
            <path
                d="M2.2 2.2l7.6 7.6M9.8 2.2l-7.6 7.6"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.7"
                strokeLinecap="round"
            />
        </svg>
    );
}
