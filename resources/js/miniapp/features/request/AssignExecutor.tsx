import { useQuery } from '@tanstack/react-query';
import { CellSimple, Typography } from '@maxhub/max-ui';
import { listExecutors } from '@/api/organization';
import { texts } from '@/app/texts';
import { Section } from '@/components/Section';
import { MutationError } from './MutationError';
import type { StaffRequestActionMutations } from './useRequest';

export function AssignExecutor({
    currentName,
    action,
    disabled,
}: {
    currentName?: string | null;
    action: StaffRequestActionMutations['assign'];
    disabled: boolean;
}) {
    const executors = useQuery({
        queryKey: ['organization', 'executors'],
        queryFn: ({ signal }) => listExecutors(signal),
    });

    return (
        <div className="flex min-w-0 flex-col gap-12">
            <Section title={texts.request.assign}>
                <CellSimple
                    separator
                    title={texts.request.executor}
                    subtitle={currentName ?? texts.request.noExecutor}
                />
                {(executors.data ?? []).map((executor) => (
                    <CellSimple
                        key={executor.id}
                        separator
                        title={executor.name}
                        subtitle={[executor.specialty, executor.phone].filter(Boolean).join(' · ') || undefined}
                        disabled={disabled || action.isPending}
                        onClick={() => action.mutate(executor.id)}
                    />
                ))}
            </Section>
            {executors.data?.length === 0 && (
                <Typography.Body variant="small" className="px-16 text-muted">
                    {texts.request.assignEmpty}
                </Typography.Body>
            )}
            <MutationError error={action.error ?? executors.error} />
        </div>
    );
}
