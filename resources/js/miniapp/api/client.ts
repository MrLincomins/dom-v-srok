/** Все запросы идут отсюда, чтобы ошибки API и сети выглядели одинаково. */
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
        // Нет sessionStorage — токен живёт только в этой вкладке.
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

export async function downloadFile(
    path: string,
    options: {
        query?: Record<string, string | number | boolean | null | undefined>;
        filename: string;
        signal?: AbortSignal;
    },
): Promise<void> {
    const url = new URL(BASE + path, window.location.origin);
    for (const [key, value] of Object.entries(options.query ?? {})) {
        if (value !== undefined && value !== null && value !== '') {
            url.searchParams.set(key, typeof value === 'boolean' ? String(Number(value)) : String(value));
        }
    }

    const headers: Record<string, string> = { Accept: 'text/csv' };
    if (token) headers.Authorization = `Bearer ${token}`;

    let response: Response;
    try {
        response = await fetch(url, { headers, signal: options.signal });
    } catch (error) {
        if (options.signal?.aborted) throw error;
        throw new ApiError('network', 'Нет связи с сервером', 0);
    }

    if (!response.ok) {
        const json = await parseJson(response);
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

    const blob = await response.blob();
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = filenameFromDisposition(response.headers.get('Content-Disposition')) ?? options.filename;
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(objectUrl);
}

function filenameFromDisposition(header: string | null): string | null {
    if (!header) return null;
    const encoded = /filename\*=UTF-8''([^;]+)/i.exec(header);
    if (encoded?.[1]) return decodeURIComponent(encoded[1]);
    const plain = /filename="?([^";]+)"?/i.exec(header);
    return plain?.[1] ?? null;
}
