<template>
    <Layout :title="`Vente #${vente.id}`">
        <div class="container py-5">
            <div class="row">
                <!-- Colonne principale -->
                <div class="col-lg-8">
                    <!-- Carte Informations Vente -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-file-contract me-2"></i>
                                Détails de la Vente
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <small class="text-muted">Référence</small>
                                    <div class="fw-bold">#{{ vente.id }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Date de vente</small>
                                    <div class="fw-bold">{{ formatDate(vente.date_vente) }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Prix de vente</small>
                                    <div class="fw-bold text-success fs-5">
                                        {{ formatPrice(vente.prix_vente) }} FCFA
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Statut</small>
                                    <div>
                                        <span :class="getStatusClass(vente.status)" class="badge">
                                            {{ getStatusLabel(vente.status) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Acheteur</small>
                                    <div class="fw-bold">{{ vente.acheteur?.name || 'N/A' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Vendeur</small>
                                    <div class="fw-bold">
                                        {{ vente.ancien_proprietaire?.name || vente.bien?.proprietaire?.name || 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Composant Statut Transfert -->
                    <TransferStatusCard
                        :vente="vente"
                        :transaction-status="transactionStatus"
                        :ancien-proprietaire="vente.ancien_proprietaire"
                        @continuer-paiement="continuerPaiement"
                        @signer-contrat="signerContrat"
                    />

                    <!-- Informations sur le Bien -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-home me-2"></i>
                                Bien Concerné
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4" v-if="vente.bien?.images?.[0]">
                                    <img
                                        :src="getBienImageUrl(vente.bien.images[0].chemin_image)"
                                        :alt="vente.bien.title"
                                        class="img-fluid rounded"
                                        style="max-height: 200px; width: 100%; object-fit: cover;">
                                </div>
                                <div :class="vente.bien?.images?.[0] ? 'col-md-8' : 'col-12'">
                                    <h5 class="mb-3">{{ vente.bien?.title }}</h5>
                                    <div class="mb-2">
                                        <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                        <span>{{ vente.bien?.address }}, {{ vente.bien?.city }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <i class="fas fa-tag text-muted me-2"></i>
                                        <span>{{ vente.bien?.category?.name }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <i class="fas fa-ruler-combined text-muted me-2"></i>
                                        <span>{{ vente.bien?.superficy }} m²</span>
                                    </div>
                                    <div class="mb-2">
                                        <i class="fas fa-info-circle text-muted me-2"></i>
                                        <span class="badge" :class="getBienStatusClass(vente.bien?.status)">
                                            {{ vente.bien?.status }}
                                        </span>
                                    </div>
                                    <Link
                                        v-if="vente.bien?.id"
                                        :href="`/biens/${vente.bien.id}`"
                                        class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-eye me-1"></i>
                                        Voir le bien
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations Paiement -->
                    <div class="card shadow-sm border-0 mb-4" v-if="vente.paiement">
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
                                    <div class="fw-bold">{{ formatPrice(vente.paiement.montant_total) }} FCFA</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Montant payé</small>
                                    <div class="fw-bold text-success">
                                        {{ formatPrice(vente.paiement.montant_paye) }} FCFA
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Montant restant</small>
                                    <div class="fw-bold" :class="vente.paiement.montant_restant > 0 ? 'text-danger' : 'text-success'">
                                        {{ formatPrice(vente.paiement.montant_restant) }} FCFA
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Statut du paiement</small>
                                    <div>
                                        <span :class="getPaiementStatusClass(vente.paiement.statut)" class="badge">
                                            {{ getPaiementStatusLabel(vente.paiement.statut) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-12" v-if="vente.paiement.montant_restant > 0">
                                    <div class="progress" style="height: 25px;">
                                        <div
                                            class="progress-bar bg-success"
                                            :style="`width: ${(vente.paiement.montant_paye / vente.paiement.montant_total * 100)}%`">
                                            {{ Math.round(vente.paiement.montant_paye / vente.paiement.montant_total * 100) }}%
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
                                    v-if="!transactionStatus.paiement_complet && isAcheteur"
                                    @click="continuerPaiement"
                                    class="btn btn-warning">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Continuer le Paiement
                                </button>

                                <!-- Signer le contrat -->
                                <Link
                                    v-if="transactionStatus.paiement_complet && canSign"
                                    :href="`/ventes/${vente.id}/signature`"
                                    class="btn btn-primary">
                                    <i class="fas fa-signature me-2"></i>
                                    Signer le Contrat
                                </Link>

                                <!-- Télécharger le contrat -->
                                <a
                                    v-if="transactionStatus.signatures_completes"
                                    :href="`/ventes/${vente.id}/contrat/download`"
                                    class="btn btn-success"
                                    target="_blank">
                                    <i class="fas fa-download me-2"></i>
                                    Télécharger le Contrat
                                </a>

                                <!-- Prévisualiser le contrat -->
                                <a
                                    v-if="transactionStatus.paiement_complet"
                                    :href="`/ventes/${vente.id}/contrat/preview`"
                                    class="btn btn-outline-primary"
                                    target="_blank">
                                    <i class="fas fa-eye me-2"></i>
                                    Prévisualiser le Contrat
                                </a>

                                <!-- Retour -->
                                <Link href="/ventes" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Retour à mes ventes
                                </Link>

                                <!-- Admin: Editer -->
                                <Link
                                    v-if="isAdmin"
                                    :href="`/ventes/${vente.id}/edit`"
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
                                    <span class="ms-4">{{ formatDate(vente.created_at) }}</span>
                                </div>
                                <div class="mb-3" v-if="vente.property_transferred_at">
                                    <i class="fas fa-exchange-alt text-success me-2"></i>
                                    <strong>Propriété transférée le:</strong><br>
                                    <span class="ms-4">{{ formatDate(vente.property_transferred_at) }}</span>
                                </div>
                                <div class="mb-3" v-if="transactionStatus.vendeur_signe">
                                    <i class="fas fa-signature text-success me-2"></i>
                                    <strong>Signature vendeur:</strong><br>
                                    <span class="ms-4">✓ Signée</span>
                                </div>
                                <div v-if="transactionStatus.acheteur_signe">
                                    <i class="fas fa-signature text-success me-2"></i>
                                    <strong>Signature acheteur:</strong><br>
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
    vente: Object,
    signatureStats: Object,
    userRoles: Array,
    isAcheteur: Boolean,
    isVendeur: Boolean,
    isAdmin: Boolean,
    propertyTransferred: Boolean
})

// Calculer le statut de la transaction
const transactionStatus = computed(() => {
    const paiement = props.vente.paiement
    const paiementComplet = paiement &&
        paiement.statut === 'reussi' &&
        paiement.montant_restant <= 0

    return {
        paiement_complet: paiementComplet,
        montant_paye: paiement?.montant_paye || 0,
        montant_restant: paiement?.montant_restant || props.vente.prix_vente,
        signatures_completes: props.vente.signature_vendeur && props.vente.signature_acheteur,
        vendeur_signe: !!props.vente.signature_vendeur,
        acheteur_signe: !!props.vente.signature_acheteur,
        propriete_transferee: props.vente.property_transferred || false,
        date_transfert: props.vente.property_transferred_at,
        statut_vente: props.vente.status
    }
})

// Vérifier si l'utilisateur peut signer
const canSign = computed(() => {
    if (props.isAcheteur && !props.vente.signature_acheteur) return true
    if (props.isVendeur && !props.vente.signature_vendeur) return true
    return false
})

// Actions
const continuerPaiement = () => {
    const paiement = props.vente.paiement
    if (paiement) {
        router.visit(`/paiement/initier/${props.vente.id}/${paiement.id}`)
    }
}

const signerContrat = () => {
    router.visit(`/ventes/${props.vente.id}/signature`)
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
const getStatusClass = (status) => {
    const classes = {
        'en_cours': 'bg-info',
        'confirmée': 'bg-success',
        'annulée': 'bg-danger',
        'en_attente_paiement': 'bg-warning'
    }
    return classes[status] || 'bg-secondary'
}

const getStatusLabel = (status) => {
    const labels = {
        'en_cours': 'En cours',
        'confirmée': 'Confirmée',
        'annulée': 'Annulée',
        'en_attente_paiement': 'En attente de paiement'
    }
    return labels[status] || status
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
