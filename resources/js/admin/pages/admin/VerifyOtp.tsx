import { FormEvent, useEffect, useMemo, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useAdminAuth } from '../../auth/AdminAuthContext';

const getErrorMessage = (error: unknown) => {
    if (error && typeof error === 'object' && 'response' in error && error.response) {
        // @ts-expect-error axios response typing
        return error.response?.data?.message ?? 'Unable to verify code.';
    }

    return 'Unable to verify code.';
};

export default function VerifyOtp() {
    const navigate = useNavigate();
    const location = useLocation();
    const { verifyOtp } = useAdminAuth();
    const [otp, setOtp] = useState('');
    const [verifying, setVerifying] = useState(false);
    const [error, setError] = useState('');

    const email = useMemo(() => {
        const params = new URLSearchParams(location.search);
        return params.get('email') ?? '';
    }, [location.search]);

    useEffect(() => {
        if (!email) {
            navigate('/admin/login', { replace: true });
        }
    }, [email, navigate]);

    const onSubmit = async (event: FormEvent) => {
        event.preventDefault();

        if (!email) {
            navigate('/admin/login', { replace: true });
            return;
        }

        setVerifying(true);
        setError('');

        try {
            await verifyOtp(email, otp);
            navigate('/admin/dashboard', { replace: true });
        } catch (err) {
            setError(getErrorMessage(err));
        } finally {
            setVerifying(false);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
            <div className="w-full max-w-md bg-white shadow-sm border border-gray-200 rounded-xl p-8">
                <h1 className="text-2xl font-semibold text-gray-900 mb-2">Verify OTP</h1>
                <p className="text-sm text-gray-600 mb-6">Enter the 4-digit code sent to {email || 'your email'}.</p>

                <form onSubmit={onSubmit} className="space-y-4">
                    <div className="space-y-1">
                        <label htmlFor="otp" className="text-sm font-medium text-gray-800">
                            One-time passcode
                        </label>
                        <input
                            id="otp"
                            type="text"
                            inputMode="numeric"
                            pattern="[0-9]{4}"
                            maxLength={4}
                            value={otp}
                            onChange={(e) => setOtp(e.target.value.replace(/\D/g, '').slice(0, 4))}
                            required
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 shadow-sm tracking-[0.4em] text-center text-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                            placeholder="0000"
                        />
                    </div>

                    {error && <p className="text-sm text-red-600">{error}</p>}

                    <button
                        type="submit"
                        disabled={verifying}
                        className="w-full inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-white font-semibold shadow-sm hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        {verifying ? 'Verifying...' : 'Verify & Continue'}
                    </button>
                </form>

                <div className="mt-4 text-sm text-gray-600 text-center">
                    <button
                        type="button"
                        onClick={() => navigate(`/admin/login?email=${encodeURIComponent(email)}`)}
                        className="font-medium text-indigo-600 hover:underline"
                    >
                        Use a different email
                    </button>
                </div>
            </div>
        </div>
    );
}
