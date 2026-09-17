import { createContext, useCallback, useContext, useMemo, type ReactNode } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { loginWithInitData, loginWithPassword, logout as apiLogout, me } from '@/api/auth';
import { ApiError, hasToken, setToken } from '@/api/client';
import type { User } from '@/api/types';
import { getInitData, isInsideMax } from '@/bridge/maxWebApp';

type AuthStatus = 'loading' | 'ready' | 'anonymous' | 'error';

interface AuthState {
    status: AuthStatus;
    user: User | null;
    error: string | null;
    loginDemo: (login: string, password: string) => Promise<void>;
    logout: () => Promise<void>;
    refresh: () => Promise<void>;
}

const AuthContext = createContext<AuthState | null>(null);
const SESSION_KEY = ['session'] as const;

/** сессия как обычный запрос: есть токен - /me, внутри маха - initData, иначе null и экран «откройте из max» */
async function loadSession(): Promise<User | null> {
    try {
        if (hasToken()) return await me();
        if (isInsideMax()) return (await loginWithInitData(getInitData())).user;
        return null;
    } catch (error) {
        if (error instanceof ApiError && error.isAuth) {
            setToken(null);
            if (!isInsideMax()) return null;
        }
        throw error;
    }
}

export function AuthProvider({ children }: { children: ReactNode }) {
    const client = useQueryClient();
    const session = useQuery({
        queryKey: SESSION_KEY,
        queryFn: loadSession,
        retry: false,
        staleTime: Infinity,
    });

    const loginDemo = useCallback(
        async (login: string, password: string) => {
            const auth = await loginWithPassword(login, password);
            client.setQueryData(SESSION_KEY, auth.user);
        },
        [client],
    );

    const logout = useCallback(async () => {
        await apiLogout();
        client.setQueryData(SESSION_KEY, null);
        client.removeQueries({ queryKey: ['requests'] });
    }, [client]);

    const refresh = useCallback(async () => {
        await session.refetch();
    }, [session]);

    const value = useMemo<AuthState>(() => {
        const status: AuthStatus = session.isPending
            ? 'loading'
            : session.isError
              ? 'error'
              : session.data
                ? 'ready'
                : 'anonymous';
        const error = session.isError
            ? session.error instanceof Error
                ? session.error.message
                : 'Не получилось войти'
            : null;
        return { status, user: session.data ?? null, error, loginDemo, logout, refresh };
    }, [session.isPending, session.isError, session.data, session.error, loginDemo, logout, refresh]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
    const ctx = useContext(AuthContext);
    if (!ctx) throw new Error('useAuth вне AuthProvider');
    return ctx;
}
