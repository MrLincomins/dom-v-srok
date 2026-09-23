import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import type { House } from '@/api/types';
import { downloadQr, houseQrFilename, houseQrUrl } from '@/lib/qr';

export function useHouseQr(house: House, onDownloaded: () => void) {
    const [entrance, setEntrance] = useState<number | null>(null);
    const compact = house.entrances <= 12;
    const selected = entrance != null && entrance <= house.entrances ? entrance : null;
    const src = houseQrUrl(house.qr_url, selected);
    const download = useMutation({
        mutationFn: () => downloadQr(src, houseQrFilename(house.qr_token, selected)),
        onSuccess: onDownloaded,
    });

    return { compact, selected, src, entrance, setEntrance, download, house };
}
