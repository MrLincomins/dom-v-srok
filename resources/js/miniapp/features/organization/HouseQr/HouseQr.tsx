import { CellSimple } from '@maxhub/max-ui';
import type { House } from '@/api/types';
import { texts } from '@/app/texts';
import { CellHeading } from '@/components/CellHeading';
import { Section } from '@/components/Section';
import { SettingsField } from '@/components/SettingsField';
import { openExternalLink } from '@/bridge/maxWebApp';
import { MutationError } from '@/features/request/MutationError';
import { useHouseQr } from './HouseQr.model';

export function HouseQr({ house, onDownloaded }: { house: House; onDownloaded: () => void }) {
    const qr = useHouseQr(house, onDownloaded);

    return (
        <div className="house-qr-block">
            <CellHeading>{texts.organization.qr}</CellHeading>
            <div className="house-qr">
                <img src={qr.src} alt={texts.organization.qrAlt} />
            </div>
            {qr.compact ? (
                <div className="house-qr-entrances" role="group" aria-label={texts.organization.qrEntrance}>
                    <button
                        type="button"
                        className={`ios-tab${qr.selected === null ? ' is-active' : ''}`}
                        aria-pressed={qr.selected === null}
                        onClick={() => qr.setEntrance(null)}
                    >
                        {texts.organization.qrWholeHouse}
                    </button>
                    {Array.from({ length: qr.house.entrances }, (_, index) => index + 1).map((number) => (
                        <button
                            key={number}
                            type="button"
                            className={`ios-tab${qr.selected === number ? ' is-active' : ''}`}
                            aria-pressed={qr.selected === number}
                            onClick={() => qr.setEntrance(number)}
                        >
                            {number}
                        </button>
                    ))}
                </div>
            ) : (
                <SettingsField
                    label={texts.organization.qrEntrance}
                    inputMode="numeric"
                    defaultValue={qr.selected == null ? '' : String(qr.selected)}
                    onBlur={(event) => {
                        const raw = event.currentTarget.value.trim();
                        if (raw === '') {
                            qr.setEntrance(null);
                            return;
                        }
                        const next = Number(raw);
                        if (!Number.isInteger(next) || next < 1 || next > qr.house.entrances) {
                            event.currentTarget.value = qr.selected == null ? '' : String(qr.selected);
                            return;
                        }
                        qr.setEntrance(next);
                    }}
                />
            )}
            <Section>
                <CellSimple
                    separator
                    title={texts.organization.qrDownload}
                    onClick={() => qr.download.mutate()}
                />
                <CellSimple title={texts.organization.qrOpen} onClick={() => openExternalLink(qr.src)} />
            </Section>
            <MutationError error={qr.download.error} />
        </div>
    );
}
