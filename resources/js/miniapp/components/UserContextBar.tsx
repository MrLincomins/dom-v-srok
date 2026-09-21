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
    const to = user.role === 'resident' ? '/house' : '/organization';
    const label = user.role === 'resident' ? texts.home.house : texts.home.organization;

    return (
        <CellList mode="island" filled className="request-card">
            <CellSimple
                title={user.name}
                subtitle={[role, place].filter(Boolean).join(' · ')}
                showChevron
                onClick={() => navigate(to)}
                aria-label={label}
            />
        </CellList>
    );
}
