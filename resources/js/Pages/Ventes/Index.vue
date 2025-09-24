<template>
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <!-- En-tête dynamique -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 text-primary mb-1">{{ getPageTitle }}</h1>
                        <p class="text-muted mb-0">{{ getPageDescription }}</p>
                    </div>
                    <Link href="/" class="btn btn-outline-primary">
                        <i class="fas fa-home me-2"></i>Retour à l'accueil
                    </Link>
                </div>

                <!-- Onglets pour séparer les vues -->
                <div class="mb-4" v-if="!isAdmin && hasMultipleRoles">
                    <ul class="nav nav-pills">
                        <li class="nav-item">
                            <button
                                class="nav-link"
                                :class="{ active: activeTab === 'achats' }"
                                @click="activeTab = 'achats'"
                            >
                                <i class="fas fa-shopping-cart me-2"></i>Mes Achats ({{ ventesAsAcheteur.length }})
                            </button>
                        </li>
                        <li class="nav-item">
                            <button
                                class="nav-link"
                                :class="{ active: activeTab === 'ventes' }"
                                @click="activeTab = 'ventes'"
                            >
                                <i class="fas fa-home me-2"></i>Mes Ventes ({{ ventesAsProprietaire.length }})
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Statistiques rapides -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ getVentesByStatus('en_cours').length }}</h3>
                                <small>En cours</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ getVentesByStatus('confirmée').length }}</h3>
                                <small>Confirmées</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ getVentesByStatus('annulee').length }}</h3>
                                <small>Annulées</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ displayedVentes.length }}</h3>
                                <small>Total</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message si aucune transaction -->
                <div v-if="displayedVentes.length === 0" class="text-center py-5">
                    <div class="mb-4">
                        <i :class="getEmptyIcon" class="text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="text-muted mb-3">{{ getEmptyTitle }}</h4>
                    <p class="text-muted mb-4">{{ getEmptyDescription }}</p>
                    <Link href="/biens" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Parcourir les biens disponibles
                    </Link>
                </div>

                <!-- Liste des ventes -->
                <div v-else class="row g-4">
                    <div v-for="vente in displayedVentes" :key="vente.id" class="col-lg-6">
                        <div class="card h-100 shadow-sm border-0">
                            <!-- En-tête avec indicateur de rôle -->
                            <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i :class="getTransactionIcon(vente)" class="me-2"></i>
                                    <span class="fw-bold">{{ getTransactionLabel(vente) }} #{{ vente.id }}</span>
                                    <span class="badge ms-2" :class="getRoleBadgeClass(vente)">
                                        {{ getUserRoleLabel(vente) }}
                                    </span>
                                </div>
                                <span class="badge" :class="getStatusBadgeClass(vente.statut)">
                                    {{ getStatusLabel(vente.statut) }}
                                </span>
                            </div>

                            <div class="card-body">
                                <!-- Image et titre du bien -->
                                <div class="row mb-3">
                                    <div class="col-4">
                                        <img
                                            :src="vente.bien?.image ? `/storage/${vente.bien.image}` : '/images/placeholder.jpg'"
                                            :alt="vente.bien?.title || 'Bien'"
                                            class="img-fluid rounded"
                                            style="height: 80px; object-fit: cover; width: 100%;"
                                        />
                                    </div>
                                    <div class="col-8">
                                        <h6 class="card-title mb-1 text-primary">{{ vente.bien?.title || 'Bien non spécifié' }}</h6>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            {{ vente.bien?.address }}, {{ vente.bien?.city }}
                                        </p>
                                        <p class="text-muted small mb-0">{{ vente.bien?.category?.name }}</p>
                                    </div>
                                </div>

                                <!-- Informations contextuelles -->
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="bg-light p-2 rounded text-center">
                                            <div class="fw-bold text-success">{{ formatPrice(vente.prix_vente) }} FCFA</div>
                                            <small class="text-muted">{{ getPriceLabel(vente) }}</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-light p-2 rounded text-center">
                                            <div class="fw-bold text-info">{{ formatDate(vente.date_vente) }}</div>
                                            <small class="text-muted">{{ getDateLabel(vente) }}</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Information sur l'autre partie -->
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <strong>{{ getOtherPartyLabel(vente) }}:</strong>
                                        {{ getOtherPartyName(vente) }}
                                    </small>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="card-footer bg-transparent border-0">
                                <div class="d-flex gap-2 flex-wrap">
                                    <Link :href="route('ventes.show', vente.id)" class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="fas fa-eye me-1"></i>Détails
                                    </Link>

                                    <button @click="previewContract(vente)" class="btn btn-outline-info btn-sm flex-fill">
                                        <i class="fas fa-file-eye me-1"></i>Contrat
                                    </button>

                                    <button @click="downloadContract(vente)" class="btn btn-outline-success btn-sm flex-fill">
                                        <i class="fas fa-download me-1"></i>PDF
                                    </button>

                                    <button
                                        v-if="vente.can_sign"
                                        @click="goToSignature(vente)"
                                        class="btn btn-warning btn-sm flex-fill"
                                        :class="{'btn-success': vente.signature_stats?.fully_signed}"
                                    >
                                        <i class="fas fa-pen me-1" v-if="!vente.signature_stats?.fully_signed"></i>
                                        <i class="fas fa-check-circle me-1" v-else></i>
                                        {{ getSignatureButtonText(vente) }}
                                    </button>

                                    <div class="w-100 mt-1" v-if="vente.signature_stats">
                                        <small class="badge" :class="getSignatureStatusClass(vente.signature_stats.signature_status)">
                                            {{ getSignatureStatusText(vente.signature_stats.signature_status) }}
                                        </small>
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
import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    ventes: { type: Array, default: () => [] },
    userRoles: { type: Array, default: () => [] },
    userType: { type: String, default: 'client' }
})

// État local
const activeTab = ref('achats')

// Computed properties
const isAdmin = computed(() => props.userRoles.includes('admin'))

const ventesAsAcheteur = computed(() =>
    props.ventes.filter(vente => vente.user_role_in_vente === 'acheteur')
)

const ventesAsProprietaire = computed(() =>
    props.ventes.filter(vente => vente.user_role_in_vente === 'vendeur')
)

const hasMultipleRoles = computed(() =>
    ventesAsAcheteur.value.length > 0 && ventesAsProprietaire.value.length > 0
)

const displayedVentes = computed(() => {
    if (isAdmin.value) return props.ventes
    if (!hasMultipleRoles.value) return props.ventes
    return activeTab.value === 'achats' ? ventesAsAcheteur.value : ventesAsProprietaire.value
})

const getPageTitle = computed(() => {
    if (isAdmin.value) return 'Gestion des Ventes'
    if (!hasMultipleRoles.value) {
        return ventesAsAcheteur.value.length > 0 ? 'Mes Achats' : 'Mes Ventes'
    }
    return activeTab.value === 'achats' ? 'Mes Achats' : 'Mes Ventes'
})

const getPageDescription = computed(() => {
    if (isAdmin.value) return 'Gérez toutes les transactions immobilières'
    if (!hasMultipleRoles.value) {
        return ventesAsAcheteur.value.length > 0 ? 'Gérez vos achats immobiliers' : 'Gérez vos ventes immobilières'
    }
    return activeTab.value === 'achats' ? 'Gérez vos achats immobiliers' : 'Gérez vos ventes immobilières'
})

const getEmptyIcon = computed(() => {
    if (!hasMultipleRoles.value) {
        return ventesAsAcheteur.value.length === 0 && ventesAsProprietaire.value.length === 0
            ? 'fas fa-exchange-alt' : (ventesAsAcheteur.value.length > 0 ? 'fas fa-home' : 'fas fa-shopping-cart')
    }
    return activeTab.value === 'achats' ? 'fas fa-shopping-cart' : 'fas fa-home'
})

const getEmptyTitle = computed(() => {
    if (!hasMultipleRoles.value) {
        if (ventesAsAcheteur.value.length === 0 && ventesAsProprietaire.value.length === 0) {
            return 'Aucune transaction trouvée'
        }
        return ventesAsAcheteur.value.length > 0 ? 'Aucune vente trouvée' : 'Aucun achat trouvé'
    }
    return activeTab.value === 'achats' ? 'Aucun achat trouvé' : 'Aucune vente trouvée'
})

const getEmptyDescription = computed(() => {
    if (!hasMultipleRoles.value) {
        if (ventesAsAcheteur.value.length === 0 && ventesAsProprietaire.value.length === 0) {
            return 'Vous n\'avez encore aucune transaction immobilière.'
        }
        return ventesAsAcheteur.value.length > 0 ? 'Vous n\'avez encore vendu aucun bien.' : 'Vous n\'avez encore effectué aucun achat.'
    }
    return activeTab.value === 'achats' ? 'Vous n\'avez encore effectué aucun achat.' : 'Vous n\'avez encore vendu aucun bien.'
})

// Méthodes utilitaires existantes
const formatPrice = (value) => {
    if (!value) return '0'
    return Number(value).toLocaleString('fr-FR')
}

const formatDate = (dateString) => {
    if (!dateString) return 'Non définie'
    return new Date(dateString).toLocaleDateString('fr-FR')
}

const getStatusLabel = (statut) => ({
    en_cours: 'En cours',
    confirmée: 'Confirmée',
    annulee: 'Annulée'
}[statut] || statut)

const getStatusBadgeClass = (statut) => ({
    en_cours: 'bg-warning text-dark',
    confirmée: 'bg-success text-white',
    annulee: 'bg-danger text-white'
}[statut] || 'bg-secondary text-white')

const getVentesByStatus = (statut) => displayedVentes.value.filter(v => v.statut === statut)

// Nouvelles méthodes pour la contextualisation
const getTransactionIcon = (vente) => {
    return vente.user_role_in_vente === 'acheteur' ? 'fas fa-shopping-cart text-primary' : 'fas fa-home text-warning'
}

const getTransactionLabel = (vente) => {
    return vente.user_role_in_vente === 'acheteur' ? 'Achat' : 'Vente'
}

const getUserRoleLabel = (vente) => {
    return vente.user_role_in_vente === 'acheteur' ? 'Acheteur' : 'Vendeur'
}

const getRoleBadgeClass = (vente) => {
    return vente.user_role_in_vente === 'acheteur' ? 'bg-info text-white' : 'bg-warning text-dark'
}

const getPriceLabel = (vente) => {
    return vente.user_role_in_vente === 'acheteur' ? 'Prix d\'achat' : 'Prix de vente'
}

const getDateLabel = (vente) => {
    return vente.user_role_in_vente === 'acheteur' ? 'Date d\'achat' : 'Date de vente'
}

const getOtherPartyLabel = (vente) => {
    return vente.user_role_in_vente === 'acheteur' ? 'Vendeur' : 'Acheteur'
}

const getOtherPartyName = (vente) => {
    return vente.user_role_in_vente === 'acheteur'
        ? vente.bien?.proprietaire?.name || 'Non spécifié'
        : vente.acheteur?.name || 'Non spécifié'
}

// Méthodes d'actions (inchangées)
const previewContract = (vente) => window.open(route('ventes.contract.preview', vente.id), '_blank')
const downloadContract = (vente) => window.open(route('ventes.contract.download', vente.id))
const goToSignature = (vente) => router.visit(route('ventes.signature.show', vente.id))

const getSignatureButtonText = (vente) => vente.signature_stats?.fully_signed ? 'Signé' : 'Signer'
const getSignatureStatusText = (status) => ({
    non_signe: 'Non signé',
    partiellement_signe: 'Partiellement signé',
    entierement_signe: 'Entièrement signé'
}[status] || 'Statut inconnu')
const getSignatureStatusClass = (status) => ({
    non_signe: 'bg-danger text-white',
    partiellement_signe: 'bg-warning text-dark',
    entierement_signe: 'bg-success text-white'
}[status] || 'bg-secondary text-white')
</script>
