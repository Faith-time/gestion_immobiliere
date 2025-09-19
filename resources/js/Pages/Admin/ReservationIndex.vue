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
                        Gestion des Réservations
                    </h1>
                    <p class="text-xl text-blue-100 max-w-2xl mx-auto">
                        Validez ou rejetez les réservations en fonction des documents fournis
                    </p>
                </div>

                <!-- Statistiques des réservations -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                    <div
                        @click="filterByStatus('')"
                        :class="{'ring-2 ring-white': selectedStatus === ''}"
                        class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center cursor-pointer hover:bg-white/30 transition-all duration-300"
                    >
                        <div class="text-2xl font-bold text-white">{{ reservationsStats.total }}</div>
                        <div class="text-sm text-blue-100">Total</div>
                    </div>
                    <div
                        @click="filterByStatus('en_attente')"
                        :class="{'ring-2 ring-white': selectedStatus === 'en_attente'}"
                        class="bg-orange-500/20 backdrop-blur-sm rounded-xl p-4 text-center cursor-pointer hover:bg-orange-500/30 transition-all duration-300"
                    >
                        <div class="text-2xl font-bold text-white">{{ reservationsStats.en_attente }}</div>
                        <div class="text-sm text-orange-100">En attente</div>
                    </div>
                    <div
                        @click="filterByStatus('confirmee')"
                        :class="{'ring-2 ring-white': selectedStatus === 'confirmee'}"
                        class="bg-green-500/20 backdrop-blur-sm rounded-xl p-4 text-center cursor-pointer hover:bg-green-500/30 transition-all duration-300"
                    >
                        <div class="text-2xl font-bold text-white">{{ reservationsStats.confirmee }}</div>
                        <div class="text-sm text-green-100">Confirmée</div>
                    </div>
                    <div
                        @click="filterByStatus('payee')"
                        :class="{'ring-2 ring-white': selectedStatus === 'payee'}"
                        class="bg-blue-500/20 backdrop-blur-sm rounded-xl p-4 text-center cursor-pointer hover:bg-blue-500/30 transition-all duration-300"
                    >
                        <div class="text-2xl font-bold text-white">{{ reservationsStats.payee }}</div>
                        <div class="text-sm text-blue-100">Payée</div>
                    </div>
                    <div
                        @click="filterByStatus('annulee')"
                        :class="{'ring-2 ring-white': selectedStatus === 'annulee'}"
                        class="bg-red-500/20 backdrop-blur-sm rounded-xl p-4 text-center cursor-pointer hover:bg-red-500/30 transition-all duration-300"
                    >
                        <div class="text-2xl font-bold text-white">{{ reservationsStats.annulee }}</div>
                        <div class="text-sm text-red-100">Annulée</div>
                    </div>
                </div>

                <!-- Barre de recherche -->
                <div class="flex justify-center">
                    <div class="relative max-w-md w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="searchTerm"
                            type="text"
                            placeholder="Rechercher une réservation, client ou bien..."
                            class="w-full pl-10 pr-4 py-3 bg-white/90 backdrop-blur-sm border border-white/30 rounded-xl focus:ring-4 focus:ring-white/20 focus:border-white transition-all duration-300"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Message si aucune réservation -->
            <div v-if="props.reservations.length === 0" class="text-center py-16">
                <div class="bg-white/70 backdrop-blur-lg rounded-3xl shadow-xl border border-white/20 p-12 mx-auto max-w-md">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-100 to-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Aucune réservation</h3>
                    <p class="text-gray-600">Aucune réservation n'a encore été effectuée.</p>
                </div>
            </div>

            <!-- Liste des réservations -->
            <div v-else>
                <!-- Compteur de résultats -->
                <div class="mb-6 flex justify-between items-center">
                    <p class="text-gray-600 font-medium">
                        {{ filteredReservations.length }} réservation{{ filteredReservations.length > 1 ? 's' : '' }}
                        {{ selectedStatus ? `(${getStatusLabel(selectedStatus)})` : 'trouvée' }}{{ filteredReservations.length > 1 ? 's' : '' }}
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

                <!-- Grille des réservations -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div
                        v-for="reservation in filteredReservations"
                        :key="reservation.id"
                        class="group bg-white/70 backdrop-blur-lg rounded-2xl shadow-lg border border-white/20 overflow-hidden hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2"
                    >
                        <!-- Header de la carte -->
                        <div class="relative p-6 bg-gradient-to-r from-blue-50 to-green-50">
                            <!-- Badges de statut -->
                            <div class="flex justify-between items-start mb-4">
                                <span :class="`px-3 py-1 rounded-full text-xs font-semibold ${getStatusColor(reservation.statut)}`">
                                    {{ getStatusLabel(reservation.statut) }}
                                </span>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-green-600">
                                        {{ formatPrice(reservation.montant) }} FCFA
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ formatDate(reservation.date_reservation) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Informations du client -->
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ reservation.client.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="ml-3">
                                    <h3 class="font-semibold text-gray-800">{{ reservation.client.name }}</h3>
                                    <p class="text-sm text-gray-600">{{ reservation.client.email }}</p>
                                    <p class="text-sm text-gray-600" v-if="reservation.client.telephone">
                                        {{ reservation.client.telephone }}
                                    </p>
                                </div>
                            </div>

                            <!-- Actions rapides -->
                            <div v-if="reservation.statut === 'en_attente'" class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex space-x-2">
                                <button
                                    @click="openValidationModal(reservation)"
                                    class="p-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200 shadow-lg"
                                    title="Valider la réservation"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <button
                                    @click="openRejectionModal(reservation)"
                                    class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200 shadow-lg"
                                    title="Rejeter la réservation"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Informations du bien -->
                        <div class="p-6">
                            <div class="mb-4">
                                <h4 class="font-bold text-lg text-gray-800 mb-2 line-clamp-2">
                                    {{ reservation.bien.title }}
                                </h4>
                                <div class="flex items-center text-gray-600 mb-2">
                                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-sm">{{ reservation.bien.city }}</span>
                                </div>
                            </div>

                            <!-- Documents fournis -->
                            <div class="mb-4">
                                <h5 class="font-medium text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Documents fournis ({{ reservation.client_documents.length }})
                                </h5>
                                <div v-if="reservation.client_documents.length > 0" class="space-y-2">
                                    <div
                                        v-for="document in reservation.client_documents"
                                        :key="document.id"
                                        class="flex items-center justify-between p-2 bg-gray-50 rounded-lg"
                                    >
                                        <div class="flex items-center">
                                            <span class="text-xs font-medium text-gray-600">
                                                {{ getDocumentTypeLabel(document.type_document) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span :class="`px-2 py-1 rounded text-xs font-medium ${getDocumentStatusColor(document.statut)}`">
                                                {{ getDocumentStatusLabel(document.statut) }}
                                            </span>
                                            <a
                                                :href="`/storage/${document.fichier_path}`"
                                                target="_blank"
                                                class="text-blue-600 hover:text-blue-700"
                                                title="Voir le document"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-gray-500 italic">
                                    Aucun document fourni
                                </div>
                            </div>

                            <!-- Motif de rejet si annulée -->
                            <div v-if="reservation.statut === 'annulee' && reservation.motif_rejet" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <div class="text-xs font-medium text-red-800 mb-1">Motif du rejet:</div>
                                <div class="text-xs text-red-600">{{ reservation.motif_rejet }}</div>
                                <div v-if="reservation.rejected_at" class="text-xs text-red-500 mt-1">
                                    Rejeté le {{ formatDate(reservation.rejected_at) }}
                                </div>
                            </div>

                            <!-- Actions principales -->
                            <div class="flex space-x-2">
                                <button
                                    @click="showReservation(reservation)"
                                    class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 text-sm"
                                >
                                    Voir détails
                                </button>
                                <div v-if="reservation.statut === 'en_attente'" class="flex space-x-2 flex-1">
                                    <button
                                        @click="openValidationModal(reservation)"
                                        class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 text-sm"
                                    >
                                        Valider
                                    </button>
                                    <button
                                        @click="openRejectionModal(reservation)"
                                        class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors duration-200 text-sm"
                                    >
                                        Rejeter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message si aucun résultat de recherche -->
                <div v-if="searchTerm && filteredReservations.length === 0" class="text-center py-12">
                    <div class="bg-white/50 backdrop-blur-sm rounded-2xl shadow-lg border border-white/20 p-8 mx-auto max-w-md">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Aucun résultat</h3>
                        <p class="text-gray-600 mb-4">Aucune réservation ne correspond à votre recherche "{{ searchTerm }}"</p>
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
                            Validation de la réservation
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
                    <div v-if="selectedReservation" class="mb-6">
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold mr-3">
                                    {{ selectedReservation.client.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">{{ selectedReservation.client.name }}</h4>
                                    <p class="text-sm text-gray-600">{{ selectedReservation.client.email }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                <div>
                                    <span class="font-medium">Bien:</span><br>
                                    {{ selectedReservation.bien.title }}
                                </div>
                                <div>
                                    <span class="font-medium">Montant:</span><br>
                                    {{ formatPrice(selectedReservation.montant) }} FCFA
                                </div>
                                <div>
                                    <span class="font-medium">Date réservation:</span><br>
                                    {{ formatDate(selectedReservation.date_reservation) }}
                                </div>
                                <div>
                                    <span class="font-medium">Documents:</span><br>
                                    {{ selectedReservation.client_documents.length }} fourni(s)
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
                                En validant cette réservation, vous confirmez que:
                            </p>
                            <ul class="text-sm text-green-700 mt-2 ml-4 space-y-1">
                                <li>• Les documents fournis sont conformes et complets</li>
                                <li>• La réservation peut être confirmée</li>
                                <li>• Le client pourra procéder au paiement</li>
                                <li>• Tous les documents seront automatiquement validés</li>
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
                            @click="validateReservation"
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors duration-200 flex items-center justify-center"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Valider la réservation
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
                            Rejet de la réservation
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
                    <div v-if="selectedReservation" class="mb-6">
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold mr-3">
                                    {{ selectedReservation.client.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">{{ selectedReservation.client.name }}</h4>
                                    <p class="text-sm text-gray-600">{{ selectedReservation.client.email }}</p>
                                </div>
                            </div>
                            <div class="text-sm text-gray-600">
                                <span class="font-medium">Bien:</span> {{ selectedReservation.bien.title }}
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
                                placeholder="Expliquez pourquoi cette réservation est rejetée (documents non conformes, informations manquantes, etc.)"
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
                                Le client sera notifié du rejet et le bien sera remis en disponible pour d'autres clients.
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
                            @click="rejectReservation"
                            :disabled="!rejectionReason.trim()"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors duration-200 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Rejeter la réservation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { route } from 'ziggy-js'

const props = defineProps({
    reservations: Array,
    userRoles: Array,
})

const searchTerm = ref('')
const selectedStatus = ref('')
const showValidationModal = ref(false)
const selectedReservation = ref(null)
const showRejectionModal = ref(false)
const rejectionReason = ref('')

const filteredReservations = computed(() => {
    let filtered = props.reservations

    // Filtrage par terme de recherche
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase()
        filtered = filtered.filter(reservation =>
            reservation.client.name.toLowerCase().includes(term) ||
            reservation.client.email.toLowerCase().includes(term) ||
            reservation.bien.title.toLowerCase().includes(term) ||
            reservation.bien.city.toLowerCase().includes(term)
        )
    }

    // Filtrage par statut
    if (selectedStatus.value) {
        filtered = filtered.filter(reservation => reservation.statut === selectedStatus.value)
    }

    return filtered
})

// Compter les réservations par statut
const reservationsStats = computed(() => {
    return {
        total: props.reservations.length,
        en_attente: props.reservations.filter(r => r.statut === 'en_attente').length,
        confirmee: props.reservations.filter(r => r.statut === 'confirmee').length,
        payee: props.reservations.filter(r => r.statut === 'payee').length,
        annulee: props.reservations.filter(r => r.statut === 'annulee').length,
    }
})

const getStatusColor = (status) => {
    const colors = {
        'en_attente': 'bg-orange-100 text-orange-800 border-orange-200',
        'confirmee': 'bg-green-100 text-green-800 border-green-200',
        'payee': 'bg-blue-100 text-blue-800 border-blue-200',
        'annulee': 'bg-red-100 text-red-800 border-red-200',
    }
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200'
}

const getStatusLabel = (status) => {
    const labels = {
        'en_attente': 'En attente',
        'confirmee': 'Confirmée',
        'payee': 'Payée',
        'annulee': 'Annulée',
    }
    return labels[status] || status
}

const getDocumentStatusColor = (status) => {
    const colors = {
        'en_attente': 'bg-orange-100 text-orange-800',
        'valide': 'bg-green-100 text-green-800',
        'refuse': 'bg-red-100 text-red-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getDocumentStatusLabel = (status) => {
    const labels = {
        'en_attente': 'En attente',
        'valide': 'Validé',
        'refuse': 'Refusé',
    }
    return labels[status] || status
}

const getDocumentTypeLabel = (type) => {
    const labels = {
        'justificatif_domicile': 'Justificatif de domicile',
        'piece_identite': 'Pièce d\'identité',
        'justificatif_revenus': 'Justificatif de revenus',
        'autre': 'Autre document'
    }
    return labels[type] || type
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR')
}

const showReservation = (reservation) => {
    router.visit(route('reservations.show', reservation.id))
}

// Fonctions de validation pour admin
const openValidationModal = (reservation) => {
    selectedReservation.value = reservation
    showValidationModal.value = true
}

const closeValidationModal = () => {
    showValidationModal.value = false
    selectedReservation.value = null
}

const validateReservation = () => {
    if (selectedReservation.value) {
        router.post(route('admin.reservations.valider', selectedReservation.value.id), {}, {
            onSuccess: () => {
                closeValidationModal()
                router.reload()
            },
            onError: (errors) => {
                console.error('Erreur lors de la validation:', errors)
                alert('Erreur lors de la validation de la réservation')
            }
        })
    }
}

const openRejectionModal = (reservation) => {
    selectedReservation.value = reservation
    rejectionReason.value = ''
    showRejectionModal.value = true
}

const closeRejectionModal = () => {
    showRejectionModal.value = false
    selectedReservation.value = null
    rejectionReason.value = ''
}

const rejectReservation = () => {
    if (selectedReservation.value && rejectionReason.value.trim()) {
        router.post(route('admin.reservations.rejeter', selectedReservation.value.id), {
            motif_rejet: rejectionReason.value
        }, {
            onSuccess: () => {
                closeRejectionModal()
                router.reload()
            },
            onError: (errors) => {
                console.error('Erreur lors du rejet:', errors)
                alert('Erreur lors du rejet de la réservation')
            }
        })
    }
}

const filterByStatus = (status) => {
    selectedStatus.value = selectedStatus.value === status ? '' : status
}
</script>

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
</style>
