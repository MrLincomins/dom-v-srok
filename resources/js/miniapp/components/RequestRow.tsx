import { CellList, CellSimple } from '@maxhub/max-ui';
import type { RequestListItem } from '@/api/types';
import { texts } from '@/app/texts';
import { statusExplain, statusTone, type StatusTone } from '@/lib/status';

const TONE_TEXT: Record<StatusTone, string> = {
    fresh: 'text-fresh',
    work: 'text-work',
    done: 'text-done',
    late: 'text-late',
    muted: 'text-muted',
};

export function RequestRow({
    item,
    onOpen,
}: {
    item: RequestListItem;
    onOpen: (id: number) => void;
}) {
    const tone = statusTone(item.status, item.is_overdue);
    const place = [item.address, item.entrance ? `подъезд ${item.entrance}` : null, item.flat ? `кв. ${item.flat}` : null]
        .filter(Boolean)
        .join(', ');
    const status = statusExplain(item.status, item.is_overdue);
    const neighbors =
        item.participants_count > 0 ? texts.queue.neighbors(item.participants_count) : null;
    const details = [status, neighbors].filter(Boolean).join('. ');

    return (
        <CellList mode="island" filled className="request-card">
            <CellSimple
                showChevron
                onClick={() => onOpen(item.id)}
                aria-label={`${texts.request.openRow} № ${item.id}. ${item.category}. ${status}`}
                title={
                    <span className="tabular block min-w-0 truncate">
                        № {item.id}. {item.category}
                    </span>
                }
                subtitle={
                    <>
                        <span className={`${TONE_TEXT[tone]} block min-w-0 truncate`}>{details}</span>
                        <span className="block text-muted">{place}</span>
                    </>
                }
            />
        </CellList>
    );
}
