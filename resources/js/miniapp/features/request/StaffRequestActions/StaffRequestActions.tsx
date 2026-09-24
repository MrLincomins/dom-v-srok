import { useEffect, useMemo, useRef, useState, type ChangeEvent, type FormEvent } from 'react';
import { Button } from '@maxhub/max-ui';
import type { RequestCard } from '@/api/types';
import { texts } from '@/app/texts';
import { CellHeading } from '@/components/CellHeading';
import { CompactNote } from '@/components/CompactNote';
import { Notice } from '@/components/Notice';
import { useErrorNotice } from '@/components/Notice/useErrorNotice';
import { SettingsField } from '@/components/SettingsField';
import { staffPrimaryAction } from '@/lib/status';
import { AssignExecutor } from '@/features/request/AssignExecutor';
import { MutationError } from '@/features/request/MutationError';
import {
    useStaffRequestActions,
    type StaffRequestActionMutations,
} from './StaffRequestActions.model';

export function StaffRequestActions({ card }: { card: RequestCard }) {
    const actions = useStaffRequestActions(card.id);
    const { text: noticeText, show: showNotice, clear: clearNotice } = useErrorNotice();
    const [comment, setComment] = useState('');
    const [redirectOpen, setRedirectOpen] = useState(false);
    const [finishOpen, setFinishOpen] = useState(false);
    const busy =
        actions.status.isPending ||
        actions.assign.isPending ||
        actions.redirect.isPending ||
        actions.close.isPending ||
        actions.addComment.isPending;
    const primary = staffPrimaryAction(card.status);
    const failed =
        [actions.status, actions.assign, actions.redirect, actions.close, actions.addComment].find(
            (action) => action.error,
        ) ?? null;

    useEffect(() => {
        if (failed?.error) showNotice(failed.error, failed.failureCount);
    }, [failed, showNotice]);

    return (
        <section className="request-actions flex min-w-0 flex-col gap-24">
            {primary === 'assign' && (
                <AssignExecutor currentName={card.executor?.name} action={actions.assign} disabled={busy} />
            )}

            {primary === 'start' && card.allowed_transitions.includes('in_progress') && (
                <Button
                    size="medium"
                    variant="primary"
                    stretched
                    loading={actions.status.isPending}
                    disabled={busy}
                    onClick={() => actions.status.mutate({ status: 'in_progress' })}
                >
                    {texts.request.takeWork}
                </Button>
            )}

            {primary === 'finish' && card.allowed_transitions.includes('done') && (
                <div className="flex min-w-0 flex-col gap-8">
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
                    {finishOpen && (
                        <div className="open-pulse">
                            <CloseRequestForm action={actions.close} disabled={busy} />
                        </div>
                    )}
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
                                onSuccess={() => setRedirectOpen(false)}
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
                        actions.addComment.mutate(comment.trim(), { onSuccess: () => setComment('') })
                    }
                >
                    {texts.request.actions.comment}
                </Button>
            </div>
            <Notice text={noticeText} onGone={clearNotice} />
        </section>
    );
}

function CloseRequestForm({
    action,
    disabled,
}: {
    action: StaffRequestActionMutations['close'];
    disabled: boolean;
}) {
    const [comment, setComment] = useState('');
    const [photos, setPhotos] = useState<File[]>([]);
    const [photoError, setPhotoError] = useState<string | null>(null);
    const photoInput = useRef<HTMLInputElement>(null);
    const previews = usePhotoPreviews(photos);

    const selectPhotos = (event: ChangeEvent<HTMLInputElement>) => {
        const selected = Array.from(event.target.files ?? []);
        setPhotoError(selected.length > 5 ? texts.request.photoLimit : null);
        setPhotos(selected.slice(0, 5));
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
                            setComment('');
                            setPhotos([]);
                            setPhotoError(null);
                        },
                    },
                );
            }}
        >
            <CompactNote
                label={texts.request.finishTitle}
                placeholder={texts.request.finishComment}
                value={comment}
                onChange={(event) => setComment(event.target.value)}
            />
            <input
                ref={photoInput}
                className="sr-only"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                multiple
                onChange={selectPhotos}
            />
            {photoError && <MutationError error={photoError} />}
            {previews.length > 0 && (
                <div
                    className="flex snap-x snap-mandatory gap-12 overflow-x-auto scroll-px-16"
                    aria-label="Выбранные фотографии"
                >
                    {previews.map((preview, index) => (
                        <div key={preview.url} className="relative shrink-0 snap-start">
                            <img
                                src={preview.url}
                                alt={preview.name}
                                className="request-photo"
                            />
                            <button
                                type="button"
                                className="absolute top-8 right-8 flex h-32 w-32 items-center justify-center rounded-full bg-black/70 text-[16px] text-white"
                                onClick={() =>
                                    setPhotos((current) =>
                                        current.filter((_, currentIndex) => currentIndex !== index),
                                    )
                                }
                                aria-label={texts.request.removePhoto(preview.name)}
                            >
                                ×
                            </button>
                        </div>
                    ))}
                </div>
            )}
            <div className="request-action-row">
                <Button
                    size="medium"
                    variant="secondary"
                    type="button"
                    onClick={() => photoInput.current?.click()}
                >
                    {texts.request.addPhoto}
                </Button>
                <Button
                    type="submit"
                    variant="primary"
                    size="medium"
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
    const [note, setNote] = useState('');

    const submit = (event: FormEvent) => {
        event.preventDefault();
        action.mutate(
            {
                name: name.trim(),
                phone: phone.trim() || undefined,
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
            <SettingsField
                label={texts.request.redirectPhone}
                value={phone}
                inputMode="tel"
                onChange={(event) => setPhone(event.target.value)}
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

function usePhotoPreviews(files: File[]): Array<{ name: string; url: string }> {
    const previews = useMemo(
        () => files.map((file) => ({ name: file.name, url: URL.createObjectURL(file) })),
        [files],
    );

    useEffect(
        () => () => {
            previews.forEach((preview) => URL.revokeObjectURL(preview.url));
        },
        [previews],
    );

    return previews;
}
