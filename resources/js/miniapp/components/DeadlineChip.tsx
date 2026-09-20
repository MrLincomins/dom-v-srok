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
        ? 'bg-late/15 text-late'
        : view.urgent
          ? 'bg-work/15 text-work'
          : 'bg-page-secondary text-muted';
    return <span className={`chip tabular ${tone}`}>{view.text}</span>;
}
