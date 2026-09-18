import { describeDeadline } from '@/lib/deadline';

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
        <span className={`tabular inline-flex whitespace-nowrap rounded-lg px-8 py-2 text-[13px] ${tone}`}>
            {view.text}
        </span>
    );
}
