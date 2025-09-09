import axios from 'axios'

const toPermissionIri = (id) => `/api/permissions/${String(id).trim()}`
const compact = (obj) => {
  const out = {}
  Object.entries(obj || {}).forEach(([k, v]) => {
    if (v === undefined || v === null) return
    if (typeof v === 'string' && v.trim() === '') return
    out[k] = v
  })
  return out
}

const normalizeRolePayload = (input = {}) => {
  const payload = {
    name: input.name?.trim(),
    slug: input.slug?.trim(),            // si vide => retiré par compact()
    description: input.description?.trim(),
  }
  if (Array.isArray(input.permissions)) {
    payload.permissions = input.permissions.map(toPermissionIri)
  }
  return compact(payload)
}

class RolesApi {
  static async getAll() {
    const response = await axios.get('/api/roles')
    return response.data
  }

  static async getById(id) {
    const response = await axios.get(`/api/roles/${id}`)
    return response.data
  }

  static async create(roleData) {
    const response = await axios.post('/api/roles', normalizeRolePayload(roleData))
    return response.data
  }

  static async update(id, roleData) {
    const response = await axios.put(`/api/roles/${id}`, normalizeRolePayload(roleData))
    return response.data
  }

  static async delete(id) {
    await axios.delete(`/api/roles/${id}`)
  }

  static async getAllPermissions() {
    const response = await axios.get('/api/admin/permissions/grouped')
    return response.data
  }

  static async syncPermissions(roleId, permissionIds) {
    const response = await axios.put(`/api/roles/${roleId}`, normalizeRolePayload({ permissions: permissionIds }))
    return response.data
  }
}

export default RolesApi
