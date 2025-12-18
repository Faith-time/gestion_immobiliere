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
                        @click="filterByStatus('confirmée')"
                        :class="{'ring-2 ring-white': selectedStatus === 'confirmée'}"
                        class="bg-green-500/20 backdrop-blur-sm rounded-xl p-4 text-center cursor-pointer hover:bg-green-500/30 transition-all duration-300"
                    >
                        <div class="text-2xl font-bold text-white">{{ reservationsStats.confirmee }}</div>
                        <div class="text-sm text-green-100">Confirmée</div>
                    </div>
                    <div
                        @click="filterByStatus('annulée')"
                        :class="{'ring-2 ring-white': selectedStatus === 'annulée'}"
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

                            <!-- Documents du dossier client -->
                            <div class="mb-4">
                                <h5 class="font-medium text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Dossier client
                                </h5>
                                <div v-if="reservation.dossier_client" class="space-y-2">
                                    <!-- Numéro CNI -->
                                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                        <span class="text-xs font-medium text-gray-600">Numéro CNI</span>
                                        <span class="text-xs font-semibold text-gray-800">{{ reservation.dossier_client.numero_cni }}</span>
                                    </div>

                                    <!-- Carte d'identité -->
                                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                        <span class="text-xs font-medium text-gray-600">Photo CNI</span>
                                        <a
                                            v-if="reservation.dossier_client.carte_identite_path"
                                            :href="`/storage/${reservation.dossier_client.carte_identite_path}`"
                                            target="_blank"
                                            class="text-blue-600 hover:text-blue-700"
                                            title="Voir la CNI"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <span v-else class="text-xs text-red-600">Non fournie</span>
                                    </div>

                                    <!-- Dernière quittance -->
                                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                        <span class="text-xs font-medium text-gray-600">Dernière quittance</span>
                                        <a
                                            v-if="reservation.dossier_client.derniere_quittance_path"
                                            :href="`/storage/${reservation.dossier_client.derniere_quittance_path}`"
                                            target="_blank"
                                            class="text-blue-600 hover:text-blue-700"
                                            title="Voir la quittance"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <span v-else class="text-xs text-gray-500">Non fournie</span>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-gray-500 italic">
                                    Aucun dossier fourni
                                </div>
                            </div>

                            <!-- Motif de rejet si annulée -->
                            <div v-if="reservation.statut === 'annulée' && reservation.motif_rejet" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <div class="text-xs font-medium text-red-800 mb-1">Motif du rejet:</div>
                                <div class="text-xs text-red-600">{{ reservation.motif_rejet }}</div>
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
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click="closeValidationModal"
        >
            <div
                class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full transform transition-all duration-300 max-h-[90vh] overflow-y-auto"
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
                            </div>
                        </div>

                        <!-- Aperçu des documents -->
                        <div v-if="selectedReservation.dossier_client" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <h5 class="font-semibold text-blue-800 mb-3">Documents du dossier</h5>

                            <!-- CNI -->
                            <div class="mb-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">
                                    Numéro CNI: <span class="font-bold">{{ selectedReservation.dossier_client.numero_cni }}</span>
                                </p>
                                <div v-if="selectedReservation.dossier_client.carte_identite_path">
                                    <p class="text-xs text-gray-600 mb-2">Photo de la carte d'identité:</p>
                                    <img
                                        :src="`/storage/${selectedReservation.dossier_client.carte_identite_path}`"
                                        alt="Carte d'identité"
                                        class="max-w-full h-auto rounded border-2 border-gray-300 cursor-pointer hover:border-blue-500 transition"
                                        @click="openImageModal(`/storage/${selectedReservation.dossier_client.carte_identite_path}`)"
                                    />
                                </div>
                            </div>

                            <!-- Quittance -->
                            <div v-if="selectedReservation.dossier_client.derniere_quittance_path">
                                <p class="text-xs text-gray-600 mb-2">Dernière quittance de loyer (reçu de paiement):</p>
                                <img
                                    :src="`/storage/${selectedReservation.dossier_client.derniere_quittance_path}`"
                                    alt="Dernière quittance"
                                    class="max-w-full h-auto rounded border-2 border-gray-300 cursor-pointer hover:border-blue-500 transition"
                                    @click="openImageModal(`/storage/${selectedReservation.dossier_client.derniere_quittance_path}`)"
                                />
                            </div>
                            <div v-else>
                                <p class="text-sm text-gray-500 italic">Aucune quittance de loyer fournie</p>
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
                                En validant cette réservation, vous confirmez que le numéro CNI, la photo de la carte d'identité
                                et la dernière quittance de loyer (si fournie) sont conformes.
                            </p>
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
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click="closeRejectionModal"
        >
            <div
                class="bg-white rounded-2xl shadow-2xl max-w-lg w-full transform transition-all duration-300"
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

                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="font-medium text-red-800">Raison du rejet</span>
                            </div>
                            <p class="text-sm text-red-700 mb-3">
                                Veuillez indiquer la raison du rejet de cette réservation (optionnel).
                            </p>
                            <textarea
                                v-model="rejectionReason"
                                rows="4"
                                class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                placeholder="Ex: Documents non conformes, CNI expirée, quittance manquante..."
                            ></textarea>
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
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors duration-200 flex items-center justify-center"
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

        <!-- Modal d'aperçu d'image -->
        <div
            v-if="showImageModal"
            class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50 p-4"
            @click="closeImageModal"
        >
            <div class="relative max-w-6xl w-full">
                <button
                    @click="closeImageModal"
                    class="absolute top-4 right-4 text-white hover:text-gray-300 transition-colors bg-black/50 rounded-full p-2"
                >
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <img
                    :src="selectedImage"
                    alt="Aperçu du document"
                    class="max-w-full max-h-[90vh] mx-auto rounded-lg shadow-2xl"
                    @click.stop
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    reservations: Array,
    userRoles: Array
});

const searchTerm = ref('');
const selectedStatus = ref('');
const showValidationModal = ref(false);
const showRejectionModal = ref(false);
const showImageModal = ref(false);
const selectedReservation = ref(null);
const selectedImage = ref('');
const rejectionReason = ref('');

// Statistiques des réservations
const reservationsStats = computed(() => {
    return {
        total: props.reservations.length,
        en_attente: props.reservations.filter(r => r.statut === 'en_attente').length,
        confirmee: props.reservations.filter(r => r.statut === 'confirmée').length,
        payee: props.reservations.filter(r => r.statut === 'payée').length,
        annulee: props.reservations.filter(r => r.statut === 'annulée').length,
    };
});

// Réservations filtrées
const filteredReservations = computed(() => {
    let filtered = props.reservations;

    // Filtre par statut
    if (selectedStatus.value) {
        filtered = filtered.filter(r => r.statut === selectedStatus.value);
    }

    // Filtre par recherche
    if (searchTerm.value) {
        const search = searchTerm.value.toLowerCase();
        filtered = filtered.filter(r => {
            return (
                r.client.name.toLowerCase().includes(search) ||
                r.client.email.toLowerCase().includes(search) ||
                r.bien.title.toLowerCase().includes(search) ||
                r.bien.city.toLowerCase().includes(search) ||
                (r.dossier_client?.numero_cni && r.dossier_client.numero_cni.toLowerCase().includes(search))
            );
        });
    }

    return filtered;
});

// Fonctions utilitaires
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price);
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const getStatusColor = (statut) => {
    const colors = {
        'en_attente': 'bg-orange-100 text-orange-800',
        'confirmée': 'bg-green-100 text-green-800',
        'payée': 'bg-blue-100 text-blue-800',
        'annulée': 'bg-red-100 text-red-800'
    };
    return colors[statut] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (statut) => {
    const labels = {
        'en_attente': 'En attente',
        'confirmée': 'Confirmée',
        'payée': 'Payée',
        'annulée': 'Annulée'
    };
    return labels[statut] || statut;
};

// Gestion des filtres
const filterByStatus = (status) => {
    selectedStatus.value = status;
};

// Gestion des modals
const openValidationModal = (reservation) => {
    selectedReservation.value = reservation;
    showValidationModal.value = true;
};

const closeValidationModal = () => {
    showValidationModal.value = false;
    selectedReservation.value = null;
};

const openRejectionModal = (reservation) => {
    selectedReservation.value = reservation;
    rejectionReason.value = '';
    showRejectionModal.value = true;
};

const closeRejectionModal = () => {
    showRejectionModal.value = false;
    selectedReservation.value = null;
    rejectionReason.value = '';
};

const openImageModal = (imageUrl) => {
    selectedImage.value = imageUrl;
    showImageModal.value = true;
};

const closeImageModal = () => {
    showImageModal.value = false;
    selectedImage.value = '';
};

// Actions
const validateReservation = () => {
    if (!selectedReservation.value) return;

    router.post(
        `/admin/reservations/${selectedReservation.value.id}/valider`,
        {},
        {
            onSuccess: () => {
                closeValidationModal();
            },
            onError: (errors) => {
                console.error('Erreur validation:', errors);
            }
        }
    );
};

const rejectReservation = () => {
    if (!selectedReservation.value) return;

    router.post(
        `/admin/reservations/${selectedReservation.value.id}/rejeter`,
        {
            motif_rejet: rejectionReason.value || 'Documents non conformes'
        },
        {
            onSuccess: () => {
                closeRejectionModal();
            },
            onError: (errors) => {
                console.error('Erreur rejet:', errors);
            }
        }
    );
};

const showReservation = (reservation) => {
    router.visit(`/reservations/${reservation.id}`);
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
