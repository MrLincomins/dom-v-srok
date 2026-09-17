export function ListSkeleton({ rows = 6 }: { rows?: number }) {
    return (
        <div className="flex flex-col gap-2 p-4" aria-busy="true" aria-label="Загрузка">
            {Array.from({ length: rows }, (_, i) => (
                <div key={i} className="h-[72px] animate-pulse rounded-xl bg-surface opacity-70" />
            ))}
        </div>
    );
}
