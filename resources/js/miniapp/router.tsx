import { lazy, Suspense } from 'react';
import { createBrowserRouter, Navigate, Outlet, useLocation } from 'react-router';
import { useAuth } from './app/authContext';
import { texts } from './app/texts';
import { FullscreenSpinner } from './components/FullscreenSpinner';
import { ErrorState } from './components/ErrorState';
import { ListSkeleton } from './components/ListSkeleton';
import { Screen } from './components/Screen';
import { HomePage } from './features/home/HomePage';

const OpenInMaxPage = lazy(() =>
    import('./features/auth/OpenInMaxPage').then((module) => ({ default: module.OpenInMaxPage })),
);
function QueueToHome() {
    const { search } = useLocation();
    return <Navigate to={search ? `/${search}` : '/'} replace />;
}
const RequestPage = lazy(() =>
    import('./features/request/RequestPage').then((module) => ({ default: module.RequestPage })),
);
const MyRequestsPage = lazy(() =>
    import('./features/resident/MyRequestsPage').then((module) => ({ default: module.MyRequestsPage })),
);
const OrganizationPage = lazy(() =>
    import('./features/organization/OrganizationPage').then((module) => ({
        default: module.OrganizationPage,
    })),
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

    const residentHome = user?.role === 'resident' && (pathname === '/' || pathname.endsWith('/my'));

    return (
        <Screen
            className="is-fallback"
            title={
                pathname.includes('/organization')
                    ? texts.organization.title
                    : residentHome
                      ? texts.resident.title
                      : pathname === '/' || pathname.endsWith('/queue')
                        ? user?.role === 'resident'
                          ? texts.resident.title
                          : texts.queue.title
                        : pathname.endsWith('/my')
                          ? texts.resident.title
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
    return user?.role === 'resident' ? <Navigate to="/my" replace /> : <Outlet />;
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
                        { path: 'queue', element: <QueueToHome /> },
                        { path: 'organization', element: <OrganizationPage /> },
                    ],
                },
                {
                    element: <ResidentOnly />,
                    children: [{ path: 'my', element: <MyRequestsPage backTo="/" /> }],
                },
                { path: '*', element: <Navigate to="/" replace /> },
            ],
        },
    ],
    { basename: '/app' },
);
