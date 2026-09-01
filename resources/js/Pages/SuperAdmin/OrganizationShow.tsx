import FlashNotification from '@/Components/FlashNotification';
import { Form, Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Building2, Calendar, CreditCard, Edit3, KeyRound, LoaderCircle, LogOut, Mail, MapPin, Plus, ShieldCheck, Users, X } from 'lucide-react';
import { useState } from 'react';

type Branch = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    is_active: boolean;
    administrators_count: number;
    members_count: number;
};

type Administrator = { id: number; name: string; email: string; phone: string | null; must_change_password: boolean; branch_ids: number[] };

type Client = {
    id: number;
    name: string;
    multi_branch_enabled: boolean;
    subscription_plan: string;
    subscription_status: string;
    subscription_expires_at: string | null;
    monthly_fee: string;
    payment_status: string;
    members_count: number;
    primary_gym: { id: number; name: string };
    branches: Branch[];
    administrators: Administrator[];
};

export default function OrganizationShow({ client, superAdmin }: { client: Client; superAdmin: { name: string; username: string; temporaryPassword: string } }) {
    const [editingAdministrator, setEditingAdministrator] = useState<Administrator | null>(null);
    const [pendingAdministratorActions, setPendingAdministratorActions] = useState<Record<number, 'reset' | 'temporary'>>({});
    const fee = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(Number(client.monthly_fee));

    const finishAdministratorAction = (administratorId: number) => {
        setPendingAdministratorActions(currentActions => {
            const remainingActions = { ...currentActions };
            delete remainingActions[administratorId];

            return remainingActions;
        });
    };

    const sendPasswordReset = (administrator: Administrator) => {
        if (!window.confirm(`Send a password-reset email to ${administrator.email}?`)) {
            return;
        }

        setPendingAdministratorActions(currentActions => ({ ...currentActions, [administrator.id]: 'reset' }));
        router.post(
            `/super-admin/organizations/${client.id}/administrators/${administrator.id}/password-reset-link`,
            {},
            {
                preserveScroll: true,
                onFinish: () => finishAdministratorAction(administrator.id),
            },
        );
    };

    const setTemporaryPassword = (administrator: Administrator) => {
        if (!window.confirm(`Set ${administrator.name}'s temporary password to ${superAdmin.temporaryPassword}? Their active sessions will end and they must change it after login.`)) {
            return;
        }

        setPendingAdministratorActions(currentActions => ({ ...currentActions, [administrator.id]: 'temporary' }));
        router.put(
            `/super-admin/organizations/${client.id}/administrators/${administrator.id}/temporary-password`,
            {},
            {
                preserveScroll: true,
                onFinish: () => finishAdministratorAction(administrator.id),
            },
        );
    };

    return <>
        <Head title={`${client.name} · Super Admin`} />
        <div className="sa-shell">
            <header className="sa-top">
                <div className="sa-login-brand"><span><ShieldCheck /></span><div><strong>Gym CRM Portal</strong><small>Super Admin</small></div></div>
                <div><span>{superAdmin.name}</span><button onClick={() => router.post('/super-admin/logout')}><LogOut /> Sign out</button></div>
            </header>
            <main className="sa-content sa-client-page">
                <Link className="sa-client-back" href="/super-admin/gyms"><ArrowLeft /> Back to gym clients</Link>
                <section className="page-heading">
                    <div><span className="eyebrow">CLIENT OVERVIEW</span><h1>{client.name}</h1><p>Manage the primary gym, branches, administrator access, and subscription details.</p></div>
                </section>

                <section className="sa-overview-summary">
                    <a className="sa-summary-link" href="#branches"><Building2 /><div><small>Branches</small><strong>{client.branches.length}</strong></div><ArrowRight className="sa-summary-arrow" /></a>
                    <Link className="sa-summary-link" href={`/super-admin/organizations/${client.id}/members`} prefetch><Users /><div><small>Members</small><strong>{client.members_count}</strong></div><ArrowRight className="sa-summary-arrow" /></Link>
                    <a className="sa-summary-link" href="#administrators"><ShieldCheck /><div><small>Administrators</small><strong>{client.administrators.length}</strong></div><ArrowRight className="sa-summary-arrow" /></a>
                    <a className="sa-summary-link" href="#subscription"><CreditCard /><div><small>Monthly fee</small><strong>{fee}</strong></div><ArrowRight className="sa-summary-arrow" /></a>
                </section>
                <section className="sa-overview-subscription" id="subscription">
                    <div><small>Subscription plan</small><strong>{client.subscription_plan}</strong></div>
                    <div><small>Subscription status</small><strong>{client.subscription_status}</strong></div>
                    <div><small>Payment status</small><strong className={`payment-${client.payment_status}`}>{client.payment_status}</strong></div>
                    <div><small>Expires</small><strong><Calendar /> {client.subscription_expires_at ? new Date(client.subscription_expires_at).toLocaleDateString('en-IN') : 'No expiry'}</strong></div>
                </section>

                <section className="card sa-client-section" id="branches">
                    <div className="card-head"><div><strong>Branches</strong><span>Open a branch to view its people, activity, contact details, and controls.</span></div><strong>{client.branches.length} branches</strong></div>
                    {client.branches.length > 0 ? <div className="sa-branch-grid">{client.branches.map(branch => <Link key={branch.id} href={`/super-admin/organizations/${client.id}/branches/${branch.id}`} className={`sa-branch-card ${!branch.is_active ? 'inactive' : ''}`}>
                        <div className="sa-branch-card-head"><span><MapPin /></span><i className={branch.is_active ? 'enabled' : 'disabled'}>{branch.is_active ? 'Enabled' : 'Disabled'}</i></div>
                        <div className="sa-branch-card-copy"><strong>{branch.name}</strong><span>{[branch.email, branch.phone].filter(Boolean).join(' · ') || 'No contact details'}</span></div>
                        <div className="sa-branch-card-stats"><div><Users /><span>Members</span><strong>{branch.members_count}</strong></div><div><ShieldCheck /><span>Administrators</span><strong>{branch.administrators_count}</strong></div></div>
                        <div className="sa-branch-card-open">View branch details <ArrowRight /></div>
                    </Link>)}</div> : <div className="sa-member-empty"><Building2 /><strong>No branch gyms yet</strong><span>Add the first branch below when this client is ready to expand.</span></div>}
                </section>

                <section className="card sa-client-section" id="administrators">
                    <div className="card-head"><div><strong>Administrator access</strong><span>The primary gym is permanent; choose any additional branches each administrator can manage.</span></div></div>
                    <div className="sa-administrator-list">
                        {client.administrators.map(administrator => <Form key={administrator.id} action={`/super-admin/organizations/${client.id}/administrators/${administrator.id}/branches`} method="put">
                            {({ errors, processing }) => <><div className="sa-administrator-head"><div className="sa-administrator-identity"><span className="avatar avatar-dark">{administrator.name.split(' ').map(part => part[0]).slice(0, 2).join('').toUpperCase()}</span><div><strong>{administrator.name}{administrator.must_change_password && <i className="temporary-password-badge">Password change required</i>}</strong><small>{administrator.email}{administrator.phone ? ` · ${administrator.phone}` : ''}</small></div></div><div className="sa-administrator-actions"><button type="button" className="secondary" disabled={pendingAdministratorActions[administrator.id] !== undefined} aria-busy={pendingAdministratorActions[administrator.id] === 'reset'} onClick={() => sendPasswordReset(administrator)}>{pendingAdministratorActions[administrator.id] === 'reset' ? <LoaderCircle className="animate-spin" /> : <Mail />} {pendingAdministratorActions[administrator.id] === 'reset' ? 'Sending…' : 'Send reset'}</button><button type="button" className="secondary temporary-password-action" disabled={pendingAdministratorActions[administrator.id] !== undefined} aria-busy={pendingAdministratorActions[administrator.id] === 'temporary'} onClick={() => setTemporaryPassword(administrator)}>{pendingAdministratorActions[administrator.id] === 'temporary' ? <LoaderCircle className="animate-spin" /> : <KeyRound />} {pendingAdministratorActions[administrator.id] === 'temporary' ? 'Setting…' : 'Set temporary'}</button><button type="button" className="secondary sa-edit-administrator" onClick={() => setEditingAdministrator(administrator)}><Edit3 /> Edit details</button></div></div><div className="sa-access-panel"><div className="sa-access-copy"><strong>Gym access</strong><small>Select the branches this administrator can manage.</small></div><div className="sa-branch-checkboxes"><div className="sa-primary-access"><span>{client.primary_gym.name}</span><i>Primary</i></div>{client.branches.map(branch => <label key={branch.id} className={!branch.is_active ? 'inactive' : ''}><input type="checkbox" name="branch_ids[]" value={branch.id} defaultChecked={administrator.branch_ids.includes(branch.id)} /><span>{branch.name}</span>{!branch.is_active && <small>Disabled</small>}</label>)}</div><button className="primary sa-save-access" disabled={processing}>{processing ? 'Saving…' : 'Save access'}</button></div>{Object.values(errors)[0] && <em className="form-error">{Object.values(errors)[0]}</em>}</>}
                        </Form>)}
                        {client.administrators.length === 0 && <p className="sa-no-administrators">This client does not have an administrator yet.</p>}
                    </div>
                </section>

                {client.multi_branch_enabled ? <section className="card sa-client-section sa-branch-editor">
                    <Form action={`/super-admin/organizations/${client.id}/branches`} method="post" resetOnSuccess>
                        {({ errors, processing }) => <>
                            <div className="card-head"><div><strong>Add another branch</strong><span>Subscription settings are inherited from the primary gym.</span></div></div>
                            <div className="workspace-form-grid sa-branch-form"><label>Branch name<input name="name" required /></label><label>Contact email<input name="email" type="email" /></label><label>Phone<input name="phone" /></label></div>
                            {Object.values(errors)[0] && <em className="form-error">{Object.values(errors)[0]}</em>}
                            <div className="modal-actions"><button className="primary" disabled={processing}><Plus /> {processing ? 'Saving…' : 'Add branch'}</button></div>
                        </>}
                    </Form>
                </section> : <div className="sa-multi-branch-note"><MapPin /><div><strong>Single-gym client</strong><span>Enable multiple branches on the client card before adding a branch.</span></div></div>}
            </main>
        </div>
        {editingAdministrator && <div className="modal-backdrop" onMouseDown={() => setEditingAdministrator(null)}><Form key={editingAdministrator.id} action={`/super-admin/organizations/${client.id}/administrators/${editingAdministrator.id}`} method="put" className="modal sa-administrator-modal" onMouseDown={event => event.stopPropagation()} onSuccess={() => setEditingAdministrator(null)}>
            {({ errors, processing }) => <><div className="modal-head"><div><h2>Edit administrator</h2><p>Update this administrator’s identity and login email.</p></div><button type="button" onClick={() => setEditingAdministrator(null)} aria-label="Close"><X /></button></div><div className="workspace-form-grid"><label>Full name<input name="name" defaultValue={editingAdministrator.name} autoComplete="name" required />{errors.name && <em className="form-error">{errors.name}</em>}</label><label>Login email<input name="email" type="email" defaultValue={editingAdministrator.email} autoComplete="email" required />{errors.email && <em className="form-error">{errors.email}</em>}</label><label>Phone number<input name="phone" defaultValue={editingAdministrator.phone ?? ''} autoComplete="tel" />{errors.phone && <em className="form-error">{errors.phone}</em>}</label></div><div className="modal-actions"><button type="button" className="secondary" onClick={() => setEditingAdministrator(null)}>Cancel</button><button className="primary" disabled={processing}>{processing ? 'Saving…' : 'Save administrator'}</button></div></>}
        </Form></div>}
        <FlashNotification />
    </>;
}
