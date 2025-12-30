import { Navigate } from 'react-router-dom';
import { useAdminAuth } from '../auth/AdminAuthContext';

export function AdminProtectedRoute({ children }: { children: JSX.Element }) {
    const { admin, token, loading } = useAdminAuth();

    if (loading) {
        return (
            <div className="flex h-screen items-center justify-center text-sm text-gray-500">
                Checking admin access...
            </div>
        );
    }

    if (!token || !admin) {
        return <Navigate to="/admin/login" replace />;
    }

    return children;
}

export default AdminProtectedRoute;
