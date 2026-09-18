import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';

export function LogoutButton() {
    const { logout } = useAuth();

    return (
        <button
            type="button"
            className="-mr-8 rounded-lg px-8 py-6 text-[14px] font-medium text-accent transition-colors hover:bg-accent-muted active:bg-accent-muted"
            onClick={() => void logout()}
        >
            {texts.auth.logout}
        </button>
    );
}
