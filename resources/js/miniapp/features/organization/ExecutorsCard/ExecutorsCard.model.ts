import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { archiveExecutor, createExecutor, listExecutors, updateExecutor } from '@/api/organization';
import type { Executor } from '@/api/types';
import { texts } from '@/app/texts';
import { isCompletePhone, localPhoneDigits, toStoredPhone } from '@/lib/phone';

export function useExecutorsCard() {
    const client = useQueryClient();
    const executors = useQuery({
        queryKey: ['organization', 'executors'],
        queryFn: ({ signal }) => listExecutors(signal),
    });
    const [editing, setEditing] = useState<Executor | null>(null);
    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [specialty, setSpecialty] = useState('');
    const [nameError, setNameError] = useState('');
    const [phoneError, setPhoneError] = useState('');
    const [pendingArchiveId, setPendingArchiveId] = useState<number | null>(null);
    const [notice, setNotice] = useState<string | null>(null);

    const fill = (executor: Executor | null) => {
        setEditing(executor);
        setName(executor?.name ?? '');
        setPhone(localPhoneDigits(executor?.phone ?? ''));
        setSpecialty(executor?.specialty ?? '');
        setNameError('');
        setPhoneError('');
    };

    const create = useMutation({
        mutationFn: createExecutor,
        onSuccess: () => {
            fill(null);
            setNotice(texts.organization.executorAdded);
            void client.invalidateQueries({ queryKey: ['organization', 'executors'] });
        },
    });
    const update = useMutation({
        mutationFn: (id: number) =>
            updateExecutor(id, {
                name: name.trim(),
                phone: toStoredPhone(phone),
                specialty: specialty.trim() || null,
            }),
        onSuccess: () => {
            fill(null);
            setNotice(texts.organization.executorSaved);
            void client.invalidateQueries({ queryKey: ['organization', 'executors'] });
        },
    });
    const archive = useMutation({
        mutationFn: archiveExecutor,
        onSuccess: () => {
            fill(null);
            setPendingArchiveId(null);
            setNotice(texts.organization.executorRemoved);
            void client.invalidateQueries({ queryKey: ['organization', 'executors'] });
        },
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const trimmed = name.trim();
        if (!trimmed) {
            setNameError(texts.organization.executorNameRequired);
            return;
        }
        if (phone.length > 0 && !isCompletePhone(phone)) {
            setPhoneError(texts.organization.phoneError);
            return;
        }
        setNameError('');
        setPhoneError('');
        const storedPhone = toStoredPhone(phone) ?? undefined;
        if (editing) {
            update.mutate(editing.id);
            return;
        }
        create.mutate({
            name: trimmed,
            phone: storedPhone,
            specialty: specialty.trim() || undefined,
        });
    };

    const busy = create.isPending || update.isPending || archive.isPending;

    return {
        executors,
        editing,
        name,
        setName,
        phone,
        setPhone,
        specialty,
        setSpecialty,
        nameError,
        setNameError,
        phoneError,
        setPhoneError,
        pendingArchiveId,
        setPendingArchiveId,
        notice,
        setNotice,
        fill,
        create,
        update,
        archive,
        submit,
        busy,
    };
}
