<template>
    <!-- 🔍 DEBUG - À RETIRER APRÈS -->
    <div v-if="!reservation || !bien || !bien.price" class="alert alert-warning m-4">
        <h4>Debug Info:</h4>
        <pre>{{ {
            has_reservation: !!reservation,
            reservation_id: reservation?.id,
            reservation_appartement_id: reservation?.appartement_id,
            form_reservation_id: form.reservation_id,
            form_appartement_id: form.appartement_id,
            isImmeuble: isImmeuble,
            bien_id: bien?.id
        } }}</pre>
    </div>

    <div v-if="!reservation || !bien || !bien.price" class="container py-5 text-center">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Chargement...</span>
        </div>
        <p class="text-muted">Chargement des informations...</p>
    </div>

    <div class="py-5">
        <!-- Messages Flash -->
        <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show mb-4">
            <i class="fas fa-check-circle me-2"></i>{{ $page.props.flash.success }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show mb-4">
            <i class="fas fa-exclamation-circle me-2"></i>{{ $page.props.flash.error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- En-tête -->
                <div class="text-center mb-5">
                    <h1 class="display-5 text-primary fw-bold">Demande de Location</h1>
                    <p class="text-muted fs-5">Finalisez votre demande de location pour cette propriété</p>
                </div>

                <!-- Récapitulatif du bien -->
                <div class="card mb-4 shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="row align-items-center g-4">
                            <div class="col-md-4">
                                <img
                                    :src="bien.image ? `/storage/${bien.image}` : '/images/placeholder.jpg'"
                                    :alt="bien.title"
                                    class="img-fluid rounded shadow-sm"
                                    loading="lazy"
                                    style="width: 100%; height: 200px; object-fit: cover;"
                                />
                            </div>
                            <div class="col-md-8">
                                <h4 class="card-title text-primary fw-bold mb-3">{{ bien.title }}</h4>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                    <strong>{{ bien.address }}, {{ bien.city }}</strong>
                                </p>
                                <p class="text-muted mb-3">
                                    <i class="fas fa-home me-2 text-info"></i>
                                    {{ bien.category?.name }}
                                </p>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div>
                                        <span class="h4 text-success mb-0 fw-bold">
                                        {{ formatPrice(bien?.price || 0) }} FCFA                                        </span>
                                        <small class="text-muted d-block">/mois</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Encadré explicatif du paiement initial -->
                <div class="card mb-4 shadow-lg border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body p-4 text-white">
                        <div class="d-flex align-items-start">
                            <div class="me-4">
                                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                    <i class="fas fa-info-circle fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h4 class="fw-bold mb-3">
                                    <i class="fas fa-calculator me-2"></i>
                                    Comprendre votre paiement initial
                                </h4>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="bg-white bg-opacity-10 rounded p-3 backdrop-blur">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-check-circle me-2 text-success"></i>
                                                <strong>Dépôt de garantie</strong>
                                            </div>
                                            <p class="mb-1">{{ formatPrice(bien.price) }} FCFA</p>
                                            <small class="opacity-75">
                                                <i class="fas fa-shield-alt me-1"></i>Déjà payé lors de la réservation ✓
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bg-white bg-opacity-20 rounded p-3 border border-white border-2">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-plus-circle me-2 text-warning"></i>
                                                <strong>Caution locative</strong>
                                            </div>
                                            <p class="mb-1 text-warning fw-bold">{{ formatPrice(bien.price) }} FCFA</p>
                                            <small class="opacity-75">
                                                <i class="fas fa-hand-holding-usd me-1"></i>À payer maintenant
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bg-white bg-opacity-20 rounded p-3 border border-white border-2">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-plus-circle me-2 text-warning"></i>
                                                <strong>1er mois de loyer</strong>
                                            </div>
                                            <p class="mb-1 text-warning fw-bold">{{ formatPrice(bien.price) }} FCFA</p>
                                            <small class="opacity-75">
                                                <i class="fas fa-calendar-check me-1"></i>À payer maintenant
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 p-3 bg-white bg-opacity-20 rounded border border-white border-2">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5 class="mb-2">
                                                <i class="fas fa-calculator me-2"></i>
                                                Montant total à payer aujourd'hui :
                                            </h5>
                                            <ul class="mb-0 ps-3 small opacity-90">
                                                <li>Caution : {{ formatPrice(bien.price) }} FCFA</li>
                                                <li>Premier mois : {{ formatPrice(bien.price) }} FCFA</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <div class="display-6 fw-bold text-warning">
                                                {{ formatPrice(montantPaiementInitial) }} FCFA
                                            </div>
                                            <small class="opacity-75">= 2 mois de loyer</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-light mt-3 mb-0">
                                    <i class="fas fa-lightbulb text-warning me-2"></i>
                                    <strong>À savoir :</strong> Le dépôt de garantie ({{ formatPrice(bien.price) }} FCFA) a déjà été payé lors de votre réservation.
                                    Aujourd'hui, vous payez la caution + le premier mois, soit <strong>{{ formatPrice(montantPaiementInitial) }} FCFA</strong>.
                                    À partir du 2ème mois, vous ne paierez que le loyer mensuel.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de location -->
                <form @submit.prevent="handleSubmit">
                    <!-- TYPE DE CONTRAT -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-header bg-gradient-primary text-white py-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-file-contract me-2"></i>Type de Contrat de Location
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div
                                    v-for="(typeInfo, typeKey) in typesContrat"
                                    :key="typeKey"
                                    class="col-lg-4 col-md-6"
                                >
                                    <label class="type-contrat-card" :class="{ active: form.type_contrat === typeKey }">
                                        <input
                                            type="radio"
                                            :value="typeKey"
                                            v-model="form.type_contrat"
                                            class="d-none"
                                        />
                                        <div class="card-content">
                                            <div class="icon-wrapper" :class="`bg-${typeInfo.color}`">
                                                <i :class="`fas ${typeInfo.icon}`"></i>
                                            </div>
                                            <h6 class="fw-bold mb-2">{{ typeInfo.label }}</h6>
                                            <p class="text-muted small mb-0">{{ typeInfo.description }}</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Détails du type sélectionné -->
                            <transition name="slide-fade">
                                <div v-if="form.type_contrat" class="type-details mt-4 p-4 bg-light rounded-3">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="icon-badge me-3" :class="`bg-${selectedTypeInfo.color}`">
                                            <i :class="`fas ${selectedTypeInfo.icon}`"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-2">{{ selectedTypeInfo.label }}</h5>
                                            <p class="text-muted mb-3">{{ selectedTypeInfo.description }}</p>
                                        </div>
                                    </div>

                                    <div class="caracteristiques">
                                        <h6 class="fw-bold text-primary mb-3">
                                            <i class="fas fa-check-circle me-2"></i>Caractéristiques principales :
                                        </h6>
                                        <ul class="list-unstyled mb-0">
                                            <li
                                                v-for="(carac, index) in selectedTypeInfo.caracteristiques"
                                                :key="index"
                                                class="mb-2"
                                            >
                                                <i class="fas fa-check text-success me-2"></i>
                                                {{ carac }}
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="alert alert-info mt-3 mb-0 border-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Durée minimale :</strong> {{ selectedTypeInfo.duree_min }} mois
                                    </div>
                                </div>
                            </transition>

                            <div v-if="errors.type_contrat" class="alert alert-danger mt-3">
                                {{ errors.type_contrat }}
                            </div>
                        </div>
                    </div>

                    <!-- Détails de la location -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-key me-2"></i>Détails de la location
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="date_debut">
                                        Date de début <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        id="date_debut"
                                        v-model="form.date_debut"
                                        class="form-control form-control-lg"
                                        :class="{ 'is-invalid': errors.date_debut }"
                                        :min="tomorrow"
                                        required
                                    />
                                    <div v-if="errors.date_debut" class="invalid-feedback">
                                        {{ errors.date_debut }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="duree_mois">
                                        Durée (en mois) <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        id="duree_mois"
                                        v-model="form.duree_mois"
                                        class="form-select form-select-lg"
                                        :class="{ 'is-invalid': errors.duree_mois }"
                                        required
                                        :disabled="!form.type_contrat"
                                    >
                                        <option value="">{{ form.type_contrat ? 'Choisir la durée...' : 'Sélectionnez d\'abord un type de contrat' }}</option>
                                        <option v-for="option in dureesDisponibles" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                    <div v-if="errors.duree_mois" class="invalid-feedback">
                                        {{ errors.duree_mois }}
                                    </div>
                                    <small v-if="form.type_contrat && selectedTypeInfo" class="text-muted">
                                        Minimum requis : {{ selectedTypeInfo.duree_min }} mois
                                    </small>
                                </div>
                            </div>

                            <!-- Récapitulatif détaillé du paiement -->
                            <div v-if="form.duree_mois" class="mt-4">
                                <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <div class="card-body p-4 text-white">
                                        <h5 class="fw-bold mb-4 text-center">
                                            <i class="fas fa-receipt me-2"></i>
                                            Récapitulatif complet du paiement
                                        </h5>

                                        <!-- Paiements déjà effectués -->
                                        <div class="mb-4 p-3 bg-white bg-opacity-20 rounded">
                                            <h6 class="fw-bold mb-3">
                                                <i class="fas fa-check-double me-2 text-success"></i>
                                                Déjà payé lors de la réservation
                                            </h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>
                                                    <i class="fas fa-shield-alt me-2"></i>
                                                    Dépôt de garantie (caution restituable)
                                                </span>
                                                <span class="fw-bold fs-5">{{ formatPrice(bien.price) }} FCFA</span>
                                            </div>
                                        </div>

                                        <!-- À payer maintenant -->
                                        <div class="p-3 bg-white bg-opacity-30 rounded border border-white border-2">
                                            <h6 class="fw-bold mb-3">
                                                <i class="fas fa-credit-card me-2 text-warning"></i>
                                                À payer maintenant (paiement unique)
                                            </h6>
                                            <div class="row g-2 mb-3">
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-white border-opacity-50">
                                                        <span>
                                                            <i class="fas fa-hand-holding-usd me-2"></i>
                                                            Caution locative
                                                        </span>
                                                        <span class="fw-bold">{{ formatPrice(bien.price) }} FCFA</span>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-white border-opacity-50">
                                                        <span>
                                                            <i class="fas fa-calendar-check me-2"></i>
                                                            Premier mois de loyer
                                                        </span>
                                                        <span class="fw-bold">{{ formatPrice(bien.price) }} FCFA</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="p-3 bg-white rounded text-dark">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong class="fs-5">
                                                            <i class="fas fa-wallet me-2 text-success"></i>
                                                            Total à payer aujourd'hui
                                                        </strong>
                                                        <br>
                                                        <small class="text-muted">Caution + 1er mois</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="display-6 fw-bold text-success">
                                                            {{ formatPrice(montantPaiementInitial) }} FCFA
                                                        </div>
                                                        <small class="text-muted">= 2 × {{ formatPrice(bien.price) }} FCFA</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Paiements mensuels futurs -->
                                        <div class="mt-4 p-3 bg-white bg-opacity-20 rounded">
                                            <h6 class="fw-bold mb-3">
                                                <i class="fas fa-calendar-alt me-2"></i>
                                                Loyers mensuels suivants (à partir du 2ème mois)
                                            </h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>
                                                    <i class="fas fa-home me-2"></i>
                                                    Loyer mensuel uniquement
                                                </span>
                                                <span class="fw-bold fs-5">{{ formatPrice(bien.price) }} FCFA/mois</span>
                                            </div>
                                            <hr class="border-white border-opacity-50 my-3">
                                            <div class="d-flex justify-content-between align-items-center text-warning">
                                                <span>
                                                    <i class="fas fa-calculator me-2"></i>
                                                    <strong>Total sur {{ form.duree_mois }} mois</strong>
                                                    <br>
                                                    <small>({{ form.duree_mois - 1 }} mois restants × {{ formatPrice(bien.price) }} FCFA)</small>
                                                </span>
                                                <span class="fw-bold fs-5">
                                                    {{ formatPrice(bien.price * (form.duree_mois - 1)) }} FCFA
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Total général -->
                                        <div class="mt-4 p-4 bg-dark bg-opacity-50 rounded">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <h6 class="mb-2">
                                                        <i class="fas fa-chart-line me-2"></i>
                                                        Coût total sur {{ form.duree_mois }} mois
                                                    </h6>
                                                    <small class="opacity-75">
                                                        (Hors dépôt de garantie restituable)
                                                    </small>
                                                </div>
                                                <div class="col-md-6 text-end">
                                                    <div class="display-6 fw-bold">
                                                        {{ formatPrice(montantTotalLocation) }} FCFA
                                                    </div>
                                                    <small class="opacity-75">
                                                        Paiement initial ({{ formatPrice(montantPaiementInitial) }}) + {{ form.duree_mois - 1 }} mois restants
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PRÉVISUALISATION DU CONTRAT -->
                    <transition name="fade">
                        <div v-if="showContract" class="mb-4">
                            <div class="preview-card">
                                <div class="preview-header">
                                    <h5 class="preview-title">
                                        <i class="fas fa-file-alt me-2"></i>Prévisualisation du Contrat
                                    </h5>
                                    <button
                                        type="button"
                                        class="toggle-btn"
                                        @click="toggleContractPreview"
                                    >
                                        <i :class="showContractPreview ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
                                        {{ showContractPreview ? 'Masquer' : 'Afficher' }}
                                    </button>
                                </div>
                                <transition name="slide-down">
                                    <div v-if="showContractPreview" class="preview-body">
                                        <div class="document-container" v-html="contractPreviewHtml"></div>
                                    </div>
                                </transition>
                            </div>
                        </div>
                    </transition>

                    <!-- ACCEPTATION DES TERMES -->
                    <div v-if="showContract" class="card shadow mb-4 border-primary">
                        <div class="card-body bg-light p-4">
                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="acceptTerms"
                                    v-model="acceptedTerms"
                                    required
                                    style="width: 20px; height: 20px;"
                                />
                                <label class="form-check-label ms-2" for="acceptTerms" style="cursor: pointer;">
                                    <strong class="text-primary fs-5">
                                        Je certifie avoir lu et accepté l'intégralité des termes et conditions
                                    </strong>
                                    <p class="text-muted mt-2 mb-0">
                                        du contrat de location {{ selectedTypeInfo?.label }} ci-dessus. Je comprends mes droits et obligations en tant que locataire
                                        et je m'engage à respecter les clauses du présent contrat.
                                    </p>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Information paiement -->
                    <transition name="fade">
                        <div v-if="acceptedTerms" class="alert alert-success mb-4 border-0 shadow-lg p-4">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-check-circle me-3 mt-1 fs-2 text-success"></i>
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold text-success mb-3">
                                        <i class="fas fa-lock me-2"></i>
                                        Prêt pour le paiement sécurisé !
                                    </h5>
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <p class="mb-2">
                                                Vous serez redirigé vers la page de paiement sécurisée pour régler :
                                            </p>
                                            <ul class="mb-0 ps-3">
                                                <li class="mb-1">Caution locative : <strong>{{ formatPrice(bien.price) }} FCFA</strong></li>
                                                <li>Premier mois de loyer : <strong>{{ formatPrice(bien.price) }} FCFA</strong></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <div class="p-3 bg-success bg-opacity-10 rounded border border-success">
                                                <small class="text-muted d-block mb-1">Montant à payer</small>
                                                <div class="display-6 fw-bold text-success">
                                                    {{ formatPrice(montantPaiementInitial) }} FCFA
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Actions -->
                    <div class="card shadow-sm border-0">
                        <div class="card-footer bg-light py-3">
                            <div class="d-flex justify-content-between flex-wrap gap-3">
                                <Link
                                    :href="route('biens.show', bien.id)"
                                    class="btn btn-outline-secondary btn-lg px-4"
                                >
                                    <i class="fas fa-arrow-left me-2"></i>Retour
                                </Link>
                                <button
                                    type="submit"
                                    class="btn btn-success btn-lg px-5 shadow"
                                    :disabled="!canSubmit"
                                >
                                    <i :class="processing ? 'fas fa-spinner fa-spin me-2' : 'fas fa-credit-card me-2'"></i>
                                    {{ processing ? 'Redirection en cours...' : `Payer ${formatPrice(montantPaiementInitial)} FCFA` }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import Layout from '@/Pages/Layout.vue'
export default { layout: Layout }
</script>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import { route } from "ziggy-js"

const props = defineProps({
    bien: { type: Object, required: true },  // ← Changé en required
    reservation: { type: Object, required: true },  // ← Changé en required
    appartements: { type: Array, default: () => [] },
    isImmeuble: { type: Boolean, default: false },
    typesContrat: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) }
})

const getAppartementId = () => {
    // 1. Si la réservation a déjà un appartement_id, l'utiliser
    if (props.reservation.appartement_id) {
        console.log('✅ Appartement depuis réservation:', props.reservation.appartement_id)
        return props.reservation.appartement_id
    }

    // 2. Si c'est un immeuble avec UN SEUL appartement, le sélectionner automatiquement
    if (props.isImmeuble && props.appartements && props.appartements.length === 1) {
        console.log('✅ Auto-sélection du seul appartement disponible:', props.appartements[0].id)
        return props.appartements[0].id
    }

    // 3. Sinon, null (l'utilisateur devra choisir)
    return null
}

const form = ref({
    reservation_id: props.reservation.id,
    appartement_id: getAppartementId(), // ← Utiliser la fonction
    date_debut: '',
    duree_mois: '',
    type_contrat: 'bail_classique'
})

console.log('✅ Form initialisé:', form.value)

const processing = ref(false)
const acceptedTerms = ref(false)
const showContractPreview = ref(true)

const isFormValid = computed(() => {
    return form.value.reservation_id !== null && form.value.reservation_id !== undefined
})

// Computed - Montants
const montantPaiementInitial = computed(() => {
    // Caution + 1er mois = 2 fois le loyer mensuel
    return props.bien.price * 2
})

const montantTotalLocation = computed(() => {
    if (!form.value.duree_mois) return 0
    // Total = Paiement initial + loyers restants
    // Paiement initial couvre déjà le 1er mois, donc on compte (duree - 1) mois restants
    return montantPaiementInitial.value + (props.bien.price * (form.value.duree_mois - 1))
})

const tomorrow = computed(() => {
    const date = new Date()
    date.setDate(date.getDate() + 1)
    return date.toISOString().split('T')[0]
})

const selectedTypeInfo = computed(() => {
    return form.value.type_contrat ? props.typesContrat[form.value.type_contrat] : null
})

const dureesDisponibles = computed(() => {
    if (!form.value.type_contrat || !selectedTypeInfo.value) return []

    const options = []

    if (form.value.type_contrat === 'bail_classique') {
        options.push({ value: 36, label: '3 ans (36 mois) - Minimum légal' })
        options.push({ value: 48, label: '4 ans (48 mois)' })
        options.push({ value: 60, label: '5 ans (60 mois)' })
        options.push({ value: 72, label: '6 ans (72 mois)' })
    } else if (form.value.type_contrat === 'bail_meuble') {
        options.push({
            value: 1,
            label: '1 mois - Court séjour'
        })
        options.push({
            value: 2,
            label: '2 mois - Séjour moyen'
        })
        options.push({
            value: 3,
            label: '3 mois - Maximum autorisé'
        })
    } else if (form.value.type_contrat === 'bail_commercial') {
        options.push({ value: 36, label: '3 ans (36 mois)' })
        options.push({ value: 72, label: '6 ans (72 mois)' })
        options.push({ value: 108, label: '9 ans (108 mois)' })
    }

    return options
})
const showContract = computed(() => {
    return form.value.date_debut && form.value.duree_mois && form.value.type_contrat
})

const contractPreviewHtml = computed(() => {
    if (!showContract.value || !selectedTypeInfo.value) return ''

    const dateDebut = new Date(form.value.date_debut).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })

    const dateFin = new Date(form.value.date_debut)
    dateFin.setMonth(dateFin.getMonth() + parseInt(form.value.duree_mois))
    const dateFinFormatted = dateFin.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })

    let clauseResiliation = ''
    if (form.value.type_contrat === 'bail_meuble') {
        clauseResiliation = `
            <p>Pour un bail meublé de courte durée (1 à 3 mois), les conditions de résiliation sont les suivantes :</p>
            <ul>
                <li>Résiliation possible avec un préavis de 15 jours minimum</li>
                <li>En cas de résiliation anticipée, aucun remboursement du loyer payé</li>
                <li>Le dépôt de garantie sera restitué sous 15 jours après l'état des lieux de sortie</li>
            </ul>
        `
    } else if (form.value.type_contrat === 'bail_classique') {
        clauseResiliation = `
            <p>Le locataire peut résilier le bail en respectant un préavis de <strong>3 mois</strong>.
            Le bailleur peut résilier le bail pour vente, reprise ou motif légitime avec un préavis de <strong>6 mois</strong>.</p>
        `
    } else {
        clauseResiliation = `
            <p>Pour un bail commercial, la résiliation suit les règles du bail 3-6-9.
            Un préavis de <strong>6 mois</strong> est requis pour toute résiliation.</p>
        `
    }

    return `
        <div class="contract-document">
            <div class="contract-header text-center mb-4">
                <h3 class="fw-bold text-primary">CONTRAT DE LOCATION</h3>
                <h5 class="text-muted">${selectedTypeInfo.value.label}</h5>
            </div>

            <div class="contract-section">
                <h6 class="fw-bold text-primary">ARTICLE 1 : PARTIES AU CONTRAT</h6>
                <p>Le présent contrat de location est conclu entre :</p>
                <ul>
                    <li><strong>Le bailleur :</strong> Propriétaire du bien situé à ${props.bien.address}, ${props.bien.city}</li>
                    <li><strong>Le locataire :</strong> Demandeur de la location</li>
                </ul>
            </div>

            <div class="contract-section">
                <h6 class="fw-bold text-primary">ARTICLE 2 : DÉSIGNATION DU BIEN</h6>
                <p>Le bailleur donne en location au locataire qui accepte, le bien suivant :</p>
                <ul>
                    <li><strong>Adresse :</strong> ${props.bien.address}, ${props.bien.city}</li>
                    <li><strong>Type de bien :</strong> ${props.bien.category?.name}</li>
                    <li><strong>Description :</strong> ${props.bien.title}</li>
                </ul>
            </div>

            <div class="contract-section">
                <h6 class="fw-bold text-primary">ARTICLE 3 : DURÉE DE LA LOCATION</h6>
                <p>Le présent bail est consenti et accepté pour une durée de <strong>${form.value.duree_mois} mois</strong>, soit du <strong>${dateDebut}</strong> au <strong>${dateFinFormatted}</strong>.</p>
                ${form.value.type_contrat === 'bail_meuble' ?
        '<p class="text-info"><i class="fas fa-info-circle me-2"></i><strong>Bail meublé de courte durée</strong> : Idéal pour séjours temporaires, mutations professionnelles ou études.</p>'
        : ''}
            </div>

            <div class="contract-section">
                <h6 class="fw-bold text-primary">ARTICLE 4 : CONDITIONS FINANCIÈRES</h6>
                <p>Le loyer mensuel est fixé à : <strong>${formatPrice(props.bien.price)} FCFA</strong></p>

                <div class="mt-3">
                    <h6 class="fw-bold">Paiements initiaux :</h6>
                    <ul>
                        <li><strong>Dépôt de garantie :</strong> ${formatPrice(props.bien.price)} FCFA (déjà payé lors de la réservation)</li>
                        <li><strong>Caution locative :</strong> ${formatPrice(props.bien.price)} FCFA (à payer maintenant)</li>
                        <li><strong>Premier mois de loyer :</strong> ${formatPrice(props.bien.price)} FCFA (à payer maintenant)</li>
                        <li class="mt-2 text-success"><strong>TOTAL À PAYER AUJOURD'HUI : ${formatPrice(montantPaiementInitial.value)} FCFA</strong></li>
                    </ul>
                </div>

                <div class="mt-3">
                    <h6 class="fw-bold">Paiements mensuels suivants :</h6>
                    <p>À partir du 2ème mois : <strong>${formatPrice(props.bien.price)} FCFA/mois</strong></p>
                    ${form.value.type_contrat === 'bail_meuble' && form.value.duree_mois <= 3 ?
        '<p class="text-muted small"><i class="fas fa-calendar-alt me-1"></i>Pour une location de courte durée, le paiement peut être effectué en une seule fois.</p>'
        : ''}
                </div>
            </div>

            <div class="contract-section">
                <h6 class="fw-bold text-primary">ARTICLE 5 : OBLIGATIONS DU LOCATAIRE</h6>
                <ul>
                    <li>Payer le loyer à la date convenue</li>
                    <li>Utiliser les lieux paisiblement et conformément à leur destination</li>
                    <li>Entretenir le bien en bon état</li>
                    <li>Souscrire une assurance habitation</li>
                    <li>Ne pas sous-louer sans accord écrit du bailleur</li>
                    ${form.value.type_contrat === 'bail_meuble' ?
        '<li class="text-primary"><strong>Restituer le mobilier en bon état</strong></li>'
        : ''}
                </ul>
            </div>

            <div class="contract-section">
                <h6 class="fw-bold text-primary">ARTICLE 6 : OBLIGATIONS DU BAILLEUR</h6>
                <ul>
                    <li>Délivrer le logement en bon état d'usage</li>
                    ${form.value.type_contrat === 'bail_meuble' ?
        '<li class="text-primary"><strong>Fournir un logement entièrement meublé et équipé</strong></li>'
        : ''}
                    <li>Assurer la jouissance paisible du bien</li>
                    <li>Effectuer les réparations nécessaires</li>
                    <li>Ne pas s'opposer aux aménagements raisonnables</li>
                </ul>
            </div>

            <div class="contract-section">
                <h6 class="fw-bold text-primary">ARTICLE 7 : DÉPÔT DE GARANTIE</h6>
                <p>Un dépôt de garantie de <strong>${formatPrice(props.bien.price)} FCFA</strong> a été versé lors de la réservation.
                Il sera restitué au locataire dans un délai ${form.value.type_contrat === 'bail_meuble' ? 'd\'un mois' : 'de deux mois'} après la remise des clés, déduction faite, le cas échéant,
                des sommes restant dues au bailleur et du coût des réparations locatives.</p>
            </div>

            <div class="contract-section">
                <h6 class="fw-bold text-primary">ARTICLE 8 : RÉSILIATION</h6>
                ${clauseResiliation}
            </div>

            <div class="contract-section">
                <h6 class="fw-bold text-primary">ARTICLE 9 : LITIGES</h6>
                <p>En cas de litige, les parties s'engagent à rechercher une solution amiable.
                À défaut, le tribunal compétent sera celui du lieu de situation du bien.</p>
            </div>

            <div class="contract-footer text-center mt-4 pt-4 border-top">
                <p class="text-muted">Fait en deux exemplaires originaux</p>
                <p class="text-muted">Date : ${new Date().toLocaleDateString('fr-FR')}</p>
            </div>
        </div>
    `
})

const canSubmit = computed(() => {
    return form.value.date_debut &&
        form.value.duree_mois &&
        form.value.type_contrat &&
        acceptedTerms.value &&
        !processing.value
})

// Methods
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const toggleContractPreview = () => {
    showContractPreview.value = !showContractPreview.value
}

const handleSubmit = () => {
    if (!canSubmit.value) return

    if (!form.value.reservation_id) {
        console.error('❌ reservation_id manquant')
        alert('Erreur : Réservation non trouvée')
        return
    }

    if (props.isImmeuble && !form.value.appartement_id) {
        console.error('❌ appartement_id requis pour un immeuble', {
            isImmeuble: props.isImmeuble,
            appartement_id: form.value.appartement_id,
            appartements: props.appartements
        })
        alert('Erreur : Veuillez sélectionner un appartement')
        return
    }

    console.log('📤 Envoi du formulaire:', form.value)

    processing.value = true

    router.post(route('locations.store'), {
        ...form.value,
        montant_paiement: montantPaiementInitial.value
    }, {
        onSuccess: () => {
            console.log('✅ Location créée avec succès')
            processing.value = false
        },
        onError: (errors) => {
            console.error('❌ Erreurs validation:', errors)
            alert('Erreur lors de la création : ' + JSON.stringify(errors))
            processing.value = false
        }
    })
}
watch(() => form.value.type_contrat, () => {
    form.value.duree_mois = ''
})
</script>

<style scoped>
/* Type de contrat cards */
.type-contrat-card {
    display: block;
    cursor: pointer;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s ease;
    height: 100%;
    background: white;
}

.type-contrat-card:hover {
    border-color: #667eea;
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.1);
}

.type-contrat-card.active {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.type-contrat-card .card-content {
    text-align: center;
}

.type-contrat-card .icon-wrapper {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    color: white;
    font-size: 24px;
}

.bg-primary { background-color: #667eea !important; }
.bg-info { background-color: #17a2b8 !important; }
.bg-warning { background-color: #ffc107 !important; }

.type-details {
    animation: slideDown 0.3s ease;
}

.icon-badge {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

/* Preview Card */
.preview-card {
    border: 2px solid #667eea;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.preview-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.preview-title {
    color: white;
    margin: 0;
    font-weight: bold;
}

.toggle-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid white;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.toggle-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.preview-body {
    background: white;
    padding: 30px;
    max-height: 600px;
    overflow-y: auto;
}

/* Document styles */
.document-container {
    font-family: 'Georgia', serif;
    line-height: 1.8;
}

.contract-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.contract-section:last-child {
    border-bottom: none;
}

.contract-section h6 {
    margin-bottom: 15px;
    color: #667eea;
}

.contract-section ul {
    list-style-type: disc;
    padding-left: 25px;
}

.contract-section ul li {
    margin-bottom: 8px;
}

/* Animations */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .type-contrat-card {
        margin-bottom: 15px;
    }

    .preview-body {
        padding: 20px;
    }
}
</style>
