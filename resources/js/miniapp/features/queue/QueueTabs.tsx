import { useCallback, useLayoutEffect, useRef, useState, type KeyboardEvent } from 'react';
import { texts } from '@/app/texts';
import type { QueueTab } from './useQueue';

const TABS: QueueTab[] = ['new', 'in_progress', 'overdue', 'closed'];

export function QueueTabs({
    tab,
    counters,
    onChange,
}: {
    tab: QueueTab;
    counters?: Partial<Record<QueueTab, number>>;
    onChange: (tab: QueueTab) => void;
}) {
    const navRef = useRef<HTMLElement>(null);
    const [indicator, setIndicator] = useState({ x: 0, w: 0, ready: false });

    const updateIndicator = useCallback(() => {
        const nav = navRef.current;
        const active = nav?.querySelector<HTMLElement>(`#queue-tab-${tab}`);
        if (!nav || !active) return;
        setIndicator({ x: active.offsetLeft, w: active.offsetWidth, ready: true });
    }, [tab]);

    useLayoutEffect(() => {
        updateIndicator();
        const nav = navRef.current;
        if (!nav || typeof ResizeObserver === 'undefined') return undefined;
        const observer = new ResizeObserver(updateIndicator);
        observer.observe(nav);
        for (const button of nav.querySelectorAll('.ios-tab')) observer.observe(button);
        return () => observer.disconnect();
    }, [updateIndicator, counters]);

    const selectAdjacent = useCallback(
        (event: KeyboardEvent, current: QueueTab) => {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
            event.preventDefault();
            const offset = event.key === 'ArrowRight' ? 1 : -1;
            const next = TABS[(TABS.indexOf(current) + offset + TABS.length) % TABS.length];
            if (!next) return;
            onChange(next);
            document.getElementById(`queue-tab-${next}`)?.focus();
        },
        [onChange],
    );

    return (
        <nav ref={navRef} className="ios-tabs" aria-label={texts.queue.filters} role="tablist">
            <span
                className={`ios-tabs-indicator${tab === 'overdue' ? ' is-alert' : ''}${indicator.ready ? ' is-ready' : ''}`}
                style={{ width: indicator.w, transform: `translate3d(${indicator.x}px, 0, 0)` }}
                aria-hidden
            />
            {TABS.map((item) => {
                const count = counters?.[item] ?? 0;
                const selected = item === tab;
                return (
                    <button
                        key={item}
                        type="button"
                        className={`ios-tab${selected ? ' is-active' : ''}${item === 'overdue' ? ' is-alert' : ''}`}
                        onClick={() => onChange(item)}
                        onKeyDown={(event) => selectAdjacent(event, item)}
                        role="tab"
                        id={`queue-tab-${item}`}
                        tabIndex={selected ? 0 : -1}
                        aria-selected={selected}
                        aria-controls="queue-panel"
                    >
                        {texts.queue.tabs[item]}
                        {count > 0 ? <span className="ios-tab-count">{count}</span> : null}
                    </button>
                );
            })}
        </nav>
    );
}

export { TABS };
