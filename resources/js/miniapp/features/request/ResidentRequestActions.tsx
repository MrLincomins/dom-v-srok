import { useState } from 'react';
import { Button, Textarea, Typography } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { MutationError } from './MutationError';
import { useResidentRequestActions } from './useRequest';

export function ResidentRequestActions({ requestId }: { requestId: number }) {
    const actions = useResidentRequestActions(requestId);
    const [comment, setComment] = useState('');
    const [choice, setChoice] = useState<'yes' | 'no' | null>(null);
    const busy = actions.confirm.isPending;

    return (
        <section className="app-card enter flex flex-col gap-12 p-16">
            <Typography.Title variant="small-strong">{texts.request.resolvedTitle}</Typography.Title>
            <Textarea
                placeholder={texts.request.returnComment}
                aria-label={texts.request.returnComment}
                value={comment}
                onChange={(event) => setComment(event.target.value)}
                rows={2}
            />
            <div className="grid grid-cols-2 gap-8">
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
