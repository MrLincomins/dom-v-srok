export type NoticeTone = 'success' | 'error';

export interface NoticeProps {
    text: string | null;
    tone: NoticeTone;
    revision?: number;
    onGone: () => void;
}
