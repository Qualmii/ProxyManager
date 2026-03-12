<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal">
      <h2 class="modal-title">{{ isEdit ? 'Редактировать прокси' : 'Добавить прокси' }}</h2>

      <form @submit.prevent="submit" class="form">
        <div class="form-row">
          <div class="field">
            <label>Название *</label>
            <input v-model="form.name" type="text" placeholder="Мой прокси" required />
            <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
          </div>
          <div class="field field--sm">
            <label>Протокол *</label>
            <select v-model="form.protocol" required>
              <option value="http">HTTP</option>
              <option value="https">HTTPS</option>
              <option value="socks4">SOCKS4</option>
              <option value="socks5">SOCKS5</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="field">
            <label>Хост *</label>
            <input v-model="form.host" type="text" placeholder="192.168.1.1" required />
            <span v-if="errors.host" class="field-error">{{ errors.host[0] }}</span>
          </div>
          <div class="field field--xs">
            <label>Порт *</label>
            <input v-model.number="form.port" type="number" min="1" max="65535" placeholder="8080" required />
            <span v-if="errors.port" class="field-error">{{ errors.port[0] }}</span>
          </div>
        </div>

        <div class="form-row">
          <div class="field">
            <label>Логин</label>
            <input v-model="form.username" type="text" placeholder="(необязательно)" />
          </div>
          <div class="field">
            <label>Пароль</label>
            <input v-model="form.password" type="password" placeholder="(необязательно)" />
          </div>
        </div>

        <p v-if="serverError" class="server-error">{{ serverError }}</p>

        <div class="form-actions">
          <button type="button" class="btn btn--ghost" @click="$emit('close')">Отмена</button>
          <button type="submit" class="btn btn--primary" :disabled="saving">
            {{ saving ? 'Сохранение…' : (isEdit ? 'Сохранить' : 'Добавить') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useProxyStore } from '@/stores/proxy'

const props = defineProps({
  proxy: { type: Object, default: null },
})
const emit = defineEmits(['close', 'saved'])

const store = useProxyStore()
const saving = ref(false)
const errors = ref({})
const serverError = ref('')

const isEdit = computed(() => !!props.proxy)

const form = reactive({
  name: '',
  host: '',
  port: '',
  protocol: 'http',
  username: '',
  password: '',
})

watch(
  () => props.proxy,
  (p) => {
    if (p) {
      form.name = p.name
      form.host = p.host
      form.port = p.port
      form.protocol = p.protocol
      form.username = p.username || ''
      form.password = ''
    }
  },
  { immediate: true }
)

async function submit() {
  errors.value = {}
  serverError.value = ''
  saving.value = true

  const payload = {
    name: form.name,
    host: form.host,
    port: Number(form.port),
    protocol: form.protocol,
    username: form.username || null,
    password: form.password || null,
  }

  try {
    if (isEdit.value) {
      await store.updateProxy(props.proxy.id, payload)
    } else {
      await store.createProxy(payload)
    }
    emit('saved')
    emit('close')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else {
      serverError.value = e.response?.data?.message || 'Произошла ошибка'
    }
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.45);
  display: flex; align-items: center; justify-content: center;
  z-index: 100;
}
.modal {
  background: #fff;
  border-radius: 12px;
  padding: 28px 32px;
  width: 100%;
  max-width: 560px;
  box-shadow: 0 20px 60px rgba(0,0,0,.2);
}
.modal-title { margin: 0 0 20px; font-size: 1.2rem; }
.form { display: flex; flex-direction: column; gap: 14px; }
.form-row { display: flex; gap: 12px; }
.field { display: flex; flex-direction: column; flex: 1; }
.field--sm { flex: 0 0 140px; }
.field--xs { flex: 0 0 110px; }
label { font-size: .82rem; font-weight: 600; margin-bottom: 4px; color: #374151; }
input, select {
  padding: 8px 10px; border: 1px solid #d1d5db;
  border-radius: 6px; font-size: .9rem; outline: none;
  transition: border-color .15s;
}
input:focus, select:focus { border-color: #6366f1; }
.field-error { font-size: .75rem; color: #dc2626; margin-top: 2px; }
.server-error { color: #dc2626; font-size: .85rem; }
.form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; }
.btn { padding: 8px 20px; border-radius: 7px; font-size: .9rem; font-weight: 600; cursor: pointer; border: none; transition: opacity .15s; }
.btn:disabled { opacity: .6; cursor: not-allowed; }
.btn--primary { background: #6366f1; color: #fff; }
.btn--primary:hover:not(:disabled) { background: #4f46e5; }
.btn--ghost { background: #f3f4f6; color: #374151; }
.btn--ghost:hover { background: #e5e7eb; }
</style>

