export function ListSkeleton({ rows = 6 }: { rows?: number }) {
    return (
        <div className="flex flex-col gap-12" aria-busy="true" aria-label="Загрузка">
            {Array.from({ length: rows }, (_, i) => (
                <div key={i} className="skeleton-shimmer h-[88px] rounded-card bg-surface shadow-card" />
            ))}
        </div>
    );
}
