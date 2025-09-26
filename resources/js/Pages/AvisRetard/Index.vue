<template>
    <div class="container-fluid py-4">
        <!-- En-tête avec statistiques -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h1 class="h2 text-primary mb-1">Gestion des Paiements de Loyer</h1>
                        <p class="text-muted mb-0">Rappels et avis de retard automatisés</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button
                            @click="envoyerRappels"
                            class="btn btn-outline-primary"
                            :disabled="loading"
                        >
                            <i class="fas fa-bell me-2"></i>Envoyer Rappels
                        </button>
                        <button
                            @click="envoyerAvisRetards"
                            class="btn btn-outline-warning"
                            :disabled="loading"
                        >
                            <i class="fas fa-exclamation-triangle me-2"></i>Traiter Retards
                        </button>
                        <button
                            @click="traiterTout"
                            class="btn btn-primary"
                            :disabled="loading"
                        >
                            <i class="fas fa-sync me-2" :class="{'fa-spin': loading}"></i>Tout Traiter
                        </button>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ statistiques.total_avis }}</h3>
                                <small>Total des avis</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ statistiques.rappels_envoyes }}</h3>
                                <small>Rappels envoyés</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ statistiques.avis_retard }}</h3>
                                <small>Avis de retard</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ statistiques.payes }}</h3>
                                <small>Payés</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Type</label>
                                <select v-model="filtres.type" class="form-select">
                                    <option value="">Tous</option>
                                    <option value="rappel">Rappels</option>
                                    <option value="retard">Avis de retard</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Statut</label>
                                <select v-model="filtres.statut" class="form-select">
                                    <option value="">Tous</option>
                                    <option value="envoye">Envoyé</option>
                                    <option value="paye">Payé</option>
                                    <option value="annule">Annulé</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date d'échéance</label>
                                <input v-model="filtres.dateEcheance" type="date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button @click="resetFiltres" class="btn btn-outline-secondary d-block">
                                    <i class="fas fa-times me-2"></i>Réinitialiser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages d'alerte -->
        <div v-if="message.text" class="alert" :class="message.type" role="alert">
            <i class="fas" :class="message.icon"></i>
            {{ message.text }}
        </div>

        <!-- Liste des avis -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Avis de Paiement ({{ avisFiltres.length }} résultat{{ avisFiltres.length > 1 ? 's' : '' }})</h5>
                    </div>
                    <div class="card-body p-0">
                        <!-- Message si aucun avis -->
                        <div v-if="avisFiltres.length === 0" class="text-center py-5">
                            <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3 mb-0">Aucun avis trouvé</p>
                        </div>

                        <!-- Table des avis -->
                        <div v-else class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Client</th>
                                    <th>Bien</th>
                                    <th>Échéance</th>
                                    <th>Montant</th>
                                    <th>Retard</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="avis in avisFiltres" :key="avis.id">
                                    <td>
                                            <span class="badge" :class="getTypeBadgeClass(avis.type)">
                                                <i class="fas" :class="getTypeIcon(avis.type)"></i>
                                                {{ getTypeLabel(avis.type) }}
                                            </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ avis.location?.client?.name || 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ avis.location?.client?.email || '' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ avis.location?.bien?.title || 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ avis.location?.bien?.city || '' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ formatDate(avis.date_echeance) }}</strong>
                                        <br>
                                        <small class="text-muted">{{ getDelaiText(avis.date_echeance) }}</small>
                                    </td>
                                    <td>
                                        <strong class="text-success">{{ formatPrice(avis.montant_du) }} FCFA</strong>
                                        <div v-if="avis.type === 'retard' && getPenalites(avis) > 0" class="text-danger small">
                                            +{{ formatPrice(getPenalites(avis)) }} FCFA pénalités
                                        </div>
                                    </td>
                                    <td>
                                            <span v-if="avis.jours_retard > 0" class="badge bg-danger">
                                                {{ avis.jours_retard }} jour{{ avis.jours_retard > 1 ? 's' : '' }}
                                            </span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td>
                                            <span class="badge" :class="getStatutBadgeClass(avis.statut)">
                                                {{ getStatutLabel(avis.statut) }}
                                            </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button
                                                @click="voirDetails(avis)"
                                                class="btn btn-outline-primary"
                                                title="Voir détails"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button
                                                v-if="avis.statut === 'envoye'"
                                                @click="marquerPaye(avis)"
                                                class="btn btn-outline-success"
                                                title="Marquer comme payé"
                                            >
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button
                                                @click="renvoyerNotification(avis)"
                                                class="btn btn-outline-warning"
                                                title="Renvoyer la notification"
                                            >
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="avisRetards.links" class="d-flex justify-content-center mt-4">
            <nav>
                <ul class="pagination">
                    <li
                        v-for="(link, index) in avisRetards.links"
                        :key="index"
                        class="page-item"
                        :class="{ 'active': link.active, 'disabled': !link.url }"
                    >
                        <component
                            :is="link.url ? 'Link' : 'span'"
                            :href="link.url"
                            class="page-link"
                            v-html="link.label"
                        />
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</template>

<script>
import Layout from '@/Pages/Layout.vue'
export default { layout: Layout }
</script>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    avisRetards: { type: Object, required: true },
    statistiques: { type: Object, required: true }
})

// État réactif
const loading = ref(false)
const message = ref({ text: '', type: '', icon: '' })
const filtres = ref({
    type: '',
    statut: '',
    dateEcheance: ''
})

// Computed
const avisFiltres = computed(() => {
    let avis = props.avisRetards.data || []

    if (filtres.value.type) {
        avis = avis.filter(a => a.type === filtres.value.type)
    }

    if (filtres.value.statut) {
        avis = avis.filter(a => a.statut === filtres.value.statut)
    }

    if (filtres.value.dateEcheance) {
        avis = avis.filter(a => a.date_echeance === filtres.value.dateEcheance)
    }

    return avis
})

// Méthodes
const showMessage = (text, type = 'alert-info', icon = 'fa-info-circle') => {
    message.value = { text, type, icon }
    setTimeout(() => {
        message.value = { text: '', type: '', icon: '' }
    }, 5000)
}

const envoyerRappels = async () => {
    loading.value = true
    try {
        const response = await fetch('/avis-retard/rappels', { method: 'POST' })
        const data = await response.json()

        if (data.success) {
            showMessage(data.message, 'alert-success', 'fa-check-circle')
        } else {
            showMessage('Erreur lors de l\'envoi des rappels', 'alert-danger', 'fa-exclamation-circle')
        }
    } catch (error) {
        showMessage('Erreur de connexion', 'alert-danger', 'fa-exclamation-circle')
    } finally {
        loading.value = false
    }
}

const envoyerAvisRetards = async () => {
    loading.value = true
    try {
        const response = await fetch('/avis-retard/retards', { method: 'POST' })
        const data = await response.json()

        if (data.success) {
            showMessage(data.message, 'alert-warning', 'fa-exclamation-triangle')
        } else {
            showMessage('Erreur lors de l\'envoi des avis', 'alert-danger', 'fa-exclamation-circle')
        }
    } catch (error) {
        showMessage('Erreur de connexion', 'alert-danger', 'fa-exclamation-circle')
    } finally {
        loading.value = false
    }
}

const traiterTout = async () => {
    loading.value = true
    try {
        const response = await fetch('/avis-retard/traiter-automatique', { method: 'POST' })
        const data = await response.json()

        if (data.success) {
            showMessage(`Traitement terminé: ${data.rappels} rappels et ${data.avis} avis envoyés`, 'alert-success', 'fa-check-circle')
        } else {
            showMessage('Erreur lors du traitement', 'alert-danger', 'fa-exclamation-circle')
        }
    } catch (error) {
        showMessage('Erreur de connexion', 'alert-danger', 'fa-exclamation-circle')
    } finally {
        loading.value = false
    }
}

const marquerPaye = async (avis) => {
    if (confirm('Marquer cet avis comme payé ?')) {
        try {
            const response = await fetch(`/avis-retard/${avis.id}/paye`, { method: 'POST' })
            const data = await response.json()

            if (data.success) {
                avis.statut = 'paye'
                avis.date_paiement = new Date().toISOString()
                showMessage('Avis marqué comme payé', 'alert-success', 'fa-check-circle')
            }
        } catch (error) {
            showMessage('Erreur lors de la mise à jour', 'alert-danger', 'fa-exclamation-circle')
        }
    }
}

const voirDetails = (avis) => {
    router.visit(`/avis-retard/${avis.id}`)
}

const renvoyerNotification = async (avis) => {
    if (confirm('Renvoyer la notification ?')) {
        showMessage('Fonctionnalité en cours de développement', 'alert-info', 'fa-info-circle')
    }
}

const resetFiltres = () => {
    filtres.value = {
        type: '',
        statut: '',
        dateEcheance: ''
    }
}

// Méthodes utilitaires
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR')
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price || 0)
}

const getDelaiText = (dateEcheance) => {
    const date = new Date(dateEcheance)
    const aujourd = new Date()
    const diffDays = Math.ceil((date - aujourd) / (1000 * 60 * 60 * 24))

    if (diffDays > 0) return `Dans ${diffDays} jour${diffDays > 1 ? 's' : ''}`
    if (diffDays < 0) return `Il y a ${Math.abs(diffDays)} jour${Math.abs(diffDays) > 1 ? 's' : ''}`
    return 'Aujourd\'hui'
}

const getPenalites = (avis) => {
    if (avis.type === 'retard' && avis.jours_retard > 0) {
        const semaines = Math.ceil(avis.jours_retard / 7)
        return avis.montant_du * 0.02 * semaines
    }
    return 0
}

const getTypeBadgeClass = (type) => {
    return type === 'rappel' ? 'bg-primary' : 'bg-warning text-dark'
}

const getTypeIcon = (type) => {
    return type === 'rappel' ? 'fa-bell' : 'fa-exclamation-triangle'
}

const getTypeLabel = (type) => {
    return type === 'rappel' ? 'Rappel' : 'Retard'
}

const getStatutBadgeClass = (statut) => {
    const classes = {
        'envoye': 'bg-info',
        'paye': 'bg-success',
        'annule': 'bg-secondary'
    }
    return classes[statut] || 'bg-secondary'
}

const getStatutLabel = (statut) => {
    const labels = {
        'envoye': 'Envoyé',
        'paye': 'Payé',
        'annule': 'Annulé'
    }
    return labels[statut] || statut
}
</script>

<style scoped>
.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75rem;
}

.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
}

.alert {
    border: none;
    border-radius: 8px;
}

.card {
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.table-responsive {
    border-radius: 0.375rem;
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }

    .btn-group .btn {
        border-radius: 0.375rem !important;
        margin-bottom: 0.25rem;
    }
}
</style>
