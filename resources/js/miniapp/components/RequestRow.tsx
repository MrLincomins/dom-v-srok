import type { RequestListItem } from '@/api/types';
import { texts } from '@/app/texts';
import { DeadlineChip } from '@/components/DeadlineChip';
import { StatusChip } from '@/components/StatusChip';
import { withoutMarks } from '@/lib/address';
import { statusExplain } from '@/lib/status';

export function RequestRow({
    item,
    detail,
    onOpen,
}: {
    item: Pick<
        RequestListItem,
        | 'id'
        | 'status'
        | 'category'
        | 'address'
        | 'entrance'
        | 'flat'
        | 'participants_count'
        | 'is_overdue'
        | 'deadline_fix_at'
    >;
    detail?: string;
    onOpen: (id: number) => void;
}) {
    const status = statusExplain(item.status, item.is_overdue);
    const place = [
        withoutMarks(item.address),
        item.entrance ? `подъезд ${item.entrance}` : null,
        item.flat ? `кв. ${item.flat}` : null,
    ]
        .filter(Boolean)
        .join(', ');
    const neighbors = item.participants_count > 0 ? texts.queue.neighbors(item.participants_count) : null;
    const meta = [detail, neighbors].filter(Boolean).join(' · ');

    return (
        <button
            type="button"
            className="request-tile"
            onClick={() => onOpen(item.id)}
            aria-label={`${texts.request.openRow} № ${item.id}. ${item.category}. ${status}`}
        >
            <span className="request-tile-name tabular">
                № {item.id}. {item.category}
            </span>
            {meta ? <span className="request-tile-meta">{meta}</span> : null}
            <span className="request-tile-place">{place}</span>
            <span className="request-tile-foot">
                <StatusChip status={item.status} overdue={item.is_overdue} />
                <DeadlineChip
                    deadline={item.deadline_fix_at}
                    closed={
                        item.status === 'done' || item.status === 'confirmed' || item.status === 'redirected'
                    }
                />
            </span>
            <span className="request-tile-chevron" aria-hidden>
                <Chevron />
            </span>
        </button>
    );
}

function Chevron() {
    return (
        <svg width="8" height="14" viewBox="0 0 8 14" fill="none">
            <path
                d="M1.5 1.5 6.5 7 1.5 12.5"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
