import axios from 'axios';

export const TOKEN_KEY = 'admin_auth_token';

const baseURL = import.meta.env.VITE_API_BASE_URL ?? '';

export const api = axios.create({
    baseURL,
});

const canUseStorage = typeof window !== 'undefined' && typeof window.localStorage !== 'undefined';

const getStoredToken = (): string | null => {
    if (!canUseStorage) {
        return null;
    }

    return window.localStorage.getItem(TOKEN_KEY);
};

export const applyAdminAuthToken = (token: string | null): void => {
    if (!canUseStorage) {
        return;
    }

    if (token) {
        window.localStorage.setItem(TOKEN_KEY, token);
        api.defaults.headers.common.Authorization = `Bearer ${token}`;
    } else {
        window.localStorage.removeItem(TOKEN_KEY);
        delete api.defaults.headers.common.Authorization;
    }
};

applyAdminAuthToken(getStoredToken());

api.interceptors.request.use((config) => {
    const token = getStoredToken();

    if (token) {
        config.headers = config.headers ?? {};
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});
