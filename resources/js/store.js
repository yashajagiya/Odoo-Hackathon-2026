import { defineStore } from 'pinia';
import axios from 'axios';

// Configure Axios defaults
axios.defaults.baseURL = '/api';
axios.defaults.headers.common['Accept'] = 'application/json';

// Retrieve token from local storage if exists
const initialToken = localStorage.getItem('token');
if (initialToken) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${initialToken}`;
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: initialToken || null,
        loading: false,
        error: null,
        toasts: [],
        confirmModal: null, // { title, message, onConfirm, onCancel }
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        roleSlug: (state) => state.user?.role?.slug || '',
        permissions: (state) => state.user?.role?.permissions || [],
    },

    actions: {
        hasPermission(permission) {
            if (!this.user || !this.user.role) return false;
            const perms = this.permissions;

            if (perms.includes('*')) return true;
            if (perms.includes(permission)) return true;

            const parts = permission.split('.');
            if (parts.length >= 2) {
                const moduleWildcard = `${parts[0]}.*`;
                if (perms.includes(moduleWildcard)) return true;
            }

            return false;
        },

        async login(email, password) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.post('/login', { email, password });
                const { user, token } = response.data;
                
                this.user = user;
                this.token = token;
                
                localStorage.setItem('token', token);
                localStorage.setItem('user', JSON.stringify(user));
                
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Login failed';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async register(name, email, password, password_confirmation, role_id = null) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.post('/register', {
                    name,
                    email,
                    password,
                    password_confirmation,
                    role_id
                });
                const { user, token } = response.data;

                this.user = user;
                this.token = token;

                localStorage.setItem('token', token);
                localStorage.setItem('user', JSON.stringify(user));

                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Registration failed';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await axios.post('/logout');
            } catch (err) {
                console.error('Logout error on server:', err);
            } finally {
                this.user = null;
                this.token = null;
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                delete axios.defaults.headers.common['Authorization'];
            }
        },

        async fetchMe() {
            if (!this.token) return;
            try {
                const response = await axios.get('/me');
                this.user = response.data.user;
                localStorage.setItem('user', JSON.stringify(this.user));
            } catch (err) {
                if (err.response?.status === 401) {
                    this.logout();
                }
            }
        },

        showToast(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 4000);
        },

        showConfirm(title, message, onConfirm, onCancel = null) {
            this.confirmModal = { title, message, onConfirm, onCancel };
        },

        clearConfirm() {
            this.confirmModal = null;
        }
    }
});
