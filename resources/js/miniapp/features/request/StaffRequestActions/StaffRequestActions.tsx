import { useEffect, useMemo, useRef, useState, type ChangeEvent, type FormEvent } from 'react';
import { Button } from '@maxhub/max-ui';
import type { RequestCard } from '@/api/types';
import { texts } from '@/app/texts';
import { CompactNote } from '@/components/CompactNote';
import { SettingsField } from '@/components/SettingsField';
import { transitionLabel } from '@/lib/status';
import { AssignExecutor } from '@/features/request/AssignExecutor';
import { MutationError } from '@/features/request/MutationError';
import {
    useStaffRequestActions,
    type StaffRequestActionMutations,
} from './StaffRequestActions.model';

export function StaffRequestActions({ card }: { card: RequestCard }) {
    const actions = useStaffRequestActions(card.id);
    const [comment, setComment] = useState('');
    const [redirectOpen, setRedirectOpen] = useState(false);
    const busy =
        actions.status.isPending ||
        actions.assign.isPending ||
        actions.redirect.isPending ||
        actions.close.isPending ||
        actions.addComment.isPending;
    const canAssign =
        card.status === 'new' ||
        card.status === 'assigned' ||
        card.status === 'in_progress' ||
        card.status === 'returned';
    // assigned только через POST /assign, не PATCH /status
    const directTransitions = card.allowed_transitions.filter((status) => status === 'in_progress');

    return (
        <section className="request-actions flex min-w-0 flex-col gap-24">
            {canAssign && (
                <AssignExecutor currentName={card.executor?.name} action={actions.assign} disabled={busy} />
            )}

            {directTransitions.length > 0 && (
                <div className="flex min-w-0 flex-col gap-8">
                    {directTransitions.map((status) => (
                        <Button
                            key={status}
                            size="medium"
                            variant="primary"
                            loading={actions.status.isPending}
                            disabled={busy}
                            onClick={() =>
                                actions.status.mutate(
                                    { status, comment: comment.trim() || undefined },
                                    { onSuccess: () => setComment('') },
                                )
                            }
                        >
                            {transitionLabel(status, card.status)}
                        </Button>
                    ))}
                    <MutationError error={actions.status.error} />
                </div>
            )}

            {card.allowed_transitions.includes('done') && (
                <CloseRequestForm action={actions.close} disabled={busy} />
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
                        <RedirectRequestForm
                            action={actions.redirect}
                            disabled={busy}
                            onSuccess={() => setRedirectOpen(false)}
                        />
                    )}
                </div>
            )}

            <div className="flex min-w-0 flex-col gap-8">
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
                <MutationError error={actions.addComment.error} />
            </div>
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
                                className="h-128 w-128 rounded-card object-cover"
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
            <MutationError error={action.error} />
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
            <MutationError error={action.error} />
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
