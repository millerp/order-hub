<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/api'

const auth = useAuthStore()
const products = ref([])
const loading = ref(true)
const error = ref(null)
const success = ref(null)

// Order Modal
const showOrderModal = ref(false)
const selectedProduct = ref(null)
const quantity = ref(1)
const ordering = ref(false)
const orderError = ref(null)

// Admin: Add Product Modal
const showAddModal = ref(false)
const form = ref({ name: '', description: '', price: '', stock: '' })
const saving = ref(false)
const saveError = ref(null)

async function fetchProducts() {
  loading.value = true
  try {
    const { data } = await api.get('/products')
    products.value = Array.isArray(data) ? data : (data.data || [])
  } catch {
    error.value = 'Failed to load products.'
  } finally {
    loading.value = false
  }
}

function openOrder(product) {
  selectedProduct.value = product
  quantity.value = 1
  orderError.value = null
  showOrderModal.value = true
}

async function placeOrder() {
  ordering.value = true
  orderError.value = null
  try {
    await api.post('/orders', { product_id: selectedProduct.value.id, quantity: quantity.value })
    showOrderModal.value = false
    success.value = `✓ Order placed for "${selectedProduct.value.name}"!`
    setTimeout(() => success.value = null, 4000)
    fetchProducts()
  } catch (e) {
    orderError.value = e.response?.data?.message || 'Order failed.'
  } finally {
    ordering.value = false
  }
}

async function saveProduct() {
  saving.value = true
  saveError.value = null
  try {
    await api.post('/products', { ...form.value, price: parseFloat(form.value.price), stock: parseInt(form.value.stock) })
    showAddModal.value = false
    form.value = { name: '', description: '', price: '', stock: '' }
    fetchProducts()
  } catch (e) {
    saveError.value = e.response?.data?.message || 'Failed to save product.'
  } finally {
    saving.value = false
  }
}

function stockBadge(stock) {
  if (stock <= 0) return 'badge-danger'
  if (stock < 5) return 'badge-warning'
  return 'badge-success'
}

onMounted(fetchProducts)
</script>

<template>
  <div class="page-inner">
    <div class="page-header">
      <div>
        <h1 class="page-title">Product Catalog</h1>
        <p class="page-subtitle">Browse and order available products</p>
      </div>
      <button v-if="auth.isAdmin" class="btn btn-primary" @click="showAddModal = true">+ Add Product</button>
    </div>

    <div v-if="success" class="alert alert-success">{{ success }}</div>
    <div v-if="error" class="alert alert-error">{{ error }}</div>

    <div v-if="loading" style="text-align:center;padding:60px 0">
      <div class="spinner" style="margin:0 auto;width:36px;height:36px;border-width:3px"></div>
    </div>

    <div v-else class="grid-3">
      <div v-for="p in products" :key="p.id" class="card product-card">
        <div class="product-header">
          <span class="product-icon">📦</span>
          <span :class="['badge', stockBadge(p.stock)]">Stock: {{ p.stock }}</span>
        </div>
        <h3 class="product-name">{{ p.name }}</h3>
        <p class="product-desc">{{ p.description || 'No description provided.' }}</p>
        <div class="product-footer">
          <span class="product-price">${{ Number(p.price).toFixed(2) }}</span>
          <button class="btn btn-primary" style="padding:7px 16px;font-size:13px" :disabled="p.stock <= 0" @click="openOrder(p)">
            {{ p.stock > 0 ? 'Order Now' : 'Out of Stock' }}
          </button>
        </div>
      </div>

      <div v-if="!products.length" style="grid-column:1/-1;text-align:center;padding:60px 0;color:var(--text-muted)">
        No products available yet.
      </div>
    </div>

    <!-- Order Modal -->
    <div v-if="showOrderModal" class="modal-overlay" @click.self="showOrderModal = false">
      <div class="modal">
        <h2 class="modal-title">Place Order</h2>
        <p style="color:var(--text-muted);font-size:13px">Ordering: <strong style="color:var(--text)">{{ selectedProduct?.name }}</strong></p>
        <div v-if="orderError" class="alert alert-error">{{ orderError }}</div>
        <div class="form-group">
          <label>Quantity (max: {{ selectedProduct?.stock }})</label>
          <input v-model.number="quantity" type="number" min="1" :max="selectedProduct?.stock" />
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-secondary" style="flex:1" @click="showOrderModal = false">Cancel</button>
          <button class="btn btn-primary" style="flex:2" :disabled="ordering" @click="placeOrder">
            <span v-if="!ordering">Confirm Order — ${{ (selectedProduct?.price * quantity).toFixed(2) }}</span>
            <span v-else class="spinner"></span>
          </button>
        </div>
      </div>
    </div>

    <!-- Add Product Modal (Admin Only) -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="showAddModal = false">
      <div class="modal">
        <h2 class="modal-title">Add Product</h2>
        <div v-if="saveError" class="alert alert-error">{{ saveError }}</div>
        <div class="form-group"><label>Name</label><input v-model="form.name" placeholder="Product name" /></div>
        <div class="form-group"><label>Description</label><input v-model="form.description" placeholder="Optional" /></div>
        <div class="form-group"><label>Price ($)</label><input v-model="form.price" type="number" min="0" step="0.01" /></div>
        <div class="form-group"><label>Stock</label><input v-model="form.stock" type="number" min="0" /></div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-secondary" style="flex:1" @click="showAddModal = false">Cancel</button>
          <button class="btn btn-primary" style="flex:2" :disabled="saving" @click="saveProduct">
            <span v-if="!saving">Save Product</span>
            <span v-else class="spinner"></span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.product-card { display: flex; flex-direction: column; gap: 10px; }
.product-header { display: flex; align-items: center; justify-content: space-between; }
.product-icon { font-size: 28px; }
.product-name { font-size: 16px; font-weight: 600; }
.product-desc { font-size: 13px; color: var(--text-muted); flex: 1; }
.product-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 4px; }
.product-price { font-size: 20px; font-weight: 700; color: var(--accent); }
</style>
