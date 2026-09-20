import { useEffect, useState, type ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import '@maxhub/max-ui/dist/styles.css';
import { getColorScheme, getPlatform, subscribeColorScheme } from '@/bridge/maxWebApp';
import { AuthProvider } from './auth';

// Повторяем только чтение: автоматический повтор мутации может дважды изменить заявку.
const queryClient = new QueryClient({
    defaultOptions: {
        queries: { retry: 1, staleTime: 15_000, refetchOnWindowFocus: false },
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
