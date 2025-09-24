<!-- resources/js/Pages/Paiement/Index.vue -->
<template>
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <!-- En-tête -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">
                        <i class="fas fa-credit-card me-3 text-primary"></i>Mes paiements
                    </h2>
                    <div class="text-muted">
                        {{ paiements.length }} paiement{{ paiements.length > 1 ? 's' : '' }}
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

                <!-- Filtres -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="filter_statut" class="form-label small text-muted">Statut</label>
                                <select
                                    id="filter_statut"
                                    v-model="filters.statut"
                                    class="form-select form-select-sm">
                                    <option value="">Tous les statuts</option>
                                    <option value="en_attente">En attente</option>
                                    <option value="reussi">Réussi</option>
                                    <option value="echoue">Échec</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_type" class="form-label small text-muted">Type</label>
                                <select
                                    id="filter_type"
                                    v-model="filters.type"
                                    class="form-select form-select-sm">
                                    <option value="">Tous les types</option>
                                    <option value="reservation">Réservation</option>
                                    <option value="location">Location</option>
                                    <option value="vente">Vente</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_mode" class="form-label small text-muted">Mode de paiement</label>
                                <select
                                    id="filter_mode"
                                    v-model="filters.mode_paiement"
                                    class="form-select form-select-sm">
                                    <option value="">Tous les modes</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="carte">Carte bancaire</option>
                                    <option value="virement">Virement</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button
                                    @click="resetFilters"
                                    class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="fas fa-filter-circle-xmark me-1"></i>Réinitialiser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des paiements -->
                <div v-if="paiementsFiltres.length > 0" class="row g-4">
                    <div v-for="paiement in paiementsFiltres" :key="paiement.id" class="col-lg-6">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <!-- En-tête avec statut et type -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h6 class="card-title mb-0 text-primary">
                                                <i :class="getTypeIcon(paiement.type)" class="me-2"></i>
                                                {{ getTypeLabel(paiement.type) }}
                                            </h6>
                                            <span :class="getStatutBadgeClass(paiement.statut)">
                                                {{ getStatutLabel(paiement.statut) }}
                                            </span>
                                        </div>
                                        <p class="text-muted mb-0 small">
                                            ID: {{ paiement.transaction_id || `PAI-${paiement.id}` }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Informations sur le bien associé -->
                                <div v-if="getBienAssocie(paiement)" class="bg-light rounded p-3 mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-8">
                                            <h6 class="mb-1 text-dark">{{ getBienAssocie(paiement).title }}</h6>
                                            <p class="text-muted mb-0 small">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                {{ getBienAssocie(paiement).address }}, {{ getBienAssocie(paiement).city }}
                                            </p>
                                        </div>
                                        <div class="col-4 text-end">
                                            <img
                                                v-if="getBienAssocie(paiement).image"
                                                :src="getBienImageUrl(getBienAssocie(paiement).image)"
                                                :alt="getBienAssocie(paiement).title"
                                                class="img-fluid rounded"
                                                style="max-height: 50px; max-width: 80px; object-fit: cover;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Détails du paiement -->
                                <div class="row g-3 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Montant total</small>
                                        <div class="fw-bold text-dark">
                                            {{ formatPrice(paiement.montant_total) }} FCFA
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Montant payé</small>
                                        <div class="fw-bold" :class="getPaiementStatusClass(paiement)">
                                            {{ formatPrice(paiement.montant_paye) }} FCFA
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Mode de paiement</small>
                                        <div class="fw-medium">
                                            <i :class="getModeIcon(paiement.mode_paiement)" class="me-1"></i>
                                            {{ getModeLabel(paiement.mode_paiement) }}
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Date</small>
                                        <div class="fw-medium">
                                            {{ formatDate(paiement.date_transaction || paiement.created_at) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Montant restant si applicable -->
                                <div v-if="paiement.montant_restant > 0" class="alert alert-warning py-2 px-3 mb-3">
                                    <small>
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <strong>Reste à payer :</strong> {{ formatPrice(paiement.montant_restant) }} FCFA
                                    </small>
                                </div>

                                <!-- Actions spécifiques pour réservations réussies -->
                                <div v-if="showReservationActions(paiement)" class="mb-3">
                                    <div class="border-top pt-3">
                                        <h6 class="text-success mb-2">
                                            <i class="fas fa-check-circle me-2"></i>Prochaines étapes
                                        </h6>
                                        <div class="d-grid gap-2">
                                            <Link
                                                :href="route('visites.create', { bien_id: getBienAssocie(paiement).id })"
                                                class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-calendar-alt me-1"></i>Programmer une visite
                                            </Link>
                                            <Link
                                                v-if="peutProcederVente(paiement)"
                                                :href="route('ventes.create', { bien_id: getBienAssocie(paiement).id })"
                                                class="btn btn-success btn-sm">
                                                <i class="fas fa-handshake me-1"></i>Procéder à la vente
                                            </Link>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="d-flex gap-2 mt-auto">
                                    <Link
                                        :href="getDetailsRoute(paiement)"
                                        class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="fas fa-eye me-1"></i>Détails
                                    </Link>

                                    <!-- Reprendre le paiement si échec -->
                                    <button
                                        v-if="paiement.statut === 'echoue'"
                                        @click="reprendrePaiement(paiement)"
                                        class="btn btn-warning btn-sm">
                                        <i class="fas fa-redo me-1"></i>Reprendre
                                    </button>
                                </div>
                            </div>

                            <!-- Footer avec date de création -->
                            <div class="card-footer bg-transparent border-0 pt-0">
                                <small class="text-muted">
                                    Créé le {{ formatDate(paiement.created_at) }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- État vide -->
                <div v-else-if="paiements.length === 0" class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-credit-card display-1 text-muted opacity-50"></i>
                    </div>
                    <h4 class="text-muted mb-3">Aucun paiement trouvé</h4>
                    <p class="text-muted mb-4">
                        Vous n'avez encore effectué aucun paiement. <br>
                        Réservez un bien pour commencer.
                    </p>
                    <Link href="/" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Explorer les biens
                    </Link>
                </div>

                <!-- Aucun résultat après filtrage -->
                <div v-else-if="paiementsFiltres.length === 0" class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-filter display-1 text-muted opacity-50"></i>
                    </div>
                    <h4 class="text-muted mb-3">Aucun paiement correspondant</h4>
                    <p class="text-muted mb-4">
                        Aucun paiement ne correspond à vos critères de recherche.
                    </p>
                    <button @click="resetFilters" class="btn btn-outline-primary">
                        <i class="fas fa-filter-circle-xmark me-2"></i>Réinitialiser les filtres
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    paiements: { type: Array, required: true },
    userRoles: { type: Array, default: () => [] }
})

// Filtres
const filters = ref({
    statut: '',
    type: '',
    mode_paiement: ''
})

// Paiements filtrés
const paiementsFiltres = computed(() => {
    let result = [...props.paiements]

    if (filters.value.statut) {
        result = result.filter(p => p.statut === filters.value.statut)
    }

    if (filters.value.type) {
        result = result.filter(p => p.type === filters.value.type)
    }

    if (filters.value.mode_paiement) {
        result = result.filter(p => p.mode_paiement === filters.value.mode_paiement)
    }

    return result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

// Méthodes utilitaires
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price || 0)
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatutLabel = (statut) => {
    const labels = {
        'en_attente': 'En attente',
        'reussi': 'Réussi',
        'echoue': 'Échec'
    }
    return labels[statut] || statut
}

const getStatutBadgeClass = (statut) => {
    const classes = {
        'en_attente': 'badge bg-warning text-dark',
        'reussi': 'badge bg-success',
        'echoue': 'badge bg-danger'
    }
    return classes[statut] || 'badge bg-secondary'
}

const getTypeLabel = (type) => {
    const labels = {
        'reservation': 'Réservation',
        'location': 'Location',
        'vente': 'Vente'
    }
    return labels[type] || type
}

const getTypeIcon = (type) => {
    const icons = {
        'reservation': 'fas fa-bookmark',
        'location': 'fas fa-key',
        'vente': 'fas fa-handshake'
    }
    return icons[type] || 'fas fa-credit-card'
}

const getModeLabel = (mode) => {
    const labels = {
        'mobile_money': 'Mobile Money',
        'carte': 'Carte bancaire',
        'virement': 'Virement'
    }
    return labels[mode] || mode
}

const getModeIcon = (mode) => {
    const icons = {
        'mobile_money': 'fas fa-mobile-alt',
        'carte': 'fas fa-credit-card',
        'virement': 'fas fa-university'
    }
    return icons[mode] || 'fas fa-money-bill'
}

const getPaiementStatusClass = (paiement) => {
    if (paiement.statut === 'reussi') return 'text-success'
    if (paiement.statut === 'echoue') return 'text-danger'
    return 'text-warning'
}

const getBienAssocie = (paiement) => {
    if (paiement.reservation?.bien) return paiement.reservation.bien
    if (paiement.location?.bien) return paiement.location.bien
    if (paiement.vente?.bien) return paiement.vente.bien
    return null
}

const getBienImageUrl = (imagePath) => {
    return `/storage/${imagePath}`
}

const showReservationActions = (paiement) => {
    return paiement.reservation_id &&
        paiement.statut === 'reussi' &&
        getBienAssocie(paiement)
}

const peutProcederVente = (paiement) => {
    const bien = getBienAssocie(paiement)
    return bien?.mandat?.type_mandat === 'vente'
}

const getDetailsRoute = (paiement) => {
    if (paiement.reservation_id) {
        return route('reservations.show', paiement.reservation_id)
    } else if (paiement.location_id) {
        return route('locations.show', paiement.location_id)
    } else if (paiement.vente_id) {
        return route('ventes.show', paiement.vente_id)
    }
    return '#'
}

const resetFilters = () => {
    filters.value = {
        statut: '',
        type: '',
        mode_paiement: ''
    }
}

const reprendrePaiement = (paiement) => {
    // Rediriger vers la page d'initiation de paiement
    const type = paiement.type
    const id = paiement.reservation_id || paiement.location_id || paiement.vente_id

    window.location.href = route('paiement.initier', {
        type: type,
        id: id,
        paiement_id: paiement.id
    })
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

.bg-light {
    background-color: #f8f9fa !important;
}

.form-select-sm {
    border-radius: 6px;
}

.text-break {
    word-break: break-word;
}
</style>
