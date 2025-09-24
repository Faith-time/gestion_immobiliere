<template>
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <!-- En-tête -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 text-primary mb-1">Détails de la Vente #{{ vente.id }}</h1>
                        <p class="text-muted mb-0">Informations complètes sur votre achat immobilier</p>
                    </div>
                    <div class="d-flex gap-2">
                        <Link :href="route('ventes.index')" class="btn btn-outline-secondary">
                            ← Retour aux ventes
                        </Link>
                    </div>
                </div>

                <!-- Statut de la vente -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-1">Statut de la Vente</h5>
                                        <p class="text-muted mb-0">Suivi de l'état de votre transaction</p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="badge fs-6 px-3 py-2" :class="getStatusBadgeClass(vente.statut)">
                                            {{ getStatusLabel(vente.statut) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Informations sur le bien -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-light border-0">
                                <h5 class="mb-0">
                                    <i class="fas fa-home text-primary me-2"></i>
                                    Bien Acquis
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <img
                                            :src="vente.bien?.image ? `/storage/${vente.bien.image}` : '/images/placeholder.jpg'"
                                            :alt="vente.bien?.title || 'Bien'"
                                            class="img-fluid rounded"
                                            style="height: 200px; object-fit: cover; width: 100%;"
                                        />
                                    </div>
                                    <div class="col-md-8">
                                        <h4 class="text-primary mb-2">{{ vente.bien?.title || 'Titre non disponible' }}</h4>

                                        <div class="row g-3 mb-3">
                                            <div class="col-6">
                                                <div class="d-flex align-items-center text-muted">
                                                    <i class="fas fa-map-marker-alt me-2"></i>
                                                    <span>{{ vente.bien?.address }}, {{ vente.bien?.city }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="d-flex align-items-center text-muted">
                                                    <i class="fas fa-tag me-2"></i>
                                                    <span>{{ vente.bien?.category?.name || 'Non spécifié' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="d-flex align-items-center text-muted">
                                                    <i class="fas fa-expand-arrows-alt me-2"></i>
                                                    <span>{{ vente.bien?.superficy }} m²</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="d-flex align-items-center text-muted">
                                                    <i class="fas fa-door-open me-2"></i>
                                                    <span>{{ vente.bien?.rooms }} pièces</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-light p-3 rounded">
                                            <p class="mb-0 text-muted">
                                                <strong>Description:</strong><br>
                                                {{ vente.bien?.description || 'Aucune description disponible' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Détails financiers et participants -->
                    <div class="col-lg-4">
                        <!-- Informations financières -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light border-0">
                                <h5 class="mb-0">
                                    <i class="fas fa-calculator text-success me-2"></i>
                                    Détails Financiers
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                            <h3 class="text-success mb-1">{{ formatPrice(vente.prix_vente) }} FCFA</h3>
                                            <small class="text-muted">Prix d'achat</small>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Date d'achat:</span>
                                            <strong>{{ formatDate(vente.date_vente) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Participants -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light border-0">
                                <h5 class="mb-0">
                                    <i class="fas fa-users text-info me-2"></i>
                                    Participants
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Acheteur -->
                                <div class="mb-3 pb-3 border-bottom">
                                    <h6 class="text-primary mb-2">Acheteur</h6>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ vente.acheteur?.name || 'Non spécifié' }}</div>
                                            <small class="text-muted">{{ vente.acheteur?.email || 'Email non disponible' }}</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vendeur -->
                                <div class="mb-0">
                                    <h6 class="text-warning mb-2">Vendeur (Propriétaire)</h6>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-3">
                                            <i class="fas fa-home text-warning"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ vente.bien?.proprietaire?.name || 'Non spécifié' }}</div>
                                            <small class="text-muted">Propriétaire du bien</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Signature -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-pen-fancy text-purple me-2"></i>
                                    Signatures du Contrat
                                </h5>
                                <span class="badge" :class="getSignatureStatusBadgeClass(signatureStats.signature_status)">
                                    {{ getSignatureStatusLabel(signatureStats.signature_status) }}
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <!-- Signature Vendeur -->
                                    <div class="col-md-6">
                                        <div class="border rounded p-3" :class="signatureStats.vendeur_signed ? 'border-success bg-success bg-opacity-5' : 'border-secondary bg-light'">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0">Vendeur</h6>
                                                <span v-if="signatureStats.vendeur_signed" class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Signé
                                                </span>
                                                <span v-else class="badge bg-secondary">Non signé</span>
                                            </div>
                                            <p class="text-muted small mb-0">{{ vente.bien?.proprietaire?.name }}</p>
                                            <p v-if="signatureStats.vendeur_signed_at" class="text-muted small mb-0">
                                                Signé le {{ formatDateTime(signatureStats.vendeur_signed_at) }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Signature Acheteur -->
                                    <div class="col-md-6">
                                        <div class="border rounded p-3" :class="signatureStats.acheteur_signed ? 'border-success bg-success bg-opacity-5' : 'border-secondary bg-light'">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0">Acheteur</h6>
                                                <span v-if="signatureStats.acheteur_signed" class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Signé
                                                </span>
                                                <span v-else class="badge bg-secondary">Non signé</span>
                                            </div>
                                            <p class="text-muted small mb-0">{{ vente.acheteur?.name }}</p>
                                            <p v-if="signatureStats.acheteur_signed_at" class="text-muted small mb-0">
                                                Signé le {{ formatDateTime(signatureStats.acheteur_signed_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light border-0">
                                <h5 class="mb-0">
                                    <i class="fas fa-tools text-dark me-2"></i>
                                    Actions Disponibles
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- Prévisualiser le contrat -->
                                    <div class="col-md-3">
                                        <button
                                            @click="previewContract"
                                            class="btn btn-outline-info w-100 d-flex align-items-center justify-content-center"
                                        >
                                            <i class="fas fa-eye me-2"></i>
                                            Prévisualiser
                                        </button>
                                    </div>

                                    <!-- Télécharger le contrat -->
                                    <div class="col-md-3">
                                        <button
                                            @click="downloadContract"
                                            class="btn btn-outline-success w-100 d-flex align-items-center justify-content-center"
                                        >
                                            <i class="fas fa-download me-2"></i>
                                            Télécharger PDF
                                        </button>
                                    </div>

                                    <!-- Signer le contrat -->
                                    <div class="col-md-3" v-if="canSign">
                                        <Link
                                            :href="route('ventes.signature.show', vente.id)"
                                            class="btn w-100 d-flex align-items-center justify-content-center"
                                            :class="signatureStats.fully_signed ? 'btn-success' : 'btn-warning'"
                                        >
                                            <i :class="signatureStats.fully_signed ? 'fas fa-check-circle me-2' : 'fas fa-pen me-2'"></i>
                                            {{ signatureStats.fully_signed ? 'Contrat Signé' : 'Signer le Contrat' }}
                                        </Link>
                                    </div>

                                    <!-- Admin: Éditer -->
                                    <div class="col-md-3" v-if="isAdmin">
                                        <Link
                                            :href="route('ventes.edit', vente.id)"
                                            class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center"
                                        >
                                            <i class="fas fa-edit me-2"></i>
                                            Modifier
                                        </Link>
                                    </div>
                                </div>

                                <!-- Message de statut complet -->
                                <div v-if="signatureStats.fully_signed" class="mt-3 p-3 bg-success bg-opacity-10 border border-success rounded">
                                    <div class="d-flex align-items-center text-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Contrat entièrement signé et valide légalement</strong>
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

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    vente: { type: Object, required: true },
    signatureStats: { type: Object, required: true },
    userRoles: { type: Array, default: () => [] },
    isAcheteur: { type: Boolean, default: false },
    isVendeur: { type: Boolean, default: false },
    isAdmin: { type: Boolean, default: false }
})

// Calculs dérivés
const canSign = computed(() => {
    return (props.isAcheteur && props.signatureStats.can_sign_acheteur) ||
        (props.isVendeur && props.signatureStats.can_sign_vendeur)
})

// Méthodes utilitaires
const formatPrice = (value) => {
    if (!value) return '0'
    return Number(value).toLocaleString('fr-FR')
}

const formatDate = (dateString) => {
    if (!dateString) return 'Non définie'
    return new Date(dateString).toLocaleDateString('fr-FR')
}

const formatDateTime = (dateString) => {
    if (!dateString) return 'Non définie'
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatusLabel = (statut) => {
    const labels = {
        'en_cours': 'En cours',
        'confirmée': 'Confirmée',
        'annulee': 'Annulée'
    }
    return labels[statut] || statut
}

const getStatusBadgeClass = (statut) => {
    const classes = {
        'en_cours': 'bg-warning text-dark',
        'confirmée': 'bg-success text-white',
        'annulee': 'bg-danger text-white'
    }
    return classes[statut] || 'bg-secondary text-white'
}

const getSignatureStatusLabel = (status) => {
    const labels = {
        'non_signe': 'Non signé',
        'partiellement_signe': 'Partiellement signé',
        'entierement_signe': 'Entièrement signé'
    }
    return labels[status] || 'Statut inconnu'
}

const getSignatureStatusBadgeClass = (status) => {
    const classes = {
        'non_signe': 'bg-danger text-white',
        'partiellement_signe': 'bg-warning text-dark',
        'entierement_signe': 'bg-success text-white'
    }
    return classes[status] || 'bg-secondary text-white'
}

// Actions
const previewContract = () => {
    window.open(route('ventes.contract.preview', props.vente.id), '_blank')
}

const downloadContract = () => {
    window.open(route('ventes.contract.download', props.vente.id))
}
</script>

<style scoped>
.card {
    transition: all 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

.btn {
    transition: all 0.2s ease;
}

.badge {
    font-size: 0.85rem;
}

.bg-purple {
    background-color: #6f42c1;
    color: white;
}

.text-purple {
    color: #6f42c1;
}
</style>
