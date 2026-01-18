import httpClient from './httpClient'

const toPermissionIri = (id) => `/permissions/${String(id).trim()}`
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
    slug: input.slug?.trim(),
    description: input.description?.trim(),
  }
  if (Array.isArray(input.permissions)) {
    payload.permissions = input.permissions.map(toPermissionIri)
  }
  return compact(payload)
}

class RolesApi {
  static async getAll() {
    const response = await httpClient.get('/roles')
    return response.data
  }

  static async getById(id) {
    const response = await httpClient.get(`/roles/${id}`)
    return response.data
  }

  static async create(roleData) {
    const response = await httpClient.post('/roles', normalizeRolePayload(roleData))
    return response.data
  }

  static async update(id, roleData) {
    const response = await httpClient.put(`/roles/${id}`, normalizeRolePayload(roleData))
    return response.data
  }

  static async delete(id) {
    await httpClient.delete(`/roles/${id}`)
  }

  static async getAllPermissions() {
    const response = await httpClient.get('/admin/permissions/grouped')
    return response.data
  }

  static async syncPermissions(roleId, permissionIds) {
    const response = await httpClient.put(`/roles/${roleId}`, normalizeRolePayload({ permissions: permissionIds }))
    return response.data
  }
}

export default RolesApi