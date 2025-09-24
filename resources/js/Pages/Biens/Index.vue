<script setup>
import { router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { route } from 'ziggy-js'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    biens: Array,
    userRoles: Array,
})

const searchTerm = ref('')
const selectedStatus = ref('')
const showValidationModal = ref(false)
const selectedBien = ref(null)
const showRejectionModal = ref(false)
const rejectionReason = ref('')

const filteredBiens = computed(() => {
    let filtered = props.biens

    // Filtrage par terme de recherche
    if (searchTerm.value) {
        filtered = filtered.filter(bien =>
            bien.title.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            bien.city.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            bien.address.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            (bien.proprietaire && bien.proprietaire.name.toLowerCase().includes(searchTerm.value.toLowerCase()))
        )
    }

    // Filtrage par statut
    if (selectedStatus.value) {
        filtered = filtered.filter(bien => bien.status === selectedStatus.value)
    }

    return filtered
})

// Vérifier si l'utilisateur est admin
const isAdmin = computed(() => {
    return props.userRoles && props.userRoles.includes('admin')
})

// Vérifier si l'utilisateur est propriétaire
const isProprietaire = computed(() => {
    return props.userRoles && props.userRoles.includes('proprietaire')
})

// Compter les biens par statut
const biensStats = computed(() => {
    return {
        total: props.biens.length,
        en_validation: props.biens.filter(b => b.status === 'en_validation').length,
        disponible: props.biens.filter(b => b.status === 'disponible').length,
        loue: props.biens.filter(b => b.status === 'loue').length,
        vendu: props.biens.filter(b => b.status === 'vendu').length,
        reserve: props.biens.filter(b => b.status === 'reserve').length,
    }
})

// Fonction pour vérifier si l'utilisateur peut voir les boutons PDF
const canAccessPdf = (bien) => {
    if (!bien.mandat || bien.mandat.statut !== 'actif') {
        return false
    }

    // Admin peut toujours accéder
    if (isAdmin.value) {
        return true
    }

    // Propriétaire peut accéder à ses propres biens
    if (isProprietaire.value && bien.proprietaire_id === getCurrentUserId()) {
        return true
    }

    return false
}

// Fonction pour obtenir l'ID de l'utilisateur actuel
const getCurrentUserId = () => {
    return usePage().props.auth?.user?.id || null
}

const getStatusColor = (status) => {
    const colors = {
        'en_validation': 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'disponible': 'bg-green-100 text-green-800 border-green-200',
        'loue': 'bg-blue-100 text-blue-800 border-blue-200',
        'vendu': 'bg-red-100 text-red-800 border-red-200',
        'reserve': 'bg-purple-100 text-purple-800 border-purple-200',
    }
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200'
}

const getStatusLabel = (status) => {
    const labels = {
        'en_validation': 'En validation',
        'disponible': 'Disponible',
        'loue': 'Loué',
        'vendu': 'Vendu',
        'reserve': 'Réservé',
    }
    return labels[status] || status
}

const getMandatTypeColor = (typeMandat) => {
    return typeMandat === 'vente'
        ? 'bg-emerald-100 text-emerald-800 border-emerald-200'
        : 'bg-cyan-100 text-cyan-800 border-cyan-200'
}

const getMandatTypeLabel = (typeMandat) => {
    return typeMandat === 'vente' ? 'Vente' : 'Location'
}

const getMandatStatusColor = (statut) => {
    const colors = {
        'en_attente': 'bg-orange-100 text-orange-800 border-orange-200',
        'actif': 'bg-green-100 text-green-800 border-green-200',
        'expire': 'bg-red-100 text-red-800 border-red-200',
        'rejete': 'bg-gray-100 text-gray-800 border-gray-200'
    }
    return colors[statut] || 'bg-gray-100 text-gray-800 border-gray-200'
}

const getMandatStatusLabel = (statut) => {
    const labels = {
        'en_attente': 'En attente',
        'actif': 'Actif',
        'expire': 'Expiré',
        'rejete': 'Rejeté'
    }
    return labels[statut] || statut
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR')
}

const createBien = () => {
    router.visit(route('biens.create'))
}

const editBien = (bien) => {
    router.visit(route('biens.edit', bien.id))
}

const showBien = (bien) => {
    router.visit(route('biens.show', bien.id))
}

const deleteBien = (bien) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le bien "${bien.title}" ? Cette action est irréversible.`)) {
        router.delete(route('biens.destroy', bien.id))
    }
}

// Fonctions de validation pour admin
const openValidationModal = (bien) => {
    selectedBien.value = bien
    showValidationModal.value = true
}

const closeValidationModal = () => {
    showValidationModal.value = false
    selectedBien.value = null
}

const validateBien = () => {
    if (selectedBien.value) {
        router.post(route('biens.valider', selectedBien.value.id), {}, {
            onSuccess: () => {
                closeValidationModal()
                router.reload()
            },
            onError: (errors) => {
                console.error('Erreur lors de la validation:', errors)
                alert('Erreur lors de la validation du bien')
            }
        })
    }
}

const rejectBien = () => {
    if (selectedBien.value && rejectionReason.value.trim()) {
        router.post(route('biens.rejeter', selectedBien.value.id), {
            motif_rejet: rejectionReason.value
        }, {
            onSuccess: () => {
                closeRejectionModal()
                router.reload()
            },
            onError: (errors) => {
                console.error('Erreur lors du rejet:', errors)
                alert('Erreur lors du rejet du bien')
            }
        })
    }
}

const openRejectionModal = (bien) => {
    selectedBien.value = bien
    rejectionReason.value = ''
    showRejectionModal.value = true
}

const closeRejectionModal = () => {
    showRejectionModal.value = false
    selectedBien.value = null
    rejectionReason.value = ''
}

const filterByStatus = (status) => {
    selectedStatus.value = selectedStatus.value === status ? '' : status
}

// Fonctions PDF corrigées
const downloadMandatPdf = (bien) => {
    if (!bien.mandat) {
        alert('Aucun mandat trouvé pour ce bien')
        return
    }

    if (!canAccessPdf(bien)) {
        alert('Vous n\'êtes pas autorisé à télécharger ce mandat')
        return
    }

    window.open(route('biens.download-mandat-pdf', bien.id), '_blank')
}

const previewMandatPdf = (bien) => {
    if (!bien.mandat) {
        alert('Aucun mandat trouvé pour ce bien')
        return
    }

    if (!canAccessPdf(bien)) {
        alert('Vous n\'êtes pas autorisé à prévisualiser ce mandat')
        return
    }

    window.open(route('biens.preview-mandat-pdf', bien.id), '_blank')
}

const regenerateMandatPdf = (bien) => {
    if (!bien.mandat) {
        alert('Aucun mandat trouvé pour ce bien')
        return
    }

    if (!isAdmin.value) {
        alert('Seuls les administrateurs peuvent régénérer les PDFs')
        return
    }

    if (confirm('Êtes-vous sûr de vouloir régénérer le PDF du mandat ? L\'ancien fichier sera remplacé.')) {
        router.post(route('biens.regenerate-mandat-pdf', bien.id), {}, {
            onSuccess: () => {
                router.reload()
            },
            onError: (errors) => {
                console.error('Erreur lors de la régénération:', errors)
                alert('Erreur lors de la régénération du PDF du mandat')
            }
        })
    }
}

const showSignaturePage = (bien) => {
    router.visit(route('biens.mandat.sign', bien.id))
}

// Fonction pour vérifier si l'utilisateur peut signer
const canSignMandat = (bien) => {
    if (!bien.mandat || bien.mandat.statut !== 'actif') {
        return false
    }

    // Propriétaire peut toujours signer son mandat
    if (isProprietaire.value && bien.proprietaire_id === getCurrentUserId()) {
        return true
    }

    // Admin peut signer pour l'agence
    if (isAdmin.value) {
        return true
    }

    return false
}

// Fonction pour obtenir le statut de signature
const getSignatureStatusBadge = (mandat) => {
    if (!mandat.signature_status) {
        return { text: 'Non signé', color: 'bg-gray-100 text-gray-800 border-gray-200' }
    }

    const statusMap = {
        'non_signe': { text: 'Non signé', color: 'bg-red-100 text-red-800 border-red-200' },
        'partiellement_signe': { text: 'Partiellement signé', color: 'bg-orange-100 text-orange-800 border-orange-200' },
        'entierement_signe': { text: 'Entièrement signé', color: 'bg-green-100 text-green-800 border-green-200' }
    }

    return statusMap[mandat.signature_status] || statusMap['non_signe']
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-green-50">
        <!-- Header avec animation -->
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-green-600 to-indigo-800 py-16">
            <div class="absolute inset-0 bg-black/20"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-green-300/20 rounded-full blur-3xl"></div>

            <div class="relative max-w-7xl mx-auto px-4">
                <div class="text-center mb-8">
                    <h1 class="text-5xl font-extrabold text-white mb-4 tracking-tight">
                        {{ isAdmin ? 'Gestion des Biens' : 'Mes Biens Immobiliers' }}
                    </h1>
                    <p class="text-xl text-blue-100 max-w-2xl mx-auto">
                        {{ isAdmin ? 'Validez et gérez tous les biens immobiliers' : 'Gérez vos propriétés et leurs mandats' }}
                    </p>
                </div>

                <!-- Statistiques pour admin -->
                <div v-if="isAdmin" class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-8">
                    <div
                        @click="filterByStatus('')"
                        :class="{'ring-2 ring-white': selectedStatus === ''}"
                        class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center cursor-pointer hover:bg-white/30 transition-all duration-300"
                    >
                        <div class="text-2xl font-bold text-white">{{ biensStats.total }}</div>
                        <div class="text-sm text-blue-100">Total</div>
                    </div>
                    <div
                        @click="filterByStatus('en_validation')"
                        :class="{'ring-2 ring-white': selectedStatus === 'en_validation'}"
                        class="bg-yellow-500/20 backdrop-blur-sm rounded-xl p-4 text-center cursor-pointer hover:bg-yellow-500/30 transition-all duration-300"
                    >
                        <div class="text-2xl font-bold text-white">{{ biensStats.en_validation }}</div>
                        <div class="text-sm text-yellow-100">En validation</div>
                    </div>
                    <div
                        @click="filterByStatus('disponible')"
                        :class="{'ring-2 ring-white': selectedStatus === 'disponible'}"
                        class="bg-green-500/20 backdrop-blur-sm rounded-xl p-4 text-center cursor-pointer hover:bg-green-500/30 transition-all duration-300"
                    >
                        <div class="text-2xl font-bold text-white">{{ biensStats.disponible }}</div>
                        <div class="text-sm text-green-100">Disponible</div>
                    </div>
                    <div
                        @click="filterByStatus('loue')"
                        :class="{'ring-2 ring-white': selectedStatus === 'loué'}"
                        class="bg-blue-500/20 backdrop-blur-sm rounded-xl p-4 text-center cursor-pointer hover:bg-blue-500/30 transition-all duration-300"
                    >
                        <div class="text-2xl font-bold text-white">{{ biensStats.loue }}</div>
                        <div class="text-sm text-blue-100">Loué</div>
                    </div>
                    <div
                        @click="filterByStatus('vendu')"
                        :class="{'ring-2 ring-white': selectedStatus === 'Vendu'}"
                        class="bg-red-500/20 backdrop-blur-sm rounded-xl p-4 text-center cursor-pointer hover:bg-red-500/30 transition-all duration-300"
                    >
                        <div class="text-2xl font-bold text-white">{{ biensStats.vendu }}</div>
                        <div class="text-sm text-red-100">Vendu</div>
                    </div>
                    <div
                        @click="filterByStatus('reserve')"
                        :class="{'ring-2 ring-white': selectedStatus === 'reserve'}"
                        class="bg-gray-500/20 backdrop-blur-sm rounded-xl p-4 text-center cursor-pointer hover:bg-gray-500/30 transition-all duration-300"
                    >
                        <div class="text-2xl font-bold text-white">{{ biensStats.reserve }}</div>
                        <div class="text-sm text-gray-100">Réservé</div>
                    </div>
                </div>

                <!-- Barre d'actions -->
                <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <!-- Barre de recherche -->
                    <div class="relative flex-1 max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="searchTerm"
                            type="text"
                            :placeholder="isAdmin ? 'Rechercher un bien ou propriétaire...' : 'Rechercher un bien...'"
                            class="w-full pl-10 pr-4 py-3 bg-white/90 backdrop-blur-sm border border-white/30 rounded-xl focus:ring-4 focus:ring-white/20 focus:border-white transition-all duration-300"
                        />
                    </div>

                    <!-- Bouton d'ajout -->
                    <button
                        v-if="!isAdmin"
                        @click="createBien"
                        class="inline-flex items-center px-6 py-3 bg-white text-blue-600 rounded-xl font-semibold hover:bg-blue-50 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajouter un bien
                    </button>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Message si aucun bien -->
            <div v-if="props.biens.length === 0" class="text-center py-16">
                <div class="bg-white/70 backdrop-blur-lg rounded-3xl shadow-xl border border-white/20 p-12 mx-auto max-w-md">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-100 to-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0H3m2 0v-6a2 2 0 012-2h4a2 2 0 012 2v6" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Aucun bien enregistré</h3>
                    <p class="text-gray-600 mb-6">
                        {{ isAdmin ? 'Aucun bien n\'a encore été soumis.' : 'Commencez par ajouter votre premier bien immobilier' }}
                    </p>
                    <button
                        v-if="!isAdmin"
                        @click="createBien"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-green-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-green-700 transition-all duration-300 transform hover:scale-105 shadow-lg"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajouter mon premier bien
                    </button>
                </div>
            </div>

            <!-- Liste des biens -->
            <div v-else>
                <!-- Compteur de résultats -->
                <div class="mb-6 flex justify-between items-center">
                    <p class="text-gray-600 font-medium">
                        {{ filteredBiens.length }} bien{{ filteredBiens.length > 1 ? 's' : '' }}
                        {{ selectedStatus ? `(${getStatusLabel(selectedStatus)})` : 'trouvé' }}{{ filteredBiens.length > 1 ? 's' : '' }}
                        <span v-if="searchTerm"> pour "{{ searchTerm }}"</span>
                    </p>
                    <button
                        v-if="selectedStatus || searchTerm"
                        @click="selectedStatus = ''; searchTerm = ''"
                        class="text-sm text-blue-600 hover:text-blue-700 underline"
                    >
                        Réinitialiser les filtres
                    </button>
                </div>

                <!-- Grille des biens -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div
                        v-for="bien in filteredBiens"
                        :key="bien.id"
                        class="group bg-white/70 backdrop-blur-lg rounded-2xl shadow-lg border border-white/20 overflow-hidden hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2"
                    >
                        <!-- Image -->
                        <div class="relative h-56 overflow-hidden">
                            <img
                                v-if="bien.image"
                                :src="`/storage/${bien.image}`"
                                :alt="bien.title"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            />
                            <div v-else class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>

                            <!-- Badges de statut -->
                            <div class="absolute top-4 left-4 flex flex-col space-y-2">
                                <span :class="`px-3 py-1 rounded-full text-xs font-semibold border ${getStatusColor(bien.status)}`">
                                    {{ getStatusLabel(bien.status) }}
                                </span>
                                <span
                                    v-if="bien.mandat"
                                    :class="`px-3 py-1 rounded-full text-xs font-semibold border ${getMandatTypeColor(bien.mandat.type_mandat)}`"
                                >
                                    {{ getMandatTypeLabel(bien.mandat.type_mandat) }}
                                </span>
                                <span
                                    v-if="bien.mandat && isAdmin"
                                    :class="`px-2 py-1 rounded-full text-xs font-semibold border ${getMandatStatusColor(bien.mandat.statut)}`"
                                >
                                    {{ getMandatStatusLabel(bien.mandat.statut) }}
                                </span>
                            </div>

                            <!-- Actions rapides -->
                            <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col space-y-2">
                                <!-- Actions pour admin -->
                                <template v-if="isAdmin">
                                    <!-- Bouton de validation -->
                                    <button
                                        v-if="bien.status === 'en_validation'"
                                        @click="openValidationModal(bien)"
                                        class="p-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200 shadow-lg"
                                        title="Valider le bien"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <!-- Bouton de rejet -->
                                    <button
                                        v-if="bien.status === 'en_validation'"
                                        @click="openRejectionModal(bien)"
                                        class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200 shadow-lg"
                                        title="Rejeter le bien"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </template>

                                <!-- Actions communes -->
                                <button
                                    @click="showBien(bien)"
                                    class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 shadow-lg"
                                    title="Voir les détails"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <button
                                    @click="editBien(bien)"
                                    class="p-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors duration-200 shadow-lg"
                                    title="Modifier"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteBien(bien)"
                                    class="p-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors duration-200 shadow-lg"
                                    title="Supprimer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>

                                <!-- BOUTONS PDF -->
                                <template v-if="canAccessPdf(bien)">
                                    <button
                                        @click="downloadMandatPdf(bien)"
                                        class="p-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors duration-200 shadow-lg"
                                        title="Télécharger le mandat PDF"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </button>

                                    <button
                                        @click="previewMandatPdf(bien)"
                                        class="p-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors duration-200 shadow-lg"
                                        title="Prévisualiser le mandat PDF"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    <button
                                        v-if="isAdmin"
                                        @click="regenerateMandatPdf(bien)"
                                        class="p-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors duration-200 shadow-lg"
                                        title="Régénérer le mandat PDF"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                </template>

                                <!-- BOUTON DE SIGNATURE -->
                                <button
                                    v-if="canSignMandat(bien)"
                                    @click="showSignaturePage(bien)"
                                    class="p-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors duration-200 shadow-lg"
                                    title="Signer le mandat"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="p-6">
                            <!-- Titre et prix -->
                            <div class="mb-4">
                                <h3 class="text-xl font-bold text-gray-800 mb-2 line-clamp-2">
                                    {{ bien.title }}
                                </h3>
                                <p class="text-2xl font-bold text-green-600">
                                    {{ formatPrice(bien.price) }} FCFA
                                </p>
                            </div>

                            <!-- Propriétaire pour admin -->
                            <div v-if="isAdmin && bien.proprietaire" class="flex items-center text-gray-600 mb-3">
                                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span class="text-sm font-medium">{{ bien.proprietaire.name }}</span>
                            </div>

                            <!-- Localisation -->
                            <div class="flex items-center text-gray-600 mb-4">
                                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-sm truncate">{{ bien.address }}, {{ bien.city }}</span>
                            </div>

                            <!-- Caractéristiques -->
                            <div class="flex items-center space-x-4 text-sm text-gray-600 mb-4">
                                <div v-if="bien.superficy" class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                    </svg>
                                    {{ bien.superficy }}m²
                                </div>
                                <div v-if="bien.rooms" class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10z" />
                                    </svg>
                                    {{ bien.rooms }} ch.
                                </div>
                                <div v-if="bien.bathrooms" class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                    </svg>
                                    {{ bien.bathrooms }} sdb
                                </div>
                            </div>

                            <!-- Catégorie -->
                            <div v-if="bien.category" class="mb-4">
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium">
                                    {{ bien.category.name }}
                                </span>
                            </div>

                            <!-- SECTION PDF DANS LE CONTENU -->
                            <div v-if="canAccessPdf(bien)" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="text-xs text-blue-800 mb-2 font-medium flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Mandat PDF disponible
                                </div>
                                <div class="flex space-x-2">
                                    <button
                                        @click="downloadMandatPdf(bien)"
                                        class="flex-1 px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs transition-colors duration-200 flex items-center justify-center"
                                    >
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                                        </svg>
                                        Télécharger
                                    </button>
                                    <button
                                        @click="previewMandatPdf(bien)"
                                        class="flex-1 px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs transition-colors duration-200 flex items-center justify-center"
                                    >
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Voir
                                    </button>
                                    <button
                                        v-if="isAdmin"
                                        @click="regenerateMandatPdf(bien)"
                                        class="px-3 py-1 bg-orange-600 hover:bg-orange-700 text-white rounded text-xs transition-colors duration-200 flex items-center justify-center"
                                        title="Régénérer"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- SECTION SIGNATURE ÉLECTRONIQUE - UNE SEULE FOIS -->
                            <div v-if="bien.mandat && bien.mandat.statut === 'actif'" class="mb-4 p-3 bg-indigo-50 border border-indigo-200 rounded-lg">
                                <div class="text-xs text-indigo-800 mb-2 font-medium flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Signature électronique
                                </div>

                                <!-- Statut de signature -->
                                <div v-if="bien.mandat.signature_status" class="mb-2">
                                    <span :class="`text-xs px-2 py-1 rounded-full border ${getSignatureStatusBadge(bien.mandat).color}`">
                                        {{ getSignatureStatusBadge(bien.mandat).text }}
                                    </span>
                                </div>

                                <div class="flex space-x-2">
                                    <button
                                        v-if="canSignMandat(bien)"
                                        @click="showSignaturePage(bien)"
                                        class="flex-1 px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs transition-colors duration-200 flex items-center justify-center"
                                    >
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Signer
                                    </button>

                                    <button
                                        v-if="bien.mandat.signature_status && bien.mandat.signature_status !== 'non_signe'"
                                        @click="previewMandatPdf(bien)"
                                        class="flex-1 px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs transition-colors duration-200 flex items-center justify-center"
                                    >
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Voir signé
                                    </button>
                                </div>
                            </div>

                            <!-- Informations mandat pour admin -->
                            <div v-if="isAdmin && bien.mandat" class="mb-4 p-3 bg-gray-50 rounded-lg">
                                <div class="text-xs text-gray-600 mb-2">
                                    <strong>Mandat:</strong>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span>Commission: {{ bien.mandat.commission_pourcentage }}%</span>
                                    <span>{{ formatPrice(bien.mandat.commission_fixe) }} FCFA</span>
                                </div>
                                <div class="flex justify-between items-center text-xs mt-1">
                                    <span>Fin: {{ formatDate(bien.mandat.date_fin) }}</span>
                                    <span :class="`px-2 py-1 rounded text-xs ${getMandatStatusColor(bien.mandat.statut)}`">
                                        {{ getMandatStatusLabel(bien.mandat.statut) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Motif de rejet si rejeté -->
                            <div v-if="bien.status === 'rejete' && bien.motif_rejet" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <div class="text-xs font-medium text-red-800 mb-1">Motif du rejet:</div>
                                <div class="text-xs text-red-600">{{ bien.motif_rejet }}</div>
                                <div v-if="bien.rejected_at" class="text-xs text-red-500 mt-1">
                                    Rejeté le {{ formatDate(bien.rejected_at) }}
                                </div>
                            </div>

                            <!-- Actions principales pour admin -->
                            <div v-if="isAdmin && bien.status === 'en_validation'" class="flex space-x-2 mb-4">
                                <button
                                    @click="openValidationModal(bien)"
                                    class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 text-sm flex items-center justify-center"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Valider
                                </button>
                                <button
                                    @click="openRejectionModal(bien)"
                                    class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors duration-200 text-sm flex items-center justify-center"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Rejeter
                                </button>
                            </div>

                            <!-- Actions principales standard -->
                            <div class="flex space-x-2">
                                <button
                                    @click="showBien(bien)"
                                    class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 text-sm"
                                >
                                    Voir
                                </button>
                                <button
                                    @click="editBien(bien)"
                                    class="flex-1 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium transition-colors duration-200 text-sm"
                                >
                                    Modifier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message si aucun résultat de recherche -->
                <div v-if="searchTerm && filteredBiens.length === 0" class="text-center py-12">
                    <div class="bg-white/50 backdrop-blur-sm rounded-2xl shadow-lg border border-white/20 p-8 mx-auto max-w-md">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Aucun résultat</h3>
                        <p class="text-gray-600 mb-4">Aucun bien ne correspond à votre recherche "{{ searchTerm }}"</p>
                        <button
                            @click="searchTerm = ''"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200"
                        >
                            Effacer la recherche
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de validation -->
        <div
            v-if="showValidationModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click="closeValidationModal"
        >
            <div
                class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300"
                @click.stop
            >
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Validation du bien
                        </h3>
                        <button
                            @click="closeValidationModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Contenu -->
                    <div v-if="selectedBien" class="mb-6">
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <h4 class="font-semibold text-gray-800 mb-2">{{ selectedBien.title }}</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                <div>
                                    <span class="font-medium">Propriétaire:</span><br>
                                    {{ selectedBien.proprietaire?.name }}
                                </div>
                                <div>
                                    <span class="font-medium">Prix:</span><br>
                                    {{ formatPrice(selectedBien.price) }} FCFA
                                </div>
                                <div>
                                    <span class="font-medium">Localisation:</span><br>
                                    {{ selectedBien.city }}
                                </div>
                                <div>
                                    <span class="font-medium">Type de mandat:</span><br>
                                    {{ getMandatTypeLabel(selectedBien.mandat?.type_mandat) }}
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="font-medium text-green-800">Action de validation</span>
                            </div>
                            <p class="text-sm text-green-700">
                                En validant ce bien, vous confirmez que:
                            </p>
                            <ul class="text-sm text-green-700 mt-2 ml-4 space-y-1">
                                <li>• Tous les documents fournis sont conformes</li>
                                <li>• Les informations du bien sont exactes</li>
                                <li>• Le bien sera visible publiquement</li>
                                <li>• Le mandat sera activé automatiquement</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <button
                            @click="closeValidationModal"
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-medium hover:bg-gray-300 transition-colors duration-200"
                        >
                            Annuler
                        </button>
                        <button
                            @click="validateBien"
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors duration-200 flex items-center justify-center"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Valider le bien
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de rejet -->
        <div
            v-if="showRejectionModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click="closeRejectionModal"
        >
            <div
                class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300"
                @click.stop
            >
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            Rejet du bien
                        </h3>
                        <button
                            @click="closeRejectionModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Contenu -->
                    <div v-if="selectedBien" class="mb-6">
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <h4 class="font-semibold text-gray-800 mb-2">{{ selectedBien.title }}</h4>
                            <div class="text-sm text-gray-600">
                                Propriétaire: {{ selectedBien.proprietaire?.name }}
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Motif du rejet <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="rejectionReason"
                                rows="4"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors"
                                placeholder="Expliquez pourquoi ce bien est rejeté (documents manquants, informations incorrectes, etc.)"
                                required
                            ></textarea>
                        </div>

                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <span class="font-medium text-red-800">Action de rejet</span>
                            </div>
                            <p class="text-sm text-red-700">
                                Le propriétaire sera notifié du rejet et pourra corriger le bien pour une nouvelle soumission.
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <button
                            @click="closeRejectionModal"
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-medium hover:bg-gray-300 transition-colors duration-200"
                        >
                            Annuler
                        </button>
                        <button
                            @click="rejectBien"
                            :disabled="!rejectionReason.trim()"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors duration-200 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Rejeter le bien
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Animation au chargement */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.grid > div {
    animation: fadeInUp 0.6s ease-out;
}

.grid > div:nth-child(1) { animation-delay: 0.1s; }
.grid > div:nth-child(2) { animation-delay: 0.2s; }
.grid > div:nth-child(3) { animation-delay: 0.3s; }
.grid > div:nth-child(4) { animation-delay: 0.4s; }
.grid > div:nth-child(5) { animation-delay: 0.5s; }
.grid > div:nth-child(6) { animation-delay: 0.6s; }

/* Animation des statistiques */
@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

.grid > div:hover {
    animation: pulse 0.3s ease-in-out;
}

/* Styles pour les modals */
.modal-enter-active, .modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
    opacity: 0;
}

.modal-content-enter-active, .modal-content-leave-active {
    transition: all 0.3s ease;
}

.modal-content-enter-from, .modal-content-leave-to {
    opacity: 0;
    transform: scale(0.9) translateY(-50px);
}
</style>
