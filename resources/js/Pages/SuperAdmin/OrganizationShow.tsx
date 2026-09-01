import FlashNotification from '@/Components/FlashNotification';
import { Form, Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Building2, Calendar, CreditCard, Edit3, LogIn, LogOut, MapPin, Plus, ShieldCheck, Users } from 'lucide-react';
import { useRef, useState } from 'react';

type Location = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    is_active: boolean;
    is_primary: boolean;
    administrators_count: number;
    members_count: number;
};

type Administrator = { id: number; name: string; email: string; location_ids: number[] };

type Client = {
    id: number;
    name: string;
    multi_location_enabled: boolean;
    subscription_plan: string;
    subscription_status: string;
    subscription_expires_at: string | null;
    monthly_fee: string;
    payment_status: string;
    members_count: number;
    locations: Location[];
    administrators: Administrator[];
};

export default function OrganizationShow({ client, superAdmin }: { client: Client; superAdmin: { name: string; username: string } }) {
    const [editingLocation, setEditingLocation] = useState<Location | null>(null);
    const locationEditorRef = useRef<HTMLElement>(null);
    const fee = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(Number(client.monthly_fee));
    const editLocation = (location: Location) => {
        setEditingLocation(location);
        window.requestAnimationFrame(() => locationEditorRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
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
                    <div><span className="eyebrow">CLIENT OVERVIEW</span><h1>{client.name}</h1><p>Manage gym locations, administrator access, and subscription details.</p></div>
                </section>

                <section className="sa-overview-summary">
                    <article><Building2 /><div><small>Locations</small><strong>{client.locations.length}</strong></div></article>
                    <article><Users /><div><small>Members</small><strong>{client.members_count}</strong></div></article>
                    <article><ShieldCheck /><div><small>Administrators</small><strong>{client.administrators.length}</strong></div></article>
                    <article><CreditCard /><div><small>Monthly fee</small><strong>{fee}</strong></div></article>
                </section>
                <section className="sa-overview-subscription">
                    <div><small>Subscription plan</small><strong>{client.subscription_plan}</strong></div>
                    <div><small>Subscription status</small><strong>{client.subscription_status}</strong></div>
                    <div><small>Payment status</small><strong className={`payment-${client.payment_status}`}>{client.payment_status}</strong></div>
                    <div><small>Expires</small><strong><Calendar /> {client.subscription_expires_at ? new Date(client.subscription_expires_at).toLocaleDateString('en-IN') : 'No expiry'}</strong></div>
                </section>

                <section className="card sa-client-section">
                    <div className="card-head"><div><strong>Gym locations</strong><span>Open, edit, or control access for each location.</span></div></div>
                    <div className="sa-location-list">{client.locations.map(location => <article key={location.id} className={!location.is_active ? 'inactive' : ''}>
                        <span><MapPin /></span>
                        <div><div className="sa-location-name"><strong>{location.name}</strong><i className={location.is_primary ? 'primary-location' : ''}>{location.is_primary ? 'Primary' : 'Branch'}</i>{!location.is_active && <i className="disabled-location">Disabled</i>}</div><small>{[location.email, location.phone].filter(Boolean).join(' · ') || 'No contact details'}</small></div>
                        <em>{location.administrators_count} admins · {location.members_count} members</em>
                        <div className="sa-location-actions">
                            <button className="secondary" disabled={location.administrators_count === 0} onClick={() => router.post(`/super-admin/gyms/${location.id}/login`)}><LogIn /> Login</button>
                            <button className="secondary" onClick={() => editLocation(location)}><Edit3 /> Edit</button>
                            {!location.is_primary && <button className={`gym-status-button ${location.is_active ? 'enabled' : 'disabled'}`} onClick={() => router.patch(`/super-admin/organizations/${client.id}/locations/${location.id}/status`)}>{location.is_active ? 'Enabled' : 'Disabled'}</button>}
                        </div>
                    </article>)}</div>
                </section>

                <section className="card sa-client-section">
                    <div className="card-head"><div><strong>Administrator access</strong><span>The primary gym is permanent; choose any additional branches each administrator can manage.</span></div></div>
                    <div className="sa-administrator-list">
                        {client.administrators.map(administrator => <Form key={administrator.id} action={`/super-admin/organizations/${client.id}/administrators/${administrator.id}/locations`} method="put">
                            {({ errors, processing }) => <><div className="sa-administrator-head"><div><strong>{administrator.name}</strong><small>{administrator.email}</small></div><button className="secondary" disabled={processing}>{processing ? 'Saving…' : 'Save access'}</button></div><div className="sa-location-checkboxes">{client.locations.map(location => location.is_primary
                                ? <div className="sa-primary-access" key={location.id}><span>{location.name}</span><i>Primary</i></div>
                                : <label key={location.id} className={!location.is_active ? 'inactive' : ''}><input type="checkbox" name="location_ids[]" value={location.id} defaultChecked={administrator.location_ids.includes(location.id)} /><span>{location.name}</span>{!location.is_active && <small>Disabled</small>}</label>)}</div>{Object.values(errors)[0] && <em className="form-error">{Object.values(errors)[0]}</em>}</>}
                        </Form>)}
                        {client.administrators.length === 0 && <p className="sa-no-administrators">This client does not have an administrator yet.</p>}
                    </div>
                </section>

                {client.multi_location_enabled ? <section ref={locationEditorRef} className="card sa-client-section sa-location-editor">
                    <Form key={editingLocation?.id ?? 'new'} action={editingLocation ? `/super-admin/organizations/${client.id}/locations/${editingLocation.id}` : `/super-admin/organizations/${client.id}/locations`} method={editingLocation ? 'put' : 'post'} resetOnSuccess={!editingLocation} onSuccess={() => setEditingLocation(null)}>
                        {({ errors, processing }) => <>
                            <div className="card-head"><div><strong>{editingLocation ? `Edit ${editingLocation.name}` : 'Add another gym'}</strong><span>{editingLocation ? 'Update this location’s contact information.' : 'Subscription settings are inherited from the primary gym.'}</span></div></div>
                            <div className="workspace-form-grid sa-location-form"><label>Location name<input name="name" defaultValue={editingLocation?.name ?? ''} required /></label><label>Contact email<input name="email" type="email" defaultValue={editingLocation?.email ?? ''} /></label><label>Phone<input name="phone" defaultValue={editingLocation?.phone ?? ''} /></label></div>
                            {Object.values(errors)[0] && <em className="form-error">{Object.values(errors)[0]}</em>}
                            <div className="modal-actions">{editingLocation && <button type="button" className="secondary" onClick={() => setEditingLocation(null)}>Cancel edit</button>}<button className="primary" disabled={processing}>{editingLocation ? <Edit3 /> : <Plus />} {processing ? 'Saving…' : editingLocation ? 'Save location' : 'Add location'}</button></div>
                        </>}
                    </Form>
                </section> : <div className="sa-multi-location-note"><MapPin /><div><strong>Single-gym client</strong><span>Enable Multiple gyms on the client card before adding another location.</span></div></div>}
            </main>
        </div>
        <FlashNotification />
    </>;
}
