<template>
  <div class="table-wrap">
    <div v-if="store.loading" class="state-msg">Загрузка…</div>
    <div v-else-if="store.error" class="state-msg state-msg--error">{{ store.error }}</div>
    <div v-else-if="store.proxies.length === 0" class="state-msg">
      Прокси не найдены. Добавьте первый!
    </div>

    <table v-else class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Название</th>
          <th>Адрес</th>
          <th>Протокол</th>
          <th>Статус</th>
          <th>Время отклика</th>
          <th>Последняя проверка</th>
          <th>Действия</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="proxy in store.proxies" :key="proxy.id">
          <td class="td-id">{{ proxy.id }}</td>
          <td class="td-name">{{ proxy.name }}</td>
          <td class="td-addr">
            <code>{{ proxy.host }}:{{ proxy.port }}</code>
          </td>
          <td><span class="protocol-tag">{{ proxy.protocol.toUpperCase() }}</span></td>
          <td><ProxyStatusBadge :status="proxy.status" /></td>
          <td class="td-rt">
            {{ proxy.response_time_ms != null ? proxy.response_time_ms + ' мс' : '—' }}
          </td>
          <td class="td-date">{{ formatDate(proxy.last_checked_at) }}</td>
          <td class="td-actions">
            <button
              class="icon-btn icon-btn--check"
              title="Проверить"
              :disabled="proxy.status === 'checking'"
              @click="$emit('check', proxy.id)"
            >
              ↻
            </button>
            <button
              class="icon-btn icon-btn--edit"
              title="Редактировать"
              @click="$emit('edit', proxy)"
            >
              ✎
            </button>
            <button
              class="icon-btn icon-btn--delete"
              title="Удалить"
              @click="$emit('delete', proxy.id)"
            >
              ✕
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Пагинация -->
    <div v-if="store.meta && store.meta.last_page > 1" class="pagination">
      <button
        v-for="page in store.meta.last_page"
        :key="page"
        :class="['page-btn', { 'page-btn--active': page === store.meta.current_page }]"
        @click="$emit('page', page)"
      >
        {{ page }}
      </button>
    </div>
  </div>
</template>

<script setup>
import ProxyStatusBadge from './ProxyStatusBadge.vue'
import { useProxyStore } from '@/stores/proxy'

const store = useProxyStore()

defineEmits(['edit', 'delete', 'check', 'page'])

function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('ru-RU', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
</script>

<style scoped>
.table-wrap { overflow-x: auto; }
.state-msg { text-align: center; padding: 40px; color: #6b7280; font-size: .95rem; }
.state-msg--error { color: #dc2626; }

.table { width: 100%; border-collapse: collapse; font-size: .88rem; }
.table th {
  background: #f9fafb; text-align: left;
  padding: 10px 14px; font-size: .78rem;
  font-weight: 700; text-transform: uppercase;
  letter-spacing: .04em; color: #6b7280;
  border-bottom: 2px solid #e5e7eb;
}
.table td { padding: 11px 14px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.table tr:last-child td { border-bottom: none; }
.table tr:hover td { background: #f9fafb; }

.td-id { color: #9ca3af; width: 50px; }
.td-name { font-weight: 600; }
.td-addr code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: .82rem; }
.td-rt { color: #6b7280; }
.td-date { color: #9ca3af; white-space: nowrap; font-size: .8rem; }
.td-actions { display: flex; gap: 6px; }

.protocol-tag {
  background: #ede9fe; color: #5b21b6;
  padding: 2px 8px; border-radius: 4px;
  font-size: .75rem; font-weight: 700;
}

.icon-btn {
  width: 30px; height: 30px; border: none; border-radius: 6px;
  cursor: pointer; font-size: 1rem; display: inline-flex;
  align-items: center; justify-content: center; transition: background .15s;
}
.icon-btn:disabled { opacity: .4; cursor: not-allowed; }
.icon-btn--check  { background: #dbeafe; color: #1d4ed8; }
.icon-btn--check:hover:not(:disabled)  { background: #bfdbfe; }
.icon-btn--edit   { background: #fef9c3; color: #854d0e; }
.icon-btn--edit:hover   { background: #fef08a; }
.icon-btn--delete { background: #fee2e2; color: #991b1b; }
.icon-btn--delete:hover { background: #fecaca; }

.pagination { display: flex; gap: 6px; justify-content: center; padding: 16px 0 4px; }
.page-btn {
  width: 34px; height: 34px; border: 1px solid #e5e7eb;
  border-radius: 6px; cursor: pointer; background: #fff;
  font-size: .85rem; font-weight: 600; color: #374151;
  transition: all .15s;
}
.page-btn:hover { background: #f3f4f6; }
.page-btn--active { background: #6366f1; color: #fff; border-color: #6366f1; }
</style>

