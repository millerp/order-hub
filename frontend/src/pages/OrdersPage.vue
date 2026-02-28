<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import api from '@/api'

const orders = ref([])
const loading = ref(true)
const error = ref(null)
const liveConnected = ref(false)
let stream = null

async function fetchOrders() {
  loading.value = true
  try {
    const { data } = await api.get('/orders')
    orders.value = Array.isArray(data) ? data : (data.data || [])
  } catch {
    error.value = 'Failed to load orders.'
  } finally {
    loading.value = false
  }
}

function statusClass(status) {
  const map = { pending: 'badge-warning', paid: 'badge-success', cancelled: 'badge-danger' }
  return map[status] ?? 'badge-default'
}

function statusIcon(status) {
  const map = { pending: '⏳', paid: '✓', cancelled: '✕' }
  return map[status] ?? '·'
}

function formatDate(d) {
  return new Date(d).toLocaleString()
}

function connectOrderStream() {
  const token = sessionStorage.getItem('token')
  if (!token) return

  const baseUrl = (import.meta.env.VITE_API_URL || 'http://localhost/api/v1').replace(/\/$/, '')
  const streamUrl = `${baseUrl}/orders/stream?access_token=${encodeURIComponent(token)}&max_iterations=20`

  stream = new EventSource(streamUrl)
  stream.onopen = () => { liveConnected.value = true }
  stream.onerror = () => { liveConnected.value = false }
  stream.addEventListener('orders', (event) => {
    try {
      const payload = JSON.parse(event.data)
      orders.value = payload.data || []
      error.value = null
    } catch {
      // no-op: keep last known state
    }
  })
}

onMounted(() => {
  fetchOrders()
  connectOrderStream()
})

onUnmounted(() => {
  if (stream) stream.close()
})
</script>

<template>
  <div class="page-inner">
    <div class="page-header">
      <div>
        <h1 class="page-title">My Orders</h1>
        <p class="page-subtitle">
          Track the status of your orders
          <span :class="['live-indicator', liveConnected ? 'is-on' : 'is-off']">
            {{ liveConnected ? 'live' : 'offline' }}
          </span>
        </p>
      </div>
      <button class="btn btn-secondary" @click="fetchOrders">↺ Refresh</button>
    </div>

    <div v-if="error" class="alert alert-error">{{ error }}</div>

    <div v-if="loading" style="text-align:center;padding:60px 0">
      <div class="spinner" style="margin:0 auto;width:36px;height:36px;border-width:3px"></div>
    </div>

    <div v-else-if="!orders.length" class="empty-state">
      <div class="empty-icon">🛒</div>
      <h3>No orders yet</h3>
      <p>Head to the <router-link to="/products">product catalog</router-link> to place your first order!</p>
    </div>

    <div v-else class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Order #</th>
            <th>Product ID</th>
            <th>Qty</th>
            <th>Total</th>
            <th>Status</th>
            <th>Placed At</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="o in orders" :key="o.id">
            <td><span class="order-id">#{{ o.id }}</span></td>
            <td><span style="color:var(--text-muted)">Product #{{ o.product_id }}</span></td>
            <td>{{ o.quantity }}</td>
            <td style="font-weight:600;color:var(--accent)">${{ Number(o.total_amount).toFixed(2) }}</td>
            <td>
              <span :class="['badge', statusClass(o.status)]">
                {{ statusIcon(o.status) }} {{ o.status }}
              </span>
            </td>
            <td style="color:var(--text-muted);font-size:12px">{{ formatDate(o.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="orders.length" class="orders-legend">
      <span class="badge badge-warning">⏳ pending</span> — awaiting payment
      <span class="badge badge-success">✓ paid</span> — payment approved
      <span class="badge badge-danger">✕ cancelled</span> — payment failed
    </div>
  </div>
</template>

<style scoped>
.empty-state { text-align: center; padding: 80px 20px; }
.empty-icon { font-size: 60px; margin-bottom: 16px; }
.empty-state h3 { font-size: 20px; margin-bottom: 8px; }
.empty-state p { color: var(--text-muted); font-size: 14px; }
.empty-state a { color: var(--accent); text-decoration: none; }
.order-id { font-weight: 600; font-family: monospace; font-size: 13px; color: var(--accent); }
.orders-legend { margin-top: 20px; display: flex; gap: 20px; align-items: center; color: var(--text-muted); font-size: 13px; flex-wrap: wrap; }
.live-indicator { margin-left: 10px; font-size: 11px; border-radius: 999px; padding: 2px 8px; text-transform: uppercase; letter-spacing: .04em; }
.live-indicator.is-on { background: rgba(34, 197, 94, .15); color: #16a34a; }
.live-indicator.is-off { background: rgba(234, 179, 8, .15); color: #ca8a04; }
</style>
