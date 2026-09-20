import { CellList, CellSimple } from '@maxhub/max-ui';
import { useNavigate } from 'react-router';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';

export function UserContextBar() {
    const { user } = useAuth();
    const navigate = useNavigate();
    if (!user) return null;

    const place = user.organization?.name ?? user.house?.address;
    const role = user.role === 'resident' ? texts.home.residentRole : texts.home.dispatcherRole;
    const toOrg = user.role !== 'resident';

    return (
        <CellList mode="island" filled className="request-card">
            <CellSimple
                title={user.name}
                subtitle={[role, place].filter(Boolean).join(' · ')}
                showChevron={toOrg}
                onClick={toOrg ? () => navigate('/organization') : undefined}
                aria-label={toOrg ? texts.home.organization : undefined}
            />
        </CellList>
    );
}
