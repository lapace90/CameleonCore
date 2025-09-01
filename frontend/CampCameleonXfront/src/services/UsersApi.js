// import axios from 'axios'

// class UsersApi {
//   static defaultHeaders = {
//     Accept: 'application/json',
//     'Content-Type': 'application/json'
//   }

//   static async getAll() {
//     try {
//       await axios.get('/sanctum/csrf-cookie')
//       const response = await axios.get('/api/admin/users', {
//         headers: this.defaultHeaders,
//         withCredentials: true
//       })
//       return Array.isArray(response.data)
//         ? response.data
//         : response.data['hydra:member'] || []
//     } catch (error) {
//       console.error('Erreur lors du chargement des utilisateurs:', error)
//       throw error
//     }
//   }
// }

// export default UsersApi