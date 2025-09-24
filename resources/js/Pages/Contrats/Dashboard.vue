<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        Gestion des Contrats
                    </h1>
                    <p class="text-gray-600 mt-2">
                        Gérez vos contrats de vente et de location avec signature électronique
                    </p>
                </div>

                <div class="flex space-x-3">
                    <button
                        @click="activeTab = 'ventes'"
                        :class="`px-4 py-2 rounded-lg font-medium transition-colors ${
                            activeTab === 'ventes'
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-600 hover:bg-gray-50'
                        }`"
                    >
                        Ventes
                    </button>
                    <button
                        @click="activeTab = 'locations'"
                        :class="`px-4 py-2 rounded-lg font-medium transition-colors ${
                            activeTab === 'locations'
                                ? 'bg-green-600 text-white'
                                : 'bg-white text-gray-600 hover:bg-gray-50'
                        }`"
                    >
                        Locations
                    </button>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6 border border-white/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Contrats Signés</p>
                            <p class="text-2xl font-bold text-green-600">
                                {{ stats.signed_count }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border border-white/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">En Attente</p>
                            <p class="text-2xl font-bold text-yellow-600">
                                {{ stats.pending_count }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border border-white/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Non Signés</p>
                            <p class="text-2xl font-bold text-gray-600">
                                {{ stats.unsigned_count }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border border-white/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total</p>
                            <p class="text-2xl font-bold text-blue-600">
                                {{ stats.total_count }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border border-white/20">
                <div class="flex flex-wrap gap-4 items-center">
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700">Statut:</label>
                        <select
                            v-model="filters.status"
                            class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">Tous</option>
                            <option value="non_signe">Non signé</option>
                            <option value="partiellement_signe">Partiellement signé</option>
                            <option value="entierement_signe">Entièrement signé</option>
                        </select>
                    </div>

                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700">Recherche:</label>
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Rechercher..."
                            class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                    </div>

                    <button
                        @click="resetFilters"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-sm"
                    >
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Liste des contrats -->
            <div class="bg-white rounded-xl shadow-lg border border-white/20 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ activeTab === 'ventes' ? 'Bien / Parties' : 'Bien / Parties' }}
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ activeTab === 'ventes' ? 'Prix' : 'Loyer' }}
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut Signature
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="contract in filteredContracts" :key="contract.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ contract.bien.title }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ contract.bien.city }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                            <span v-if="activeTab === 'ventes'">
                                                {{ contract.bien.proprietaire.name }} → {{ contract.acheteur.name }}
                                            </span>
                                        <span v-else>
                                                {{ contract.bien.proprietaire.name }} → {{ contract.client.name }}
                                            </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                    <span v-if="activeTab === 'ventes'">
                                        {{ formatPrice(contract.prix_vente) }} FCFA
                                    </span>
                                <span v-else>
                                        {{ formatPrice(contract.loyer_mensuel) }} FCFA/mois
                                    </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                    <span v-if="activeTab === 'ventes'">
                                        {{ formatDate(contract.date_vente) }}
                                    </span>
                                <span v-else>
                                        {{ formatDate(contract.date_debut) }}
                                    </span>
                            </td>
                            <td class="px-6 py-4">
                                    <span :class="getStatusBadgeClass(contract.signature_status)">
                                        {{ getStatusText(contract.signature_status) }}
                                    </span>
                                <div class="mt-2 space-y-1">
                                    <div v-if="activeTab === 'ventes'" class="flex space-x-2 text-xs">
                                            <span :class="contract.vendeur_signed_at ? 'text-green-600' : 'text-gray-400'">
                                                {{ contract.vendeur_signed_at ? '✓' : '○' }} Vendeur
                                            </span>
                                        <span :class="contract.acheteur_signed_at ? 'text-green-600' : 'text-gray-400'">
                                                {{ contract.acheteur_signed_at ? '✓' : '○' }} Acheteur
                                            </span>
                                    </div>
                                    <div v-else class="flex space-x-2 text-xs">
                                            <span :class="contract.bailleur_signed_at ? 'text-green-600' : 'text-gray-400'">
                                                {{ contract.bailleur_signed_at ? '✓' : '○' }} Bailleur
                                            </span>
                                        <span :class="contract.locataire_signed_at ? 'text-green-600' : 'text-gray-400'">
                                                {{ contract.locataire_signed_at ? '✓' : '○' }} Locataire
                                            </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm space-y-2">
                                <div class="flex space-x-2">
                                    <button
                                        @click="viewContract(contract)"
                                        class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-xs"
                                    >
                                        Voir
                                    </button>
                                    <button
                                        @click="signContract(contract)"
                                        :disabled="!canSign(contract)"
                                        :class="`px-3 py-1 rounded-lg text-xs transition-colors ${
                                                canSign(contract)
                                                    ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            }`"
                                    >
                                        Signer
                                    </button>
                                </div>
                                <div class="flex space-x-2">
                                    <button
                                        @click="previewPdf(contract)"
                                        class="px-3 py-1 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors text-xs"
                                    >
                                        PDF
                                    </button>
                                    <button
                                        @click="downloadPdf(contract)"
                                        class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors text-xs"
                                    >
                                        ↓
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="filteredContracts.length === 0" class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-gray-500 text-lg">Aucun contrat trouvé</p>
                    <p class="text-gray-400 text-sm mt-2">Modifiez vos filtres pour voir plus de résultats</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps({
    ventes: Array,
    locations: Array,
    userRoles: Array,
    stats: Object,
})

// États réactifs
const activeTab = ref('ventes')
const filters = ref({
    status: '',
    search: ''
})

// Contrats filtrés selon l'onglet actif
const activeContracts = computed(() => {
    return activeTab.value === 'ventes' ? props.ventes : props.locations
})

// Filtrage des contrats
const filteredContracts = computed(() => {
    let contracts = activeContracts.value || []

    // Filtrer par statut
    if (filters.value.status) {
        contracts = contracts.filter(contract =>
            contract.signature_status === filters.value.status
        )
    }

    // Filtrer par recherche
    if (filters.value.search) {
        const search = filters.value.search.toLowerCase()
        contracts = contracts.filter(contract =>
            contract.bien.title.toLowerCase().includes(search) ||
            contract.bien.city.toLowerCase().includes(search) ||
            contract.bien.proprietaire.name.toLowerCase().includes(search) ||
            (activeTab.value === 'ventes'
                    ? contract.acheteur.name.toLowerCase().includes(search)
                    : contract.client.name.toLowerCase().includes(search)
            )
        )
    }

    return contracts
})

// Méthodes utilitaires
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR')
}

const getStatusText = (status) => {
    const statusLabels = {
        'non_signe': 'Non signé',
        'partiellement_signe': 'Partiellement signé',
        'entierement_signe': 'Entièrement signé'
    }
    return statusLabels[status] || 'Inconnu'
}

const getStatusBadgeClass = (status) => {
    const baseClasses = 'px-2 py-1 rounded-full text-xs font-medium border'
    const statusClasses = {
        'non_signe': 'bg-gray-100 border-gray-300 text-gray-700',
        'partiellement_signe': 'bg-yellow-100 border-yellow-300 text-yellow-700',
        'entierement_signe': 'bg-green-100 border-green-300 text-green-700'
    }
    return `${baseClasses} ${statusClasses[status] || statusClasses['non_signe']}`
}

const canSign = (contract) => {
    const user = props.userRoles || []

    if (activeTab.value === 'ventes') {
        // Pour une vente, vérifier si l'utilisateur peut signer
        const isVendeur = contract.bien.proprietaire_id === getCurrentUserId()
        const isAcheteur = contract.acheteur_id === getCurrentUserId()

        return (isVendeur && !contract.vendeur_signed_at) ||
            (isAcheteur && !contract.acheteur_signed_at)
    } else {
        // Pour une location
        const isBailleur = contract.bien.proprietaire_id === getCurrentUserId()
        const isLocataire = contract.client_id === getCurrentUserId()

        return (isBailleur && !contract.bailleur_signed_at) ||
            (isLocataire && !contract.locataire_signed_at)
    }
}

const getCurrentUserId = () => {
    // Cette fonction devrait retourner l'ID de l'utilisateur connecté
    // À adapter selon votre système d'authentification
    return window.auth?.user?.id || null
}

// Actions
const resetFilters = () => {
    filters.value = {
        status: '',
        search: ''
    }
}

const viewContract = (contract) => {
    const routeName = activeTab.value === 'ventes' ? 'ventes.show' : 'locations.show'
    router.visit(route(routeName, contract.id))
}

const signContract = (contract) => {
    if (!canSign(contract)) return

    const routeName = activeTab.value === 'ventes' ? 'ventes.signature.show' : 'locations.signature.show'
    router.visit(route(routeName, contract.id))
}

const previewPdf = (contract) => {
    const routeName = activeTab.value === 'ventes'
        ? 'ventes.contract.preview'
        : 'locations.contract.preview'

    window.open(route(routeName, contract.id), '_blank')
}

const downloadPdf = (contract) => {
    const routeName = activeTab.value === 'ventes'
        ? 'ventes.contract.download'
        : 'locations.contract.download'

    window.location.href = route(routeName, contract.id)
}

// Initialisation
onMounted(() => {
    // Logique d'initialisation si nécessaire
})
</script>
