import { useCallback, useEffect, useMemo, type ReactNode } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { loginWithInitData, loginWithPassword, logout as apiLogout, me } from '@/api/auth';
import { ApiError, hasToken, setToken, UNAUTHORIZED_EVENT } from '@/api/client';
import type { User } from '@/api/types';
import { getInitData, isInsideMax } from '@/bridge/maxWebApp';
import { describeSessionError } from '@/lib/describeError';
import { AuthContext, type AuthState } from './authContext';
const SESSION_KEY = ['session'] as const;

/** В браузере поднимаем сохранённый вход. В MAX меняем initData на токен. */
async function loadSession(): Promise<User | null> {
    if (hasToken()) {
        try {
            return await me();
        } catch (error) {
            if (!(error instanceof ApiError && error.isAuth)) throw error;
            setToken(null);
        }
    }
    if (isInsideMax()) return (await loginWithInitData(getInitData())).user;
    return null;
}

export function AuthProvider({ children }: { children: ReactNode }) {
    const client = useQueryClient();
    const session = useQuery({
        queryKey: SESSION_KEY,
        queryFn: loadSession,
        retry: false,
        staleTime: Infinity,
    });
    const dropData = useCallback(() => {
        client.removeQueries({ queryKey: ['requests'] });
        client.removeQueries({ queryKey: ['my-requests'] });
        client.removeQueries({ queryKey: ['request'] });
        client.removeQueries({ queryKey: ['organization'] });
        client.removeQueries({ queryKey: ['executors'] });
    }, [client]);
    const clearSession = useCallback(() => {
        client.setQueryData(SESSION_KEY, null);
        dropData();
    }, [client, dropData]);
    const relogin = useCallback(() => {
        dropData();
        if (client.isFetching({ queryKey: SESSION_KEY }) > 0) return;
        void client.refetchQueries({ queryKey: SESSION_KEY });
    }, [client, dropData]);

    useEffect(() => {
        window.addEventListener(UNAUTHORIZED_EVENT, relogin);
        return () => window.removeEventListener(UNAUTHORIZED_EVENT, relogin);
    }, [relogin]);

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
        const error = session.isError ? describeSessionError(session.error) : null;
        return { status, user: session.data ?? null, error, loginDemo, logout, refresh };
    }, [session.isPending, session.isError, session.data, session.error, loginDemo, logout, refresh]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
