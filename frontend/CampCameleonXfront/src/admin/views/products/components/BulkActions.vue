<template>
  <div class="bulk-actions">
    <div class="bulk-info">
      <i class="fas fa-check-square"></i>
      <span>{{ selectedCount }} produit(s) sélectionné(s)</span>
    </div>
    <div class="bulk-buttons">
      <button @click="$emit('bulk-action', 'activate')" class="btn btn-success btn-sm">
        <i class="fas fa-check"></i>
        Activer
      </button>
      <button @click="$emit('bulk-action', 'deactivate')" class="btn btn-warning btn-sm">
        <i class="fas fa-pause"></i>
        Désactiver
      </button>
      <button @click="$emit('bulk-action', 'draft')" class="btn btn-secondary btn-sm">
        <i class="fas fa-edit"></i>
        Brouillon
      </button>
      <button @click="$emit('bulk-action', 'delete')" class="btn btn-danger btn-sm">
        <i class="fas fa-trash"></i>
        Supprimer
      </button>
      <button @click="$emit('clear-selection')" class="btn btn-outline btn-sm">
        <i class="fas fa-times"></i>
        Annuler
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BulkActions',
  props: {
    selectedCount: { type: Number, required: true }
  }
}
</script>

<style scoped>
.bulk-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  margin-bottom: 20px;
}

.bulk-info {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
  color: #1e40af;
}

.bulk-buttons {
  display: flex;
  gap: 8px;
}



</style>