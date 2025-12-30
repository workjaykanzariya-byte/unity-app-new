import { FormEvent, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAdminAuth } from '../../auth/AdminAuthContext';

const errorMessage = (error: unknown) => {
    if (error && typeof error === 'object' && 'response' in error && error.response) {
        // @ts-expect-error axios shape
        return error.response?.data?.message ?? 'Unable to send OTP.';
    }

    return 'Unable to send OTP.';
};

export default function Login() {
    const navigate = useNavigate();
    const { requestOtp } = useAdminAuth();
    const [email, setEmail] = useState('');
    const [sending, setSending] = useState(false);
    const [cooldown, setCooldown] = useState(0);
    const [status, setStatus] = useState('');
    const [error, setError] = useState('');

    useEffect(() => {
        if (cooldown <= 0) return;

        const timer = setInterval(() => {
            setCooldown((prev) => (prev > 0 ? prev - 1 : 0));
        }, 1000);

        return () => clearInterval(timer);
    }, [cooldown]);

    const onSubmit = async (event: FormEvent) => {
        event.preventDefault();
        setSending(true);
        setError('');
        setStatus('');

        try {
            const trimmedEmail = email.trim();
            await requestOtp(trimmedEmail);
            setStatus('OTP sent. Check your email for the 4-digit code.');
            setCooldown(60);
            navigate(`/admin/verify?email=${encodeURIComponent(trimmedEmail)}`);
        } catch (err) {
            setError(errorMessage(err));
        } finally {
            setSending(false);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
            <div className="w-full max-w-md bg-white shadow-sm border border-gray-200 rounded-xl p-8">
                <h1 className="text-2xl font-semibold text-gray-900 mb-2">Admin Login</h1>
                <p className="text-sm text-gray-600 mb-6">Enter your admin email to receive a one-time passcode.</p>

                <form onSubmit={onSubmit} className="space-y-4">
                    <div className="space-y-1">
                        <label htmlFor="email" className="text-sm font-medium text-gray-800">
                            Work email
                        </label>
                        <input
                            id="email"
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            required
                            autoComplete="email"
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                            placeholder="admin@example.com"
                        />
                    </div>

                    {error && <p className="text-sm text-red-600">{error}</p>}
                    {status && <p className="text-sm text-green-700">{status}</p>}

                    <button
                        type="submit"
                        disabled={sending || cooldown > 0}
                        className="w-full inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-white font-semibold shadow-sm hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        {sending ? 'Sending...' : cooldown > 0 ? `Resend in ${cooldown}s` : 'Send OTP'}
                    </button>
                </form>
            </div>
        </div>
    );
}
