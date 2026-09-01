import FlashNotification from '@/Components/FlashNotification';
import { Form, Head, Link, router } from '@inertiajs/react';
import { Activity, ArrowLeft, Building2, Calendar, CreditCard, Dumbbell, LogIn, LogOut, Mail, MapPin, Phone, ReceiptIndianRupee, ShieldCheck, Users } from 'lucide-react';

type Person = { id: number; name: string; email: string; phone: string | null };
type Member = Person & { plan: string; status: 'Active' | 'Inactive'; joined: string };

type Branch = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    is_active: boolean;
    subscription_plan: string;
    subscription_status: string;
    subscription_expires_at: string | null;
    monthly_fee: string;
    payment_status: string;
    created_at: string;
    payments_count: number;
    attendances_count: number;
    sessions_count: number;
    administrators: Person[];
    members: Member[];
};

export default function BranchShow({ client, branch, superAdmin }: { client: { id: number; name: string }; branch: Branch; superAdmin: { name: string; username: string } }) {
    const fee = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(Number(branch.monthly_fee));

    return <>
        <Head title={`${branch.name} · ${client.name}`} />
        <div className="sa-shell">
            <header className="sa-top">
                <div className="sa-login-brand"><span><ShieldCheck /></span><div><strong>Gym CRM Portal</strong><small>Super Admin</small></div></div>
                <div><span>{superAdmin.name}</span><button onClick={() => router.post('/super-admin/logout')}><LogOut /> Sign out</button></div>
            </header>
            <main className="sa-content sa-client-page sa-branch-page">
                <Link className="sa-client-back" href={`/super-admin/organizations/${client.id}`}><ArrowLeft /> Back to {client.name}</Link>
                <section className="page-heading sa-branch-heading">
                    <div><span className="eyebrow">BRANCH GYM</span><h1>{branch.name}</h1><p>Full branch information, assigned people, activity, and access controls.</p></div>
                    <div className="heading-actions"><button className="primary" disabled={branch.administrators.length === 0} onClick={() => router.post(`/super-admin/gyms/${branch.id}/login`)}><LogIn /> Login</button><button className={`gym-status-button ${branch.is_active ? 'enabled' : 'disabled'}`} onClick={() => router.patch(`/super-admin/organizations/${client.id}/branches/${branch.id}/status`)}>{branch.is_active ? 'Enabled' : 'Disabled'}</button></div>
                </section>

                <section className="sa-branch-metrics">
                    <Link className="metric-link" href={`/super-admin/organizations/${client.id}/members?gym=${branch.id}`} prefetch><Users /><div><small>Members</small><strong>{branch.members.length}</strong></div></Link>
                    <a className="metric-link" href="#branch-administrators"><ShieldCheck /><div><small>Administrators</small><strong>{branch.administrators.length}</strong></div></a>
                    <article><Dumbbell /><div><small>Sessions</small><strong>{branch.sessions_count}</strong></div></article>
                    <article><Activity /><div><small>Attendance records</small><strong>{branch.attendances_count}</strong></div></article>
                    <article><ReceiptIndianRupee /><div><small>Payment records</small><strong>{branch.payments_count}</strong></div></article>
                </section>

                <section className="sa-overview-subscription">
                    <div><small>Subscription plan</small><strong>{branch.subscription_plan}</strong></div>
                    <div><small>Subscription status</small><strong>{branch.subscription_status}</strong></div>
                    <div><small>Monthly fee</small><strong><CreditCard /> {fee}</strong></div>
                    <div><small>Payment status</small><strong className={`payment-${branch.payment_status}`}>{branch.payment_status}</strong></div>
                    <div><small>Expires</small><strong><Calendar /> {branch.subscription_expires_at ? new Date(branch.subscription_expires_at).toLocaleDateString('en-IN') : 'No expiry'}</strong></div>
                </section>

                <section className="card sa-client-section sa-branch-details">
                    <div className="card-head"><div><strong>Branch information</strong><span>Update the branch name and contact details used by this branch.</span></div><i className={`status status-${branch.is_active ? 'active' : 'inactive'}`}>{branch.is_active ? 'Active branch' : 'Disabled branch'}</i></div>
                    <div className="sa-branch-contact"><span><Building2 /><small>Client</small><strong>{client.name}</strong></span><span><MapPin /><small>Created</small><strong>{branch.created_at}</strong></span><span><Mail /><small>Email</small><strong>{branch.email || 'Not provided'}</strong></span><span><Phone /><small>Phone</small><strong>{branch.phone || 'Not provided'}</strong></span></div>
                    <Form action={`/super-admin/organizations/${client.id}/branches/${branch.id}`} method="put" className="sa-branch-edit-form" setDefaultsOnSuccess>
                        {({ errors, processing }) => <><div className="workspace-form-grid sa-branch-form"><label>Branch name<input name="name" defaultValue={branch.name} required />{errors.name && <em className="form-error">{errors.name}</em>}</label><label>Contact email<input name="email" type="email" defaultValue={branch.email ?? ''} />{errors.email && <em className="form-error">{errors.email}</em>}</label><label>Phone<input name="phone" defaultValue={branch.phone ?? ''} />{errors.phone && <em className="form-error">{errors.phone}</em>}</label></div><div className="modal-actions"><button className="primary" disabled={processing}>{processing ? 'Saving…' : 'Save branch details'}</button></div></>}
                    </Form>
                </section>

                <section className="card sa-client-section" id="branch-administrators">
                    <div className="card-head"><div><strong>Assigned administrators</strong><span>Administrators currently permitted to manage this branch.</span></div><strong>{branch.administrators.length}</strong></div>
                    {branch.administrators.length > 0 ? <div className="sa-person-card-grid">{branch.administrators.map(administrator => <article key={administrator.id}><span className="avatar avatar-dark">{administrator.name.split(' ').map(part => part[0]).slice(0, 2).join('')}</span><div><strong>{administrator.name}</strong><small>{administrator.email}</small><small>{administrator.phone || 'No phone number'}</small></div><i>Administrator</i></article>)}</div> : <div className="sa-member-empty"><ShieldCheck /><strong>No administrator assigned</strong><span>Assign access from the client page before logging into this branch.</span></div>}
                </section>

                <section className="card sa-client-section" id="branch-members">
                    <div className="card-head"><div><strong>Branch members</strong><span>Only members registered at {branch.name} are shown here.</span></div><strong>{branch.members.length} members</strong></div>
                    {branch.members.length > 0 ? <div className="sa-branch-member-grid">{branch.members.map(member => <article key={member.id}><div className="sa-branch-member-head"><span className="avatar">{member.name.split(' ').map(part => part[0]).slice(0, 2).join('')}</span><i className={`status status-${member.status.toLowerCase()}`}>{member.status}</i></div><strong>{member.name}</strong><small>{member.email}</small><small>{member.phone || 'No phone number'}</small><div><span>Membership plan</span><strong>{member.plan}</strong></div><div><span>Joined</span><strong>{member.joined}</strong></div></article>)}</div> : <div className="sa-member-empty"><Users /><strong>No members at this branch</strong><span>Members created under this branch will appear here.</span></div>}
                </section>
            </main>
        </div>
        <FlashNotification />
    </>;
}
