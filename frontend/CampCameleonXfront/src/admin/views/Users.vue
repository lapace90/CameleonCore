<template>
  <div class="users-page">
    <div class="page-header">
      <button id="add-user" class="btn btn-primary">
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

<style scoped>
.users-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: block;
  margin-bottom: 1rem;
}

.users-table-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
  border: 1px solid #e9ecef;
}

.table-header {
  padding: 1.5rem;
  border-bottom: 1px solid #f8f9fe;
}

.search-filter {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.search-input, .filter-select {
  padding: 0.5rem 1rem;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  background-color: #f8f9fe;
}

.search-input {
  flex: 1;
  max-width: 300px;
}

.table-placeholder {
  padding: 4rem 2rem;
  text-align: center;
  color: #8898aa;
}

.table-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.3;
}

.table-placeholder h3 {
  color: #32325d;
  margin-bottom: 1rem;
}

.feature-list {
  list-style: none;
  padding: 0;
  margin: 2rem 0 0 0;
  display: inline-block;
  text-align: left;
}

.feature-list li {
  padding: 0.25rem 0;
  color: #5e72e4;
}

.feature-list li::before {
  content: '✓ ';
  margin-right: 0.5rem;
}
</style>
