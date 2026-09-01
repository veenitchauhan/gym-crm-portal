import FlashNotification from '@/Components/FlashNotification';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Eye, LockKeyhole, ShieldCheck } from 'lucide-react';
import { useState } from 'react';

interface ResetPasswordProps {
    email: string;
    token: string;
}

export default function ResetPassword({ email, token }: ResetPasswordProps) {
    const [showPassword, setShowPassword] = useState(false);

    return <>
        <Head title="Reset password · Gym CRM Portal" />
        <main className="auth-shell">
            <section className="auth-showcase">
                <Link href="/" className="auth-platform-logo"><img src="/images/gym-crm-portal-logo-on-dark.svg" alt="Gym CRM Portal" /></Link>
                <div className="auth-message">
                    <span className="eyebrow">SECURE ACCOUNT RECOVERY</span>
                    <h1>Create a new password.</h1>
                    <p>Choose a strong password that you do not use for another service.</p>
                    <div className="auth-benefits"><div><ShieldCheck /><span><b>One-time reset link</b><small>The link cannot be reused after your password changes</small></span></div></div>
                </div>
            </section>
            <section className="auth-form-side"><div className="auth-form-wrap">
                <Link href="/" className="mobile-auth-brand auth-platform-logo"><img src="/images/gym-crm-portal-logo.svg" alt="Gym CRM Portal" /></Link>
                <span className="auth-pill"><LockKeyhole size={13} /> Password reset</span>
                <h2>Set your new password</h2>
                <p>This password will be used to sign in as <strong>{email}</strong>.</p>
                <Form action="/reset-password" method="post" className="auth-form">
                    {({ errors, processing }) => <>
                        <input type="hidden" name="token" value={token} />
                        <input type="hidden" name="email" value={email} />
                        <label>New password<div className="input-with-icon"><LockKeyhole size={17} /><input name="password" type={showPassword ? 'text' : 'password'} autoComplete="new-password" autoFocus placeholder="At least 8 characters" /><button type="button" onClick={() => setShowPassword(!showPassword)} aria-label="Show password"><Eye size={17} /></button></div>{errors.password && <em>{errors.password}</em>}</label>
                        <label>Confirm new password<div className="input-with-icon"><LockKeyhole size={17} /><input name="password_confirmation" type={showPassword ? 'text' : 'password'} autoComplete="new-password" placeholder="Repeat your new password" /></div></label>
                        {errors.email && <em>{errors.email}</em>}
                        {errors.token && <em>{errors.token}</em>}
                        <button className="primary auth-submit" disabled={processing}>{processing ? 'Updating…' : 'Reset password'} <ArrowRight size={17} /></button>
                    </>}
                </Form>
                <p className="auth-switch"><Link href="/login"><ArrowLeft size={15} /> Back to sign in</Link></p>
            </div></section>
        </main>
        <FlashNotification />
    </>;
}
