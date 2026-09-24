import { useCallback, useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { bindBackButton, getPlatform, useNativeBackButton } from '@/bridge/maxWebApp';
import { DESKTOP_QUERY } from '@/lib/useWideLayout';

const EDGE = 28;
const DISTANCE = 72;
const SLANT = 56;

function historyIndex(): number {
    const state = window.history.state as { idx?: number } | null;
    return typeof state?.idx === 'number' ? state.idx : 0;
}

function isHorizontallyScrollable(target: EventTarget | null): boolean {
    let node = target instanceof Element ? target : null;
    while (node) {
        const style = window.getComputedStyle(node);
        const overflowX = style.overflowX;
        if ((overflowX === 'auto' || overflowX === 'scroll') && node.scrollWidth > node.clientWidth + 8) {
            return true;
        }
        node = node.parentElement;
    }
    return false;
}

export function edgeSwipeAllowed(platform = getPlatform(), desktopWidth = desktopWidthNow()): boolean {
    return platform !== 'desktop' && !desktopWidth;
}

function desktopWidthNow(): boolean {
    return window.matchMedia(DESKTOP_QUERY).matches;
}

/** Назад: история, кнопка MAX и на телефоне свайп от левого края. */
export function useScreenBack(fallback?: string) {
    const navigate = useNavigate();
    const native = useNativeBackButton();
    const [desktopWidth, setDesktopWidth] = useState(desktopWidthNow);

    const goBack = useCallback(() => {
        if (historyIndex() > 0) {
            navigate(-1);
            return;
        }
        if (fallback) navigate(fallback);
    }, [fallback, navigate]);

    useEffect(() => {
        if (!fallback || !native) return undefined;
        return bindBackButton(goBack);
    }, [fallback, goBack, native]);

    useEffect(() => {
        const media = window.matchMedia(DESKTOP_QUERY);
        const onChange = () => setDesktopWidth(media.matches);
        onChange();
        media.addEventListener('change', onChange);
        return () => media.removeEventListener('change', onChange);
    }, []);

    useEffect(() => {
        if (!fallback || !edgeSwipeAllowed(getPlatform(), desktopWidth)) return undefined;

        let startX = 0;
        let startY = 0;
        let tracking = false;

        const onStart = (event: TouchEvent) => {
            const touch = event.touches[0];
            if (!touch || isHorizontallyScrollable(event.target)) {
                tracking = false;
                return;
            }
            if (touch.clientX > EDGE) {
                tracking = false;
                return;
            }
            startX = touch.clientX;
            startY = touch.clientY;
            tracking = true;
        };

        const onEnd = (event: TouchEvent) => {
            if (!tracking) return;
            tracking = false;
            const touch = event.changedTouches[0];
            if (!touch) return;
            const dx = touch.clientX - startX;
            const dy = touch.clientY - startY;
            if (Math.abs(dy) > SLANT) return;
            if (startX <= EDGE && dx >= DISTANCE) goBack();
        };

        window.addEventListener('touchstart', onStart, { passive: true });
        window.addEventListener('touchend', onEnd, { passive: true });
        return () => {
            window.removeEventListener('touchstart', onStart);
            window.removeEventListener('touchend', onEnd);
        };
    }, [desktopWidth, fallback, goBack]);

    return { goBack, showHeaderBack: Boolean(fallback) && !native };
}
