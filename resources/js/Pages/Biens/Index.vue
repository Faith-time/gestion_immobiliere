<script setup>
import { Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref } from 'vue'
import Layout from '@/Pages/Layout.vue'

defineOptions({
    layout: Layout
})

const props = defineProps({
    biens: Array
})

const isDeleting = ref({})

const deleteBien = async (bien) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le bien "${bien.title}" ? Cette action est irréversible.`)) {
        isDeleting.value[bien.id] = true

        try {
            await router.delete(route('biens.destroy', bien.id), {
                onFinish: () => {
                    isDeleting.value[bien.id] = false
                },
                onError: (errors) => {
                    console.error('Erreur lors de la suppression:', errors)
                    alert('Une erreur est survenue lors de la suppression du bien.')
                }
            })
        } catch (error) {
            console.error('Erreur:', error)
            isDeleting.value[bien.id] = false
        }
    }
}

const getStatusClass = (status) => {
    const statusClasses = {
        'disponible': 'badge-success',
        'vendu': 'badge-danger',
        'loué': 'badge-warning',
        'en_attente': 'badge-info',
        'réservé': 'badge-secondary'
    }
    return statusClasses[status?.toLowerCase()] || 'badge-secondary'
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(price) + ' FCFA'
}

console.log('Bien reçus :', props.biens)
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="bg-white rounded-2xl shadow-xl mb-8 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-700 px-8 py-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-white mb-2">🏠 Gestion Immobilière</h1>
                            <p class="text-blue-100">Gérez votre portefeuille de biens immobiliers</p>
                        </div>
                        <Link
                            :href="route('biens.create')"
                            class="group inline-flex items-center px-6 py-3 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl"
                        >
                            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Nouveau Bien
                        </Link>
                    </div>
                </div>

                <!-- Stats Section -->
                <div class="px-8 py-4 bg-gray-50 border-b">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">{{ props.biens?.length || 0 }}</span> bien(s) au total
                        </div>
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                            Vue tableau
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div v-if="!props.biens?.length" class="text-center py-16">
                    <div class="text-6xl mb-4">🏘️</div>
                    <h3 class="text-2xl font-semibold text-gray-800 mb-2">Aucun bien trouvé</h3>
                    <p class="text-gray-600 mb-6">Commencez par ajouter votre premier bien immobilier</p>
                    <Link
                        :href="route('biens.create')"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Ajouter un bien
                    </Link>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Image</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Informations</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Caractéristiques</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Prix</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Localisation</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="bien in props.biens"
                            :key="bien.id"
                            class="hover:bg-blue-50 transition-colors duration-150"
                        >
                            <!-- Image -->
                            <td class="px-6 py-4">
                                <div class="relative group">
                                    <img
                                        v-if="bien.image"
                                        :src="bien.image.startsWith('http') ? bien.image : `/storage/${bien.image}`"
                                        :alt="bien.title"
                                        class="w-20 h-16 object-cover rounded-xl shadow-md group-hover:shadow-lg transition-shadow duration-200"
                                        @error="$event.target.style.display = 'none'; $event.target.nextElementSibling.style.display = 'flex'"
                                    />
                                    <div class="w-20 h-16 bg-gradient-to-br from-gray-200 to-gray-300 rounded-xl flex items-center justify-center" :style="bien.image ? 'display: none' : 'display: flex'">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </td>

                            <!-- Informations -->
                            <td class="px-6 py-4">
                                <div class="max-w-xs">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-1">{{ bien.title }}</h4>
                                    <p class="text-sm text-gray-600 line-clamp-2">{{ bien.description }}</p>
                                    <span class="inline-block mt-2 px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
                                            {{ bien.category?.name ?? 'Non défini' }}
                                        </span>
                                </div>
                            </td>

                            <!-- Caractéristiques -->
                            <td class="px-6 py-4">
                                <div class="space-y-2">
                                    <div class="flex items-center text-sm text-gray-700">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        {{ bien.rooms }} chambres
                                    </div>
                                    <div class="flex items-center text-sm text-gray-700">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10v11M20 10v11"></path>
                                        </svg>
                                        {{ bien.floors }} étages
                                    </div>
                                    <div class="flex items-center text-sm text-gray-700">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                        </svg>
                                        {{ bien.bathrooms }} SDB • {{ bien.superficy }}m²
                                    </div>
                                </div>
                            </td>

                            <!-- Prix -->
                            <td class="px-6 py-4">
                                <div class="text-xl font-bold text-green-600">
                                    {{ formatPrice(bien.price) }}
                                </div>
                            </td>

                            <!-- Statut -->
                            <td class="px-6 py-4">
                                    <span :class="[
                                        'inline-flex px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide',
                                        getStatusClass(bien.status)
                                    ]">
                                        {{ bien.status }}
                                    </span>
                            </td>

                            <!-- Localisation -->
                            <td class="px-6 py-4">
                                <div class="max-w-xs">
                                    <div class="flex items-center text-sm font-medium text-gray-900 mb-1">
                                        <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        {{ bien.city }}
                                    </div>
                                    <p class="text-xs text-gray-600 truncate">{{ bien.address }}</p>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right space-x-2">
                                <Link
                                    :href="route('biens.edit', bien.id)"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 shadow-sm hover:shadow-md"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Modifier
                                </Link>
                                <br>
                                <br>

                                <Link
                                    @click="deleteBien(bien)"
                                    :disabled="isDeleting[bien.id]"
                                    :class="[
                                            'inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2',
                                            isDeleting[bien.id]
                                                ? 'bg-gray-400 text-white cursor-not-allowed'
                                                : 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500'
                                        ]"
                                >
                                    <svg
                                        v-if="isDeleting[bien.id]"
                                        class="w-4 h-4 mr-1 animate-spin"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg
                                        v-else
                                        class="w-4 h-4 mr-1"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    {{ isDeleting[bien.id] ? 'Suppression...' : 'Supprimer' }}
                                </Link>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.badge-success {
    @apply bg-green-100 text-green-800;
}

.badge-danger {
    @apply bg-red-100 text-red-800;
}

.badge-warning {
    @apply bg-yellow-100 text-yellow-800;
}

.badge-info {
    @apply bg-blue-100 text-blue-800;
}

.badge-secondary {
    @apply bg-gray-100 text-gray-800;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
