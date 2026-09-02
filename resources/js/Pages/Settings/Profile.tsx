import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Components/AdminLayout';
import FlashNotification from '@/Components/FlashNotification';
import { ArrowLeft, Database, Dumbbell, KeyRound, Plus, Save, ShieldCheck, Trash2, UserRound } from 'lucide-react';
import { useEffect, useState } from 'react';

type User = { name: string; email: string; phone: string | null; role: 'admin' | 'member'; must_change_password: boolean };
type DropdownOption = { id: number; label: string; is_active: boolean; amount: number | null; minimumAmount: number | null };
type DropdownCategory = { key: string; label: string; options: DropdownOption[] };

export default function Profile({ user, dropdownCategories = [] }: { user: User; dropdownCategories?: DropdownCategory[] }) {
    const home = user.role === 'admin' ? '/admin/dashboard' : '/member/dashboard';
    const { gym, adminPermissions } = usePage<{ gym: { name: string } | null; adminPermissions: string[] }>().props;
    const gymName = gym?.name ?? 'Your gym';

    const settingsPanels = <div className="settings-panels">
            {!user.must_change_password && <section className="card settings-card" id="profile"><div className="settings-card-head"><span><UserRound/></span><div><h2>Profile information</h2><p>Update your name and contact details.</p></div></div><Form action="/settings/profile" method="patch" setDefaultsOnSuccess>{({errors,processing,recentlySuccessful})=><><div className="settings-form"><label>Full name<input name="name" defaultValue={user.name}/>{errors.name&&<em>{errors.name}</em>}</label><label>Email address<input name="email" type="email" defaultValue={user.email}/>{errors.email&&<em>{errors.email}</em>}</label><label>Phone number<input name="phone" defaultValue={user.phone ?? ''} placeholder="Add a phone number"/>{errors.phone&&<em>{errors.phone}</em>}</label><label>Account type<input value={user.role === 'admin' ? 'Administrator' : 'Gym member'} disabled/></label></div><div className="settings-actions"><span>{recentlySuccessful && 'Saved'}</span><button className="primary" disabled={processing}>{processing?'Saving…':'Save changes'}</button></div></>}</Form></section>}
            <section className="card settings-card" id="password"><div className="settings-card-head"><span><ShieldCheck/></span><div><h2>Password & security</h2><p>Use a unique password with at least 8 characters.</p></div></div>{user.must_change_password && <div className="forced-password-notice"><KeyRound/><div><strong>Temporary password detected</strong><span>Set a new private password before using the rest of the portal.</span></div></div>}<Form action="/settings/password" method="put" resetOnSuccess>{({errors,processing,recentlySuccessful})=><><div className="settings-form single"><label>Current password<input name="current_password" type="password" autoFocus={user.must_change_password}/>{errors.current_password&&<em>{errors.current_password}</em>}</label><label>New password<input name="password" type="password"/>{errors.password&&<em>{errors.password}</em>}</label><label>Confirm new password<input name="password_confirmation" type="password"/></label></div><div className="settings-actions"><span>{recentlySuccessful&&'Password updated'}</span><button className="primary" disabled={processing}>{processing?'Updating…':'Update password'}</button></div></>}</Form></section>
        </div>;

    if (user.role === 'admin') {
        return <><Head title="Account settings · Gym CRM Portal"/><AdminLayout activeSection="Settings" user={user}><section className="page-heading"><div><span className="eyebrow">ADMINISTRATION</span><h1>{user.must_change_password ? 'Change your password' : 'Settings'}</h1><p>{user.must_change_password ? 'Replace the temporary password to unlock the portal.' : 'Manage your account and portal dropdown data.'}</p></div></section><SettingsWorkspace categories={dropdownCategories} accountPanels={settingsPanels} forcePasswordChange={user.must_change_password} permissions={adminPermissions}/></AdminLayout></>;
    }

    return <><Head title="Account settings · Gym CRM Portal"/><div className="settings-shell">
        <header className="settings-top"><Link href={home} className="auth-brand dark"><span><Dumbbell size={18}/></span>{gymName}</Link><Link href={home}><ArrowLeft size={17}/> Back to dashboard</Link></header>
        <main className="settings-content"><aside><span className="eyebrow">ACCOUNT</span><h1>Settings</h1><p>Manage your personal information and security.</p><nav><a href="#profile" className="active"><UserRound/> Profile</a><a href="#password"><KeyRound/> Password</a></nav></aside>{settingsPanels}</main>
        <FlashNotification/>
    </div></>;
}

function SettingsWorkspace({ categories, accountPanels, forcePasswordChange = false, permissions }: { categories: DropdownCategory[]; accountPanels: React.ReactNode; forcePasswordChange?: boolean; permissions: string[] }) {
    const [section, setSection] = useState<'account' | 'dropdowns'>('account');
    const [categoryKey, setCategoryKey] = useState(categories[0]?.key ?? '');
    const selectedCategory = categories.find(category => category.key === categoryKey);

    return <div className="settings-workspace">
        <aside className="settings-subnav">
            <span>SETTINGS</span>
            <button className={section === 'account' ? 'active' : ''} onClick={() => setSection('account')}><UserRound/> Account & security</button>
            {!forcePasswordChange && categories.length > 0 && <button className={section === 'dropdowns' ? 'active' : ''} onClick={() => setSection('dropdowns')}><Database/> Dropdown data</button>}
            {section === 'dropdowns' && <nav>{categories.map(category => <button key={category.key} className={categoryKey === category.key ? 'active' : ''} onClick={() => setCategoryKey(category.key)}>{category.label}<em>{category.options.length}</em></button>)}</nav>}
        </aside>
        <div className="settings-workspace-body">
            {section === 'account' ? accountPanels : selectedCategory && <DropdownManager key={selectedCategory.key} category={selectedCategory} permissions={permissions}/>}
        </div>
    </div>;
}

function DropdownManager({ category, permissions }: { category: DropdownCategory; permissions: string[] }) {
    const [options, setOptions] = useState(category.options);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        setOptions(category.options);
    }, [category.options]);

    const updateOption = (id: number, changes: Partial<DropdownOption>) => setOptions(current => current.map(option => option.id === id ? { ...option, ...changes } : option));
    const saveAll = () => router.put('/settings/dropdown-options', { category: category.key, options }, { preserveScroll: true, onStart: () => setSaving(true), onFinish: () => setSaving(false) });

    return <section className="card dropdown-manager">
        <div className="settings-card-head dropdown-manager-head"><span><Database/></span><div><h2>{category.label}</h2><p>These values appear in portal forms and dropdowns immediately.</p></div>{permissions.includes('settings.edit')&&<button className="primary" onClick={saveAll} disabled={saving || options.length === 0}><Save/>{saving ? 'Saving…' : 'Save all changes'}</button>}</div>
        {permissions.includes('settings.create')&&<Form action="/settings/dropdown-options" method="post" resetOnSuccess className={`dropdown-create ${category.key === 'membership_plans' ? 'with-amount' : ''}`}>{({ errors, processing }) => <><input type="hidden" name="category" value={category.key}/><label>New option<input name="label" placeholder={`Add to ${category.label.toLowerCase()}`} required/>{errors.label && <em>{errors.label}</em>}</label>{category.key === 'membership_plans' && <><label>Plan amount (₹)<input name="amount" type="number" min="0" step="0.01" placeholder="Enter amount" required/>{errors.amount && <em>{errors.amount}</em>}</label><label>Minimum amount (₹)<input name="minimumAmount" type="number" min="0" step="0.01" placeholder="Enter minimum" required/>{errors.minimumAmount && <em>{errors.minimumAmount}</em>}</label></>}<button className="primary" disabled={processing}><Plus/> Add option</button></>}</Form>}
        <div className={`dropdown-list ${category.key === 'membership_plans' ? 'membership-plan-options' : ''}`}>{options.length === 0 ? <div className="compact-empty"><Database/><strong>No options yet</strong><span>Add the first value for this dropdown.</span></div> : options.map(option => <DropdownRow key={option.id} option={option} showAmount={category.key === 'membership_plans'} canEdit={permissions.includes('settings.edit')} canDelete={permissions.includes('settings.delete')} update={changes => updateOption(option.id, changes)}/>)}</div>
    </section>;
}

function DropdownRow({ option, showAmount, canEdit, canDelete, update }: { option: DropdownOption; showAmount: boolean; canEdit: boolean; canDelete: boolean; update: (changes: Partial<DropdownOption>) => void }) {
    const save = () => router.put(`/settings/dropdown-options/${option.id}`, { label: option.label, amount: option.amount, minimumAmount: option.minimumAmount, is_active: option.is_active }, { preserveScroll: true });
    const remove = () => window.confirm(`Delete “${option.label}”?`) && router.delete(`/settings/dropdown-options/${option.id}`, { preserveScroll: true });

    return <div className="dropdown-row"><input aria-label="Plan or option name" value={option.label} disabled={!canEdit} onChange={event => update({ label: event.target.value })}/>{showAmount && <><input aria-label={`${option.label} plan amount`} title="Plan amount" type="number" min="0" step="0.01" value={option.amount ?? ''} disabled={!canEdit} onChange={event => update({ amount: event.target.value === '' ? null : Number(event.target.value) })}/><input aria-label={`${option.label} minimum payment amount`} title="Minimum payment amount" type="number" min="0" step="0.01" value={option.minimumAmount ?? ''} disabled={!canEdit} onChange={event => update({ minimumAmount: event.target.value === '' ? null : Number(event.target.value) })}/></>}<label className="status-toggle"><input type="checkbox" checked={option.is_active} disabled={!canEdit} onChange={event => update({ is_active: event.target.checked })}/><span>{option.is_active ? 'Active' : 'Inactive'}</span></label>{canEdit&&<button className="secondary" onClick={save}><Save/> Save</button>}{canDelete&&<button className="danger-icon" onClick={remove} aria-label={`Delete ${option.label}`}><Trash2/></button>}</div>;
}
