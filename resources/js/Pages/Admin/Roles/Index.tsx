import AdminLayout from '@/Components/AdminLayout';
import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, Pencil, Plus, ShieldCheck, Trash2, UserCog, Users, X } from 'lucide-react';
import { useState } from 'react';

type AccessRole = { id: number; name: string; permissions: string[]; usersCount: number };
type ModuleDefinition = { label: string; actions: string[] };

const actionLabels: Record<string, string> = { view: 'View', create: 'Create', edit: 'Edit', delete: 'Delete' };

export default function RolesIndex({ roles, modules, allowedPermissions }: { roles: AccessRole[]; modules: Record<string, ModuleDefinition>; allowedPermissions: string[] }) {
    const page = usePage<{ auth: { user: { name: string } }; adminPermissions: string[] }>();
    const { auth, adminPermissions } = page.props;
    const canCreate = adminPermissions.includes('roles.create');
    const canEdit = adminPermissions.includes('roles.edit');
    const canDelete = adminPermissions.includes('roles.delete');
    const canViewUsers = adminPermissions.includes('users.view');
    const [creating, setCreating] = useState(false);
    const [editingRole, setEditingRole] = useState<AccessRole | null>(null);
    const closeModal = () => { setCreating(false); setEditingRole(null); };

    return <>
        <Head title="Roles Management · Gym CRM Portal"/>
        <AdminLayout activeSection="Users & Roles" user={auth.user}>
            <section className="page-heading access-page-heading"><div><span className="eyebrow">ACCESS CONTROL</span><h1>Roles Management</h1><p>Build reusable permission sets for your managers, receptionists, and other staff.</p></div>{canCreate && <button className="primary" onClick={() => setCreating(true)}><Plus/> Create role</button>}</section>
            <nav className="access-tabs" aria-label="Access management">{canViewUsers && <Link href="/admin/users"><Users/> Users</Link>}<Link className="active" href="/admin/roles"><ShieldCheck/> Roles & permissions</Link></nav>

            <section className="card access-directory">
                <div className="access-section-head"><div><h2>Access roles</h2><p>Permissions are enforced across navigation, actions, and server requests.</p></div><span>{roles.length} {roles.length === 1 ? 'role' : 'roles'}</span></div>
                {roles.length === 0 ? <div className="compact-empty table-empty"><ShieldCheck/><strong>No roles configured</strong><span>Create your first role to invite staff with limited access.</span>{canCreate && <button className="secondary empty-state-action" onClick={() => setCreating(true)}>Create first role</button>}</div> : <div className="role-grid">
                    {roles.map(role => <article className="role-card" key={role.id}>
                        <div className="role-card-icon"><ShieldCheck/></div>
                        <div className="role-card-main"><div><h3>{role.name}</h3><span>{role.permissions.length} {role.permissions.length === 1 ? 'permission' : 'permissions'}</span></div><p><Users/>{role.usersCount} assigned {role.usersCount === 1 ? 'user' : 'users'}</p><div className="role-permission-preview">{permissionGroups(role.permissions, modules).slice(0, 4).map(label => <span key={label}>{label}</span>)}</div></div>
                        <div className="role-card-actions">{canEdit && <button className="secondary" onClick={() => setEditingRole(role)}><Pencil/> Edit permissions</button>}{canDelete && <button className="danger-icon" disabled={role.usersCount > 0} title={role.usersCount > 0 ? 'Reassign users before deleting this role' : 'Delete role'} aria-label={`Delete ${role.name}`} onClick={() => window.confirm(`Delete the ${role.name} role?`) && router.delete(`/admin/roles/${role.id}`, { preserveScroll: true })}><Trash2/></button>}</div>
                    </article>)}
                </div>}
            </section>
        </AdminLayout>
        {(creating || editingRole) && <RoleModal role={editingRole} modules={modules} allowedPermissions={allowedPermissions} close={closeModal} saved={closeModal}/>} 
    </>;
}

function RoleModal({ role, modules, allowedPermissions, close, saved }: { role: AccessRole | null; modules: Record<string, ModuleDefinition>; allowedPermissions: string[]; close: () => void; saved: () => void }) {
    const [permissions, setPermissions] = useState<string[]>(role?.permissions ?? []);
    const availableModules = Object.entries(modules).filter(([module, definition]) => definition.actions.some(action => allowedPermissions.includes(`${module}.${action}`)));
    const togglePermission = (module: string, action: string, checked: boolean) => {
        const key = `${module}.${action}`;

        setPermissions(current => {
            if (! checked) {
                return action === 'view' ? current.filter(permission => ! permission.startsWith(`${module}.`)) : current.filter(permission => permission !== key);
            }

            const additions = action === 'view' ? [key] : [key, `${module}.view`];
            return Array.from(new Set([...current, ...additions]));
        });
    };

    return <div className="modal-backdrop" onMouseDown={close}>
        <Form action={role ? `/admin/roles/${role.id}` : '/admin/roles'} method={role ? 'put' : 'post'} className="modal role-modal" onMouseDown={event => event.stopPropagation()} onSuccess={saved}>
            {({ errors, processing }) => <>
                <div className="modal-head"><div><span className="access-modal-icon"><ShieldCheck/></span><div><h2>{role ? `Edit ${role.name}` : 'Create access role'}</h2><p>Select what this role can see and what actions it can perform.</p></div></div><button type="button" onClick={close} aria-label="Close"><X/></button></div>
                <div className="role-name-field"><label>Role name<input name="name" defaultValue={role?.name ?? ''} placeholder="e.g. Receptionist" required autoFocus/>{errors.name && <em>{errors.name}</em>}</label><div><Eye/><span><strong>View controls visibility</strong>Turning it off also clears create, edit, and delete for that module.</span></div></div>
                {permissions.map(permission => <input key={permission} type="hidden" name="permissions[]" value={permission}/>)}
                <div className="permission-matrix">
                    <div className="permission-row permission-head"><strong>Module or feature</strong>{['view', 'create', 'edit', 'delete'].map(action => <span key={action}>{actionLabels[action]}</span>)}</div>
                    {availableModules.map(([module, definition]) => <div className="permission-row" key={module}><strong>{definition.label}</strong>{['view', 'create', 'edit', 'delete'].map(action => {
                        const key = `${module}.${action}`;
                        const exists = definition.actions.includes(action);
                        const allowed = allowedPermissions.includes(key);
                        return <span key={action}>{exists && allowed ? <label className="permission-check"><input type="checkbox" checked={permissions.includes(key)} onChange={event => togglePermission(module, action, event.target.checked)}/><i/></label> : <b>—</b>}</span>;
                    })}</div>)}
                </div>
                {errors.permissions && <em className="permission-error">{errors.permissions}</em>}
                <div className="modal-actions"><span className="permission-count">{permissions.length} selected</span><button type="button" className="secondary" onClick={close}>Cancel</button><button className="primary" disabled={processing || permissions.length === 0}>{processing ? 'Saving…' : (role ? 'Save permissions' : 'Create role')}</button></div>
            </>}
        </Form>
    </div>;
}

function permissionGroups(permissions: string[], modules: Record<string, ModuleDefinition>): string[] {
    return Object.entries(modules).filter(([module]) => permissions.some(permission => permission.startsWith(`${module}.`))).map(([module, definition]) => {
        const count = permissions.filter(permission => permission.startsWith(`${module}.`)).length;
        return `${definition.label} · ${count}`;
    });
}
