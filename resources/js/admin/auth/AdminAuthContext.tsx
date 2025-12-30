import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { api, applyAdminAuthToken, TOKEN_KEY } from '../lib/api';

export type AdminUser = {
    id: string;
    name?: string | null;
    email?: string | null;
    first_name?: string | null;
    last_name?: string | null;
    roles: string[];
};

type AdminAuthContextState = {
    admin: AdminUser | null;
    token: string | null;
    loading: boolean;
    requestOtp: (email: string) => Promise<void>;
    verifyOtp: (email: string, otp: string) => Promise<AdminUser>;
    logout: () => Promise<void>;
    fetchMe: () => Promise<AdminUser>;
};

const AdminAuthContext = createContext<AdminAuthContextState | undefined>(undefined);

export function AdminAuthProvider({ children }: { children: ReactNode }) {
    const [admin, setAdmin] = useState<AdminUser | null>(null);
    const [token, setToken] = useState<string | null>(() => {
        if (typeof window === 'undefined') return null;
        return window.localStorage.getItem(TOKEN_KEY);
    });
    const [loading, setLoading] = useState(true);

    const setAuthState = useCallback((nextToken: string | null, adminData?: AdminUser | null) => {
        setToken(nextToken);
        applyAdminAuthToken(nextToken);

        if (adminData !== undefined) {
            setAdmin(adminData);
        }

        if (!nextToken && adminData === undefined) {
            setAdmin(null);
        }
    }, []);

    const fetchMe = useCallback(async (): Promise<AdminUser> => {
        const response = await api.get('/api/v1/admin/auth/me');
        setAdmin(response.data.admin);
        return response.data.admin;
    }, []);

    useEffect(() => {
        const bootstrap = async () => {
            if (!token) {
                setLoading(false);
                return;
            }

            applyAdminAuthToken(token);

            try {
                await fetchMe();
            } catch (error) {
                setAuthState(null, null);
            } finally {
                setLoading(false);
            }
        };

        bootstrap();
    }, [token, fetchMe, setAuthState]);

    const requestOtp = useCallback(async (email: string) => {
        await api.post('/api/v1/admin/auth/request-otp', { email });
    }, []);

    const verifyOtp = useCallback(
        async (email: string, otp: string): Promise<AdminUser> => {
            const response = await api.post('/api/v1/admin/auth/verify-otp', { email, otp });
            setAuthState(response.data.token, response.data.admin);
            return response.data.admin;
        },
        [setAuthState]
    );

    const logout = useCallback(async () => {
        try {
            await api.post('/api/v1/admin/auth/logout');
        } finally {
            setAuthState(null, null);
        }
    }, [setAuthState]);

    const value = useMemo(
        () => ({
            admin,
            token,
            loading,
            requestOtp,
            verifyOtp,
            logout,
            fetchMe,
        }),
        [admin, token, loading, requestOtp, verifyOtp, logout, fetchMe]
    );

    return <AdminAuthContext.Provider value={value}>{children}</AdminAuthContext.Provider>;
}

export function useAdminAuth(): AdminAuthContextState {
    const ctx = useContext(AdminAuthContext);

    if (!ctx) {
        throw new Error('useAdminAuth must be used inside AdminAuthProvider');
    }

    return ctx;
}
