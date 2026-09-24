import { useEffect, useState } from 'react';
import { Button } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { CompactNote } from '@/components/CompactNote';
import { Notice, useNoticeState } from '@/components/Notice';
import { describeError } from '@/lib/describeError';
import { useResidentRequestActions } from './ResidentRequestActions.model';

export function ResidentRequestActions({ requestId }: { requestId: number }) {
    const actions = useResidentRequestActions(requestId);
    const notice = useNoticeState();
    const [comment, setComment] = useState('');
    const [choice, setChoice] = useState<'yes' | 'no' | null>(null);
    const busy = actions.confirm.isPending;

    useEffect(() => {
        if (actions.confirm.error) {
            notice.show(describeError(actions.confirm.error, actions.confirm.failureCount), 'error');
        }
    }, [actions.confirm.error, actions.confirm.failureCount, notice.show]);

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
                    stretched
                    className="btn-done"
                    loading={busy && choice === 'yes'}
                    disabled={busy}
                    onClick={() => {
                        setChoice('yes');
                        actions.confirm.mutate(
                            {
                                resolved: true,
                                comment: comment.trim() || undefined,
                            },
                            {
                                onSuccess: () => notice.show(texts.request.confirmedDone, 'success'),
                                onSettled: () => setChoice(null),
                            },
                        );
                    }}
                >
                    {texts.request.confirmYes}
                </Button>
                <Button
                    size="medium"
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
                            {
                                onSuccess: () => notice.show(texts.request.returnedToWork, 'success'),
                                onSettled: () => setChoice(null),
                            },
                        );
                    }}
                >
                    {texts.request.confirmNo}
                </Button>
            </div>
            <Notice
                text={notice.text}
                tone={notice.tone}
                revision={notice.revision}
                onGone={notice.clear}
            />
        </section>
    );
}
