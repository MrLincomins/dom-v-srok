import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { ContactPhones } from '@/components/ContactPhones';
import { withoutCity, withoutMarks } from '@/lib/address';

export function HouseDetails({ showName = false }: { showName?: boolean }) {
    const { user } = useAuth();
    const house = user?.house;
    const organization = user?.organization;
    if (!user || (!house && !organization && !showName)) return null;

    return (
        <div className="profile">
            {showName ? (
                <div className="profile-person">
                    <span className="profile-avatar" aria-hidden>
                        {initials(user.name)}
                    </span>
                    <span className="profile-person-name">{user.name}</span>
                </div>
            ) : null}
            {house || user.entrance || user.flat ? (
                <div className="profile-card">
                    {house ? (
                        <div className="profile-row">
                            <span className="profile-label">{texts.resident.address}</span>
                            <span className="profile-value">{withoutMarks(house.address)}</span>
                        </div>
                    ) : null}
                    {user.entrance || user.flat ? (
                        <div className="profile-split">
                            {user.entrance ? (
                                <div className="profile-row">
                                    <span className="profile-label">{texts.resident.entrance}</span>
                                    <span className="profile-value tabular">{user.entrance}</span>
                                </div>
                            ) : null}
                            {user.flat ? (
                                <div className="profile-row">
                                    <span className="profile-label">{texts.resident.flat}</span>
                                    <span className="profile-value tabular">{user.flat}</span>
                                </div>
                            ) : null}
                        </div>
                    ) : null}
                </div>
            ) : null}
            {organization ? (
                <div className="profile-card">
                    <div className="profile-row">
                        <span className="profile-label">{texts.organization.types[organization.type]}</span>
                        <span className="profile-value">{organization.name}</span>
                    </div>
                    {organization.reception_hours ? (
                        <div className="profile-row">
                            <span className="profile-label">{texts.organization.hours}</span>
                            <span className="profile-value">{organization.reception_hours}</span>
                        </div>
                    ) : null}
                    {organization.email ? (
                        <a className="profile-row is-link" href={`mailto:${organization.email}`}>
                            <span className="profile-label">{texts.organization.email}</span>
                            <span className="profile-value">{organization.email}</span>
                        </a>
                    ) : null}
                    {organization.reception_address ? (
                        <div className="profile-row">
                            <span className="profile-label">{texts.organization.address}</span>
                            <span className="profile-value">
                                {withoutCity(organization.reception_address)}
                            </span>
                        </div>
                    ) : null}
                </div>
            ) : null}
            <ContactPhones ads={organization?.phone_ads} dispatch={organization?.phone_dispatch} />
        </div>
    );
}

function initials(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    const letters = parts.slice(0, 2).map((part) => part[0]).join('');
    return letters.toUpperCase() || '?';
}
