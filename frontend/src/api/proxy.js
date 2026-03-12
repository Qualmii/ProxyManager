import axios from 'axios'

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

export const proxyApi = {
  getAll(params = {}) {
    return api.get('/proxies', { params })
  },

  getOne(id) {
    return api.get(`/proxies/${id}`)
  },

  create(data) {
    return api.post('/proxies', data)
  },

  update(id, data) {
    return api.put(`/proxies/${id}`, data)
  },

  remove(id) {
    return api.delete(`/proxies/${id}`)
  },

  check(id) {
    return api.post(`/proxies/${id}/check`)
  },

  checkAll() {
    return api.post('/proxies/check-all')
  },
}

