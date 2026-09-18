import { lazy, Suspense } from 'react';
import { createBrowserRouter, Navigate, Outlet, useLocation } from 'react-router';
import { useAuth } from './app/authContext';
import { texts } from './app/texts';
import { FullscreenSpinner } from './components/FullscreenSpinner';
import { ErrorState } from './components/ErrorState';
import { ListSkeleton } from './components/ListSkeleton';
import { LogoutButton } from './components/LogoutButton';
import { Screen } from './components/Screen';

const OpenInMaxPage = lazy(() =>
    import('./features/auth/OpenInMaxPage').then((module) => ({ default: module.OpenInMaxPage })),
);
const QueuePage = lazy(() =>
    import('./features/queue/QueuePage').then((module) => ({ default: module.QueuePage })),
);
const RequestPage = lazy(() =>
    import('./features/request/RequestPage').then((module) => ({ default: module.RequestPage })),
);
const MyRequestsPage = lazy(() =>
    import('./features/resident/MyRequestsPage').then((module) => ({ default: module.MyRequestsPage })),
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
                backTo={user?.role === 'resident' ? '/my' : '/queue'}
            >
                <ListSkeleton rows={2} />
            </Screen>
        );
    }

    return (
        <Screen
            title={pathname.endsWith('/my') ? texts.resident.title : texts.queue.title}
            right={<LogoutButton />}
        >
            <ListSkeleton />
        </Screen>
    );
}

function Home() {
    const { user } = useAuth();
    return user?.role === 'resident' ? <Navigate to="/my" replace /> : <Navigate to="/queue" replace />;
}

function StaffOnly() {
    const { user } = useAuth();
    return user?.role === 'resident' ? <Navigate to="/my" replace /> : <Outlet />;
}

function ResidentOnly() {
    const { user } = useAuth();
    return user?.role === 'resident' ? <Outlet /> : <Navigate to="/queue" replace />;
}

export const router = createBrowserRouter(
    [
        {
            element: <Gate />,
            children: [
                { index: true, element: <Home /> },
                { path: 'requests/:id', element: <RequestPage /> },
                {
                    element: <StaffOnly />,
                    children: [{ path: 'queue', element: <QueuePage /> }],
                },
                {
                    element: <ResidentOnly />,
                    children: [{ path: 'my', element: <MyRequestsPage /> }],
                },
                { path: '*', element: <Navigate to="/" replace /> },
            ],
        },
    ],
    { basename: '/app' },
);
