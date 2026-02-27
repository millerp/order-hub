<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const isLoggedIn = computed(() => auth.isLoggedIn)

function logout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="page">
    <header v-if="isLoggedIn" class="nav">
      <div class="nav-inner">
        <div class="nav-brand">
          <span class="nav-logo">⬡</span>
          <span>OrderHub</span>
        </div>
        <nav class="nav-links">
          <router-link to="/products">Products</router-link>
          <router-link to="/orders">My Orders</router-link>
        </nav>
        <div class="nav-user">
          <span class="nav-role-badge" :class="auth.isAdmin ? 'admin' : 'customer'">
            {{ auth.user?.role }}
          </span>
          <span class="nav-username">{{ auth.user?.name }}</span>
          <button class="btn btn-secondary" style="padding:6px 14px;font-size:13px" @click="logout">Logout</button>
        </div>
      </div>
    </header>

    <router-view />
  </div>
</template>

<style scoped>
.nav {
  position: sticky; top: 0; z-index: 50;
  background: rgba(13,17,23,0.85);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border);
}
.nav-inner {
  max-width: 1100px; margin: 0 auto;
  padding: 0 20px; height: 60px;
  display: flex; align-items: center; gap: 24px;
}
.nav-brand {
  display: flex; align-items: center; gap: 8px;
  font-weight: 700; font-size: 17px; flex: 1;
}
.nav-logo {
  font-size: 22px; color: var(--accent);
  filter: drop-shadow(0 0 8px var(--accent-glow));
}
.nav-links { display: flex; gap: 4px; }
.nav-links a {
  color: var(--text-muted); text-decoration: none;
  padding: 6px 14px; border-radius: 8px; font-size: 14px; font-weight: 500;
  transition: var(--transition);
}
.nav-links a:hover, .nav-links a.router-link-active {
  color: var(--text); background: var(--surface-2);
}
.nav-user { display: flex; align-items: center; gap: 10px; margin-left: auto; }
.nav-username { font-size: 13px; color: var(--text-muted); }
.nav-role-badge {
  font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 100px; letter-spacing: 0.5px;
}
.nav-role-badge.admin { background: rgba(124,58,237,0.2); color: var(--accent); }
.nav-role-badge.customer { background: rgba(16,185,129,0.1); color: var(--success); }
</style>
