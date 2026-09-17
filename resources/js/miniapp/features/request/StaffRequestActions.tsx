import { useEffect, useMemo, useState, type ChangeEvent, type FormEvent } from 'react';
import { Button, Input, Textarea, Typography } from '@maxhub/max-ui';
import type { RequestCard } from '@/api/types';
import { texts } from '@/app/texts';
import { transitionLabel } from '@/lib/status';
import { MutationError } from './MutationError';
import { useStaffRequestActions, type StaffRequestActionMutations } from './useRequest';

export function StaffRequestActions({ card }: { card: RequestCard }) {
    const actions = useStaffRequestActions(card.id);
    const [comment, setComment] = useState('');
    const [redirectOpen, setRedirectOpen] = useState(false);
    const busy =
        actions.status.isPending ||
        actions.redirect.isPending ||
        actions.close.isPending ||
        actions.addComment.isPending;
    const directTransitions = card.allowed_transitions.filter((status) => status === 'in_progress');

    return (
        <section className="app-card enter flex flex-col gap-12 p-16">
            <Typography.Title variant="small-strong">{texts.request.actionTitle}</Typography.Title>

            {directTransitions.length > 0 && (
                <>
                    <div className="flex flex-wrap gap-8">
                        {directTransitions.map((status) => (
                            <Button
                                key={status}
                                size="medium"
                                variant="secondary"
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
                    </div>
                    <MutationError error={actions.status.error} />
                </>
            )}

            {card.allowed_transitions.includes('done') && (
                <CloseRequestForm action={actions.close} disabled={busy} />
            )}

            {card.allowed_transitions.includes('redirected') && (
                <>
                    <Button
                        size="medium"
                        variant="ghost"
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
                </>
            )}

            <Textarea
                placeholder={texts.request.commentPlaceholder}
                aria-label={texts.request.commentPlaceholder}
                value={comment}
                onChange={(event) => setComment(event.target.value)}
                rows={2}
            />
            <Button
                size="small"
                variant="ghost"
                disabled={busy || comment.trim() === ''}
                loading={actions.addComment.isPending}
                onClick={() => actions.addComment.mutate(comment.trim(), { onSuccess: () => setComment('') })}
            >
                {texts.request.actions.comment}
            </Button>
            <MutationError error={actions.addComment.error} />
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
    const previews = usePhotoPreviews(photos);

    const selectPhotos = (event: ChangeEvent<HTMLInputElement>) => {
        const selected = Array.from(event.target.files ?? []);
        setPhotoError(selected.length > 5 ? texts.request.photoLimit : null);
        setPhotos(selected.slice(0, 5));
        event.target.value = '';
    };

    return (
        <form
            className="flex flex-col gap-12 rounded-xl bg-page-secondary p-12"
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
            <Typography.Body variant="small" className="font-medium">
                {texts.request.finishTitle}
            </Typography.Body>
            <Textarea
                placeholder={texts.request.finishComment}
                aria-label={texts.request.finishComment}
                value={comment}
                onChange={(event) => setComment(event.target.value)}
                rows={2}
            />
            <label className="cursor-pointer rounded-xl border border-divider bg-surface px-12 py-8 text-center text-[14px] font-medium text-accent">
                {texts.request.addPhoto}
                <input
                    className="sr-only"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    onChange={selectPhotos}
                />
            </label>
            {photoError && (
                <Typography.Body variant="small" className="text-negative" role="alert">
                    {photoError}
                </Typography.Body>
            )}
            {previews.length > 0 && (
                <>
                    <Typography.Body variant="small" className="text-muted">
                        {texts.request.selectedPhotos(previews.length)}
                    </Typography.Body>
                    <div
                        className="flex snap-x snap-mandatory gap-8 overflow-x-auto scroll-px-8"
                        aria-label="Выбранные фотографии"
                    >
                        {previews.map((preview, index) => (
                            <div key={preview.url} className="relative shrink-0 snap-start">
                                <img
                                    src={preview.url}
                                    alt={preview.name}
                                    className="h-80 w-80 rounded-lg object-cover"
                                />
                                <button
                                    type="button"
                                    className="absolute top-2 right-2 flex h-24 w-24 items-center justify-center rounded-full bg-black/70 text-[16px] text-white"
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
                </>
            )}
            <Button
                type="submit"
                variant="primary"
                size="medium"
                stretched
                loading={action.isPending}
                disabled={disabled}
            >
                {texts.request.finishSubmit}
            </Button>
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
            <Input
                placeholder={texts.request.redirectName}
                aria-label={texts.request.redirectName}
                value={name}
                onChange={(event) => setName(event.target.value)}
                required
            />
            <Input
                placeholder={texts.request.redirectPhone}
                aria-label={texts.request.redirectPhone}
                value={phone}
                onChange={(event) => setPhone(event.target.value)}
                inputMode="tel"
            />
            <Textarea
                placeholder={texts.request.redirectNote}
                aria-label={texts.request.redirectNote}
                value={note}
                onChange={(event) => setNote(event.target.value)}
                rows={2}
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
