/** Все запросы проходят здесь, чтобы экраны одинаково понимали ошибки API и сети. */
export class ApiError extends Error {
    constructor(
        public readonly code: string,
        message: string,
        public readonly status: number,
        public readonly details: Record<string, unknown> = {},
    ) {
        super(message);
        this.name = 'ApiError';
    }

    get isAuth(): boolean {
        return this.status === 401;
    }

    get isForbidden(): boolean {
        return this.status === 403;
    }

    get isNetwork(): boolean {
        return this.code === 'network';
    }
}

const BASE = '/api/v1';
const TOKEN_KEY = 'dom.token';
export const UNAUTHORIZED_EVENT = 'dom:unauthorized';

let token: string | null = readStoredToken();

function readStoredToken(): string | null {
    try {
        return sessionStorage.getItem(TOKEN_KEY);
    } catch {
        return null;
    }
}

export function setToken(value: string | null): void {
    token = value;
    try {
        if (value) sessionStorage.setItem(TOKEN_KEY, value);
        else sessionStorage.removeItem(TOKEN_KEY);
    } catch {
        // Если sessionStorage недоступен, текущая вкладка всё равно сможет работать с токеном в памяти.
    }
}

export function hasToken(): boolean {
    return token !== null;
}

interface RequestOptions {
    method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
    body?: unknown;
    formData?: FormData;
    query?: Record<string, string | number | boolean | null | undefined>;
    signal?: AbortSignal;
}

async function parseJson(response: Response): Promise<unknown> {
    const text = await response.text();
    if (!text) return null;
    try {
        return JSON.parse(text);
    } catch {
        return null;
    }
}

export async function api<T>(path: string, options: RequestOptions = {}): Promise<T> {
    const url = new URL(BASE + path, window.location.origin);
    for (const [key, value] of Object.entries(options.query ?? {})) {
        if (value !== undefined && value !== null && value !== '') {
            url.searchParams.set(key, typeof value === 'boolean' ? String(Number(value)) : String(value));
        }
    }

    const headers: Record<string, string> = { Accept: 'application/json' };
    if (token) headers.Authorization = `Bearer ${token}`;
    if (options.body !== undefined) headers['Content-Type'] = 'application/json';

    let response: Response;
    try {
        response = await fetch(url, {
            method: options.method ?? (options.body !== undefined || options.formData ? 'POST' : 'GET'),
            headers,
            body: options.formData ?? (options.body !== undefined ? JSON.stringify(options.body) : undefined),
            signal: options.signal,
        });
    } catch (error) {
        if (options.signal?.aborted) throw error;
        throw new ApiError('network', 'Нет связи с сервером', 0);
    }

    if (response.status === 204) return undefined as T;

    const json = await parseJson(response);

    if (!response.ok) {
        const error = (
            json as { error?: { code?: string; message?: string; details?: Record<string, unknown> } } | null
        )?.error;
        if (response.status === 401) {
            setToken(null);
            window.dispatchEvent(new Event(UNAUTHORIZED_EVENT));
        }
        throw new ApiError(
            error?.code ?? `http_${response.status}`,
            error?.message ?? `Ошибка ${response.status}`,
            response.status,
            error?.details ?? {},
        );
    }

    if (json === null) {
        throw new ApiError('invalid_response', 'Сервер вернул некорректный ответ', response.status);
    }

    return json as T;
}
