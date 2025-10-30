<template>
    <Layout>
        <Head title="Mes Réservations" />

        <div class="container py-5">
            <!-- Messages Flash -->
            <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ $page.props.flash.success }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ $page.props.flash.error }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <!-- En-tête avec explication -->
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle mb-3"
                         style="width: 70px; height: 70px;">
                        <i class="fas fa-shield-alt text-primary fa-2x"></i>
                    </div>
                    <h1 class="h2 text-primary mb-3">Mes Réservations</h1>
                    <p class="text-muted lead mb-4">
                        Suivez l'état de vos engagements immobiliers
                    </p>

                    <!-- Encadré explicatif -->
                    <div class="card border-info shadow-sm">
                        <div class="card-body text-start">
                            <h5 class="card-title text-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Comprendre vos paiements initiaux
                            </h5>
                            <p class="mb-3">
                                Selon le type de transaction, vous devez verser un montant initial différent :
                            </p>
                            <div class="row g-3 small">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-home text-success me-2 mt-1"></i>
                                        <div>
                                            <strong>Pour une LOCATION :</strong>
                                            <br>Dépôt de garantie (caution) = 1 mois de loyer
                                            <br><span class="text-muted">Restituable en fin de bail</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-shopping-cart text-info me-2 mt-1"></i>
                                        <div>
                                            <strong>Pour un ACHAT :</strong>
                                            <br>Acompte = 10% du prix de vente
                                            <br><span class="text-muted">Déduit du prix total</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="h5 mb-0">
                        <i class="fas fa-list me-2"></i>
                        Historique de vos réservations
                        <span class="badge bg-primary rounded-pill ms-2">{{ reservations.length }}</span>
                    </h3>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button @click="filtrerReservations('tous')"
                            class="btn btn-sm"
                            :class="filtreActif === 'tous' ? 'btn-primary' : 'btn-outline-primary'">
                        <i class="fas fa-list me-1"></i>Tous
                    </button>
                    <button @click="filtrerReservations('en_attente')"
                            class="btn btn-sm"
                            :class="filtreActif === 'en_attente' ? 'btn-warning' : 'btn-outline-warning'">
                        <i class="fas fa-clock me-1"></i>En vérification
                    </button>
                    <button @click="filtrerReservations('confirmée')"
                            class="btn btn-sm"
                            :class="filtreActif === 'confirmée' ? 'btn-success' : 'btn-outline-success'">
                        <i class="fas fa-check me-1"></i>Validés
                    </button>
                    <button @click="filtrerReservations('annulée')"
                            class="btn btn-sm"
                            :class="filtreActif === 'annulée' ? 'btn-danger' : 'btn-outline-danger'">
                        <i class="fas fa-times me-1"></i>Annulés
                    </button>
                </div>
            </div>

            <!-- État vide -->
            <div v-if="reservationsFiltrees.length === 0" class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-clipboard-list text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
                <h4 class="text-muted mb-3">{{ getTitreVide() }}</h4>
                <p class="text-muted mb-4">{{ getMessageVide() }}</p>

                <div class="mt-4">
                    <Link :href="route('home')" class="btn btn-primary btn-lg">
                        <i class="fas fa-search me-2"></i>Découvrir des propriétés
                    </Link>
                </div>
            </div>

            <!-- Liste des réservations -->
            <div v-else class="row g-4">
                <div v-for="reservation in reservationsFiltrees"
                     :key="reservation.id"
                     class="col-lg-6">
                    <div class="card h-100 shadow-sm reservation-card border-0">
                        <!-- En-tête de la carte -->
                        <div class="card-header bg-gradient d-flex justify-content-between align-items-center"
                             :class="getHeaderClass(reservation.statut)">
                            <div>
                                <h6 class="mb-0 fw-bold">
                                    <i class="fas fa-clipboard-check me-2"></i>
                                    Réservation #{{ reservation.id }}
                                </h6>
                                <small class="opacity-75">
                                    <i class="fas fa-calendar me-1"></i>
                                    {{ formatDate(reservation.created_at) }}
                                </small>
                            </div>
                            <span class="badge badge-lg" :class="getStatutClass(reservation.statut)">
                                <i :class="getStatutIcon(reservation.statut)" class="me-1"></i>
                                {{ getStatutText(reservation.statut) }}
                            </span>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <!-- Image du bien -->
                                <div class="col-4">
                                    <img
                                        :src="getBienImageUrl(reservation.bien)"
                                        :alt="reservation.bien?.title || 'Image du bien'"
                                        class="img-fluid rounded shadow-sm"
                                        style="height: 120px; object-fit: cover; width: 100%;"
                                        @error="handleImageError"
                                    />
                                </div>

                                <!-- Informations -->
                                <div class="col-8">
                                    <!-- ✅ Indication IMMEUBLE vs BIEN -->
                                    <div v-if="isImmeuble(reservation)" class="mb-2">
                                        <span class="badge bg-purple-600 text-white">
                                            <i class="fas fa-building me-1"></i>
                                            IMMEUBLE
                                        </span>
                                    </div>

                                    <h6 class="card-title text-truncate mb-2 fw-bold">
                                        {{ reservation.bien?.title }}
                                    </h6>
                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                        {{ reservation.bien?.city }}
                                    </p>

                                    <!-- ✅ Affichage appartement si immeuble -->
                                    <div v-if="reservation.appartement_id && reservation.appartement"
                                         class="alert alert-info border-info mb-2 py-2 px-2 small">
                                        <i class="fas fa-door-open me-1"></i>
                                        <strong>Appartement :</strong> {{ reservation.appartement.numero }}
                                        <br>
                                        <small class="text-muted">
                                            Étage {{ reservation.appartement.etage }} •
                                            {{ reservation.appartement.nombre_pieces || reservation.appartement.pieces }} pièces
                                        </small>
                                    </div>

                                    <!-- Type d'opération -->
                                    <div class="mb-2">
                                        <span v-if="reservation.bien?.mandat?.type_mandat === 'vente'"
                                              class="badge bg-info">
                                            <i class="fas fa-shopping-cart me-1"></i>Achat
                                        </span>
                                        <span v-else-if="reservation.bien?.mandat?.type_mandat === 'gestion_locative'"
                                              class="badge bg-warning text-dark">
                                            <i class="fas fa-key me-1"></i>Location
                                        </span>
                                        <span v-else class="badge bg-secondary">
                                            <i class="fas fa-question me-1"></i>Type inconnu
                                        </span>
                                    </div>

                                    <!-- Montant versé -->
                                    <div class="reservation-info border rounded p-2 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">
                                                <i class="fas fa-money-bill-wave me-1"></i>
                                                <strong>{{ getMontantLabel(reservation.bien?.mandat?.type_mandat) }}:</strong>
                                            </small>
                                        </div>
                                        <div class="text-success fw-bold fs-6">
                                            {{ formatPrice(reservation.montant) }} FCFA
                                        </div>
                                        <small class="text-muted d-block">
                                            {{ getMontantDescription(reservation.bien?.mandat?.type_mandat) }}
                                        </small>

                                        <!-- Indicateur de paiement -->
                                        <div class="mt-2 pt-2 border-top">
                                            <small v-if="reservation.deja_payee" class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Montant payé
                                            </small>
                                            <small v-else-if="reservation.statut === 'confirmée'" class="text-warning">
                                                <i class="fas fa-clock me-1"></i>
                                                Paiement en attente
                                            </small>
                                            <small v-else class="text-muted">
                                                <i class="fas fa-hourglass-half me-1"></i>
                                                En cours de validation
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="card-footer bg-light border-0">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <!-- Bouton voir détails -->
                                <Link :href="route('reservations.show', reservation.id)"
                                      class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>Détails
                                </Link>

                                <!-- Actions conditionnelles -->
                                <div class="d-flex gap-2">
                                    <!-- Bouton paiement si confirmée et non payée -->
                                    <Link
                                        v-if="reservation.statut === 'confirmée' && !reservation.deja_payee"
                                        :href="route('reservations.initier-paiement', reservation.id)"
                                        class="btn btn-sm btn-success">
                                        <i class="fas fa-credit-card me-1"></i>Payer
                                    </Link>

                                    <!-- Boutons achat/location si payée ET pas encore créée -->
                                    <template v-if="reservation.statut === 'confirmée' && reservation.deja_payee && reservation.bien?.mandat">
                                        <!-- Bouton Acheter -->
                                        <Link
                                            v-if="reservation.bien.mandat.type_mandat === 'vente' && !reservation.vente_existe"
                                            :href="route('ventes.create', { reservation_id: reservation.id })"
                                            class="btn btn-sm btn-success">
                                            <i class="fas fa-shopping-cart me-1"></i>Finaliser l'achat
                                        </Link>

                                        <!-- Bouton Louer - ✅ CORRIGÉ pour rediriger vers locations.create -->
                                        <button
                                            v-else-if="reservation.bien.mandat.type_mandat === 'gestion_locative' && !reservation.location_existe"
                                            @click="redirectToLocationCreate(reservation.id)"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-key me-1"></i>Finaliser la location
                                        </button>

                                        <!-- Message si vente/location déjà créée -->
                                        <span
                                            v-else-if="reservation.vente_existe || reservation.location_existe"
                                            class="badge bg-info">
                                            <i class="fas fa-check me-1"></i>Transaction en cours
                                        </span>
                                    </template>

                                    <!-- Bouton annulation -->
                                    <button
                                        v-if="reservation.statut === 'en_attente' && !reservation.deja_payee"
                                        @click="annulerReservation(reservation.id)"
                                        class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-times me-1"></i>Annuler
                                    </button>
                                </div>
                            </div>

                            <!-- Messages d'aide contextuel -->
                            <div v-if="reservation.statut === 'en_attente'" class="mt-2">
                                <small class="text-muted d-block">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Vos documents sont en cours de vérification (24-48h)
                                </small>
                            </div>
                            <div v-else-if="reservation.statut === 'confirmée' && !reservation.deja_payee" class="mt-2">
                                <small class="text-warning d-block">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Documents validés - Procédez au paiement {{ getMontantTypeText(reservation.bien?.mandat?.type_mandat) }}
                                </small>
                            </div>
                            <div v-else-if="reservation.vente_existe || reservation.location_existe" class="mt-2">
                                <small class="text-info d-block">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Transaction en cours de finalisation
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script>
import Layout from '@/Pages/Layout.vue'
export default { layout: Layout }
</script>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { route } from 'ziggy-js'
import placeholderImage from '@/assets/images/hero_bg_1.jpg'

const props = defineProps({
    reservations: { type: Array, required: true }
})

const filtreActif = ref('tous')

const reservationsFiltrees = computed(() => {
    if (filtreActif.value === 'tous') {
        return props.reservations
    }
    return props.reservations.filter(r => r.statut === filtreActif.value)
})

// ✅ Vérifier si la réservation concerne un immeuble d'appartements
const isImmeuble = (reservation) => {
    return reservation.bien?.category?.name?.toLowerCase() === 'appartement' &&
        reservation.appartement_id !== null
}

// Fonctions pour les labels selon le type de mandat
const getMontantLabel = (typeMandat) => {
    if (typeMandat === 'vente') return 'Acompte versé'
    if (typeMandat === 'gestion_locative') return 'Dépôt de garantie'
    return 'Montant versé'
}

const getMontantDescription = (typeMandat) => {
    if (typeMandat === 'vente') return '10% du prix de vente'
    if (typeMandat === 'gestion_locative') return '1 mois de loyer (caution)'
    return 'Montant de réservation'
}

const getMontantTypeText = (typeMandat) => {
    if (typeMandat === 'vente') return 'de l\'acompte'
    if (typeMandat === 'gestion_locative') return 'du dépôt de garantie'
    return 'du montant'
}

const getBienImageUrl = (bien) => {
    if (bien?.images && Array.isArray(bien.images) && bien.images.length > 0) {
        const firstImage = bien.images[0]
        // Gérer les différents formats de chemin d'image
        if (firstImage.url) return firstImage.url
        if (firstImage.chemin_image) return `/storage/${firstImage.chemin_image}`
        if (firstImage.path) return `/storage/${firstImage.path}`
    }
    return placeholderImage
}

const handleImageError = (event) => {
    event.target.src = placeholderImage
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price || 0)
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatutText = (statut) => {
    const textes = {
        'en_attente': 'En vérification',
        'confirmée': 'Validé',
        'annulée': 'Annulé'
    }
    return textes[statut] || statut
}

const getStatutClass = (statut) => {
    const classes = {
        'en_attente': 'bg-warning text-dark',
        'confirmée': 'bg-success text-white',
        'annulée': 'bg-danger text-white'
    }
    return classes[statut] || 'bg-secondary'
}

const getStatutIcon = (statut) => {
    const icons = {
        'en_attente': 'fas fa-hourglass-half',
        'confirmée': 'fas fa-check-circle',
        'annulée': 'fas fa-times-circle'
    }
    return icons[statut] || 'fas fa-info'
}

const getHeaderClass = (statut) => {
    return getStatutClass(statut)
}

const getTitreVide = () => {
    const titres = {
        'tous': 'Aucune réservation',
        'en_attente': 'Aucune réservation en vérification',
        'confirmée': 'Aucune réservation validée',
        'annulée': 'Aucune réservation annulée'
    }
    return titres[filtreActif.value] || 'Aucune réservation trouvée'
}

const getMessageVide = () => {
    const messages = {
        'tous': 'Vous n\'avez pas encore effectué de réservation. Parcourez nos propriétés pour commencer.',
        'en_attente': 'Aucune réservation en attente de vérification.',
        'confirmée': 'Aucune réservation validée pour le moment.',
        'annulée': 'Vous n\'avez aucune réservation annulée.'
    }
    return messages[filtreActif.value] || 'Aucune réservation correspondant à vos critères.'
}

const filtrerReservations = (filtre) => {
    filtreActif.value = filtre
}

const annulerReservation = (id) => {
    if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ? Cette action est irréversible.')) {
        router.post(route('reservations.annuler', id), {}, {
            onSuccess: () => {
                console.log('Réservation annulée avec succès')
            }
        })
    }
}

// ✅ Redirection vers locations.create
const redirectToLocationCreate = (reservationId) => {
    const url = route('locations.create', { reservation_id: reservationId })
    console.log('🔗 Redirection vers:', url)
    window.location.href = url
}
</script>

<style scoped>
.reservation-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden;
}

.reservation-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15) !important;
}

.bg-gradient {
    background: linear-gradient(135deg, var(--bs-primary));
}

.badge-lg {
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
}

.bg-purple-600 {
    background-color: #7c3aed !important;
}
</style>
