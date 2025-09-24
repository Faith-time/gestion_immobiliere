<template>
    <div class="container py-5">
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
                        <form @submit.prevent="submitLocation">
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
                                        :min="tomorrow"
                                        required
                                    />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="duree_mois">
                                        Durée (en mois) <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        id="duree_mois"
                                        v-model="form.duree_mois"
                                        class="form-select"
                                        required
                                    >
                                        <option value="">Choisir la durée...</option>
                                        <option value="6">6 mois</option>
                                        <option value="12">1 an</option>
                                        <option value="24">2 ans</option>
                                        <option value="36">3 ans</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Récapitulatif automatique -->
                            <div v-if="form.duree_mois" class="alert alert-info mt-4">
                                <h6><i class="fas fa-calculator me-2"></i>Récapitulatif :</h6>
                                <ul class="mb-0">
                                    <li>Loyer mensuel : <strong>{{ formatPrice(bien.price) }} FCFA</strong></li>
                                    <li>Durée : <strong>{{ form.duree_mois }} mois</strong></li>
                                    <li>Total sur la période : <strong>{{ formatPrice(bien.price * form.duree_mois) }} FCFA</strong></li>
                                </ul>
                            </div>
                        </form>
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
                                <i class="fas fa-key me-2" v-if="!processing"></i>
                                <i class="fas fa-spinner fa-spin me-2" v-if="processing"></i>
                                {{ processing ? 'Traitement...' : 'Créer la demande de location' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import {route} from "ziggy-js";

// ✅ Déclaration des props
const props = defineProps({
    bien: {
        type: Object,
        required: true
    }
})

// ✅ Utilisation de useForm (plus clean que reactive + router.post)
const form = useForm({
    bien_id: props.bien?.id || null,
    date_debut: '',
    duree_mois: ''
})

// ✅ Indicateur de processing
const processing = ref(false)

// ✅ Calcul de la date min (demain)
const tomorrow = computed(() => {
    const date = new Date()
    date.setDate(date.getDate() + 1)
    return date.toISOString().split('T')[0]
})

// ✅ Format prix avec séparateur français
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const submitLocation = () => {
if (!form.date_debut || !form.duree_mois || !form.bien_id) return;

processing.value = true;

form.post(route('locations.store'), {
    onSuccess: (page) => {
        const flash = page.props?.flash;
        if (flash?.success) {
            console.log('✅', flash.success);
        }
        router.visit(route('locations.index'))

    },
    onError: (errors) => {
        console.error('❌ Erreurs:', errors);
    },
    onFinish: () => {
        processing.value = false;
    }
});
}

</script>
