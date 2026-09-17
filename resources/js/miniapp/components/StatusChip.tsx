import type { RequestStatus } from '@/api/types';
import { STATUS_LABEL, statusTone, type StatusTone } from '@/lib/status';

const TONE_CLASS: Record<StatusTone, string> = {
    fresh: 'bg-fresh/10 text-fresh',
    work: 'bg-work/10 text-work',
    done: 'bg-done/10 text-done',
    late: 'bg-late/10 text-late',
    muted: 'bg-muted/10 text-muted',
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
    return (
        <span
            className={`inline-flex items-center rounded-lg px-8 py-2 text-[13px] font-medium ${TONE_CLASS[tone]}`}
        >
            {label ?? STATUS_LABEL[status]}
        </span>
    );
}
