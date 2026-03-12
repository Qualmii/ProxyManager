import { defineStore } from 'pinia'
import { ref } from 'vue'
import { proxyApi } from '@/api/proxy'

export const useProxyStore = defineStore('proxy', () => {
  const proxies = ref([])
  const meta = ref(null)
  const loading = ref(false)
  const checkingAll = ref(false)
  const error = ref(null)

  async function fetchProxies(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await proxyApi.getAll(params)
      proxies.value = data.data
      meta.value = data.meta
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка загрузки'
    } finally {
      loading.value = false
    }
  }

  async function createProxy(formData) {
    const { data } = await proxyApi.create(formData)
    proxies.value.unshift(data.data)
    return data.data
  }

  async function updateProxy(id, formData) {
    const { data } = await proxyApi.update(id, formData)
    const idx = proxies.value.findIndex((p) => p.id === id)
    if (idx !== -1) proxies.value[idx] = data.data
    return data.data
  }

  async function deleteProxy(id) {
    await proxyApi.remove(id)
    proxies.value = proxies.value.filter((p) => p.id !== id)
  }

  async function checkProxy(id) {
    // Оптимистично выставляем статус checking
    const proxy = proxies.value.find((p) => p.id === id)
    if (proxy) proxy.status = 'checking'

    const { data } = await proxyApi.check(id)
    const idx = proxies.value.findIndex((p) => p.id === id)
    if (idx !== -1) proxies.value[idx] = data.proxy
  }

  async function checkAllProxies() {
    checkingAll.value = true
    // Выставляем всем статус checking
    proxies.value.forEach((p) => (p.status = 'checking'))
    try {
      await proxyApi.checkAll()
    } finally {
      checkingAll.value = false
    }
  }

  // Обновляем статусы после проверки, не перезагружая весь список
  async function refreshStatuses() {
    if (proxies.value.length === 0) return
    try {
      const { data } = await proxyApi.getAll({ per_page: 100 })
      const updated = data.data
      proxies.value = proxies.value.map((p) => {
        const fresh = updated.find((u) => u.id === p.id)
        return fresh || p
      })
    } catch (_) {}
  }

  return {
    proxies,
    meta,
    loading,
    checkingAll,
    error,
    fetchProxies,
    createProxy,
    updateProxy,
    deleteProxy,
    checkProxy,
    checkAllProxies,
    refreshStatuses,
  }
})

