import type { RequestCard } from '@/api/types';
import { ResidentRequestActions } from '@/features/request/ResidentRequestActions';
import { StaffRequestActions } from '@/features/request/StaffRequestActions';

export function RequestActions({ card, isStaff }: { card: RequestCard; isStaff: boolean }) {
    if (isStaff) return <StaffRequestActions card={card} />;
    if (card.status === 'done') return <ResidentRequestActions requestId={card.id} />;
    return null;
}
