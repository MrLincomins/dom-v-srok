import {
    useEffect,
    useRef,
    useState,
    type ChangeEvent,
    type Dispatch,
    type FormEvent,
    type SetStateAction,
} from 'react';
import { Button } from '@maxhub/max-ui';
import type { RequestCard } from '@/api/types';
import { texts } from '@/app/texts';
import { CellHeading } from '@/components/CellHeading';
import { CompactNote } from '@/components/CompactNote';
import { ClearMark } from '@/components/FieldClear';
import { Notice, useNoticeState } from '@/components/Notice';
import { PhotoGrid } from '@/components/PhotoGrid';
import { PhoneField } from '@/components/PhoneField';
import { SettingsField } from '@/components/SettingsField';
import { describeError } from '@/lib/describeError';
import { usePhotoPreviews } from '@/lib/photoPreviews';
import { isCompletePhone, toStoredPhone } from '@/lib/phone';
import { staffPrimaryAction } from '@/lib/status';
import { AssignExecutor } from '@/features/request/AssignExecutor';
import { MutationError } from '@/features/request/MutationError';
import {
    useStaffRequestActions,
    type StaffRequestActionMutations,
} from './StaffRequestActions.model';

export function StaffRequestActions({ card }: { card: RequestCard }) {
    const actions = useStaffRequestActions(card.id);
    const notice = useNoticeState();
    const [comment, setComment] = useState('');
    const [redirectOpen, setRedirectOpen] = useState(false);
    const [finishOpen, setFinishOpen] = useState(false);
    const [finishComment, setFinishComment] = useState('');
    const [finishPhotos, setFinishPhotos] = useState<File[]>([]);
    const [finishPhotoError, setFinishPhotoError] = useState<string | null>(null);
    const busy =
        actions.status.isPending ||
        actions.assign.isPending ||
        actions.redirect.isPending ||
        actions.close.isPending ||
        actions.addComment.isPending;
    const primary = staffPrimaryAction(card.status, Boolean(card.executor));
    const canFinish =
        card.allowed_transitions.includes('done') &&
        (primary === 'finish' || (card.status === 'returned' && Boolean(card.executor)));
    const failed =
        [actions.status, actions.assign, actions.redirect, actions.close, actions.addComment].find(
            (action) => action.error,
        ) ?? null;

    useEffect(() => {
        if (failed?.error) notice.show(describeError(failed.error, failed.failureCount), 'error');
    }, [failed, notice.show]);

    return (
        <section className="request-actions flex min-w-0 flex-col gap-24">
            {primary === 'assign' && (
                <AssignExecutor
                    currentName={card.executor?.name}
                    action={actions.assign}
                    disabled={busy}
                    onAssigned={() => notice.show(texts.request.executorAssigned, 'success')}
                />
            )}

            {primary === 'start' && card.allowed_transitions.includes('in_progress') && (
                <Button
                    size="medium"
                    variant="primary"
                    stretched
                    loading={actions.status.isPending}
                    disabled={busy}
                    onClick={() =>
                        actions.status.mutate(
                            { status: 'in_progress' },
                            { onSuccess: () => notice.show(texts.request.takenWork, 'success') },
                        )
                    }
                >
                    {texts.request.takeWork}
                </Button>
            )}

            {canFinish && (
                <div className="flex min-w-0 flex-col">
                    <Button
                        size="medium"
                        variant="primary"
                        stretched
                        className="btn-done"
                        disabled={busy}
                        aria-expanded={finishOpen}
                        onClick={() => setFinishOpen((open) => !open)}
                    >
                        {texts.request.finish}
                    </Button>
                    <div
                        className={`section-fold${finishOpen ? ' is-open' : ''}`}
                        aria-hidden={!finishOpen}
                        inert={!finishOpen}
                    >
                        <div className="section-fold-inner">
                            <div className="section-fold-body">
                                <CloseRequestForm
                                    action={actions.close}
                                    disabled={busy}
                                    comment={finishComment}
                                    photos={finishPhotos}
                                    photoError={finishPhotoError}
                                    onCommentChange={setFinishComment}
                                    onPhotosChange={setFinishPhotos}
                                    onPhotoErrorChange={setFinishPhotoError}
                                    onDone={() => notice.show(texts.request.workDone, 'success')}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {card.allowed_transitions.includes('redirected') && (
                <div className="flex min-w-0 flex-col gap-8">
                    <Button
                        size="medium"
                        variant="secondary"
                        disabled={busy}
                        onClick={() => setRedirectOpen((open) => !open)}
                        aria-expanded={redirectOpen}
                    >
                        {texts.request.actions.redirect}
                    </Button>
                    {redirectOpen && (
                        <div className="open-pulse">
                            <RedirectRequestForm
                                action={actions.redirect}
                                disabled={busy}
                                onSuccess={() => {
                                    setRedirectOpen(false);
                                    notice.show(texts.request.requestRedirected, 'success');
                                }}
                            />
                        </div>
                    )}
                </div>
            )}

            <div className="request-block flex min-w-0 flex-col gap-8">
                <CellHeading>{texts.request.commentTitle}</CellHeading>
                <div className="request-templates" role="group" aria-label={texts.request.templates}>
                    {texts.request.templateOptions.map((template) => (
                        <button
                            key={template}
                            type="button"
                            className={`ios-tab${comment === template ? ' is-active' : ''}`}
                            disabled={busy}
                            onClick={() => setComment(template)}
                        >
                            {template}
                        </button>
                    ))}
                </div>
                <CompactNote
                    label={texts.request.actions.comment}
                    placeholder={texts.request.commentPlaceholder}
                    value={comment}
                    onChange={(event) => setComment(event.target.value)}
                />
                <Button
                    size="medium"
                    variant="secondary"
                    stretched
                    disabled={busy || comment.trim() === ''}
                    loading={actions.addComment.isPending}
                    onClick={() =>
                        actions.addComment.mutate(comment.trim(), {
                            onSuccess: () => {
                                setComment('');
                                notice.show(texts.request.commentAdded, 'success');
                            },
                        })
                    }
                >
                    {texts.request.actions.comment}
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

function CloseRequestForm({
    action,
    disabled,
    comment,
    photos,
    photoError,
    onCommentChange,
    onPhotosChange,
    onPhotoErrorChange,
    onDone,
}: {
    action: StaffRequestActionMutations['close'];
    disabled: boolean;
    comment: string;
    photos: File[];
    photoError: string | null;
    onCommentChange: Dispatch<SetStateAction<string>>;
    onPhotosChange: Dispatch<SetStateAction<File[]>>;
    onPhotoErrorChange: Dispatch<SetStateAction<string | null>>;
    onDone: () => void;
}) {
    const photoInput = useRef<HTMLInputElement>(null);
    const previews = usePhotoPreviews(photos);

    const selectPhotos = (event: ChangeEvent<HTMLInputElement>) => {
        const selected = Array.from(event.target.files ?? []);
        onPhotosChange((current) => {
            const next = [...current, ...selected];
            onPhotoErrorChange(next.length > 5 ? texts.request.photoLimit : null);
            return next.slice(0, 5);
        });
        event.target.value = '';
    };

    return (
        <form
            className="flex flex-col gap-8"
            onSubmit={(event) => {
                event.preventDefault();
                action.mutate(
                    { comment: comment.trim(), photos },
                    {
                        onSuccess: () => {
                            onCommentChange('');
                            onPhotosChange([]);
                            onPhotoErrorChange(null);
                            onDone();
                        },
                    },
                );
            }}
        >
            <CompactNote
                label={texts.request.finishTitle}
                placeholder={texts.request.finishComment}
                value={comment}
                onChange={(event) => onCommentChange(event.target.value)}
            />
            <input
                ref={photoInput}
                className="sr-only"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                multiple
                onChange={selectPhotos}
            />
            {previews.length > 0 && (
                <PhotoGrid label="Выбранные фотографии">
                    {previews.map((preview, index) => (
                        <div key={preview.url} className="relative">
                            <img
                                src={preview.url}
                                alt={preview.name}
                                className="request-photo"
                            />
                            <button
                                type="button"
                                className="photo-remove"
                                onClick={() =>
                                    onPhotosChange((current) =>
                                        current.filter((_, currentIndex) => currentIndex !== index),
                                    )
                                }
                                aria-label={texts.request.removePhoto(preview.name)}
                            >
                                <ClearMark />
                            </button>
                        </div>
                    ))}
                </PhotoGrid>
            )}
            {photoError && <MutationError error={photoError} />}
            <div className="request-action-row">
                <Button
                    size="medium"
                    variant="secondary"
                    type="button"
                    stretched
                    onClick={() => photoInput.current?.click()}
                >
                    {texts.request.addPhoto}
                </Button>
                <Button
                    type="submit"
                    variant="primary"
                    size="medium"
                    stretched
                    className="btn-done"
                    loading={action.isPending}
                    disabled={disabled}
                >
                    {texts.request.finishSubmit}
                </Button>
            </div>
        </form>
    );
}

function RedirectRequestForm({
    action,
    disabled,
    onSuccess,
}: {
    action: StaffRequestActionMutations['redirect'];
    disabled: boolean;
    onSuccess: () => void;
}) {
    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [phoneError, setPhoneError] = useState('');
    const [note, setNote] = useState('');

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (phone.length > 0 && !isCompletePhone(phone)) {
            setPhoneError(texts.organization.phoneError);
            return;
        }
        action.mutate(
            {
                name: name.trim(),
                phone: toStoredPhone(phone) ?? undefined,
                note: note.trim() || undefined,
            },
            { onSuccess },
        );
    };

    return (
        <form className="flex flex-col gap-8" onSubmit={submit}>
            <SettingsField
                label={texts.request.redirectName}
                value={name}
                required
                onChange={(event) => setName(event.target.value)}
            />
            <PhoneField
                label={texts.request.redirectPhone}
                value={phone}
                error={phoneError}
                onChange={(next) => {
                    setPhone(next);
                    if (phoneError) setPhoneError('');
                }}
            />
            <CompactNote
                placeholder={texts.request.redirectNote}
                value={note}
                onChange={(event) => setNote(event.target.value)}
            />
            <Button
                type="submit"
                size="medium"
                variant="destructive"
                loading={action.isPending}
                disabled={disabled || name.trim() === ''}
            >
                {texts.request.redirectSubmit}
            </Button>
        </form>
    );
}

