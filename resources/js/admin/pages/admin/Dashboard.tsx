import { useAdminAuth } from '../../auth/AdminAuthContext';

export default function Dashboard() {
    const { admin } = useAdminAuth();

    return (
        <div className="p-6">
            <div className="mb-4">
                <h1 className="text-2xl font-semibold text-gray-900">Admin Dashboard</h1>
                <p className="text-sm text-gray-600">You are signed in as {admin?.name || admin?.email}.</p>
            </div>

            <div className="rounded-lg border border-dashed border-gray-300 bg-white p-6 shadow-sm">
                <p className="text-gray-700 text-sm">
                    Authentication is confirmed. Load any admin data here after guarding requests with the admin token.
                </p>
            </div>
        </div>
    );
}
