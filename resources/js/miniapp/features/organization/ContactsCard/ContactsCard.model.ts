import { useLayoutEffect, useState, type FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ApiError } from '@/api/client';
import { updateOrganization } from '@/api/organization';
import type { Organization } from '@/api/types';
import { texts } from '@/app/texts';
import { useNoticeState } from '@/components/Notice';
import { describeError } from '@/lib/describeError';
import { isCompletePhone, localPhoneDigits, toStoredPhone } from '@/lib/phone';
import { useDelayedFlag } from '@/lib/useDelayedFlag';

function isValidEmail(raw: string): boolean {
    const value = raw.trim();
    if (value === '') return true;
    return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value);
}

export const contactFieldId = {
    name: 'contact-name',
    phone_ads: 'contact-phone-ads',
    phone_dispatch: 'contact-phone-dispatch',
    email: 'contact-email',
} as const;

const FIELD_ORDER = ['name', 'phone_ads', 'phone_dispatch', 'email'] as const;

function fieldKeys(details: Record<string, unknown>): string[] {
    const fields = details.fields;
    if (!fields || typeof fields !== 'object') return [];
    return Object.keys(fields);
}

function focusContactField(id: string) {
    const field = document.getElementById(id);
    if (!(field instanceof HTMLElement)) return;
    field.focus({ preventScroll: true });
}

export function useContactsCard(organization: Organization) {
    const client = useQueryClient();
    const [name, setName] = useState(organization.name);
    const [phoneAds, setPhoneAds] = useState(localPhoneDigits(organization.phone_ads));
    const [phoneDispatch, setPhoneDispatch] = useState(localPhoneDigits(organization.phone_dispatch ?? ''));
    const [email, setEmail] = useState(organization.email ?? '');
    const [hours, setHours] = useState(organization.reception_hours ?? '');
    const [address, setAddress] = useState(organization.reception_address ?? '');
    const notice = useNoticeState();
    const [checked, setChecked] = useState(false);
    const [serverFields, setServerFields] = useState<string[]>([]);
    const [focusId, setFocusId] = useState<string | null>(null);

    useLayoutEffect(() => {
        if (!focusId) return;
        focusContactField(focusId);
        setFocusId(null);
    }, [focusId]);

    const dirty =
        name.trim() !== organization.name.trim() ||
        phoneAds !== localPhoneDigits(organization.phone_ads) ||
        phoneDispatch !== localPhoneDigits(organization.phone_dispatch ?? '') ||
        email.trim() !== (organization.email ?? '').trim() ||
        hours.trim() !== (organization.reception_hours ?? '').trim() ||
        address.trim() !== (organization.reception_address ?? '').trim();

    const nameInvalid = checked && name.trim() === '';
    const phoneAdsInvalid = checked && (serverFields.includes('phone_ads') || !isCompletePhone(phoneAds));
    const phoneDispatchInvalid =
        checked &&
        (serverFields.includes('phone_dispatch') ||
            (phoneDispatch.length > 0 && !isCompletePhone(phoneDispatch)));
    const emailInvalid = checked && (serverFields.includes('email') || !isValidEmail(email));

    const firstInvalid = (): { id: string; message: string } | null => {
        if (name.trim() === '') return { id: contactFieldId.name, message: texts.organization.nameRequired };
        if (!isCompletePhone(phoneAds)) {
            return {
                id: contactFieldId.phone_ads,
                message: phoneAds.length === 0 ? texts.organization.phoneRequired : texts.organization.phoneError,
            };
        }
        if (phoneDispatch.length > 0 && !isCompletePhone(phoneDispatch)) {
            return { id: contactFieldId.phone_dispatch, message: texts.organization.phoneError };
        }
        if (!isValidEmail(email)) return { id: contactFieldId.email, message: texts.organization.emailError };
        return null;
    };

    const save = useMutation({
        mutationFn: () =>
            updateOrganization({
                name: name.trim(),
                phone_ads: toStoredPhone(phoneAds) ?? '',
                phone_dispatch: toStoredPhone(phoneDispatch),
                email: email.trim() || null,
                reception_hours: hours.trim() || null,
                reception_address: address.trim() || null,
            }),
        onSuccess: (data) => {
            client.setQueryData(['organization'], data);
            setServerFields([]);
            notice.show(texts.organization.saved, 'success');
        },
        onError: (error) => {
            const fields = error instanceof ApiError ? fieldKeys(error.details) : [];
            setServerFields(fields);
            notice.show(
                fields.includes('email')
                    ? texts.organization.emailError
                    : describeError(error, save.failureCount),
                'error',
            );
            const first = FIELD_ORDER.find((key) => fields.includes(key));
            if (first) setFocusId(contactFieldId[first]);
        },
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (!dirty) return;
        setChecked(true);
        setServerFields([]);
        const invalid = firstInvalid();
        if (invalid) {
            notice.show(invalid.message, 'error');
            setFocusId(invalid.id);
            return;
        }
        save.mutate();
    };

    const saving = useDelayedFlag(save.isPending);

    return {
        name,
        setName: (value: string) => {
            setName(value);
            setServerFields((fields) => fields.filter((item) => item !== 'name'));
        },
        phoneAds,
        setPhoneAds: (value: string) => {
            setPhoneAds(value);
            setServerFields((fields) => fields.filter((item) => item !== 'phone_ads'));
        },
        phoneDispatch,
        setPhoneDispatch: (value: string) => {
            setPhoneDispatch(value);
            setServerFields((fields) => fields.filter((item) => item !== 'phone_dispatch'));
        },
        email,
        setEmail: (value: string) => {
            setEmail(value);
            setServerFields((fields) => fields.filter((item) => item !== 'email'));
        },
        hours,
        setHours,
        address,
        setAddress,
        nameInvalid,
        phoneAdsInvalid,
        phoneDispatchInvalid,
        emailInvalid,
        dirty,
        notice,
        save,
        saving,
        submit,
    };
}
