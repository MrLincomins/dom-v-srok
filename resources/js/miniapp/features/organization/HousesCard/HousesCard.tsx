import { CellSimple, Switch, Typography } from '@maxhub/max-ui';
import type { House } from '@/api/types';
import { texts } from '@/app/texts';
import { Notice } from '@/components/Notice';
import { Section } from '@/components/Section';
import { SettingsField } from '@/components/SettingsField';
import { MutationError } from '@/features/request/MutationError';
import { HouseQr } from '../HouseQr';
import { useHousesCard } from './HousesCard.model';

export function HousesCard({ houses }: { houses: House[] }) {
    const card = useHousesCard();

    if (houses.length === 0) return null;

    return (
        <div className="flex min-w-0 flex-col gap-24">
            {houses.map((house) => (
                <div key={house.id} className="house-panel">
                    <Section title={house.address} className="settings-card">
                        <CellSimple
                            separator
                            title={
                                house.chat_bound
                                    ? texts.organization.houseChat
                                    : texts.organization.houseNoChat
                            }
                            subtitle={
                                house.chat_bound
                                    ? house.chat_pinned
                                        ? texts.organization.housePinned
                                        : texts.organization.houseNotPinned
                                    : undefined
                            }
                        />
                        {!house.chat_bound && (
                            <CellSimple
                                separator
                                title={texts.organization.houseBind}
                                subtitle={texts.organization.houseBindCommand(house.qr_token)}
                                after={
                                    card.copiedKey === `bind:${house.id}` ? (
                                        <span className="cell-copied">{texts.app.copied}</span>
                                    ) : undefined
                                }
                                onClick={() =>
                                    card.copy(
                                        `bind:${house.id}`,
                                        texts.organization.houseBindCommand(house.qr_token),
                                    )
                                }
                            />
                        )}
                        <CellSimple
                            separator
                            title={texts.organization.joinInChat}
                            onClick={
                                house.chat_bound
                                    ? () =>
                                          card.save.mutate({
                                              id: house.id,
                                              chat_keywords_enabled: !house.chat_keywords_enabled,
                                          })
                                    : undefined
                            }
                            after={
                                <span className="switch-hit">
                                    <Switch
                                        checked={house.chat_keywords_enabled}
                                        disabled={!house.chat_bound}
                                        aria-label={texts.organization.joinInChat}
                                    />
                                </span>
                            }
                        />
                        <CellSimple
                            title={texts.organization.openHouse}
                            subtitle={house.start_url}
                            after={
                                card.copiedKey === `link:${house.id}` ? (
                                    <span className="cell-copied">{texts.app.copied}</span>
                                ) : undefined
                            }
                            onClick={() => card.copy(`link:${house.id}`, house.start_url)}
                        />
                    </Section>
                    <SettingsField
                        key={house.entrances}
                        label={texts.organization.entrances}
                        defaultValue={String(house.entrances)}
                        inputMode="numeric"
                        disabled={card.save.isPending}
                        onBlur={(event) => {
                            const next = Number(event.currentTarget.value);
                            if (
                                !Number.isInteger(next) ||
                                next < 1 ||
                                next > 50 ||
                                next === house.entrances
                            ) {
                                event.currentTarget.value = String(house.entrances);
                                return;
                            }
                            card.save.mutate({ id: house.id, entrances: next });
                        }}
                    />
                    <HouseQr
                        house={house}
                        onDownloaded={() => card.notice.show(texts.organization.qrDownloaded, 'success')}
                    />
                    <Typography.Body variant="small" className="settings-hint">
                        {house.chat_bound
                            ? texts.organization.joinInChatHint
                            : texts.organization.houseBindHint}
                    </Typography.Body>
                </div>
            ))}
            <MutationError error={card.save.error} />
            <Notice
                text={card.notice.text}
                tone={card.notice.tone}
                revision={card.notice.revision}
                onGone={card.notice.clear}
            />
        </div>
    );
}
