import React, { useState } from 'react';

type Props = {
    initialEmail?: string;
    onOtpRequested: (email: string) => void;
};

type StatusVariant = 'success' | 'error' | '';

const getCsrfToken = () =>
    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';

const AdminLoginEmail: React.FC<Props> = ({ initialEmail = '', onOtpRequested }) => {
    const [email, setEmail] = useState(initialEmail);
    const [loading, setLoading] = useState(false);
    const [status, setStatus] = useState<{ message: string; variant: StatusVariant }>({
        message: '',
        variant: '',
    });

    const submit = async (event: React.FormEvent) => {
        event.preventDefault();
        setStatus({ message: '', variant: '' });

        if (!email) {
            setStatus({ message: 'Please enter a valid email.', variant: 'error' });
            return;
        }

        setLoading(true);
        try {
            const response = await fetch('/admin/api/auth/request-otp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({ email }),
            });

            if (response.status === 429) {
                const data = await response.json();
                setStatus({
                    message: data.message || 'Please wait before requesting another OTP.',
                    variant: 'error',
                });
                return;
            }

            if (!response.ok) {
                setStatus({
                    message: 'Unable to send OTP right now. Please try again.',
                    variant: 'error',
                });
                return;
            }

            setStatus({ message: 'OTP sent. Check your inbox.', variant: 'success' });
            onOtpRequested(email);
        } catch (error) {
            console.error(error);
            setStatus({
                message: 'Something went wrong. Please retry.',
                variant: 'error',
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="auth-card">
            <h1>Admin OTP Login</h1>
            <p>Enter your admin email address to receive a one-time passcode.</p>
            <form onSubmit={submit}>
                <div className="field">
                    <label htmlFor="admin-email">Admin email</label>
                    <input
                        id="admin-email"
                        className="input"
                        type="email"
                        name="email"
                        autoComplete="email"
                        value={email}
                        onChange={(event) => setEmail(event.target.value)}
                        placeholder="admin@example.com"
                        required
                    />
                </div>
                <button type="submit" className="btn" disabled={loading}>
                    {loading ? 'Sending...' : 'Send OTP'}
                </button>
                <div className={`status ${status.variant}`}>{status.message}</div>
            </form>
        </div>
    );
};

export default AdminLoginEmail;
