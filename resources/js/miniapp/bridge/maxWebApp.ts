/** обёртка над бриджем маха (window.WebApp), никогда не бросает исключений, в обычном браузере объекта нет */
export type Platform = 'ios' | 'android' | 'desktop' | 'web';

export interface MaxBackButton {
    show?: () => void;
    hide?: () => void;
    onClick?: (handler: () => void) => void;
    offClick?: (handler: () => void) => void;
}

export interface MaxWebApp {
    initData?: string;
    initDataUnsafe?: {
        user?: {
            id: number;
            first_name?: string;
            last_name?: string;
            username?: string;
            language_code?: string;
        };
        start_param?: string;
        auth_date?: number;
    };
    platform?: string;
    version?: string;
    colorScheme?: 'light' | 'dark';
    ready?: () => void;
    close?: () => void;
    openLink?: (url: string) => void;
    BackButton?: MaxBackButton;
    onEvent?: (event: string, handler: (...args: unknown[]) => void) => void;
    offEvent?: (event: string, handler: (...args: unknown[]) => void) => void;
}

declare global {
    interface Window {
        WebApp?: MaxWebApp;
    }
}

export function getWebApp(): MaxWebApp | null {
    if (typeof window === 'undefined') return null;
    return window.WebApp ?? null;
}

export function isInsideMax(): boolean {
    return typeof getWebApp()?.initData === 'string' && (getWebApp()?.initData ?? '') !== '';
}

export function getInitData(): string {
    return getWebApp()?.initData ?? '';
}

export function getPlatform(): Platform {
    const raw = getWebApp()?.platform ?? 'web';
    return raw === 'ios' || raw === 'android' || raw === 'desktop' ? raw : 'web';
}

export function getColorScheme(): 'light' | 'dark' {
    const fromBridge = getWebApp()?.colorScheme;
    if (fromBridge === 'light' || fromBridge === 'dark') return fromBridge;
    if (typeof window !== 'undefined' && window.matchMedia?.('(prefers-color-scheme: dark)').matches)
        return 'dark';
    return 'light';
}

export function signalReady(): void {
    try {
        getWebApp()?.ready?.();
    } catch {
        /* в вебе метода может не быть */
    }
}

export function openExternalLink(url: string): void {
    const app = getWebApp();
    if (app?.openLink) {
        app.openLink(url);
        return;
    }
    window.open(url, '_blank', 'noopener');
}

/** нативная кнопка назад на мобилках, в вебе и десктопе false и экран рисует свою */
export function useNativeBackButton(): boolean {
    const button = getWebApp()?.BackButton;
    return Boolean(button?.show && button?.onClick) && getPlatform() !== 'web';
}

export function bindBackButton(handler: () => void): () => void {
    const button = getWebApp()?.BackButton;
    if (!button?.show || !button.onClick) return () => undefined;
    button.show();
    button.onClick(handler);
    return () => {
        button.offClick?.(handler);
        button.hide?.();
    };
}
