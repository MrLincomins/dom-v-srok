import type { RequestCard } from '@/api/types';

export function describeRedirect(redirected: RequestCard['redirected_to']): string | null {
    if (!redirected) return null;
    const line = [
        redirected.name || redirected.party?.name,
        redirected.phone || redirected.party?.phone,
        redirected.note,
    ]
        .filter(Boolean)
        .join(' · ');
    return line || null;
}
