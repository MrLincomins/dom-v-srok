import { createContext, useContext } from 'react';
import type { User } from '@/api/types';

export type AuthState = {
    status: 'loading' | 'ready' | 'anonymous' | 'error';
    user: User | null;
    error: string | null;
    loginDemo: (login: string, password: string) => Promise<void>;
    logout: () => Promise<void>;
    refresh: () => Promise<void>;
};

export const AuthContext = createContext<AuthState | null>(null);

export function useAuth(): AuthState {
    const context = useContext(AuthContext);
    if (!context) throw new Error('useAuth вне AuthProvider');
    return context;
}
