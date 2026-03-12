<template>
  <span :class="badgeClass" class="status-badge">
    <span v-if="status === 'checking'" class="spinner" />
    {{ label }}
  </span>
</template>

<script setup>
const props = defineProps({
  status: {
    type: String,
    required: true,
  },
})

const map = {
  active:   { label: 'Активен',    cls: 'badge--active' },
  inactive: { label: 'Недоступен', cls: 'badge--inactive' },
  checking: { label: 'Проверка…',  cls: 'badge--checking' },
}

const label = computed(() => map[props.status]?.label ?? props.status)
const badgeClass = computed(() => map[props.status]?.cls ?? '')
</script>

<script>
import { computed } from 'vue'
export default { name: 'ProxyStatusBadge' }
</script>

<style scoped>
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 10px;
  border-radius: 12px;
  font-size: 0.78rem;
  font-weight: 600;
  white-space: nowrap;
}
.badge--active   { background: #d1fae5; color: #065f46; }
.badge--inactive { background: #fee2e2; color: #991b1b; }
.badge--checking { background: #fef9c3; color: #854d0e; }

.spinner {
  width: 10px;
  height: 10px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>

