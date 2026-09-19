import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Typography } from '@maxhub/max-ui';
import { useNavigate } from 'react-router';
import { getQueue, myRequests } from '@/api/requests';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { LogoutButton } from '@/components/LogoutButton';
import { Screen } from '@/components/Screen';
import { UserContextBar } from '@/components/UserContextBar';
import { closeMiniApp } from '@/bridge/maxWebApp';

export function HomePage() {
    const { user } = useAuth();
    return user?.role === 'resident' ? <ResidentHome /> : <DispatcherHome />;
}

function DispatcherHome() {
    const navigate = useNavigate();
    const summary = useQuery({
        queryKey: ['requests', 'home-summary'],
        queryFn: ({ signal }) => getQueue({ page: 1, per_page: 1 }, signal),
    });
    const counters = summary.data?.meta.counters;

    return (
        <Screen
            title={texts.home.dispatcherTitle}
            description={texts.home.dispatcherDescription}
            contentClassName="flex flex-col gap-16"
            right={<LogoutButton />}
        >
            <UserContextBar />
            <section aria-labelledby="dispatcher-menu-title" className="flex flex-col gap-10">
                <h2 id="dispatcher-menu-title" className="m-0 text-lg font-semibold text-ink">
                    {texts.home.dispatcherMenu}
                </h2>
                <div className="grid grid-cols-1 gap-12 sm:grid-cols-2">
                    <MenuCard
                        title={texts.home.allRequests}
                        hint={texts.home.allRequestsHint}
                        count={summary.data?.meta.total}
                        showCount
                        onClick={() => navigate('/queue')}
                    />
                    <MenuCard
                        title={texts.queue.tabs.new}
                        hint={texts.home.newRequestsHint}
                        count={counters?.new}
                        showCount
                        onClick={() => navigate('/queue')}
                    />
                    <MenuCard
                        title={texts.queue.tabs.in_progress}
                        hint={texts.home.inProgressHint}
                        count={counters?.in_progress}
                        showCount
                        onClick={() => navigate('/queue?tab=in_progress')}
                    />
                    <MenuCard
                        title={texts.queue.tabs.overdue}
                        hint={texts.home.overdueHint}
                        count={counters?.overdue}
                        showCount
                        attention={Boolean(counters?.overdue)}
                        onClick={() => navigate('/queue?tab=overdue')}
                    />
                    <MenuCard
                        title={texts.queue.tabs.closed}
                        hint={texts.home.closedHint}
                        count={counters?.closed}
                        showCount
                        onClick={() => navigate('/queue?tab=closed')}
                    />
                </div>
            </section>
        </Screen>
    );
}

function ResidentHome() {
    const navigate = useNavigate();
    const [browserHint, setBrowserHint] = useState(false);
    const summary = useQuery({
        queryKey: ['my-requests', 'home-summary'],
        queryFn: ({ signal }) => myRequests(1, signal),
    });

    const reportProblem = () => {
        if (!closeMiniApp()) setBrowserHint(true);
    };

    return (
        <Screen
            title={texts.home.residentTitle}
            description={texts.home.residentDescription}
            contentClassName="flex flex-col gap-16"
            right={<LogoutButton />}
        >
            <UserContextBar />
            <div className="flex flex-col gap-12">
                <MenuCard
                    title={texts.home.reportProblem}
                    hint={texts.home.reportProblemHint}
                    primary
                    onClick={reportProblem}
                />
                <MenuCard
                    title={texts.home.myRequests}
                    hint={texts.home.myRequestsHint}
                    count={summary.data?.meta.total}
                    showCount
                    onClick={() => navigate('/my')}
                />
            </div>
            {browserHint && (
                <Typography.Body
                    variant="medium"
                    className="rounded-12 bg-accent-muted p-12 text-ink"
                    role="status"
                >
                    {texts.home.browserReportHint}
                </Typography.Body>
            )}
            <section className="app-card p-16" aria-labelledby="resident-steps-title">
                <h2 id="resident-steps-title" className="m-0 text-lg font-semibold text-ink">
                    {texts.home.howItWorks}
                </h2>
                <ol className="mt-12 flex flex-col gap-12">
                    {texts.home.steps.map((step, index) => (
                        <li key={step} className="flex gap-12">
                            <span
                                className="flex h-28 w-28 shrink-0 items-center justify-center rounded-full bg-accent-muted text-sm font-semibold text-accent"
                                aria-hidden
                            >
                                {index + 1}
                            </span>
                            <span className="pt-2 text-base leading-relaxed text-ink">{step}</span>
                        </li>
                    ))}
                </ol>
            </section>
        </Screen>
    );
}

function MenuCard({
    title,
    hint,
    count,
    showCount = false,
    attention = false,
    primary = false,
    onClick,
}: {
    title: string;
    hint: string;
    count?: number;
    showCount?: boolean;
    attention?: boolean;
    primary?: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            className={`app-card interactive-card flex min-h-[88px] w-full items-center gap-12 p-16 text-left ${
                primary ? 'border-accent bg-accent-muted' : ''
            }`}
            onClick={onClick}
        >
            <span className="min-w-0 flex-1">
                <span className="block text-lg font-semibold text-ink">{title}</span>
                <span className="mt-4 block text-sm leading-snug text-muted">{hint}</span>
            </span>
            {showCount && (
                <span
                    className={`tabular flex min-h-36 min-w-36 items-center justify-center rounded-full px-8 text-base font-semibold ${
                        attention ? 'bg-late/10 text-late' : 'bg-page-secondary text-ink'
                    }`}
                    aria-label={count === undefined ? 'Загрузка количества заявок' : `${count} заявок`}
                >
                    {count ?? '—'}
                </span>
            )}
            <span className="text-2xl text-muted" aria-hidden>
                ›
            </span>
        </button>
    );
}
