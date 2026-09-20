import { useAuth } from '@/app/authContext';
import { QueuePage } from '@/features/queue/QueuePage';
import { MyRequestsPage } from '@/features/resident/MyRequestsPage';

export function HomePage() {
    const { user } = useAuth();
    return user?.role === 'resident' ? <MyRequestsPage /> : <QueuePage />;
}
