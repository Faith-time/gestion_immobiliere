<template>
    <Layout title="Finaliser l'Achat">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Finaliser l'Achat du Bien
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 sm:p-8">
                        <!-- Message d'information -->
                        <div class="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-blue-800 font-medium">
                                    Vous êtes sur le point de finaliser l'achat de ce bien immobilier.
                                </p>
                            </div>
                        </div>

                        <!-- Détails du bien à acheter -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Bien à Acquérir
                            </h3>

                            <div class="bg-gray-50 rounded-lg p-6">
                                <div class="flex items-start space-x-6">
                                    <div class="flex-shrink-0">
                                        <img
                                            v-if="bien.image"
                                            :src="`/storage/${bien.image}`"
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
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                {{ bien.address }}, {{ bien.city }}
                                            </p>
                                            <p class="flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                                </svg>
                                                {{ bien.superficy }} m² • {{ bien.rooms }} pièces
                                            </p>
                                            <div class="mt-4">
                                                <span class="text-2xl font-bold text-green-600">
                                                    {{ formatPrice(bien.price) }} FCFA
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Récapitulatif automatique -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Récapitulatif de l'Achat
                            </h3>

                            <div class="bg-gray-50 rounded-lg p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Acheteur
                                            </label>
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
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Date d'Achat
                                            </label>
                                            <div class="p-3 bg-white rounded border text-gray-900">
                                                {{ formatDate(today) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Prix d'Achat
                                            </label>
                                            <div class="p-3 bg-white rounded border">
                                                <span class="text-lg font-bold text-green-600">
                                                    {{ formatPrice(bien.price) }} FCFA
                                                </span>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Statut
                                            </label>
                                            <div class="p-3 bg-white rounded border">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Prêt pour finalisation
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Message de confirmation -->
                        <div class="mb-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.732 15.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <div>
                                    <p class="text-yellow-800 font-medium mb-1">Confirmation importante</p>
                                    <p class="text-yellow-700 text-sm">
                                        En cliquant sur "Finaliser l'Achat", vous confirmez votre intention d'acquérir ce bien au prix indiqué.
                                        Cette action créera un enregistrement de vente officiel.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                            <Link
                                href="/"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-6 rounded-lg transition duration-150 ease-in-out"
                            >
                                Retour à l'accueil
                            </Link>

                            <button
                                @click="finaliserAchat"
                                :disabled="processing"
                                :class="{
                                    'bg-green-600 hover:bg-green-700 text-white': !processing,
                                    'bg-gray-400 text-gray-600 cursor-not-allowed': processing
                                }"
                                class="font-bold py-3 px-8 rounded-lg transition duration-150 ease-in-out flex items-center"
                            >
                                <span v-if="processing">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Finalisation...
                                </span>
                                <span v-else class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Finaliser l'Achat
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
import { ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import Layout from '../Layout.vue'
import {route} from "ziggy-js";

// Props - Le bien est automatiquement passé depuis la page de succès
const props = defineProps({
    bien: {
        type: Object,
        required: true
    }
})

const processing = ref(false)
const { auth } = usePage().props

// Date d'aujourd'hui
const today = new Date().toISOString().split('T')[0]

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

// Finalisation automatique de l'achat
const finaliserAchat = () => {
    processing.value = true

    // Vérification de sécurité - empêcher l'auto-achat
    if (props.bien.proprietaire_id === auth.user.id) {
        alert('Vous ne pouvez pas acheter votre propre bien.')
        processing.value = false
        return
    }

    // Données automatiquement mappées
    const venteData = {
        biens_id: props.bien.id,
        prix_vente: props.bien.price,  // Utiliser le prix du bien
        date_vente: today
    }

    router.post(route('ventes.store'), venteData, {
        onSuccess: (page) => {
            processing.value = false
            router.visit(route('ventes.index'))
        },
        onError: (errors) => {
            processing.value = false
            console.error('Erreur lors de la finalisation:', errors)
            // Afficher les erreurs à l'utilisateur
            if (errors.message) {
                alert(errors.message)
            } else {
                alert('Une erreur est survenue lors de la finalisation de l\'achat.')
            }
        }
    })
}
</script>
