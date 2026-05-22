<template>
  <div class="bulk-actions">
    <div class="bulk-info">
      <AppIcon name="square-check" />
      <span>{{ selectedCount }} produit(s) sélectionné(s)</span>
    </div>
    <div class="bulk-buttons">
      <button @click="$emit('bulk-action', 'activate')" class="btn btn-success btn-sm">
        <AppIcon name="check" />
        Activer
      </button>
      <button @click="$emit('bulk-action', 'deactivate')" class="btn btn-warning btn-sm">
        <AppIcon name="pause" />
        Désactiver
      </button>
      <button @click="$emit('bulk-action', 'draft')" class="btn btn-secondary btn-sm">
        <AppIcon name="pencil" />
        Brouillon
      </button>
      <button @click="$emit('bulk-action', 'delete')" class="btn btn-danger btn-sm">
        <AppIcon name="trash-2" />
        Supprimer
      </button>
      <button @click="$emit('clear-selection')" class="btn btn-outline btn-sm">
        <AppIcon name="x" />
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