/** Доступ к MAX Bridge всегда идёт через безопасные функции: в обычном браузере window.WebApp нет. */
type Platform = 'ios' | 'android' | 'desktop' | 'web';

interface MaxBackButton {
    show?: () => void;
    hide?: () => void;
    onClick?: (handler: () => void) => void;
    offClick?: (handler: () => void) => void;
}

interface MaxWebApp {
    initData?: string;
    platform?: string;
    colorScheme?: 'light' | 'dark';
    ready?: () => void;
    close?: () => void;
    openLink?: (url: string) => void;
    onEvent?: (event: 'themeChanged', handler: () => void) => void;
    offEvent?: (event: 'themeChanged', handler: () => void) => void;
    BackButton?: MaxBackButton;
}

declare global {
    interface Window {
        WebApp?: MaxWebApp;
    }
}

function getWebApp(): MaxWebApp | null {
    if (typeof window === 'undefined') return null;
    return window.WebApp ?? null;
}

export function isInsideMax(): boolean {
    const initData = getWebApp()?.initData;
    return typeof initData === 'string' && initData.length > 0;
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

export function subscribeColorScheme(handler: (scheme: 'light' | 'dark') => void): () => void {
    const app = getWebApp();
    const notify = () => handler(getColorScheme());
    if (app?.onEvent) {
        app.onEvent('themeChanged', notify);
        return () => app.offEvent?.('themeChanged', notify);
    }

    const media = window.matchMedia?.('(prefers-color-scheme: dark)');
    media?.addEventListener('change', notify);
    return () => media?.removeEventListener('change', notify);
}

export function openExternalLink(url: string): void {
    let target: URL;
    try {
        target = new URL(url, window.location.origin);
    } catch {
        return;
    }
    if (target.protocol !== 'http:' && target.protocol !== 'https:') return;

    const app = getWebApp();
    if (app?.openLink) {
        app.openLink(target.href);
        return;
    }
    window.open(target.href, '_blank', 'noopener,noreferrer');
}

export function openFileLink(url: string): void {
    if (isInsideMax() && getWebApp()?.openLink) {
        openExternalLink(url);
        return;
    }
    let target: URL;
    try {
        target = new URL(url, window.location.origin);
    } catch {
        return;
    }
    if (target.protocol === 'http:' || target.protocol === 'https:') window.location.assign(target.href);
}

export function signalReady(): void {
    try {
        getWebApp()?.ready?.();
    } catch {
        // Старые клиенты MAX могут объявить bridge без рабочего ready().
    }
}

export function closeMiniApp(): boolean {
    try {
        const close = getWebApp()?.close;
        if (!close) return false;
        close();
        return true;
    } catch {
        return false;
    }
}

/** На desktop и в браузере оставляем кнопку в header, потому что нативной там нет. */
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
