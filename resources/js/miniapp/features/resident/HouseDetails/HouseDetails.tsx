import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { ContactPhones } from '@/components/ContactPhones';
import { withoutCity, withoutMarks } from '@/lib/address';

export function HouseDetails() {
    const { user } = useAuth();
    const house = user?.house;
    const organization = user?.organization;
    if (!house) return null;

    const facts = [
        user.entrance ? `${texts.resident.entrance} ${user.entrance}` : null,
        user.flat ? `${texts.resident.flat} ${user.flat}` : null,
        house.entrances > 0 ? `${texts.organization.entrances}: ${house.entrances}` : null,
    ].filter(Boolean);

    return (
        <div className="flex min-w-0 flex-col gap-16">
            <div className="place-hero">
                <span className="place-hero-kicker">{texts.resident.address}</span>
                <span className="place-hero-title">{withoutMarks(house.address)}</span>
                {facts.length > 0 ? <span className="place-hero-meta">{facts.join(' · ')}</span> : null}
            </div>
            <ContactPhones ads={organization?.phone_ads} dispatch={organization?.phone_dispatch} />
            {organization ? (
                <section className="place-org">
                    <span className="place-org-kicker">{texts.organization.types[organization.type]}</span>
                    <span className="place-org-name">{organization.name}</span>
                    {organization.reception_hours ? (
                        <span className="place-org-row">
                            <span className="place-org-label">{texts.organization.hours}</span>
                            <span className="place-org-value">{organization.reception_hours}</span>
                        </span>
                    ) : null}
                    {organization.email ? (
                        <a className="place-org-row is-link" href={`mailto:${organization.email}`}>
                            <span className="place-org-label">{texts.organization.email}</span>
                            <span className="place-org-value">{organization.email}</span>
                        </a>
                    ) : null}
                    {organization.reception_address ? (
                        <span className="place-org-row">
                            <span className="place-org-label">{texts.organization.address}</span>
                            <span className="place-org-value">
                                {withoutCity(organization.reception_address)}
                            </span>
                        </span>
                    ) : null}
                </section>
            ) : null}
        </div>
    );
}
