<template>
    <div class="container py-5">
        <!-- ✅ AJOUT : Messages Flash -->
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

        <div v-if="$page.props.flash?.warning" class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ $page.props.flash.warning }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 text-primary">Mes réservations</h1>
                <p class="text-muted">Gérez toutes vos réservations de propriétés</p>
            </div>
            <div class="d-flex gap-2">
                <button @click="filtrerReservations('tous')"
                        class="btn btn-sm"
                        :class="filtreActif === 'tous' ? 'btn-primary' : 'btn-outline-primary'">
                    Toutes
                </button>
                <button @click="filtrerReservations('en_attente')"
                        class="btn btn-sm"
                        :class="filtreActif === 'en_attente' ? 'btn-warning' : 'btn-outline-warning'">
                    En attente
                </button>
                <button @click="filtrerReservations('confirmée')"
                        class="btn btn-sm"
                        :class="filtreActif === 'confirmée' ? 'btn-success' : 'btn-outline-success'">
                    Confirmées
                </button>
                <button @click="filtrerReservations('annulée')"
                        class="btn btn-sm"
                        :class="filtreActif === 'annulée' ? 'btn-danger' : 'btn-outline-danger'">
                    Annulées
                </button>
            </div>
        </div>

        <!-- Liste des réservations -->
        <div v-if="reservationsFiltrees.length === 0" class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-calendar-times text-muted" style="font-size: 4rem;"></i>
            </div>
            <h4 class="text-muted">Aucune réservation trouvée</h4>
            <p class="text-muted">{{ getMessageVide() }}</p>
            <Link :href="route('biens.index')" class="btn btn-primary">
                <i class="fas fa-search me-2"></i>Découvrir des propriétés
            </Link>
        </div>

        <div v-else class="row g-4">
            <div v-for="reservation in reservationsFiltrees"
                 :key="reservation.id"
                 class="col-lg-6">
                <div class="card h-100 shadow-sm reservation-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-primary">
                                Réservation #{{ reservation.id }}
                            </h6>
                            <small class="text-muted">
                                {{ formatDate(reservation.created_at) }}
                            </small>
                        </div>
                        <span class="badge" :class="getStatutClass(reservation.statut)">
                            <i :class="getStatutIcon(reservation.statut)" class="me-1"></i>
                            {{ getStatutText(reservation.statut) }}
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-4">
                                <img :src="reservation.bien?.image ? `/storage/${reservation.bien.image}` : '/images/placeholder.jpg'"
                                     :alt="reservation.bien?.title"
                                     class="img-fluid rounded">
                            </div>
                            <div class="col-8">
                                <h6 class="card-title text-truncate">{{ reservation.bien?.title }}</h6>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    {{ reservation.bien?.city }}
                                </p>

                                <div class="reservation-info">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small><strong>Caution:</strong></small>
                                        <small class="text-success fw-bold">
                                            {{ formatPrice(reservation.montant) }} FCFA
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <small><strong>Date:</strong></small>
                                        <small>{{ formatDateCourt(reservation.date_reservation) }}</small>
                                    </div>
                                    <!-- NOUVEAU: Afficher le type de mandat -->
                                    <div v-if="reservation.bien?.mandat" class="d-flex justify-content-between mb-1">
                                        <small><strong>Type:</strong></small>
                                        <small>
                                            <span v-if="reservation.bien.mandat.type_mandat === 'vente'" class="badge bg-info">
                                                <i class="fas fa-home me-1"></i>Vente
                                            </span>
                                            <span v-else class="badge bg-warning text-dark">
                                                <i class="fas fa-key me-1"></i>Location
                                            </span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light d-flex justify-content-between flex-wrap gap-2">
                        <Link :href="route('reservations.show', reservation.id)"
                              class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye me-1"></i>Voir détails
                        </Link>

                        <div class="btn-group" role="group">
                            <!-- ✅ NOUVEAU: Boutons conditionnels selon le type de mandat ET le statut confirmée -->
                            <template v-if="reservation.statut === 'confirmée' && reservation.bien?.mandat">
                                <!-- Bouton Achat pour mandat de vente -->
                                <Link v-if="reservation.bien.mandat.type_mandat === 'vente'"
                                      :href="route('ventes.create', { bien_id: reservation.bien.id })"
                                      class="btn btn-sm btn-success">
                                    <i class="fas fa-shopping-cart me-1"></i>Procéder à l'achat
                                </Link>

                                <!-- Bouton Location pour mandat de gestion locative -->
                                <Link v-else-if="reservation.bien.mandat.type_mandat === 'gestion_locative'"
                                      :href="route('locations.create', { bien_id: reservation.bien.id })"
                                      class="btn btn-sm btn-primary">
                                    <i class="fas fa-key me-1"></i>Procéder à la location
                                </Link>
                            </template>

                            <!-- Bouton paiement si en_attente, documents validés ET pas encore payée -->
                            <Link v-if="reservation.statut === 'en_attente' && peutPayer(reservation) && !reservation.deja_payee"
                                  :href="route('reservations.initier-paiement', reservation.id)"
                                  class="btn btn-sm btn-success">
                                <i class="fas fa-credit-card me-1"></i>Payer
                            </Link>

                            <!-- Message si déjà payée -->
                            <span v-else-if="reservation.deja_payee"
                                  class="btn btn-sm btn-outline-success disabled"
                                  title="Paiement déjà effectué">
                                <i class="fas fa-check-circle me-1"></i>Payée
                            </span>

                            <!-- Message si documents non validés -->
                            <span v-else-if="reservation.statut === 'en_attente' && !peutPayer(reservation)"
                                  class="btn btn-sm btn-outline-warning disabled"
                                  title="En attente de validation des documents">
                                <i class="fas fa-clock me-1"></i>Documents en cours
                            </span>

                            <!-- Bouton édition si en attente et pas encore payée -->
                            <Link v-if="reservation.statut === 'en_attente' && !reservation.deja_payee"
                                  :href="route('reservations.edit', reservation.id)"
                                  class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </Link>

                            <!-- Bouton annulation si en attente et pas encore payée -->
                            <button v-if="reservation.statut === 'en_attente' && !reservation.deja_payee"
                                    @click="annulerReservation(reservation.id)"
                                    class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-times me-1"></i>Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination si nécessaire -->
        <div v-if="reservations.length > 12" class="mt-5 d-flex justify-content-center">
            <nav>
                <ul class="pagination">
                    <li class="page-item">
                        <button class="page-link">Précédent</button>
                    </li>
                    <li class="page-item active">
                        <button class="page-link">1</button>
                    </li>
                    <li class="page-item">
                        <button class="page-link">Suivant</button>
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
import { Link } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    reservations: { type: Array, required: true }
})

// État réactif
const filtreActif = ref('tous')

// Computed
const reservationsFiltrees = computed(() => {
    if (filtreActif.value === 'tous') {
        return props.reservations
    }
    return props.reservations.filter(r => r.statut === filtreActif.value)
})

// Méthodes
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatDateCourt = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

const getStatutText = (statut) => {
    const textes = {
        'en_attente': 'En attente',
        'confirmée': 'Confirmée',
        'annulée': 'Annulée'
    }
    return textes[statut] || statut
}

const getStatutClass = (statut) => {
    const classes = {
        'en_attente': 'bg-warning text-dark',
        'confirmée': 'bg-success',
        'annulée': 'bg-danger'
    }
    return classes[statut] || 'bg-secondary'
}

const getStatutIcon = (statut) => {
    const icons = {
        'en_attente': 'fas fa-clock',
        'confirmée': 'fas fa-check-circle',
        'annulee': 'fas fa-times'
    }
    return icons[statut] || 'fas fa-info'
}

const getMessageVide = () => {
    const messages = {
        'tous': 'Vous n\'avez encore aucune réservation.',
        'en_attente': 'Aucune réservation en attente.',
        'confirmée': 'Aucune réservation confirmée.',
        'annulée': 'Aucune réservation annulée.'
    }
    return messages[filtreActif.value] || 'Aucune réservation trouvée.'
}

const filtrerReservations = (filtre) => {
    filtreActif.value = filtre
}

const peutPayer = (reservation) => {
    return reservation.documents_valides === true || reservation.documents_valides === 1
}

const annulerReservation = (id) => {
    if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
        router.post(route('reservations.annuler', id), {}, {
            onSuccess: () => {
                // Succès géré automatiquement par Inertia
            }
        })
    }
}
</script>

<style scoped>
.reservation-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.reservation-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.reservation-info {
    font-size: 0.9rem;
}

.btn-group .btn {
    border-radius: 0.25rem;
    margin-left: 0.25rem;
}

.btn-group .btn:first-child {
    margin-left: 0;
}

.disabled {
    pointer-events: none;
    opacity: 0.6;
}

@media (max-width: 768px) {
    .card-footer {
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
    }

    .btn-group {
        width: 100%;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .btn-group .btn,
    .btn-group a {
        flex: 1 1 auto;
        min-width: fit-content;
    }
}
</style>
