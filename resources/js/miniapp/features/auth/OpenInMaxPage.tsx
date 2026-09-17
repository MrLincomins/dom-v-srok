import { useState, type FormEvent } from 'react';
import { Button, Input, Typography } from '@maxhub/max-ui';
import { useAuth } from '@/app/auth';
import { texts } from '@/app/texts';
import { describe } from '@/components/ErrorState';

function demoLoginEnabled(): boolean {
    return document.querySelector<HTMLMetaElement>('meta[name="demo-login"]')?.content === '1';
}

/** вне маха подсказка открыть из бота, под флагом форма тестовой учётки */
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
            setError(describe(err));
        } finally {
            setBusy(false);
        }
    };

    return (
        <main className="mx-auto flex min-h-screen max-w-md flex-col justify-center gap-6 p-4">
            <div className="flex flex-col gap-2">
                <Typography.Headline variant="medium">{texts.auth.openInMax}</Typography.Headline>
                <Typography.Body variant="medium" className="text-muted">
                    {texts.auth.openInMaxHint}
                </Typography.Body>
            </div>

            {demoLoginEnabled() && (
                <form onSubmit={submit} className="flex flex-col gap-3 rounded-xl bg-surface p-4">
                    <Typography.Title variant="small-strong">{texts.auth.demoTitle}</Typography.Title>
                    <Typography.Body variant="small" className="text-muted">
                        {texts.auth.demoHint}
                    </Typography.Body>
                    <Input
                        placeholder={texts.auth.login}
                        value={login}
                        onChange={(e) => setLogin(e.target.value)}
                        autoComplete="username"
                        required
                    />
                    <Input
                        type="password"
                        placeholder={texts.auth.password}
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
        </main>
    );
}
