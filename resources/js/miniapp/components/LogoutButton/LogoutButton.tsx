import { useState } from 'react';
import { Button } from '@maxhub/max-ui';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';

export function LogoutButton() {
    const { user, logout } = useAuth();
    const [busy, setBusy] = useState(false);
    if (!user?.is_demo) return null;

    return (
        <Button
            type="button"
            variant="secondary"
            size="large"
            stretched
            className="auth-logout"
            loading={busy}
            onClick={() => {
                setBusy(true);
                void logout().finally(() => setBusy(false));
            }}
        >
            {texts.auth.logout}
        </Button>
    );
}
