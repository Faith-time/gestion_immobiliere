<template>
    <!-- Modal de demande de visite -->
    <div class="modal fade" id="modalDemandeVisite" tabindex="-1" aria-labelledby="modalDemandeVisiteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="modalDemandeVisiteLabel">
                        <i class="fas fa-calendar-alt me-2"></i>Demander une visite
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Informations du bien -->
                    <div class="bg-light rounded p-3 mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="text-primary mb-2">{{ bien.title }}</h6>
                                <p class="text-muted mb-1 small">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    {{ bien.address }}, {{ bien.city }}
                                </p>
                                <p class="text-muted mb-1 small">
                                    <i class="fas fa-tag me-2"></i>
                                    {{ bien.category?.name }}
                                </p>
                                <p class="mb-0">
                                    <strong class="text-success">{{ formatPrice(bien.price) }} FCFA</strong>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <img
                                    v-if="getBienImageUrl(bien)"
                                    :src="getBienImageUrl(bien)"
                                    :alt="bien.title"
                                    class="img-fluid rounded"
                                    style="max-height: 100px; width: 100%; object-fit: cover;">
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire -->
                    <form @submit.prevent="submitForm">
                        <div class="row g-3">
                            <!-- Date de visite souhaitée -->
                            <div class="col-md-12">
                                <label for="date_visite" class="form-label required">
                                    <i class="fas fa-calendar me-2"></i>Date et heure de visite souhaitée
                                </label>
                                <input
                                    id="date_visite"
                                    v-model="form.date_visite"
                                    type="datetime-local"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.date_visite }"
                                    :min="minDateTime"
                                    required>
                                <div v-if="form.errors.date_visite" class="invalid-feedback">
                                    {{ form.errors.date_visite }}
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-info-circle me-1"></i>
                                    La visite doit être programmée au moins 24h à l'avance
                                </small>
                            </div>

                            <!-- Message/Commentaires -->
                            <div class="col-12">
                                <label for="message" class="form-label">
                                    <i class="fas fa-comment me-2"></i>Message ou demandes spéciales
                                </label>
                                <textarea
                                    id="message"
                                    v-model="form.message"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.message }"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="Précisez vos préférences d'horaire ou toute information utile..."></textarea>
                                <div v-if="form.errors.message" class="invalid-feedback">
                                    {{ form.errors.message }}
                                </div>
                                <small class="text-muted">
                                    {{ form.message?.length || 0 }}/500 caractères
                                </small>
                            </div>

                            <!-- Informations importantes -->
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    <h6 class="alert-heading mb-2">
                                        <i class="fas fa-info-circle me-2"></i>À savoir
                                    </h6>
                                    <ul class="mb-0 ps-3 small">
                                        <li>Un agent immobilier vous accompagnera lors de la visite</li>
                                        <li>Vous recevrez une confirmation par email avec les détails</li>
                                        <li>La visite dure généralement entre 30 et 60 minutes</li>
                                        <li>N'hésitez pas à poser toutes vos questions durant la visite</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button
                        type="button"
                        @click="submitForm"
                        class="btn btn-primary"
                        :disabled="form.processing">
                        <i v-if="form.processing" class="fas fa-spinner fa-spin me-2"></i>
                        <i v-else class="fas fa-paper-plane me-2"></i>
                        {{ form.processing ? 'Envoi en cours...' : 'Envoyer la demande' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import { route } from "ziggy-js"

const props = defineProps({
    bien: { type: Object, required: true }
})

// Formulaire
const form = useForm({
    bien_id: props.bien.id,
    date_visite: '',
    message: ''
})

// Date/heure minimale (demain 9h)
const minDateTime = computed(() => {
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    tomorrow.setHours(9, 0, 0, 0)
    return tomorrow.toISOString().slice(0, 16)
})

// Fonction d'envoi
const submitForm = () => {
    if (form.processing) return

    form.post(route('visites.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Fermer la modal
            const modalElement = document.getElementById('modalDemandeVisite')
            if (modalElement && typeof window.bootstrap !== 'undefined') {
                const modal = window.bootstrap.Modal.getInstance(modalElement)
                if (modal) {
                    modal.hide()
                }
            }

            // Réinitialiser le formulaire
            form.reset()

            // Message de succès (optionnel)
            alert('Votre demande de visite a été envoyée avec succès !')
        },
        onError: (errors) => {
            console.error('Erreurs de validation:', errors)
        }
    })
}

// Helpers
const formatPrice = (price) => {
    if (!price) return '0'
    return new Intl.NumberFormat('fr-FR').format(price)
}

const getBienImageUrl = (bien) => {
    if (bien?.images && Array.isArray(bien.images) && bien.images.length > 0) {
        return bien.images[0].url
    }
    if (bien?.image) {
        return `/storage/${bien.image}`
    }
    return '/images/placeholder.jpg'
}

// Réinitialiser le formulaire quand la modal se ferme
watch(() => props.bien.id, () => {
    form.reset()
    form.bien_id = props.bien.id
})
</script>

<style scoped>
.required::after {
    content: ' *';
    color: #dc3545;
}

.modal-content {
    border-radius: 15px;
    border: none;
}

.modal-header {
    border-radius: 15px 15px 0 0;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.btn {
    border-radius: 8px;
    padding: 0.6rem 1.2rem;
    font-weight: 500;
}

.alert {
    border-radius: 10px;
    border: none;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.btn-close-white {
    filter: brightness(0) invert(1);
}
</style>
