import { useState, type FormEvent } from 'react';
import { Button, Input, Typography } from '@maxhub/max-ui';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { describeError } from '@/lib/describeError';

function demoLoginEnabled(): boolean {
    return document.querySelector<HTMLMetaElement>('meta[name="demo-login"]')?.content === '1';
}

/** Форма с паролем нужна только проверяющим; обычный пользователь входит через MAX без неё. */
export function OpenInMaxPage() {
    const { loginDemo } = useAuth();
    const [login, setLogin] = useState('');
    const [password, setPassword] = useState('');
    const [passwordVisible, setPasswordVisible] = useState(false);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const submit = async (e: FormEvent) => {
        e.preventDefault();
        setBusy(true);
        setError(null);
        try {
            await loginDemo(login.trim(), password);
        } catch (err) {
            setError(describeError(err));
        } finally {
            setBusy(false);
        }
    };

    return (
        <main className="screen outline-none">
            <div className="screen-content flex min-h-full flex-col justify-center gap-24">
                <div className="auth-panel flex flex-col gap-24">
                    <div className="flex flex-col gap-12">
                        <Typography.Headline variant="medium">{texts.auth.openInMax}</Typography.Headline>
                        <Typography.Body variant="medium" className="text-muted">
                            {texts.auth.openInMaxHint}
                        </Typography.Body>
                    </div>

                    {demoLoginEnabled() && (
                        <form onSubmit={submit} className="flex min-w-0 flex-col gap-16">
                            <Typography.Title variant="small-strong">{texts.auth.demoTitle}</Typography.Title>
                            <Typography.Body variant="small" className="text-muted">
                                {texts.auth.demoHint}
                            </Typography.Body>
                            <div className="flex min-w-0 flex-col gap-12">
                                <Input
                                    size="large"
                                    placeholder={texts.auth.login}
                                    aria-label={texts.auth.login}
                                    value={login}
                                    onChange={(e) => setLogin(e.target.value)}
                                    autoComplete="username"
                                    required
                                />
                                <Input
                                    type={passwordVisible ? 'text' : 'password'}
                                    size="large"
                                    placeholder={texts.auth.password}
                                    aria-label={texts.auth.password}
                                    value={password}
                                    onChange={(e) => {
                                        const next = e.target.value;
                                        setPassword(next);
                                        if (next.length === 0) setPasswordVisible(false);
                                    }}
                                    autoComplete="current-password"
                                    required
                                    iconAfter={
                                        password.length > 0 ? (
                                            <PasswordVisibility
                                                visible={passwordVisible}
                                                onToggle={() => setPasswordVisible((open) => !open)}
                                            />
                                        ) : undefined
                                    }
                                />
                            </div>
                            {error && (
                                <Typography.Body variant="small" className="text-negative" role="alert">
                                    {error}
                                </Typography.Body>
                            )}
                            <Button type="submit" variant="primary" size="large" stretched loading={busy}>
                                {texts.auth.submit}
                            </Button>
                        </form>
                    )}
                </div>
            </div>
        </main>
    );
}

function PasswordVisibility({ visible, onToggle }: { visible: boolean; onToggle: () => void }) {
    return (
        <button
            type="button"
            className="flex h-24 w-24 items-center justify-center text-white/56 active:opacity-60"
            onClick={onToggle}
            aria-label={visible ? texts.auth.hidePassword : texts.auth.showPassword}
            aria-pressed={visible}
        >
            {visible ? <EyeOffIcon /> : <EyeIcon />}
        </button>
    );
}

function EyeIcon() {
    return (
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden>
            <path
                d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z"
                stroke="currentColor"
                strokeWidth="1.7"
                strokeLinejoin="round"
            />
            <circle cx="12" cy="12" r="3" stroke="currentColor" strokeWidth="1.7" />
        </svg>
    );
}

function EyeOffIcon() {
    return (
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden>
            <path
                d="M3 3l18 18M10.5 10.6A3 3 0 0 0 13.4 13.5M7.1 7.3C5 8.6 3.5 10.6 2.5 12c0 0 3.5 7 9.5 7 1.7 0 3.2-.4 4.5-1.1M16.9 16.7C19 15.4 20.5 13.4 21.5 12c0 0-3.5-7-9.5-7-1.1 0-2.1.2-3 .5"
                stroke="currentColor"
                strokeWidth="1.7"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
