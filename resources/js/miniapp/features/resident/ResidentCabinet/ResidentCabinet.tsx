import { useCallback, useState } from 'react';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { openExternalLink } from '@/bridge/maxWebApp';
import { Notice, useNoticeState } from '@/components/Notice';
import { Screen } from '@/components/Screen';
import { HouseSheet } from '@/features/resident/HouseSheet';
import { MyRequestsList } from '@/features/resident/MyRequestsPage';

export function ResidentCabinet() {
    const { user } = useAuth();
    const [houseOpen, setHouseOpen] = useState(false);
    const notice = useNoticeState();
    const showNotice = notice.show;
    const closeHouse = useCallback(() => setHouseOpen(false), []);
    const house = user?.house;
    const startUrl = house?.start_url;
    const openChat = useCallback(() => {
        if (!startUrl) return;
        showNotice(texts.home.createRequestNotice, 'success');
        openExternalLink(startUrl);
    }, [startUrl, showNotice]);

    return (
        <Screen
            title={texts.home.residentTitle}
            titleLevel={2}
            contentClassName="flex min-w-0 flex-col gap-16"
            right={
                house ? (
                    <button
                        type="button"
                        className="house-trigger"
                        onClick={() => setHouseOpen(true)}
                        aria-label={texts.home.house}
                    >
                        <HouseIcon />
                    </button>
                ) : undefined
            }
        >
            {startUrl ? (
                <button type="button" className="cabinet-cta" onClick={openChat}>
                    <span className="cabinet-cta-body">
                        <span className="cabinet-cta-title">{texts.home.createRequest}</span>
                        <span className="cabinet-cta-hint">{texts.home.createRequestHint}</span>
                    </span>
                    <span className="cabinet-cta-go" aria-hidden>
                        <ExternalArrow />
                    </span>
                </button>
            ) : null}
            <MyRequestsList />
            <HouseSheet open={houseOpen} onClose={closeHouse} />
            <Notice
                text={notice.text}
                tone={notice.tone}
                revision={notice.revision}
                onGone={notice.clear}
            />
        </Screen>
    );
}

function ExternalArrow() {
    return (
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
            <path
                d="M5 13 13 5M7 5h6v6"
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

function HouseIcon() {
    return (
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden>
            <path
                d="M3.5 9.2 10 3.5l6.5 5.7V16.5H12V12H8v4.5H3.5V9.2Z"
                stroke="currentColor"
                strokeWidth="1.7"
                strokeLinejoin="round"
            />
        </svg>
    );
}
