import { Button } from '@maxhub/max-ui';
import type { Organization } from '@/api/types';
import { texts } from '@/app/texts';
import { CellHeading } from '@/components/CellHeading';
import { Notice } from '@/components/Notice';
import { PhoneField } from '@/components/PhoneField';
import { SettingsField } from '@/components/SettingsField';
import { contactFieldId, useContactsCard } from './ContactsCard.model';

export function ContactsCard({ organization }: { organization: Organization }) {
    const card = useContactsCard(organization);

    return (
        <form className="flex min-w-0 flex-col gap-16" noValidate onSubmit={card.submit}>
            <div className="request-block flex min-w-0 flex-col gap-8">
                <CellHeading>{texts.organization.types[organization.type]}</CellHeading>
                <div className="org-contacts-grid">
                    <SettingsField
                        id={contactFieldId.name}
                        label={texts.organization.name}
                        value={card.name}
                        invalid={card.nameInvalid}
                        onChange={(event) => card.setName(event.target.value)}
                    />
                    <PhoneField
                        id={contactFieldId.phone_ads}
                        label={texts.organization.phoneAds}
                        value={card.phoneAds}
                        invalid={card.phoneAdsInvalid}
                        onChange={card.setPhoneAds}
                    />
                    <PhoneField
                        id={contactFieldId.phone_dispatch}
                        label={texts.organization.phoneDispatch}
                        value={card.phoneDispatch}
                        invalid={card.phoneDispatchInvalid}
                        onChange={card.setPhoneDispatch}
                    />
                    <SettingsField
                        id={contactFieldId.email}
                        label={texts.organization.email}
                        value={card.email}
                        invalid={card.emailInvalid}
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
            <Button
                type="submit"
                variant="primary"
                size="large"
                stretched
                loading={card.saving}
                disabled={!card.dirty || card.saving}
            >
                {texts.organization.save}
            </Button>
            <Notice
                text={card.notice.text}
                tone={card.notice.tone}
                revision={card.notice.revision}
                onGone={card.notice.clear}
            />
        </form>
    );
}
