import { useCallback, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { texts } from '@/app/texts';
import { bindBackButton } from '@/bridge/maxWebApp';
import { UserContextBar } from '@/components/UserContextBar';
import { HouseDetails } from './HouseDetails';

const CLOSE_MS = 280;
const DISTANCE = 72;
const SLANT = 56;

export function HouseSheet({ open, onClose }: { open: boolean; onClose: () => void }) {
    const panel = useRef<HTMLDivElement>(null);
    const drag = useRef<{ x: number; y: number; dragged: boolean } | null>(null);
    const skipClick = useRef(false);
    const [closing, setClosing] = useState(false);
    const [offset, setOffset] = useState(0);

    const beginClose = useCallback(() => {
        setOffset(0);
        setClosing(true);
    }, []);

    const onGrabPointerDown = (event: React.PointerEvent<HTMLButtonElement>) => {
        if (closing) return;
        drag.current = { x: event.clientX, y: event.clientY, dragged: false };
        event.currentTarget.setPointerCapture?.(event.pointerId);
    };

    const onGrabPointerMove = (event: React.PointerEvent<HTMLButtonElement>) => {
        const start = drag.current;
        if (!start) return;
        const dy = event.clientY - start.y;
        const dx = event.clientX - start.x;
        if (Math.abs(dy) > 8 || Math.abs(dx) > 8) start.dragged = true;
        setOffset(dy > 0 ? dy : 0);
    };

    const onGrabPointerUp = (event: React.PointerEvent<HTMLButtonElement>) => {
        const start = drag.current;
        drag.current = null;
        if (!start) return;
        if (start.dragged) skipClick.current = true;
        const dy = event.clientY - start.y;
        const dx = event.clientX - start.x;
        if (dy >= DISTANCE && Math.abs(dx) <= SLANT) {
            beginClose();
            return;
        }
        setOffset(0);
    };

    const onGrabClick = (event: React.MouseEvent<HTMLButtonElement>) => {
        if (skipClick.current) {
            skipClick.current = false;
            event.preventDefault();
            return;
        }
        beginClose();
    };

    useEffect(() => {
        if (!open || closing) return undefined;
        panel.current?.focus({ preventScroll: true });
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') beginClose();
        };
        window.addEventListener('keydown', onKey);
        const unbind = bindBackButton(beginClose);
        return () => {
            window.removeEventListener('keydown', onKey);
            unbind();
        };
    }, [open, closing, beginClose]);

    useEffect(() => {
        if (!closing) return undefined;
        const done = () => {
            setClosing(false);
            onClose();
        };
        const node = panel.current;
        node?.addEventListener('animationend', done);
        node?.addEventListener('transitionend', done);
        const timer = window.setTimeout(done, CLOSE_MS);
        return () => {
            node?.removeEventListener('animationend', done);
            node?.removeEventListener('transitionend', done);
            window.clearTimeout(timer);
        };
    }, [closing, onClose]);

    if (!open) return null;

    const dragging = offset > 0 && !closing;

    return createPortal(
        <div
            className={`sheet-backdrop${closing ? ' is-closing' : ''}`}
            onClick={closing ? undefined : beginClose}
            role="presentation"
        >
            <div
                ref={panel}
                className={`sheet${dragging ? ' is-dragging' : ''}`}
                role="dialog"
                aria-modal="true"
                aria-labelledby="house-sheet-title"
                tabIndex={-1}
                style={
                    closing
                        ? { transform: 'translateY(100%)' }
                        : offset > 0
                          ? { transform: `translateY(${offset}px)` }
                          : undefined
                }
                onClick={(event) => event.stopPropagation()}
            >
                <button
                    type="button"
                    className="sheet-grab"
                    onClick={onGrabClick}
                    onPointerDown={onGrabPointerDown}
                    onPointerMove={onGrabPointerMove}
                    onPointerUp={onGrabPointerUp}
                    onPointerCancel={onGrabPointerUp}
                    aria-label={texts.home.closeSheet}
                    disabled={closing}
                >
                    <span className="sheet-handle" aria-hidden />
                </button>
                <h2 id="house-sheet-title" className="sheet-title">
                    {texts.home.house}
                </h2>
                <UserContextBar />
                <HouseDetails />
            </div>
        </div>,
        document.body,
    );
}
