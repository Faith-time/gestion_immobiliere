<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-green-50">
        <!-- Header -->
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-green-600 to-indigo-800 py-16">
            <div class="absolute inset-0 bg-black/20"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-green-300/20 rounded-full blur-3xl"></div>

            <div class="relative max-w-7xl mx-auto px-4">
                <div class="text-center">
                    <h1 class="text-4xl font-bold text-white mb-4">
                        Réservation #{{ reservation?.id || 'N/A' }}
                    </h1>
                    <div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold"
                         :class="getStatusBadgeClass(reservation?.statut)">
                        <i :class="getStatusIcon(reservation?.statut)" class="mr-2"></i>
                        {{ getStatusLabel(reservation?.statut) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="max-w-6xl mx-auto px-4 py-8">
            <!-- Messages d'alerte -->
            <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                {{ $page.props.flash.error }}
            </div>

            <!-- Vérification que la réservation existe -->
            <div v-if="!reservation" class="text-center py-16">
                <div class="bg-white/70 backdrop-blur-lg rounded-3xl shadow-xl border border-white/20 p-12 mx-auto max-w-md">
                    <svg class="w-24 h-24 text-gray-400 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Réservation introuvable</h3>
                    <p class="text-gray-600 mb-6">Cette réservation n'existe pas ou vous n'avez pas l'autorisation de la consulter.</p>
                    <Link href="/reservations" class="btn btn-primary">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Retour aux réservations
                    </Link>
                </div>
            </div>

            <!-- Contenu principal si la réservation existe -->
            <div v-else class="space-y-8">
                <!-- Informations générales -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Informations de la réservation -->
                    <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-lg border border-white/20 p-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Détails de la réservation
                        </h2>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-3 border-b border-gray-200">
                                <span class="text-gray-600">Date de réservation</span>
                                <span class="font-semibold">{{ formatDate(reservation.date_reservation) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-200">
                                <span class="text-gray-600">Montant de la caution</span>
                                <span class="font-bold text-green-600 text-lg">{{ formatPrice(reservation.montant) }} FCFA</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-200">
                                <span class="text-gray-600">Statut</span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"
                                      :class="getStatusBadgeClass(reservation.statut)">
                                    {{ getStatusLabel(reservation.statut) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-200">
                                <span class="text-gray-600">Créée le</span>
                                <span class="font-semibold">{{ formatDate(reservation.created_at) }}</span>
                            </div>
                            <div v-if="reservation.paiement_id" class="flex justify-between items-center py-3 border-b border-gray-200">
                                <span class="text-gray-600">ID Paiement</span>
                                <span class="font-semibold">#{{ reservation.paiement_id }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Informations du client -->
                    <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-lg border border-white/20 p-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Informations client
                        </h2>

                        <div v-if="reservation.client" class="space-y-4">
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-green-500 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold text-xl">
                                        {{ getClientInitials(reservation.client.name) }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">{{ reservation.client.name }}</h3>
                                    <p class="text-gray-600">{{ reservation.client.email }}</p>
                                    <p v-if="reservation.client.telephone" class="text-gray-600">
                                        {{ reservation.client.telephone }}
                                    </p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-gray-200">
                                <div class="text-sm text-gray-600">
                                    <strong>ID Client:</strong> #{{ reservation.client_id }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 italic">
                            Informations client non disponibles
                        </div>
                    </div>
                </div>

                <!-- Informations du bien -->
                <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-lg border border-white/20 p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Propriété réservée
                    </h2>

                    <div v-if="reservation.bien" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Image du bien -->
                        <div class="md:col-span-1">
                            <div class="aspect-w-4 aspect-h-3 rounded-lg overflow-hidden">
                                <img
                                    :src="getBienImage(reservation.bien.image)"
                                    :alt="reservation.bien.title"
                                    class="w-full h-48 object-cover rounded-lg shadow-md"
                                    @error="handleImageError"
                                />
                            </div>
                        </div>

                        <!-- Détails du bien -->
                        <div class="md:col-span-2 space-y-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">{{ reservation.bien.title }}</h3>
                                <p class="text-gray-600 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ reservation.bien.address }}, {{ reservation.bien.city }}
                                </p>
                            </div>

                            <!-- Caractéristiques -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div v-if="reservation.bien.rooms" class="text-center p-3 bg-gray-50 rounded-lg">
                                    <svg class="w-6 h-6 mx-auto mb-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                                    </svg>
                                    <div class="text-sm font-semibold">{{ reservation.bien.rooms }} chambres</div>
                                </div>
                                <div v-if="reservation.bien.bathrooms" class="text-center p-3 bg-gray-50 rounded-lg">
                                    <svg class="w-6 h-6 mx-auto mb-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                    </svg>
                                    <div class="text-sm font-semibold">{{ reservation.bien.bathrooms }} SDB</div>
                                </div>
                                <div v-if="reservation.bien.superficy" class="text-center p-3 bg-gray-50 rounded-lg">
                                    <svg class="w-6 h-6 mx-auto mb-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                    </svg>
                                    <div class="text-sm font-semibold">{{ reservation.bien.superficy }} m²</div>
                                </div>
                                <div class="text-center p-3 bg-green-50 rounded-lg">
                                    <svg class="w-6 h-6 mx-auto mb-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                    <div class="text-sm font-semibold text-green-600">{{ formatPrice(reservation.bien.price) }} FCFA</div>
                                </div>
                            </div>

                            <!-- Bouton voir le bien -->
                            <div class="pt-4">
                                <Link :href="`/biens/${reservation.bien_id}`"
                                      class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Voir la propriété
                                </Link>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-gray-500 italic text-center py-8">
                        Informations du bien non disponibles
                    </div>
                </div>

                <!-- Documents du client -->
                <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-lg border border-white/20 p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Documents fournis
                        <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 text-sm rounded-full">
                            {{ documentsCount }}
                        </span>
                    </h2>

                    <div v-if="documentsCount > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="document in documents"
                             :key="document.id"
                             class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <i :class="getFileIcon(document.fichier_path)" class="text-2xl"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">
                                            {{ getDocumentTypeLabel(document.type_document) }}
                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            {{ getFileName(document.fichier_path) }}
                                        </p>
                                        <div class="flex items-center mt-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                  :class="getDocumentStatusColor(document.statut)">
                                                {{ getDocumentStatusLabel(document.statut) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button @click="previewDocument(document)"
                                            class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors"
                                            :disabled="!canPreview(document.fichier_path)"
                                            title="Prévisualiser">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <a :href="getDownloadUrl(document.fichier_path)"
                                       :download="getFileName(document.fichier_path)"
                                       class="p-2 text-green-600 hover:bg-green-100 rounded-lg transition-colors"
                                       title="Télécharger">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div v-if="document.commentaire_admin" class="mt-3 p-2 bg-blue-50 rounded text-sm text-blue-700">
                                <strong>Commentaire admin:</strong> {{ document.commentaire_admin }}
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <p class="text-gray-500 text-lg">Aucun document fourni</p>
                        <p class="text-sm text-gray-400">Les documents requis peuvent être ajoutés lors de la réservation</p>
                    </div>
                </div>

                <!-- Motif de rejet si annulée -->
                <div v-if="reservation.statut === 'annulee' && reservation.motif_rejet"
                     class="bg-red-50 border border-red-200 rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-red-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        Motif du rejet
                    </h3>
                    <p class="text-red-700 mb-2">{{ reservation.motif_rejet }}</p>
                    <p v-if="reservation.rejected_at" class="text-sm text-red-600">
                        Rejeté le {{ formatDate(reservation.rejected_at) }}
                    </p>
                </div>

                <!-- Actions selon le statut -->
                <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-lg border border-white/20 p-6">
                    <div class="text-center">
                        <!-- En attente -->
                        <div v-if="reservation.statut === 'en_attente'">
                            <div class="mb-6">
                                <svg class="w-16 h-16 mx-auto text-yellow-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Réservation en attente</h3>
                                <p class="text-gray-600">Votre réservation est en cours de traitement par notre équipe.</p>
                            </div>
                        </div>

                        <!-- Confirmée -->
                        <!-- À AJOUTER dans la section "Confirmée" de Show.vue -->
                        <div v-else-if="reservation.statut === 'confirmée'">
                            <div class="mb-6">
                                <svg class="w-16 h-16 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Réservation confirmée</h3>

                                <!-- ✅ VÉRIFICATION SI DÉJÀ PAYÉ -->
                                <div v-if="isPaid" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <div>
                                            <p class="font-semibold">✅ Paiement effectué avec succès!</p>
                                            <p class="text-sm">
                                                Montant payé: {{ formatPrice(reservation.montant) }} FCFA
                                                <span v-if="paiement?.date_transaction">
                            le {{ formatDate(paiement.date_transaction) }}
                        </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div v-else>
                                    <p class="text-gray-600 mb-4">Votre réservation a été validée. Vous pouvez maintenant procéder au paiement.</p>

                                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                                        <div class="flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                            </svg>
                                            <div>
                                                <div class="font-bold text-lg text-green-800">{{ formatPrice(reservation.montant) }} FCFA</div>
                                                <div class="text-sm text-green-600">Caution de réservation</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-center">
                                        <button @click="initiatePayment"
                                                :disabled="processing"
                                                class="inline-flex items-center justify-center px-8 py-4 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold text-lg transition-colors disabled:opacity-50">
                                            <svg v-if="!processing" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                            </svg>
                                            <span v-if="processing">Traitement...</span>
                                            <span v-else>Payer maintenant</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Annulée -->
                        <div v-else-if="reservation.statut === 'annulee'">
                            <div class="mb-6">
                                <svg class="w-16 h-16 mx-auto text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Réservation annulée</h3>
                                <p class="text-gray-600">Cette réservation a été annulée.</p>
                            </div>
                        </div>

                        <!-- Payée -->
                        <div v-else-if="reservation.statut === 'payee'">
                            <div class="mb-6">
                                <svg class="w-16 h-16 mx-auto text-blue-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                </svg>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Réservation payée</h3>
                                <p class="text-gray-600 mb-4">Votre réservation est confirmée et le paiement a été effectué avec succès.</p>
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <p class="text-blue-800 font-semibold">Notre équipe va vous contacter prochainement pour finaliser les détails.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <Link href="/reservations"
                          class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Mes réservations
                    </Link>
                    <Link href="/biens"
                          class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Retour aux propriétés
                    </Link>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation d'annulation -->
        <div v-if="showCancelModal"
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
             @click="showCancelModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300"
                 @click.stop>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            Confirmer l'annulation
                        </h3>
                        <button @click="showCancelModal = false"
                                class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mb-6">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <p class="text-red-800">
                                Êtes-vous sûr de vouloir annuler cette réservation ? Cette action est irréversible.
                            </p>
                        </div>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• La réservation sera définitivement annulée</li>
                            <li>• Le bien sera remis en disponible</li>
                            <li>• Vous devrez refaire une nouvelle réservation si nécessaire</li>
                        </ul>
                    </div>

                    <div class="flex space-x-3">
                        <button @click="showCancelModal = false"
                                class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                            Garder la réservation
                        </button>
                        <button @click="cancelReservation"
                                :disabled="processing"
                                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors disabled:opacity-50">
                            {{ processing ? 'Annulation...' : 'Confirmer l\'annulation' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de prévisualisation des documents -->
        <div v-if="showDocumentModal"
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
             @click="closeDocumentModal">
            <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden"
                 @click.stop>
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">
                        {{ currentDocument.name }}
                    </h3>
                    <button @click="closeDocumentModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-0" style="height: calc(90vh - 120px);">
                    <div v-if="currentDocument.loading" class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-gray-600">Chargement du document...</p>
                        </div>
                    </div>

                    <div v-else-if="currentDocument.error" class="flex items-center justify-center h-full">
                        <div class="text-center text-red-600">
                            <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            <p>Impossible de charger le document</p>
                        </div>
                    </div>

                    <div v-else class="h-full">
                        <iframe v-if="currentDocument.type === 'pdf'"
                                :src="currentDocument.url"
                                class="w-full h-full border-none">
                        </iframe>

                        <div v-else-if="currentDocument.type === 'image'"
                             class="h-full flex items-center justify-center p-4">
                            <img :src="currentDocument.url"
                                 :alt="currentDocument.name"
                                 class="max-w-full max-h-full object-contain">
                        </div>

                        <div v-else class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-600 mb-4">Prévisualisation non disponible</p>
                                <a :href="currentDocument.url"
                                   :download="currentDocument.name"
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Télécharger
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 border-t bg-gray-50">
                    <a :href="currentDocument.url"
                       :download="currentDocument.name"
                       class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Télécharger
                    </a>
                    <button @click="closeDocumentModal"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition-colors">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import {route} from "ziggy-js";

// Props avec valeurs par défaut sécurisées
const props = defineProps({
    reservation: {
        type: Object,
        default: () => null
    },
    paiement: {
        type: Object,
        default: () => null
    },
    userRoles: {
        type: Array,
        default: () => []
    }
})

// État réactif
const processing = ref(false)
const showCancelModal = ref(false)
const showDocumentModal = ref(false)
const currentDocument = ref({
    url: '',
    name: '',
    type: '',
    loading: false,
    error: false
})

// Computed properties
const documents = computed(() => {
    if (!props.reservation) return []
    return props.reservation.client_documents ||
        props.reservation.clientDocuments ||
        []
})

const documentsCount = computed(() => documents.value.length)

const isPaid = computed(() => {
    return props.reservation?.paiement_id &&
        (props.paiement?.statut === 'termine' || props.reservation?.statut === 'payee')
})

// Méthodes utilitaires
const formatPrice = (price) => {
    if (!price) return '0'
    return new Intl.NumberFormat('fr-FR').format(price)
}

const formatDate = (dateString) => {
    if (!dateString) return 'Non spécifié'
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getClientInitials = (name) => {
    if (!name) return '?'
    return name.split(' ').map(word => word.charAt(0).toUpperCase()).join('').slice(0, 2)
}

const getBienImage = (imagePath) => {
    if (!imagePath) return '/images/placeholder-house.jpg'
    return imagePath.startsWith('/') ? imagePath : `/storage/${imagePath}`
}

const handleImageError = (event) => {
    event.target.src = '/images/placeholder-house.jpg'
}

// Méthodes de statut
const getStatusLabel = (status) => {
    const labels = {
        'en_attente': 'En attente de validation',
        'confirmée': 'Confirmée',
        'payee': 'Payée',
        'payée': 'Payée',
        'annulee': 'Annulée',
        'annulée': 'Annulée'
    }
    return labels[status] || status || 'Inconnu'
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'en_attente': 'bg-yellow-100 text-yellow-800',
        'confirmée': 'bg-green-100 text-green-800',
        'payee': 'bg-blue-100 text-blue-800',
        'payée': 'bg-blue-100 text-blue-800',
        'annulee': 'bg-red-100 text-red-800',
        'annulée': 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusIcon = (status) => {
    const icons = {
        'en_attente': 'fas fa-clock',
        'confirmée': 'fas fa-check-circle',
        'payee': 'fas fa-credit-card',
        'payée': 'fas fa-credit-card',
        'annulee': 'fas fa-times-circle',
        'annulée': 'fas fa-times-circle'
    }
    return icons[status] || 'fas fa-question-circle'
}

// Méthodes pour les documents
const getDocumentTypeLabel = (type) => {
    const labels = {
        'justificatif_domicile': 'Justificatif de domicile',
        'piece_identite': 'Pièce d\'identité',
        'justificatif_revenus': 'Justificatif de revenus',
        'autre': 'Autre document'
    }
    return labels[type] || type || 'Document'
}

const getDocumentStatusColor = (status) => {
    const colors = {
        'en_attente': 'bg-yellow-100 text-yellow-800',
        'valide': 'bg-green-100 text-green-800',
        'refuse': 'bg-red-100 text-red-800'
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getDocumentStatusLabel = (status) => {
    const labels = {
        'en_attente': 'En attente',
        'valide': 'Validé',
        'refuse': 'Refusé'
    }
    return labels[status] || status || 'Inconnu'
}

const getFileName = (filePath) => {
    if (!filePath) return 'Document'
    return filePath.split('/').pop() || filePath
}

const getFileIcon = (filePath) => {
    if (!filePath) return 'fas fa-file text-gray-500'
    const extension = filePath.split('.').pop()?.toLowerCase()
    const icons = {
        'pdf': 'fas fa-file-pdf text-red-500',
        'jpg': 'fas fa-file-image text-blue-500',
        'jpeg': 'fas fa-file-image text-blue-500',
        'png': 'fas fa-file-image text-blue-500',
        'gif': 'fas fa-file-image text-blue-500',
        'doc': 'fas fa-file-word text-blue-600',
        'docx': 'fas fa-file-word text-blue-600',
        'xls': 'fas fa-file-excel text-green-600',
        'xlsx': 'fas fa-file-excel text-green-600'
    }
    return icons[extension] || 'fas fa-file text-gray-500'
}

const getDownloadUrl = (filePath) => {
    if (!filePath) return '#'
    return filePath.startsWith('/') ? filePath : `/storage/${filePath}`
}

const canPreview = (filePath) => {
    if (!filePath) return false
    const extension = filePath.split('.').pop()?.toLowerCase()
    return ['pdf', 'jpg', 'jpeg', 'png', 'gif'].includes(extension)
}

// Actions
const confirmCancellation = () => {
    showCancelModal.value = true
}

const cancelReservation = () => {
    if (!props.reservation?.id) return

    processing.value = true
    router.post(`/reservations/${props.reservation.id}/annuler`, {}, {
        onSuccess: () => {
            showCancelModal.value = false
            processing.value = false
        },
        onError: (errors) => {
            processing.value = false
            console.error('Erreur lors de l\'annulation:', errors)
        }
    })
}

const initiatePayment = () => {
    if (!props.reservation?.id) return

    processing.value = true

    // Passer un objet avec la clé 'reservation'
    router.visit(route('reservations.initier-paiement', { reservation: props.reservation.id }), {
        onFinish: () => {
            processing.value = false
        }
    })
}

const previewDocument = (document) => {
    if (!canPreview(document.fichier_path)) {
        window.open(getDownloadUrl(document.fichier_path), '_blank')
        return
    }

    currentDocument.value = {
        url: getDownloadUrl(document.fichier_path),
        name: getFileName(document.fichier_path),
        type: getDocumentType(document.fichier_path),
        loading: true,
        error: false
    }

    showDocumentModal.value = true

    // Simuler le chargement
    setTimeout(() => {
        currentDocument.value.loading = false
    }, 500)
}

const getDocumentType = (filePath) => {
    if (!filePath) return 'other'
    const extension = filePath.split('.').pop()?.toLowerCase()
    if (extension === 'pdf') return 'pdf'
    if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) return 'image'
    return 'other'
}

const closeDocumentModal = () => {
    showDocumentModal.value = false
    currentDocument.value = {
        url: '',
        name: '',
        type: '',
        loading: false,
        error: false
    }
}
</script>

<style>
.status-badge {
    display: inline-block;
}

.card {
    border: none;
    border-radius: 12px;
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
}

.payment-info {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

.document-item {
    transition: all 0.3s ease;
}

.document-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.btn-group .btn {
    border-radius: 4px;
}

.btn-group .btn:not(:last-child) {
    margin-right: 5px;
}

.badge {
    font-size: 0.75em;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
</style>
