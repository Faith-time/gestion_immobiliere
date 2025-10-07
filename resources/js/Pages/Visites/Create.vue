<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Demander une visite
                        </h4>
                    </div>

                    <div class="card-body">
                        <!-- Informations du bien -->
                        <div class="bg-light rounded p-4 mb-4">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="text-primary mb-2">{{ bien.title }}</h5>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        {{ bien.address }}, {{ bien.city }}
                                    </p>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-tag me-2"></i>
                                        {{ bien.category?.name }}
                                    </p>
                                    <p class="mb-0">
                                        <strong class="text-success">{{ formatPrice(bien.price) }} FCFA</strong>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <img
                                        v-if="bien.image"
                                        :src="getBienImageUrl(bien.image)"
                                        :alt="bien.title"
                                        class="img-fluid rounded"
                                        style="max-height: 120px; width: 100%; object-fit: cover;">
                                </div>
                            </div>
                        </div>

                        <!-- Formulaire -->
                        <form @submit.prevent="submitForm">
                            <div class="row g-4">
                                <!-- Date de visite souhaitée -->
                                <div class="col-md-6">
                                    <label for="date_visite" class="form-label required">
                                        <i class="fas fa-calendar me-2"></i>Date de visite souhaitée
                                    </label>
                                    <input
                                        id="date_visite"
                                        v-model="form.date_visite"
                                        type="datetime-local"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.date_visite }"
                                        :min="minDateTime"
                                        required>
                                    <div v-if="errors.date_visite" class="invalid-feedback">
                                        {{ errors.date_visite }}
                                    </div>
                                    <small class="text-muted">
                                        La visite doit être programmée au moins 24h à l'avance
                                    </small>
                                </div>

                                <!-- Champ vide pour l'alignement -->
                                <div class="col-md-6"></div>

                                <!-- Message/Commentaires -->
                                <div class="col-12">
                                    <label for="message" class="form-label">
                                        <i class="fas fa-comment me-2"></i>Message ou demandes spéciales
                                    </label>
                                    <textarea
                                        id="message"
                                        v-model="form.message"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.message }"
                                        rows="4"
                                        maxlength="500"
                                        placeholder="Précisez vos préférences d'horaire ou toute information utile..."></textarea>
                                    <div v-if="errors.message" class="invalid-feedback">
                                        {{ errors.message }}
                                    </div>
                                    <small class="text-muted">
                                        {{ form.message?.length || 0 }}/500 caractères
                                    </small>
                                </div>

                                <!-- Informations importantes -->
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <h6 class="alert-heading">
                                            <i class="fas fa-info-circle me-2"></i>À savoir
                                        </h6>
                                        <ul class="mb-0 ps-3">
                                            <li>Un agent immobilier vous accompagnera lors de la visite</li>
                                            <li>Vous recevrez une confirmation par email avec les détails</li>
                                            <li>La visite dure généralement entre 30 et 60 minutes</li>
                                            <li>N'hésitez pas à poser toutes vos questions durant la visite</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-3 mt-4">
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    :disabled="processing">
                                    <i v-if="processing" class="fas fa-spinner fa-spin me-2"></i>
                                    <i v-else class="fas fa-paper-plane me-2"></i>
                                    {{ processing ? 'Envoi en cours...' : 'Envoyer la demande' }}
                                </button>

                                <Link :href="route('biens.show', bien.id)" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Retour au bien
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {Link, router, useForm} from '@inertiajs/vue3'
import { computed } from 'vue'
import {route} from "ziggy-js";

const props = defineProps({
    bien: { type: Object, required: true },
    userRoles: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) }
})

// Formulaire
const form = useForm({
    bien_id: props.bien.id,
    date_visite: '',
    message: ''
})

// Date/heure minimale
const minDateTime = computed(() => {
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    tomorrow.setHours(9, 0, 0, 0)
    return tomorrow.toISOString().slice(0, 16) // datetime-local veut YYYY-MM-DDTHH:MM
})

// Fonction d’envoi
const submitForm = () => {
    form.post(route('visites.store'), {
        onSuccess: () => {
            console.log('Visite enregistrée avec succès')
        },
        onError: (errors) => {
            console.error(errors)
        }
    })
}

// Helpers
const formatPrice = (price) => new Intl.NumberFormat().format(price)

const getBienImageUrl = (imagePath) => {
    return `/storage/${imagePath}`
}
</script>

<style scoped>
.required::after {
    content: ' *';
    color: #dc3545;
}

.card {
    border-radius: 15px;
    overflow: hidden;
}

.card-header {
    border-bottom: none;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.btn {
    border-radius: 8px;
    padding: 0.6rem 1.2rem;
}

.alert {
    border-radius: 10px;
    border: none;
}

.bg-light {
    background-color: #f8f9fa !important;
}
</style>
