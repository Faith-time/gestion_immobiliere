<template>
    <Layout>
        <Head title="Réserver un appartement" />

        <div class="container py-5">
            <!-- Messages Flash -->
            <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show mb-4">
                <i class="fas fa-check-circle me-2"></i>{{ $page.props.flash.success }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="fas fa-exclamation-circle me-2"></i>{{ $page.props.flash.error }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <div v-if="$page.props.errors && Object.keys($page.props.errors).length > 0" class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Erreurs de validation :</strong>
                <ul class="mb-0 mt-2">
                    <li v-for="(error, key) in $page.props.errors" :key="key">
                        {{ Array.isArray(error) ? error[0] : error }}
                    </li>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- En-tête dynamique -->
                    <div class="text-center mb-5">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                             style="width: 80px; height: 80px;"
                             :class="isImmeuble ? 'bg-purple-100' : 'bg-primary bg-opacity-10'">
                            <i :class="isImmeuble ? 'fas fa-door-open text-purple-600' : 'fas fa-clipboard-check text-primary'" class="fa-2x"></i>
                        </div>
                        <h1 class="h2 mb-3" :class="isImmeuble ? 'text-purple-600' : 'text-primary'">
                            {{ isImmeuble ? 'Réserver un appartement' : 'Réserver cette propriété' }}
                        </h1>
                        <p class="text-muted lead">
                            {{ isImmeuble
                            ? 'Sécurisez votre appartement dans cet immeuble'
                            : 'Sécurisez votre engagement pour ce bien'
                            }}
                        </p>
                    </div>

                    <!-- Encadré explicatif -->
                    <div class="card border-info mb-4 shadow-sm">
                        <div class="card-header bg-info bg-opacity-10 border-info">
                            <h5 class="mb-0 text-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Comprendre votre paiement initial
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Pour LOCATION -->
                                <div v-if="bien.mandat?.type_mandat === 'gestion_locative'" class="col-12">
                                    <div class="alert alert-warning border-warning mb-0">
                                        <h6 class="alert-heading fw-bold">
                                            <i class="fas fa-key me-2"></i>
                                            {{ isImmeuble ? 'Location d\'appartement' : 'Location' }} : Dépôt de garantie (caution)
                                        </h6>
                                        <hr>
                                        <ul class="mb-0">
                                            <li><strong>Montant :</strong> {{ formatPrice(bien.price) }} FCFA (1 mois de loyer)</li>
                                            <li><strong>Nature :</strong> Caution restituable</li>
                                            <li><strong>Utilité :</strong> Couvre les éventuels dommages et garantit votre engagement</li>
                                            <li><strong>Restitution :</strong> En fin de bail, déduction faite des dégâts éventuels</li>
                                            <li v-if="isImmeuble" class="text-primary fw-bold mt-2">
                                                <i class="fas fa-building me-1"></i>
                                                Vous réservez UN appartement spécifique dans cet immeuble
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Pour VENTE -->
                                <div v-else-if="bien.mandat?.type_mandat === 'vente'" class="col-12">
                                    <div class="alert alert-info border-info mb-0">
                                        <h6 class="alert-heading fw-bold">
                                            <i class="fas fa-shopping-cart me-2"></i>
                                            {{ isImmeuble ? 'Achat d\'appartement' : 'Achat' }} : Acompte de réservation
                                        </h6>
                                        <hr>
                                        <ul class="mb-0">
                                            <li><strong>Montant :</strong> {{ formatPrice(calculateAmount()) }} FCFA (10% du prix de vente)</li>
                                            <li><strong>Prix total :</strong> {{ formatPrice(bien.price) }} FCFA</li>
                                            <li><strong>Montant restant :</strong> {{ formatPrice(bien.price * 0.90) }} FCFA (90%)</li>
                                            <li><strong>Nature :</strong> Acompte définitif</li>
                                            <li><strong>Utilité :</strong> Réserve le bien et prouve votre engagement</li>
                                            <li><strong>Déduction :</strong> Déduit du prix total lors de l'achat final</li>
                                            <li v-if="isImmeuble" class="text-primary fw-bold mt-2">
                                                <i class="fas fa-building me-1"></i>
                                                Vous achetez UN appartement spécifique dans cet immeuble
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div v-else class="col-12">
                                    <div class="alert alert-danger mb-0">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Ce bien n'a pas de mandat valide. Impossible de créer une réservation.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Récapitulatif de l'immeuble (si applicable) -->
                    <div v-if="isImmeuble" class="card mb-4 shadow-sm border-2 border-purple-300">
                        <div class="card-header bg-gradient-to-r from-purple-600 to-indigo-600 text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-building me-2"></i>
                                Immeuble
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <img
                                        :src="getBienImageUrl(bien)"
                                        :alt="bien.title"
                                        class="img-fluid rounded shadow-sm"
                                        @error="handleImageError"
                                    />
                                </div>
                                <div class="col-md-9">
                                    <h5 class="card-title text-purple-700 mb-2">
                                        <i class="fas fa-building me-2"></i>
                                        {{ bien.title }}
                                    </h5>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                        {{ bien.address }}, {{ bien.city }}
                                    </p>
                                    <div class="alert alert-info border-0 bg-info bg-opacity-10 mt-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Immeuble d'appartements</strong> - Sélectionnez votre appartement ci-dessous
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ SECTION APPARTEMENTS (OBLIGATOIRE pour immeuble) -->
                    <div v-if="isImmeuble" class="card mb-4 shadow-lg border-2 border-primary">
                        <div class="card-header bg-gradient text-white py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold">
                                    <i class="fas fa-door-open me-2"></i>
                                    Choisissez votre appartement
                                </h5>
                                <span class="badge bg-white text-primary px-3 py-2">
                                    {{ appartements_disponibles?.length || 0 }} disponible(s)
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="alert alert-warning border-warning mb-4">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-exclamation-triangle me-3 mt-1 fs-4"></i>
                                    <div>
                                        <strong>⚠️ IMPORTANT :</strong>
                                        <p class="mb-0">Vous devez sélectionner UN appartement spécifique dans cet immeuble.
                                            Votre réservation ne concerne QUE l'appartement choisi, pas l'immeuble entier.</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="appartements_disponibles?.length > 0" class="row g-4">
                                <div v-for="appt in appartements_disponibles" :key="appt.id" class="col-md-6 col-lg-4">
                                    <label class="appartement-card h-100" :class="{ active: form.appartement_id === appt.id }">
                                        <input
                                            type="radio"
                                            :value="appt.id"
                                            v-model="form.appartement_id"
                                            class="d-none"
                                        />
                                        <div class="card h-100 border-2 shadow-sm">
                                            <div class="card-body text-center">
                                                <div class="icon-wrapper mb-3 mx-auto">
                                                    <i class="fas fa-door-open fa-3x"></i>
                                                </div>
                                                <h5 class="fw-bold mb-3 text-primary">{{ appt.numero }}</h5>

                                                <div class="mb-3">
                                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                                        <i class="fas fa-layer-group text-primary me-2"></i>
                                                        <span class="fw-semibold">Étage {{ appt.etage }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                                        <i class="fas fa-bed text-success me-2"></i>
                                                        <span>{{ appt.nombre_pieces }} pièces</span>
                                                    </div>
                                                    <div v-if="appt.superficie" class="d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-ruler-combined text-info me-2"></i>
                                                        <span>{{ appt.superficie }} m²</span>
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <span class="badge bg-success px-3 py-2 w-100">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        Disponible
                                                    </span>
                                                </div>

                                                <div class="selection-indicator mt-3" v-if="form.appartement_id === appt.id">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    <strong class="text-success">Sélectionné</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div v-else class="text-center py-5">
                                <i class="fas fa-door-closed text-muted fa-3x mb-3"></i>
                                <p class="text-muted">Aucun appartement disponible dans cet immeuble pour le moment.</p>
                            </div>

                            <div v-if="errors.appartement_id" class="alert alert-danger mt-4">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ errors.appartement_id }}
                            </div>

                            <!-- Affichage de l'appartement sélectionné -->
                            <div v-if="selectedAppartement" class="mt-4 p-4 bg-success bg-opacity-10 border-2 border-success rounded-3">
                                <h6 class="fw-bold text-success mb-3">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Appartement sélectionné
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Numéro :</strong> {{ selectedAppartement.numero }}</p>
                                        <p class="mb-1"><strong>Étage :</strong> {{ selectedAppartement.etage }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Pièces :</strong> {{ selectedAppartement.nombre_pieces }}</p>
                                        <p class="mb-1" v-if="selectedAppartement.superficie">
                                            <strong>Surface :</strong> {{ selectedAppartement.superficie }} m²
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Récapitulatif du bien (si pas immeuble) -->
                    <div v-else class="card mb-4 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-home me-2"></i>
                                Propriété sélectionnée
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <img
                                        :src="getBienImageUrl(bien)"
                                        :alt="bien.title"
                                        class="img-fluid rounded shadow-sm"
                                        @error="handleImageError"
                                    />
                                </div>
                                <div class="col-md-9">
                                    <h5 class="card-title text-primary mb-2">{{ bien.title }}</h5>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-map-marker-alt me-2"></i>{{ bien.address }}, {{ bien.city }}
                                    </p>
                                    <p class="text-muted mb-3">
                                        <i class="fas fa-tag me-2"></i>{{ bien.category?.name }}
                                    </p>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 mb-2">
                                                <small class="text-muted d-block">
                                                    {{ bien.mandat?.type_mandat === 'vente' ? 'Prix de vente' : 'Loyer mensuel' }}
                                                </small>
                                                <strong class="h5 text-success mb-0">{{ formatPrice(bien.price) }} FCFA</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 mb-2"
                                                 :class="bien.mandat?.type_mandat === 'vente' ? 'bg-info bg-opacity-10' : 'bg-warning bg-opacity-10'">
                                                <small class="text-muted d-block">{{ getMontantLabel() }}</small>
                                                <strong class="h5 mb-0"
                                                        :class="bien.mandat?.type_mandat === 'vente' ? 'text-info' : 'text-warning'">
                                                    {{ formatPrice(calculateAmount()) }} FCFA
                                                </strong>
                                                <small class="d-block text-muted mt-1">{{ getAmountDescription() }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire documents -->
                    <div class="card shadow-lg">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="mb-0">
                                <i class="fas fa-file-upload me-2"></i>Documents requis
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="alert alert-light border mb-4">
                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                <strong>Pourquoi ces documents ?</strong>
                                <p class="mb-0 small">
                                    Ils permettent de vérifier votre identité et votre solvabilité,
                                    conformément aux exigences légales.
                                </p>
                            </div>

                            <!-- Type de document -->
                            <div class="mb-4">
                                <label class="form-label fw-bold" for="type_document">
                                    Type de document <span class="text-danger">*</span>
                                </label>
                                <select
                                    id="type_document"
                                    v-model="form.type_document"
                                    class="form-select form-select-lg"
                                    :class="{ 'is-invalid': errors.type_document }"
                                >
                                    <option value="">Sélectionner le type de document...</option>
                                    <option value="cni">🆔 Carte Nationale d'Identité</option>
                                    <option value="passeport">✈️ Passeport</option>
                                    <option value="justificatif_domicile">🏠 Justificatif de domicile</option>
                                    <option value="bulletin_salaire">💰 Bulletin de salaire</option>
                                    <option value="attestation_travail">📄 Attestation de travail</option>
                                    <option value="autre">📎 Autre document</option>
                                </select>
                                <div v-if="errors.type_document" class="invalid-feedback">
                                    {{ errors.type_document }}
                                </div>
                            </div>

                            <!-- Upload du document -->
                            <div class="mb-4">
                                <label class="form-label fw-bold" for="fichier">
                                    Télécharger le document <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="file"
                                    id="fichier"
                                    @change="handleFileChange"
                                    class="form-control form-control-lg"
                                    :class="{ 'is-invalid': errors.fichier }"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                />
                                <div class="form-text">
                                    <i class="fas fa-paperclip me-1"></i>
                                    Formats acceptés : PDF, JPG, PNG (maximum 5MB)
                                </div>
                                <div v-if="errors.fichier" class="invalid-feedback">
                                    {{ errors.fichier }}
                                </div>
                            </div>

                            <!-- Récapitulatif -->
                            <div class="alert alert-success border-success border-2 shadow-sm">
                                <h6 class="alert-heading fw-bold">
                                    <i class="fas fa-clipboard-check me-2"></i>
                                    Récapitulatif de votre réservation
                                </h6>
                                <hr>
                                <ul class="mb-0">
                                    <li v-if="isImmeuble">
                                        <strong>Immeuble :</strong> {{ bien.title }}
                                    </li>
                                    <li v-else>
                                        <strong>Propriété :</strong> {{ bien.title }}
                                    </li>

                                    <li v-if="isImmeuble && selectedAppartement" class="text-primary fw-bold">
                                        <i class="fas fa-door-open me-1"></i>
                                        <strong>APPARTEMENT RÉSERVÉ :</strong> {{ selectedAppartement.numero }}
                                        (Étage {{ selectedAppartement.etage }}, {{ selectedAppartement.nombre_pieces }} pièces)
                                    </li>
                                    <li v-else-if="isImmeuble && !selectedAppartement" class="text-danger">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <strong>Veuillez sélectionner un appartement ci-dessus</strong>
                                    </li>

                                    <li><strong>Adresse :</strong> {{ bien.address }}, {{ bien.city }}</li>

                                    <li><strong>Type d'opération :</strong>
                                        <span v-if="bien.mandat?.type_mandat === 'vente'" class="badge bg-info">
                                            {{ isImmeuble ? 'Achat d\'appartement' : 'Achat' }}
                                        </span>
                                        <span v-else-if="bien.mandat?.type_mandat === 'gestion_locative'" class="badge bg-warning text-dark">
                                            {{ isImmeuble ? 'Location d\'appartement' : 'Location' }}
                                        </span>
                                    </li>

                                    <li><strong>{{ getMontantLabel() }} :</strong>
                                        <span class="text-success fw-bold">{{ formatPrice(calculateAmount()) }} FCFA</span>
                                    </li>

                                    <li><strong>Date de réservation :</strong> {{ formatDate(new Date()) }}</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="card-footer bg-light py-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <Link
                                    :href="route('biens.show', bien.id)"
                                    class="btn btn-outline-secondary btn-lg px-4"
                                >
                                    <i class="fas fa-arrow-left me-2"></i>Retour
                                </Link>
                                <button
                                    @click="submitReservation"
                                    class="btn btn-success btn-lg px-5 shadow"
                                    :disabled="processing || !canSubmit"
                                >
                                    <i class="fas fa-check me-2" v-if="!processing"></i>
                                    <i class="fas fa-spinner fa-spin me-2" v-if="processing"></i>
                                    {{ processing ? 'Envoi en cours...' : getSubmitButtonText() }}
                                </button>
                            </div>

                            <!-- Message d'aide -->
                            <div v-if="isImmeuble && !form.appartement_id" class="mt-3 text-center">
                                <small class="text-danger">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Vous devez sélectionner un appartement pour continuer
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
import { ref, reactive, computed, onMounted } from 'vue'
import { route } from 'ziggy-js'

const props = defineProps({
    bien: { type: Object, required: true },
    appartement_id: { type: [Number, String], default: null },
    appartements_disponibles: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) }
})

const processing = ref(false)
const form = reactive({
    bien_id: props.bien.id,
    appartement_id: props.appartement_id,
    type_document: '',
    fichier: null
})

// ✅ Computed
const isImmeuble = computed(() => {
    const categoryName = props.bien.category?.name?.toLowerCase()
    const hasAppartements = Array.isArray(props.appartements_disponibles) && props.appartements_disponibles.length > 0

    console.log('🔍 Vérification isImmeuble:', {
        categoryName,
        hasAppartements,
        appartements_count: props.appartements_disponibles?.length
    })

    return categoryName === 'appartement' && hasAppartements
})

const selectedAppartement = computed(() => {
    if (!form.appartement_id || !props.appartements_disponibles) return null
    return props.appartements_disponibles.find(a => a.id === form.appartement_id)
})

const canSubmit = computed(() => {
    const hasBasicInfo = form.type_document && form.fichier
    if (isImmeuble.value) {
        return hasBasicInfo && form.appartement_id
    }
    return hasBasicInfo
})

const canReserve = computed(() => {
    return props.bien.mandat &&
        (props.bien.mandat.type_mandat === 'vente' ||
            props.bien.mandat.type_mandat === 'gestion_locative')
})

// Methods
const calculateAmount = () => {
    if (!props.bien.mandat) return 0
    if (props.bien.mandat.type_mandat === 'vente') {
        return props.bien.price * 0.10
    } else if (props.bien.mandat.type_mandat === 'gestion_locative') {
        return props.bien.price
    }
    return 0
}

const getMontantLabel = () => {
    if (!props.bien.mandat) return 'Montant'
    const prefix = isImmeuble.value ? 'de l\'appartement' : ''
    if (props.bien.mandat.type_mandat === 'vente') {
        return `Acompte de réservation ${prefix}`
    } else if (props.bien.mandat.type_mandat === 'gestion_locative') {
        return `Dépôt de garantie ${prefix}`
    }
    return 'Montant de réservation'
}

const getAmountDescription = () => {
    if (!props.bien.mandat) return ''
    if (props.bien.mandat.type_mandat === 'vente') {
        return '(10% du prix de vente)'
    } else if (props.bien.mandat.type_mandat === 'gestion_locative') {
        return '(1 mois de loyer)'
    }
    return ''
}

const getSubmitButtonText = () => {
    if (isImmeuble.value) {
        return 'Réserver cet appartement'
    }
    return 'Confirmer la réservation'
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price || 0)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const getBienImageUrl = (bien) => {
    if (bien?.images && Array.isArray(bien.images) && bien.images.length > 0) {
        return `/storage/${bien.images[0].path}`
    }
    return '/images/placeholder.jpg'
}

const handleImageError = (event) => {
    event.target.src = '/images/placeholder.jpg'
}

const handleFileChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('Le fichier ne doit pas dépasser 5MB')
            event.target.value = ''
            return
        }
        form.fichier = file
    }
}

const submitReservation = () => {
    if (!canSubmit.value) {
        if (isImmeuble.value && !form.appartement_id) {
            alert('⚠️ Veuillez sélectionner un appartement avant de continuer')
        } else {
            alert('Veuillez remplir tous les champs obligatoires')
        }
        return
    }

    if (!canReserve.value) {
        alert('Ce bien n\'a pas de mandat valide pour une réservation')
        return
    }

    processing.value = true

    const formData = new FormData()
    formData.append('bien_id', form.bien_id)
    formData.append('type_document', form.type_document)
    formData.append('fichier', form.fichier)

    // ✅ Ajouter appartement_id si présent
    if (form.appartement_id) {
        formData.append('appartement_id', form.appartement_id)
        console.log('✅ Envoi réservation appartement:', form.appartement_id)
    }

    router.post(route('reservations.store'), formData, {
        onSuccess: () => {
            console.log('✅ Réservation créée avec succès')
        },
        onError: (errors) => {
            console.error('❌ Erreurs:', errors)
            processing.value = false
            let errorMessage = 'Erreurs détectées:\n'
            Object.values(errors).forEach(error => {
                errorMessage += '• ' + error + '\n'
            })
            alert(errorMessage)
        },
        onFinish: () => {
            processing.value = false
        }
    })
}

onMounted(() => {
    console.log('📋 Bien:', props.bien)
    console.log('📋 Catégorie:', props.bien.category?.name)
    console.log('📋 Appartements disponibles:', props.appartements_disponibles)
    console.log('📋 Appartements count:', props.appartements_disponibles?.length)
    console.log('📋 Appartements isArray:', Array.isArray(props.appartements_disponibles))
    console.log('📋 Appartement ID initial:', props.appartement_id)
    console.log('📋 Est un immeuble?:', isImmeuble.value)

    // Debug détaillé des appartements
    if (props.appartements_disponibles) {
        console.log('📋 Premier appartement:', props.appartements_disponibles[0])
    }

    if (isImmeuble.value) {
        console.log('🏢 Mode IMMEUBLE activé - Sélection d\'appartement requise')
    } else {
        console.log('🏠 Mode BIEN CLASSIQUE - Pas d\'appartement')
        console.log('❌ Raison:', {
            'Category OK?': props.bien.category?.name?.toLowerCase() === 'appartement',
            'Has appartements?': Array.isArray(props.appartements_disponibles) && props.appartements_disponibles.length > 0
        })
    }
})
</script>

<style scoped>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.form-label {
    font-weight: 600;
    color: #495057;
}

.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
}

.btn-primary:disabled,
.btn-success:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* ✅ Styles pour les appartements */
.appartement-card {
    display: block;
    cursor: pointer;
    transition: all 0.3s ease;
    height: 100%;
}

.appartement-card .card {
    border: 3px solid #e0e0e0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%;
}

.appartement-card:hover .card {
    border-color: #667eea;
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(102, 126, 234, 0.2);
}

.appartement-card.active .card {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.icon-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.appartement-card:hover .icon-wrapper {
    background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
    transform: scale(1.1);
}

.appartement-card.active .icon-wrapper {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transform: scale(1.15);
}

.appartement-card.active .icon-wrapper i {
    color: white;
}

.selection-indicator {
    animation: fadeInUp 0.3s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Couleurs personnalisées */
.text-purple-600 {
    color: #7c3aed;
}

.text-purple-700 {
    color: #6d28d9;
}

.bg-purple-100 {
    background-color: #f3e8ff;
}

.border-purple-300 {
    border-color: #d8b4fe;
}

/* Gradient header */
.bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Effet de survol amélioré */
.appartement-card .card-body {
    transition: all 0.3s ease;
}

.appartement-card.active .card-body {
    background: rgba(102, 126, 234, 0.05);
}

/* Animation pour les badges */
.badge {
    transition: all 0.3s ease;
}

.appartement-card.active .badge {
    transform: scale(1.05);
}

/* Responsive */
@media (max-width: 768px) {
    .appartement-card {
        margin-bottom: 1rem;
    }

    .icon-wrapper {
        width: 60px;
        height: 60px;
    }

    .icon-wrapper i {
        font-size: 2rem !important;
    }
}

/* Amélioration visuelle des alertes */
.alert {
    border-radius: 12px;
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #fff8e1 100%);
}

.alert-success {
    background: linear-gradient(135deg, #d1f4e0 0%, #e8f8f5 100%);
}

.alert-info {
    background: linear-gradient(135deg, #d1ecf1 0%, #e7f3ff 100%);
}

/* Shadow pour les cartes importantes */
.shadow-lg {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
}

/* Animation de pulsation pour attirer l'attention */
@keyframes pulse-border {
    0%, 100% {
        border-color: #667eea;
        box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.4);
    }
    50% {
        border-color: #764ba2;
        box-shadow: 0 0 0 8px rgba(118, 75, 162, 0);
    }
}

.border-primary:hover {
    animation: pulse-border 2s infinite;
}
</style>
