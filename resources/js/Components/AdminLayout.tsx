import { Link, router, usePage } from '@inertiajs/react';
import {
    CalendarDays, ClipboardList, CreditCard, Dumbbell,
    LayoutDashboard, LogOut, Menu, Search,
    Settings, Undo2, UserCog, Users, X,
} from 'lucide-react';
import { ReactNode, useEffect, useState } from 'react';
import FlashNotification from '@/Components/FlashNotification';
import SelectDropdown from './SelectDropdown';

const navigation = [
    ['overview', 'Overview', '/admin/dashboard', LayoutDashboard], ['members', 'Members', '/admin/members', Users],
    ['payments', 'Payments', '/admin/payments', CreditCard], ['trainers', 'Trainers', '/admin/trainers', Dumbbell],
    ['schedule', 'Schedule', '/admin/schedule', CalendarDays], ['leads', 'Leads', '/admin/leads', ClipboardList],
] as const;

type Props = {
    activeSection: string;
    children: ReactNode;
    user: { name: string };
};

export default function AdminLayout({ activeSection, children, user }: Props) {
    const { gym, impersonating, staffImpersonating, branchAccess, adminPermissions, adminRoleName } = usePage<{ gym: { id: number; name: string } | null; impersonating: boolean; staffImpersonating: boolean; branchAccess: { activeGymId: number | null; gyms: { id: number; name: string }[] } | null; adminPermissions: string[]; adminRoleName: string | null }>().props;
    const [menuOpen, setMenuOpen] = useState(false);
    const [search, setSearch] = useState('');
    const initials = user.name.split(' ').map(part => part[0]).slice(0, 2).join('');

    const branding = gym ?? { name: 'Gym workspace' };
    const switchGym = (gymId: string) => {
        router.put(`/admin/active-gym/${gymId}`, {}, { preserveScroll: true });
        setMenuOpen(false);
    };
    const accessManagementHref = adminPermissions.includes('users.view') ? '/admin/users' : '/admin/roles';
    const canViewAccessManagement = adminPermissions.includes('users.view') || adminPermissions.includes('roles.view');
    const visibleNavigation = [
        ...navigation.filter(([module])=>adminPermissions.includes(`${module}.view`)),
        ...(canViewAccessManagement ? [['access', 'Users & Roles', accessManagementHref, UserCog] as const] : []),
    ];
    const openSearchResult = () => { const term=search.trim().toLowerCase();const match=visibleNavigation.find(([,label])=>label.toLowerCase().includes(term));if(match){router.visit(match[2]);setSearch('');} };
    useEffect(()=>{const shortcut=(event:KeyboardEvent)=>{if((event.metaKey||event.ctrlKey)&&event.key.toLowerCase()==='k'){event.preventDefault();document.querySelector<HTMLInputElement>('.global-search input')?.focus();}};window.addEventListener('keydown',shortcut);return()=>window.removeEventListener('keydown',shortcut);},[]);

    return <div className="app-shell">
        <aside className={`sidebar ${menuOpen ? 'open' : ''}`}>
            <Link href="/dashboard" className="brand"><span className="brand-mark"><Dumbbell size={20}/></span><span>{branding.name}</span></Link>
            <button className="close-menu" onClick={() => setMenuOpen(false)} aria-label="Close menu"><X/></button>
            <div className="branch-switcher"><div><small>MANAGING</small>{branchAccess && branchAccess.gyms.length > 1 ? <SelectDropdown className="branch-select" aria-label="Active gym branch" value={branchAccess.activeGymId ?? ''} onChange={event => switchGym(event.target.value)}>{branchAccess.gyms.map(branch => <option key={branch.id} value={branch.id}>{branch.name}</option>)}</SelectDropdown> : <strong>{branding.name}</strong>}</div>{adminPermissions.includes('settings.view')&&<Link className={`branch-settings ${activeSection === 'Settings' ? 'active' : ''}`} href="/settings/profile" aria-label="Open settings" title="Settings"><Settings size={19}/></Link>}</div>
            <nav>
                <small className="nav-label">WORKSPACE</small>
                {visibleNavigation.map(([,label, href, Icon]) => <Link key={label} href={href} className={activeSection === label ? 'active' : ''} onClick={() => setMenuOpen(false)}><Icon size={19}/><span>{label}</span></Link>)}
            </nav>
            <div className="sidebar-bottom">
                <div className="account-area">
                    <div className="profile"><span className="avatar avatar-dark">{initials}</span><div><strong>{user.name}</strong><span>{adminRoleName ?? 'Administrator'}</span></div></div>
                    <button className="sidebar-logout" aria-label="Sign out" title="Sign out" onClick={() => router.post('/logout')}><LogOut size={19}/></button>
                </div>
            </div>
        </aside>
        <main>
            <header className="topbar">
                <button className="menu-trigger" onClick={() => setMenuOpen(true)} aria-label="Open menu"><Menu/></button>
                <div className="global-search"><Search size={18}/><input aria-label="Jump to workspace page" value={search} onChange={event=>setSearch(event.target.value)} onKeyDown={event=>event.key==='Enter'&&openSearchResult()} placeholder="Jump to members, payments, leads..."/><kbd>⌘ K</kbd></div>
                {impersonating && <button className="impersonation-return" onClick={() => router.post('/super-admin/impersonation/exit')}><Undo2/> Return to Super Admin</button>}
                {staffImpersonating && <button className="impersonation-return" onClick={() => router.post('/admin/staff-impersonation/exit')}><Undo2/> Return to Owner</button>}
            </header>
            <div className="content">{children}</div>
        </main>
        {menuOpen && <div className="scrim" onClick={() => setMenuOpen(false)}/>} 
        <FlashNotification/>
    </div>;
}
