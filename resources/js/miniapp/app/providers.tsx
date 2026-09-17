import type { ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import '@maxhub/max-ui/dist/styles.css';
import { getColorScheme, getPlatform } from '@/bridge/maxWebApp';
import { AuthProvider } from './auth';

// один клиент запросов на приложение, get повторяется один раз, мутации никогда
const queryClient = new QueryClient({
    defaultOptions: {
        queries: { retry: 1, staleTime: 15_000, refetchOnWindowFocus: true },
        mutations: { retry: 0 },
    },
});

export function Providers({ children }: { children: ReactNode }) {
    // max ui умеет только ios и android, десктоп и веб рисуем как android
    const platform = getPlatform() === 'ios' ? 'ios' : 'android';

    return (
        <MaxUI platform={platform} colorScheme={getColorScheme()}>
            <QueryClientProvider client={queryClient}>
                <AuthProvider>{children}</AuthProvider>
            </QueryClientProvider>
        </MaxUI>
    );
}
