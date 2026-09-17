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
        <main className="app-shell flex min-h-dvh items-center justify-center p-16">
            <div className="enter flex w-full max-w-md flex-col gap-24">
                <div className="flex flex-col gap-8 px-4">
                    <Typography.Headline variant="medium">{texts.auth.openInMax}</Typography.Headline>
                    <Typography.Body variant="medium" className="text-muted">
                        {texts.auth.openInMaxHint}
                    </Typography.Body>
                </div>

                {demoLoginEnabled() && (
                    <form onSubmit={submit} className="app-card flex flex-col gap-12 p-16">
                        <Typography.Title variant="small-strong">{texts.auth.demoTitle}</Typography.Title>
                        <Typography.Body variant="small" className="text-muted">
                            {texts.auth.demoHint}
                        </Typography.Body>
                        <Input
                            placeholder={texts.auth.login}
                            aria-label={texts.auth.login}
                            value={login}
                            onChange={(e) => setLogin(e.target.value)}
                            autoComplete="username"
                            required
                        />
                        <Input
                            type="password"
                            placeholder={texts.auth.password}
                            aria-label={texts.auth.password}
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            autoComplete="current-password"
                            required
                        />
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
        </main>
    );
}
