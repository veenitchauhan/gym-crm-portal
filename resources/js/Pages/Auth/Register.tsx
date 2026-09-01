import { Form, Head, Link } from '@inertiajs/react';
import { ArrowRight, LockKeyhole, Mail, Phone, UserRound } from 'lucide-react';

export default function Register() {
    return <>
        <Head title="Join Gym CRM Portal" />
        <main className="auth-shell register-shell">
            <section className="auth-showcase"><Link href="/" className="auth-platform-logo"><img src="/images/gym-crm-portal-logo-on-dark.svg" alt="Gym CRM Portal"/></Link><div className="auth-message"><span className="eyebrow">YOUR GYM, ONE WORKSPACE</span><h1>Your stronger operation<br/>starts here.</h1><p>Create your gym workspace, then invite members through the connected CRM.</p></div><p className="auth-quote">A 14-day Starter trial is included.</p></section>
            <section className="auth-form-side"><div className="auth-form-wrap"><Link href="/" className="mobile-auth-brand auth-platform-logo"><img src="/images/gym-crm-portal-logo.svg" alt="Gym CRM Portal"/></Link><h2>Create gym workspace</h2><p>Set up your club and first administrator.</p>
                <Form action="/register" method="post" className="auth-form">
                    {({ errors, processing }) => <>
                        <label>Gym name<div className="input-with-icon"><UserRound size={17}/><input name="gym_name" placeholder="Your gym or studio"/></div>{errors.gym_name && <em>{errors.gym_name}</em>}</label>
                        <label>Administrator name<div className="input-with-icon"><UserRound size={17}/><input name="name" placeholder="Your full name"/></div>{errors.name && <em>{errors.name}</em>}</label>
                        <label>Email address<div className="input-with-icon"><Mail size={17}/><input name="email" type="email" placeholder="you@example.com"/></div>{errors.email && <em>{errors.email}</em>}</label>
                        <label>Phone <small>(optional)</small><div className="input-with-icon"><Phone size={17}/><input name="phone" placeholder="+91 98765 43210"/></div>{errors.phone && <em>{errors.phone}</em>}</label>
                        <div className="form-row"><label>Password<div className="input-with-icon"><LockKeyhole size={17}/><input name="password" type="password"/></div>{errors.password && <em>{errors.password}</em>}</label><label>Confirm password<div className="input-with-icon"><LockKeyhole size={17}/><input name="password_confirmation" type="password"/></div></label></div>
                        <button className="primary auth-submit" disabled={processing}>{processing ? 'Creating…' : 'Create workspace'} <ArrowRight size={17}/></button>
                    </>}
                </Form><p className="auth-switch">Already have an account? <Link href="/login">Sign in</Link></p>
            </div></section>
        </main>
    </>;
}
