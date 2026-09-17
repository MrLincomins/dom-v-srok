import { useCallback, useEffect, useMemo, type ReactNode } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { loginWithInitData, loginWithPassword, logout as apiLogout, me } from '@/api/auth';
import { ApiError, hasToken, setToken, UNAUTHORIZED_EVENT } from '@/api/client';
import type { User } from '@/api/types';
import { getInitData, isInsideMax } from '@/bridge/maxWebApp';
import { AuthContext, type AuthState } from './authContext';
const SESSION_KEY = ['session'] as const;

/** В браузере восстанавливаем сохранённую сессию, а внутри MAX обмениваем initData на токен. */
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
    const clearSession = useCallback(() => {
        client.setQueryData(SESSION_KEY, null);
        client.removeQueries({ queryKey: ['requests'] });
        client.removeQueries({ queryKey: ['my-requests'] });
        client.removeQueries({ queryKey: ['request'] });
    }, [client]);

    useEffect(() => {
        window.addEventListener(UNAUTHORIZED_EVENT, clearSession);
        return () => window.removeEventListener(UNAUTHORIZED_EVENT, clearSession);
    }, [clearSession]);

    const loginDemo = useCallback(
        async (login: string, password: string) => {
            const auth = await loginWithPassword(login, password);
            client.setQueryData(SESSION_KEY, auth.user);
        },
        [client],
    );

    const logout = useCallback(async () => {
        try {
            await apiLogout();
        } finally {
            clearSession();
        }
    }, [clearSession]);

    const refetchSession = session.refetch;
    const refresh = useCallback(async () => {
        await refetchSession();
    }, [refetchSession]);

    const value = useMemo<AuthState>(() => {
        const status: AuthState['status'] = session.isPending
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
