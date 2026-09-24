import { useLayoutEffect, useState } from 'react';

export function usePhotoPreviews(files: File[]): Array<{ name: string; url: string }> {
    const [previews, setPreviews] = useState<Array<{ name: string; url: string }>>([]);

    useLayoutEffect(() => {
        const next = files.map((file) => ({ name: file.name, url: URL.createObjectURL(file) }));
        setPreviews(next);
        return () => {
            next.forEach((preview) => URL.revokeObjectURL(preview.url));
        };
    }, [files]);

    return previews;
}
