export function ListSkeleton({ rows = 6 }: { rows?: number }) {
    return (
        <div className="flex flex-col gap-12" aria-busy="true" aria-label="Загрузка">
            {Array.from({ length: rows }, (_, i) => (
                <div key={i} className="skeleton-block h-[76px] rounded-card" />
            ))}
        </div>
    );
}
