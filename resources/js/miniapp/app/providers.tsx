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
    // У MAX UI нет отдельной темы для desktop/web, поэтому там используем Android-вариант.
    const platform = getPlatform() === 'ios' ? 'ios' : 'android';
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
        <MaxUI platform={platform} colorScheme={colorScheme}>
            <QueryClientProvider client={queryClient}>
                <AuthProvider>{children}</AuthProvider>
            </QueryClientProvider>
        </MaxUI>
    );
}
