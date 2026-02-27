import axios from 'axios'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost/api/v1',
    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
})

api.interceptors.request.use((config) => {
    const token = sessionStorage.getItem('token')
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
})

api.interceptors.response.use(
    (res) => res,
    (err) => {
        if (err.response?.status === 401) {
            sessionStorage.removeItem('token')
            sessionStorage.removeItem('user')
            window.location.href = '/login'
        }
        // Surface backend validation errors
        if (err.response?.status === 422 && err.response?.data?.errors) {
            const messages = err.response.data.errors
            const firstMessage = typeof messages === 'object' && !Array.isArray(messages)
                ? Object.values(messages).flat().join(' ')
                : Array.isArray(messages) ? messages.join(' ') : err.response.data.message || 'Validation failed'
            err.message = firstMessage
        } else if (err.response?.data?.message) {
            err.message = err.response.data.message
        }
        return Promise.reject(err)
    }
)

export default api
