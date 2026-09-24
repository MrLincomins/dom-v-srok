import { api, setToken } from './client';
import type { AuthToken, User } from './types';

export async function loginWithInitData(initData: string): Promise<AuthToken> {
    const result = await api<{ data: AuthToken }>('/auth/max', { body: { init_data: initData } });
    setToken(result.data.token);
    return result.data;
}

/** Такой вход backend пускает только для демо. */
export async function loginWithPassword(login: string, password: string): Promise<AuthToken> {
    const result = await api<{ data: AuthToken }>('/auth/login', { body: { login, password } });
    setToken(result.data.token);
    return result.data;
}

export async function me(): Promise<User> {
    return (await api<{ data: User }>('/me')).data;
}

export async function logout(): Promise<void> {
    try {
        await api('/auth/logout', { method: 'POST' });
    } finally {
        setToken(null);
    }
}
