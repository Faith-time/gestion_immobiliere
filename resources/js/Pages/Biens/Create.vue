<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'

const props = defineProps({
    categories: Array,
})

const form = useForm({
    title: '',
    property_title: null,
    description: '',
    image: null,
    rooms: '',
    floors: '',
    bathrooms: '',
    city: '',
    address: '',
    superficy: '',
    price: '',
    status: '',
    categorie_id: '',
})

const imagePreview = ref(null)
const documentFileName = ref(null)

const statusOptions = [
    { value: 'disponible', label: 'Disponible' },
    { value: 'loue', label: 'Loué' },
    { value: 'vendu', label: 'Vendu' },
    { value: 'reserve', label: 'Réservé' },
]

const handleImageChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        form.image = file
        imagePreview.value = URL.createObjectURL(file)
    }
}

const handlePropertyTitleChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        form.property_title = file
        documentFileName.value = file.name
    }
}

const submit = () => {
    form.post(route('biens.store'), {
        forceFormData: true,
        preserveScroll: true
    })
}

const cancel = () => {
    router.visit(route('biens.index'))
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
        <!-- Header avec animation -->
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-800 py-16">
            <div class="absolute inset-0 bg-black/20"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-purple-300/20 rounded-full blur-3xl"></div>

            <div class="relative max-w-7xl mx-auto px-4 text-center">
                <h1 class="text-5xl font-extrabold text-white mb-4 tracking-tight">
                    Ajouter un Bien Immobilier
                </h1>
                <p class="text-xl text-blue-100 max-w-2xl mx-auto">
                    Créez une annonce exceptionnelle pour votre propriété
                </p>
            </div>
        </div>

        <!-- Formulaire principal -->
        <div class="max-w-6xl mx-auto px-4 -mt-8 relative z-10">
            <div class="bg-white/70 backdrop-blur-lg rounded-3xl shadow-2xl border border-white/20 overflow-hidden">
                <!-- Indicateur de progression -->
                <div class="h-2 bg-gradient-to-r from-blue-500 via-purple-500 to-indigo-600"></div>

                <form @submit.prevent="submit" class="p-12 space-y-10">
                    <!-- Section Informations principales -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">1</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Informations Principales</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Titre -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Titre du bien
                                </label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="Ex: Villa moderne avec piscine"
                                    required
                                />
                            </div>

                            <!-- Document -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Document commercial
                                </label>
                                <div class="relative">
                                    <input
                                        type="file"
                                        @change="handlePropertyTitleChange"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                        accept=".pdf,.doc,.docx"
                                        required
                                    />
                                    <div class="w-full px-4 py-4 border-2 border-dashed border-gray-300 rounded-xl hover:border-blue-400 transition-all duration-300 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center group-hover:from-blue-50 group-hover:to-purple-50">
                                        <div class="text-center">
                                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                                <span class="text-2xl">📎</span>
                                            </div>
                                            <p class="text-sm text-gray-600 font-medium">
                                                {{ documentFileName || 'Cliquez pour ajouter un document' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Localisation -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">2</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Localisation</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Ville -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Ville
                                </label>
                                <input
                                    v-model="form.city"
                                    type="text"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="Ex: Dakar"
                                    required
                                />
                            </div>

                            <!-- Adresse -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Adresse complète
                                </label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="Ex: Parcelles Assainies, Unité 15"
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Section Caractéristiques -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-teal-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">3</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Caractéristiques</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <!-- Superficie -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Superficie (m²)
                                </label>
                                <input
                                    v-model="form.superficy"
                                    type="number"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="200"
                                    required
                                />
                            </div>

                            <!-- Prix -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Prix (FCFA)
                                </label>
                                <input
                                    v-model="form.price"
                                    type="number"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="50000000"
                                    required
                                />
                            </div>

                            <!-- Chambres -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Nombre de chambres
                                </label>
                                <input
                                    v-model="form.rooms"
                                    type="number"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="3"
                                />
                            </div>

                            <!-- Étages -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Nombre d'étages
                                </label>
                                <input
                                    v-model="form.floors"
                                    type="number"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="2"
                                />
                            </div>

                            <!-- Salles de bain -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Salles de bain
                                </label>
                                <input
                                    v-model="form.bathrooms"
                                    type="number"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="2"
                                />
                            </div>

                            <!-- Statut -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Statut
                                </label>
                                <select
                                    v-model="form.status"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm appearance-none cursor-pointer"
                                    required
                                >
                                    <option value="" class="text-gray-500">-- Sélectionner --</option>
                                    <option v-for="status in statusOptions" :key="status.value" :value="status.value" class="text-gray-800">
                                        {{ status.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section Catégorie -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">4</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Catégorie</h2>
                        </div>

                        <div class="group max-w-md">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Type de bien
                            </label>
                            <select
                                v-model="form.categorie_id"
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm appearance-none cursor-pointer"
                                required
                            >
                                <option value="" class="text-gray-500">-- Sélectionner une catégorie --</option>
                                <option v-for="categorie in categories" :key="categorie.id" :value="categorie.id" class="text-gray-800">
                                    {{ categorie.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Section Image -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-pink-500 to-rose-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">5</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Image du Bien</h2>
                        </div>

                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Photo principale
                            </label>
                            <div class="relative">
                                <input
                                    type="file"
                                    @change="handleImageChange"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                    accept="image/*"
                                />
                                <div class="w-full h-64 border-2 border-dashed border-gray-300 rounded-xl hover:border-pink-400 transition-all duration-300 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center group-hover:from-pink-50 group-hover:to-rose-50">
                                    <div v-if="!imagePreview" class="text-center">
                                        <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <span class="text-3xl">🖼️</span>
                                        </div>
                                        <p class="text-gray-600 font-medium mb-2">Cliquez pour ajouter une photo</p>
                                        <p class="text-sm text-gray-500">PNG, JPG, WebP jusqu'à 10MB</p>
                                    </div>

                                    <div v-else class="relative w-full h-full">
                                        <img :src="imagePreview" class="w-full h-full object-cover rounded-lg" />
                                        <div class="absolute inset-0 bg-black/40 opacity-0 hover:opacity-100 transition-opacity duration-300 flex items-center justify-center rounded-lg">
                                            <p class="text-white font-semibold">Cliquez pour changer</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Description -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-teal-500 to-cyan-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">6</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Description</h2>
                        </div>

                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Description détaillée
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="6"
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm resize-none"
                                placeholder="Décrivez votre bien immobilier en détail : équipements, points forts, environnement..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-6 pt-8 border-t border-gray-200">
                        <button
                            type="button"
                            @click="cancel"
                            class="px-8 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 border-2 border-gray-200 hover:border-gray-300"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            class="px-12 py-4 bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 hover:from-blue-700 hover:via-purple-700 hover:to-indigo-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                        >
                            Créer le Bien
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Espacement en bas -->
        <div class="h-20"></div>
    </div>
</template>

<style scoped>
/* Animation pour les inputs */
.group input:focus, .group select:focus, .group textarea:focus {
    transform: translateY(-1px);
}

/* Style personnalisé pour les select */
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px;
}

/* Animation au survol des sections */
.group:hover {
    transform: translateY(-2px);
}

/* Transition fluide pour tous les éléments */
* {
    transition: all 0.3s ease;
}
</style>
