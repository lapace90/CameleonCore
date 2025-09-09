<template>
    <div class="permissions-page">
        <!-- Message de succès -->
        <div v-if="successMessage" class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            {{ successMessage }}
            <button @click="successMessage = null" class="btn-close">&times;</button>
        </div>

        <!-- En-tête -->
        <div class="page-header">
            <div class="header-content">
                <h1 class="page-title">
                    <i class="fas fa-key"></i>
                    Permissions
                </h1>
                <p class="page-subtitle">
                    Vue d'ensemble des permissions système
                </p>
                <button @click="showCreateModal = true" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nouvelle permission
                </button>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid" v-if="permissionsData.stats">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ permissionsData.stats.total_permissions }}</div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2dce89 0%, #2dcecc 100%);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ permissionsData.stats.used_permissions }}</div>
                        <div class="stat-label">Utilisées</div>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fb6340 0%, #fbb140 100%);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ permissionsData.stats.critical_permissions }}</div>
                        <div class="stat-label">Critiques</div>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #5e72e4 0%, #825ee4 100%);">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ permissionsData.stats.usage_percentage }}%</div>
                        <div class="stat-label">Taux d'usage</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="text-center" style="padding: 3rem;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p style="margin-top: 1rem;">Chargement des permissions...</p>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="text-center" style="padding: 3rem; color: #f5365c;">
            <i class="fas fa-exclamation-triangle fa-2x"></i>
            <p style="margin: 1rem 0;">{{ error }}</p>
            <button @click="loadPermissions" class="btn btn-primary btn-sm">
                Réessayer
            </button>
        </div>

        <!-- Catégories de permissions -->
        <div v-else class="permissions-categories" style="margin-top: 2rem;">
            <div v-for="category in permissionsData.categories" :key="category.key" class="category-type-section">
                <!-- En-tête de catégorie -->
                <div class="type-header" @click="toggleCategory(category.key)" style="cursor: pointer;">
                    <div class="type-info">
                        <h3>
                            <i :class="category.icon" :style="`color: ${getCategoryColor(category.color)}`"></i>
                            {{ category.name }}
                            <!-- Icône accordéon -->
                            <i class="fas fa-chevron-down ml-2"
                                :class="{ 'fa-chevron-up': isCategoryExpanded(category.key), 'fa-chevron-down': !isCategoryExpanded(category.key) }"
                                style="font-size: 0.8rem; color: #8898aa;"></i>
                        </h3>
                        <p class="text-muted">{{ category.description }}</p>
                    </div>
                    <div class="count">
                        {{ category.permissions.length }} permission(s)
                    </div>
                </div>

                <!-- Grille des permissions -->
                <div v-show="isCategoryExpanded(category.key)" class="categories-grid"
                    style="transition: all 0.3s ease;">
                    <div v-for="permission in category.permissions" :key="permission.id" class="category-card">
                        <div class="category-content">
                            <!-- Header -->
                            <div class="category-header">
                                <div>
                                    <span :class="getBadgeClass(permission.badge_class)">
                                        {{ permission.action_label }}
                                    </span>
                                </div>

                                <!-- Indicateurs -->
                                <div class="category-actions">
                                    <span v-if="permission.is_critical" class="btn-icon"
                                        style="background: #f5365c; color: white;" title="Permission critique">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </span>
                                    <span v-if="permission.is_system" class="btn-icon"
                                        style="background: #fb6340; color: white;" title="Permission système">
                                        <i class="fas fa-shield-alt"></i>
                                    </span>
                                    <span v-if="permission.requires_confirmation" class="btn-icon"
                                        style="background: #5e72e4; color: white;" title="Confirmation requise">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                </div>
                            </div>

                            <!-- Contenu -->
                            <div>
                                <h4 style="margin: 0.75rem 0 0.5rem 0; font-size: 1rem; font-weight: 500;">
                                    {{ permission.name }}
                                </h4>
                                <code
                                    style="display: block; font-size: 0.75rem; color: #8898aa; background: #f8f9fe; padding: 0.25rem 0.5rem; border-radius: 4px; margin-bottom: 1rem;">
                  {{ permission.action }}
                </code>

                            </div>

                            <!-- Stats -->
                            <div class="category-stats">
                                <div class="stat-item">
                                    <i class="fas fa-users"></i>
                                    <span>{{ permission.users_count }} utilisateur(s)</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>{{ permission.roles_count }} rôle(s)</span>
                                </div>
                            </div>

                            <!-- Indicateur d'usage -->
                            <div style="margin-top: 1rem;">
                                <div :class="getUsageClass(permission.usage_status)"
                                    style="padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; text-align: center;">
                                    {{ getUsageLabel(permission.usage_status) }}
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="text-center" style="margin-top: 1rem;">

                                <button @click="viewPermissionDetails(permission)" class="btn btn-info btn-sm mx-1 ">
                                    <i class="fas fa-eye"></i>

                                </button>
                                <button @click="editPermission(permission)" class="btn btn-sm btn-primary mx-1">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="deletePermission(permission)" class="btn btn-sm btn-danger mx-1"
                                    :disabled="permission.roles_count > 0">
                                    <i class="fas fa-trash"></i>
                                </button>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <PermissionCreateModal v-if="showCreateModal" @close="showCreateModal = false" @created="handlePermissionCreated" />
    <PermissionEditModal v-if="showEditModal && selectedPermission" :permission="selectedPermission"
        @close="closeEditModal" @updated="handlePermissionUpdated" />
    <PermissionDeleteModal v-if="showDeleteModal && selectedPermission" :permission="selectedPermission"
        @close="closeDeleteModal" @deleted="handlePermissionDeleted" />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PermissionsApi from '@/services/PermissionsApi'
import PermissionCreateModal from '@/admin/components/modals/PermissionCreateModal.vue'
import PermissionEditModal from '@/admin/components/modals/PermissionEditModal.vue'
import PermissionDeleteModal from '@/admin/components/modals/PermissionDeleteModal.vue'

const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

const handlePermissionCreated = (newPermission) => {
    successMessage.value = `Permission "${newPermission.name}" créée`
    loadPermissions()
}

const editPermission = (permission) => {
    selectedPermission.value = permission
    showEditModal.value = true
}

const closeEditModal = () => {
    showEditModal.value = false
    selectedPermission.value = null
}

const handlePermissionUpdated = (updatedPermission) => {
    successMessage.value = `Permission "${updatedPermission.name}" modifiée`
    closeEditModal()
    loadPermissions()
}

const deletePermission = (permission) => {
    selectedPermission.value = permission
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    selectedPermission.value = null
}

const handlePermissionDeleted = (deletedPermission) => {
    successMessage.value = `Permission "${deletedPermission.name}" supprimée`
    closeDeleteModal()
    loadPermissions()
}


const expandedCategories = ref({})

// Initialiser toutes les catégories comme fermées après chargement
function initializeCategories() {
    permissionsData.value.categories.forEach(category => {
        if (expandedCategories.value[category.key] === undefined) {
            expandedCategories.value[category.key] = false // fermé par défaut
        }
    })
}

const toggleCategory = (categoryKey) => {
    expandedCategories.value[categoryKey] = !expandedCategories.value[categoryKey]
}

const isCategoryExpanded = (categoryKey) => {
    return expandedCategories.value[categoryKey] === true
}

const permissionsData = ref({ categories: [], stats: {} })
const loading = ref(false)
const error = ref(null)
const successMessage = ref(null)
const showDetailsModal = ref(false)
const selectedPermission = ref(null)

async function loadPermissions() {
    loading.value = true
    error.value = null

    try {
        initializeCategories()
        const data = await PermissionsApi.getGrouped()
        permissionsData.value = data
    } catch (err) {
        console.error('Erreur lors du chargement des permissions:', err)
        error.value = 'Impossible de charger les permissions'
    } finally {
        loading.value = false
    }
}

function viewPermissionDetails(permission) {
    selectedPermission.value = permission
    showDetailsModal.value = true
}

function closeDetailsModal() {
    showDetailsModal.value = false
    selectedPermission.value = null
}

// Helpers 
function getUsageLabel(status) {
    const labels = {
        'unused': 'Non utilisée',
        'limited': 'Usage limité',
        'moderate': 'Usage modéré',
        'widespread': 'Très utilisée'
    }
    return labels[status] || status
}

function getUsageClass(status) {
    const classes = {
        'unused': 'status-inactive',
        'limited': 'status-draft',
        'moderate': 'status-active',
        'widespread': 'status-active'
    }
    return classes[status] || 'status-inactive'
}

function getBadgeClass(badgeClass) {
    // Mapping vers les classes existantes
    const mapping = {
        'badge-danger': 'status-inactive',
        'badge-success': 'status-active',
        'badge-warning': 'status-draft',
        'badge-info': 'category-badge',
        'badge-secondary': 'category-badge'
    }
    return mapping[badgeClass] || 'category-badge'
}

function getCategoryColor(color) {
    const colors = {
        'red': '#f5365c',
        'blue': '#5e72e4',
        'purple': '#8965e0',
        'green': '#2dce89',
        'orange': '#fb6340',
        'teal': '#11cdef',
        'indigo': '#5603ad',
        'yellow': '#ffd600',
        'emerald': '#2dce89',
        'pink': '#f56565',
        'cyan': '#2196f3',
        'gray': '#8898aa'
    }
    return colors[color] || '#8898aa'
}

// Chargement initial
onMounted(loadPermissions)
</script>