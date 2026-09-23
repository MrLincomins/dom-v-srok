import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { updateOrganization } from '@/api/organization';
import type { Organization } from '@/api/types';
import { texts } from '@/app/texts';

export function useContactsCard(organization: Organization) {
    const client = useQueryClient();
    const [name, setName] = useState(organization.name);
    const [phoneAds, setPhoneAds] = useState(organization.phone_ads);
    const [phoneDispatch, setPhoneDispatch] = useState(organization.phone_dispatch ?? '');
    const [email, setEmail] = useState(organization.email ?? '');
    const [hours, setHours] = useState(organization.reception_hours ?? '');
    const [address, setAddress] = useState(organization.reception_address ?? '');
    const [notice, setNotice] = useState<string | null>(null);
    const save = useMutation({
        mutationFn: () =>
            updateOrganization({
                name: name.trim(),
                phone_ads: phoneAds.trim(),
                phone_dispatch: phoneDispatch.trim() || null,
                email: email.trim() || null,
                reception_hours: hours.trim() || null,
                reception_address: address.trim() || null,
            }),
        onSuccess: (data) => {
            client.setQueryData(['organization'], data);
            setNotice(texts.organization.saved);
        },
    });

    return {
        name,
        setName,
        phoneAds,
        setPhoneAds,
        phoneDispatch,
        setPhoneDispatch,
        email,
        setEmail,
        hours,
        setHours,
        address,
        setAddress,
        notice,
        setNotice,
        save,
    };
}
