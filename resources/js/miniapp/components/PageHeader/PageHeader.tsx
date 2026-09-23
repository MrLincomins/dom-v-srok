import { Typography } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { useScreenBack } from '@/lib/navigation';

interface Props {
    title: string;
    backTo?: string;
    right?: React.ReactNode;
    titleLevel?: 1 | 2;
}

export function PageHeader({ title, backTo, right, titleLevel = 1 }: Props) {
    const { goBack, showHeaderBack } = useScreenBack(backTo);

    if (showHeaderBack) {
        return (
            <header className="page-header">
                <div className="grid min-h-[44px] grid-cols-[44px_minmax(0,1fr)_44px] items-center py-8">
                    <button
                        type="button"
                        className="flex h-44 w-44 items-center justify-start text-white active:opacity-60"
                        onClick={goBack}
                        aria-label={texts.app.back}
                    >
                        <BackChevron />
                    </button>
                    <Typography.Headline
                        variant="small"
                        className="min-w-0 truncate text-center"
                        role="heading"
                        aria-level={titleLevel}
                    >
                        {title}
                    </Typography.Headline>
                    <div className="page-header-trailing">{right}</div>
                </div>
            </header>
        );
    }

    return (
        <header className="page-header">
            <div className="flex min-h-[44px] items-center justify-between pb-8 pt-8">
                {titleLevel === 2 ? (
                    <h2 className="ios-title-24 min-w-0 truncate">{title}</h2>
                ) : (
                    <h1 className="ios-large-title min-w-0 truncate">{title}</h1>
                )}
                <div className="page-header-trailing">{right}</div>
            </div>
        </header>
    );
}

function BackChevron() {
    return (
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none" aria-hidden>
            <path
                d="M17.5 6.5 9.5 14l8 7.5"
                stroke="currentColor"
                strokeWidth="2.4"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
