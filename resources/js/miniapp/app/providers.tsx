import { useEffect, useState, type ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import '@maxhub/max-ui/dist/styles.css';
import { ApiError } from '@/api/client';
import { getColorScheme, getPlatform, subscribeColorScheme } from '@/bridge/maxWebApp';
import { AuthProvider } from './auth';

function shouldRetryQuery(failureCount: number, error: unknown): boolean {
    if (
        error instanceof ApiError &&
        (error.isAuth || error.isForbidden || error.status === 404 || error.status === 422)
    ) {
        return false;
    }
    return failureCount < 2;
}

// Повторяем только чтение: автоматический повтор мутации может дважды изменить заявку.
const queryClient = new QueryClient({
    defaultOptions: {
        queries: { retry: shouldRetryQuery, staleTime: 15_000, refetchOnWindowFocus: false },
        mutations: { retry: 0 },
    },
});

export function Providers({ children }: { children: ReactNode }) {
    // На вебе и iOS берём iOS-ячейки MAX — они ближе к мессенджеру, чем android-пилюли.
    const platform = getPlatform() === 'android' ? 'android' : 'ios';
    const [colorScheme, setColorScheme] = useState(getColorScheme);

    useEffect(
        () =>
            subscribeColorScheme((scheme) => {
                document.documentElement.dataset.colorScheme = scheme;
                setColorScheme(scheme);
            }),
        [],
    );

    return (
        <MaxUI platform={platform} colorScheme={colorScheme} className="max-root">
            <QueryClientProvider client={queryClient}>
                <AuthProvider>{children}</AuthProvider>
            </QueryClientProvider>
        </MaxUI>
    );
}
