import React, { useEffect, useState } from 'react';
import App from './app/App';
import AdminLoginEmail from './app/AdminLoginEmail';
import AdminLoginOtp from './app/AdminLoginOtp';

export type AdminUser = {
    id: string;
    email: string;
    name?: string | null;
};

type AuthStage = 'checking' | 'login' | 'otp' | 'ready';

const getCsrfToken = () =>
    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';

const AdminRoot: React.FC = () => {
    const [stage, setStage] = useState<AuthStage>('checking');
    const [admin, setAdmin] = useState<AdminUser | null>(null);
    const [pendingEmail, setPendingEmail] = useState('');

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const emailParam = params.get('email');
        if (emailParam) {
            setPendingEmail(emailParam);
        }

        const fetchMe = async () => {
            try {
                const response = await fetch('/admin/api/auth/me', {
                    credentials: 'include',
                });

                if (response.ok) {
                    const data = await response.json();
                    setAdmin(data.admin);
                    setStage('ready');
                } else {
                    setStage('login');
                }
            } catch (error) {
                console.error('Unable to verify admin session', error);
                setStage('login');
            }
        };

        fetchMe();
    }, []);

    const handleOtpRequested = (email: string) => {
        setPendingEmail(email);
        setStage('otp');
    };

    const handleVerified = (adminUser: AdminUser) => {
        setAdmin(adminUser);
        setStage('ready');
    };

    const handleLogout = async () => {
        try {
            await fetch('/admin/api/auth/logout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                credentials: 'include',
            });
        } finally {
            setAdmin(null);
            setPendingEmail('');
            setStage('login');
        }
    };

    return (
        <div className="admin-shell">
            {stage === 'ready' && admin ? (
                <App admin={admin} onLogout={handleLogout} />
            ) : (
                <div className="auth-container">
                    <div className="brand">
                        <div className="brand-circle">PG</div>
                        <div className="brand-text">
                            <span>Peers Global Unity</span>
                            <span>Admin Control Panel</span>
                        </div>
                    </div>
                    {stage === 'otp' ? (
                        <AdminLoginOtp
                            email={pendingEmail}
                            onBack={() => setStage('login')}
                            onVerified={handleVerified}
                        />
                    ) : (
                        <AdminLoginEmail
                            initialEmail={pendingEmail}
                            onOtpRequested={handleOtpRequested}
                        />
                    )}
                </div>
            )}
        </div>
    );
};

export default AdminRoot;
