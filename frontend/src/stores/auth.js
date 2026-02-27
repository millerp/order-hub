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
            this.token = data.token
            this.user = data.user
            sessionStorage.setItem('token', data.token)
            sessionStorage.setItem('user', JSON.stringify(data.user))
        },
        async register(name, email, password, role = 'customer') {
            const { data } = await api.post('/auth/register', { name, email, password, role })
            this.token = data.token
            this.user = data.user
            sessionStorage.setItem('token', data.token)
            sessionStorage.setItem('user', JSON.stringify(data.user))
        },
        logout() {
            this.token = null
            this.user = null
            sessionStorage.removeItem('token')
            sessionStorage.removeItem('user')
        },
    },
})
