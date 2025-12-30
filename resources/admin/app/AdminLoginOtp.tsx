import React, { useState } from 'react';
import type { AdminUser } from '../AdminRoot';

type Props = {
    email: string;
    onBack: () => void;
    onVerified: (admin: AdminUser) => void;
};

type StatusVariant = 'success' | 'error' | '';

const getCsrfToken = () =>
    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';

const AdminLoginOtp: React.FC<Props> = ({ email, onBack, onVerified }) => {
    const [otp, setOtp] = useState('');
    const [loading, setLoading] = useState(false);
    const [status, setStatus] = useState<{ message: string; variant: StatusVariant }>({
        message: '',
        variant: '',
    });

    const submit = async (event: React.FormEvent) => {
        event.preventDefault();
        setStatus({ message: '', variant: '' });

        if (!email) {
            setStatus({
                message: 'Email is missing. Please go back and request a code again.',
                variant: 'error',
            });
            return;
        }

        if (!otp || otp.length !== 6) {
            setStatus({ message: 'Enter the 6-digit code.', variant: 'error' });
            return;
        }

        setLoading(true);
        try {
            const response = await fetch('/admin/api/auth/verify-otp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({ email, otp }),
            });

            if (response.status === 423) {
                const data = await response.json();
                setStatus({
                    message: data.message || 'Too many attempts. Request a new code.',
                    variant: 'error',
                });
                return;
            }

            if (response.ok) {
                const data = await response.json();
                setStatus({ message: 'Verified. Redirecting...', variant: 'success' });
                onVerified(data.admin);
                return;
            }

            const data = await response.json();
            setStatus({
                message: data.message || data.errors?.otp?.[0] || 'Invalid code. Try again.',
                variant: 'error',
            });
        } catch (error) {
            console.error(error);
            setStatus({
                message: 'Something went wrong. Please try again.',
                variant: 'error',
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="auth-card">
            <h1>Enter OTP</h1>
            <p>
                We sent a 6-digit code to <strong>{email || 'your email'}</strong>. The code expires
                in 10 minutes.
            </p>
            <form onSubmit={submit}>
                <div className="field">
                    <label htmlFor="admin-otp">One-time passcode</label>
                    <input
                        id="admin-otp"
                        className="input"
                        type="text"
                        name="otp"
                        inputMode="numeric"
                        pattern="\\d{6}"
                        maxLength={6}
                        autoComplete="one-time-code"
                        value={otp}
                        onChange={(event) => setOtp(event.target.value)}
                        placeholder="000000"
                        required
                    />
                </div>
                <button type="submit" className="btn" disabled={loading}>
                    {loading ? 'Verifying...' : 'Verify OTP'}
                </button>
                <div className={`status ${status.variant}`}>{status.message}</div>
            </form>
            <button type="button" className="link" onClick={onBack}>
                ← Back to email
            </button>
        </div>
    );
};

export default AdminLoginOtp;
