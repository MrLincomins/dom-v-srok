import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { withoutMarks } from '@/lib/address';

export function UserContextBar() {
    const { user } = useAuth();
    if (!user) return null;

    const resident = user.role === 'resident';
    const place = resident
        ? user.house
            ? withoutMarks(user.house.address)
            : null
        : (user.organization?.name ?? null);
    const facts = resident
        ? [
              user.entrance ? `${texts.resident.entrance} ${user.entrance}` : null,
              user.flat ? `${texts.resident.flat} ${user.flat}` : null,
          ]
              .filter(Boolean)
              .join(' · ')
        : texts.home.dispatcherRole;

    return (
        <div className="who-card">
            <span className="who-card-name">{user.name}</span>
            {place ? <span className="who-card-place">{place}</span> : null}
            {facts ? <span className="who-card-meta">{facts}</span> : null}
        </div>
    );
}
