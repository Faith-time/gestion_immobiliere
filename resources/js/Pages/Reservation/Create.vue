<template>
    <Layout>
        <Head title="Réserver" />

        <!-- ✅ ÉCRAN DE CHARGEMENT SI DONNÉES MANQUANTES -->
        <div v-if="!bien || !bien.price || bien.price <= 0" class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <h4 class="text-muted">Chargement des informations du bien...</h4>
                    <p class="text-muted">Veuillez patienter</p>
                </div>
            </div>
        </div>

        <!-- ✅ CONTENU PRINCIPAL -->
        <div v-else class="container py-5">
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
                    <!-- En-tête -->
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
                            Complétez les informations nécessaires pour votre réservation
                        </p>
                    </div>

                    <!-- Informations du bien/appartement -->
                    <div class="card mb-4 shadow-sm border-info">
                        <div class="card-header bg-info bg-opacity-10">
                            <h5 class="mb-0 text-info">
                                <i class="fas fa-info-circle me-2"></i>
                                {{ isImmeuble ? 'Appartement sélectionné' : 'Bien sélectionné' }}
                            </h5>
                        </div>
                        <div class="card-body bg-light">
                            <div class="row">
                                <div class="col-md-4">
                                    <img
                                        :src="getBienImageUrl(bien)"
                                        :alt="bien.title || 'Bien immobilier'"
                                        class="img-fluid rounded shadow-sm"
                                        @error="handleImageError"
                                    />
                                </div>
                                <div class="col-md-8">
                                    <h5 class="text-primary">{{ bien.title || 'Bien sans titre' }}</h5>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        {{ bien.address || 'Adresse non spécifiée' }}, {{ bien.city || 'Ville non spécifiée' }}
                                    </p>

                                    <div v-if="isImmeuble && selectedAppartement" class="alert alert-primary border-primary">
                                        <h6 class="fw-bold mb-2">
                                            <i class="fas fa-door-open me-2"></i>
                                            Appartement {{ selectedAppartement.numero }}
                                        </h6>
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="d-block"><strong>Étage :</strong> {{ selectedAppartement.etage }}</small>
                                                <small class="d-block"><strong>Chambres :</strong> {{ selectedAppartement.chambres || 0 }}</small>
                                            </div>
                                            <div class="col-6">
                                                <small class="d-block"><strong>Salons :</strong> {{ selectedAppartement.salons || 0 }}</small>
                                                <small class="d-block"><strong>Surface :</strong> {{ selectedAppartement.superficie || 'N/A' }} m²</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white rounded">
                                                <small class="text-muted d-block">Chambres</small>
                                                <strong>{{ bien.rooms || 0 }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white rounded">
                                                <small class="text-muted d-block">Salons</small>
                                                <strong>{{ bien.living_rooms || 0 }}</strong>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ✅ AFFICHAGE SÉCURISÉ DU MONTANT -->
                                    <div class="mt-3 p-3 bg-white rounded">
                                        <small class="text-muted d-block">{{ getMontantLabel() }}</small>
                                        <h4 class="mb-0 text-success">
                                            {{ formatPrice(calculateAmount()) }} FCFA
                                        </h4>
                                        <small class="text-muted">{{ getAmountDescription() }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire -->
                    <div class="card shadow-lg">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="mb-0">
                                <i class="fas fa-user-edit me-2"></i>
                                Informations nécessaires pour la réservation
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <form @submit.prevent="submitReservation">
                                <div class="row g-4">
                                    <!-- Profession -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            Profession <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            v-model="form.profession"
                                            type="text"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': errors.profession }"
                                            placeholder="Ex: Développeur web"
                                            required
                                        />
                                        <div v-if="errors.profession" class="invalid-feedback">
                                            {{ errors.profession }}
                                        </div>
                                    </div>

                                    <!-- Numéro CNI -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            Numéro CNI <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            v-model="form.numero_cni"
                                            type="text"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': errors.numero_cni }"
                                            placeholder="Numéro de votre carte d'identité"
                                            required
                                        />
                                        <div v-if="errors.numero_cni" class="invalid-feedback">
                                            {{ errors.numero_cni }}
                                        </div>
                                    </div>

                                    <!-- Personne à contacter -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            Personne à contacter (urgence) <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            v-model="form.personne_contact"
                                            type="text"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': errors.personne_contact }"
                                            placeholder="Nom de la personne"
                                            required
                                        />
                                        <div v-if="errors.personne_contact" class="invalid-feedback">
                                            {{ errors.personne_contact }}
                                        </div>
                                    </div>

                                    <!-- Téléphone du contact -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            Téléphone du contact <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            v-model="form.telephone_contact"
                                            type="tel"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': errors.telephone_contact }"
                                            placeholder="+221 XX XXX XX XX"
                                            required
                                        />
                                        <div v-if="errors.telephone_contact" class="invalid-feedback">
                                            {{ errors.telephone_contact }}
                                        </div>
                                    </div>

                                    <!-- Revenus mensuels -->
                                    <div class="col-12">
                                        <label class="form-label fw-bold">
                                            Revenus mensuels <span class="text-danger">*</span>
                                        </label>
                                        <div class="row g-3">
                                            <div class="col-md-3 col-6" v-for="option in revenuOptions" :key="option.value">
                                                <label class="revenue-card">
                                                    <input type="radio" v-model="form.revenus_mensuels" :value="option.value" required />
                                                    <div class="revenue-content">
                                                        <i class="fas fa-coins"></i>
                                                        <span>{{ option.label }}</span>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ✅ DOCUMENTS À FOURNIR - SEULEMENT CNI -->
                                    <div class="col-12">
                                        <div class="card bg-warning bg-opacity-10 border-warning">
                                            <div class="card-body">
                                                <h6 class="fw-bold text-warning mb-3">
                                                    <i class="fas fa-file-upload me-2"></i>
                                                    Document à fournir
                                                </h6>

                                                <!-- Carte d'identité uniquement -->
                                                <div class="mb-0">
                                                    <label class="form-label fw-bold">
                                                        Carte d'identité (CNI/Passeport) <span class="text-danger">*</span>
                                                    </label>
                                                    <input
                                                        type="file"
                                                        @change="handleCarteIdentiteUpload"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': errors.carte_identite }"
                                                        accept=".pdf,.jpg,.jpeg,.png"
                                                        required
                                                    />
                                                    <small class="text-muted">Formats: PDF, JPG, PNG (max 5MB)</small>
                                                    <div v-if="errors.carte_identite" class="invalid-feedback">
                                                        {{ errors.carte_identite }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="mt-4 d-flex justify-content-between">
                                    <Link
                                        :href="route('biens.show', bien.id)"
                                        class="btn btn-outline-secondary btn-lg px-4"
                                    >
                                        <i class="fas fa-arrow-left me-2"></i>Retour
                                    </Link>
                                    <button
                                        type="submit"
                                        class="btn btn-success btn-lg px-5"
                                        :disabled="processing"
                                    >
                                        <i :class="processing ? 'fas fa-spinner fa-spin' : 'fas fa-check'" class="me-2"></i>
                                        {{ processing ? 'Envoi en cours...' : 'Confirmer la réservation' }}
                                    </button>
                                </div>
                            </form>
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
    bien: { type: Object, default: () => null },
    appartement_id: { type: [Number, String], default: null },
    appartements_disponibles: { type: Array, default: () => [] },
    dossier_existant: { type: Object, default: null },
    user: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) }
})

const processing = ref(false)

// ✅ FORMULAIRE SIMPLIFIÉ - SANS QUITTANCE DE LOYER
const form = reactive({
    bien_id: props.bien?.id || null,
    appartement_id: props.appartement_id,
    profession: props.dossier_existant?.profession || '',
    numero_cni: props.dossier_existant?.numero_cni || '',
    personne_contact: props.dossier_existant?.personne_contact || '',
    telephone_contact: props.dossier_existant?.telephone_contact || '',
    revenus_mensuels: props.dossier_existant?.revenus_mensuels || null,
    carte_identite: null,
    // ✅ PAS DE QUITTANCE DE LOYER
})

const revenuOptions = [
    { value: 'plus_100000', label: '+ 100K FCFA' },
    { value: 'plus_200000', label: '+ 200K FCFA' },
    { value: 'plus_300000', label: '+ 300K FCFA' },
    { value: 'plus_500000', label: '+ 500K FCFA' }
]

// ✅ COMPUTED SÉCURISÉS
const isImmeuble = computed(() => {
    if (!props.bien || !props.bien.category) return false
    const categoryName = props.bien.category.name?.toLowerCase()
    const hasAppartements = Array.isArray(props.appartements_disponibles) && props.appartements_disponibles.length > 0
    return categoryName === 'appartement' && hasAppartements
})

const selectedAppartement = computed(() => {
    if (!form.appartement_id || !props.appartements_disponibles) return null
    return props.appartements_disponibles.find(a => a.id === form.appartement_id)
})

const isLocation = computed(() => {
    if (!props.bien || !props.bien.mandat) return false
    return props.bien.mandat.type_mandat === 'gestion_locative'
})

const calculateAmount = () => {
    if (!props.bien || !props.bien.mandat || !props.bien.price) return 0

    return props.bien.mandat.type_mandat === 'vente'
        ? props.bien.price * 0.10
        : props.bien.price
}

const getMontantLabel = () => {
    if (!props.bien || !props.bien.mandat) return 'Montant'
    return props.bien.mandat.type_mandat === 'vente'
        ? 'Acompte de réservation'
        : 'Dépôt de garantie'
}

const getAmountDescription = () => {
    if (!props.bien || !props.bien.mandat) return ''
    return props.bien.mandat.type_mandat === 'vente'
        ? '(10% du prix de vente)'
        : '(1 mois de loyer - caution restituable)'
}

const formatPrice = (price) => new Intl.NumberFormat('fr-FR').format(price || 0)

const getBienImageUrl = (bien) => {
    if (bien?.images && Array.isArray(bien.images) && bien.images.length > 0) {
        return `/storage/${bien.images[0].path || bien.images[0].chemin_image}`
    }
    return '/images/placeholder.jpg'
}

const handleImageError = (event) => {
    event.target.src = '/images/placeholder.jpg'
}

const handleCarteIdentiteUpload = (event) => {
    form.carte_identite = event.target.files[0]
}

// ✅ SOUMISSION SIMPLIFIÉE - SANS QUITTANCE
const submitReservation = () => {
    // Validation uniquement de la CNI
    if (!form.carte_identite) {
        alert("⚠️ La carte d'identité est obligatoire")
        return
    }

    processing.value = true

    const formData = new FormData()
    formData.append('bien_id', form.bien_id)
    formData.append('profession', form.profession)
    formData.append('numero_cni', form.numero_cni)
    formData.append('personne_contact', form.personne_contact)
    formData.append('telephone_contact', form.telephone_contact)
    formData.append('revenus_mensuels', form.revenus_mensuels)
    formData.append('carte_identite', form.carte_identite)

    if (form.appartement_id) {
        formData.append('appartement_id', form.appartement_id)
    }

    console.log('📤 Envoi de la réservation (SANS QUITTANCE):', {
        bien_id: form.bien_id,
        appartement_id: form.appartement_id,
        has_carte_identite: !!form.carte_identite
    })

    router.post(route('reservations.store'), formData, {
        onSuccess: () => {
            console.log('✅ Réservation créée avec succès')
        },
        onError: (errors) => {
            console.error('❌ Erreurs:', errors)
            processing.value = false
        },
        onFinish: () => {
            processing.value = false
        }
    })
}

// ✅ LOGS DE DEBUG AU MONTAGE
onMounted(() => {
    console.log('📋 Réservation/Create - Données reçues:', {
        bien: props.bien,
        has_price: !!props.bien?.price,
        price: props.bien?.price,
        mandat: props.bien?.mandat,
        isLocation: isLocation.value,
        isImmeuble: isImmeuble.value
    })
})
</script>

<style scoped>
.revenue-card {
    display: block;
    cursor: pointer;
    height: 100%;
}

.revenue-card input[type="radio"] {
    display: none;
}

.revenue-content {
    padding: 1.5rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    text-align: center;
    transition: all 0.3s;
    background: white;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.revenue-content i {
    font-size: 1.5rem;
    color: #667eea;
}

.revenue-content span {
    font-size: 0.9rem;
    font-weight: 600;
    color: #374151;
}

.revenue-card input:checked + .revenue-content {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.text-purple-600 {
    color: #7c3aed;
}

.bg-purple-100 {
    background-color: #f3e8ff;
}

.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}
</style>
