import { Form, Head, Link } from '@inertiajs/react';
import FlashNotification from '@/Components/FlashNotification';
import { ArrowRight, Eye, LockKeyhole, Mail, ShieldCheck, Users } from 'lucide-react';
import { useState } from 'react';

export default function Login() {
    const [showPassword, setShowPassword] = useState(false);

    return <>
        <Head title="Sign in · Gym CRM Portal" />
        <main className="auth-shell">
            <section className="auth-showcase">
                <Link href="/" className="auth-platform-logo"><img src="/images/gym-crm-portal-logo-on-dark.svg" alt="Gym CRM Portal"/></Link>
                <div className="auth-message"><span className="eyebrow">GYM MANAGEMENT, SIMPLIFIED</span><h1>Run a stronger club.<br/>Build a stronger community.</h1><p>One calm workspace for your team, trainers, and members.</p><div className="auth-benefits"><div><ShieldCheck/><span><b>Admin workspace</b><small>Manage members, revenue and operations</small></span></div><div><Users/><span><b>Member portal</b><small>Track plans, attendance and activity</small></span></div></div></div>
                <p className="auth-quote">“Gym CRM Portal gives our staff time back to focus on what matters—our members.”</p>
            </section>
            <section className="auth-form-side"><div className="auth-form-wrap">
                <Link href="/" className="mobile-auth-brand auth-platform-logo"><img src="/images/gym-crm-portal-logo.svg" alt="Gym CRM Portal"/></Link>
                <span className="auth-pill"><LockKeyhole size={13}/> Secure sign in</span><h2>Welcome back</h2><p>Enter your account details to continue.</p>
                <Form action="/login" method="post" className="auth-form">
                    {({ errors, processing }) => <>
                        <label>Email address<div className="input-with-icon"><Mail size={17}/><input name="email" type="email" autoComplete="email" autoFocus placeholder="you@example.com"/></div>{errors.email && <em>{errors.email}</em>}</label>
                        <label>Password<div className="input-with-icon"><LockKeyhole size={17}/><input name="password" type={showPassword ? 'text' : 'password'} autoComplete="current-password" placeholder="Enter your password"/><button type="button" onClick={() => setShowPassword(!showPassword)} aria-label="Show password"><Eye size={17}/></button></div>{errors.password && <em>{errors.password}</em>}</label>
                        <div className="auth-options"><label><input type="checkbox" name="remember" value="1"/> Remember me</label><Link href="/forgot-password">Forgot password?</Link></div>
                        <button className="primary auth-submit" disabled={processing}>{processing ? 'Signing in…' : 'Sign in'} <ArrowRight size={17}/></button>
                    </>}
                </Form>
                <p className="auth-switch">Running a gym? <Link href="/register">Create your workspace</Link></p>
            </div></section>
        </main>
        <FlashNotification />
    </>;
}
