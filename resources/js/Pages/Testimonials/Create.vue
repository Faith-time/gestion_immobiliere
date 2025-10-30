<template>
    <div class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- En-tête -->
                    <div class="text-center mb-5">
                        <h1 class="display-5 fw-bold text-primary mb-3">
                            <i class="fas fa-pen-fancy me-2"></i>Partagez votre expérience
                        </h1>
                        <p class="lead text-muted">
                            Votre avis compte pour nous et aide les futurs locataires
                        </p>
                    </div>

                    <!-- Formulaire -->
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-5">
                            <form @submit.prevent="submit">
                                <!-- Sélection de la location -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-home me-2 text-primary"></i>
                                        Pour quelle location souhaitez-vous laisser un avis ?
                                    </label>
                                    <select
                                        v-model="form.location_id"
                                        class="form-select form-select-lg"
                                        :class="{ 'is-invalid': errors.location_id }"
                                        required
                                    >
                                        <option value="">Choisissez une location...</option>
                                        <option
                                            v-for="location in locations"
                                            :key="location.id"
                                            :value="location.id"
                                        >
                                            {{ location.bien.title }} -
                                            {{ formatDate(location.date_debut) }} au {{ formatDate(location.date_fin) }}
                                        </option>
                                    </select>
                                    <div v-if="errors.location_id" class="invalid-feedback">
                                        {{ errors.location_id }}
                                    </div>
                                </div>

                                <!-- Note -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-star me-2 text-warning"></i>
                                        Quelle note donneriez-vous ?
                                    </label>
                                    <div class="rating-input">
                                        <button
                                            v-for="i in 5"
                                            :key="i"
                                            type="button"
                                            class="star-btn"
                                            @click="form.rating = i"
                                            @mouseenter="hoverRating = i"
                                            @mouseleave="hoverRating = 0"
                                        >
                                            <i
                                                class="fas fa-star"
                                                :class="i <= (hoverRating || form.rating) ? 'text-warning' : 'text-muted'"
                                            ></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        {{ ratingText }}
                                    </small>
                                    <div v-if="errors.rating" class="text-danger small mt-1">
                                        {{ errors.rating }}
                                    </div>
                                </div>

                                <!-- Témoignage -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-comment-dots me-2 text-info"></i>
                                        Votre témoignage
                                    </label>
                                    <textarea
                                        v-model="form.content"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.content }"
                                        rows="6"
                                        placeholder="Partagez votre expérience avec notre agence et le bien que vous avez loué..."
                                        maxlength="500"
                                        required
                                    ></textarea>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted">
                                            Minimum 20 caractères
                                        </small>
                                        <small class="text-muted">
                                            {{ form.content.length }}/500
                                        </small>
                                    </div>
                                    <div v-if="errors.content" class="invalid-feedback">
                                        {{ errors.content }}
                                    </div>
                                </div>

                                <!-- Photo (optionnel) -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-camera me-2 text-success"></i>
                                        Photo de profil (optionnel)
                                    </label>
                                    <input
                                        type="file"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.avatar }"
                                        accept="image/*"
                                        @change="handleFileUpload"
                                    >
                                    <small class="text-muted d-block mt-2">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Ajoutez une photo pour personnaliser votre témoignage
                                    </small>
                                    <div v-if="errors.avatar" class="invalid-feedback">
                                        {{ errors.avatar }}
                                    </div>
                                    <!-- Prévisualisation -->
                                    <div v-if="previewUrl" class="mt-3">
                                        <img :src="previewUrl" alt="Prévisualisation" class="img-thumbnail" style="max-width: 150px;">
                                    </div>
                                </div>

                                <!-- Information -->
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Note :</strong> Votre témoignage sera publié après validation par notre équipe.
                                </div>

                                <!-- Boutons -->
                                <div class="d-flex justify-content-between gap-3">
                                    <Link
                                        :href="route('dashboard')"
                                        class="btn btn-outline-secondary btn-lg px-4"
                                    >
                                        <i class="fas fa-arrow-left me-2"></i>Annuler
                                    </Link>
                                    <button
                                        type="submit"
                                        class="btn btn-primary btn-lg px-5"
                                        :disabled="processing"
                                    >
                                        <i :class="processing ? 'fas fa-spinner fa-spin me-2' : 'fas fa-paper-plane me-2'"></i>
                                        {{ processing ? 'Envoi en cours...' : 'Publier mon témoignage' }}
                                    </button>
                                </div>
                            </form>
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
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps({
    locations: Array,
    errors: Object
})

const form = ref({
    location_id: '',
    content: '',
    rating: 5,
    avatar: null
})

const processing = ref(false)
const hoverRating = ref(0)
const previewUrl = ref(null)

const ratingText = computed(() => {
    const rating = hoverRating.value || form.value.rating
    const texts = {
        1: '⭐ Décevant',
        2: '⭐⭐ Moyen',
        3: '⭐⭐⭐ Bien',
        4: '⭐⭐⭐⭐ Très bien',
        5: '⭐⭐⭐⭐⭐ Excellent'
    }
    return texts[rating] || ''
})

const handleFileUpload = (event) => {
    const file = event.target.files[0]
    if (file) {
        form.value.avatar = file
        previewUrl.value = URL.createObjectURL(file)
    }
}

const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const submit = () => {
    processing.value = true

    const formData = new FormData()
    formData.append('location_id', form.value.location_id)
    formData.append('content', form.value.content)
    formData.append('rating', form.value.rating)

    if (form.value.avatar) {
        formData.append('avatar', form.value.avatar)
    }

    router.post(route('testimonials.store'), formData, {
        preserveState: false,
        preserveScroll: false,
        onSuccess: () => {
            console.log('Témoignage créé avec succès')
        },
        onError: (errors) => {
            console.error('Erreurs:', errors)
            processing.value = false
        },
        onFinish: () => {
            setTimeout(() => {
                processing.value = false
            }, 1000)
        }
    })
}
</script>

<style scoped>
.rating-input {
    display: flex;
    gap: 10px;
    margin: 15px 0;
}

.star-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    font-size: 36px;
    transition: all 0.2s ease;
}

.star-btn:hover {
    transform: scale(1.2);
}

.star-btn i {
    transition: color 0.2s ease;
}

textarea.form-control {
    border-radius: 12px;
    border: 2px solid #e9ecef;
    padding: 15px;
    font-size: 15px;
}

textarea.form-control:focus {
    border-color: #17a2b8;
    box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
}

.form-select {
    border-radius: 12px;
    border: 2px solid #e9ecef;
}

.form-select:focus {
    border-color: #17a2b8;
    box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
}

.alert-info {
    background: linear-gradient(135deg, #e3f2fd, #b3e5fc);
    border-left: 4px solid #17a2b8;
    border-radius: 12px;
}

.btn-primary {
    background-color: #17a2b8;
    border-color: #17a2b8;
    transition: all 0.3s ease;
}

.btn-primary:hover:not(:disabled) {
    background-color: #138496;
    border-color: #117a8b;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(23, 162, 184, 0.3);
}

.btn-primary:disabled {
    background-color: #6c757d;
    border-color: #6c757d;
    cursor: not-allowed;
    opacity: 0.65;
}
</style>
