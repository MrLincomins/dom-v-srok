import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { createContractor, deleteContractor, listContractors } from '@/api/organization';
import { texts } from '@/app/texts';
import { isCompletePhone, toStoredPhone } from '@/lib/phone';
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
    const [notice, setNotice] = useState<string | null>(null);

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
            setNotice(texts.organization.contractorAdded);
            void client.invalidateQueries({ queryKey: ['organization', 'contractors'] });
        },
    });
    const remove = useMutation({
        mutationFn: deleteContractor,
        onSuccess: () => {
            setPendingId(null);
            setNotice(texts.organization.contractorRemoved);
            void client.invalidateQueries({ queryKey: ['organization', 'contractors'] });
        },
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const trimmed = name.trim();
        if (!trimmed) {
            setNameError(texts.organization.contractorNameRequired);
            return;
        }
        if (phone.length > 0 && !isCompletePhone(phone)) {
            setPhoneError(texts.organization.phoneError);
            return;
        }
        setNameError('');
        setPhoneError('');
        create.mutate({
            type,
            name: trimmed,
            phone: toStoredPhone(phone) ?? undefined,
        });
    };

    const busy = create.isPending || remove.isPending;

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
        setNotice,
        create,
        remove,
        submit,
        busy,
    };
}
