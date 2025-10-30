<template>
    <Layout title="Finaliser l'Achat">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Finaliser l'Achat du Bien
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 sm:p-8">
                        <!-- Messages Flash -->
                        <div v-if="hasFlashError && !processing" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-red-800 font-medium">{{ $page.props.flash?.error }}</p>
                            </div>
                        </div>

                        <!-- Message de chargement -->
                        <div v-if="processing" class="mb-8 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="animate-spin h-5 w-5 text-green-500 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-green-800 font-medium">
                                    Création de votre achat en cours...
                                </p>
                            </div>
                        </div>

                        <!-- ✅ ALERTE DÉPÔT DÉJÀ VERSÉ (NOUVEAU) -->
                        <div v-if="!processing" class="mb-6 p-4 bg-blue-50 border-2 border-blue-300 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex-1">
                                    <p class="text-blue-900 font-semibold mb-2">
                                        📌 Information importante sur le paiement
                                    </p>
                                    <p class="text-blue-800 text-sm leading-relaxed">
                                        Vous avez déjà versé <strong class="font-bold">{{ formatPrice(depotVerse) }} FCFA (10%)</strong>
                                        lors de votre réservation. Le montant restant à payer est de
                                        <strong class="font-bold text-green-700">{{ formatPrice(montantRestant) }} FCFA (90%)</strong>.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Détails du bien -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Bien à Acquérir
                            </h3>
                            <div class="bg-gray-50 rounded-lg p-6">
                                <div class="flex items-start space-x-6">
                                    <div class="flex-shrink-0">
                                        <img
                                            v-if="bien.images && bien.images.length > 0"
                                            :src="bien.images[0].url"
                                            :alt="bien.title"
                                            class="w-32 h-32 object-cover rounded-lg"
                                        />
                                        <div v-else class="w-32 h-32 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-xl font-semibold text-gray-900 mb-2">{{ bien.title }}</h4>
                                        <div class="space-y-2 text-gray-600">
                                            <p class="flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                </svg>
                                                {{ bien.address }}, {{ bien.city }}
                                            </p>
                                            <p class="flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                                </svg>
                                                {{ bien.superficy }} m² • {{ bien.rooms }} pièces
                                            </p>

                                            <!-- ✅ DÉTAILS DU PAIEMENT (NOUVEAU) -->
                                            <div class="mt-4 pt-4 border-t border-gray-300">
                                                <div class="space-y-2">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-gray-700 font-medium">Prix total du bien :</span>
                                                        <span class="text-xl font-bold text-gray-900">
                                                            {{ formatPrice(bien.price) }} FCFA
                                                        </span>
                                                    </div>
                                                    <div class="flex justify-between items-center text-sm">
                                                        <span class="text-gray-600">Dépôt déjà versé (10%) :</span>
                                                        <span class="font-semibold text-green-600">
                                                            - {{ formatPrice(depotVerse) }} FCFA
                                                        </span>
                                                    </div>
                                                    <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                                                        <span class="text-gray-800 font-semibold">Montant restant à payer (90%) :</span>
                                                        <span class="text-2xl font-bold text-blue-600">
                                                            {{ formatPrice(montantRestant) }} FCFA
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 📄 PRÉVISUALISATION DU CONTRAT -->
                        <div v-if="!processing" class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Contrat de Vente (Prévisualisation)
                            </h3>

                            <div class="bg-white border-2 border-gray-300 rounded-lg overflow-hidden">
                                <!-- En-tête du contrat -->
                                <div class="bg-gradient-to-r from-blue-50 to-blue-100 px-6 py-4 border-b">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-lg font-bold text-gray-800">CONTRAT DE VENTE IMMOBILIÈRE</h4>
                                        <span class="text-sm text-gray-600">{{ formatDate(today) }}</span>
                                    </div>
                                </div>

                                <!-- Contenu du contrat avec scroll -->
                                <div class="px-6 py-4 max-h-96 overflow-y-auto bg-gray-50 contract-preview">
                                    <div class="space-y-4 text-sm text-gray-700">
                                        <section>
                                            <h5 class="font-semibold text-gray-900 mb-2">ENTRE LES SOUSSIGNÉS :</h5>
                                            <div class="ml-4 space-y-2">
                                                <p><strong>Le Vendeur :</strong> {{ bien.proprietaire?.name || 'Propriétaire' }}</p>
                                                <p><strong>L'Acquéreur :</strong> {{ $page.props.auth.user.name }}</p>
                                            </div>
                                        </section>

                                        <section>
                                            <h5 class="font-semibold text-gray-900 mb-2">OBJET DU CONTRAT :</h5>
                                            <p class="ml-4">
                                                Le Vendeur vend à l'Acquéreur qui accepte, le bien immobilier désigné ci-après :
                                            </p>
                                            <div class="ml-4 mt-2 space-y-1">
                                                <p><strong>Désignation :</strong> {{ bien.title }}</p>
                                                <p><strong>Adresse :</strong> {{ bien.address }}, {{ bien.city }}</p>
                                                <p><strong>Superficie :</strong> {{ bien.superficy }} m²</p>
                                                <p><strong>Type :</strong> {{ bien.category?.name }}</p>
                                            </div>
                                        </section>

                                        <section>
                                            <h5 class="font-semibold text-gray-900 mb-2">PRIX ET CONDITIONS DE PAIEMENT :</h5>
                                            <div class="ml-4 space-y-2">
                                                <p>Le prix de vente total est fixé à : <strong class="text-gray-900">{{ formatPrice(bien.price) }} FCFA</strong></p>
                                                <div class="bg-blue-50 border border-blue-200 rounded p-3 mt-2">
                                                    <p class="text-sm font-medium text-blue-900 mb-1">Modalités de paiement :</p>
                                                    <ul class="text-sm text-blue-800 space-y-1">
                                                        <li>• Dépôt de garantie (10%) : <strong>{{ formatPrice(depotVerse) }} FCFA</strong> (déjà versé)</li>
                                                        <li>• Solde restant (90%) : <strong class="text-green-700">{{ formatPrice(montantRestant) }} FCFA</strong> (à payer)</li>
                                                    </ul>
                                                </div>
                                                <p class="text-xs text-gray-600 mt-2">
                                                    Le paiement du solde sera effectué selon les modalités suivantes : paiement intégral
                                                    ou en plusieurs tranches si le montant dépasse 3 000 000 FCFA.
                                                </p>
                                            </div>
                                        </section>

                                        <section>
                                            <h5 class="font-semibold text-gray-900 mb-2">GARANTIES ET DÉCLARATIONS :</h5>
                                            <ul class="ml-4 list-disc list-inside space-y-1">
                                                <li>Le Vendeur garantit être le propriétaire légitime du bien</li>
                                                <li>Le bien est libre de toute charge, hypothèque ou servitude non déclarée</li>
                                                <li>L'Acquéreur déclare avoir visité le bien et l'accepter en l'état</li>
                                            </ul>
                                        </section>

                                        <section>
                                            <h5 class="font-semibold text-gray-900 mb-2">CONDITIONS SUSPENSIVES :</h5>
                                            <ul class="ml-4 list-disc list-inside space-y-1">
                                                <li>Obtention du financement dans un délai de 60 jours (si applicable)</li>
                                                <li>Vérification des documents de propriété</li>
                                            </ul>
                                        </section>

                                        <section>
                                            <h5 class="font-semibold text-gray-900 mb-2">TRANSFERT DE PROPRIÉTÉ :</h5>
                                            <p class="ml-4">
                                                Le transfert de propriété et la remise des clés interviendront après paiement complet
                                                du prix de vente et signature du présent contrat par les deux parties.
                                            </p>
                                        </section>

                                        <section>
                                            <h5 class="font-semibold text-gray-900 mb-2">FRAIS :</h5>
                                            <p class="ml-4">
                                                Les frais de notaire, d'enregistrement et autres frais annexes sont à la charge de l'Acquéreur,
                                                conformément aux usages en vigueur.
                                            </p>
                                        </section>
                                    </div>
                                </div>

                                <!-- Bouton téléchargement aperçu -->
                                <div class="px-6 py-3 bg-gray-100 border-t flex justify-end">
                                    <button
                                        @click="downloadPreview"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Télécharger l'aperçu
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ✅ ACCEPTATION DES TERMES -->
                        <div v-if="!processing" class="mb-8 p-6 bg-blue-50 border-2 border-blue-200 rounded-lg">
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    v-model="acceptedTerms"
                                    class="mt-1 w-5 h-5 text-blue-600 rounded focus:ring-blue-500 cursor-pointer"
                                    required
                                />
                                <span class="text-sm text-gray-700 leading-relaxed">
                                    <strong class="text-gray-900">Je certifie avoir lu et accepté l'intégralité des termes et conditions</strong>
                                    du contrat de vente ci-dessus. Je comprends mes droits et obligations en tant qu'acquéreur et je m'engage
                                    à respecter les clauses du présent contrat. Je reconnais que cette transaction est définitive après paiement complet.
                                </span>
                            </label>
                        </div>

                        <!-- Récapitulatif -->
                        <div v-if="!processing" class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Récapitulatif de l'Achat
                            </h3>
                            <div class="bg-gray-50 rounded-lg p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Acheteur</label>
                                            <div class="flex items-center p-3 bg-white rounded border">
                                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-sm font-medium text-blue-800">
                                                        {{ getInitials($page.props.auth.user.name) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900">{{ $page.props.auth.user.name }}</div>
                                                    <div class="text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Date d'Achat</label>
                                            <div class="p-3 bg-white rounded border text-gray-900">
                                                {{ formatDate(today) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix total du bien</label>
                                            <div class="p-3 bg-white rounded border">
                                                <span class="text-lg font-bold text-gray-900">
                                                    {{ formatPrice(bien.price) }} FCFA
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Montant à payer (90%)</label>
                                            <div class="p-3 bg-white rounded border">
                                                <span class="text-lg font-bold text-green-600">
                                                    {{ formatPrice(montantRestant) }} FCFA
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                                            <div class="p-3 bg-white rounded border">
                                                <span v-if="!acceptedTerms" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    En attente d'acceptation
                                                </span>
                                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Prêt pour paiement
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Message de confirmation -->
                        <div v-if="!processing && acceptedTerms" class="mb-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.732 15.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <div>
                                    <p class="text-yellow-800 font-medium mb-1">Dernière étape</p>
                                    <p class="text-yellow-700 text-sm">
                                        En cliquant sur "Procéder au Paiement", vous serez redirigé vers la page de paiement sécurisée
                                        pour régler les <strong>{{ formatPrice(montantRestant) }} FCFA (90%)</strong> restants.
                                        Le paiement pourra être effectué en plusieurs tranches si le montant dépasse 3 000 000 FCFA.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                            <Link
                                href="/"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-6 rounded-lg transition duration-150"
                                :disabled="processing"
                            >
                                Retour
                            </Link>

                            <button
                                @click="finaliserAchat"
                                :disabled="processing || !acceptedTerms"
                                :class="{
                                    'bg-green-600 hover:bg-green-700 text-white cursor-pointer': !processing && acceptedTerms,
                                    'bg-gray-400 text-gray-600 cursor-not-allowed': processing || !acceptedTerms
                                }"
                                class="font-bold py-3 px-8 rounded-lg transition duration-150 flex items-center"
                            >
                                <span v-if="processing">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Redirection...
                                </span>
                                <span v-else-if="!acceptedTerms" class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Acceptez les termes
                                </span>
                                <span v-else class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    Procéder au Paiement ({{ formatPrice(montantRestant) }} FCFA)
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import Layout from '../Layout.vue'

const props = defineProps({
    bien: { type: Object, required: true },
    reservation: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) }
})

const processing = ref(false)
const acceptedTerms = ref(false)
const page = usePage()
const today = new Date().toISOString().split('T')[0]

// ✅ Computed pour calculer le dépôt versé et le montant restant
const depotVerse = computed(() => {
    return props.bien.price * 0.10 // 10% du prix
})

const montantRestant = computed(() => {
    return props.bien.price * 0.90 // 90% du prix
})

const hasFlashError = computed(() => !!page.props.flash?.error)

const formatPrice = (price) => new Intl.NumberFormat('fr-FR').format(price)
const formatDate = (dateString) => new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric', month: 'long', day: 'numeric'
})
const getInitials = (name) => name.split(' ').map(w => w.charAt(0).toUpperCase()).join('').substring(0, 2)

const downloadPreview = () => {
    alert('📥 Fonctionnalité de téléchargement à implémenter côté serveur')
}

const finaliserAchat = () => {
    if (processing.value || !acceptedTerms.value) return
    if (props.bien.proprietaire_id === page.props.auth.user.id) {
        alert('❌ Vous ne pouvez pas acheter votre propre bien.')
        return
    }

    processing.value = true

    router.post('/ventes', {
        reservation_id: props.reservation.id,
        prix_vente: props.bien.price,
        date_vente: today,
        termes_acceptes: true
    }, {
        preserveState: false,
        preserveScroll: false,
        onSuccess: () => console.log('✅ Vente créée'),
        onError: (errors) => {
            processing.value = false
            console.error('❌ Erreurs:', errors)
        }
    })
}

onMounted(() => {
    console.log('📄 Page création vente chargée', {
        bien_id: props.bien.id,
        prix_total: props.bien.price,
        depot_verse_10: depotVerse.value,
        montant_restant_90: montantRestant.value
    })
})
</script>

<style scoped>
.contract-preview {
    scrollbar-width: thin;
    scrollbar-color: #CBD5E0 #F7FAFC;
}
.contract-preview::-webkit-scrollbar {
    width: 8px;
}
.contract-preview::-webkit-scrollbar-track {
    background: #F7FAFC;
}
.contract-preview::-webkit-scrollbar-thumb {
    background-color: #CBD5E0;
    border-radius: 4px;
}
</style>
