import { createPortal } from 'react-dom';
import { texts } from '@/app/texts';
import { HouseDetails } from '@/features/resident/HouseDetails';
import { useHouseSheet } from './HouseSheet.model';
import type { HouseSheetProps } from './HouseSheet.types';

export function HouseSheet({ open, onClose }: HouseSheetProps) {
    const {
        panel,
        wide,
        closing,
        offset,
        dragging,
        beginClose,
        onGrabClick,
        onGrabPointerDown,
        onGrabPointerMove,
        onGrabPointerUp,
    } = useHouseSheet({ open, onClose });

    if (!open) return null;

    return createPortal(
        <div
            className={`sheet-backdrop${closing ? ' is-closing' : ''}${wide ? ' is-wide' : ''}`}
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
                    closing && !wide
                        ? { transform: 'translateY(100%)' }
                        : !wide && offset > 0
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
                <div className="sheet-body">
                    <HouseDetails showName />
                </div>
            </div>
        </div>,
        document.body,
    );
}
