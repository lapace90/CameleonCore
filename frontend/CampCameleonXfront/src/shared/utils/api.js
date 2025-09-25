// import { useAuthStore } from '../stores/auth'

// class ApiClient {
//   constructor(baseURL = '/api') {
//     this.baseURL = baseURL;
//   }

//   async request(endpoint, options = {}) {
//     const authStore = useAuthStore();
    
//     const config = {
//       headers: {
//         'Content-Type': 'application/json',
//         ...options.headers,
//       },
//       ...options,
//     };

//     // Ajouter le token d'authentification si disponible
//     if (authStore.token) {
//       config.headers.Authorization = `Bearer ${authStore.token}`;
//     }

//     try {
//       const response = await fetch(`${this.baseURL}${endpoint}`, config);
      
//       // Gestion des erreurs HTTP
//       if (!response.ok) {
//         if (response.status === 401) {
//           // Token expiré, déconnecter l'utilisateur
//           authStore.logout();
//           throw new Error('Session expirée');
//         }
        
//         const errorData = await response.json().catch(() => ({}));
//         throw new Error(errorData.message || `Erreur HTTP ${response.status}`);
//       }

//       return await response.json();
//     } catch (error) {
//       console.error('Erreur API:', error);
//       throw error;
//     }
//   }

//   // Méthodes de commodité
//   get(endpoint, options = {}) {
//     return this.request(endpoint, { ...options, method: 'GET' });
//   }

//   post(endpoint, data, options = {}) {
//     return this.request(endpoint, {
//       ...options,
//       method: 'POST',
//       body: JSON.stringify(data),
//     });
//   }

//   put(endpoint, data, options = {}) {
//     return this.request(endpoint, {
//       ...options,
//       method: 'PUT',
//       body: JSON.stringify(data),
//     });
//   }

//   delete(endpoint, options = {}) {
//     return this.request(endpoint, { ...options, method: 'DELETE' });
//   }
// }

// // Instance singleton
// export const apiClient = new ApiClient();

// // Méthodes d'aide pour les appels API spécifiques
// export const authAPI = {
//   login: (credentials) => apiClient.post('/auth/login', credentials),
//   logout: () => apiClient.post('/auth/logout'),
//   register: (userData) => apiClient.post('/auth/register', userData),
//   verifyToken: () => apiClient.get('/auth/verify'),
//   forgotPassword: (email) => apiClient.post('/auth/forgot-password', { email }),
//   resetPassword: (token, password) => apiClient.post('/auth/reset-password', { token, password }),
// };

// export const userAPI = {
//   getProfile: () => apiClient.get('/user/profile'),
//   updateProfile: (data) => apiClient.put('/user/profile', data),
//   getBookings: () => apiClient.get('/user/bookings'),
//   createBooking: (data) => apiClient.post('/user/bookings', data),
//   updatePreferences: (data) => apiClient.put('/user/preferences', data),
// };

// export const adminAPI = {
//   getUsers: (params = {}) => apiClient.get('/admin/users', { params }),
//   getUser: (id) => apiClient.get(`/admin/users/${id}`),
//   updateUser: (id, data) => apiClient.put(`/admin/users/${id}`, data),
//   deleteUser: (id) => apiClient.delete(`/admin/users/${id}`),
//   getStats: () => apiClient.get('/admin/stats'),
//   getBookings: (params = {}) => apiClient.get('/admin/bookings', { params }),
// };