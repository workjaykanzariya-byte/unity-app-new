import { ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAdminAuth } from '../auth/AdminAuthContext';

export default function AdminLayout({ children }: { children: ReactNode }) {
    const { admin, logout } = useAdminAuth();
    const navigate = useNavigate();

    const handleLogout = async () => {
        await logout();
        navigate('/admin/login');
    };

    return (
        <div className="min-h-screen bg-gray-50">
            <header className="bg-white border-b border-gray-200">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                    <div className="flex items-center gap-3">
                        <div className="h-10 w-10 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold">
                            AP
                        </div>
                        <div>
                            <p className="text-sm font-semibold text-gray-900">Admin Panel</p>
                            <p className="text-xs text-gray-600">Fast access to moderator tools</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="text-right text-sm">
                            <p className="font-semibold text-gray-900">{admin?.name || 'Admin'}</p>
                            <p className="text-gray-600 text-xs">{admin?.email}</p>
                        </div>
                        <button
                            type="button"
                            onClick={handleLogout}
                            className="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </header>
            <main className="mx-auto max-w-6xl px-6 py-6">{children}</main>
        </div>
    );
}
