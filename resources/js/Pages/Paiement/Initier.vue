<script setup>
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";

const montant = ref(5000); // Montant test
const description = ref("Paiement test CinetPay");
const email = ref("client@test.com");
const telephone = ref("0700000000");

const lancerPaiement = () => {
    Inertia.post("/paiement/initier", {
        montant: montant.value,
        description: description.value,
        email: email.value,
        telephone: telephone.value,
    });
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center">
        <div class="bg-white shadow-2xl rounded-2xl p-10 w-full max-w-lg text-center">

            <h1 class="text-3xl font-bold text-gray-800 mb-6">💳 Paiement en ligne</h1>
            <p class="text-gray-500 mb-8">
                Remplissez les informations pour initier un paiement via <span class="font-semibold">CinetPay</span>.
            </p>

            <div class="space-y-4">
                <input v-model="montant" type="number" placeholder="Montant"
                       class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300" />

                <input v-model="description" type="text" placeholder="Description"
                       class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300" />

                <input v-model="email" type="email" placeholder="Email"
                       class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300" />

                <input v-model="telephone" type="tel" placeholder="Téléphone"
                       class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300" />
            </div>

            <button @click="lancerPaiement"
                    class="mt-8 w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-lg shadow-md transition duration-300">
                🚀 Lancer le paiement
            </button>
        </div>
    </div>
</template>

<style scoped>
input {
    outline: none;
}
</style>
