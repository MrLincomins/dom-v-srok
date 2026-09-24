export interface ConfirmDialogProps {
    title: string;
    confirm: string;
    cancel: string;
    open?: boolean;
    loading?: boolean;
    onConfirm: () => void;
    onCancel: () => void;
}
