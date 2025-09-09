<template>
    <!-- Modal Détails -->
    <div class="modal-overlay" @click.self="$emit('close')">
        <div class="modal-content" @click.stop>
            <div class="modal-header">
                <h3>
                    <i :class="`fas ${role.icon}`" :style="{ color: role.color }"></i>
                    {{ role.name }}
                </h3>
                <button @click="$emit('close')" class="btn-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="role-details-content">
                    <!-- Informations générales -->
                    <div class="detail-section">
                        <h4>Informations générales</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="label">Nom :</span>
                                <span class="value">{{ role.name }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Slug :</span>
                                <span class="value">{{ role.slug }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Description :</span>
                                <span class="value">{{ role.description || 'Aucune description' }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Statut :</span>
                                <span class="value">
                                    <span v-if="role.is_critical" class="badge badge-critical">
                                        <i class="fas fa-shield-alt"></i> Critique
                                    </span>
                                    <span v-else class="badge badge-normal">Normal</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="detail-section">
                        <h4>Statistiques</h4>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <i class="fas fa-key"></i>
                                <div>
                                    <span class="stat-number">{{ role.permissions_count }}</span>
                                    <span class="stat-label">Permissions</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-users"></i>
                                <div>
                                    <span class="stat-number">{{ role.total_users_count }}</span>
                                    <span class="stat-label">Utilisateurs</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-user-shield"></i>
                                <div>
                                    <span class="stat-number">{{ role.primary_users_count }}</span>
                                    <span class="stat-label">Rôle principal</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-user-plus"></i>
                                <div>
                                    <span class="stat-number">{{ role.additional_users_count }}</span>
                                    <span class="stat-label">Rôle additionnel</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div v-if="role.permissions?.length > 0" class="detail-section">
                        <h4>Permissions ({{ role.permissions.length }})</h4>
                        <div class="permissions-list">
                            <span v-for="permission in role.permissions" :key="permission.id" class="permission-badge"
                                :class="`permission-${permission.category}`">
                                {{ permission.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Utilisateurs -->
                    <div v-if="role.total_users_count > 0" class="detail-section">
                        <h4>Utilisateurs assignés</h4>

                        <div v-if="role.primary_users?.length > 0" class="users-subsection">
                            <h5>Rôle principal ({{ role.primary_users.length }})</h5>
                            <div class="users-list">
                                <div v-for="user in role.primary_users" :key="`primary-${user.id}`" class="user-item">
                                    <i class="fas fa-user-shield"></i>
                                    <div class="user-info">
                                        <span class="user-name">{{ user.name }}</span>
                                        <span class="user-email">{{ user.email }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="role.users?.length > 0" class="users-subsection">
                            <h5>Rôle additionnel ({{ role.users.length }})</h5>
                            <div class="users-list">
                                <div v-for="user in role.users" :key="`additional-${user.id}`" class="user-item">
                                    <i class="fas fa-user-plus"></i>
                                    <div class="user-info">
                                        <span class="user-name">{{ user.name }}</span>
                                        <span class="user-email">{{ user.email }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RoleDetailsModal',

    props: {
        role: {
            type: Object,
            required: true
        }
    },

    emits: ['close', 'edit'],

    computed: {
        // Grouper les permissions par catégorie pour l'affichage
        groupedPermissions() {
            if (!this.role.permissions || this.role.permissions.length === 0) {
                return []
            }

            const grouped = {}

            this.role.permissions.forEach(permission => {
                const category = permission.category || 'other'
                if (!grouped[category]) {
                    grouped[category] = {
                        key: category,
                        name: this.getCategoryDisplayName(category),
                        icon: this.getCategoryIcon(category),
                        color: this.getCategoryColor(category),
                        permissions: []
                    }
                }
                grouped[category].permissions.push(permission)
            })

            // Trier par nom de catégorie
            return Object.values(grouped).sort((a, b) => a.name.localeCompare(b.name))
        }
    },

    methods: {
        editRole() {
            this.$emit('edit', this.role)
            this.$emit('close')
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A'

            try {
                const date = new Date(dateString)
                return date.toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                })
            } catch (error) {
                return dateString
            }
        },

        getCategoryDisplayName(category) {
            const names = {
                'system': 'Système',
                'users': 'Utilisateurs',
                'accommodations': 'Hébergements',
                'activities': 'Activités',
                'bookings': 'Réservations',
                'reception': 'Réception',
                'customers': 'Clients',
                'restaurant': 'Restaurant',
                'finance': 'Finance',
                'analytics': 'Analyses',
                'communication': 'Communication',
                'other': 'Autres'
            }
            return names[category] || category
        },

        getCategoryIcon(category) {
            const icons = {
                'system': 'fa-cogs',
                'users': 'fa-users',
                'accommodations': 'fa-home',
                'activities': 'fa-hiking',
                'bookings': 'fa-calendar-alt',
                'reception': 'fa-concierge-bell',
                'customers': 'fa-address-book',
                'restaurant': 'fa-utensils',
                'finance': 'fa-coins',
                'analytics': 'fa-chart-bar',
                'communication': 'fa-comments',
                'other': 'fa-ellipsis-h'
            }
            return icons[category] || 'fa-key'
        },

        getCategoryColor(category) {
            const colors = {
                'system': '#dc2626',
                'users': '#7c3aed',
                'accommodations': '#059669',
                'activities': '#ea580c',
                'bookings': '#0891b2',
                'reception': '#be123c',
                'customers': '#4338ca',
                'restaurant': '#ca8a04',
                'finance': '#16a34a',
                'analytics': '#0f766e',
                'communication': '#9333ea',
                'other': '#6b7280'
            }
            return colors[category] || '#6b7280'
        }
    }
}
</script>