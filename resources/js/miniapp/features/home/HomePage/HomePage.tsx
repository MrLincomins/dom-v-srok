import { useAuth } from '@/app/authContext';
import { QueuePage } from '@/features/queue/QueuePage';
import { ResidentCabinet } from '@/features/resident/ResidentCabinet';

export function HomePage() {
    const { user } = useAuth();
    return user?.role === 'resident' ? <ResidentCabinet /> : <QueuePage />;
}
