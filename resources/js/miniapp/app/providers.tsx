import { useEffect, useState, type ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import '@maxhub/max-ui/dist/styles.css';
import { ApiError } from '@/api/client';
import { getColorScheme, getPlatform, subscribeColorScheme } from '@/bridge/maxWebApp';
import { AuthProvider } from './auth';
import { LIVE_REFETCH_MS } from './live';

function shouldRetryQuery(failureCount: number, error: unknown): boolean {
    if (
        error instanceof ApiError &&
        (error.isAuth || error.isForbidden || error.status === 404 || error.status === 422)
    ) {
        return false;
    }
    return failureCount < 2;
}

// Повтор только у чтения. Если повторить сохранение, заявка может уехать дважды.
const queryClient = new QueryClient({
    defaultOptions: {
        queries: { retry: shouldRetryQuery, staleTime: LIVE_REFETCH_MS, refetchOnWindowFocus: false },
        mutations: { retry: 0 },
    },
});

export function Providers({ children }: { children: ReactNode }) {
    // На вебе и iPhone — ячейки как в iOS, ближе к мессенджеру.
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
