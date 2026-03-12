<template>
  <div class="view">
    <!-- Шапка -->
    <div class="header">
      <div>
        <h1 class="title">Proxy Manager</h1>
        <p class="subtitle">Управление прокси-серверами</p>
      </div>
      <div class="header-actions">
        <button class="btn btn--ghost" :disabled="store.checkingAll" @click="handleCheckAll">
          {{ store.checkingAll ? 'Проверяем…' : '↻ Проверить все' }}
        </button>
        <button class="btn btn--primary" @click="openCreate">+ Добавить прокси</button>
      </div>
    </div>

    <!-- Фильтры -->
    <div class="filters">
      <input
        v-model="search"
        type="text"
        placeholder="Поиск по названию или хосту…"
        class="search-input"
        @input="debouncedFetch"
      />
      <div class="status-filters">
        <button
          v-for="opt in statusOptions"
          :key="opt.value"
          :class="['filter-btn', { 'filter-btn--active': statusFilter === opt.value }]"
          @click="setStatusFilter(opt.value)"
        >
          {{ opt.label }}
        </button>
      </div>
      <div class="ws-status">
        <span class="ws-dot" :class="wsConnected ? 'ws-dot--on' : 'ws-dot--off'" />
        {{ wsConnected ? 'Live' : 'Connecting…' }}
      </div>
    </div>

    <!-- Таблица -->
    <div class="card">
      <ProxyTable
        @edit="openEdit"
        @delete="handleDelete"
        @check="handleCheck"
        @page="handlePage"
      />
    </div>

    <!-- Модальное окно формы -->
    <ProxyForm
      v-if="showForm"
      :proxy="editingProxy"
      @close="closeForm"
      @saved="onSaved"
    />

    <!-- Toast уведомления -->
    <div class="toasts">
      <div v-for="t in toasts" :key="t.id" :class="['toast', `toast--${t.type}`]">
        {{ t.message }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useProxyStore } from '@/stores/proxy'
import ProxyTable from '@/components/ProxyTable.vue'
import ProxyForm from '@/components/ProxyForm.vue'
import echo from '@/echo.js'

const store = useProxyStore()

// --- Форма ---
const showForm = ref(false)
const editingProxy = ref(null)

function openCreate() {
  editingProxy.value = null
  showForm.value = true
}
function openEdit(proxy) {
  editingProxy.value = proxy
  showForm.value = true
}
function closeForm() {
  showForm.value = false
  editingProxy.value = null
}
function onSaved() {
  showToast('Прокси успешно сохранён', 'success')
}

// --- Фильтры ---
const search = ref('')
const statusFilter = ref('')
const currentPage = ref(1)

const statusOptions = [
  { value: '', label: 'Все' },
  { value: 'active', label: 'Активные' },
  { value: 'inactive', label: 'Неактивные' },
  { value: 'checking', label: 'Проверяются' },
]

function buildParams() {
  return {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    page: currentPage.value,
    per_page: 15,
  }
}

function setStatusFilter(value) {
  statusFilter.value = value
  currentPage.value = 1
  store.fetchProxies(buildParams())
}

function handlePage(page) {
  currentPage.value = page
  store.fetchProxies(buildParams())
}

let searchTimer = null
function debouncedFetch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    store.fetchProxies(buildParams())
  }, 400)
}

// --- Действия ---
async function handleCheck(id) {
  try {
    await store.checkProxy(id)
    showToast('Проверка запущена', 'info')
  } catch {
    showToast('Ошибка при запуске проверки', 'error')
  }
}

async function handleCheckAll() {
  try {
    await store.checkAllProxies()
    showToast('Проверка всех прокси запущена', 'info')
  } catch {
    showToast('Ошибка', 'error')
  }
}

async function handleDelete(id) {
  if (!confirm('Удалить этот прокси?')) return
  try {
    await store.deleteProxy(id)
    showToast('Прокси удалён', 'success')
  } catch {
    showToast('Ошибка при удалении', 'error')
  }
}

// --- WebSocket ---
const wsConnected = ref(false)
let wsChannel = null

function subscribeToUpdates() {
  // Отслеживаем состояние соединения
  echo.connector.pusher.connection.bind('connected', () => {
    wsConnected.value = true
  })
  echo.connector.pusher.connection.bind('disconnected', () => {
    wsConnected.value = false
  })
  echo.connector.pusher.connection.bind('unavailable', () => {
    wsConnected.value = false
  })

  // Подписываемся на канал proxies
  wsChannel = echo
    .channel('proxies')
    .listen('.proxy.status.updated', (event) => {
      store.applyStatusUpdate(event.proxy)
    })
}

function unsubscribeFromUpdates() {
  if (wsChannel) {
    echo.leaveChannel('proxies')
    wsChannel = null
  }
}

// --- Toast ---
const toasts = ref([])
let toastCounter = 0

function showToast(message, type = 'info') {
  const id = ++toastCounter
  toasts.value.push({ id, message, type })
  setTimeout(() => {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }, 3000)
}

// --- Lifecycle ---
onMounted(() => {
  store.fetchProxies(buildParams())
  subscribeToUpdates()
})

onUnmounted(() => {
  unsubscribeFromUpdates()
  clearTimeout(searchTimer)
})
</script>

<style scoped>
.view { max-width: 1200px; margin: 0 auto; padding: 32px 24px; }

.header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; gap: 16px; flex-wrap: wrap; }
.title { margin: 0; font-size: 1.8rem; font-weight: 800; color: #1f2937; }
.subtitle { margin: 4px 0 0; color: #6b7280; font-size: .9rem; }
.header-actions { display: flex; gap: 10px; align-items: center; }

.filters { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.search-input {
  flex: 1; min-width: 200px; padding: 8px 12px;
  border: 1px solid #d1d5db; border-radius: 8px;
  font-size: .9rem; outline: none; transition: border-color .15s;
}
.search-input:focus { border-color: #6366f1; }

.status-filters { display: flex; gap: 6px; }
.filter-btn {
  padding: 6px 14px; border-radius: 20px; border: 1px solid #e5e7eb;
  background: #fff; cursor: pointer; font-size: .82rem; font-weight: 600;
  color: #374151; transition: all .15s;
}
.filter-btn:hover { background: #f3f4f6; }
.filter-btn--active { background: #6366f1; color: #fff; border-color: #6366f1; }

/* Индикатор WebSocket */
.ws-status {
  display: flex; align-items: center; gap: 6px;
  font-size: .78rem; font-weight: 600; color: #6b7280;
  white-space: nowrap;
}
.ws-dot {
  width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.ws-dot--on  { background: #10b981; box-shadow: 0 0 0 2px #d1fae5; }
.ws-dot--off { background: #f59e0b; box-shadow: 0 0 0 2px #fef3c7; animation: pulse .8s ease infinite alternate; }

@keyframes pulse { to { opacity: .4; } }

.card { background: #fff; border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.08); overflow: hidden; }

.btn { padding: 9px 20px; border-radius: 8px; font-size: .9rem; font-weight: 600; cursor: pointer; border: none; transition: all .15s; }
.btn:disabled { opacity: .6; cursor: not-allowed; }
.btn--primary { background: #6366f1; color: #fff; }
.btn--primary:hover:not(:disabled) { background: #4f46e5; }
.btn--ghost { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }
.btn--ghost:hover:not(:disabled) { background: #e5e7eb; }

.toasts { position: fixed; bottom: 24px; right: 24px; display: flex; flex-direction: column; gap: 8px; z-index: 200; }
.toast {
  padding: 12px 20px; border-radius: 8px; font-size: .88rem;
  font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,.15);
  animation: slide-in .25s ease;
}
.toast--success { background: #d1fae5; color: #065f46; }
.toast--error   { background: #fee2e2; color: #991b1b; }
.toast--info    { background: #dbeafe; color: #1e40af; }

@keyframes slide-in {
  from { transform: translateX(30px); opacity: 0; }
  to   { transform: translateX(0);    opacity: 1; }
}
</style>

