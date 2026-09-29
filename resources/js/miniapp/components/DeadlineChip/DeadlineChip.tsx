import type { RequestStatus } from '@/api/types';
import { texts } from '@/app/texts';
import { describeDeadline } from '@/lib/deadline';
import { deadlineRuns } from '@/lib/status';

export function DeadlineChip({
    deadline,
    status,
}: {
    deadline: string | null | undefined;
    status: RequestStatus;
}) {
    if (status === 'done') {
        return (
            <span className="chip bg-page-secondary text-muted">{texts.request.awaitingConfirmation}</span>
        );
    }
    if (!deadlineRuns(status)) return null;
    const view = describeDeadline(deadline);
    const tone = view.overdue
        ? 'bg-late/15 text-late'
        : view.urgent
          ? 'bg-work/15 text-work'
          : 'bg-page-secondary text-muted';
    return <span className={`chip tabular ${tone}`}>{view.text}</span>;
}
