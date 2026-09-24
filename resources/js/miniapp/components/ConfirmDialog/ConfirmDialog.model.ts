import { useEffect, useRef, useState } from 'react';
import type { ConfirmDialogProps } from './ConfirmDialog.types';

export const CONFIRM_LEAVE_MS = 280;

export function useConfirmDialog({
    open = true,
    loading,
    onCancel,
}: Pick<ConfirmDialogProps, 'open' | 'loading' | 'onCancel'>) {
    const [shown, setShown] = useState(open);
    const [leaving, setLeaving] = useState(false);
    const [dismissed, setDismissed] = useState(false);
    const onCancelRef = useRef(onCancel);
    const notifyCancel = useRef(false);

    useEffect(() => {
        onCancelRef.current = onCancel;
    }, [onCancel]);

    if (!open && dismissed) {
        setDismissed(false);
    }

    if (open && !shown && !leaving && !dismissed) {
        setShown(true);
    } else if (!open && shown && !leaving) {
        setLeaving(true);
    }

    const beginClose = () => {
        if (leaving || !open || loading) return;
        notifyCancel.current = true;
        setDismissed(true);
        setLeaving(true);
    };

    useEffect(() => {
        if (!leaving) return undefined;
        const done = window.setTimeout(() => {
            const shouldCancel = notifyCancel.current;
            notifyCancel.current = false;
            setShown(false);
            setLeaving(false);
            if (shouldCancel) onCancelRef.current();
        }, CONFIRM_LEAVE_MS);
        return () => window.clearTimeout(done);
    }, [leaving]);

    return { shown, leaving, beginClose };
}
