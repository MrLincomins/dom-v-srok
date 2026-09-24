import { useState, type FormEvent } from 'react';
import { Button } from '@maxhub/max-ui';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { Notice, useNoticeState } from '@/components/Notice';
import { SettingsField } from '@/components/SettingsField';
import { describeLoginError } from '@/lib/describeError';

function demoLoginEnabled(): boolean {
    return document.querySelector<HTMLMetaElement>('meta[name="demo-login"]')?.content === '1';
}

function appName(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="app-name"]')?.content || 'Дом в срок';
}

/** Форма с паролем нужна только проверяющим; обычный пользователь входит через MAX без неё. */
export function OpenInMaxPage() {
    const { loginDemo } = useAuth();
    const notice = useNoticeState();
    const demo = demoLoginEnabled();
    const [login, setLogin] = useState('');
    const [password, setPassword] = useState('');
    const [passwordVisible, setPasswordVisible] = useState(false);
    const [busy, setBusy] = useState(false);
    const [invalid, setInvalid] = useState(false);
    const canSubmit = login.trim() !== '' && password !== '';

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!canSubmit || busy) return;
        setBusy(true);
        setInvalid(false);
        try {
            await loginDemo(login.trim(), password);
        } catch (err) {
            const message = describeLoginError(err);
            setInvalid(true);
            notice.show(message, 'error');
            document.getElementById('auth-password')?.focus({ preventScroll: true });
        } finally {
            setBusy(false);
        }
    };

    return (
        <main className="auth-screen">
            <div className="auth-card">
                <div className="auth-intro">
                    <p className="auth-mark">{appName()}</p>
                    <h1 className="auth-title">{demo ? texts.auth.title : texts.auth.openInMax}</h1>
                    <p className="auth-lead">{demo ? texts.auth.demoTitle : texts.auth.openInMaxHint}</p>
                </div>

                {demo ? (
                    <form className="auth-form" noValidate onSubmit={(event) => void submit(event)}>
                        <p className="auth-hint">{texts.auth.demoHint}</p>
                        <div className="auth-fields">
                            <SettingsField
                                id="auth-login"
                                label={texts.auth.login}
                                value={login}
                                invalid={invalid}
                                autoComplete="username"
                                onChange={(event) => {
                                    setLogin(event.target.value);
                                    if (invalid) setInvalid(false);
                                }}
                            />
                            <SettingsField
                                id="auth-password"
                                label={texts.auth.password}
                                type={passwordVisible ? 'text' : 'password'}
                                clearable={false}
                                value={password}
                                invalid={invalid}
                                autoComplete="current-password"
                                onChange={(event) => {
                                    const next = event.target.value;
                                    setPassword(next);
                                    if (next.length === 0) setPasswordVisible(false);
                                    if (invalid) setInvalid(false);
                                }}
                                after={
                                    password.length > 0 ? (
                                        <PasswordVisibility
                                            visible={passwordVisible}
                                            onToggle={() => setPasswordVisible((open) => !open)}
                                        />
                                    ) : undefined
                                }
                            />
                        </div>
                        <Button
                            type="submit"
                            variant="primary"
                            size="large"
                            stretched
                            loading={busy}
                            disabled={!canSubmit || busy}
                        >
                            {texts.auth.submit}
                        </Button>
                    </form>
                ) : null}
            </div>
            <Notice
                text={notice.text}
                tone={notice.tone}
                revision={notice.revision}
                onGone={notice.clear}
            />
        </main>
    );
}

function PasswordVisibility({ visible, onToggle }: { visible: boolean; onToggle: () => void }) {
    return (
        <button
            type="button"
            className="auth-eye"
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
