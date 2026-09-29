import type { RequestCard } from '@/api/types';
import { ResidentRequestActions } from '@/features/request/ResidentRequestActions';
import { StaffRequestActions } from '@/features/request/StaffRequestActions';

export function RequestActions({ card, isStaff }: { card: RequestCard; isStaff: boolean }) {
    if (isStaff) return <StaffRequestActions card={card} />;
    const canConfirm = card.allowed_transitions.includes('confirmed');
    const canReturn = card.allowed_transitions.includes('returned');
    if (!canConfirm && !canReturn) return null;
    return <ResidentRequestActions requestId={card.id} canConfirm={canConfirm} canReturn={canReturn} />;
}
