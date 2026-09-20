import { useState } from 'react';
import { Button, CellHeader, Textarea } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { MutationError } from './MutationError';
import { useResidentRequestActions } from './useRequest';

export function ResidentRequestActions({ requestId }: { requestId: number }) {
    const actions = useResidentRequestActions(requestId);
    const [comment, setComment] = useState('');
    const [choice, setChoice] = useState<'yes' | 'no' | null>(null);
    const busy = actions.confirm.isPending;

    return (
        <section className="flex min-w-0 flex-col gap-16">
            <CellHeader titleStyle="caps">{texts.request.resolvedTitle}</CellHeader>
            <Textarea
                placeholder={texts.request.returnComment}
                aria-label={texts.request.returnComment}
                value={comment}
                onChange={(event) => setComment(event.target.value)}
                rows={3}
            />
            <div className="grid grid-cols-2 gap-12">
                <Button
                    size="large"
                    variant="primary"
                    stretched
                    loading={busy && choice === 'yes'}
                    disabled={busy}
                    onClick={() => {
                        setChoice('yes');
                        actions.confirm.mutate({ resolved: true }, { onSettled: () => setChoice(null) });
                    }}
                >
                    {texts.request.confirmYes}
                </Button>
                <Button
                    size="large"
                    variant="secondary"
                    stretched
                    loading={busy && choice === 'no'}
                    disabled={busy}
                    onClick={() => {
                        setChoice('no');
                        actions.confirm.mutate(
                            {
                                resolved: false,
                                comment: comment.trim() || undefined,
                            },
                            { onSettled: () => setChoice(null) },
                        );
                    }}
                >
                    {texts.request.confirmNo}
                </Button>
            </div>
            <MutationError error={actions.confirm.error} />
        </section>
    );
}
