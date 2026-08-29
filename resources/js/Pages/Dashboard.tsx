import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Components/AdminLayout';
import { useMemo, useState } from 'react';
import {
    Activity, MoreHorizontal, Plus, Search, Sparkles, Users,
    UserRoundCheck, WalletCards, X, Zap, Bell, Pencil, Trash2
} from 'lucide-react';

type Member = { id: number; name: string; email: string; phone: string | null; membershipPlan: string | null; membershipExpiresAt: string | null; initials: string; plan: string; status: string; joined: string; visits: number; accent: string };
type Metrics = { activeMembers: number; todayCheckIns: number; monthlyRevenue: number; atRiskMembers: number };
type DropdownOptions = Record<string, string[]>;
type Props = { members: Member[]; metrics: Metrics; dropdownOptions: DropdownOptions; activeSection: string };

function Avatar({ initials, color = 'violet' }: { initials: string; color?: string }) {
    return <span className={`avatar avatar-${color}`}>{initials}</span>;
}

function Dashboard({ members, metrics, dropdownOptions, activeSection }: Props) {
    const page = usePage<{ auth: { user: { name: string } }; gym: { name: string } | null }>();
    const currentUser = page.props.auth.user;
    const gymName = page.props.gym?.name ?? 'Gym workspace';
    const [query, setQuery] = useState('');
    const [modal, setModal] = useState(false);
    const [editingMember, setEditingMember] = useState<Member | null>(null);
    const [createModal, setCreateModal] = useState<string | null>(null);
    const [toast, setToast] = useState('');

    const filtered = useMemo(() => members.filter(m => `${m.name} ${m.plan} ${m.status}`.toLowerCase().includes(query.toLowerCase())), [members, query]);
    const notify = (message: string) => { setToast(message); window.setTimeout(() => setToast(''), 2600); };

    return <>
        <Head title={`${activeSection} · ${gymName}`} />
        <AdminLayout activeSection={activeSection} user={currentUser}>
                    {activeSection === 'Overview' ? <Overview metrics={metrics} managerName={currentUser.name} gymName={gymName} openModal={() => setModal(true)} notify={notify}/> :
                        activeSection === 'Members' ? <Members members={filtered} query={query} setQuery={setQuery} openModal={() => setModal(true)} edit={setEditingMember} remove={member => window.confirm(`Delete ${member.name}? This cannot be undone.`) && router.delete(`/admin/members/${member.id}`, { preserveScroll: true })}/> :
                        <Placeholder title={activeSection} open={() => setCreateModal(activeSection)}/>} 
        </AdminLayout>
        {modal && <AddMember plans={dropdownOptions.membership_plans ?? []} close={() => setModal(false)} save={() => { setModal(false); notify('Member added successfully'); }}/>} 
        {editingMember && <EditMember member={editingMember} plans={dropdownOptions.membership_plans ?? []} close={() => setEditingMember(null)}/>} 
        {createModal && <CreateWorkspaceItem options={dropdownOptions} title={createModal} close={() => setCreateModal(null)} save={() => { notify(`${createModal.slice(0,-1) || createModal} created successfully`); setCreateModal(null); }}/>} 
        {toast && <div className="toast"><span>✓</span>{toast}</div>}
    </>;
}

function Overview({ metrics, managerName, gymName, openModal, notify }: { metrics: Metrics; managerName: string; gymName: string; openModal: () => void; notify: (s:string)=>void }) {
    const firstName = managerName.split(' ')[0];
    const today = new Intl.DateTimeFormat('en-IN', { weekday: 'long', month: 'long', day: 'numeric' }).format(new Date()).toUpperCase();
    const revenue = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(metrics.monthlyRevenue);
    return <>
        <section className="page-heading"><div><span className="eyebrow">{today}</span><h1>Good morning, {firstName} <span>👋</span></h1><p>Here’s what’s happening at {gymName} today.</p></div><div className="heading-actions"><button className="secondary" onClick={() => notify('There is no report data to export yet')}>Export report</button><button className="primary" onClick={openModal}><Plus size={18}/> Add member</button></div></section>
        <section className="stats-grid">
            <Stat icon={<Users/>} label="Active members" value={metrics.activeMembers.toLocaleString('en-IN')} detail="Current active plans" color="violet"/>
            <Stat icon={<UserRoundCheck/>} label="Today's check-ins" value={metrics.todayCheckIns.toLocaleString('en-IN')} detail="No attendance records yet" color="green"/>
            <Stat icon={<WalletCards/>} label="Monthly revenue" value={revenue} detail="No payments recorded yet" color="blue"/>
            <Stat icon={<Activity/>} label="At-risk members" value={metrics.atRiskMembers.toLocaleString('en-IN')} detail="Expiring within 7 days" color="orange"/>
        </section>
        <section className="dashboard-grid">
            <div className="card revenue-card"><div className="card-head"><div><h2>Revenue overview</h2><p>Membership revenue this month</p></div></div><div className="revenue-meta"><strong>{revenue}</strong><small>No transactions recorded this month</small></div><div className="chart-empty"><WalletCards/><strong>No revenue data yet</strong><span>Recorded membership payments will appear here.</span></div></div>
            <div className="card attendance-card"><div className="card-head"><div><h2>Club traffic</h2><p>Check-ins by time today</p></div><span className="live"><i/> Live</span></div><div className="occupancy"><div><span>Currently inside</span><strong>0</strong><small>/ 120 capacity</small></div><div className="ring" style={{'--p':'0%'} as React.CSSProperties}><span>0%</span></div></div><div className="capacity-bar"><i style={{width:'0%'}}/><span/></div><div className="peak neutral"><Zap size={16}/><p><strong>No check-ins recorded</strong><span>Traffic insights will appear after check-ins begin.</span></p></div></div>
            <div className="card activity-card"><div className="card-head"><div><h2>Live activity</h2><p>What’s happening now</p></div></div><div className="compact-empty"><Activity/><strong>No activity yet</strong><span>Member check-ins and payments will appear here.</span></div></div>
            <div className="card renewals-card"><div className="card-head"><div><h2>Membership alerts</h2><p>Renewals needing attention</p></div></div><div className="renewal-count"><strong>{metrics.atRiskMembers}</strong><span>members expire in the next 7 days</span></div>{metrics.atRiskMembers === 0 && <div className="compact-empty small"><Bell/><strong>No renewal alerts</strong><span>Upcoming expirations will appear here.</span></div>}</div>
        </section>
    </>;
}

function Stat({ icon,label,value,detail,color }: any) { return <div className="stat-card"><div className={`stat-icon ${color}`}>{icon}</div><div className="stat-title">{label}<MoreHorizontal size={18}/></div><strong>{value}</strong><div className="trend"><span>{detail}</span></div></div>; }

function Members({members,query,setQuery,openModal,edit,remove}:{members:Member[];query:string;setQuery:(s:string)=>void;openModal:()=>void;edit:(member:Member)=>void;remove:(member:Member)=>void}) { return <>
    <section className="page-heading"><div><span className="eyebrow">PEOPLE</span><h1>Members</h1><p>Manage profiles, plans and member engagement.</p></div><button className="primary" onClick={openModal}><Plus size={18}/> Add member</button></section>
    <div className="card members-card"><div className="member-toolbar"><div className="table-search"><Search size={17}/><input value={query} onChange={e=>setQuery(e.target.value)} placeholder="Search members..."/></div><div className="member-summary"><b>{members.length}</b> total members</div></div>{members.length === 0 ? <div className="compact-empty table-empty"><Users/><strong>No members yet</strong><span>Add your first member to populate this workspace.</span></div> : <div className="member-table"><div className="member-row labels"><span>Member</span><span>Membership</span><span>Status</span><span>Visits</span><span>Joined</span><span>Actions</span></div>{members.map(m=><div className="member-row" key={m.id}><div className="member-name"><Avatar initials={m.initials} color={m.accent}/><strong>{m.name}</strong></div><span>{m.plan}</span><span><i className={`status status-${m.status.toLowerCase()}`}>{m.status}</i></span><span><b>{m.visits}</b> this month</span><span>{m.joined}</span><div className="row-actions"><button className="edit-action" onClick={()=>edit(m)} aria-label={`Edit ${m.name}`} title="Edit member"><Pencil/></button><button className="delete-action" onClick={()=>remove(m)} aria-label={`Delete ${m.name}`} title="Delete member"><Trash2/></button></div></div>)}</div>}</div>
    </>; }

function Placeholder({title,open}:{title:string;open:()=>void}) { return <><section className="page-heading"><div><span className="eyebrow">DOWNTOWN CLUB</span><h1>{title}</h1><p>Everything you need to manage {title.toLowerCase()} in one place.</p></div><button className="primary" onClick={open}><Plus size={18}/> Add new</button></section><div className="card empty-state"><span><Sparkles/></span><h2>{title} workspace</h2><p>Add your first {title.toLowerCase()} record to start building this workspace.</p><button className="secondary" onClick={open}>Create first record</button></div></>; }

function AddMember({plans,close,save}:{plans:string[];close:()=>void;save:()=>void}) { return <div className="modal-backdrop" onMouseDown={close}><div className="modal" onMouseDown={e=>e.stopPropagation()}><div className="modal-head"><div><h2>Add a new member</h2><p>Create their profile and choose a membership.</p></div><button onClick={close}><X/></button></div><label>Full name<input autoFocus placeholder="e.g. Aarav Sharma"/></label><div className="form-row"><label>Email<input type="email" placeholder="aarav@example.com"/></label><label>Phone<input placeholder="+91 98765 43210"/></label></div><label>Membership<select defaultValue="" required><option value="" disabled>{plans.length ? 'Select a plan' : 'No plans configured'}</option>{plans.map(plan=><option key={plan}>{plan}</option>)}</select></label><div className="modal-actions"><button className="secondary" onClick={close}>Cancel</button><button className="primary" onClick={save} disabled={!plans.length}>Create member</button></div></div></div>; }

function EditMember({member,plans,close}:{member:Member;plans:string[];close:()=>void}) {
    const [form,setForm] = useState({name:member.name,email:member.email,phone:member.phone ?? '',membership_plan:member.membershipPlan ?? '',membership_expires_at:member.membershipExpiresAt ?? ''});
    const update = (field:keyof typeof form,value:string) => setForm(current=>({...current,[field]:value}));
    const submit = (event:React.FormEvent) => {event.preventDefault();router.put(`/admin/members/${member.id}`,form,{preserveScroll:true,onSuccess:close});};
    return <div className="modal-backdrop" onMouseDown={close}><form className="modal" onMouseDown={event=>event.stopPropagation()} onSubmit={submit}><div className="modal-head"><div><h2>Edit member</h2><p>Update profile and membership information.</p></div><button type="button" onClick={close}><X/></button></div><label>Full name<input autoFocus value={form.name} onChange={event=>update('name',event.target.value)} required/></label><div className="form-row"><label>Email<input type="email" value={form.email} onChange={event=>update('email',event.target.value)} required/></label><label>Phone<input value={form.phone} onChange={event=>update('phone',event.target.value)}/></label></div><div className="form-row"><label>Membership<select value={form.membership_plan} onChange={event=>update('membership_plan',event.target.value)}><option value="">No active plan</option>{plans.map(plan=><option key={plan}>{plan}</option>)}</select></label><label>Expires on<input type="date" value={form.membership_expires_at} onChange={event=>update('membership_expires_at',event.target.value)}/></label></div><div className="modal-actions"><button type="button" className="secondary" onClick={close}>Cancel</button><button className="primary" type="submit"><Pencil/> Save changes</button></div></form></div>;
}

const workspaceForms: Record<string, { description: string; fields: Array<{label:string;placeholder?:string;type?:string;optionsKey?:string}> }> = {
    Attendance: { description: 'Record a member check-in or attendance entry.', fields: [{label:'Member',placeholder:'Search member name'}, {label:'Check-in date & time',type:'datetime-local'}, {label:'Entry type',optionsKey:'session_types'}] },
    Memberships: { description: 'Create a plan your team can assign to members.', fields: [{label:'Plan name',placeholder:'Plan name'}, {label:'Price',placeholder:'0.00',type:'number'}, {label:'Billing cycle',optionsKey:'billing_cycles'}, {label:'Duration (days)',placeholder:'365',type:'number'}] },
    Payments: { description: 'Record a payment against a member account.', fields: [{label:'Member',placeholder:'Search member name'}, {label:'Amount (₹)',placeholder:'0.00',type:'number'}, {label:'Payment method',optionsKey:'payment_methods'}, {label:'Reference',placeholder:'Optional transaction reference'}] },
    Trainers: { description: 'Add a trainer and their primary specialty.', fields: [{label:'Trainer name',placeholder:'Full name'}, {label:'Email',placeholder:'trainer@example.com',type:'email'}, {label:'Specialty',optionsKey:'trainer_specialties'}, {label:'Phone',placeholder:'+91 98765 43210'}] },
    Schedule: { description: 'Schedule a class or personal training session.', fields: [{label:'Session name',placeholder:'Session name'}, {label:'Session type',optionsKey:'session_types'}, {label:'Trainer',placeholder:'Select or enter trainer'}, {label:'Starts at',type:'datetime-local'}, {label:'Capacity',placeholder:'20',type:'number'}] },
    Leads: { description: 'Capture a prospective member for follow-up.', fields: [{label:'Lead name',placeholder:'Full name'}, {label:'Email',placeholder:'lead@example.com',type:'email'}, {label:'Phone',placeholder:'+91 98765 43210'}, {label:'Interested in',optionsKey:'lead_interests'}] },
};

function CreateWorkspaceItem({title,options,close,save}:{title:string;options:DropdownOptions;close:()=>void;save:()=>void}) {
    const config = workspaceForms[title] ?? workspaceForms.Leads;
    const singular = title.endsWith('s') ? title.slice(0,-1) : title;
    return <div className="modal-backdrop" onMouseDown={close}><form className="modal workspace-modal" onMouseDown={event=>event.stopPropagation()} onSubmit={event=>{event.preventDefault();save();}}><div className="modal-head"><div><span className="modal-kicker">NEW {singular.toUpperCase()}</span><h2>Add {singular.toLowerCase()}</h2><p>{config.description}</p></div><button type="button" onClick={close}><X/></button></div><div className="workspace-form-grid">{config.fields.map((field,index)=>{const values=field.optionsKey ? options[field.optionsKey] ?? [] : [];return <label key={field.label}>{field.label}{field.optionsKey?<select defaultValue="" required><option value="" disabled>{values.length ? `Select ${field.label.toLowerCase()}` : 'No options configured'}</option>{values.map(option=><option key={option}>{option}</option>)}</select>:<input autoFocus={index===0} required={index<2} type={field.type ?? 'text'} placeholder={field.placeholder}/>}</label>;})}</div><div className="modal-note"><span>i</span>Dropdown values are managed in Settings → Dropdown data.</div><div className="modal-actions"><button type="button" className="secondary" onClick={close}>Cancel</button><button className="primary" type="submit"><Plus size={16}/> Create {singular.toLowerCase()}</button></div></form></div>;
}

export default Dashboard;
