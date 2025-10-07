<template>
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

        <div v-if="$page.props.flash?.warning" class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ $page.props.flash.warning }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <!-- Erreurs de validation -->
        <div v-if="Object.keys(errors).length > 0" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Erreurs de validation :</strong>
            <ul class="mb-0 mt-2">
                <li v-for="(error, key) in errors" :key="key">
                    {{ Array.isArray(error) ? error[0] : error }}
                </li>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- En-tête -->
                <div class="text-center mb-5">
                    <h1 class="h2 text-primary">Demande de Location</h1>
                    <p class="text-muted">Finalisez votre demande de location pour cette propriété</p>
                </div>

                <!-- Récapitulatif du bien -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <img
                                    :src="bien.image ? `/storage/${bien.image}` : '/images/placeholder.jpg'"
                                    :alt="bien.title"
                                    class="img-fluid rounded"
                                />
                            </div>
                            <div class="col-md-9">
                                <h5 class="card-title text-primary">{{ bien.title }}</h5>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-map-marker-alt me-2"></i>{{ bien.address }}, {{ bien.city }}
                                </p>
                                <p class="text-muted mb-2">{{ bien.category?.name }}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-success mb-0">{{ formatPrice(bien.price) }} FCFA/mois</span>
                                    <span class="badge bg-info">
                                        Caution: {{ formatPrice(bien.price * 2) }} FCFA
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-key me-2"></i>Détails de la location
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="date_debut">
                                    Date de début <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="date_debut"
                                    v-model="form.date_debut"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.date_debut }"
                                    :min="tomorrow"
                                    required
                                />
                                <div v-if="errors.date_debut" class="invalid-feedback">
                                    {{ errors.date_debut }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="duree_mois">
                                    Durée (en mois) <span class="text-danger">*</span>
                                </label>
                                <select
                                    id="duree_mois"
                                    v-model="form.duree_mois"
                                    class="form-select"
                                    :class="{ 'is-invalid': errors.duree_mois }"
                                    required
                                >
                                    <option value="">Choisir la durée...</option>
                                    <option value="6">6 mois</option>
                                    <option value="12">1 an</option>
                                    <option value="24">2 ans</option>
                                    <option value="36">3 ans</option>
                                </select>
                                <div v-if="errors.duree_mois" class="invalid-feedback">
                                    {{ errors.duree_mois }}
                                </div>
                            </div>
                        </div>

                        <!-- Récapitulatif automatique -->
                        <div v-if="form.duree_mois" class="alert alert-info mt-4">
                            <h6><i class="fas fa-calculator me-2"></i>Récapitulatif du paiement initial :</h6>
                            <ul class="mb-0">
                                <li>Premier mois de loyer : <strong>{{ formatPrice(bien.price) }} FCFA</strong></li>
                                <li>Caution (2 mois) : <strong>{{ formatPrice(bien.price * 2) }} FCFA</strong></li>
                                <li class="text-primary fw-bold mt-2">Total à payer maintenant : <strong>{{ formatPrice(montantInitial) }} FCFA</strong></li>
                            </ul>
                            <hr class="my-2">
                            <p class="text-muted mb-0 small">
                                <i class="fas fa-info-circle me-1"></i>
                                Durée totale : {{ form.duree_mois }} mois ({{ formatPrice(bien.price * form.duree_mois) }} FCFA sur toute la période)
                            </p>
                        </div>

                        <!-- Information paiement -->
                        <div class="alert alert-warning mt-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
                                <div>
                                    <strong>Important :</strong>
                                    <p class="mb-0 small">
                                        Après validation, vous serez redirigé vers PayDunya pour effectuer le paiement sécurisé du premier mois et de la caution.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between">
                            <Link
                                :href="route('biens.show', bien.id)"
                                class="btn btn-outline-secondary"
                            >
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </Link>
                            <button
                                @click="submitLocation"
                                class="btn btn-primary px-4"
                                :disabled="processing || !form.date_debut || !form.duree_mois"
                            >
                                <i class="fas fa-credit-card me-2" v-if="!processing"></i>
                                <i class="fas fa-spinner fa-spin me-2" v-if="processing"></i>
                                {{ processing ? 'Redirection vers le paiement...' : 'Procéder au Paiement' }}
                            </button>
                        </div>
                    </div>
                </div>
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
import { ref, computed } from 'vue'
import { route } from "ziggy-js"

const props = defineProps({
    bien: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    }
})

const form = ref({
    bien_id: props.bien?.id || null,
    date_debut: '',
    duree_mois: ''
})

const processing = ref(false)

const tomorrow = computed(() => {
    const date = new Date()
    date.setDate(date.getDate() + 1)
    return date.toISOString().split('T')[0]
})

// Calcul du montant initial (1er mois + 2 mois de caution)
const montantInitial = computed(() => {
    if (!props.bien?.price) return 0
    return props.bien.price * 3 // 1 mois + 2 mois de caution
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

// ✅ CORRECTION : Utiliser router.post comme pour les réservations et ventes
const submitLocation = () => {
    if (!form.value.date_debut || !form.value.duree_mois || !form.value.bien_id) {
        alert('⚠️ Veuillez remplir tous les champs requis.')
        return
    }

    if (processing.value) return

    processing.value = true

    const locationData = {
        bien_id: form.value.bien_id,
        date_debut: form.value.date_debut,
        duree_mois: form.value.duree_mois
    }

    // ✅ UTILISER router.post pour suivre automatiquement les redirections
    router.post(route('locations.store'), locationData, {
        preserveState: false,
        preserveScroll: false,
        onSuccess: (page) => {
            console.log('✅ Location créée, redirection vers paiement')
            // Inertia gère automatiquement la redirection
        },
        onError: (errors) => {
            processing.value = false
            console.error('❌ Erreurs:', errors)

            // Afficher les erreurs
            let errorMessage = 'Erreurs détectées:\n'
            Object.values(errors).forEach(error => {
                errorMessage += '• ' + (Array.isArray(error) ? error[0] : error) + '\n'
            })
            alert(errorMessage)
        },
        onFinish: () => {
            processing.value = false
        }
    })
}
</script>

<style scoped>
.alert {
    border-radius: 0.5rem;
}

.card {
    border: none;
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0 !important;
}

.badge {
    font-size: 0.85rem;
    padding: 0.5rem 0.75rem;
}

.btn {
    font-weight: 500;
}

.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
}
</style>
