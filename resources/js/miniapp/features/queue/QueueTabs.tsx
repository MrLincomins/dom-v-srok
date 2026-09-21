import { useCallback, type KeyboardEvent } from 'react';
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
        <nav className="ios-tabs" aria-label={texts.queue.filters} role="tablist">
            {TABS.map((item) => {
                const count = counters?.[item] ?? 0;
                const selected = item === tab;
                return (
                    <button
                        key={item}
                        type="button"
                        className={`ios-tab${selected ? ' is-active' : ''}${item === 'overdue' ? ' is-alert' : ''}${item === 'closed' ? ' is-done' : ''}`}
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
