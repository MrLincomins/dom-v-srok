import { Button } from '@maxhub/max-ui';

export function ConfirmDialog({
    title,
    confirm,
    cancel,
    loading,
    onConfirm,
    onCancel,
}: {
    title: string;
    confirm: string;
    cancel: string;
    loading?: boolean;
    onConfirm: () => void;
    onCancel: () => void;
}) {
    return (
        <div className="confirm-backdrop" role="presentation" onClick={onCancel}>
            <div
                className="confirm-card"
                role="dialog"
                aria-modal="true"
                aria-labelledby="confirm-title"
                onClick={(event) => event.stopPropagation()}
            >
                <p id="confirm-title" className="confirm-title">
                    {title}
                </p>
                <div className="confirm-actions">
                    <Button
                        type="button"
                        variant="destructive"
                        size="large"
                        stretched
                        loading={loading}
                        onClick={onConfirm}
                    >
                        {confirm}
                    </Button>
                    <Button type="button" variant="ghost" size="large" stretched disabled={loading} onClick={onCancel}>
                        {cancel}
                    </Button>
                </div>
            </div>
        </div>
    );
}
