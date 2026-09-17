import { describeDeadline } from '@/lib/deadline';

/** чип таймера, при просрочке красный */
export function DeadlineChip({
    deadline,
    closed = false,
}: {
    deadline: string | null | undefined;
    closed?: boolean;
}) {
    const view = describeDeadline(deadline);
    if (closed) return null;
    const tone = view.overdue
        ? 'bg-late/10 text-late'
        : view.urgent
          ? 'bg-work/10 text-work'
          : 'bg-muted/10 text-muted';
    return (
        <span className={`tabular inline-flex whitespace-nowrap rounded-lg px-2 py-0.5 text-[13px] ${tone}`}>
            {view.text}
        </span>
    );
}
