// src/services/httpClient.js
import axios from 'axios'

// Istanza axios centralizzata
export const httpClient = axios.create({
  baseURL: '/api',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
})

// Interceptor per requests (auth token, loading, etc.)
httpClient.interceptors.request.use(
  (config) => {
    // Aggiungi token se presente
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Interceptor per responses (gestione errori centralizzata)
httpClient.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    // Gestione errori centralizzata
    if (error.response?.status === 401) {
      // Logout automatico per unauthorized
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
    }
    
    if (error.response?.status === 422) {
      // Errori di validazione - mantieni il formato originale
      return Promise.reject(error)
    }
    
    // Altri errori - normalizza il messaggio
    const message = error.response?.data?.message || 
                   error.response?.data?.error || 
                   error.message || 
                   'Errore di connessione'
    
    return Promise.reject(new Error(message))
  }
)

export default httpClient