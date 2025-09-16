<script setup>
import { ref } from 'vue'
import axios from 'axios'

const montant = ref(5000)
const email = ref('')
const nom = ref('')
const telephone = ref('')
const loading = ref(false)
const errorMessage = ref('')

async function lancerPaiement() {
    loading.value = true
    errorMessage.value = ''
    try {
        const res = await axios.post('/api/paiement/initier', {
            amount: montant.value,
            email: email.value,
            name: nom.value,
            phone: telephone.value
        })

        if (res.data.code === '201' && res.data.data.payment_url) {
            window.location.href = res.data.data.payment_url
        } else {
            errorMessage.value = "Impossible d'initier le paiement. Réessayez."
        }
    } catch (e) {
        console.error(e)
        errorMessage.value = "Erreur de connexion au serveur."
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-100 to-indigo-200">
        <div class="bg-white shadow-2xl rounded-2xl p-10 w-full max-w-lg">
            <!-- Header -->
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">
                Paiement en ligne sécurisé
            </h1>
            <p class="text-center text-gray-500 mb-6">
                Remplissez vos informations pour initier le paiement via CinetPay
            </p>

            <!-- Formulaire -->
            <form @submit.prevent="lancerPaiement" class="space-y-5">
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Nom complet</label>
                    <input v-model="nom" type="text" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-400 outline-none">
                </div>

                <div>
                    <label class="block text-gray-600 font-medium mb-1">Email</label>
                    <input v-model="email" type="email" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-400 outline-none">
                </div>

                <div>
                    <label class="block text-gray-600 font-medium mb-1">Téléphone</label>
                    <input v-model="telephone" type="tel" required
                           placeholder="+22500000000"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-400 outline-none">
                </div>

                <div>
                    <label class="block text-gray-600 font-medium mb-1">Montant (XOF)</label>
                    <input v-model="montant" type="number" min="500" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-400 outline-none">
                </div>

                <!-- Message d'erreur -->
                <div v-if="errorMessage" class="text-red-600 text-sm">
                    {{ errorMessage }}
                </div>

                <!-- Bouton -->
                <button type="submit"
                        class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition disabled:opacity-50"
                        :disabled="loading">
                    <span v-if="loading">⏳ Redirection en cours...</span>
                    <span v-else>Payer maintenant</span>
                </button>
            </form>

            <!-- Footer -->
            <p class="text-center text-gray-400 text-xs mt-6">
                Mode test activé - aucun débit réel ne sera effectué 🚀
            </p>
        </div>
    </div>
</template>
