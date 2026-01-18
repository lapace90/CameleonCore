import axios from 'axios'

// Instance axios centralisée avec la bonne baseURL
export const httpClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
})

// Interceptor pour requests (auth token, loading, etc.)
httpClient.interceptors.request.use(
  (config) => {
    // Ajouter le token si présent
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

// Interceptor pour responses (gestione errori centralizzata)
httpClient.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    // Gestion centralisée des erreurs
    if (error.response?.status === 401) {
      // Logout automatique pour unauthorized
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
    }
    
    if (error.response?.status === 422) {
      // Erreurs de validation - maintenir le format original
      return Promise.reject(error)
    }
    
    // Autres erreurs - normaliser le message
    const message = error.response?.data?.message || 
                   error.response?.data?.error || 
                   error.message || 
                   'Erreur de connexion'
    
    return Promise.reject(new Error(message))
  }
)

export default httpClient