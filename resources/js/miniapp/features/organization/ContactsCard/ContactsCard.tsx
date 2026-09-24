import { Button } from '@maxhub/max-ui';
import type { Organization } from '@/api/types';
import { texts } from '@/app/texts';
import { CellHeading } from '@/components/CellHeading';
import { Notice } from '@/components/Notice';
import { SettingsField } from '@/components/SettingsField';
import { MutationError } from '@/features/request/MutationError';
import { useContactsCard } from './ContactsCard.model';

export function ContactsCard({ organization }: { organization: Organization }) {
    const card = useContactsCard(organization);

    return (
        <form
            className="flex min-w-0 flex-col gap-16"
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                card.save.mutate();
            }}
        >
            <div className="request-block flex min-w-0 flex-col gap-8">
                <CellHeading>{texts.organization.types[organization.type]}</CellHeading>
                <div className="org-contacts-grid">
                    <SettingsField
                        label={texts.organization.name}
                        value={card.name}
                        onChange={(event) => card.setName(event.target.value)}
                    />
                    <SettingsField
                        label={texts.organization.phoneAds}
                        value={card.phoneAds}
                        onChange={(event) => card.setPhoneAds(event.target.value)}
                        inputMode="tel"
                    />
                    <SettingsField
                        label={texts.organization.phoneDispatch}
                        value={card.phoneDispatch}
                        onChange={(event) => card.setPhoneDispatch(event.target.value)}
                        inputMode="tel"
                    />
                    <SettingsField
                        label={texts.organization.email}
                        value={card.email}
                        onChange={(event) => card.setEmail(event.target.value)}
                        inputMode="email"
                    />
                    <SettingsField
                        label={texts.organization.hours}
                        value={card.hours}
                        onChange={(event) => card.setHours(event.target.value)}
                    />
                    <SettingsField
                        label={texts.organization.address}
                        value={card.address}
                        onChange={(event) => card.setAddress(event.target.value)}
                    />
                </div>
            </div>
            <Button type="submit" variant="primary" size="large" stretched loading={card.save.isPending}>
                {texts.organization.save}
            </Button>
            <MutationError error={card.save.error} />
            <Notice text={card.notice} onGone={() => card.setNotice(null)} />
        </form>
    );
}
