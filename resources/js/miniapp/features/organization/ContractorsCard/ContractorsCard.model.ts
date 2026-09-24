import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { createContractor, deleteContractor, listContractors } from '@/api/organization';
import { texts } from '@/app/texts';
import { useNoticeState } from '@/components/Notice';
import { toStoredPhone } from '@/lib/phone';
import { useDelayedFlag } from '@/lib/useDelayedFlag';
import type { ContractorType } from '../constants';

export function useContractorsCard() {
    const client = useQueryClient();
    const contractors = useQuery({
        queryKey: ['organization', 'contractors'],
        queryFn: ({ signal }) => listContractors(signal),
    });
    const [type, setType] = useState<ContractorType>('lift');
    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [nameError, setNameError] = useState('');
    const [phoneError, setPhoneError] = useState('');
    const [pendingId, setPendingId] = useState<number | null>(null);
    const notice = useNoticeState();

    const reset = () => {
        setName('');
        setPhone('');
        setNameError('');
        setPhoneError('');
    };

    const create = useMutation({
        mutationFn: createContractor,
        onSuccess: () => {
            reset();
            notice.show(texts.organization.contractorAdded, 'success');
            void client.invalidateQueries({ queryKey: ['organization', 'contractors'] });
        },
    });
    const remove = useMutation({
        mutationFn: deleteContractor,
        onSuccess: () => {
            setPendingId(null);
            notice.show(texts.organization.contractorRemoved, 'success');
            void client.invalidateQueries({ queryKey: ['organization', 'contractors'] });
        },
    });

    const dirty = name.trim() !== '' || phone !== '';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (!dirty) return;
        const trimmed = name.trim();
        const storedPhone = toStoredPhone(phone);
        const nextNameError = trimmed ? '' : texts.organization.contractorNameRequired;
        const nextPhoneError = storedPhone
            ? ''
            : phone.length === 0
              ? texts.organization.phoneRequired
              : texts.organization.phoneError;
        setNameError(nextNameError);
        setPhoneError(nextPhoneError);
        if (nextNameError || nextPhoneError || !storedPhone) {
            notice.show(nextPhoneError || nextNameError, 'error');
            return;
        }
        create.mutate({
            type,
            name: trimmed,
            phone: storedPhone,
        });
    };

    const busy = useDelayedFlag(create.isPending || remove.isPending);

    return {
        contractors,
        type,
        setType,
        name,
        setName,
        phone,
        setPhone,
        nameError,
        setNameError,
        phoneError,
        setPhoneError,
        pendingId,
        setPendingId,
        notice,
        create,
        remove,
        submit,
        busy,
        dirty,
    };
}
