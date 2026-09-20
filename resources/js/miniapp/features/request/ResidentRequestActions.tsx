import { useState } from 'react';
import { Button } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { CompactNote } from '@/components/CompactNote';
import { MutationError } from './MutationError';
import { useResidentRequestActions } from './useRequest';

export function ResidentRequestActions({ requestId }: { requestId: number }) {
    const actions = useResidentRequestActions(requestId);
    const [comment, setComment] = useState('');
    const [choice, setChoice] = useState<'yes' | 'no' | null>(null);
    const busy = actions.confirm.isPending;

    return (
        <section className="request-actions flex min-w-0 flex-col gap-8">
            <CompactNote
                label={texts.request.resolvedTitle}
                placeholder={texts.request.returnComment}
                value={comment}
                onChange={(event) => setComment(event.target.value)}
            />
            <div className="request-action-row">
                <Button
                    size="medium"
                    variant="primary"
                    className="btn-done"
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
                    size="medium"
                    variant="secondary"
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
