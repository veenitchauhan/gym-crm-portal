import AdminLayout from '@/Components/AdminLayout';
import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, CalendarCheck, Clock3, CreditCard, LogIn, LogOut, Mail, Pencil, Phone, Trash2, UserRound, X } from 'lucide-react';
import { useState } from 'react';

type Attendance = { id:number;checkedInAt:string;checkedOutAt:string|null;notes:string|null };
type Payment = { id:number;amount:string;status:string;method:string;paidAt:string|null };
type Member = {
    id:number;name:string;email:string;phone:string|null;joinedAt:string;attendanceCount:number;monthVisits:number;currentAttendanceId:number|null;
    membership:{plan:string;startsAt:string;endsAt:string;status:string;isCurrent:boolean}|null;
};

export default function MemberShow({ member, attendances, payments }: { member:Member;attendances:Attendance[];payments:Payment[] }) {
    const currentUser = usePage<{auth:{user:{name:string}}}>().props.auth.user;
    const [editingAttendance, setEditingAttendance] = useState<Attendance|null>(null);
    const [checkingIn, setCheckingIn] = useState(false);
    const currentAttendance = attendances.find(item => item.id === member.currentAttendanceId) ?? null;
    const initials = member.name.split(' ').map(part => part[0]).slice(0,2).join('').toUpperCase();
    const checkOut = () => {
        if (!currentAttendance) return;
        const now = new Date();
        const checkedOutAt = new Date(now.getTime()-now.getTimezoneOffset()*60000).toISOString().slice(0,16);
        router.put(`/admin/attendances/${currentAttendance.id}`, { user_id:member.id, checked_in_at:currentAttendance.checkedInAt, checked_out_at:checkedOutAt, notes:currentAttendance.notes??'' }, { preserveScroll:true });
    };

    return <><Head title={`${member.name} · Member details`}/><AdminLayout activeSection="Members" user={currentUser}>
        <div className="member-profile-page">
            <Link href="/admin/members" className="member-back"><ArrowLeft/> Back to members</Link>
            <section className="member-profile-hero card">
                <div className="member-profile-identity"><span className="member-profile-avatar">{initials}</span><div><span className="eyebrow">MEMBER PROFILE</span><h1>{member.name}</h1><p>Joined {new Date(member.joinedAt).toLocaleDateString('en-IN',{day:'numeric',month:'long',year:'numeric'})}</p></div></div>
                <div className="member-profile-actions">{currentAttendance?<button className="checkout-member" onClick={checkOut}><LogOut/> Check out</button>:<button className="primary" onClick={()=>setCheckingIn(true)}><LogIn/> Check in</button>}</div>
            </section>
            <section className="member-detail-grid">
                <article className="card member-contact-card"><div className="card-head"><div><h2>Contact details</h2><p>Member identity and contact information</p></div><UserRound/></div><dl><div><dt><Mail/> Email</dt><dd>{member.email}</dd></div><div><dt><Phone/> Phone</dt><dd>{member.phone??'Not provided'}</dd></div></dl></article>
                <article className="card member-membership-card"><div className="card-head"><div><h2>Membership</h2><p>Current plan and validity</p></div><CreditCard/></div>{member.membership?<><strong>{member.membership.plan}</strong><span className={`status status-${member.membership.isCurrent?'active':'inactive'}`}>{member.membership.isCurrent?'Active':'Inactive'}</span><dl><div><dt>Starts</dt><dd>{new Date(member.membership.startsAt).toLocaleDateString('en-IN')}</dd></div><div><dt>Expires</dt><dd>{new Date(member.membership.endsAt).toLocaleDateString('en-IN')}</dd></div></dl></>:<div className="compact-empty small"><CreditCard/><strong>No membership assigned</strong><span>Add or update the member from the Members page.</span></div>}</article>
                <a className="card member-stat-card metric-link" href="#attendance-history"><CalendarCheck/><span>This month</span><strong>{member.monthVisits}</strong><small>gym visits</small></a>
                <a className="card member-stat-card metric-link" href="#attendance-history"><Clock3/><span>All time</span><strong>{member.attendanceCount}</strong><small>attendance records</small></a>
            </section>
            <span className="section-anchor" id="attendance-history" />
            <section className="card member-history-card"><div className="card-head"><div><h2>Attendance history</h2><p>Check-ins and check-outs for this member only</p></div>{!currentAttendance&&<button className="secondary" onClick={()=>setCheckingIn(true)}><LogIn/> Add check-in</button>}</div>{attendances.length===0?<div className="compact-empty table-empty"><CalendarCheck/><strong>No attendance yet</strong><span>Use Check in to record the member's first visit.</span></div>:<div className="member-attendance-list"><div className="member-attendance-row labels"><span>Checked in</span><span>Checked out</span><span>Duration</span><span>Notes</span><span>Actions</span></div>{attendances.map(item=><AttendanceRow key={item.id} attendance={item} edit={()=>setEditingAttendance(item)}/>)}</div>}</section>
            <section className="card member-history-card"><div className="card-head"><div><h2>Recent payments</h2><p>Latest transactions associated with this member</p></div><CreditCard/></div>{payments.length===0?<div className="compact-empty small"><CreditCard/><strong>No payments recorded</strong><span>Payments added for this member will appear here.</span></div>:<div className="member-payment-list">{payments.map(payment=><div key={payment.id}><div><strong>₹{Number(payment.amount).toLocaleString('en-IN')}</strong><span>{payment.method}</span></div><span>{payment.paidAt?new Date(payment.paidAt).toLocaleString('en-IN'):'Date not recorded'}</span><i className={`status status-${payment.status==='paid'?'active':'inactive'}`}>{payment.status}</i></div>)}</div>}</section>
        </div>
        {(checkingIn||editingAttendance)&&<AttendanceModal member={member} attendance={editingAttendance} close={()=>{setCheckingIn(false);setEditingAttendance(null);}}/>}
    </AdminLayout></>;
}

function AttendanceRow({attendance,edit}:{attendance:Attendance;edit:()=>void}) {
    const duration = attendance.checkedOutAt ? Math.max(1,Math.round((new Date(attendance.checkedOutAt).getTime()-new Date(attendance.checkedInAt).getTime())/60000)) : null;
    return <div className="member-attendance-row"><span>{new Date(attendance.checkedInAt).toLocaleString('en-IN')}</span><span>{attendance.checkedOutAt?new Date(attendance.checkedOutAt).toLocaleString('en-IN'):<i className="status status-active">Inside</i>}</span><span>{duration===null?'In progress':`${Math.floor(duration/60)}h ${duration%60}m`}</span><span>{attendance.notes??'—'}</span><div className="row-actions"><button className="edit-action" onClick={edit} aria-label="Edit attendance"><Pencil/></button><button className="delete-action" onClick={()=>window.confirm('Delete this attendance entry?')&&router.delete(`/admin/attendances/${attendance.id}`,{preserveScroll:true})} aria-label="Delete attendance"><Trash2/></button></div></div>;
}

function AttendanceModal({member,attendance,close}:{member:Member;attendance:Attendance|null;close:()=>void}) {
    const now = new Date();
    const localDateTime = new Date(now.getTime()-now.getTimezoneOffset()*60000).toISOString().slice(0,16);
    return <div className="modal-backdrop" onMouseDown={close}><Form action={attendance?`/admin/attendances/${attendance.id}`:'/admin/attendances'} method={attendance?'put':'post'} className="modal" onMouseDown={event=>event.stopPropagation()} onSuccess={close}>{({errors,processing})=><><div className="modal-head"><div><h2>{attendance?'Edit attendance':`Check in ${member.name}`}</h2><p>Record this member's gym visit.</p></div><button type="button" onClick={close}><X/></button></div><input type="hidden" name="user_id" value={member.id}/><label>Checked in at<input name="checked_in_at" type="datetime-local" defaultValue={attendance?.checkedInAt??localDateTime} required/>{errors.checked_in_at&&<em>{errors.checked_in_at}</em>}</label><label>Checked out at <small>Leave blank while the member is inside.</small><input name="checked_out_at" type="datetime-local" defaultValue={attendance?.checkedOutAt??''}/>{errors.checked_out_at&&<em>{errors.checked_out_at}</em>}</label><label>Notes<input name="notes" defaultValue={attendance?.notes??''} placeholder="Optional visit note"/>{errors.notes&&<em>{errors.notes}</em>}{errors.user_id&&<em>{errors.user_id}</em>}</label><div className="modal-actions"><button type="button" className="secondary" onClick={close}>Cancel</button><button className="primary" disabled={processing}>{processing?'Saving…':attendance?'Save attendance':'Check in member'}</button></div></>}</Form></div>;
}
