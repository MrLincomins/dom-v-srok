import { useEffect, useMemo, useRef, useState, type ChangeEvent } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router';
import { Button, CellSimple } from '@maxhub/max-ui';
import { listCategories } from '@/api/catalog';
import { createRequest } from '@/api/requests';
import type { Category } from '@/api/types';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { CompactNote } from '@/components/CompactNote';
import { EmptyState } from '@/components/EmptyState';
import { InlineLoader } from '@/components/LineLoader';
import { ErrorState } from '@/components/ErrorState';
import { RequestSection } from '@/components/RequestSection';
import { Screen } from '@/components/Screen';
import { SettingsField } from '@/components/SettingsField';
import { MutationError } from '@/features/request/MutationError';

type Step = 'emergency' | 'category' | 'subcategory' | 'details' | 'address' | 'preview';

const STEPS: Step[] = ['emergency', 'category', 'subcategory', 'details', 'address', 'preview'];

export function CreateRequestPage() {
    const navigate = useNavigate();
    const { user } = useAuth();
    const catalog = useQuery({
        queryKey: ['catalog', 'categories'],
        queryFn: ({ signal }) => listCategories(signal),
    });
    const [step, setStep] = useState<Step>('emergency');
    const [parent, setParent] = useState<Category | null>(null);
    const [category, setCategory] = useState<Category | null>(null);
    const [description, setDescription] = useState('');
    const [photos, setPhotos] = useState<File[]>([]);
    const [photoError, setPhotoError] = useState<string | null>(null);
    const [entrance, setEntrance] = useState(user?.entrance ? String(user.entrance) : '');
    const [flat, setFlat] = useState(user?.flat ?? '');
    const [detailsError, setDetailsError] = useState<string | null>(null);

    const create = useMutation({
        mutationFn: (unsure: boolean) =>
            createRequest({
                category_id: category?.id ?? 0,
                description: description.trim(),
                entrance: Number.parseInt(entrance, 10) || null,
                flat: flat.trim() || null,
                unsure,
                photos,
            }),
        onSuccess: (card) => navigate(`/requests/${card.id}`, { replace: true }),
    });

    const roots = useMemo(() => (catalog.data ?? []).filter((item) => !item.is_emergency), [catalog.data]);

    if (!user?.house) {
        return (
            <Screen
                title={texts.home.createRequest}
                backTo="/"
                contentClassName="flex min-w-0 flex-col gap-16"
            >
                <EmptyState text={texts.resident.noHouse} />
            </Screen>
        );
    }

    const go = (next: Step) => setStep(next);
    const backStep = () => {
        if (step === 'emergency') {
            navigate('/');
            return;
        }
        if (step === 'details') {
            go(parent?.children?.length ? 'subcategory' : 'category');
            return;
        }
        const index = STEPS.indexOf(step);
        const previous = STEPS[index - 1];
        if (previous) go(previous);
    };

    const title =
        step === 'emergency'
            ? texts.resident.create.emergencyTitle
            : step === 'category' || step === 'subcategory'
              ? texts.resident.create.category
              : step === 'details'
                ? texts.resident.create.details
                : step === 'address'
                  ? texts.resident.create.address
                  : texts.resident.create.preview;

    return (
        <Screen title={title} backTo="/" contentClassName="flex min-w-0 flex-col gap-16">
            {step !== 'emergency' && (
                <Button type="button" variant="ghost" size="small" onClick={backStep}>
                    {texts.app.back}
                </Button>
            )}

            {step === 'emergency' && (
                <EmergencyStep phone={user.organization?.phone_ads} onSafe={() => go('category')} />
            )}

            {step === 'category' && (
                <CategoryStep
                    loading={catalog.isPending}
                    error={catalog.error}
                    onRetry={() => void catalog.refetch()}
                    items={roots}
                    onPick={(item) => {
                        if (item.children?.length) {
                            setParent(item);
                            setCategory(null);
                            go('subcategory');
                            return;
                        }
                        setParent(null);
                        setCategory(item);
                        go('details');
                    }}
                />
            )}

            {step === 'subcategory' && parent && (
                <CategoryStep
                    items={(parent.children ?? []).filter((item) => !item.is_emergency)}
                    onPick={(item) => {
                        setCategory(item);
                        go('details');
                    }}
                />
            )}

            {step === 'details' && (
                <DetailsStep
                    description={description}
                    photos={photos}
                    photoError={photoError}
                    error={detailsError}
                    onDescription={(value) => {
                        setDescription(value);
                        if (detailsError) setDetailsError(null);
                    }}
                    onPhotos={(files) => {
                        setPhotoError(files.length > 5 ? texts.request.photoLimit : null);
                        setPhotos(files.slice(0, 5));
                    }}
                    onNext={() => {
                        if (description.trim() === '' && photos.length === 0) {
                            setDetailsError(texts.resident.create.detailsRequired);
                            return;
                        }
                        go('address');
                    }}
                />
            )}

            {step === 'address' && (
                <AddressStep
                    entrance={entrance}
                    flat={flat}
                    remembered={Boolean(user.entrance || user.flat)}
                    onEntrance={setEntrance}
                    onFlat={setFlat}
                    onSame={() => {
                        setEntrance(user.entrance ? String(user.entrance) : entrance);
                        setFlat(user.flat ?? flat);
                        go('preview');
                    }}
                    onNext={() => go('preview')}
                />
            )}

            {step === 'preview' && category && (
                <PreviewStep
                    category={category}
                    description={description}
                    photos={photos.length}
                    entrance={entrance}
                    flat={flat}
                    address={user.house.address}
                    loading={create.isPending}
                    error={create.error}
                    onSend={(unsure) => create.mutate(unsure)}
                />
            )}
        </Screen>
    );
}

function EmergencyStep({ phone, onSafe }: { phone?: string | null; onSafe: () => void }) {
    const [emergency, setEmergency] = useState(false);

    return (
        <div className="flex min-w-0 flex-col gap-16">
            <p className="settings-hint px-0">{texts.resident.create.emergencyHint}</p>
            <div className="cabinet-tiles">
                <button type="button" className="cabinet-tile is-create" onClick={() => setEmergency(true)}>
                    <span className="cabinet-tile-title">{texts.resident.create.emergencyYes}</span>
                </button>
                <button type="button" className="cabinet-tile is-current" onClick={onSafe}>
                    <span className="cabinet-tile-title">{texts.resident.create.emergencyNo}</span>
                </button>
            </div>
            {emergency &&
                (phone ? (
                    <a className="cabinet-tile is-house" href={`tel:${phone}`}>
                        <span className="cabinet-tile-title">{texts.resident.create.callAds}</span>
                        <span className="cabinet-tile-hint">{phone}</span>
                    </a>
                ) : (
                    <EmptyState text={texts.resident.create.noAds} />
                ))}
        </div>
    );
}

function CategoryStep({
    items,
    loading,
    error,
    onRetry,
    onPick,
}: {
    items: Category[];
    loading?: boolean;
    error?: unknown;
    onRetry?: () => void;
    onPick: (item: Category) => void;
}) {
    return (
        <div className="flex min-w-0 flex-col gap-8">
            {loading ? <InlineLoader /> : null}
            {error ? <ErrorState error={error} onRetry={onRetry} /> : null}
            {!loading && !error && (
                <RequestSection>
                    {items.map((item) => (
                        <CellSimple
                            key={item.id}
                            separator
                            title={item.name}
                            subtitle={item.advice ?? undefined}
                            onClick={() => onPick(item)}
                        />
                    ))}
                </RequestSection>
            )}
        </div>
    );
}

function DetailsStep({
    description,
    photos,
    photoError,
    error,
    onDescription,
    onPhotos,
    onNext,
}: {
    description: string;
    photos: File[];
    photoError: string | null;
    error: string | null;
    onDescription: (value: string) => void;
    onPhotos: (files: File[]) => void;
    onNext: () => void;
}) {
    const photoInput = useRef<HTMLInputElement>(null);
    const previews = usePhotoPreviews(photos);

    return (
        <div className="flex min-w-0 flex-col gap-16">
            <p className="settings-hint px-0">{texts.resident.create.detailsHint}</p>
            <CompactNote
                placeholder={texts.resident.create.detailsPlaceholder}
                value={description}
                onChange={(event) => onDescription(event.target.value)}
            />
            <input
                ref={photoInput}
                className="sr-only"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                multiple
                onChange={(event: ChangeEvent<HTMLInputElement>) => {
                    onPhotos(Array.from(event.target.files ?? []));
                    event.target.value = '';
                }}
            />
            {previews.length > 0 && (
                <div
                    className="flex snap-x snap-mandatory gap-12 overflow-x-auto"
                    aria-label={texts.request.photos}
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
                                className="absolute top-8 right-8 flex h-32 w-32 items-center justify-center rounded-full bg-black/70 text-white"
                                onClick={() => onPhotos(photos.filter((_, current) => current !== index))}
                                aria-label={texts.request.removePhoto(preview.name)}
                            >
                                ×
                            </button>
                        </div>
                    ))}
                </div>
            )}
            <Button
                type="button"
                variant="secondary"
                size="large"
                stretched
                onClick={() => photoInput.current?.click()}
            >
                {texts.request.addPhoto}
                {photos.length > 0 ? ` · ${texts.request.selectedPhotos(photos.length)}` : ''}
            </Button>
            <MutationError error={photoError ?? error} />
            <Button type="button" variant="primary" size="large" stretched onClick={onNext}>
                {texts.resident.create.next}
            </Button>
        </div>
    );
}

function AddressStep({
    entrance,
    flat,
    remembered,
    onEntrance,
    onFlat,
    onSame,
    onNext,
}: {
    entrance: string;
    flat: string;
    remembered: boolean;
    onEntrance: (value: string) => void;
    onFlat: (value: string) => void;
    onSame: () => void;
    onNext: () => void;
}) {
    return (
        <div className="flex min-w-0 flex-col gap-16">
            {remembered && (
                <Button type="button" variant="secondary" size="large" stretched onClick={onSame}>
                    {texts.resident.create.sameAddress}
                </Button>
            )}
            <SettingsField
                label={texts.resident.entrance}
                inputMode="numeric"
                value={entrance}
                onChange={(event) => onEntrance(event.target.value.replace(/\D/g, '').slice(0, 2))}
            />
            <SettingsField
                label={texts.resident.flat}
                value={flat}
                onChange={(event) => onFlat(event.target.value.slice(0, 16))}
            />
            <Button type="button" variant="primary" size="large" stretched onClick={onNext}>
                {texts.resident.create.next}
            </Button>
        </div>
    );
}

function PreviewStep({
    category,
    description,
    photos,
    entrance,
    flat,
    address,
    loading,
    error,
    onSend,
}: {
    category: Category;
    description: string;
    photos: number;
    entrance: string;
    flat: string;
    address: string;
    loading: boolean;
    error: unknown;
    onSend: (unsure: boolean) => void;
}) {
    const place = [
        address,
        entrance ? `${texts.resident.entrance} ${entrance}` : null,
        flat ? `${texts.resident.flat} ${flat}` : null,
    ]
        .filter(Boolean)
        .join(', ');

    return (
        <div className="flex min-w-0 flex-col gap-16">
            <RequestSection>
                <CellSimple
                    title={texts.request.what}
                    subtitle={[category.name, description].filter(Boolean).join('. ')}
                />
                <CellSimple title={texts.request.where} subtitle={place} />
                {photos > 0 && (
                    <CellSimple
                        title={texts.request.photos}
                        subtitle={texts.request.selectedPhotos(photos)}
                    />
                )}
                {category.basis && <CellSimple title={texts.request.basis} subtitle={category.basis} />}
                {category.advice && (
                    <CellSimple title={texts.request.responsible} subtitle={category.advice} />
                )}
            </RequestSection>
            <Button
                type="button"
                variant="primary"
                size="large"
                stretched
                loading={loading}
                onClick={() => onSend(false)}
            >
                {texts.resident.create.send}
            </Button>
            <Button
                type="button"
                variant="secondary"
                size="large"
                stretched
                disabled={loading}
                onClick={() => onSend(true)}
            >
                {texts.resident.create.unsure}
            </Button>
            <MutationError error={error} />
        </div>
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
