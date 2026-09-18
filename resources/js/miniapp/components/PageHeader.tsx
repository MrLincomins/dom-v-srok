import { useEffect } from 'react';
import { useNavigate } from 'react-router';
import { Typography } from '@maxhub/max-ui';
import { bindBackButton, useNativeBackButton } from '@/bridge/maxWebApp';
import { texts } from '@/app/texts';

interface Props {
    title: string;
    backTo?: string;
    right?: React.ReactNode;
}

export function PageHeader({ title, backTo, right }: Props) {
    const navigate = useNavigate();
    const native = useNativeBackButton();

    useEffect(() => {
        if (!backTo || !native) return undefined;
        return bindBackButton(() => navigate(backTo));
    }, [backTo, native, navigate]);

    return (
        <header className="sticky top-0 z-20 border-b border-divider bg-page/90 pt-[env(safe-area-inset-top)] backdrop-blur-xl">
            <div className="grid min-h-[56px] grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-8 px-16 py-10">
                {backTo && !native ? (
                    <button
                        type="button"
                        className="flex h-44 w-44 items-center justify-center rounded-full text-[24px] text-ink transition-colors hover:bg-page-secondary active:bg-page-secondary"
                        onClick={() => navigate(backTo)}
                        aria-label={texts.app.back}
                    >
                        ←
                    </button>
                ) : (
                    <span aria-hidden />
                )}
                <Typography.Headline variant="small" className="min-w-0 truncate font-semibold">
                    {title}
                </Typography.Headline>
                <div className="flex shrink-0 items-center justify-end">{right}</div>
            </div>
        </header>
    );
}
