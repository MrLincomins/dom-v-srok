import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';

export function UserContextBar() {
    const { user } = useAuth();
    if (!user) return null;

    const place = user.organization?.name ?? user.house?.address;
    const role = user.role === 'resident' ? texts.home.residentRole : texts.home.dispatcherRole;

    return (
        <div className="flex items-start justify-between gap-12 rounded-12 bg-page-secondary px-12 py-10">
            <div className="min-w-0">
                <span className="block truncate text-base font-medium text-ink">{user.name}</span>
                {place && <span className="mt-2 block truncate text-sm text-muted">{place}</span>}
            </div>
            <span className="shrink-0 rounded-lg bg-surface px-8 py-4 text-sm font-medium text-muted">
                {role}
            </span>
        </div>
    );
}
