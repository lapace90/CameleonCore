<template>
  <div class="users-page">
    <div class="page-header">
      <button id="add-user" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i>
        Nouvel utilisateur
      </button>
    </div>

    <div class="users-table-card">
      <div class="table-header">
        <div class="search-filter">
          <input type="text" placeholder="Rechercher un utilisateur..." class="search-input">
          <select class="filter-select">
            <option>Tous les rôles</option>
            <option>Admin</option>
            <option>Client</option>
          </select>
        </div>
      </div>


    </div>
    <div v-if="loading" class="table-placeholder">
      <i class="fas fa-spinner fa-spin table-icon"></i>
      <h3>Chargement des utilisateurs...</h3>
    </div>

    <div v-else-if="error" class="table-placeholder">
      <i class="fas fa-exclamation-triangle table-icon"></i>
      <h3>{{ error }}</h3>
    </div>

    <table v-else class="users-table">
      <thead>
        <tr>
          <th>Nom</th>
          <th>Email</th>
          <th>Rôle</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in users" :key="user.id">
          <td>{{ user.name }}</td>
          <td>{{ user.email }}</td>
          <td>{{ user.role?.name || '—' }}</td>
        </tr>
      </tbody>
    </table>
  </div>

</template>

<script>
import axios from 'axios';

export default {
  name: 'AdminUsers',
  data() {
    return {
      users: [],
      loading: true,
      error: null
    }
  },
  created() {
    this.fetchUsers()
  },
  methods: {
    async fetchUsers() {
      this.loading = true
      this.error = null

      try {
        const response = await axios.get('/api/admin/users')
        // API Platform may return an array or wrap it in hydra:member
        this.users = Array.isArray(response.data)
          ? response.data
          : response.data['hydra:member'] || []
      } catch (error) {
        console.error('Erreur lors du chargement des utilisateurs:', error)
        this.error = 'Impossible de charger les utilisateurs'
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
