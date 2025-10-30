<template>
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <!-- En-tête -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">
                        <i class="fas fa-key me-3 text-primary"></i>Mes Contrats de location
                    </h2>
                    <div class="text-muted">
                        {{ locations.length }} location{{ locations.length > 1 ? 's' : '' }}
                    </div>
                </div>

                <!-- Messages de session -->
                <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ $page.props.flash.success }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ $page.props.flash.error }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <!-- Statistiques rapides -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ getLocationsByStatus('active').length }}</h3>
                                <small>Actives</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark h-100">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ getLocationsByStatus('en_attente_paiement').length }}</h3>
                                <small>En attente de paiement</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ getLocationsByStatus('terminee').length }}</h3>
                                <small>Terminées</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ locations.length }}</h3>
                                <small>Total</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des locations -->
                <div v-if="locations.length > 0" class="row g-4">
                    <div v-for="location in locations" :key="location.id" class="col-lg-6">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <!-- En-tête de la carte avec statut -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            {{ location.bien?.titre || 'Bien sans titre' }}
                                        </h5>
                                        <p class="text-muted mb-0 small">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            {{ location.bien?.adresse || 'Adresse non spécifiée' }}, {{ location.bien?.ville }}
                                        </p>
                                    </div>
                                    <span :class="getStatutBadgeClass(location.statut)">
                                        {{ getStatutLabel(location.statut) }}
                                    </span>
                                </div>

                                <!-- Informations principales -->
                                <div class="row g-3 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Locataire</small>
                                        <div class="fw-medium">
                                            <i class="fas fa-user me-1 text-primary"></i>
                                            {{ location.locataire?.name || 'Non défini' }}
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Loyer mensuel</small>
                                        <div class="fw-medium text-success">
                                            <i class="fas fa-coins me-1"></i>
                                            {{ formatPrice(location.montant) }} FCFA
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Date début</small>
                                        <div class="fw-medium">
                                            <i class="fas fa-calendar-check me-1 text-info"></i>
                                            {{ formatDate(location.date_debut) }}
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Date fin</small>
                                        <div class="fw-medium">
                                            <i class="fas fa-calendar-times me-1 text-danger"></i>
                                            {{ formatDate(location.date_fin) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Durée restante -->
                                <div class="alert alert-info py-2 mb-3">
                                    <small>
                                        <i class="fas fa-clock me-1"></i>
                                        <strong>{{ getDureeRestante(location) }}</strong>
                                        <span class="text-muted"> sur {{ getDureeTotal(location) }} mois</span>
                                    </small>
                                </div>

                                <!-- Barre de progression -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">Progression</small>
                                        <small class="text-muted">{{ getProgressPercentage(location) }}%</small>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div
                                            class="progress-bar"
                                            :class="getProgressBarClass(location.statut)"
                                            role="progressbar"
                                            :style="`width: ${getProgressPercentage(location)}%`"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Informations de signature -->
                                <div v-if="location.signature_stats" class="mb-3">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <small class="badge" :class="getSignatureStatusClass(location.signature_stats.signature_status)">
                                            <i class="fas fa-pen me-1"></i>
                                            {{ getSignatureStatusText(location.signature_stats.signature_status) }}
                                        </small>
                                        <small v-if="location.signature_stats.locataire_signed" class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Locataire signé
                                        </small>
                                        <small v-if="location.signature_stats.bailleur_signed" class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Bailleur signé
                                        </small>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="d-flex gap-2 mt-auto flex-wrap">
                                    <!-- Voir le bien -->
                                    <Link
                                        v-if="location.bien?.id"
                                        :href="route('biens.show', location.bien.id)"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Voir le bien
                                    </Link>

                                    <!-- Gérer les loyers (pour le locataire uniquement et si location active) -->
                                    <Link
                                        v-if="isLocataire(location) && location.statut === 'active'"
                                        :href="route('locations.mes-loyers')"
                                        class="btn btn-primary btn-sm">
                                        <i class="fas fa-wallet me-1"></i>Gérer mes loyers
                                    </Link>

                                    <!-- Aperçu du contrat -->
                                    <button
                                        @click="previewContract(location)"
                                        class="btn btn-outline-info btn-sm"
                                        title="Aperçu du contrat">
                                        <i class="fas fa-file-alt me-1"></i>Aperçu
                                    </button>

                                    <!-- Télécharger le contrat -->
                                    <button
                                        @click="downloadContract(location)"
                                        class="btn btn-outline-success btn-sm"
                                        title="Télécharger le contrat">
                                        <i class="fas fa-download me-1"></i>PDF
                                    </button>

                                    <!-- Signer le contrat -->
                                    <button
                                        @click="goToSignature(location)"
                                        class="btn btn-sm"
                                        :class="location.signature_stats?.fully_signed ? 'btn-success' : 'btn-warning'"
                                        :title="getSignatureButtonTitle(location)">
                                        <i class="fas me-1" :class="location.signature_stats?.fully_signed ? 'fa-check-circle' : 'fa-pen'"></i>
                                        {{ getSignatureButtonText(location) }}
                                    </button>
                                </div>
                            </div>

                            <!-- Footer avec date de création -->
                            <div class="card-footer bg-transparent border-0 pt-0">
                                <small class="text-muted">
                                    Créée le {{ formatDate(location.created_at) }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- État vide -->
                <div v-else class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-key display-1 text-muted opacity-50"></i>
                    </div>
                    <h4 class="text-muted mb-3">Aucune location trouvée</h4>
                    <p class="text-muted mb-4">
                        Vous n'avez pas encore de contrat de location. <br>
                        Explorez nos biens disponibles à la location.
                    </p>
                    <Link href="/" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Explorer les biens
                    </Link>
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
import { onMounted } from 'vue'

const props = defineProps({
    locations: { type: Array, default: () => [] },
    userRoles: { type: Array, default: () => [] }
})

onMounted(() => {
    console.log('📦 Locations reçues:', props.locations)
    if (props.locations.length > 0) {
        console.log('📦 Première location:', props.locations[0])
        console.log('💰 Montant:', props.locations[0].montant)
        console.log('🏠 Bien:', props.locations[0].bien)
    }
})

// Vérifier si l'utilisateur est le locataire
const isLocataire = (location) => {
    return location.user_role_in_location === 'locataire'
}

// Méthodes utilitaires de formatage
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price || 0)
}

const formatDate = (dateString) => {
    if (!dateString) return 'Non spécifié'
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

// Méthodes de statut
const getStatutLabel = (statut) => {
    const labels = {
        'en_attente_paiement': 'En attente de paiement',
        'active': 'Active',
        'terminee': 'Terminée',
        'en_retard': 'En retard',
        'suspendue': 'Suspendue',
        'annulee': 'Annulée'
    }
    return labels[statut] || statut
}

const getStatutBadgeClass = (statut) => {
    const classes = {
        'en_attente_paiement': 'badge bg-warning text-dark',
        'active': 'badge bg-success',
        'terminee': 'badge bg-secondary',
        'en_retard': 'badge bg-danger',
        'suspendue': 'badge bg-danger',
        'annulee': 'badge bg-dark'
    }
    return classes[statut] || 'badge bg-secondary'
}

const getProgressBarClass = (statut) => {
    const classes = {
        'en_attente_paiement': 'bg-warning',
        'active': 'bg-success',
        'terminee': 'bg-secondary',
        'en_retard': 'bg-danger',
        'suspendue': 'bg-danger',
        'annulee': 'bg-dark'
    }
    return classes[statut] || 'bg-secondary'
}

// Méthodes de filtrage
const getLocationsByStatus = (statut) => {
    return props.locations.filter(location => location.statut === statut)
}

// Calculs de progression et durée
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

const getDureeTotal = (location) => {
    if (!location.date_debut || !location.date_fin) return 0

    const debut = new Date(location.date_debut)
    const fin = new Date(location.date_fin)

    const diffTime = fin.getTime() - debut.getTime()
    const diffMonths = Math.round(diffTime / (1000 * 60 * 60 * 24 * 30))

    return diffMonths
}

// Actions sur les contrats
const previewContract = (location) => {
    window.open(route('locations.contract.preview', location.id), '_blank')
}

const downloadContract = (location) => {
    window.open(route('locations.contract.download', location.id))
}

const goToSignature = (location) => {
    router.visit(route('signature.show', location.id))
}

// Méthodes pour les signatures
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
    border-radius: 15px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.card-title {
    color: #2c3e50;
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.875rem;
    border-radius: 6px;
}

.alert {
    border-radius: 8px;
}

.badge {
    font-size: 0.75rem;
    padding: 0.4em 0.8em;
    border-radius: 8px;
}

.progress {
    background-color: #e9ecef;
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
}

.card.bg-primary:hover,
.card.bg-warning:hover,
.card.bg-success:hover,
.card.bg-info:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}
</style>
