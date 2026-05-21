import { showToast } from '@/shared/utils/toast'

const BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
const MUTATING = new Set(['POST', 'PUT', 'PATCH', 'DELETE'])

async function request(method, path, data, options = {}) {
  const { params, headers: extraHeaders = {}, timeout, responseType } = options

  // Build URL, appending query params from options if provided
  let url = BASE_URL + path
  if (params) {
    const qs = new URLSearchParams()
    Object.entries(params).forEach(([k, v]) => {
      if (v !== null && v !== undefined && v !== '') qs.append(k, v)
    })
    const str = qs.toString()
    if (str) url += (url.includes('?') ? '&' : '?') + str
  }

  const token = localStorage.getItem('auth-token')
  const headers = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...extraHeaders,
  }

  // Let the browser set Content-Type with multipart boundary for FormData
  if (data instanceof FormData) {
    delete headers['Content-Type']
  }

  const fetchOptions = { method, headers }
  if (data !== null && data !== undefined) {
    fetchOptions.body = data instanceof FormData ? data : JSON.stringify(data)
  }

  let controller, timerId
  if (timeout) {
    controller = new AbortController()
    fetchOptions.signal = controller.signal
    timerId = setTimeout(() => controller.abort(), timeout)
  }

  let response
  try {
    response = await fetch(url, fetchOptions)
  } catch (err) {
    if (timerId) clearTimeout(timerId)
    if (err.name === 'AbortError') {
      const timeoutErr = new Error('Le serveur met trop de temps à répondre')
      timeoutErr.code = 'ECONNABORTED'
      if (MUTATING.has(method)) showToast('Le serveur met trop de temps à répondre', 'error')
      throw timeoutErr
    }
    const msg = err.message || 'Erreur de connexion'
    if (MUTATING.has(method)) showToast(msg, 'error')
    throw new Error(msg)
  }

  if (timerId) clearTimeout(timerId)

  if (response.status === 401) {
    localStorage.removeItem('auth-token')
    window.location.href = '/login'
    throw new Error('Session expirée')
  }

  let body
  if (responseType === 'blob') {
    body = await response.blob()
  } else {
    const text = await response.text()
    try {
      body = text ? JSON.parse(text) : null
    } catch {
      body = null
    }
  }

  if (!response.ok) {
    const message = body?.message || body?.error || `Erreur HTTP ${response.status}`
    if (MUTATING.has(method)) showToast(message, 'error')

    if (response.status === 422) {
      const err = new Error(message)
      err.response = { status: 422, data: body }
      throw err
    }

    throw new Error(message)
  }

  if (MUTATING.has(method)) showToast('Action réalisée avec succès')

  const headersObj = {}
  response.headers.forEach((v, k) => { headersObj[k] = v })

  return { data: body, headers: headersObj, status: response.status }
}

export const httpClient = {
  get:    (path, options)        => request('GET',    path, null, options),
  post:   (path, data, options)  => request('POST',   path, data, options),
  put:    (path, data, options)  => request('PUT',    path, data, options),
  patch:  (path, data, options)  => request('PATCH',  path, data, options),
  delete: (path, options)        => request('DELETE', path, null, options),
}

export default httpClient
