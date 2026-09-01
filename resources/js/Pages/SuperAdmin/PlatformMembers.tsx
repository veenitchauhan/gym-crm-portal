import FlashNotification from '@/Components/FlashNotification';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, LogOut, Search, ShieldCheck, Users } from 'lucide-react';
import { useMemo, useState } from 'react';

type Member = { id: number; name: string; email: string; phone: string | null; client: { id: number; name: string }; gym: { id: number; name: string; type: 'Primary gym' | 'Branch' }; plan: string; status: 'Active' | 'Inactive'; joined: string };

export default function PlatformMembers({ members, superAdmin }: { members: Member[]; superAdmin: { name: string; username: string } }) {
    const [query, setQuery] = useState('');
    const visibleMembers = useMemo(() => {
        const term = query.trim().toLowerCase();
        return term ? members.filter(member => [member.name, member.email, member.phone, member.client.name, member.gym.name, member.plan].some(value => value?.toLowerCase().includes(term))) : members;
    }, [members, query]);

    return <><Head title="All members · Super Admin" /><div className="sa-shell">
        <header className="sa-top"><div className="sa-login-brand"><span><ShieldCheck /></span><div><strong>Gym CRM Portal</strong><small>Super Admin</small></div></div><div><span>{superAdmin.name}</span><button onClick={() => router.post('/super-admin/logout')}><LogOut /> Sign out</button></div></header>
        <main className="sa-content sa-client-page sa-client-members-page"><Link className="sa-client-back" href="/super-admin/gyms"><ArrowLeft /> Back to gym clients</Link>
            <section className="page-heading"><div><span className="eyebrow">PLATFORM MEMBERS</span><h1>All members</h1><p>Members registered across every client, primary gym, and branch.</p></div></section>
            <section className="card sa-client-section"><div className="sa-member-directory-toolbar"><label><Search /><input value={query} onChange={event => setQuery(event.target.value)} placeholder="Search members, clients, gyms, or plans..." /></label><strong>{visibleMembers.length} {visibleMembers.length === 1 ? 'member' : 'members'}</strong></div>
                {visibleMembers.length > 0 ? <div className="sa-member-directory platform"><div className="sa-member-directory-row labels"><span>Member</span><span>Client</span><span>Gym / branch</span><span>Membership plan</span><span>Status</span><span>Joined</span></div>{visibleMembers.map(member => <div className="sa-member-directory-row" key={member.id}>
                    <div className="sa-member-identity"><span className="avatar">{member.name.split(' ').map(part => part[0]).slice(0, 2).join('').toUpperCase()}</span><div><strong>{member.name}</strong><small>{member.email}{member.phone ? ` · ${member.phone}` : ''}</small></div></div>
                    <Link className="sa-member-client-link" href={`/super-admin/organizations/${member.client.id}`}>{member.client.name}</Link><div className="sa-member-gym"><strong>{member.gym.name}</strong><small className={member.gym.type === 'Primary gym' ? 'primary' : 'branch'}>{member.gym.type}</small></div><span>{member.plan}</span><span><i className={`status status-${member.status.toLowerCase()}`}>{member.status}</i></span><span>{member.joined}</span>
                </div>)}</div> : <div className="sa-member-empty"><Users /><strong>{members.length === 0 ? 'No members yet' : 'No matching members'}</strong><span>{members.length === 0 ? 'Members created by gym clients will appear here.' : 'Try another search term.'}</span></div>}
            </section>
        </main></div><FlashNotification /></>;
}
