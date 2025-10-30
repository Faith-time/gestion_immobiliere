<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
        <!-- Header -->
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-800 py-12">
            <div class="relative max-w-4xl mx-auto px-4">
                <div class="text-center">
                    <h1 class="text-4xl font-extrabold text-white mb-3 tracking-tight">
                        Modifier l'appartement
                    </h1>
                    <p class="text-lg text-blue-100">
                        {{ bien.title }} - {{ appartement.numero }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Bouton retour -->
            <div class="mb-6">
                <button
                    @click="retourAuBien"
                    class="inline-flex items-center px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition-colors shadow"
                >
                    <i class="fas fa-arrow-left me-2"></i>
                    Retour aux appartements
                </button>
            </div>

            <!-- Formulaire -->
            <div class="bg-white/70 backdrop-blur-lg rounded-3xl shadow-xl p-8">
                <form @submit.prevent="submitForm">
                    <!-- Informations de base -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-info-circle text-indigo-600 me-3"></i>
                            Informations de base
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Numéro -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Numéro d'appartement <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.numero"
                                    type="text"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    placeholder="Ex: A1, 101, RDC-1..."
                                />
                                <p v-if="errors.numero" class="mt-1 text-sm text-red-600">{{ errors.numero }}</p>
                            </div>

                            <!-- Étage -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Étage <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model.number="form.etage"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                >
                                    <option value="">Sélectionner un étage</option>
                                    <option v-for="etage in etagesDisponibles" :key="etage" :value="etage">
                                        {{ getEtageLabel(etage) }}
                                    </option>
                                </select>
                                <p v-if="errors.etage" class="mt-1 text-sm text-red-600">{{ errors.etage }}</p>
                            </div>

                            <!-- Superficie -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Superficie (m²)
                                </label>
                                <input
                                    v-model.number="form.superficie"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    placeholder="Ex: 45.5"
                                />
                                <p v-if="errors.superficie" class="mt-1 text-sm text-red-600">{{ errors.superficie }}</p>
                            </div>

                            <!-- Statut -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Statut <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.statut"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                >
                                    <option value="disponible">Disponible</option>
                                    <option value="loue">Loué</option>
                                    <option value="reserve">Réservé</option>
                                    <option value="maintenance">En maintenance</option>
                                </select>
                                <p v-if="errors.statut" class="mt-1 text-sm text-red-600">{{ errors.statut }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Caractéristiques -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-home text-indigo-600 me-3"></i>
                            Caractéristiques
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <!-- Salons -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-couch text-indigo-500 me-2"></i>
                                    Salons
                                </label>
                                <input
                                    v-model.number="form.salons"
                                    type="number"
                                    min="0"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                />
                            </div>

                            <!-- Chambres -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-bed text-indigo-500 me-2"></i>
                                    Chambres
                                </label>
                                <input
                                    v-model.number="form.chambres"
                                    type="number"
                                    min="0"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                />
                            </div>

                            <!-- Salles de bain -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-bath text-indigo-500 me-2"></i>
                                    Salles de bain
                                </label>
                                <input
                                    v-model.number="form.salles_bain"
                                    type="number"
                                    min="0"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                />
                            </div>

                            <!-- Cuisines -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-utensils text-indigo-500 me-2"></i>
                                    Cuisines
                                </label>
                                <input
                                    v-model.number="form.cuisines"
                                    type="number"
                                    min="0"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-file-alt text-indigo-600 me-3"></i>
                            Description
                        </h2>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Description de l'appartement
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="5"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors resize-none"
                                placeholder="Décrivez les particularités de cet appartement..."
                            ></textarea>
                            <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <button
                            type="button"
                            @click="retourAuBien"
                            class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium"
                        >
                            <i class="fas fa-times me-2"></i>
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all font-medium shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i class="fas fa-save me-2"></i>
                            {{ processing ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps({
    bien: Object,
    appartement: Object,
    dernier_etage: Number,
    errors: {
        type: Object,
        default: () => ({})
    }
})

// État du formulaire
const processing = ref(false)
const form = ref({
    numero: props.appartement.numero,
    etage: props.appartement.etage,
    superficie: props.appartement.superficie,
    statut: props.appartement.statut,
    salons: props.appartement.salons,
    chambres: props.appartement.chambres,
    salles_bain: props.appartement.salles_bain,
    cuisines: props.appartement.cuisines,
    description: props.appartement.description
})

// Calcul des étages disponibles
const etagesDisponibles = computed(() => {
    const etages = []
    for (let i = 0; i <= props.dernier_etage; i++) {
        etages.push(i)
    }
    return etages
})

// Fonction pour obtenir le libellé de l'étage
const getEtageLabel = (etage) => {
    const labels = {
        0: 'Rez-de-chaussée',
        1: '1er étage',
        2: '2ème étage',
        3: '3ème étage'
    }
    return labels[etage] || `${etage}ème étage`
}

// Soumettre le formulaire
const submitForm = () => {
    processing.value = true

    router.put(
        route('appartements.update', {
            bien: props.bien.id,
            appartement: props.appartement.id
        }),
        form.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                processing.value = false
            },
            onError: () => {
                processing.value = false
            }
        }
    )
}

// Retour à la liste des appartements
const retourAuBien = () => {
    router.visit(route('appartements.index', props.bien.id))
}
</script>

<style scoped>
/* Animation pour les champs focus */
input:focus,
select:focus,
textarea:focus {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Transition douce pour tous les éléments interactifs */
button,
input,
select,
textarea {
    transition: all 0.2s ease-in-out;
}
</style>
