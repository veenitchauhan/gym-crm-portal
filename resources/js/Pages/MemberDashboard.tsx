import { Head, Link, router, usePage } from '@inertiajs/react';
import { Activity, CalendarDays, Clock3, CreditCard, Dumbbell, LogOut, Settings, UserRoundCheck } from 'lucide-react';

type Membership = { plan:string;startsAt:string;endsAt:string;status:string;price:string };
type Booking = { id:number;sessionName:string;sessionType:string;trainerName:string|null;startsAt:string;endsAt:string };
type AvailableSession = { id:number;name:string;sessionType:string;trainerName:string|null;startsAt:string;endsAt:string;placesRemaining:number;isBooked:boolean };
type Attendance = { id:number;checkedInAt:string;checkedOutAt:string|null };
type Payment = { id:number;amount:string;status:string;paymentMethod:string;paidAt:string|null };
type Props = {
    member:{name:string;visitsThisMonth:number;trainingMinutesThisMonth:number;visitsThisWeek:number;membership:Membership|null};
    upcomingBookings:Booking[];
    availableSessions:AvailableSession[];
    recentAttendance:Attendance[];
    payments:Payment[];
    club:{currentlyInside:number;todayCheckIns:number};
};

export default function MemberDashboard({member,upcomingBookings,availableSessions,recentAttendance,payments,club}:Props) {
    const firstName=member.name.split(' ')[0];
    const gym=usePage<{gym:{name:string}|null}>().props.gym;
    const gymName=gym?.name??'Your gym';
    const trainingHours=(member.trainingMinutesThisMonth/60).toFixed(member.trainingMinutesThisMonth%60===0?0:1);

    return <><Head title={`My membership · ${gymName}`}/><div className="member-app">
        <header className="member-topbar"><div className="auth-brand dark"><span><Dumbbell size={18}/></span>{gymName}</div><nav><a href="#activity">Activity</a><a href="#sessions">Sessions</a><Link href="/settings/profile"><Settings size={17}/> Settings</Link><button onClick={()=>router.post('/logout')}><LogOut size={17}/> Sign out</button></nav></header>
        <main className="member-content"><section className="member-hero"><div><span className="eyebrow">MY CLUB · {gymName.toUpperCase()}</span><h1>Welcome, {firstName}.</h1><p>Your membership, visits, payments, and bookings are connected here.</p></div><div className="member-streak"><UserRoundCheck/><strong>{member.visitsThisWeek}</strong><span>visits this week</span></div></section>
            <section className="member-stats"><a className="metric-link" href="#activity"><span className="member-stat-icon purple"><Activity/></span><div><small>Visits this month</small><strong>{member.visitsThisMonth}</strong><p>{member.visitsThisMonth?'Attendance recorded':'No visits yet'}</p></div></a><a className="metric-link" href="#activity"><span className="member-stat-icon green"><Clock3/></span><div><small>Training time</small><strong>{trainingHours}h</strong><p>Completed visits this month</p></div></a><a className="metric-link" href="#sessions"><span className="member-stat-icon orange"><CalendarDays/></span><div><small>Upcoming bookings</small><strong>{upcomingBookings.length}</strong><p>{upcomingBookings.length?'Sessions reserved':'Nothing booked'}</p></div></a></section>
            <section className="member-grid"><article className="card plan-card"><div className="plan-top"><span><CreditCard/></span><small>CURRENT MEMBERSHIP</small><h2>{member.membership?.plan??'No active plan'}</h2><p>{member.membership?`${member.membership.status} · ₹${Number(member.membership.price).toLocaleString('en-IN')}`:'Contact the club to activate a plan'}</p></div><div className="plan-bottom"><div><small>Valid until</small><strong>{member.membership?new Date(member.membership.endsAt).toLocaleDateString('en-IN',{month:'long',day:'numeric',year:'numeric'}):'Not set'}</strong></div></div></article>
                <article className="card upcoming" id="sessions"><div className="member-card-head"><div><span className="eyebrow">UP NEXT</span><h2>Your bookings</h2></div></div>{upcomingBookings.length===0?<div className="compact-empty"><CalendarDays/><strong>No sessions booked</strong><span>Choose an available session below.</span></div>:upcomingBookings.map(booking=><div className="member-booking" key={booking.id}><div><strong>{booking.sessionName}</strong><span>{new Date(booking.startsAt).toLocaleString('en-IN')} · {booking.trainerName??'Coach TBA'}</span></div><button onClick={()=>window.confirm('Cancel this booking?')&&router.delete(`/member/bookings/${booking.id}`,{preserveScroll:true})}>Cancel</button></div>)}</article>
                <article className="card member-wide"><div className="member-card-head"><div><span className="eyebrow">BOOK A WORKOUT</span><h2>Available sessions</h2></div></div>{availableSessions.length===0?<div className="compact-empty"><CalendarDays/><strong>No upcoming sessions</strong><span>The club schedule will appear here.</span></div>:<div className="member-session-grid">{availableSessions.map(session=><div className="member-session" key={session.id}><div><strong>{session.name}</strong><span>{session.sessionType} · {session.trainerName??'Coach TBA'}</span><small>{new Date(session.startsAt).toLocaleString('en-IN')} · {session.placesRemaining} places left</small></div><button className="secondary" disabled={session.isBooked||session.placesRemaining===0} onClick={()=>router.post(`/member/sessions/${session.id}/book`,{},{preserveScroll:true})}>{session.isBooked?'Booked':session.placesRemaining?'Book':'Full'}</button></div>)}</div>}</article>
                <article className="card activity-summary" id="activity"><div className="member-card-head"><div><span className="eyebrow">VISIT HISTORY</span><h2>Recent attendance</h2></div></div>{recentAttendance.length===0?<div className="compact-empty"><Activity/><strong>No activity recorded</strong><span>Your completed visits will appear here.</span></div>:recentAttendance.map(visit=><div className="member-history-row" key={visit.id}><span>{new Date(visit.checkedInAt).toLocaleDateString('en-IN')}</span><strong>{new Date(visit.checkedInAt).toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit'})}</strong><small>{visit.checkedOutAt?`Out ${new Date(visit.checkedOutAt).toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit'})}`:'Currently inside'}</small></div>)}</article>
                <article className="card"><div className="member-card-head"><div><span className="eyebrow">PAYMENT HISTORY</span><h2>Recent payments</h2></div></div>{payments.length===0?<div className="compact-empty"><CreditCard/><strong>No payments recorded</strong><span>Membership payments will appear here.</span></div>:payments.map(payment=><div className="member-history-row" key={payment.id}><span>{payment.paidAt?new Date(payment.paidAt).toLocaleDateString('en-IN'):'Pending'}</span><strong>₹{Number(payment.amount).toLocaleString('en-IN')}</strong><small>{payment.paymentMethod} · {payment.status}</small></div>)}</article>
                <article className="card club-card"><CalendarDays/><div><span className="eyebrow">{gymName.toUpperCase()}</span><h2>Today at your club</h2><p>{club.currentlyInside} members currently inside · {club.todayCheckIns} check-ins today</p></div></article>
            </section>
        </main>
    </div></>;
}
