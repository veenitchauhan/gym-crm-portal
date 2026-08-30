import { Link, router, usePage } from '@inertiajs/react';
import {
    CalendarDays, ClipboardList, CreditCard, Dumbbell,
    LayoutDashboard, LogOut, Menu, Search,
    Settings, Undo2, Users, X,
} from 'lucide-react';
import { ReactNode, useEffect, useState } from 'react';
import FlashNotification from '@/Components/FlashNotification';

const navigation = [
    ['Overview', '/admin/dashboard', LayoutDashboard], ['Members', '/admin/members', Users],
    ['Payments', '/admin/payments', CreditCard], ['Trainers', '/admin/trainers', Dumbbell],
    ['Schedule', '/admin/schedule', CalendarDays], ['Leads', '/admin/leads', ClipboardList],
] as const;

type Props = {
    activeSection: string;
    children: ReactNode;
    user: { name: string };
};

export default function AdminLayout({ activeSection, children, user }: Props) {
    const { gym, impersonating } = usePage<{ gym: { name: string } | null; impersonating: boolean }>().props;
    const [menuOpen, setMenuOpen] = useState(false);
    const [search, setSearch] = useState('');
    const initials = user.name.split(' ').map(part => part[0]).slice(0, 2).join('');

    const branding = gym ?? { name: 'Gym workspace' };
    const openSearchResult = () => { const term=search.trim().toLowerCase();const match=navigation.find(([label])=>label.toLowerCase().includes(term));if(match){router.visit(match[1]);setSearch('');} };
    useEffect(()=>{const shortcut=(event:KeyboardEvent)=>{if((event.metaKey||event.ctrlKey)&&event.key.toLowerCase()==='k'){event.preventDefault();document.querySelector<HTMLInputElement>('.global-search input')?.focus();}};window.addEventListener('keydown',shortcut);return()=>window.removeEventListener('keydown',shortcut);},[]);

    return <div className="app-shell">
        <aside className={`sidebar ${menuOpen ? 'open' : ''}`}>
            <Link href="/admin/dashboard" className="brand"><span className="brand-mark"><Dumbbell size={20}/></span><span>{branding.name}</span></Link>
            <button className="close-menu" onClick={() => setMenuOpen(false)} aria-label="Close menu"><X/></button>
            <div className="location"><div><small>MANAGING</small><strong>{branding.name}</strong></div><Link className={`location-settings ${activeSection === 'Settings' ? 'active' : ''}`} href="/settings/profile" aria-label="Open settings" title="Settings"><Settings size={19}/></Link></div>
            <nav>
                <small className="nav-label">WORKSPACE</small>
                {navigation.map(([label, href, Icon]) => <Link key={label} href={href} className={activeSection === label ? 'active' : ''} onClick={() => setMenuOpen(false)}><Icon size={19}/><span>{label}</span></Link>)}
            </nav>
            <div className="sidebar-bottom">
                <div className="account-area">
                    <div className="profile"><span className="avatar avatar-dark">{initials}</span><div><strong>{user.name}</strong><span>Administrator</span></div></div>
                    <button className="sidebar-logout" aria-label="Sign out" title="Sign out" onClick={() => router.post('/logout')}><LogOut size={19}/></button>
                </div>
            </div>
        </aside>
        <main>
            <header className="topbar">
                <button className="menu-trigger" onClick={() => setMenuOpen(true)} aria-label="Open menu"><Menu/></button>
                <div className="global-search"><Search size={18}/><input aria-label="Jump to workspace page" value={search} onChange={event=>setSearch(event.target.value)} onKeyDown={event=>event.key==='Enter'&&openSearchResult()} placeholder="Jump to members, payments, leads..."/><kbd>⌘ K</kbd></div>
                {impersonating && <button className="impersonation-return" onClick={() => router.post('/super-admin/impersonation/exit')}><Undo2/> Return to Super Admin</button>}
            </header>
            <div className="content">{children}</div>
        </main>
        {menuOpen && <div className="scrim" onClick={() => setMenuOpen(false)}/>} 
        <FlashNotification/>
    </div>;
}
