import { lazy, Suspense } from 'react';
import { createBrowserRouter, Navigate, Outlet, useLocation } from 'react-router';
import { useAuth } from './app/authContext';
import { texts } from './app/texts';
import { FullscreenSpinner } from './components/FullscreenSpinner';
import { ErrorState } from './components/ErrorState';
import { ListSkeleton } from './components/ListSkeleton';
import { Screen } from './components/Screen';
import { HomePage } from './features/home/HomePage';
import { HousePage } from './features/resident/HousePage';

const OpenInMaxPage = lazy(() =>
    import('./features/auth/OpenInMaxPage').then((module) => ({ default: module.OpenInMaxPage })),
);
function ToHome() {
    const { search } = useLocation();
    return <Navigate to={search ? `/${search}` : '/'} replace />;
}
const RequestPage = lazy(() =>
    import('./features/request/RequestPage').then((module) => ({ default: module.RequestPage })),
);
const CreateRequestPage = lazy(() =>
    import('./features/resident/CreateRequestPage').then((module) => ({
        default: module.CreateRequestPage,
    })),
);
const OrganizationPage = lazy(() =>
    import('./features/organization/OrganizationPage').then((module) => ({
        default: module.OrganizationPage,
    })),
);
const JournalPage = lazy(() =>
    import('./features/journal/JournalPage').then((module) => ({ default: module.JournalPage })),
);

/** Не монтируем рабочие экраны, пока не понятно, кто открыл приложение. */
function Gate() {
    const auth = useAuth();
    if (auth.status === 'loading') return <FullscreenSpinner />;
    if (auth.status === 'error')
        return (
            <ErrorState message={auth.error ?? undefined} onRetry={() => void auth.refresh()} fullscreen />
        );
    if (auth.status === 'anonymous')
        return (
            <Suspense fallback={<FullscreenSpinner />}>
                <OpenInMaxPage />
            </Suspense>
        );
    return (
        <div className="app-shell">
            <Suspense fallback={<RouteFallback />}>
                <Outlet />
            </Suspense>
        </div>
    );
}

function RouteFallback() {
    const { pathname } = useLocation();
    const { user } = useAuth();
    const requestId = Number(pathname.split('/').at(-1));

    if (pathname.includes('/requests/')) {
        return (
            <Screen
                title={
                    Number.isFinite(requestId) ? texts.request.title(requestId) : texts.request.invalidTitle
                }
                backTo="/"
            >
                <ListSkeleton rows={2} />
            </Screen>
        );
    }

    const residentHome = user?.role === 'resident' && pathname === '/';

    return (
        <Screen
            className="is-fallback"
            title={
                pathname.includes('/organization')
                    ? texts.organization.title
                    : pathname.includes('/house')
                      ? texts.resident.house
                      : pathname.includes('/create')
                          ? texts.home.createRequest
                          : pathname.includes('/journal')
                            ? texts.journal.title
                            : residentHome
                              ? texts.home.residentTitle
                              : texts.queue.title
            }
            titleLevel={pathname === '/' || residentHome ? 2 : 1}
        >
            <ListSkeleton />
        </Screen>
    );
}

function StaffOnly() {
    const { user } = useAuth();
    return user?.role === 'resident' ? <Navigate to="/" replace /> : <Outlet />;
}

function ResidentOnly() {
    const { user } = useAuth();
    return user?.role === 'resident' ? <Outlet /> : <Navigate to="/" replace />;
}

export const router = createBrowserRouter(
    [
        {
            element: <Gate />,
            children: [
                { index: true, element: <HomePage /> },
                { path: 'requests/:id', element: <RequestPage /> },
                {
                    element: <StaffOnly />,
                    children: [
                        { path: 'queue', element: <ToHome /> },
                        { path: 'organization', element: <OrganizationPage /> },
                        { path: 'journal', element: <JournalPage /> },
                    ],
                },
                {
                    element: <ResidentOnly />,
                    children: [
                        { path: 'my', element: <ToHome /> },
                        { path: 'create', element: <CreateRequestPage /> },
                        { path: 'house', element: <HousePage /> },
                    ],
                },
                { path: '*', element: <Navigate to="/" replace /> },
            ],
        },
    ],
    { basename: '/app' },
);
