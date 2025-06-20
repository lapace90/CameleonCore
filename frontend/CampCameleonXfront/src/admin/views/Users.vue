<template>
  <div class="users-page">
    <div class="page-header">
      <h2>Gestion des utilisateurs</h2>
      <button class="btn btn-primary">
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
      
      <div class="table-placeholder">
        <i class="fas fa-users table-icon"></i>
        <h3>Table des utilisateurs</h3>
        <p>Ici sera affichée la liste des utilisateurs avec leurs informations</p>
        <ul class="feature-list">
          <li>Tri par colonne</li>
          <li>Filtres avancés</li>
          <li>Actions (éditer, supprimer, bloquer)</li>
          <li>Export des données</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminUsers'
}
</script>

