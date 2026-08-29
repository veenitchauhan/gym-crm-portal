import { Head, Link, router, usePage } from '@inertiajs/react';
import { Activity, CalendarDays, ChevronRight, Clock3, CreditCard, Dumbbell, LogOut, Settings, Trophy } from 'lucide-react';

type Props = { member: { name: string; membership_plan: string | null; membership_expires_at: string | null } };

export default function MemberDashboard({ member }: Props) {
    const firstName = member.name.split(' ')[0];
    const gym = usePage<{ gym: { name: string; primaryColor: string; accentColor: string } | null }>().props.gym;
    const gymName = gym?.name ?? 'Your gym';

    return <><Head title={`My membership · ${gymName}`}/><div className="member-app" style={{'--violet':gym?.primaryColor ?? '#7357e8','--ink':gym?.accentColor ?? '#202126'} as React.CSSProperties}>
        <header className="member-topbar"><div className="auth-brand dark"><span style={{background:gym?.accentColor}}><Dumbbell size={18}/></span>{gymName}</div><nav><a href="#activity">Activity</a><a href="#sessions">Sessions</a><Link href="/settings/profile"><Settings size={17}/> Settings</Link><button onClick={() => router.post('/logout')}><LogOut size={17}/> Sign out</button></nav></header>
        <main className="member-content"><section className="member-hero"><div><span className="eyebrow">MY CLUB · {gymName.toUpperCase()}</span><h1>Welcome, {firstName}.</h1><p>Your activity will appear here after your first visit.</p></div></section>
            <section className="member-stats"><article><span className="member-stat-icon purple"><Activity/></span><div><small>Visits this month</small><strong>0</strong><p>No visits recorded</p></div></article><article><span className="member-stat-icon green"><Clock3/></span><div><small>Training time</small><strong>0h</strong><p>No workouts recorded</p></div></article><article><span className="member-stat-icon orange"><Trophy/></span><div><small>Weekly goal</small><strong>0 / 0</strong><p>No goal configured</p></div></article></section>
            <section className="member-grid"><article className="card plan-card"><div className="plan-top"><span><CreditCard/></span><small>CURRENT MEMBERSHIP</small><h2>{member.membership_plan ?? 'No active plan'}</h2><p>{member.membership_plan ? 'Your assigned membership plan' : 'Contact the club to activate a plan'}</p></div><div className="plan-bottom"><div><small>Valid until</small><strong>{member.membership_expires_at ? new Date(member.membership_expires_at).toLocaleDateString('en-IN',{month:'long',day:'numeric',year:'numeric'}) : 'Not set'}</strong></div>{member.membership_plan && <button>View membership <ChevronRight/></button>}</div></article>
                <article className="card upcoming" id="sessions"><div className="member-card-head"><div><span className="eyebrow">UP NEXT</span><h2>Upcoming sessions</h2></div></div><div className="compact-empty"><CalendarDays/><strong>No sessions booked</strong><span>Your upcoming bookings will appear here.</span></div></article>
                <article className="card activity-summary" id="activity"><div className="member-card-head"><div><span className="eyebrow">THIS WEEK</span><h2>Your activity</h2></div></div><div className="compact-empty"><Activity/><strong>No activity recorded</strong><span>Completed workouts will appear here.</span></div></article>
                <article className="card club-card"><CalendarDays/><div><span className="eyebrow">{gymName.toUpperCase()}</span><h2>Today at your club</h2><p>No live occupancy data available</p></div></article>
            </section>
        </main>
    </div></>;
}
