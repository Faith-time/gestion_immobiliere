<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- En-tête -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-credit-card me-3 fs-4"></i>
                            <div>
                                <h4 class="mb-0">Finaliser le paiement</h4>
                                <small class="opacity-75">
                                    {{ getTypeLabel(type) }} - {{ formatDate(item.created_at) }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Résumé de la commande -->
                    <div class="col-lg-5">
                        <div class="card shadow-sm h-100">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-receipt me-2"></i>Résumé
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Image du bien -->
                                <div class="mb-3">
                                    <img :src="item.bien?.image ? `/storage/${item.bien.image}` : '/images/placeholder.jpg'"
                                         :alt="item.bien?.title"
                                         class="img-fluid rounded"
                                         style="width: 100%; height: 200px; object-fit: cover;">
                                </div>

                                <!-- Détails du bien -->
                                <h6 class="text-primary">{{ item.bien?.title }}</h6>
                                <p class="text-muted mb-3">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    {{ item.bien?.city }}
                                </p>

                                <!-- Détails du paiement -->
                                <div class="border rounded p-3 bg-light">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ getMontantLabel(type) }}:</span>
                                        <strong class="text-success">
                                            {{ formatPrice(paiement?.montant_total || item.montant) }} FCFA
                                        </strong>
                                    </div>

                                    <div v-if="paiement?.commission_agence"
                                         class="d-flex justify-content-between mb-2 text-muted">
                                        <small>Commission agence:</small>
                                        <small>{{ formatPrice(paiement.commission_agence) }} FCFA</small>
                                    </div>

                                    <hr class="my-2">

                                    <div class="d-flex justify-content-between">
                                        <strong>Total à payer:</strong>
                                        <strong class="text-success fs-5">
                                            {{ formatPrice(paiement?.montant_total || item.montant) }} FCFA
                                        </strong>
                                    </div>
                                </div>

                                <!-- Informations du client -->
                                <div class="mt-3 pt-3 border-top">
                                    <small class="text-muted">Client</small>
                                    <p class="mb-1"><strong>{{ user.nom }} {{ user.prenom }}</strong></p>
                                    <p class="mb-0 text-muted">{{ user.email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire de paiement -->
                    <div class="col-lg-7">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-lock me-2"></i>Informations de paiement
                                </h5>
                            </div>
                            <div class="card-body">
                                <form @submit.prevent="submitPaiement">
                                    <!-- Mode de paiement -->
                                    <div class="mb-4">
                                        <label class="form-label">
                                            <i class="fas fa-payment me-1"></i>Mode de paiement
                                        </label>
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <input type="radio"
                                                       class="btn-check"
                                                       name="mode_paiement"
                                                       id="mobile_money"
                                                       value="mobile_money"
                                                       v-model="form.mode_paiement">
                                                <label class="btn btn-outline-primary w-100 py-3" for="mobile_money">
                                                    <i class="fas fa-mobile-alt d-block mb-1"></i>
                                                    <small>Mobile Money</small>
                                                </label>
                                            </div>
                                            <div class="col-4">
                                                <input type="radio"
                                                       class="btn-check"
                                                       name="mode_paiement"
                                                       id="carte"
                                                       value="carte"
                                                       v-model="form.mode_paiement">
                                                <label class="btn btn-outline-primary w-100 py-3" for="carte">
                                                    <i class="fas fa-credit-card d-block mb-1"></i>
                                                    <small>Carte bancaire</small>
                                                </label>
                                            </div>
                                            <div class="col-4">
                                                <input type="radio"
                                                       class="btn-check"
                                                       name="mode_paiement"
                                                       id="virement"
                                                       value="virement"
                                                       v-model="form.mode_paiement">
                                                <label class="btn btn-outline-primary w-100 py-3" for="virement">
                                                    <i class="fas fa-university d-block mb-1"></i>
                                                    <small>Virement</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Informations client -->
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label">Nom complet *</label>
                                            <input type="text"
                                                   class="form-control"
                                                   v-model="form.customer_name"
                                                   required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email *</label>
                                            <input type="email"
                                                   class="form-control"
                                                   v-model="form.customer_email"
                                                   required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Téléphone *</label>
                                            <input type="tel"
                                                   class="form-control"
                                                   v-model="form.customer_phone"
                                                   placeholder="+221 XX XXX XX XX"
                                                   required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Description (optionnel)</label>
                                            <textarea class="form-control"
                                                      rows="3"
                                                      v-model="form.description"
                                                      :placeholder="`Paiement ${getTypeLabel(type).toLowerCase()} - ${item.bien?.title}`"></textarea>
                                        </div>
                                    </div>

                                    <!-- Sécurité -->
                                    <div class="alert alert-info mb-4">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-shield-alt me-2"></i>
                                            <div>
                                                <strong>Paiement sécurisé</strong>
                                                <p class="mb-0 small">Vos données sont protégées par un cryptage SSL 256 bits et traitées par CinetPay.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Conditions -->
                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   v-model="form.acceptConditions"
                                                   id="acceptConditions"
                                                   required>
                                            <label class="form-check-label" for="acceptConditions">
                                                J'accepte les <a href="#" class="text-primary">conditions générales</a>
                                                et la <a href="#" class="text-primary">politique de confidentialité</a>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="d-grid gap-2">
                                        <button type="submit"
                                                class="btn btn-success btn-lg"
                                                :disabled="loading || !form.acceptConditions">
                                            <div v-if="loading" class="d-flex align-items-center justify-content-center">
                                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                                Traitement en cours...
                                            </div>
                                            <div v-else class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-lock me-2"></i>
                                                Payer {{ formatPrice(paiement?.montant_total || item.montant) }} FCFA
                                            </div>
                                        </button>

                                        <Link :href="getPreviousPageRoute()"
                                              class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Retour
                                        </Link>
                                    </div>
                                </form>
                            </div>
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
import { Link } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'

const props = defineProps({
    type: { type: String, required: true },
    item: { type: Object, required: true },
    paiement: { type: Object, required: true },
    user: { type: Object, required: true }
})

// État réactif
const loading = ref(false)
const form = ref({
    paiement_id: props.paiement.id,
    mode_paiement: 'mobile_money',
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    description: '',
    acceptConditions: false
})

// Initialiser le formulaire
onMounted(() => {
    form.value.customer_name = props.user.name
    form.value.customer_email = props.user.email
    form.value.customer_phone = props.user.telephone || ''
    form.value.description = `Paiement ${getTypeLabel(props.type).toLowerCase()} - ${props.item.bien?.title}`
})

// Méthodes
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const getTypeLabel = (type) => {
    const labels = {
        'reservation': 'Réservation',
        'location': 'Location',
        'vente': 'Vente'
    }
    return labels[type] || type
}

const getMontantLabel = (type) => {
    const labels = {
        'reservation': 'Caution de réservation',
        'location': 'Montant de la location',
        'vente': 'Prix de vente'
    }
    return labels[type] || 'Montant'
}

const getPreviousPageRoute = () => {
    switch (props.type) {
        case 'reservation':
            return route('reservations.index')
        case 'location':
            return route('locations.index') // À adapter selon vos routes
        case 'vente':
            return route('ventes.index') // À adapter selon vos routes
        default:
            return route('home')
    }
}

const submitPaiement = () => {
    if (!form.value.acceptConditions) {
        alert('Veuillez accepter les conditions générales')
        return
    }

    loading.value = true

    try {
        // Créer un formulaire HTML classique pour la soumission
        const formElement = document.createElement('form')
        formElement.method = 'POST'
        formElement.action = route('paiement.initier')

        // Méthode 1: Essayer de récupérer le token CSRF depuis la balise meta
        let csrfToken = null
        const csrfMeta = document.querySelector('meta[name="csrf-token"]')

        if (csrfMeta) {
            csrfToken = csrfMeta.getAttribute('content')
        }

        // Méthode 2: Si pas trouvé, essayer depuis le document Inertia
        if (!csrfToken && window?.Laravel?.csrfToken) {
            csrfToken = window.Laravel.csrfToken
        }

        // Méthode 3: Si toujours pas trouvé, essayer depuis la page Inertia
        if (!csrfToken && window?.document?.head?.querySelector) {
            const altCsrf = window.document.head.querySelector('meta[name="csrf-token"]')
            if (altCsrf) {
                csrfToken = altCsrf.content
            }
        }

        if (!csrfToken) {
            throw new Error('Token CSRF introuvable. Veuillez actualiser la page.')
        }

        // Ajouter le token CSRF
        const csrfInput = document.createElement('input')
        csrfInput.type = 'hidden'
        csrfInput.name = '_token'
        csrfInput.value = csrfToken
        formElement.appendChild(csrfInput)

        // Ajouter tous les champs du formulaire
        Object.keys(form.value).forEach(key => {
            if (form.value[key] !== null && form.value[key] !== undefined && form.value[key] !== '') {
                const input = document.createElement('input')
                input.type = 'hidden'
                input.name = key
                input.value = form.value[key]
                formElement.appendChild(input)
            }
        })

        // Ajouter le formulaire au DOM et le soumettre
        document.body.appendChild(formElement)

        // Petite pause pour s'assurer que le formulaire est bien dans le DOM
        setTimeout(() => {
            formElement.submit()
        }, 100)

    } catch (error) {
        console.error('Erreur lors de la soumission:', error)
        alert('Erreur: ' + error.message)
        loading.value = false
    }
}

</script>

<style scoped>
.btn-check:checked + .btn {
    background-color: var(--bs-primary) !important;
    border-color: var(--bs-primary) !important;
    color: white !important;
}

.card {
    border: none;
}

.alert-info {
    background-color: rgba(13, 202, 240, 0.1);
    border-color: rgba(13, 202, 240, 0.2);
}

@media (max-width: 768px) {
    .row.g-4 > .col-lg-5,
    .row.g-4 > .col-lg-7 {
        order: 2;
    }

    .row.g-4 > .col-lg-5 {
        order: 1;
    }
}
</style>
