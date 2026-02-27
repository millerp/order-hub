<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const role = ref('customer')
const loading = ref(false)
const error = ref(null)

async function submit() {
  error.value = null
  loading.value = true
  try {
    await auth.register(name.value, email.value, password.value, role.value)
    router.push('/products')
  } catch (e) {
    error.value = e.response?.data?.message || 'Registration failed.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-logo">⬡ <strong>OrderHub</strong></div>
      <h1 class="auth-title">Create account</h1>
      <p class="auth-sub">Join the OrderHub marketplace</p>

      <div v-if="error" class="alert alert-error">{{ error }}</div>

      <form @submit.prevent="submit" class="auth-form">
        <div class="form-group">
          <label>Name</label>
          <input v-model="name" type="text" placeholder="John Doe" required />
        </div>
        <div class="form-group">
          <label>Email address</label>
          <input v-model="email" type="email" placeholder="you@example.com" required />
        </div>
        <div class="form-group">
          <label>Password</label>
          <input v-model="password" type="password" placeholder="Min. 6 characters" required minlength="6" />
        </div>
        <div class="form-group">
          <label>Role</label>
          <select v-model="role">
            <option value="customer">Customer</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <button class="btn btn-primary btn-block" type="submit" :disabled="loading">
          <span v-if="!loading">Create Account</span>
          <span v-else class="spinner"></span>
        </button>
      </form>

      <p class="auth-footer">
        Already have an account?
        <router-link to="/login">Sign in</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.auth-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background: radial-gradient(ellipse at 80% 20%, rgba(124,58,237,0.08), transparent 60%), var(--bg); }
.auth-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 40px; width: 100%; max-width: 400px; display: flex; flex-direction: column; gap: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
.auth-logo { font-size: 24px; color: var(--accent); }
.auth-title { font-size: 24px; font-weight: 700; }
.auth-sub { color: var(--text-muted); font-size: 14px; margin-top: -12px; }
.auth-form { display: flex; flex-direction: column; gap: 16px; }
.auth-footer { font-size: 13px; color: var(--text-muted); text-align: center; }
.auth-footer a { color: var(--accent); text-decoration: none; font-weight: 500; }
</style>
