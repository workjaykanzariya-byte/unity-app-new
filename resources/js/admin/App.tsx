import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AdminAuthProvider } from './auth/AdminAuthContext';
import AdminLayout from './components/AdminLayout';
import Dashboard from './pages/admin/Dashboard';
import Login from './pages/admin/Login';
import VerifyOtp from './pages/admin/VerifyOtp';
import AdminProtectedRoute from './routes/AdminProtectedRoute';

export default function App() {
    return (
        <BrowserRouter>
            <AdminAuthProvider>
                <Routes>
                    <Route path="/admin/login" element={<Login />} />
                    <Route path="/admin/verify" element={<VerifyOtp />} />
                    <Route
                        path="/admin/dashboard"
                        element={
                            <AdminProtectedRoute>
                                <AdminLayout>
                                    <Dashboard />
                                </AdminLayout>
                            </AdminProtectedRoute>
                        }
                    />
                    <Route path="/admin" element={<Navigate to="/admin/dashboard" replace />} />
                    <Route path="/admin/*" element={<Navigate to="/admin/dashboard" replace />} />
                </Routes>
            </AdminAuthProvider>
        </BrowserRouter>
    );
}
