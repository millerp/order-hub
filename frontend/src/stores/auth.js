import { defineStore } from 'pinia'
import api from '@/api'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: sessionStorage.getItem('token') || null,
        user: JSON.parse(sessionStorage.getItem('user') || 'null'),
    }),
    getters: {
        isAdmin: (state) => state.user?.role === 'admin',
        isLoggedIn: (state) => !!state.token,
    },
    actions: {
        async login(email, password) {
            const { data } = await api.post('/auth/login', { email, password })
            const payload = data.data || data
            this.token = payload.token
            this.user = payload.user
            sessionStorage.setItem('token', payload.token)
            sessionStorage.setItem('user', JSON.stringify(payload.user))
        },
        async register(name, email, password, role = 'customer') {
            const { data } = await api.post('/auth/register', { name, email, password, role })
            const payload = data.data || data
            this.token = payload.token
            this.user = payload.user
            sessionStorage.setItem('token', payload.token)
            sessionStorage.setItem('user', JSON.stringify(payload.user))
        },
        logout() {
            this.token = null
            this.user = null
            sessionStorage.removeItem('token')
            sessionStorage.removeItem('user')
        },
    },
})
