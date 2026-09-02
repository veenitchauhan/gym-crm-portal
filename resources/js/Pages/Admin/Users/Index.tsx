import AdminLayout from '@/Components/AdminLayout';
import SelectDropdown from '@/Components/SelectDropdown';
import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Crown, KeyRound, LoaderCircle, LogIn, Mail, Pencil, Plus, ShieldCheck, Trash2, UserCog, Users, X } from 'lucide-react';
import { useState } from 'react';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    roleId: number | null;
    roleName: string;
    isOwner: boolean;
};

type AccessRole = { id: number; name: string; usersCount: number };

export default function UsersIndex({ users, roles, canLoginAsUsers }: { users: ManagedUser[]; roles: AccessRole[]; canLoginAsUsers: boolean }) {
    const page = usePage<{ auth: { user: { name: string } }; adminPermissions: string[] }>();
    const { auth, adminPermissions } = page.props;
    const [editingUser, setEditingUser] = useState<ManagedUser | null>(null);
    const [creating, setCreating] = useState(page.url.includes('create=1'));
    const [pendingUserActions, setPendingUserActions] = useState<Record<number, 'reset' | 'login' | undefined>>({});
    const canCreate = adminPermissions.includes('users.create');
    const canEdit = adminPermissions.includes('users.edit');
    const canDelete = adminPermissions.includes('users.delete');
    const canViewRoles = adminPermissions.includes('roles.view');
    const owners = users.filter(user => user.isOwner);
    const staff = users.filter(user => ! user.isOwner);
    const addUser = () => setCreating(true);
    const resetPassword = (user: ManagedUser) => {
        if (! window.confirm(`Reset ${user.name}'s password to P@ssw0rd? Their sessions will end and they must change it after login.`)) {
            return;
        }

        router.put(`/admin/users/${user.id}/temporary-password`, {}, {
            preserveScroll: true,
            onStart: () => setPendingUserActions(current => ({ ...current, [user.id]: 'reset' })),
            onFinish: () => setPendingUserActions(current => ({ ...current, [user.id]: undefined })),
        });
    };
    const loginAsUser = (user: ManagedUser) => router.post(`/admin/users/${user.id}/login`, {}, {
        onStart: () => setPendingUserActions(current => ({ ...current, [user.id]: 'login' })),
        onFinish: () => setPendingUserActions(current => ({ ...current, [user.id]: undefined })),
    });

    return <>
        <Head title="Users Management · Gym CRM Portal"/>
        <AdminLayout activeSection="Users & Roles" user={auth.user}>
            <section className="page-heading access-page-heading">
                <div><span className="eyebrow">TEAM ACCESS</span><h1>Users Management</h1><p>Create staff accounts and assign exactly what each person can manage.</p></div>
                {canCreate && <button className="primary" onClick={addUser}><Plus/> Add user</button>}
            </section>
            <nav className="access-tabs" aria-label="Access management">
                <Link className="active" href="/admin/users"><Users/> Users</Link>
                {canViewRoles && <Link href="/admin/roles"><ShieldCheck/> Roles & permissions</Link>}
            </nav>

            <section className="access-summary-grid">
                <article><span><Users/></span><div><small>Total users</small><strong>{users.length}</strong></div></article>
                <article><span><Crown/></span><div><small>Owners</small><strong>{owners.length}</strong></div></article>
                <article><span><UserCog/></span><div><small>Staff users</small><strong>{staff.length}</strong></div></article>
            </section>

            <section className="card access-directory">
                <div className="access-section-head"><div><h2>Workspace users</h2><p>Owners retain full access. Staff permissions come from their assigned role.</p></div><span>{users.length} {users.length === 1 ? 'user' : 'users'}</span></div>
                <div className="staff-grid">
                    {users.map(user => <article className="staff-card" key={user.id}>
                        <div className="staff-avatar">{user.name.split(' ').map(part => part[0]).slice(0, 2).join('').toUpperCase()}</div>
                        <div className="staff-identity"><div><h3>{user.name}</h3><span className={`access-badge ${user.isOwner ? 'owner' : (! user.roleId ? 'unassigned' : '')}`}>{user.isOwner && <Crown/>}{user.roleName}</span></div><p><Mail/>{user.email}</p><small>{user.phone || 'No phone number'}</small></div>
                        <div className="staff-actions">
                            {user.isOwner ? <span className="protected-account"><ShieldCheck/> Protected account</span> : <>
                                {canLoginAsUsers && <button className="secondary" disabled={pendingUserActions[user.id] !== undefined} onClick={() => resetPassword(user)}>{pendingUserActions[user.id] === 'reset' ? <LoaderCircle className="animate-spin"/> : <KeyRound/>}{pendingUserActions[user.id] === 'reset' ? 'Resetting…' : 'Reset password'}</button>}
                                {canLoginAsUsers && <button className="secondary" disabled={pendingUserActions[user.id] !== undefined} onClick={() => loginAsUser(user)}>{pendingUserActions[user.id] === 'login' ? <LoaderCircle className="animate-spin"/> : <LogIn/>}{pendingUserActions[user.id] === 'login' ? 'Logging in…' : 'Login'}</button>}
                                {canEdit && <button className="secondary" onClick={() => setEditingUser(user)}><Pencil/> Edit</button>}
                                {canDelete && <button className="danger-icon" aria-label={`Delete ${user.name}`} onClick={() => window.confirm(`Delete ${user.name}'s account?`) && router.delete(`/admin/users/${user.id}`, { preserveScroll: true })}><Trash2/></button>}
                            </>}
                        </div>
                    </article>)}
                </div>
            </section>
        </AdminLayout>
        {(creating || editingUser) && <UserModal user={editingUser} roles={roles} close={() => { setCreating(false); setEditingUser(null); }}/>} 
    </>;
}

function UserModal({ user, roles, close }: { user: ManagedUser | null; roles: AccessRole[]; close: () => void }) {
    const [selectedRoleId, setSelectedRoleId] = useState(String(user?.roleId ?? ''));

    return <div className="modal-backdrop" onMouseDown={close}>
        <Form action={user ? `/admin/users/${user.id}` : '/admin/users'} method={user ? 'put' : 'post'} className="modal access-modal" onMouseDown={event => event.stopPropagation()} onSuccess={close}>
            {({ errors, processing }) => <>
                <div className="modal-head"><div><span className="access-modal-icon"><UserCog/></span><div><h2>{user ? 'Edit user' : 'Add workspace user'}</h2><p>{user ? 'Update their details or assigned role.' : 'They can sign in with the temporary password P@ssw0rd.'}</p></div></div><button type="button" onClick={close} aria-label="Close"><X/></button></div>
                <div className="form-row"><label>Full name<input name="name" defaultValue={user?.name ?? ''} required autoFocus/>{errors.name && <em>{errors.name}</em>}</label><label>Email address<input name="email" type="email" defaultValue={user?.email ?? ''} required/>{errors.email && <em>{errors.email}</em>}</label></div>
                <div className="form-row"><label>Phone number<input name="phone" defaultValue={user?.phone ?? ''} placeholder="Optional"/>{errors.phone && <em>{errors.phone}</em>}</label><label>Access role<SelectDropdown name="access_role_id" value={selectedRoleId} onChange={event => setSelectedRoleId(event.target.value)}><option value="">No role assigned</option>{roles.map(role => <option key={role.id} value={role.id}>{role.name}</option>)}</SelectDropdown>{errors.access_role_id && <em>{errors.access_role_id}</em>}</label></div>
                {! selectedRoleId && <div className="unassigned-role-note"><ShieldCheck/><span><strong>No role is required</strong>An unassigned user can sign in but cannot access workspace modules until a role is attached.</span></div>}
                {! user && <div className="email-setup-note"><Mail/><span><strong>Temporary password: P@ssw0rd</strong>It is emailed to the user and must be changed immediately after login.</span></div>}
                <div className="modal-actions"><button type="button" className="secondary" onClick={close}>Cancel</button><button className="primary" disabled={processing}>{processing ? (user ? 'Saving…' : 'Creating…') : (user ? 'Save changes' : 'Create user')}</button></div>
            </>}
        </Form>
    </div>;
}
