import { Form, Head, Link } from '@inertiajs/react';
import { ArrowRight, LockKeyhole, Mail, Phone, UserRound } from 'lucide-react';

export default function Register() {
    return <>
        <Head title="Join Gym CRM Portal" />
        <main className="auth-shell register-shell">
            <section className="auth-showcase"><Link href="/" className="auth-platform-logo"><img src="/images/gym-crm-portal-logo.svg" alt="Gym CRM Portal"/></Link><div className="auth-message"><span className="eyebrow">YOUR FITNESS, IN ONE PLACE</span><h1>Your club journey<br/>starts here.</h1><p>View your membership, activity, upcoming sessions and progress from one personal portal.</p></div><p className="auth-quote">Built for members of Downtown Club.</p></section>
            <section className="auth-form-side"><div className="auth-form-wrap"><Link href="/" className="mobile-auth-brand auth-platform-logo"><img src="/images/gym-crm-portal-logo.svg" alt="Gym CRM Portal"/></Link><h2>Create member account</h2><p>Join your club’s digital workspace.</p>
                <Form action="/register" method="post" className="auth-form">
                    {({ errors, processing }) => <>
                        <label>Full name<div className="input-with-icon"><UserRound size={17}/><input name="name" placeholder="Your full name"/></div>{errors.name && <em>{errors.name}</em>}</label>
                        <label>Email address<div className="input-with-icon"><Mail size={17}/><input name="email" type="email" placeholder="you@example.com"/></div>{errors.email && <em>{errors.email}</em>}</label>
                        <label>Phone <small>(optional)</small><div className="input-with-icon"><Phone size={17}/><input name="phone" placeholder="+91 98765 43210"/></div>{errors.phone && <em>{errors.phone}</em>}</label>
                        <div className="form-row"><label>Password<div className="input-with-icon"><LockKeyhole size={17}/><input name="password" type="password"/></div>{errors.password && <em>{errors.password}</em>}</label><label>Confirm password<div className="input-with-icon"><LockKeyhole size={17}/><input name="password_confirmation" type="password"/></div></label></div>
                        <button className="primary auth-submit" disabled={processing}>{processing ? 'Creating…' : 'Create account'} <ArrowRight size={17}/></button>
                    </>}
                </Form><p className="auth-switch">Already have an account? <Link href="/login">Sign in</Link></p>
            </div></section>
        </main>
    </>;
}
