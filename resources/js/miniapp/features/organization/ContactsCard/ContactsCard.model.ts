import { useState, type FormEvent } from 'react';
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

function fieldKeys(details: Record<string, unknown>): string[] {
    const fields = details.fields;
    if (!fields || typeof fields !== 'object') return [];
    return Object.keys(fields);
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

    const nameInvalid = checked && name.trim() === '';
    const phoneAdsInvalid = checked && (serverFields.includes('phone_ads') || !isCompletePhone(phoneAds));
    const phoneDispatchInvalid =
        checked &&
        (serverFields.includes('phone_dispatch') ||
            (phoneDispatch.length > 0 && !isCompletePhone(phoneDispatch)));
    const emailInvalid = checked && (serverFields.includes('email') || !isValidEmail(email));

    const firstError = () => {
        if (name.trim() === '') return texts.organization.nameRequired;
        if (!isCompletePhone(phoneAds)) {
            return phoneAds.length === 0 ? texts.organization.phoneRequired : texts.organization.phoneError;
        }
        if (phoneDispatch.length > 0 && !isCompletePhone(phoneDispatch)) {
            return texts.organization.phoneError;
        }
        if (!isValidEmail(email)) return texts.organization.emailError;
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
        },
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setChecked(true);
        setServerFields([]);
        const message = firstError();
        if (message) {
            notice.show(message, 'error');
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
        notice,
        save,
        saving,
        submit,
    };
}
