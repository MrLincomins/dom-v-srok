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
