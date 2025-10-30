<template>
    <Layout>
        <Head :title="`Réservation #${reservation?.id || 'N/A'}`" />

        <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-green-50">
            <!-- Header dynamique -->
            <div class="relative overflow-hidden py-16"
                 :class="isVente ? 'bg-gradient-to-r from-blue-600 via-indigo-700 to-purple-800' : 'bg-gradient-to-r from-green-600 via-teal-600 to-blue-700'">
                <div class="absolute inset-0 bg-black/20"></div>
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>

                <div class="relative max-w-7xl mx-auto px-4">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center bg-white/20 backdrop-blur-lg rounded-full p-4 mb-4">
                            <i :class="isVente ? 'fas fa-shopping-cart' : 'fas fa-shield-alt'" class="text-white fa-2x"></i>
                        </div>
                        <h1 class="text-4xl font-bold text-white mb-2">
                            {{ getTitreReservation() }} #{{ reservation?.id || 'N/A' }}
                        </h1>
                        <p class="text-white/90 text-lg mb-4">
                            {{ getSousTitreReservation() }}
                        </p>

                        <!-- ✅ Badge IMMEUBLE si applicable -->
                        <div v-if="isImmeuble" class="mb-3">
                            <span class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-full text-sm font-semibold">
                                <i class="fas fa-building mr-2"></i>
                                RÉSERVATION D'APPARTEMENT
                            </span>
                        </div>

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
                <!-- Messages -->
                <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>{{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $page.props.flash.error }}
                </div>

                <!-- Explication dynamique -->
                <div class="border-2 rounded-2xl p-6 mb-8"
                     :class="isVente ? 'bg-blue-50 border-blue-200' : 'bg-green-50 border-green-200'">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mr-4">
                            <div class="text-white rounded-full p-3"
                                 :class="isVente ? 'bg-blue-600' : 'bg-green-600'">
                                <i :class="isVente ? 'fas fa-info-circle' : 'fas fa-key'" class="fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold mb-3"
                                :class="isVente ? 'text-blue-900' : 'text-green-900'">
                                {{ getExplicationTitre() }}
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div class="bg-white rounded-lg p-3">
                                    <div class="flex items-center mb-2">
                                        <i :class="isVente ? 'fas fa-shopping-cart text-blue-600' : 'fas fa-home text-green-600'" class="mr-2"></i>
                                        <strong>Type d'opération</strong>
                                    </div>
                                    <p class="text-gray-700">
                                        {{ isVente ? 'Achat immobilier' : 'Location immobilière' }}
                                        <span v-if="isImmeuble" class="block text-purple-600 mt-1">
                                            <i class="fas fa-building mr-1"></i>
                                            Appartement dans un immeuble
                                        </span>
                                    </p>
                                </div>

                                <div class="bg-white rounded-lg p-3">
                                    <div class="flex items-center mb-2">
                                        <i :class="isVente ? 'fas fa-percentage text-purple-600' : 'fas fa-calendar-alt text-teal-600'" class="mr-2"></i>
                                        <strong>Montant</strong>
                                    </div>
                                    <p class="text-gray-700">
                                        {{ isVente ? '10% du prix de vente' : '1 mois de loyer' }}
                                    </p>
                                </div>

                                <div class="bg-white rounded-lg p-3">
                                    <div class="flex items-center mb-2">
                                        <i :class="isVente ? 'fas fa-coins text-yellow-600' : 'fas fa-undo text-blue-600'" class="mr-2"></i>
                                        <strong>{{ isVente ? 'Utilisation' : 'Remboursable ?' }}</strong>
                                    </div>
                                    <p class="text-gray-700">
                                        {{ isVente ? 'Déduit du prix total lors de l\'achat final' : 'Oui, en fin de bail (si pas de dégâts)' }}
                                    </p>
                                </div>

                                <div class="bg-white rounded-lg p-3">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-shield-alt text-indigo-600 mr-2"></i>
                                        <strong>Sécurité</strong>
                                    </div>
                                    <p class="text-gray-700">
                                        Géré par l'agence selon la réglementation
                                    </p>
                                </div>
                            </div>

                            <!-- Info supplémentaire pour vente -->
                            <div v-if="isVente && reservation?.bien?.price" class="mt-4 p-3 bg-white rounded-lg border-l-4 border-blue-500">
                                <div class="flex items-start">
                                    <i class="fas fa-calculator text-blue-600 mr-2 mt-1"></i>
                                    <div class="flex-1">
                                        <strong class="text-blue-900">Détail du paiement :</strong>
                                        <ul class="mt-2 space-y-1 text-sm text-gray-700">
                                            <li>• <strong>Acompte (10%) :</strong> {{ formatPrice(reservation.montant) }} FCFA</li>
                                            <li>• <strong>Restant (90%) :</strong> {{ formatPrice(getMontantRestant()) }} FCFA</li>
                                            <li>• <strong>Prix total :</strong> {{ formatPrice(reservation.bien.price) }} FCFA</li>
                                        </ul>
                                        <p class="text-xs text-blue-600 mt-2">
                                            💡 Les 90% restants seront payés lors de la signature définitive
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vérification réservation existe -->
                <div v-if="!reservation" class="text-center py-16">
                    <div class="bg-white/70 backdrop-blur-lg rounded-3xl shadow-xl border border-white/20 p-12 mx-auto max-w-md">
                        <i class="fas fa-exclamation-triangle text-gray-400 text-6xl mb-4"></i>
                        <h3 class="text-xl font-bold text-gray-800 mb-3">Réservation introuvable</h3>
                        <p class="text-gray-600 mb-6">Cette réservation n'existe pas ou vous n'y avez pas accès.</p>
                        <Link :href="route('reservations.index')" class="btn btn-primary">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Retour aux réservations
                        </Link>
                    </div>
                </div>

                <!-- Contenu si réservation existe -->
                <div v-else class="space-y-8">
                    <!-- Informations générales -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Détails de la réservation -->
                        <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-lg border border-white/20 p-6">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                                <i :class="isVente ? 'fas fa-file-invoice-dollar text-blue-600' : 'fas fa-file-contract text-green-600'" class="mr-3"></i>
                                {{ isVente ? 'Détails de l\'acompte' : 'Détails du dépôt de garantie' }}
                            </h2>

                            <div class="space-y-4">
                                <!-- ✅ Info IMMEUBLE + APPARTEMENT -->
                                <div v-if="isImmeuble && reservation.appartement"
                                     class="p-4 bg-purple-50 border-2 border-purple-200 rounded-lg">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-building text-purple-600 mr-2"></i>
                                        <strong class="text-purple-900">Appartement réservé</strong>
                                    </div>
                                    <div class="space-y-1 text-sm text-gray-700">
                                        <p><strong>Numéro :</strong> {{ reservation.appartement.numero }}</p>
                                        <p><strong>Étage :</strong> {{ reservation.appartement.etage }}</p>
                                        <p><strong>Pièces :</strong> {{ reservation.appartement.nombre_pieces || reservation.appartement.pieces }}</p>
                                        <p v-if="reservation.appartement.superficie">
                                            <strong>Superficie :</strong> {{ reservation.appartement.superficie }} m²
                                        </p>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                                    <span class="text-gray-600">Date de dépôt</span>
                                    <span class="font-semibold">{{ formatDate(reservation.date_reservation) }}</span>
                                </div>

                                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                                    <span class="text-gray-600 flex items-center">
                                        <i :class="isVente ? 'fas fa-percentage text-blue-600' : 'fas fa-money-check-alt text-green-600'" class="mr-2"></i>
                                        {{ isVente ? 'Acompte (10%)' : 'Caution (1 mois)' }}
                                    </span>
                                    <span class="font-bold text-lg"
                                          :class="isVente ? 'text-blue-600' : 'text-green-600'">
                                        {{ formatPrice(reservation.montant) }} FCFA
                                    </span>
                                </div>

                                <div v-if="isVente" class="flex justify-between items-center py-3 border-b border-gray-200">
                                    <span class="text-gray-600">Montant restant (90%)</span>
                                    <span class="font-semibold text-purple-600">{{ formatPrice(getMontantRestant()) }} FCFA</span>
                                </div>

                                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                                    <span class="text-gray-600">{{ isVente ? 'Prix total du bien' : 'Loyer mensuel' }}</span>
                                    <span class="font-bold text-gray-800">{{ formatPrice(reservation.bien?.price) }} FCFA</span>
                                </div>

                                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                                    <span class="text-gray-600">Statut</span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"
                                          :class="getStatusBadgeClass(reservation.statut)">
                                        {{ getStatusLabel(reservation.statut) }}
                                    </span>
                                </div>

                                <div v-if="reservation.paiement_id" class="flex justify-between items-center py-3">
                                    <span class="text-gray-600">Paiement</span>
                                    <span class="font-semibold flex items-center text-green-600">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        Effectué
                                    </span>
                                </div>
                            </div>

                            <!-- Processus -->
                            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                                <h6 class="font-bold text-gray-800 mb-3 flex items-center">
                                    <i class="fas fa-tasks mr-2"
                                       :class="isVente ? 'text-blue-600' : 'text-green-600'"></i>
                                    {{ isVente ? 'Étapes de l\'achat' : 'Étapes de la location' }}
                                </h6>
                                <ol class="space-y-2 text-sm">
                                    <li class="flex items-start">
                                        <span class="flex-shrink-0 w-6 h-6 text-white rounded-full flex items-center justify-center mr-2 text-xs"
                                              :class="reservation.statut !== 'en_attente' ? 'bg-green-500' : 'bg-blue-500'">
                                            <i class="fas" :class="reservation.statut !== 'en_attente' ? 'fa-check' : 'fa-clock'"></i>
                                        </span>
                                        <span class="text-gray-700">Vérification des documents</span>
                                    </li>
                                    <li class="flex items-start">
                                        <span class="flex-shrink-0 w-6 h-6 text-white rounded-full flex items-center justify-center mr-2 text-xs"
                                              :class="reservation.statut === 'confirmée' || isPaid ? 'bg-green-500' : 'bg-gray-300'">
                                            <i class="fas" :class="reservation.statut === 'confirmée' || isPaid ? 'fa-check' : 'fa-ellipsis-h'"></i>
                                        </span>
                                        <span class="text-gray-700">Validation et paiement du {{ isVente ? 'acompte' : 'dépôt' }}</span>
                                    </li>
                                    <li class="flex items-start">
                                        <span class="flex-shrink-0 w-6 h-6 text-white rounded-full flex items-center justify-center mr-2 text-xs"
                                              :class="isPaid ? 'bg-green-500' : 'bg-gray-300'">
                                            <i class="fas" :class="isPaid ? 'fa-check' : 'fa-ellipsis-h'"></i>
                                        </span>
                                        <span class="text-gray-700">
                                            {{ isVente ? 'Signature de l\'acte de vente' : 'Signature du bail' }}
                                        </span>
                                    </li>
                                </ol>
                            </div>
                        </div>

                        <!-- Informations client -->
                        <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-lg border border-white/20 p-6">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                                <i class="fas fa-user-circle text-indigo-600 mr-3"></i>
                                Vos informations
                            </h2>

                            <div v-if="reservation.client" class="space-y-4">
                                <div class="flex items-center space-x-4 p-4 rounded-lg"
                                     :class="isVente ? 'bg-gradient-to-r from-blue-50 to-indigo-50' : 'bg-gradient-to-r from-green-50 to-teal-50'">
                                    <div class="w-16 h-16 rounded-full flex items-center justify-center"
                                         :class="isVente ? 'bg-gradient-to-r from-blue-500 to-indigo-500' : 'bg-gradient-to-r from-green-500 to-teal-500'">
                                        <span class="text-white font-bold text-xl">
                                            {{ getClientInitials(reservation.client.name) }}
                                        </span>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">{{ reservation.client.name }}</h3>
                                        <p class="text-gray-600 text-sm flex items-center">
                                            <i class="fas fa-envelope mr-2"></i>{{ reservation.client.email }}
                                        </p>
                                        <p v-if="reservation.client.telephone" class="text-gray-600 text-sm flex items-center">
                                            <i class="fas fa-phone mr-2"></i>{{ reservation.client.telephone }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations du bien -->
                    <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-lg border border-white/20 p-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-building text-indigo-600 mr-3"></i>
                            {{ isImmeuble ? 'Immeuble' : 'Propriété' }} {{ isVente ? 'à acheter' : 'à louer' }}
                        </h2>

                        <div v-if="reservation.bien" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-1">
                                <img
                                    :src="getBienImage(reservation.bien.images)"
                                    :alt="reservation.bien.title"
                                    class="w-full h-48 object-cover rounded-lg shadow-md"
                                    @error="handleImageError"
                                />
                            </div>

                            <div class="md:col-span-2 space-y-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800 mb-2">{{ reservation.bien.title }}</h3>
                                    <p class="text-gray-600 flex items-center mb-3">
                                        <i class="fas fa-map-marker-alt mr-2"></i>
                                        {{ reservation.bien.address }}, {{ reservation.bien.city }}
                                    </p>

                                    <!-- Badge type d'opération -->
                                    <div class="mb-4">
                                        <span v-if="isImmeuble"
                                              class="inline-flex items-center px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-semibold mr-2">
                                            <i class="fas fa-building mr-1"></i>
                                            Immeuble d'appartements
                                        </span>
                                        <span v-if="isVente"
                                              class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                            <i class="fas fa-shopping-cart mr-2"></i>
                                            Achat - Acompte de 10%
                                        </span>
                                        <span v-else
                                              class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                                            <i class="fas fa-key mr-2"></i>
                                            Location - Dépôt de garantie
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div v-if="reservation.bien.rooms" class="text-center p-3 bg-gray-50 rounded-lg">
                                        <i class="fas fa-bed text-gray-600 text-xl mb-2"></i>
                                        <div class="text-sm font-semibold">{{ reservation.bien.rooms }} chambres</div>
                                    </div>
                                    <div v-if="reservation.bien.bathrooms" class="text-center p-3 bg-gray-50 rounded-lg">
                                        <i class="fas fa-bath text-gray-600 text-xl mb-2"></i>
                                        <div class="text-sm font-semibold">{{ reservation.bien.bathrooms }} SDB</div>
                                    </div>
                                    <div v-if="reservation.bien.superficy" class="text-center p-3 bg-gray-50 rounded-lg">
                                        <i class="fas fa-ruler-combined text-gray-600 text-xl mb-2"></i>
                                        <div class="text-sm font-semibold">{{ reservation.bien.superficy }} m²</div>
                                    </div>
                                    <div class="text-center p-3 rounded-lg"
                                         :class="isVente ? 'bg-blue-50' : 'bg-green-50'">
                                        <i class="fas fa-tag text-xl mb-2"
                                           :class="isVente ? 'text-blue-600' : 'text-green-600'"></i>
                                        <div class="text-sm font-semibold"
                                             :class="isVente ? 'text-blue-600' : 'text-green-600'">
                                            {{ formatPrice(reservation.bien.price) }} FCFA
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-4">
                                    <Link :href="route('biens.show', reservation.bien_id)"
                                          class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                                        <i class="fas fa-eye mr-2"></i>Voir la propriété
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documents fournis -->
                    <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-lg border border-white/20 p-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-folder-open text-yellow-600 mr-3"></i>
                            Documents justificatifs
                            <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 text-sm rounded-full">
                                {{ documentsCount }}
                            </span>
                        </h2>

                        <div class="mb-4 p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                            <p class="text-sm text-blue-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                Ces documents permettent de vérifier votre identité et votre solvabilité.
                            </p>
                        </div>

                        <div v-if="documentsCount > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="document in documents"
                                 :key="document.id"
                                 class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors border border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3 flex-1">
                                        <i :class="getFileIcon(document.fichier_path)" class="text-2xl"></i>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-800 truncate">
                                                {{ getDocumentTypeLabel(document.type_document) }}
                                            </h4>
                                            <p class="text-sm text-gray-600 truncate">
                                                {{ getFileName(document.fichier_path) }}
                                            </p>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mt-1"
                                                  :class="getDocumentStatusColor(document.statut)">
                                                {{ getDocumentStatusLabel(document.statut) }}
                                            </span>
                                        </div>
                                    </div>
                                    <a :href="getDownloadUrl(document.fichier_path)"
                                       :download="getFileName(document.fichier_path)"
                                       class="p-2 text-green-600 hover:bg-green-100 rounded-lg transition-colors"
                                       title="Télécharger">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <i class="fas fa-folder-open text-gray-400 text-4xl mb-3"></i>
                            <p class="text-gray-500">Aucun document fourni</p>
                        </div>
                    </div>

                    <!-- Actions selon statut -->
                    <div class="bg-white/70 backdrop-blur-lg rounded-2xl shadow-lg border border-white/20 p-6">
                        <div class="text-center">
                            <!-- En attente -->
                            <div v-if="reservation.statut === 'en_attente'">
                                <div class="mb-6">
                                    <div class="inline-flex items-center justify-center bg-yellow-100 rounded-full p-4 mb-4">
                                        <i class="fas fa-clock text-yellow-600 fa-3x"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800 mb-2">
                                        {{ isVente ? 'Acompte' : 'Dépôt' }} en cours de vérification
                                    </h3>
                                    <p class="text-gray-600 mb-4">
                                        Notre équipe examine vos documents pour valider votre {{ isVente ? 'acompte de réservation' : 'dépôt de garantie' }}.
                                        <br>Vous serez notifié sous 24-48h.
                                    </p>
                                </div>
                            </div>

                            <!-- Confirmée -->
                            <div v-else-if="reservation.statut === 'confirmée'">
                                <div v-if="isPaid" class="mb-6">
                                    <div class="inline-flex items-center justify-center bg-green-100 rounded-full p-4 mb-4">
                                        <i class="fas fa-check-circle text-green-600 fa-3x"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800 mb-2">
                                        ✅ {{ isVente ? 'Acompte' : 'Dépôt de garantie' }} payé !
                                    </h3>
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 inline-block">
                                        <p class="text-green-800 font-semibold">
                                            Montant versé : {{ formatPrice(reservation.montant) }} FCFA
                                        </p>
                                        <p v-if="paiement?.date_transaction" class="text-sm text-green-700">
                                            Payé le {{ formatDate(paiement.date_transaction) }}
                                        </p>
                                    </div>
                                    <p class="text-gray-600">
                                        {{ isVente
                                        ? 'Votre acompte est sécurisé. L\'agence va vous contacter pour finaliser l\'achat.'
                                        : 'Votre caution est sécurisée. L\'agence va vous contacter pour signer le bail.'
                                        }}
                                        <span v-if="isImmeuble" class="block mt-2 text-purple-600 font-semibold">
                                            <i class="fas fa-building mr-1"></i>
                                            Appartement {{ reservation.appartement?.numero }} réservé avec succès
                                        </span>
                                    </p>
                                </div>

                                <div v-else class="mb-6">
                                    <div class="inline-flex items-center justify-center bg-blue-100 rounded-full p-4 mb-4">
                                        <i class="fas fa-credit-card text-blue-600 fa-3x"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800 mb-2">
                                        {{ isVente ? 'Acompte' : 'Dépôt' }} validé - Paiement requis
                                    </h3>
                                    <p class="text-gray-600 mb-4">
                                        Vos documents sont validés. Procédez maintenant au paiement sécurisé.
                                        <span v-if="isImmeuble" class="block mt-2 text-purple-600">
                                            <i class="fas fa-building mr-1"></i>
                                            Pour l'appartement {{ reservation.appartement?.numero }}
                                        </span>
                                    </p>

                                    <div class="rounded-lg p-6 mb-6 inline-block border-2"
                                         :class="isVente ? 'bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200' : 'bg-gradient-to-r from-green-50 to-teal-50 border-green-200'">
                                        <div class="flex items-center justify-center mb-3">
                                            <i :class="isVente ? 'fas fa-percentage text-blue-600' : 'fas fa-shield-alt text-green-600'" class="text-3xl mr-3"></i>
                                            <div class="text-left">
                                                <div class="text-2xl font-bold"
                                                     :class="isVente ? 'text-blue-700' : 'text-green-700'">
                                                    {{ formatPrice(reservation.montant) }} FCFA
                                                </div>
                                                <div class="text-sm text-gray-600">
                                                    {{ isVente ? 'Acompte (10% du prix)' : 'Caution (1 mois de loyer)' }}
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 mb-2">
                                            💳 Paiement 100% sécurisé | 🔒 Conforme aux normes bancaires
                                        </p>
                                        <p v-if="isVente" class="text-xs text-blue-600">
                                            ℹ️ Restant à payer : {{ formatPrice(getMontantRestant()) }} FCFA (90%)
                                        </p>
                                    </div>

                                    <button @click="initiatePayment"
                                            :disabled="processing"
                                            class="inline-flex items-center px-8 py-4 text-white rounded-lg font-bold text-lg transition-colors disabled:opacity-50 shadow-lg"
                                            :class="isVente ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700'">
                                        <i class="fas fa-lock mr-2"></i>
                                        <span v-if="processing">Traitement...</span>
                                        <span v-else>Payer {{ isVente ? 'l\'acompte' : 'la caution' }} maintenant</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Annulée -->
                            <div v-else-if="reservation.statut === 'annulée'">
                                <div class="mb-6">
                                    <div class="inline-flex items-center justify-center bg-red-100 rounded-full p-4 mb-4">
                                        <i class="fas fa-times-circle text-red-600 fa-3x"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800 mb-2">
                                        {{ isVente ? 'Acompte' : 'Dépôt' }} annulé
                                    </h3>
                                    <p class="text-gray-600">Cette demande a été annulée.</p>
                                    <div v-if="reservation.motif_rejet" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg inline-block text-left">
                                        <strong class="text-red-800">Raison :</strong>
                                        <p class="text-red-700 mt-1">{{ reservation.motif_rejet }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                        <Link :href="route('reservations.index')"
                              class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors shadow-lg">
                            <i class="fas fa-list mr-2"></i>
                            Mes réservations
                        </Link>
                        <Link :href="route('biens.index')"
                              class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition-colors shadow-lg">
                            <i class="fas fa-home mr-2"></i>
                            Parcourir les propriétés
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script>
import Layout from '@/Pages/Layout.vue'
export default { layout: Layout }
</script>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { route } from 'ziggy-js'
import placeholderImage from '@/assets/images/hero_bg_1.jpg'

const props = defineProps({
    reservation: { type: Object, default: () => null },
    paiement: { type: Object, default: () => null },
    documents: { type: Array, default: () => [] },
    userRoles: { type: Array, default: () => [] }
})

const processing = ref(false)

// Computed properties
const isVente = computed(() => {
    return props.reservation?.bien?.mandat?.type_mandat === 'vente'
})

// ✅ Vérifier si c'est un immeuble d'appartements
const isImmeuble = computed(() => {
    return props.reservation?.bien?.category?.name?.toLowerCase() === 'appartement' &&
        props.reservation?.appartement_id !== null
})

const documentsCount = computed(() => props.documents?.length || 0)

const isPaid = computed(() => {
    return props.reservation?.paiement_id &&
        (props.paiement?.statut === 'termine' ||
            props.paiement?.statut === 'reussi' ||
            props.reservation?.statut === 'payee')
})

// Méthodes dynamiques selon type de mandat
const getTitreReservation = () => {
    if (isImmeuble.value) {
        return isVente.value ? 'Réservation d\'appartement - Acompte' : 'Réservation d\'appartement - Caution'
    }
    return isVente.value ? 'Acompte de réservation' : 'Dépôt de garantie'
}

const getSousTitreReservation = () => {
    if (isImmeuble.value) {
        return isVente.value
            ? 'Suivi de votre acompte pour l\'achat d\'un appartement'
            : 'Suivi de votre caution pour la location d\'un appartement'
    }
    return isVente.value
        ? 'Suivi de votre acompte d\'achat immobilier'
        : 'Suivi de votre caution immobilière'
}

const getExplicationTitre = () => {
    if (isImmeuble.value) {
        return isVente.value
            ? 'Comprendre votre acompte pour l\'appartement'
            : 'Comprendre votre dépôt de garantie pour l\'appartement'
    }
    return isVente.value
        ? 'Comprendre votre acompte de réservation'
        : 'Comprendre votre dépôt de garantie'
}

const getMontantRestant = () => {
    if (!isVente.value || !props.reservation?.bien?.price) return 0
    return props.reservation.bien.price * 0.90
}

// Fonctions utilitaires
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

const getBienImage = (images) => {
    if (!images) return placeholderImage
    if (Array.isArray(images) && images.length > 0) {
        const firstImage = images[0]
        if (firstImage.url) return firstImage.url
        if (firstImage.chemin_image) return `/storage/${firstImage.chemin_image}`
        if (firstImage.path) return `/storage/${firstImage.path}`
    }
    return placeholderImage
}

const handleImageError = (event) => {
    event.target.src = placeholderImage
}

const getStatusLabel = (status) => {
    const labels = {
        'en_attente': 'En cours de vérification',
        'confirmée': 'Validé - Paiement requis',
        'payee': 'Payé',
        'payée': 'Payé',
        'annulee': 'Annulé',
        'annulée': 'Annulé'
    }
    return labels[status] || status || 'Inconnu'
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'en_attente': 'bg-yellow-100 text-yellow-800 border border-yellow-300',
        'confirmée': 'bg-green-100 text-green-800 border border-green-300',
        'payee': 'bg-blue-100 text-blue-800 border border-blue-300',
        'payée': 'bg-blue-100 text-blue-800 border border-blue-300',
        'annulee': 'bg-red-100 text-red-800 border border-red-300',
        'annulée': 'bg-red-100 text-red-800 border border-red-300'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusIcon = (status) => {
    const icons = {
        'en_attente': 'fas fa-clock',
        'confirmée': 'fas fa-check-circle',
        'payee': 'fas fa-check-double',
        'payée': 'fas fa-check-double',
        'annulee': 'fas fa-times-circle',
        'annulée': 'fas fa-times-circle'
    }
    return icons[status] || 'fas fa-question-circle'
}

const getDocumentTypeLabel = (type) => {
    const labels = {
        'cni': 'Carte Nationale d\'Identité',
        'passeport': 'Passeport',
        'justificatif_domicile': 'Justificatif de domicile',
        'bulletin_salaire': 'Bulletin de salaire',
        'attestation_travail': 'Attestation de travail',
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
        'png': 'fas fa-file-image text-blue-500'
    }
    return icons[extension] || 'fas fa-file text-gray-500'
}

const getDownloadUrl = (filePath) => {
    if (!filePath) return '#'
    return filePath.startsWith('/') ? filePath : `/storage/${filePath}`
}

const initiatePayment = () => {
    if (!props.reservation?.id) {
        alert('Erreur : Impossible d\'initier le paiement')
        return
    }
    processing.value = true

    try {
        const paymentUrl = route('reservations.initier-paiement', props.reservation.id)
        window.location.href = paymentUrl
    } catch (error) {
        console.error('Erreur lors de l\'initialisation du paiement:', error)
        processing.value = false
        alert('Une erreur est survenue. Veuillez réessayer.')
    }
}
</script>

<style scoped>
.btn {
    @apply px-4 py-2 rounded-lg font-medium transition-colors;
}

.btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700;
}

/* Animations personnalisées */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.bg-gradient-to-r {
    animation: fadeIn 0.5s ease-out;
}

/* Effets de survol améliorés */
.hover\:bg-blue-100:hover {
    transition: all 0.2s ease-in-out;
}

.hover\:bg-green-100:hover {
    transition: all 0.2s ease-in-out;
}

/* Style pour les badges */
.rounded-full {
    transition: transform 0.2s ease-in-out;
}

.rounded-full:hover {
    transform: scale(1.05);
}
</style>
