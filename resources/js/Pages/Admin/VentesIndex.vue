<template>
    <AppLayout title="Liste des Ventes">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Liste des Ventes
                </h2>
                <Link
                    :href="route('ventes.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out"
                >
                    Nouvelle Vente
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Messages Flash -->
                <div v-if="$page.props.flash.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ $page.props.flash.success }}
                </div>

                <div v-if="$page.props.flash.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ $page.props.flash.error }}
                </div>

                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- En-tête avec statistiques -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Gestion des Ventes
                            </h3>
                            <div class="text-sm text-gray-600">
                                Total: {{ ventes.length }} vente{{ ventes.length > 1 ? 's' : '' }}
                            </div>
                        </div>

                        <!-- Statistiques rapides -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-green-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-8 w-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-green-800">Chiffre d'affaires</p>
                                        <p class="text-2xl font-bold text-green-900">{{ formatPrice(totalCA) }} FCFA</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-8 w-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-blue-800">Ventes ce mois</p>
                                        <p class="text-2xl font-bold text-blue-900">{{ ventesThisMonth }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-purple-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-8 w-8 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-purple-800">Prix moyen</p>
                                        <p class="text-2xl font-bold text-purple-900">{{ formatPrice(averagePrice) }} FCFA</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tableau des ventes -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" v-if="ventes.length > 0">
                            <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Bien
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acheteur
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Prix de Vente
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date de Vente
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="vente in ventes" :key="vente.id" class="hover:bg-gray-50">
                                <!-- Bien -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12" v-if="vente.bien.image">
                                            <img
                                                class="h-12 w-12 rounded-lg object-cover"
                                                :src="`/storage/${vente.bien.image}`"
                                                :alt="vente.bien.title"
                                            />
                                        </div>
                                        <div class="flex-shrink-0 h-12 w-12 bg-gray-200 rounded-lg flex items-center justify-center" v-else>
                                            <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 13-3 3-3-3" />
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ vente.bien.title }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ vente.bien.city }} - {{ vente.bien.address }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Acheteur -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-blue-800">
                                                        {{ getInitials(vente.acheteur.name) }}
                                                    </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ vente.acheteur.name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ vente.acheteur.email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Prix de vente -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-green-900">
                                        {{ formatPrice(vente.prix_vente) }} FCFA
                                    </div>
                                </td>

                                <!-- Date de vente -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(vente.date_vente) }}
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center space-x-3">
                                        <Link
                                            :href="route('ventes.show', vente.id)"
                                            class="text-blue-600 hover:text-blue-900 transition duration-150 ease-in-out"
                                            title="Voir détails"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>

                                        <Link
                                            :href="route('ventes.edit', vente.id)"
                                            class="text-indigo-600 hover:text-indigo-900 transition duration-150 ease-in-out"
                                            title="Modifier"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>

                                        <button
                                            @click="confirmDelete(vente)"
                                            class="text-red-600 hover:text-red-900 transition duration-150 ease-in-out"
                                            title="Supprimer"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <!-- État vide -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune vente</h3>
                            <p class="mt-1 text-sm text-gray-500">Commencez par créer votre première vente.</p>
                            <div class="mt-6">
                                <Link
                                    :href="route('ventes.create')"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Nouvelle Vente
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation de suppression -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <svg class="w-20 h-20 text-red-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.732 15.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mt-5">Confirmer la suppression</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Êtes-vous sûr de vouloir supprimer la vente du bien "{{ venteToDelete?.bien?.title }}" ?
                            Cette action ne peut pas être annulée.
                        </p>
                    </div>
                    <div class="flex gap-4 items-center px-4 py-3">
                        <button
                            @click="showDeleteModal = false"
                            class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                        >
                            Annuler
                        </button>
                        <button
                            @click="deleteVente"
                            class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300"
                        >
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../Layout.vue'

// Props
const props = defineProps({
    ventes: Array
})

// Variables réactives pour le modal
const showDeleteModal = ref(false)
const venteToDelete = ref(null)

// Statistiques calculées
const totalCA = computed(() => {
    return props.ventes.reduce((sum, vente) => sum + vente.prix_vente, 0)
})

const ventesThisMonth = computed(() => {
    const currentMonth = new Date().getMonth()
    const currentYear = new Date().getFullYear()

    return props.ventes.filter(vente => {
        const venteDate = new Date(vente.date_vente)
        return venteDate.getMonth() === currentMonth && venteDate.getFullYear() === currentYear
    }).length
})

const averagePrice = computed(() => {
    if (props.ventes.length === 0) return 0
    return totalCA.value / props.ventes.length
})

// Fonctions utilitaires
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

const getInitials = (name) => {
    return name.split(' ').map(word => word.charAt(0).toUpperCase()).join('').substring(0, 2)
}

// Gestion de la suppression
const confirmDelete = (vente) => {
    venteToDelete.value = vente
    showDeleteModal.value = true
}

const deleteVente = () => {
    if (venteToDelete.value) {
        router.delete(route('ventes.destroy', venteToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false
                venteToDelete.value = null
            },
            onError: () => {
                showDeleteModal.value = false
                venteToDelete.value = null
            }
        })
    }
}
</script>

<style scoped>
/* Styles personnalisés pour les animations et transitions */
.hover\:bg-gray-50:hover {
    transition: background-color 0.15s ease-in-out;
}

/* Style pour les boutons d'action */
.text-blue-600:hover {
    transform: scale(1.1);
    transition: transform 0.1s ease-in-out;
}

.text-indigo-600:hover {
    transform: scale(1.1);
    transition: transform 0.1s ease-in-out;
}

.text-red-600:hover {
    transform: scale(1.1);
    transition: transform 0.1s ease-in-out;
}

/* Animation pour le modal */
.fixed.inset-0 {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Style pour les cartes de statistiques */
.bg-green-50:hover,
.bg-blue-50:hover,
.bg-purple-50:hover {
    transform: translateY(-2px);
    transition: transform 0.2s ease-in-out;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>
