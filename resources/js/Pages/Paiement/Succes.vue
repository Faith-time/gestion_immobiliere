<template>
    <Layout title="Paiement Réussi">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center py-5">
                            <!-- Icône de succès ou partiellement payé -->
                            <div class="mb-4">
                                <div :class="[
                                    'rounded-circle mx-auto d-flex align-items-center justify-content-center',
                                    estPartiellementPaye ? 'bg-warning' : 'bg-success'
                                ]" style="width: 80px; height: 80px;">
                                    <i :class="[
                                        'text-white fs-2',
                                        estPartiellementPaye ? 'fas fa-hourglass-half' : 'fas fa-check'
                                    ]"></i>
                                </div>
                            </div>

                            <!-- Titre -->
                            <h2 :class="estPartiellementPaye ? 'text-warning' : 'text-success'" class="mb-3">
                                {{ estPartiellementPaye ? 'Paiement Partiel Réussi !' : 'Paiement Complété !' }}
                            </h2>

                            <!-- Message -->
                            <p class="text-muted mb-4">
                                {{ estPartiellementPaye
                                ? 'Votre paiement partiel a été traité avec succès. Continuez avec les tranches suivantes.'
                                : 'Votre paiement a été complété avec succès. Vous recevrez une confirmation par email.'
                                }}
                            </p>

                            <!-- Détails du paiement -->
                            <div class="bg-light rounded p-4 mb-4">
                                <div class="row g-3 text-start">
                                    <div class="col-md-6">
                                        <small class="text-muted">Transaction ID:</small>
                                        <div class="fw-bold">{{ paiement.transaction_id || 'En attente' }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Mode de paiement:</small>
                                        <div class="fw-bold">{{ getModeLabel(paiement.mode_paiement) }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Montant total:</small>
                                        <div class="fw-bold">{{ formatPrice(paiement.montant_total) }} FCFA</div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Montant déjà payé:</small>
                                        <div :class="[
                                            'fw-bold',
                                            estPartiellementPaye ? 'text-warning' : 'text-success'
                                        ]">
                                            {{ formatPrice(paiement.montant_paye) }} FCFA
                                        </div>
                                    </div>
                                    <div v-if="estPartiellementPaye" class="col-md-6">
                                        <small class="text-muted">Montant restant:</small>
                                        <div class="fw-bold text-danger">
                                            {{ formatPrice(paiement.montant_restant) }} FCFA
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Date:</small>
                                        <div class="fw-bold">{{ formatDate(paiement.date_transaction) }}</div>
                                    </div>
                                </div>

                                <!-- Barre de progression -->
                                <div v-if="estPartiellementPaye && infoFractionnement" class="mt-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <small class="text-muted">Progression du paiement</small>
                                        <small class="fw-bold">{{ Math.round(infoFractionnement.pourcentage_paye) }}%</small>
                                    </div>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated"
                                             :style="`width: ${infoFractionnement.pourcentage_paye}%`"
                                             role="progressbar">
                                            {{ Math.round(infoFractionnement.pourcentage_paye) }}%
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Alerte paiement partiel -->
                            <div v-if="estPartiellementPaye && infoFractionnement" class="alert alert-warning mb-4">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-exclamation-triangle me-3 mt-1 fs-4"></i>
                                    <div class="text-start">
                                        <h6 class="mb-2">Paiement en plusieurs tranches</h6>
                                        <p class="mb-2">
                                            Il vous reste <strong>{{ formatPrice(infoFractionnement.montant_restant) }} FCFA</strong> à payer.
                                        </p>
                                        <p class="mb-0 small">
                                            Vous devez effectuer <strong>{{ infoFractionnement.nombre_tranches_restantes }}</strong> tranche(s) supplémentaire(s)
                                            pour compléter votre paiement.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Détails des tranches restantes -->
                            <div v-if="estPartiellementPaye && infoFractionnement" class="bg-light rounded p-4 mb-4">
                                <h6 class="text-start mb-3">
                                    <i class="fas fa-list-ol me-2"></i>Tranches restantes à payer
                                </h6>
                                <div class="list-group">
                                    <div v-for="tranche in infoFractionnement.tranches"
                                         :key="tranche.numero"
                                         class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-circle-notch me-2 text-warning"></i>
                                            Tranche {{ tranche.numero }}
                                        </span>
                                        <span class="badge bg-warning text-dark">
                                            {{ formatPrice(tranche.montant) }} FCFA
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Bouton pour continuer le paiement -->
                            <div v-if="estPartiellementPaye" class="mb-4">
                                <button @click="continuerPaiement"
                                        class="btn btn-warning btn-lg w-100 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Payer la tranche suivante ({{ formatPrice(infoFractionnement.tranches[0].montant) }} FCFA)
                                </button>
                                <p class="text-muted small mt-2 mb-0">
                                    Vous pouvez payer les tranches restantes maintenant ou plus tard
                                </p>
                            </div>

                            <!-- Informations sur le bien réservé -->
                            <div v-if="actionsDisponibles.bien" class="bg-info bg-opacity-10 rounded p-4 mb-4">
                                <h5 class="text-info mb-3">
                                    <i class="fas fa-home me-2"></i>
                                    {{ estPartiellementPaye ? 'Bien en cours de réservation' : 'Bien réservé avec succès' }}
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

                            <!-- Actions uniquement si paiement COMPLET -->
                            <template v-if="!estPartiellementPaye">
                                <!-- Actions après paiement de réservation -->
                                <div v-if="showReservationActions" class="mb-4">
                                    <h5 class="text-primary mb-3">Prochaines étapes</h5>
                                    <div class="d-grid gap-3">
                                        <button v-if="actionsDisponibles.peutProcederVente"
                                                @click="procederVente"
                                                class="btn btn-success btn-lg">
                                            <i class="fas fa-handshake me-2"></i>
                                            Procéder à l'achat du bien
                                        </button>

                                        <button v-if="actionsDisponibles.peutProcederLocation"
                                                @click="procederLocation"
                                                class="btn btn-info btn-lg">
                                            <i class="fas fa-key me-2"></i>
                                            Procéder à la location du bien
                                        </button>

                                        <div class="alert alert-info mb-0" v-if="actionsDisponibles.typeMandat">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Type de mandat :</strong>
                                            <span class="text-capitalize">{{ getTypeMandatLabel(actionsDisponibles.typeMandat) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions après paiement de vente -->
                                <div v-if="showVenteActions" class="mb-4">
                                    <h5 class="text-success mb-3">Vente Finalisée avec Succès !</h5>
                                    <div class="d-grid gap-3">
                                        <div class="alert alert-success mb-3" v-if="actionsDisponibles.vente">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-check-circle me-3 text-success fs-4"></i>
                                                <div class="text-start">
                                                    <h6 class="mb-1">Achat confirmé</h6>
                                                    <p class="mb-0 small">
                                                        Votre achat a été enregistré avec succès.
                                                        Le contrat de vente a été généré automatiquement.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <button v-if="actionsDisponibles.peutVoirVente"
                                                @click="voirVente"
                                                class="btn btn-success btn-lg">
                                            <i class="fas fa-file-contract me-2"></i>
                                            Voir les détails de mon achat et signer le contrat
                                        </button>
                                    </div>
                                </div>

                                <!-- Actions après paiement de location -->
                                <div v-if="showLocationActions" class="mb-4">
                                    <h5 class="text-info mb-3">Location Confirmée !</h5>
                                    <div class="d-grid gap-3">
                                        <div class="alert alert-info mb-3" v-if="actionsDisponibles.location">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-check-circle me-3 text-info fs-4"></i>
                                                <div class="text-start">
                                                    <h6 class="mb-1">Location activée</h6>
                                                    <p class="mb-0 small">
                                                        Votre location a été confirmée.
                                                        Le contrat de location est disponible.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <button v-if="actionsDisponibles.peutVoirLocation"
                                                @click="voirLocation"
                                                class="btn btn-info btn-lg">
                                            <i class="fas fa-file-contract me-2"></i>
                                            Voir les détails de ma location
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Actions standards -->
                            <div class="d-grid gap-2">
                                <Link href="/" class="btn btn-primary">
                                    <i class="fas fa-home me-2"></i>Retour à l'accueil
                                </Link>
                                <Link v-if="!estPartiellementPaye" :href="getDetailsRoute()" class="btn btn-outline-secondary">
                                    <i class="fas fa-eye me-2"></i>Voir les détails
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'
import Layout from '../Layout.vue'

const props = defineProps({
    paiement: { type: Object, required: true },
    actionsDisponibles: { type: Object, required: true },
    estPartiellementPaye: { type: Boolean, default: false },
    infoFractionnement: { type: Object, default: null }
})

const showReservationActions = computed(() => {
    return !props.estPartiellementPaye &&
        props.paiement.reservation_id &&
        props.paiement.statut === 'reussi' &&
        (props.actionsDisponibles.peutProcederVente ||
            props.actionsDisponibles.peutProcederLocation)
})

const showVenteActions = computed(() => {
    return !props.estPartiellementPaye &&
        props.paiement.vente_id &&
        props.paiement.statut === 'reussi' &&
        props.actionsDisponibles.peutVoirVente
})

const showLocationActions = computed(() => {
    return !props.estPartiellementPaye &&
        props.paiement.location_id &&
        props.paiement.statut === 'reussi' &&
        props.actionsDisponibles.peutVoirLocation
})

const continuerPaiement = () => {
    const id = props.paiement.reservation_id ||
        props.paiement.vente_id ||
        props.paiement.location_id

    console.log('=== CONTINUER PAIEMENT ===', {
        id: id,
        paiement_id: props.paiement.id,
        type: props.paiement.type
    })

    router.visit(route('paiement.initier.show', {
        id: id,
        paiement_id: props.paiement.id
    }))
}

const procederLocation = () => {
    if (props.actionsDisponibles.bien?.id) {
        router.visit(route('locations.create', { bien_id: props.actionsDisponibles.bien.id }))
    }
}

const procederVente = () => {
    if (props.actionsDisponibles.bien?.id) {
        router.visit(route('ventes.create', { bien_id: props.actionsDisponibles.bien.id }))
    }
}

const voirVente = () => {
    if (props.actionsDisponibles.vente?.id) {
        router.visit(route('ventes.show', props.actionsDisponibles.vente.id))
    }
}

const voirLocation = () => {
    if (props.actionsDisponibles.location?.id) {
        // router.visit(route('locations.show', props.actionsDisponibles.location.id))
    }
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
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
        'wave': 'Wave',
        'orange_money': 'Orange Money',
        'mtn_money': 'MTN Money',
        'moov_money': 'Moov Money',
        'carte': 'Carte bancaire',
        'virement': 'Virement bancaire'
    }
    return labels[mode] || mode || 'En attente'
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
</script>

<style scoped>
.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
}

.card {
    border-radius: 15px;
}

.progress {
    border-radius: 10px;
}

.progress-bar {
    font-weight: bold;
}

.list-group-item {
    border-radius: 8px !important;
    margin-bottom: 8px;
}

.alert {
    border-radius: 10px;
    border: none;
}

.bg-info.bg-opacity-10 {
    background-color: rgba(13, 202, 240, 0.1) !important;
}
</style>
