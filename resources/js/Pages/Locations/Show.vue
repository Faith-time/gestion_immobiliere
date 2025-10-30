<template>
    <Layout :title="`Location #${location.id}`">
        <div class="container py-5">
            <div class="row">
                <!-- Colonne principale -->
                <div class="col-lg-8">
                    <!-- Carte Informations Location -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-file-contract me-2"></i>
                                Détails de la Location
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <small class="text-muted">Référence</small>
                                    <div class="fw-bold">#{{ location.id }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Type de contrat</small>
                                    <div class="fw-bold">
                                        <span class="badge bg-info">
                                            {{ location.type_contrat_info?.label || location.type_contrat }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Date de début</small>
                                    <div class="fw-bold">{{ formatDate(location.date_debut) }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Date de fin</small>
                                    <div class="fw-bold">{{ formatDate(location.date_fin) }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Loyer mensuel</small>
                                    <div class="fw-bold text-success fs-5">
                                        {{ formatPrice(location.loyer_mensuel) }} FCFA
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Statut</small>
                                    <div>
                                        <span :class="getStatusClass(location.statut)" class="badge">
                                            {{ getStatusLabel(location.statut) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Locataire</small>
                                    <div class="fw-bold">{{ location.client?.name || 'N/A' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Bailleur</small>
                                    <div class="fw-bold">
                                        {{ location.bien?.proprietaire?.name || 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Composant Statut Signatures -->
                    <TransferStatusCard
                        :location="location"
                        :transaction-status="transactionStatus"
                        :bailleur="location.bien?.proprietaire"
                        @continuer-paiement="continuerPaiement"
                        @signer-contrat="signerContrat"
                    />

                    <!-- Informations sur le Bien -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-home me-2"></i>
                                Bien Loué
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4" v-if="location.bien?.images?.[0]">
                                    <img
                                        :src="getBienImageUrl(location.bien.images[0].chemin_image)"
                                        :alt="location.bien.title"
                                        class="img-fluid rounded"
                                        style="max-height: 200px; width: 100%; object-fit: cover;">
                                </div>
                                <div :class="location.bien?.images?.[0] ? 'col-md-8' : 'col-12'">
                                    <h5 class="mb-3">{{ location.bien?.title }}</h5>
                                    <div class="mb-2">
                                        <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                        <span>{{ location.bien?.address }}, {{ location.bien?.city }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <i class="fas fa-tag text-muted me-2"></i>
                                        <span>{{ location.bien?.category?.name }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <i class="fas fa-ruler-combined text-muted me-2"></i>
                                        <span>{{ location.bien?.superficy }} m²</span>
                                    </div>
                                    <div class="mb-2">
                                        <i class="fas fa-info-circle text-muted me-2"></i>
                                        <span class="badge" :class="getBienStatusClass(location.bien?.status)">
                                            {{ location.bien?.status }}
                                        </span>
                                    </div>
                                    <Link
                                        v-if="location.bien?.id"
                                        :href="`/biens/${location.bien.id}`"
                                        class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-eye me-1"></i>
                                        Voir le bien
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations Paiement -->
                    <div class="card shadow-sm border-0 mb-4" v-if="location.paiement">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-credit-card me-2"></i>
                                Informations de Paiement
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <small class="text-muted">Montant total</small>
                                    <div class="fw-bold">{{ formatPrice(location.paiement.montant_total) }} FCFA</div>
                                    <small class="text-muted">(Premier mois + Caution)</small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Montant payé</small>
                                    <div class="fw-bold text-success">
                                        {{ formatPrice(location.paiement.montant_paye) }} FCFA
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Montant restant</small>
                                    <div class="fw-bold" :class="location.paiement.montant_restant > 0 ? 'text-danger' : 'text-success'">
                                        {{ formatPrice(location.paiement.montant_restant) }} FCFA
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Statut du paiement</small>
                                    <div>
                                        <span :class="getPaiementStatusClass(location.paiement.statut)" class="badge">
                                            {{ getPaiementStatusLabel(location.paiement.statut) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-12" v-if="location.paiement.montant_restant > 0">
                                    <div class="progress" style="height: 25px;">
                                        <div
                                            class="progress-bar bg-success"
                                            :style="`width: ${(location.paiement.montant_paye / location.paiement.montant_total * 100)}%`">
                                            {{ Math.round(location.paiement.montant_paye / location.paiement.montant_total * 100) }}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colonne actions -->
                <div class="col-lg-4">
                    <!-- Actions rapides -->
                    <div class="card shadow-sm border-0 mb-4 sticky-top" style="top: 20px;">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-tasks me-2"></i>
                                Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <!-- Continuer le paiement -->
                                <button
                                    v-if="!transactionStatus.paiement_complet && isLocataire"
                                    @click="continuerPaiement"
                                    class="btn btn-warning">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Continuer le Paiement
                                </button>

                                <!-- Signer le contrat -->
                                <Link
                                    v-if="transactionStatus.paiement_complet && canSign"
                                    :href="`/locations/${location.id}/signature`"
                                    class="btn btn-primary">
                                    <i class="fas fa-signature me-2"></i>
                                    Signer le Contrat
                                </Link>

                                <!-- Télécharger le contrat -->
                                <a
                                    v-if="transactionStatus.signatures_completes"
                                    :href="`/locations/${location.id}/contrat/download`"
                                    class="btn btn-success"
                                    target="_blank">
                                    <i class="fas fa-download me-2"></i>
                                    Télécharger le Contrat
                                </a>

                                <!-- Prévisualiser le contrat -->
                                <a
                                    v-if="transactionStatus.paiement_complet"
                                    :href="`/locations/${location.id}/contrat/preview`"
                                    class="btn btn-outline-primary"
                                    target="_blank">
                                    <i class="fas fa-eye me-2"></i>
                                    Prévisualiser le Contrat
                                </a>

                                <!-- Retour -->
                                <Link href="/locations" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Retour à mes locations
                                </Link>

                                <!-- Admin: Editer -->
                                <Link
                                    v-if="isAdmin"
                                    :href="`/locations/${location.id}/edit`"
                                    class="btn btn-outline-warning">
                                    <i class="fas fa-edit me-2"></i>
                                    Modifier
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Informations complémentaires -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Informations
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="small">
                                <div class="mb-3">
                                    <i class="fas fa-calendar text-muted me-2"></i>
                                    <strong>Créée le:</strong><br>
                                    <span class="ms-4">{{ formatDate(location.created_at) }}</span>
                                </div>
                                <div class="mb-3" v-if="location.type_contrat_info">
                                    <i class="fas fa-clock text-muted me-2"></i>
                                    <strong>Durée minimale:</strong><br>
                                    <span class="ms-4">{{ location.type_contrat_info.duree_min }} mois</span>
                                </div>
                                <div class="mb-3" v-if="transactionStatus.bailleur_signe">
                                    <i class="fas fa-signature text-success me-2"></i>
                                    <strong>Signature bailleur:</strong><br>
                                    <span class="ms-4">✓ Signée</span>
                                </div>
                                <div v-if="transactionStatus.locataire_signe">
                                    <i class="fas fa-signature text-success me-2"></i>
                                    <strong>Signature locataire:</strong><br>
                                    <span class="ms-4">✓ Signée</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Layout from '../Layout.vue'
import TransferStatusCard from '../Components/TransferStatusCard.vue'

const props = defineProps({
    location: Object,
    signatureStats: Object,
    userRoles: Array,
    isLocataire: Boolean,
    isBailleur: Boolean,
    isAdmin: Boolean
})

// Calculer le statut de la transaction
const transactionStatus = computed(() => {
    const paiement = props.location.paiement
    const paiementComplet = paiement &&
        paiement.statut === 'reussi' &&
        paiement.montant_restant <= 0

    return {
        paiement_complet: paiementComplet,
        montant_paye: paiement?.montant_paye || 0,
        montant_restant: paiement?.montant_restant || props.location.loyer_mensuel,
        signatures_completes: props.location.signature_bailleur && props.location.signature_locataire,
        bailleur_signe: !!props.location.signature_bailleur,
        locataire_signe: !!props.location.signature_locataire,
        statut_location: props.location.statut
    }
})

// Vérifier si l'utilisateur peut signer
const canSign = computed(() => {
    if (props.isLocataire && !props.location.signature_locataire) return true
    if (props.isBailleur && !props.location.signature_bailleur) return true
    return false
})

// Actions
const continuerPaiement = () => {
    const paiement = props.location.paiement
    if (paiement) {
        router.visit(`/paiement/initier/${props.location.id}/${paiement.id}`)
    }
}

const signerContrat = () => {
    router.visit(`/locations/${props.location.id}/signature`)
}

// Formatage
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price || 0)
}

const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    })
}

const getBienImageUrl = (path) => {
    return `/storage/${path}`
}

// Classes CSS
const getStatusClass = (statut) => {
    const classes = {
        'active': 'bg-success',
        'finalisée': 'bg-info',
        'terminée': 'bg-secondary',
        'annulée': 'bg-danger',
        'en_retard': 'bg-warning'
    }
    return classes[statut] || 'bg-secondary'
}

const getStatusLabel = (statut) => {
    const labels = {
        'active': 'Active',
        'finalisée': 'Finalisée',
        'terminée': 'Terminée',
        'annulée': 'Annulée',
        'en_retard': 'En retard'
    }
    return labels[statut] || statut
}

const getBienStatusClass = (status) => {
    const classes = {
        'disponible': 'bg-success',
        'vendu': 'bg-danger',
        'loue': 'bg-warning',
        'reserve': 'bg-info'
    }
    return classes[status] || 'bg-secondary'
}

const getPaiementStatusClass = (statut) => {
    const classes = {
        'en_attente': 'bg-warning',
        'reussi': 'bg-success',
        'echoue': 'bg-danger',
        'partiellement_paye': 'bg-info'
    }
    return classes[statut] || 'bg-secondary'
}

const getPaiementStatusLabel = (statut) => {
    const labels = {
        'en_attente': 'En attente',
        'reussi': 'Réussi',
        'echoue': 'Échoué',
        'partiellement_paye': 'Partiellement payé'
    }
    return labels[statut] || statut
}
</script>

<style scoped>
.sticky-top {
    position: sticky;
}

.card {
    border-radius: 10px;
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
}

.btn {
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>
