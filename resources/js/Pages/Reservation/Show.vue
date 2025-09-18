<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- En-tête avec statut -->
                <div class="text-center mb-4">
                    <h1 class="h2 text-primary">Détails de la réservation #{{ reservation.id }}</h1>
                    <div class="status-badge mt-3">
                        <span class="badge fs-6 px-3 py-2" :class="getStatusClass(reservation.statut)">
                            <i :class="getStatusIcon(reservation.statut)" class="me-2"></i>
                            {{ getStatusText(reservation.statut) }}
                        </span>
                    </div>
                </div>

                <!-- Informations de réservation -->
                <div class="card mb-4 shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Informations de réservation
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">Détails client</h6>
                                <div class="mb-2">
                                    <strong>Client:</strong> {{ reservation.client?.name || 'Non spécifié' }}
                                </div>
                                <div class="mb-2">
                                    <strong>Email:</strong> {{ reservation.client?.email || 'Non spécifié' }}
                                </div>
                                <div class="mb-2">
                                    <strong>ID Client:</strong> #{{ reservation.client_id }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary">Détails de réservation</h6>
                                <div class="mb-2">
                                    <strong>Date de réservation:</strong> {{ formatDate(reservation.date_reservation) }}
                                </div>
                                <div class="mb-2">
                                    <strong>Montant caution:</strong>
                                    <span class="text-success fw-bold">{{ formatPrice(reservation.montant) }} FCFA</span>
                                </div>
                                <div class="mb-2">
                                    <strong>Créée le:</strong> {{ formatDate(reservation.created_at) }}
                                </div>
                                <div class="mb-2">
                                    <strong>Statut:</strong>
                                    <span class="badge" :class="getStatusClass(reservation.statut)">
                                        {{ getStatusText(reservation.statut) }}
                                    </span>
                                </div>
                                <div class="mb-2" v-if="reservation.paiement_id">
                                    <strong>Paiement ID:</strong> #{{ reservation.paiement_id }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations du bien -->
                <div class="card mb-4 shadow">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-home me-2"></i>
                            Propriété réservée
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <img
                                    :src="reservation.bien?.image ? `/storage/${reservation.bien.image}` : '/images/placeholder.jpg'"
                                    :alt="reservation.bien?.title"
                                    class="img-fluid rounded"
                                />
                            </div>
                            <div class="col-md-8">
                                <h5 class="text-primary">{{ reservation.bien?.title }}</h5>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    {{ reservation.bien?.address }}, {{ reservation.bien?.city }}
                                </p>
                                <div class="row g-2 mb-3">
                                    <div class="col-auto">
                                        <small class="badge bg-light text-dark">
                                            <i class="fas fa-bed me-1"></i>{{ reservation.bien?.rooms }} chambres
                                        </small>
                                    </div>
                                    <div class="col-auto">
                                        <small class="badge bg-light text-dark">
                                            <i class="fas fa-bath me-1"></i>{{ reservation.bien?.bathrooms }} SDB
                                        </small>
                                    </div>
                                    <div class="col-auto">
                                        <small class="badge bg-light text-dark">
                                            <i class="fas fa-ruler-combined me-1"></i>{{ reservation.bien?.superficy }} m²
                                        </small>
                                    </div>
                                </div>
                                <div class="h4 text-success mb-0">{{ formatPrice(reservation.bien?.price) }} FCFA</div>
                                <div class="mt-2">
                                    <Link :href="route('biens.show', reservation.bien_id)"
                                          class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>Voir la propriété
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions selon le statut -->
                <div class="card shadow">
                    <div class="card-body text-center">
                        <!-- Réservation en attente -->
                        <div v-if="reservation.statut === 'en_attente'" class="py-4">
                            <div class="mb-3">
                                <i class="fas fa-clock text-warning" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="text-warning">Réservation en cours</h5>
                            <p class="text-muted mb-4">
                                Votre réservation est en cours de traitement. Notre équipe va la valider sous peu.
                            </p>
                            <div class="d-flex gap-2 justify-content-center">
                                <Link :href="route('reservations.edit', reservation.id)"
                                      class="btn btn-outline-primary">
                                    <i class="fas fa-edit me-2"></i>Modifier
                                </Link>
                                <button @click="annulerReservation"
                                        class="btn btn-outline-danger">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </button>
                            </div>
                        </div>

                        <!-- Réservation validée - Paiement en attente -->
                        <div v-else-if="reservation.statut === 'confirmée'" class="py-4">
                            <div class="mb-3">
                                <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="text-success">Réservation validée</h5>
                            <p class="text-muted mb-4">
                                Votre réservation a été validée.
                                Vous pouvez maintenant payer la caution pour confirmer votre réservation.
                            </p>

                            <div class="payment-info bg-light p-4 rounded mb-4">
                                <h6 class="text-primary">Informations de paiement</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Montant à payer:</strong><br>
                                        <span class="h5 text-success">{{ formatPrice(reservation.montant) }} FCFA</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Type:</strong> Caution de réservation<br>
                                        <small class="text-muted">Montant fixe</small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 justify-content-center">
                                <Link :href="route('reservations.initier-paiement', reservation.id)"
                                      class="btn btn-success btn-lg px-5">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Payer la caution maintenant
                                </Link>
                                <button @click="annulerReservation"
                                        class="btn btn-outline-danger">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </button>
                            </div>
                        </div>

                        <!-- Réservation confirmée - NOUVEAU: Bouton de paiement -->
                        <div v-else-if="reservation.statut === 'confirmée' || reservation.statut === 'confirmee'" class="py-4">
                            <div class="mb-3">
                                <i class="fas fa-trophy text-warning" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="text-success">Réservation confirmée</h5>

                            <!-- Si pas encore payé -->
                            <div v-if="!reservation.paiement_id || (paiement && paiement.statut !== 'termine')" class="mb-4">
                                <p class="text-muted mb-4">
                                    Votre réservation est confirmée. Vous pouvez maintenant procéder au paiement de la caution.
                                </p>

                                <div class="payment-info bg-light p-4 rounded mb-4">
                                    <h6 class="text-primary">Informations de paiement</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Montant à payer:</strong><br>
                                            <span class="h5 text-success">{{ formatPrice(reservation.montant) }} FCFA</span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Type:</strong> Caution de réservation<br>
                                            <small class="text-muted">Paiement sécurisé avec CinetPay</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 justify-content-center">
                                    <Link :href="route('reservations.initier-paiement', reservation.id)"
                                          class="btn btn-success btn-lg px-5"
                                          :disabled="processing">
                                        <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="fas fa-credit-card me-2"></i>
                                        {{ processing ? 'Traitement...' : 'Payer la Réservation' }}
                                    </Link>
                                </div>

                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Paiement sécurisé:</strong> Vous serez redirigé vers notre plateforme de paiement sécurisée CinetPay.
                                </div>
                            </div>

                            <!-- Si déjà payé -->
                            <div v-else class="mb-4">
                                <p class="text-muted mb-4">
                                    Votre réservation est confirmée et votre paiement a été effectué avec succès.
                                    Notre équipe va vous contacter prochainement.
                                </p>

                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Paiement effectué:</strong> {{ formatPrice(reservation.montant) }} FCFA
                                    <div v-if="reservation.paiement_id" class="small mt-1">
                                        Référence: #{{ reservation.paiement_id }}
                                    </div>
                                </div>

                                <div class="d-flex gap-2 justify-content-center">
                                    <Link :href="route('paiement.show', reservation.paiement_id)"
                                          class="btn btn-outline-info">
                                        <i class="fas fa-receipt me-2"></i>Voir le reçu
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Réservation annulée -->
                        <div v-else-if="reservation.statut === 'annulée'" class="py-4">
                            <div class="mb-3">
                                <i class="fas fa-times-circle text-danger" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="text-danger">Réservation annulée</h5>
                            <p class="text-muted">
                                Cette réservation a été annulée. Si vous avez des questions, n'hésitez pas à nous contacter.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="text-center mt-4">
                    <Link :href="route('reservations.index')"
                          class="btn btn-outline-primary me-3">
                        <i class="fas fa-list me-2"></i>Mes réservations
                    </Link>
                    <Link :href="route('biens.index')"
                          class="btn btn-outline-secondary">
                        <i class="fas fa-home me-2"></i>Retour à l'accueil
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Layout from '@/Pages/Layout.vue'

export default {
    layout: Layout
}
</script>

<script setup>
import { Link } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'

// Props
const props = defineProps({
    reservation: {
        type: Object,
        required: true
    },
    paiement: {
        type: Object,
        default: null
    }
})

// État réactif
const processing = ref(false)

// Méthodes utilitaires
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const formatDate = (dateString) => {
    if (!dateString) return 'Non spécifié'
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatusText = (status) => {
    const statusTexts = {
        'en_attente': 'Réservation en cours',
        'confirmee': 'Réservation confirmée',
        'confirmée': 'Réservation confirmée',
        'annulée': 'Réservation annulée'
    }
    return statusTexts[status] || status
}

const getStatusClass = (status) => {
    const statusClasses = {
        'en_attente': 'bg-warning text-dark',
        'confirmée': 'bg-primary',
        'annulée': 'bg-danger'
    }
    return statusClasses[status] || 'bg-secondary'
}

const getStatusIcon = (status) => {
    const statusIcons = {
        'en_attente': 'fas fa-clock',
        'confirmée': 'fas fa-trophy',
        'annulée': 'fas fa-times-circle'
    }
    return statusIcons[status] || 'fas fa-question-circle'
}

const annulerReservation = () => {
    if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
        processing.value = true
        router.post(route('reservations.annuler', props.reservation.id), {}, {
            onFinish: () => {
                processing.value = false
            }
        })
    }
}
</script>

<style scoped>
.status-badge {
    display: inline-block;
}

.card {
    border: none;
    border-radius: 12px;
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
}

.payment-info {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

.payment-success {
    border-left: 4px solid #28a745;
}

.btn:disabled {
    opacity: 0.6;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
</style>
