import { useId } from 'react';
import { createPortal } from 'react-dom';
import { Button } from '@maxhub/max-ui';
import { useConfirmDialog } from './ConfirmDialog.model';
import type { ConfirmDialogProps } from './ConfirmDialog.types';

export function ConfirmDialog({
    title,
    confirm,
    cancel,
    open = true,
    loading,
    onConfirm,
    onCancel,
}: ConfirmDialogProps) {
    const titleId = useId();
    const { shown, leaving, beginClose } = useConfirmDialog({ open, loading, onCancel });

    if (!shown) return null;

    return createPortal(
        <div
            className={`confirm-backdrop${leaving ? ' is-closing' : ''}`}
            role="presentation"
            onClick={beginClose}
        >
            <div
                className="confirm-card"
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                onClick={(event) => event.stopPropagation()}
            >
                <p id={titleId} className="confirm-title">
                    {title}
                </p>
                <div className="confirm-actions">
                    <Button
                        type="button"
                        variant="destructive"
                        size="large"
                        stretched
                        className="btn-confirm"
                        loading={loading}
                        disabled={leaving}
                        onClick={onConfirm}
                    >
                        {confirm}
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="large"
                        stretched
                        disabled={loading || leaving}
                        onClick={beginClose}
                    >
                        {cancel}
                    </Button>
                </div>
            </div>
        </div>,
        document.querySelector('.max-root') ?? document.body,
    );
}
