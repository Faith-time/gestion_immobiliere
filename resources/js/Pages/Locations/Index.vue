<template>
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <!-- En-tête -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 text-primary mb-1">Mes Locations</h1>
                        <p class="text-muted mb-0">Gérez vos contrats de location</p>
                    </div>
                    <Link
                        href="/"
                        class="btn btn-outline-primary"
                    >
                        <i class="fas fa-home me-2"></i>Retour à l'accueil
                    </Link>
                </div>

                <!-- Statistiques rapides -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ getLocationsByStatus('active').length }}</h3>
                                <small>Locations actives</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ getLocationsByStatus('en_attente').length }}</h3>
                                <small>En attente</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ getLocationsByStatus('terminee').length }}</h3>
                                <small>Terminées</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ locations.length }}</h3>
                                <small>Total</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message si aucune location -->
                <div v-if="locations.length === 0" class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-key text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="text-muted mb-3">Aucune location trouvée</h4>
                    <p class="text-muted mb-4">Vous n'avez pas encore de contrat de location.</p>
                    <Link
                        href="/"
                        class="btn btn-primary"
                    >
                        <i class="fas fa-search me-2"></i>Parcourir les biens disponibles
                    </Link>
                </div>

                <!-- Liste des locations -->
                <div v-else class="row g-4">
                    <div
                        v-for="location in locations"
                        :key="location.id"
                        class="col-lg-6"
                    >
                        <div class="card h-100 shadow-sm border-0">
                            <!-- En-tête de la carte -->
                            <div class="card-header bg-light border-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-key text-primary me-2"></i>
                                        <span class="fw-bold">Location {{ location.bien?.title }}</span>
                                    </div>
                                    <span
                                        class="badge"
                                        :class="getStatusBadgeClass(location.statut)"
                                    >
                                        {{ getStatusLabel(location.statut) }}
                                    </span>
                                </div>
                            </div>

                            <div class="card-body">
                                <!-- Image et titre du bien -->
                                <div class="row mb-3">
                                    <div class="col-4">
                                        <img
                                            :src="location.bien?.image ? `/storage/${location.bien.image}` : '/images/placeholder.jpg'"
                                            :alt="location.bien?.title || 'Bien'"
                                            class="img-fluid rounded"
                                            style="height: 80px; object-fit: cover; width: 100%;"
                                        />
                                    </div>
                                    <div class="col-8">
                                        <h6 class="card-title mb-1 text-primary">
                                            {{ location.bien?.title || 'Bien non spécifié' }}
                                        </h6>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            {{ location.bien?.address }}, {{ location.bien?.city }}
                                        </p>
                                        <p class="text-muted small mb-0">
                                            {{ location.bien?.category?.name }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Informations financières -->
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="bg-light p-2 rounded text-center">
                                            <div class="fw-bold text-success">{{ formatPrice(location.loyer_mensuel) }} FCFA</div>
                                            <small class="text-muted">Loyer/mois</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dates -->
                                <div class="mb-3">
                                    <div class="row g-2 text-sm">
                                        <div class="col-6">
                                            <small class="text-muted">Début:</small>
                                            <div class="fw-medium">{{ formatDate(location.date_debut) }}</div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Fin:</small>
                                            <div class="fw-medium">{{ formatDate(location.date_fin) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Durée et progression -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted">Progression</small>
                                        <small class="text-muted">{{ getDureeRestante(location) }}</small>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div
                                            class="progress-bar"
                                            :class="getProgressBarClass(location.statut)"
                                            :style="{ width: getProgressPercentage(location) + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="card-footer bg-transparent border-0">
                                <div class="d-flex gap-2 flex-wrap">
                                    <Link
                                        :href="route('locations.show', location.id)"
                                        class="btn btn-outline-primary btn-sm flex-fill"
                                    >
                                        <i class="fas fa-eye me-1"></i>Détails
                                    </Link>

                                    <!-- Boutons de contrat -->
                                    <button
                                        @click="previewContract(location)"
                                        class="btn btn-outline-info btn-sm flex-fill"
                                        title="Prévisualiser le contrat"
                                    >
                                        <i class="fas fa-file-eye me-1"></i>Contrat
                                    </button>

                                    <button
                                        @click="downloadContract(location)"
                                        class="btn btn-outline-success btn-sm flex-fill"
                                        title="Télécharger le contrat PDF"
                                    >
                                        <i class="fas fa-download me-1"></i>PDF
                                    </button>

                                    <!-- Bouton de signature -->
                                    <button
                                        v-if="location.can_sign"
                                        @click="goToSignature(location)"
                                        class="btn btn-warning btn-sm flex-fill"
                                        :class="{
                'btn-outline-warning': !location.signature_stats?.fully_signed,
                'btn-success': location.signature_stats?.fully_signed
            }"
                                        :title="getSignatureButtonTitle(location)"
                                    >
                                        <i class="fas fa-pen me-1" v-if="!location.signature_stats?.fully_signed"></i>
                                        <i class="fas fa-check-circle me-1" v-else></i>
                                        {{ getSignatureButtonText(location) }}
                                    </button>

                                    <!-- Badge de statut de signature -->
                                    <div class="w-100 mt-1" v-if="location.signature_stats">
                                        <small class="badge" :class="getSignatureStatusClass(location.signature_stats.signature_status)">
                                            {{ getSignatureStatusText(location.signature_stats.signature_status) }}
                                        </small>
                                    </div>

                                    <!-- Actions supplémentaires -->
                                    <button
                                        v-if="location.statut === 'active'"
                                        @click="gererPaiements(location)"
                                        class="btn btn-outline-success btn-sm flex-fill mt-1"
                                    >
                                    </button>

                                    <button
                                        v-if="location.statut === 'en_attente'"
                                        @click="annulerLocation(location)"
                                        class="btn btn-outline-danger btn-sm mt-1"
                                    >
                                        <i class="fas fa-times me-1"></i>Annuler
                                    </button>
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
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    locations: { type: Array, default: () => [] },
    userRoles: { type: Array, default: () => [] }
})

// Méthodes utilitaires
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price || 0)
}

const formatDate = (dateString) => {
    if (!dateString) return 'Non spécifié'
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const getStatusLabel = (statut) => {
    const labels = {
        'en_attente': 'En attente',
        'active': 'Active',
        'terminee': 'Terminée',
        'suspendue': 'Suspendue',
        'annulee': 'Annulée'
    }
    return labels[statut] || statut
}

const getStatusBadgeClass = (statut) => {
    const classes = {
        'en_attente': 'bg-warning text-dark',
        'active': 'bg-success',
        'terminee': 'bg-secondary',
        'suspendue': 'bg-danger',
        'annulee': 'bg-dark'
    }
    return classes[statut] || 'bg-secondary'
}

const getProgressBarClass = (statut) => {
    const classes = {
        'en_attente': 'bg-warning',
        'active': 'bg-success',
        'terminee': 'bg-secondary',
        'suspendue': 'bg-danger',
        'annulee': 'bg-dark'
    }
    return classes[statut] || 'bg-secondary'
}

const getLocationsByStatus = (statut) => {
    return props.locations.filter(location => location.statut === statut)
}

const getProgressPercentage = (location) => {
    if (!location.date_debut || !location.date_fin) return 0

    const debut = new Date(location.date_debut)
    const fin = new Date(location.date_fin)
    const maintenant = new Date()

    if (maintenant < debut) return 0
    if (maintenant > fin) return 100

    const dureeTotal = fin.getTime() - debut.getTime()
    const dureeEcoulee = maintenant.getTime() - debut.getTime()

    return Math.round((dureeEcoulee / dureeTotal) * 100)
}

const getDureeRestante = (location) => {
    if (!location.date_fin) return 'Non spécifié'

    const fin = new Date(location.date_fin)
    const maintenant = new Date()

    if (maintenant > fin) return 'Terminée'

    const diffTime = fin.getTime() - maintenant.getTime()
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

    if (diffDays < 30) {
        return `${diffDays} jour${diffDays > 1 ? 's' : ''} restant${diffDays > 1 ? 's' : ''}`
    } else {
        const diffMonths = Math.ceil(diffDays / 30)
        return `${diffMonths} mois restant${diffMonths > 1 ? 's' : ''}`
    }
}

// Actions
const gererPaiements = (location) => {
    router.visit(route('paiements.location', location.id))
}

const annulerLocation = (location) => {
    if (confirm('Êtes-vous sûr de vouloir annuler cette location ?')) {
        router.post(route('locations.annuler', location.id), {}, {
            onSuccess: () => {
                console.log('Location annulée')
            }
        })
    }
}

// Actions pour les contrats et signatures
const previewContract = (location) => {
    window.open(route('locations.contract.preview', location.id), '_blank')
}

const downloadContract = (location) => {
    window.open(route('locations.contract.download', location.id))
}

const goToSignature = (location) => {
    router.visit(route('locations.signature.show', location.id))
}

// Méthodes utilitaires pour les signatures
const getSignatureButtonText = (location) => {
    if (location.signature_stats?.fully_signed) {
        return 'Signé'
    }

    const userRole = location.user_role_in_location
    if (userRole === 'locataire') {
        return location.signature_stats?.locataire_signed ? 'Signé' : 'Signer'
    } else if (userRole === 'bailleur') {
        return location.signature_stats?.bailleur_signed ? 'Signé' : 'Signer'
    }

    return 'Signer'
}

const getSignatureButtonTitle = (location) => {
    if (location.signature_stats?.fully_signed) {
        return 'Contrat entièrement signé'
    }

    const userRole = location.user_role_in_location
    if (userRole === 'locataire') {
        return location.signature_stats?.locataire_signed ?
            'Vous avez signé ce contrat' : 'Signer en tant que locataire'
    } else if (userRole === 'bailleur') {
        return location.signature_stats?.bailleur_signed ?
            'Vous avez signé ce contrat' : 'Signer en tant que propriétaire'
    }

    return 'Signer le contrat'
}

const getSignatureStatusText = (status) => {
    const statusLabels = {
        'non_signe': 'Non signé',
        'partiellement_signe': 'Partiellement signé',
        'entierement_signe': 'Entièrement signé'
    }
    return statusLabels[status] || 'Statut inconnu'
}

const getSignatureStatusClass = (status) => {
    const statusClasses = {
        'non_signe': 'bg-danger text-white',
        'partiellement_signe': 'bg-warning text-dark',
        'entierement_signe': 'bg-success text-white'
    }
    return statusClasses[status] || 'bg-secondary text-white'
}
</script>

<style scoped>
.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

.progress {
    background-color: #e9ecef;
}

.text-sm {
    font-size: 0.875rem;
}

.bg-primary {
    background-color: #17a2b8 !important;
}

.text-primary {
    color: #17a2b8 !important;
}

.btn-primary {
    background-color: #17a2b8;
    border-color: #17a2b8;
}

.btn-primary:hover {
    background-color: #138496;
    border-color: #117a8b;
}

@media (max-width: 768px) {
    .card-footer .d-flex {
        flex-direction: column;
        gap: 0.5rem !important;
    }

    .card-footer .btn {
        width: 100%;
    }
}

/* Styles pour les boutons de signature */
.btn-warning.btn-success {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
}

.btn-warning.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

/* Responsive pour les actions */
@media (max-width: 576px) {
    .card-footer .d-flex {
        flex-direction: column;
    }

    .card-footer .btn {
        margin-bottom: 0.25rem;
    }
}

/* Badge de signature */
.badge {
    font-size: 0.75rem;
}
</style>
