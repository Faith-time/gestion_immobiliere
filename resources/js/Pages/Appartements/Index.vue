<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
        <!-- Header -->
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-800 py-16">
            <div class="relative max-w-7xl mx-auto px-4">
                <div class="text-center mb-8">
                    <h1 class="text-5xl font-extrabold text-white mb-4 tracking-tight">
                        Appartements - {{ bien.title }}
                    </h1>
                    <p class="text-xl text-blue-100 max-w-2xl mx-auto">
                        <i class="fas fa-map-marker-alt me-2"></i>{{ bien.address }}, {{ bien.city }}
                    </p>
                </div>

                <!-- Statistiques -->
                <div v-if="stats" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold text-white">{{ stats.total }}</div>
                        <div class="text-sm text-blue-100">Total</div>
                    </div>
                    <div class="bg-green-500/20 backdrop-blur-sm rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold text-white">{{ stats.disponibles }}</div>
                        <div class="text-sm text-green-100">Disponibles</div>
                    </div>
                    <div class="bg-blue-500/20 backdrop-blur-sm rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold text-white">{{ stats.loues }}</div>
                        <div class="text-sm text-blue-100">Loués</div>
                    </div>
                    <div class="bg-purple-500/20 backdrop-blur-sm rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold text-white">{{ stats.taux_occupation }}%</div>
                        <div class="text-sm text-purple-100">Occupation</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Bouton retour -->
            <div class="mb-6">
                <button
                    @click="retourAuBien"
                    class="inline-flex items-center px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition-colors shadow"
                >
                    <i class="fas fa-arrow-left me-2"></i>
                    Retour au bien
                </button>
            </div>

            <!-- Message si aucun appartement -->
            <div v-if="!appartements || appartements.length === 0" class="text-center py-16">
                <div class="bg-white/70 backdrop-blur-lg rounded-3xl shadow-xl p-12 mx-auto max-w-md">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-building text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Aucun appartement</h3>
                    <p class="text-gray-600">
                        Ce bien ne contient aucun appartement pour le moment.
                    </p>
                </div>
            </div>

            <!-- Liste des appartements -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="appartement in appartements"
                    :key="appartement.id"
                    class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-lg border border-white/20 overflow-hidden hover:shadow-2xl transition-all duration-300"
                >
                    <!-- En-tête -->
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-4 text-white">
                        <h3 class="text-xl font-bold mb-1">{{ appartement.numero }}</h3>
                        <p class="text-indigo-100 text-sm">{{ getEtageLabel(appartement.etage) }}</p>
                    </div>

                    <!-- Contenu -->
                    <div class="p-6">
                        <!-- Statut -->
                        <div class="mb-4">
                            <span :class="getStatutClass(appartement.statut)" class="px-3 py-1 rounded-full text-xs font-semibold">
                                {{ getStatutLabel(appartement.statut) }}
                            </span>
                        </div>

                        <!-- Caractéristiques -->
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-ruler-combined text-indigo-500 w-5 me-2"></i>
                                <span class="text-sm">{{ appartement.superficie }} m²</span>
                            </div>
                            <div v-if="appartement.salons" class="flex items-center text-gray-700">
                                <i class="fas fa-couch text-indigo-500 w-5 me-2"></i>
                                <span class="text-sm">{{ appartement.salons }} salon(s)</span>
                            </div>
                            <div v-if="appartement.chambres" class="flex items-center text-gray-700">
                                <i class="fas fa-bed text-indigo-500 w-5 me-2"></i>
                                <span class="text-sm">{{ appartement.chambres }} chambre(s)</span>
                            </div>
                            <div v-if="appartement.salles_bain" class="flex items-center text-gray-700">
                                <i class="fas fa-bath text-indigo-500 w-5 me-2"></i>
                                <span class="text-sm">{{ appartement.salles_bain }} salle(s) de bain</span>
                            </div>
                            <div v-if="appartement.cuisines" class="flex items-center text-gray-700">
                                <i class="fas fa-utensils text-indigo-500 w-5 me-2"></i>
                                <span class="text-sm">{{ appartement.cuisines }} cuisine(s)</span>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="appartement.description" class="mb-4">
                            <p class="text-sm text-gray-600 line-clamp-2">{{ appartement.description }}</p>
                        </div>

                        <!-- Locataire actuel -->
                        <div v-if="appartement.location_active && appartement.location_active.client" class="mb-4 p-3 bg-blue-50 rounded-lg">
                            <div class="text-xs text-blue-800 font-semibold mb-1">Locataire actuel</div>
                            <div class="text-sm text-blue-900">{{ appartement.location_active.client.name }}</div>
                        </div>

                        <!-- Actions -->
                        <div class="flex space-x-2">
                            <button
                                @click="editerAppartement(appartement)"
                                class="flex-1 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium transition-colors"
                            >
                                <i class="fas fa-edit me-1"></i>
                                Modifier
                            </button>
                            <button
                                @click="supprimerAppartement(appartement)"
                                :disabled="appartement.statut === 'loue'"
                                class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <i class="fas fa-trash me-1"></i>
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps({
    bien: Object,
    appartements: Array,
    stats: Object
})

const getEtageLabel = (etage) => {
    const labels = {
        0: 'Rez-de-chaussée',
        1: '1er étage',
        2: '2ème étage',
        3: '3ème étage'
    }
    return labels[etage] || `${etage}ème étage`
}

const getStatutClass = (statut) => {
    const classes = {
        'disponible': 'bg-green-100 text-green-800',
        'loue': 'bg-blue-100 text-blue-800',
        'reserve': 'bg-yellow-100 text-yellow-800',
        'maintenance': 'bg-red-100 text-red-800'
    }
    return classes[statut] || 'bg-gray-100 text-gray-800'
}

const getStatutLabel = (statut) => {
    const labels = {
        'disponible': 'Disponible',
        'loue': 'Loué',
        'reserve': 'Réservé',
        'maintenance': 'En maintenance'
    }
    return labels[statut] || statut
}

const retourAuBien = () => {
    router.visit(route('biens.show', props.bien.id))
}

const editerAppartement = (appartement) => {
    router.visit(route('appartements.edit', {
        bien: props.bien.id,
        appartement: appartement.id
    }))
}

const supprimerAppartement = (appartement) => {
    if (appartement.statut === 'loue') {
        alert('Impossible de supprimer un appartement loué')
        return
    }

    if (confirm(`Êtes-vous sûr de vouloir supprimer l'appartement ${appartement.numero} ?`)) {
        router.delete(route('appartements.destroy', {
            bien: props.bien.id,
            appartement: appartement.id
        }))
    }
}
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
