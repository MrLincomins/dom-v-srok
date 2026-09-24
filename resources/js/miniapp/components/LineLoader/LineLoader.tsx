import { texts } from '@/app/texts';

export function LineLoader({ size = 28 }: { size?: number }) {
    return (
        <svg
            className="line-loader"
            width={size}
            height={size}
            viewBox="0 0 28 28"
            fill="none"
            aria-hidden
        >
            <circle cx="14" cy="14" r="11" />
        </svg>
    );
}

export function InlineLoader() {
    return (
        <div
            className="flex justify-center py-32"
            role="status"
            aria-live="polite"
            aria-busy="true"
            aria-label={texts.app.loading}
        >
            <LineLoader />
        </div>
    );
}
