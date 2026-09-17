import { useEffect } from 'react';
import { useNavigate } from 'react-router';
import { Button, Typography } from '@maxhub/max-ui';
import { bindBackButton, useNativeBackButton } from '@/bridge/maxWebApp';
import { texts } from '@/app/texts';

interface Props {
    title: string;
    backTo?: string;
    right?: React.ReactNode;
}

/** заголовок экрана, нативная кнопка назад на мобилках, своя в вебе и десктопе */
export function PageHeader({ title, backTo, right }: Props) {
    const navigate = useNavigate();
    const native = useNativeBackButton();

    useEffect(() => {
        if (!backTo || !native) return undefined;
        return bindBackButton(() => navigate(backTo));
    }, [backTo, native, navigate]);

    return (
        <header className="sticky top-0 z-10 flex items-center gap-2 bg-page/95 px-4 py-3 backdrop-blur">
            {backTo && !native && (
                <Button
                    variant="ghost"
                    size="small"
                    onClick={() => navigate(backTo)}
                    aria-label={texts.app.back}
                >
                    ← {texts.app.back}
                </Button>
            )}
            <Typography.Headline variant="small" className="flex-1 truncate">
                {title}
            </Typography.Headline>
            {right}
        </header>
    );
}
