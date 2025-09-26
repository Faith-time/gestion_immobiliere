<!-- resources/js/Pages/Paiement/Succes.vue -->
<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <!-- Icône de succès -->
                        <div class="mb-4">
                            <div class="bg-success rounded-circle mx-auto d-flex align-items-center justify-content-center"
                                 style="width: 80px; height: 80px;">
                                <i class="fas fa-check text-white fs-2"></i>
                            </div>
                        </div>

                        <!-- Titre -->
                        <h2 class="text-success mb-3">Paiement Réussi !</h2>

                        <!-- Message -->
                        <p class="text-muted mb-4">
                            Votre paiement a été traité avec succès. Vous recevrez une confirmation par email.
                        </p>

                        <!-- Détails du paiement -->
                        <div class="bg-light rounded p-4 mb-4">
                            <div class="row g-3 text-start">
                                <div class="col-md-6">
                                    <small class="text-muted">Transaction ID:</small>
                                    <div class="fw-bold">{{ paiement.transaction_id }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Montant payé:</small>
                                    <div class="fw-bold text-success">
                                        {{ formatPrice(paiement.montant_paye) }} FCFA
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Mode de paiement:</small>
                                    <div class="fw-bold">{{ getModeLabel(paiement.mode_paiement) }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Date:</small>
                                    <div class="fw-bold">{{ formatDate(paiement.date_transaction) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Informations sur le bien réservé (si applicable) -->
                        <div v-if="actionsDisponibles.bien" class="bg-info bg-opacity-10 rounded p-4 mb-4">
                            <h5 class="text-info mb-3">
                                <i class="fas fa-home me-2"></i>Bien réservé avec succès
                            </h5>
                            <div class="row text-start">
                                <div class="col-md-8">
                                    <h6 class="mb-1">{{ actionsDisponibles.bien.title }}</h6>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        {{ actionsDisponibles.bien.address }}, {{ actionsDisponibles.bien.city }}
                                    </p>
                                    <p class="text-muted mb-0">
                                        <strong>{{ formatPrice(actionsDisponibles.bien.price) }} FCFA</strong>
                                    </p>
                                </div>
                                <div class="col-md-4 d-flex align-items-center justify-content-end">
                                    <img v-if="actionsDisponibles.bien.image"
                                         :src="getBienImageUrl(actionsDisponibles.bien.image)"
                                         :alt="actionsDisponibles.bien.title"
                                         class="img-fluid rounded"
                                         style="max-height: 80px; max-width: 120px; object-fit: cover;">
                                </div>
                            </div>
                        </div>

                        <!-- Actions spécifiques après paiement réussi pour une réservation -->
                        <div v-if="showReservationActions" class="mb-4">
                            <h5 class="text-primary mb-3">Prochaines étapes</h5>
                            <div class="d-grid gap-3">
                                <!-- Bouton Visiter le bien (toujours disponible) -->
                                <button
                                    v-if="actionsDisponibles.peutVisiter"
                                    @click="planifierVisite"
                                    class="btn btn-outline-primary btn-lg d-flex align-items-center justify-content-center">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    Prendre rendez-vous pour visiter le bien
                                </button>

                                <!-- Bouton Procéder à la vente (seulement si mandat de vente) -->
                                <button
                                    v-if="actionsDisponibles.peutProcederVente"
                                    @click="procederVente"
                                    class="btn btn-success btn-lg d-flex align-items-center justify-content-center">
                                    <i class="fas fa-handshake me-2"></i>
                                    Procéder à l'achat du bien
                                </button>

                                <!-- Bouton Procéder à la location (seulement si mandat de gestion locative) -->
                                <button
                                    v-if="actionsDisponibles.peutProcederLocation"
                                    @click="procederLocation"
                                    class="btn btn-info btn-lg d-flex align-items-center justify-content-center">
                                    <i class="fas fa-key me-2"></i>
                                    Procéder à la location du bien
                                </button>

                                <!-- Information sur le type de mandat -->
                                <div class="alert alert-info mb-0" v-if="actionsDisponibles.typeMandat">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Type de mandat :</strong>
                                    <span class="text-capitalize">{{ getTypeMandatLabel(actionsDisponibles.typeMandat) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions spécifiques après paiement réussi pour une vente -->
                        <div v-if="showVenteActions" class="mb-4">
                            <h5 class="text-success mb-3">Vente Finalisée avec Succès !</h5>
                            <div class="d-grid gap-3">
                                <!-- Information sur la vente -->
                                <div class="alert alert-success mb-3" v-if="actionsDisponibles.vente">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle me-3 text-success fs-4"></i>
                                        <div>
                                            <h6 class="mb-1">Achat confirmé</h6>
                                            <p class="mb-0 small">
                                                Votre achat a été enregistré avec succès.
                                                Le contrat de vente a été généré automatiquement.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bouton pour voir les détails de la vente -->
                                <button
                                    v-if="actionsDisponibles.peutVoirVente"
                                    @click="voirVente"
                                    class="btn btn-success btn-lg d-flex align-items-center justify-content-center">
                                    <i class="fas fa-file-contract me-2"></i>
                                    Voir les détails de mon achat et signer le contrat
                                </button>
                            </div>
                        </div>
                        <!-- Actions standards -->
                        <div class="d-grid gap-2">
                            <Link href="/" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Retour à l'accueil
                            </Link>
                            <Link :href="getDetailsRoute()" class="btn btn-outline-secondary">
                                <i class="fas fa-eye me-2"></i>Voir les détails de ma réservation
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {Link, router} from '@inertiajs/vue3'
import { computed } from 'vue'
import {route} from "ziggy-js";

const props = defineProps({
    paiement: { type: Object, required: true },
    actionsDisponibles: { type: Object, required: true }
})

// Vérifier si on doit afficher les actions de réservation
// Vérifier si on doit afficher les actions de réservation
const showReservationActions = computed(() => {
    return props.paiement.reservation_id &&
        props.paiement.statut === 'reussi' &&
        (props.actionsDisponibles.peutVisiter ||
            props.actionsDisponibles.peutProcederVente ||
            props.actionsDisponibles.peutProcederLocation)  // AJOUT
})

// AJOUT : Nouvelle méthode pour la location
const procederLocation = () => {
    if (props.actionsDisponibles.bien && props.actionsDisponibles.bien.id) {
        router.visit(route('locations.create', { bien_id: props.actionsDisponibles.bien.id }))
    } else {
        console.error("Aucun bien sélectionné")
    }
}

const procederVente = () => {
    if (props.actionsDisponibles.bien && props.actionsDisponibles.bien.id) {
         router.visit(route('ventes.create', { bien_id: props.actionsDisponibles.bien.id }))
    } else {
        console.error("Aucun bien sélectionné")
    }
}
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

const getModeLabel = (mode) => {
    const labels = {
        'mobile_money': 'Mobile Money',
        'carte': 'Carte bancaire',
        'virement': 'Virement bancaire'
    }
    return labels[mode] || mode
}

const getTypeMandatLabel = (type) => {
    const labels = {
        'vente': 'Mandat de vente',
        'gestion_locative': 'Mandat de gestion locative'
    }
    return labels[type] || type
}

const getBienImageUrl = (imagePath) => {
    return `/storage/${imagePath}`
}

const getDetailsRoute = () => {
    if (props.paiement.reservation_id) {
        return route('reservations.show', props.paiement.reservation_id)
    } else if (props.paiement.location_id) {
        return route('locations.show', props.paiement.location_id)
    } else if (props.paiement.vente_id) {
        return route('ventes.show', props.paiement.vente_id)
    }
    return route('dashboard')
}

const planifierVisite = () => {
    if (props.actionsDisponibles.bien) {
        router.visit(route('visites.create', { bien_id: props.actionsDisponibles.bien.id }))
    }
}

// Vérifier si on doit afficher les actions de vente
const showVenteActions = computed(() => {
    return props.paiement.vente_id &&
        props.paiement.statut === 'reussi' &&
        props.actionsDisponibles.peutVoirVente
})

// Nouvelle méthode pour la vente
const voirVente = () => {
    if (props.actionsDisponibles.vente && props.actionsDisponibles.vente.id) {
        router.visit(route('ventes.show', props.actionsDisponibles.vente.id))
    } else {
        console.error("Aucune vente trouvée")
    }
}

</script>

<style scoped>
.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
}

.card {
    border-radius: 15px;
}

.bg-success {
    background-color: #198754 !important;
}

.text-success {
    color: #198754 !important;
}

.alert-info {
    border-radius: 10px;
    border: none;
}

.bg-info {
    background-color: #0dcaf0 !important;
}

.bg-light {
    background-color: #f8f9fa !important;
}
</style>
