import FlashNotification from '@/Components/FlashNotification';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, LockKeyhole, Mail, ShieldCheck } from 'lucide-react';

export default function ForgotPassword() {
    return <>
        <Head title="Forgot password · Gym CRM Portal" />
        <main className="auth-shell">
            <section className="auth-showcase">
                <Link href="/" className="auth-platform-logo"><img src="/images/gym-crm-portal-logo-on-dark.svg" alt="Gym CRM Portal" /></Link>
                <div className="auth-message">
                    <span className="eyebrow">SECURE ACCOUNT RECOVERY</span>
                    <h1>Get back to managing your gym.</h1>
                    <p>We will email a secure, time-limited link to reset your password. Your existing password is never sent or displayed.</p>
                    <div className="auth-benefits"><div><ShieldCheck /><span><b>Protected recovery</b><small>Reset links are unique and expire automatically</small></span></div></div>
                </div>
            </section>
            <section className="auth-form-side"><div className="auth-form-wrap">
                <Link href="/" className="mobile-auth-brand auth-platform-logo"><img src="/images/gym-crm-portal-logo.svg" alt="Gym CRM Portal" /></Link>
                <span className="auth-pill"><LockKeyhole size={13} /> Password recovery</span>
                <h2>Forgot your password?</h2>
                <p>Enter the email used for your account and we will send reset instructions.</p>
                <Form action="/forgot-password" method="post" className="auth-form">
                    {({ errors, processing }) => <>
                        <label>Email address<div className="input-with-icon"><Mail size={17} /><input name="email" type="email" autoComplete="email" autoFocus placeholder="you@example.com" /></div>{errors.email && <em>{errors.email}</em>}</label>
                        <button className="primary auth-submit" disabled={processing}>{processing ? 'Sending…' : 'Send reset link'} <ArrowRight size={17} /></button>
                    </>}
                </Form>
                <p className="auth-switch"><Link href="/login"><ArrowLeft size={15} /> Back to sign in</Link></p>
            </div></section>
        </main>
        <FlashNotification />
    </>;
}
