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
                <div className="page-header-bar">
                    <button
                        type="button"
                        className="page-header-back"
                        onClick={goBack}
                        aria-label={texts.app.back}
                    >
                        <BackChevron />
                    </button>
                    <p className="page-header-title" role="heading" aria-level={titleLevel}>
                        {title}
                    </p>
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
        <svg viewBox="0 0 8 16" fill="none" aria-hidden>
            <path
                d="M7 1 1 8l6 7"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
