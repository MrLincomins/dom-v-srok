import { useEffect, useState } from 'react';

/** Скелет показываем только если загрузка длится дольше delay, чтобы экран не дёргался. */
export function useDelayedFlag(active: boolean, delay = 500): boolean {
    const [armed, setArmed] = useState(false);
    if (!active && armed) {
        setArmed(false);
    }

    useEffect(() => {
        if (!active) return undefined;
        const timer = window.setTimeout(() => setArmed(true), delay);
        return () => window.clearTimeout(timer);
    }, [active, delay]);

    return active && armed;
}
