<template>
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <!-- En-tête -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 text-primary mb-1">
                            {{ avisRetard.type === 'rappel' ? 'Rappel de Paiement' : 'Avis de Retard' }}
                            #{{ avisRetard.id }}
                        </h1>
                        <p class="text-muted mb-0">
                            {{ avisRetard.type === 'rappel' ? 'Notification préventive' : 'Paiement en retard' }}
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <Link
                            :href="route('avis-retard.index')"
                            class="btn btn-outline-secondary"
                        >
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </Link>
                        <button
                            v-if="avisRetard.statut === 'envoye'"
                            @click="marquerPaye"
                            class="btn btn-success"
                        >
                            <i class="fas fa-check me-2"></i>Marquer payé
                        </button>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Informations principales -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Détails de l'avis
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- Type et statut -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Type</label>
                                        <div>
                                            <span class="badge" :class="getTypeBadgeClass(avisRetard.type)">
                                                <i class="fas" :class="getTypeIcon(avisRetard.type)"></i>
                                                {{ getTypeLabel(avisRetard.type) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Statut</label>
                                        <div>
                                            <span class="badge" :class="getStatutBadgeClass(avisRetard.statut)">
                                                {{ getStatutLabel(avisRetard.statut) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Dates -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Date d'échéance</label>
                                        <div class="fs-6">{{ formatDate(avisRetard.date_echeance) }}</div>
                                        <small class="text-muted">{{ getDelaiText(avisRetard.date_echeance) }}</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Date d'envoi</label>
                                        <div class="fs-6">{{ formatDateTime(avisRetard.date_envoi) }}</div>
                                    </div>

                                    <!-- Paiement -->
                                    <div class="col-md-6" v-if="avisRetard.date_paiement">
                                        <label class="form-label fw-bold">Date de paiement</label>
                                        <div class="fs-6 text-success">{{ formatDateTime(avisRetard.date_paiement) }}</div>
                                    </div>

                                    <!-- Montants -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Montant dû</label>
                                        <div class="fs-5 fw-bold text-success">{{ formatPrice(avisRetard.montant_du) }} FCFA</div>
                                    </div>

                                    <!-- Retard et pénalités si applicable -->
                                    <div v-if="avisRetard.type === 'retard'" class="col-12">
                                        <hr class="my-3">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Jours de retard</label>
                                                <div class="fs-4 fw-bold text-danger">{{ avisRetard.jours_retard }}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Pénalités</label>
                                                <div class="fs-5 fw-bold text-warning">{{ formatPrice(penalites) }} FCFA</div>
                                                <small class="text-muted">2% par semaine</small>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Total à payer</label>
                                                <div class="fs-4 fw-bold text-danger">{{ formatPrice(montantTotal) }} FCFA</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Commentaires -->
                                    <div v-if="avisRetard.commentaires" class="col-12">
                                        <label class="form-label fw-bold">Commentaires</label>
                                        <div class="border rounded p-3 bg-light">{{ avisRetard.commentaires }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations du bien et client -->
                    <div class="col-lg-4">
                        <!-- Client -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-user me-2"></i>Locataire
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary text-white rounded-circle p-2 me-3">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ avisRetard.location?.client?.name || 'N/A' }}</div>
                                        <small class="text-muted">{{ avisRetard.location?.client?.email || '' }}</small>
                                    </div>
                                </div>
                                <div v-if="avisRetard.location?.client?.phone" class="mb-2">
                                    <i class="fas fa-phone text-muted me-2"></i>
                                    <a :href="`tel:${avisRetard.location.client.phone}`" class="text-decoration-none">
                                        {{ avisRetard.location.client.phone }}
                                    </a>
                                </div>
                                <div class="d-grid">
                                    <a
                                        :href="`mailto:${avisRetard.location?.client?.email}?subject=Concernant votre loyer`"
                                        class="btn btn-outline-primary btn-sm"
                                    >
                                        <i class="fas fa-envelope me-2"></i>Contacter
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Bien -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-home me-2"></i>Bien loué
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <img
                                        :src="avisRetard.location?.bien?.image ? `/storage/${avisRetard.location.bien.image}` : '/images/placeholder.jpg'"
                                        :alt="avisRetard.location?.bien?.title || 'Bien'"
                                        class="img-fluid rounded"
                                        style="height: 120px; object-fit: cover; width: 100%;"
                                    />
                                </div>
                                <h6 class="fw-bold text-primary mb-2">{{ avisRetard.location?.bien?.title || 'N/A' }}</h6>
                                <div class="mb-2">
                                    <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                    {{ avisRetard.location?.bien?.address }}, {{ avisRetard.location?.bien?.city }}
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">{{ avisRetard.location?.bien?.category?.name }}</small>
                                </div>
                                <div class="d-grid">
                                    <Link
                                        :href="route('locations.show', avisRetard.location?.id)"
                                        class="btn btn-outline-info btn-sm"
                                    >
                                        <i class="fas fa-eye me-2"></i>Voir location
                                    </Link>
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
import Layout from '@/Pages/Layout.vue'
export default { layout: Layout }
</script>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    avisRetard: { type: Object, required: true }
})

// Computed
const penalites = computed(() => {
    if (props.avisRetard.type === 'retard' && props.avisRetard.jours_retard > 0) {
        const semaines = Math.ceil(props.avisRetard.jours_retard / 7)
        return props.avisRetard.montant_du * 0.02 * semaines
    }
    return 0
})

const montantTotal = computed(() => {
    return props.avisRetard.montant_du + penalites.value
})

// Méthodes
const marquerPaye = async () => {
    if (confirm('Marquer cet avis comme payé ?')) {
        router.post(route('avis-retard.paye', props.avisRetard.id), {}, {
            onSuccess: () => {
                router.reload()
            }
        })
    }
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleString('fr-FR')
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

const getTypeBadgeClass = (type) => {
    return type === 'rappel' ? 'bg-primary' : 'bg-warning text-dark'
}

const getTypeIcon = (type) => {
    return type === 'rappel' ? 'fa-bell' : 'fa-exclamation-triangle'
}

const getTypeLabel = (type) => {
    return type === 'rappel' ? 'Rappel' : 'Avis de retard'
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
.card {
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.badge {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

.form-label {
    color: #495057;
    margin-bottom: 0.25rem;
}

hr {
    border-color: #dee2e6;
    opacity: 0.5;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.rounded-circle {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 768px) {
    .col-md-4, .col-md-6 {
        margin-bottom: 1rem;
    }
}
</style>
