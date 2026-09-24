import { useEffect, useState } from 'react';

export const TABLET_QUERY = '(min-width: 768px)';
export const DESKTOP_QUERY = '(min-width: 1024px)';

/** Планшет и ПК: шторка как окно, без свайпа вниз. */
export function useWideLayout(): boolean {
    const [wide, setWide] = useState(() => window.matchMedia(TABLET_QUERY).matches);

    useEffect(() => {
        const media = window.matchMedia(TABLET_QUERY);
        const onChange = () => setWide(media.matches);
        onChange();
        media.addEventListener('change', onChange);
        return () => media.removeEventListener('change', onChange);
    }, []);

    return wide;
}
