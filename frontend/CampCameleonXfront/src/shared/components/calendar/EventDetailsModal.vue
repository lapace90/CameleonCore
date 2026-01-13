<template>
    <BaseModal :model-value="open" @update:modelValue="$emit('update:modelValue', $event)" :title="headerTitle"
        size="md">
        <div class="detail-grid">
            <div class="row">
                <span class="label">Type</span>
                <span class="value">{{ isReservation ? 'Réservation' : (p.type || 'Événement') }}</span>
            </div>

            <div class="row">
                <span class="label">Période</span>
                <span class="value">{{ startText }} → {{ endText }}</span>
            </div>

            <template v-if="isReservation">
                <div class="row">
                    <span class="label">Client</span>
                    <span class="value">
                        {{ p.customer_name || '—' }}
                        <template v-if="p.customer_email"> — {{ p.customer_email }}</template>
                        <template v-if="p.customer_phone"> — {{ p.customer_phone }}</template>
                    </span>
                </div>

                <div class="row">
                    <span class="label">Invités</span>
                    <span class="value">{{ guestsText }}</span>
                </div>

                <div class="row">
                    <span class="label">Montant</span>
                    <span class="value">{{ amountText }}</span>
                </div>

                <div class="row">
                    <span class="label">Statut</span>
                    <span class="value">{{ p.status || 'pending' }}</span>
                </div>

                <div class="row" v-if="p.comment">
                    <span class="label">Commentaire</span>
                    <span class="value">{{ p.comment }}</span>
                </div>
            </template>

            <template v-else>
                <div class="row" v-if="p.location">
                    <span class="label">Lieu</span>
                    <span class="value">{{ p.location }}</span>
                </div>
                <div class="row" v-if="p.responsible">
                    <span class="label">Responsable</span>
                    <span class="value">{{ p.responsible }}</span>
                </div>
                <div class="row" v-if="p.notes">
                    <span class="label">Notes</span>
                    <span class="value">{{ p.notes }}</span>
                </div>
            </template>

        </div>

        <template #footer>
            <!-- Pas de v-permission, juste un v-if simple -->
            <button v-if="isReservation && p.status === 'confirmed'" class="btn btn-sm btn-success"
                @click="handleCheckIn">
                <i class="fas fa-door-open"></i> Check-in
            </button>

            <button v-if="isReservation && p.status === 'checked_in'" class="btn btn-sm btn-info"
                @click="handleCheckOut">
                <i class="fas fa-door-closed"></i> Check-out
            </button>

            <button class="btn btn-sm" @click="$emit('update:modelValue', false)">Fermer</button>
            <button v-if="isReservation && reservationId" class="btn btn-sm btn-primary" @click="goToReservation">
                Voir la réservation
            </button>
        </template>

    </BaseModal>
</template>

<script>
import BaseModal from '../ui/BaseModal.vue'
import AdminApi from '@/services/AdminApi'

export default {
    name: 'EventDetailsModal',
    components: { BaseModal },
    emits: ['update:modelValue', 'close', 'refresh'],
    props: {
        modelValue: { type: Boolean, default: false },
        // on passe l'objet brut { title, id, start, end, props, ... }
        event: { type: Object, default: () => ({}) }
    },
    computed: {
        open() { return !!this.modelValue },
        headerTitle() { return (this.event && this.event.title) || 'Détails' },
        p() { return (this.event && this.event.props) || {} },
        isReservation() { return this.p.source === 'reservation' },

        startText() {
            const d = this.event && this.event.start ? new Date(this.event.start) : null
            return d ? d.toLocaleString() : '—'
        },
        endText() {
            const d = this.event && this.event.end ? new Date(this.event.end) : null
            return d ? d.toLocaleString() : '—'
        },
        guestsText() {
            const a = Number(this.p.number_of_adults || 0)
            const c = Number(this.p.number_of_children || 0)
            const g = this.p.guests != null ? Number(this.p.guests) : (a + c)
            return g > 0 ? String(g) : '—'
        },
        amountText() {
            const raw = this.p.amount
            if (raw == null || raw === '') return '—'
            const n = Number(raw)
            return Number.isFinite(n) ? n.toFixed(2) + ' €' : String(raw) + ' €'
        },
        reservationId() {
            const ext = this.p || {}
            if (ext.reservation_id) return String(ext.reservation_id)

            const raw = this.event?.id
            if (typeof raw === 'string' && raw.startsWith('reservation_')) {
                return raw.split('_').pop()
            }
            return raw ? String(raw) : null
        },
        reservationRoute() {
            if (this.$router && this.$router.hasRoute && this.$router.hasRoute('admin.reservations.edit')) {
                return { name: 'admin.reservations.edit', params: { id: this.reservationId } }
            }
            return `/admin/reservations/${this.reservationId}`

        },
    },
    methods: {
        goToReservation() {
            if (!this.reservationId) return
            this.$router.push(this.reservationRoute)
            this.$emit('update:modelValue', false) // fermer la modal
        },
        async handleCheckIn() {
            if (!confirm('Confirmer l\'arrivée du client ?')) return
            try {
                await AdminApi.doReservationCheckIn(this.reservationId)
                this.$emit('refresh')
                this.$emit('update:modelValue', false)
            } catch (error) {
                alert('<i class="fas fa-times-circle" style="padding: .5rem;"></i> ' + (error.response?.data?.message || 'Erreur'))
            }
        },
        
        async handleCheckOut() {
            if (!confirm('Confirmer le départ du client ?')) return
            try {
                await AdminApi.doReservationCheckOut(this.reservationId)
                this.$emit('refresh')
                this.$emit('update:modelValue', false)
            } catch (error) {
                alert('<i class="fas fa-times-circle" style="padding: .5rem;"></i> ' + (error.response?.data?.message || 'Erreur'))
            }
        }
    }
}
</script>

<style scoped>
.detail-grid {
    display: grid;
    gap: 0.75rem;
}

.row {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 1rem;
    align-items: start;
}

.label {
    color: var(--text-secondary);
    font-weight: 600;
}

.value {
    color: var(--text-primary);
    word-break: break-word;
}
</style>
