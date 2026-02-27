<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref(null)

async function submit() {
  error.value = null
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push('/products')
  } catch (e) {
    error.value = e.response?.data?.message || 'Login failed. Check credentials.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-logo">⬡ <strong>OrderHub</strong></div>
      <h1 class="auth-title">Welcome back</h1>
      <p class="auth-sub">Sign in to your marketplace account</p>

      <div v-if="error" class="alert alert-error">{{ error }}</div>

      <form @submit.prevent="submit" class="auth-form">
        <div class="form-group">
          <label>Email address</label>
          <input v-model="email" type="email" placeholder="admin@example.com" required />
        </div>
        <div class="form-group">
          <label>Password</label>
          <input v-model="password" type="password" placeholder="••••••••" required />
        </div>
        <button class="btn btn-primary btn-block" type="submit" :disabled="loading">
          <span v-if="!loading">Sign In</span>
          <span v-else class="spinner"></span>
        </button>
      </form>

      <p class="auth-footer">
        Don't have an account?
        <router-link to="/register">Create one</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.auth-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background: radial-gradient(ellipse at 20% 80%, rgba(124,58,237,0.08), transparent 60%), var(--bg); }
.auth-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 40px; width: 100%; max-width: 400px; display: flex; flex-direction: column; gap: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
.auth-logo { font-size: 24px; color: var(--accent); filter: drop-shadow(0 0 12px var(--accent-glow)); }
.auth-title { font-size: 24px; font-weight: 700; }
.auth-sub { color: var(--text-muted); font-size: 14px; margin-top: -12px; }
.auth-form { display: flex; flex-direction: column; gap: 16px; }
.auth-footer { font-size: 13px; color: var(--text-muted); text-align: center; }
.auth-footer a { color: var(--accent); text-decoration: none; font-weight: 500; }
.auth-footer a:hover { text-decoration: underline; }
</style>
