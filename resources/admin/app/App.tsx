import React from 'react';
import type { AdminUser } from '../AdminRoot';

type Props = {
    admin: AdminUser;
    onLogout: () => Promise<void> | void;
};

const stats = [
    { label: 'Active members', value: '1,842', trend: '+4.2% vs last week' },
    { label: 'Referrals passed', value: '326', trend: '+12.4% vs last week' },
    { label: 'Events this month', value: '18', trend: 'On track' },
    { label: 'Open support tickets', value: '14', trend: 'Monitor' },
];

const members = [
    { name: 'Anika Sharma', circle: 'Bangalore Founders', status: 'Active', coins: 4200 },
    { name: 'Ravi Patel', circle: 'Mumbai Innovators', status: 'Active', coins: 3610 },
    { name: 'Lena Rodrigues', circle: 'Chennai Leaders', status: 'Invited', coins: 1800 },
    { name: 'Noah Mehta', circle: 'Delhi Growth', status: 'Pending', coins: 940 },
];

const activities = [
    { title: 'New requirement posted', by: 'Anika Sharma', time: '5 min ago' },
    { title: 'Business deal closed', by: 'Ravi Patel', time: '22 min ago' },
    { title: 'Event RSVP spike', by: 'Circle Leads', time: '1 hr ago' },
    { title: 'New testimonial awaiting approval', by: 'Product Team', time: '2 hr ago' },
];

const App: React.FC<Props> = ({ admin, onLogout }) => {
    return (
        <div className="dashboard-shell">
            <div className="top-bar">
                <div className="brand">
                    <div className="brand-circle">PG</div>
                    <div className="brand-text">
                        <span>Peers Global Unity</span>
                        <span>Admin Dashboard</span>
                    </div>
                </div>
                <div className="user">
                    <div>
                        <div style={{ fontWeight: 700 }}>
                            {admin.name || admin.email}
                        </div>
                        <div className="pill">Verified OTP session</div>
                    </div>
                    <button className="logout-btn" onClick={onLogout}>
                        Logout
                    </button>
                </div>
            </div>

            <div className="stat-grid">
                {stats.map((stat) => (
                    <div key={stat.label} className="stat-card">
                        <h3>{stat.label}</h3>
                        <div className="value">{stat.value}</div>
                        <div className="trend">▲ {stat.trend}</div>
                    </div>
                ))}
            </div>

            <div className="panels">
                <div className="panel">
                    <h2>Recent Members</h2>
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Circle</th>
                                <th>Status</th>
                                <th>Coins</th>
                            </tr>
                        </thead>
                        <tbody>
                            {members.map((member) => (
                                <tr key={member.name}>
                                    <td>{member.name}</td>
                                    <td>{member.circle}</td>
                                    <td>
                                        <span
                                            className={`pill ${
                                                member.status === 'Active'
                                                    ? 'success'
                                                    : member.status === 'Invited'
                                                        ? 'warning'
                                                        : 'muted'
                                            }`}
                                        >
                                            {member.status}
                                        </span>
                                    </td>
                                    <td>{member.coins.toLocaleString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="panel">
                    <h2>Latest Activity</h2>
                    <div className="activity-list">
                        {activities.map((item) => (
                            <div key={item.title + item.time} className="activity">
                                <strong>{item.title}</strong>
                                <div>{item.by}</div>
                                <small>{item.time}</small>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default App;
