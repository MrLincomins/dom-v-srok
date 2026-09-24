import { texts } from '@/app/texts';
import { telHref } from '@/lib/phone';

export function ContactPhones({
    ads,
    dispatch,
}: {
    ads?: string | null;
    dispatch?: string | null;
}) {
    if (!ads && !dispatch) return null;

    return (
        <div className="contact-phones">
            {ads ? (
                <a className="contact-phone is-ads" href={telHref(ads)}>
                    <span className="contact-phone-title">{texts.organization.phoneAds}</span>
                    <span className="contact-phone-value">{ads}</span>
                </a>
            ) : null}
            {dispatch ? (
                <a className="contact-phone is-dispatch" href={telHref(dispatch)}>
                    <span className="contact-phone-title">{texts.organization.phoneDispatch}</span>
                    <span className="contact-phone-value">{dispatch}</span>
                </a>
            ) : null}
        </div>
    );
}
