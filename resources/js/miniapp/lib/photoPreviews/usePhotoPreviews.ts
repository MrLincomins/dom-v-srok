import { useLayoutEffect, useState } from 'react';

export function usePhotoPreviews(files: File[]): Array<{ name: string; url: string }> {
    const [previews, setPreviews] = useState<Array<{ name: string; url: string }>>([]);

    useLayoutEffect(() => {
        const next = files.map((file) => ({ name: file.name, url: URL.createObjectURL(file) }));
        // Адрес нужен до отрисовки, иначе картинка мигнёт пустой. Отзыв — в cleanup того же списка.
        // eslint-disable-next-line react-hooks/set-state-in-effect -- blob URL до paint
        setPreviews(next);
        return () => {
            next.forEach((preview) => URL.revokeObjectURL(preview.url));
        };
    }, [files]);

    return previews;
}
