<template>
    <Layout title="Paiement">
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-8">
                        <!-- En-tête -->
                        <div class="text-center mb-8">
                            <div class="flex justify-center mb-4">
                                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </div>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">Finaliser le Paiement</h2>
                            <p class="text-gray-600">Vous allez être redirigé vers PayDunya pour effectuer le paiement sécurisé</p>
                        </div>

                        <!-- Détails du paiement -->
                        <div class="mb-8 bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Détails du Paiement</h3>

                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Type :</span>
                                    <span class="font-semibold capitalize">{{ getTypeLabel(type) }}</span>
                                </div>

                                <div v-if="item.bien" class="flex justify-between items-center">
                                    <span class="text-gray-600">Bien :</span>
                                    <span class="font-semibold text-right">{{ item.bien.title }}</span>
                                </div>

                                <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                                    <span class="text-gray-600">Montant Total :</span>
                                    <span class="text-2xl font-bold text-green-600">
                                        {{ formatPrice(paiement.montant_total) }} FCFA
                                    </span>
                                </div>

                                <div v-if="type === 'location'" class="text-sm text-gray-500 italic">
                                    (Premier mois + Caution)
                                </div>
                            </div>
                        </div>

                        <!-- Formulaire de paiement -->
                        <form @submit.prevent="initierPaiement">
                            <div class="space-y-4 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Nom complet <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.customer_name"
                                        type="text"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.customer_email"
                                        type="email"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Téléphone <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.customer_phone"
                                        type="tel"
                                        placeholder="+221701234567"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Mode de paiement <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.mode_paiement"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="">Sélectionner un mode de paiement</option>
                                        <option value="mobile_money">Mobile Money (Tous)</option>
                                        <option value="wave">Wave</option>
                                        <option value="orange_money">Orange Money</option>
                                        <option value="mtn_money">MTN Money</option>
                                        <option value="moov_money">Moov Money</option>
                                        <option value="carte">Carte bancaire</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Message d'information -->
                            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-sm text-blue-800">
                                        Vous serez redirigé vers la plateforme sécurisée PayDunya pour finaliser votre paiement.
                                        Ne fermez pas cette fenêtre pendant le processus.
                                    </p>
                                </div>
                            </div>

                            <!-- Boutons d'action -->
                            <div class="flex items-center justify-between">
                                <Link
                                    :href="getPreviousUrl()"
                                    class="px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition"
                                >
                                    Annuler
                                </Link>

                                <button
                                    type="submit"
                                    :disabled="processing"
                                    :class="{
                                        'bg-blue-600 hover:bg-blue-700 text-white': !processing,
                                        'bg-gray-400 text-gray-600 cursor-not-allowed': processing
                                    }"
                                    class="px-8 py-3 font-semibold rounded-lg transition flex items-center"
                                >
                                    <span v-if="processing">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Redirection vers PayDunya...
                                    </span>
                                    <span v-else class="flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        Procéder au Paiement
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Layout from '../Layout.vue'
import { route } from 'ziggy-js'
import axios from 'axios'

const props = defineProps({
    type: {
        type: String,
        required: true
    },
    item: {
        type: Object,
        required: true
    },
    paiement: {
        type: Object,
        required: true
    },
    user: {
        type: Object,
        required: true
    }
})

const processing = ref(false)
const form = ref({
    paiement_id: props.paiement.id,
    customer_name: props.user.name || '',
    customer_email: props.user.email || '',
    customer_phone: '',
    description: `Paiement ${props.type} #${props.paiement.id}`,
    mode_paiement: ''
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const getTypeLabel = (type) => {
    const labels = {
        'reservation': 'Réservation',
        'vente': 'Achat',
        'location': 'Location'
    }
    return labels[type] || type
}

const getPreviousUrl = () => {
    if (props.type === 'vente') {
        return route('ventes.create', { bien_id: props.item.bien?.id })
    } else if (props.type === 'location') {
        return route('locations.create', { bien_id: props.item.bien?.id })
    } else if (props.type === 'reservation') {
        return route('reservations.create', props.item.bien?.id)
    }
    return route('home')
}

const initierPaiement = async () => {
    if (processing.value) return

    if (!form.value.customer_name || !form.value.customer_email || !form.value.customer_phone || !form.value.mode_paiement) {
        alert('⚠️ Veuillez remplir tous les champs requis.')
        return
    }

    processing.value = true

    try {
        const response = await axios.post(route('paiement.initier'), form.value)

        if (response.data.success && response.data.payment_url) {
            // Rediriger vers PayDunya
            window.location.href = response.data.payment_url
        } else {
            alert('❌ Erreur lors de l\'initiation du paiement : ' + (response.data.message || 'Erreur inconnue'))
            processing.value = false
        }
    } catch (error) {
        console.error('Erreur initiation paiement:', error)
        alert('❌ Une erreur est survenue : ' + (error.response?.data?.message || error.message))
        processing.value = false
    }
}

onMounted(() => {
    console.log('Page paiement chargée:', {
        type: props.type,
        paiement_id: props.paiement.id,
        montant: props.paiement.montant_total
    })
})
</script>
