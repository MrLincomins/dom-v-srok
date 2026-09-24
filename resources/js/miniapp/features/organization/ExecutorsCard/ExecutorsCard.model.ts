import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { archiveExecutor, createExecutor, listExecutors, updateExecutor } from '@/api/organization';
import type { Executor } from '@/api/types';
import { texts } from '@/app/texts';
import { useNoticeState } from '@/components/Notice';
import { localPhoneDigits, toStoredPhone } from '@/lib/phone';
import { useDelayedFlag } from '@/lib/useDelayedFlag';

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
    const notice = useNoticeState();

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
            notice.show(texts.organization.executorAdded, 'success');
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
            notice.show(texts.organization.executorSaved, 'success');
            void client.invalidateQueries({ queryKey: ['organization', 'executors'] });
        },
    });
    const archive = useMutation({
        mutationFn: archiveExecutor,
        onSuccess: () => {
            fill(null);
            setPendingArchiveId(null);
            notice.show(texts.organization.executorRemoved, 'success');
            void client.invalidateQueries({ queryKey: ['organization', 'executors'] });
        },
    });

    const dirty = editing
        ? name.trim() !== editing.name.trim() ||
          phone !== localPhoneDigits(editing.phone ?? '') ||
          specialty.trim() !== (editing.specialty ?? '').trim()
        : name.trim() !== '' || phone !== '' || specialty.trim() !== '';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (!dirty) return;
        const trimmed = name.trim();
        const storedPhone = toStoredPhone(phone);
        const nextNameError = trimmed ? '' : texts.organization.executorNameRequired;
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

    const busy = useDelayedFlag(create.isPending || update.isPending || archive.isPending);

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
        fill,
        create,
        update,
        archive,
        submit,
        busy,
        dirty,
    };
}
