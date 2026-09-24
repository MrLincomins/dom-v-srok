import { useCallback, useEffect, useRef, useState } from 'react';
import type { ScrollThumb } from './ScrollArea.types';

const MIN_THUMB = 32;

export function measureScrollThumb(box: {
    scrollTop: number;
    scrollHeight: number;
    clientHeight: number;
}): ScrollThumb | null {
    const { scrollTop, scrollHeight, clientHeight } = box;
    if (scrollHeight <= clientHeight + 1) return null;
    const height = Math.max(MIN_THUMB, (clientHeight / scrollHeight) * clientHeight);
    const maxTop = clientHeight - height;
    const travel = scrollHeight - clientHeight;
    return { top: travel <= 0 ? 0 : (scrollTop / travel) * maxTop, height };
}

export function useScrollArea() {
    const viewportRef = useRef<HTMLDivElement>(null);
    const hideTimer = useRef(0);
    const [thumb, setThumb] = useState<ScrollThumb | null>(null);
    const [scrolling, setScrolling] = useState(false);

    const sync = useCallback(() => {
        const viewport = viewportRef.current;
        if (!viewport) return;
        setThumb(measureScrollThumb(viewport));
    }, []);

    const onScroll = useCallback(() => {
        sync();
        setScrolling(true);
        window.clearTimeout(hideTimer.current);
        hideTimer.current = window.setTimeout(() => setScrolling(false), 700);
    }, [sync]);

    useEffect(() => {
        const viewport = viewportRef.current;
        if (!viewport) return;

        const frame = window.requestAnimationFrame(sync);
        const observer =
            typeof ResizeObserver === 'undefined' ? null : new ResizeObserver(sync);
        observer?.observe(viewport);
        const watch = () => {
            if (!observer) return;
            for (const child of viewport.children) observer.observe(child);
        };
        watch();
        const mutations = new MutationObserver(() => {
            watch();
            sync();
        });
        mutations.observe(viewport, { childList: true });
        viewport.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', sync);
        return () => {
            window.cancelAnimationFrame(frame);
            window.clearTimeout(hideTimer.current);
            observer?.disconnect();
            mutations.disconnect();
            viewport.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', sync);
        };
    }, [onScroll, sync]);

    return { viewportRef, thumb, scrolling };
}
