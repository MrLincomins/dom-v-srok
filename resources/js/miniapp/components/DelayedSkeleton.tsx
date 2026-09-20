import { ListSkeleton } from './ListSkeleton';
import { useDelayedFlag } from '@/lib/useDelayedFlag';

export function DelayedSkeleton({ loading, rows = 4 }: { loading: boolean; rows?: number }) {
    const show = useDelayedFlag(loading);
    if (!show) return null;
    return <ListSkeleton rows={rows} />;
}
