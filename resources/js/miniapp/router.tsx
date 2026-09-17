import { createBrowserRouter, Navigate, Outlet } from 'react-router';
import { useAuth } from './app/auth';
import { OpenInMaxPage } from './features/auth/OpenInMaxPage';
import { QueuePage } from './features/queue/QueuePage';
import { RequestPage } from './features/request/RequestPage';
import { MyRequestsPage } from './features/resident/MyRequestsPage';
import { FullscreenSpinner } from './components/FullscreenSpinner';
import { ErrorState } from './components/ErrorState';

/** пока идёт вход спиннер, вне маха экран входа, житель в «мои заявки», диспетчер в очередь */
function Gate() {
    const auth = useAuth();
    if (auth.status === 'loading') return <FullscreenSpinner />;
    if (auth.status === 'error')
        return (
            <ErrorState message={auth.error ?? undefined} onRetry={() => void auth.refresh()} fullscreen />
        );
    if (auth.status === 'anonymous') return <OpenInMaxPage />;
    return <Outlet />;
}

function Home() {
    const { user } = useAuth();
    return user?.role === 'resident' ? <Navigate to="/my" replace /> : <Navigate to="/queue" replace />;
}

export const router = createBrowserRouter(
    [
        {
            element: <Gate />,
            children: [
                { index: true, element: <Home /> },
                { path: 'queue', element: <QueuePage /> },
                { path: 'requests/:id', element: <RequestPage /> },
                { path: 'my', element: <MyRequestsPage /> },
                { path: '*', element: <Navigate to="/" replace /> },
            ],
        },
    ],
    { basename: '/app' },
);
