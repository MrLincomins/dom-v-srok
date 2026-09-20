import type { RequestStatus } from '@/api/types';
import { STATUS_LABEL, statusTone, type StatusTone } from '@/lib/status';

const TONE_CLASS: Record<StatusTone, string> = {
    fresh: 'bg-fresh/15 text-fresh',
    accepted: 'bg-accepted-blue/15 text-accepted-blue',
    work: 'bg-work/15 text-work',
    done: 'bg-done/15 text-done',
    late: 'bg-late/15 text-late',
    muted: 'bg-page-secondary text-muted',
};

export function StatusChip({
    status,
    overdue = false,
    label,
}: {
    status: RequestStatus;
    overdue?: boolean;
    label?: string;
}) {
    const tone = statusTone(status, overdue);
    return <span className={`chip ${TONE_CLASS[tone]}`}>{label ?? STATUS_LABEL[status]}</span>;
}
