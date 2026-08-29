import { Link, router, usePage } from '@inertiajs/react';
import {
    Bell, CalendarDays, ChevronDown, ClipboardList, CreditCard, Dumbbell,
    LayoutDashboard, LogOut, Menu, MessageCircle, MoreHorizontal, Search,
    Settings, Undo2, UserRound, UserRoundCheck, Users, WalletCards, X,
} from 'lucide-react';
import { ReactNode, useState } from 'react';
import FlashNotification from '@/Components/FlashNotification';

const navigation = [
    ['Overview', '/admin/dashboard', LayoutDashboard], ['Members', '/admin/members', Users],
    ['Attendance', '/admin/attendance', UserRoundCheck], ['Memberships', '/admin/memberships', WalletCards],
    ['Payments', '/admin/payments', CreditCard], ['Trainers', '/admin/trainers', Dumbbell],
    ['Schedule', '/admin/schedule', CalendarDays], ['Leads', '/admin/leads', ClipboardList],
] as const;

type Props = {
    activeSection: string;
    children: ReactNode;
    user: { name: string };
};

export default function AdminLayout({ activeSection, children, user }: Props) {
    const { gym, impersonating } = usePage<{ gym: { name: string; logoText: string; primaryColor: string; accentColor: string } | null; impersonating: boolean }>().props;
    const [menuOpen, setMenuOpen] = useState(false);
    const [accountOpen, setAccountOpen] = useState(false);
    const initials = user.name.split(' ').map(part => part[0]).slice(0, 2).join('');

    const branding = gym ?? { name: 'Gym workspace', logoText: 'Gym CRM Portal', primaryColor: '#7357e8', accentColor: '#202126' };

    return <div className="app-shell" style={{ '--violet': branding.primaryColor, '--ink': branding.accentColor } as React.CSSProperties}>
        <aside className={`sidebar ${menuOpen ? 'open' : ''}`}>
            <Link href="/admin/dashboard" className="brand"><span className="brand-mark" style={{background:branding.accentColor}}><Dumbbell size={20}/></span><span>{branding.name}</span></Link>
            <button className="close-menu" onClick={() => setMenuOpen(false)} aria-label="Close menu"><X/></button>
            <div className="location"><div><small>MANAGING</small><strong>{branding.name}</strong></div><ChevronDown size={16}/></div>
            <nav>
                <small className="nav-label">WORKSPACE</small>
                {navigation.map(([label, href, Icon]) => <Link key={label} href={href} className={activeSection === label ? 'active' : ''} onClick={() => setMenuOpen(false)}><Icon size={19}/><span>{label}</span></Link>)}
            </nav>
            <div className="sidebar-bottom">
                <Link className={`sidebar-link ${activeSection === 'Settings' ? 'active' : ''}`} href="/settings/profile"><Settings size={19}/><span>Settings</span></Link>
                <div className="account-area">
                    <div className="profile"><span className="avatar avatar-dark">{initials}</span><div><strong>{user.name}</strong><span>Administrator</span></div><button className="profile-menu-trigger" aria-label="Open account menu" onClick={() => setAccountOpen(!accountOpen)}><MoreHorizontal size={18}/></button></div>
                    {accountOpen && <div className="account-popover"><div><strong>{user.name}</strong><span>Club administrator</span></div><Link href="/settings/profile"><UserRound/> View profile</Link><Link href="/settings/profile"><Settings/> Account settings</Link><button onClick={() => router.post('/logout')}><LogOut/> Sign out</button></div>}
                </div>
            </div>
        </aside>
        <main>
            <header className="topbar">
                <button className="menu-trigger" onClick={() => setMenuOpen(true)} aria-label="Open menu"><Menu/></button>
                <div className="global-search"><Search size={18}/><input aria-label="Search" placeholder="Search members, payments, leads..."/><kbd>⌘ K</kbd></div>
                {impersonating && <button className="impersonation-return" onClick={() => router.post('/super-admin/impersonation/exit')}><Undo2/> Return to Super Admin</button>}
                <button className="icon-btn" aria-label="Messages"><MessageCircle size={19}/><i/></button>
                <button className="icon-btn" aria-label="Notifications"><Bell size={19}/><i/></button>
            </header>
            <div className="content">{children}</div>
        </main>
        {menuOpen && <div className="scrim" onClick={() => setMenuOpen(false)}/>} 
        <FlashNotification/>
    </div>;
}
