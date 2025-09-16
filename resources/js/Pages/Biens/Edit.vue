<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'

const props = defineProps({
    bien: Object,
    categories: Array,
})

console.log('Props reçues:', props)

const form = useForm({
    title: props.bien?.title || '',
    property_title: null,
    description: props.bien?.description || '',
    image: null,
    rooms: props.bien?.rooms || '',
    floors: props.bien?.floors || '',
    bathrooms: props.bien?.bathrooms || '',
    city: props.bien?.city || '',
    address: props.bien?.address || '',
    superficy: props.bien?.superficy || '',
    price: props.bien?.price || '',
    status: props.bien?.status || '',
    categorie_id: props.bien?.categorie_id || '',
})

const submit = () => {
    if (!props.bien?.id) {
        console.error('ID du bien manquant')
        return
    }

    // Préparer manuellement le FormData
    const formData = new FormData()
    const rawData = form.data()

    for (const key in rawData) {
        const value = rawData[key]

        // Cas spécial pour fichier
        if (value instanceof File) {
            formData.append(key, value)
        } else if (value === null || value === undefined) {
            formData.append(key, '')
        } else {
            formData.append(key, value)
        }
    }

    // On simule la méthode PUT
    formData.append('_method', 'PUT')

    // Envoi manuel via axios (plus fiable pour FormData complexe)
    axios.post(route('biens.update', props.bien.id), formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(() => {
            console.log('✅ Bien modifié avec succès')
            router.visit(route('biens.index'))
        })
        .catch(error => {
            console.error('❌ Erreur lors de la mise à jour', error)

            if (error.response?.data?.errors) {
                let errorMessage = 'Erreurs de validation:\n'
                Object.entries(error.response.data.errors).forEach(([key, value]) => {
                    errorMessage += `- ${key}: ${value}\n`
                })
                alert(errorMessage)
            } else {
                alert('Une erreur inattendue est survenue.')
            }
        })
}

const imagePreview = ref(props.bien?.image ? `/storage/${props.bien.image}` : null)
const documentFileName = ref(props.bien?.property_title ? 'Document existant' : null)
const currentDocument = ref(props.bien?.property_title || null)

const statusOptions = [
    { value: 'disponible', label: 'Disponible' },
    { value: 'loue', label: 'Loué' },
    { value: 'vendu', label: 'Vendu' },
    { value: 'reserve', label: 'Réservé' },
]

const debugFormData = () => {
    console.log('Valeurs actuelles du formulaire:', {
        title: form.title,
        city: form.city,
        address: form.address,
        superficy: form.superficy,
        price: form.price,
        status: form.status,
        categorie_id: form.categorie_id,
        rooms: form.rooms,
        floors: form.floors,
        bathrooms: form.bathrooms
    })
}

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



const cancel = () => {
    router.visit(route('biens.index'))
}
</script>

<script>
import Layout from '@/Pages/Layout.vue'

export default {
    layout: Layout
}
</script>

<template>
    <!-- Debug: Afficher les props pour vérifier -->
    <div v-if="!bien || !bien.id" class="min-h-screen flex items-center justify-center">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-red-600 mb-4">❌ Erreur: Bien non trouvé</h2>
            <p class="text-gray-600">Les données du bien ne sont pas disponibles.</p>
            <div class="mt-4 p-4 bg-gray-100 rounded-lg text-left">
                <p class="text-sm text-gray-700 mb-2"><strong>Debug:</strong></p>
                <pre class="text-xs">{{ JSON.stringify(props, null, 2) }}</pre>
            </div>
            <button @click="cancel" class="mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg">
                Retour à la liste
            </button>
        </div>
    </div>

    <div v-else class="min-h-screen bg-gradient-to-br from-amber-50 via-orange-50 to-red-50">
        <!-- Header avec animation -->
        <div class="relative overflow-hidden bg-gradient-to-r from-orange-600 via-red-600 to-pink-800 py-16">
            <div class="absolute inset-0 bg-black/20"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-yellow-300/20 rounded-full blur-3xl"></div>

            <div class="relative max-w-7xl mx-auto px-4 text-center">
                <h1 class="text-5xl font-extrabold text-white mb-4 tracking-tight">
                    ✏️ Modifier le Bien Immobilier
                </h1>
                <p class="text-xl text-orange-100 max-w-2xl mx-auto">
                    Mettez à jour les informations de votre propriété
                </p>
                <!-- Debug info -->
                <div class="mt-4 text-orange-100 text-sm">
                    ID du bien: {{ bien.id }} | Titre: {{ bien.title }}
                </div>
            </div>
        </div>

        <!-- Formulaire principal -->
        <div class="max-w-6xl mx-auto px-4 -mt-8 relative z-10">
            <div class="bg-white/70 backdrop-blur-lg rounded-3xl shadow-2xl border border-white/20 overflow-hidden">
                <!-- Indicateur de progression -->
                <div class="h-2 bg-gradient-to-r from-orange-500 via-red-500 to-pink-600"></div>

                <!-- Message d'erreur global -->
                <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4 m-4">
                    <h3 class="text-red-800 font-semibold mb-2">Erreurs de validation:</h3>
                    <ul class="text-red-700 text-sm">
                        <li v-for="(error, field) in form.errors" :key="field">
                            <strong>{{ field }}:</strong> {{ error }}
                        </li>
                    </ul>
                </div>

                <!-- ✅ Bouton de débogage (temporaire) -->
                <div class="p-4 bg-blue-50 border-b">
                    <button type="button" @click="debugFormData" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                        🐛 Debug Form Data
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-12 space-y-10">
                    <!-- Section Informations principales -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">1</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Informations Principales</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Titre -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    🏠 Titre du bien <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    required
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    :class="{ 'border-red-500': form.errors.title }"
                                    placeholder="Ex: Villa moderne avec piscine"
                                    @input="debugFormData"
                                />
                                <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <!-- Document -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    📎 Document commercial
                                </label>
                                <div class="relative">
                                    <input
                                        type="file"
                                        @change="handlePropertyTitleChange"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                        accept=".pdf,.doc,.docx"
                                    />
                                    <div class="w-full px-4 py-4 border-2 border-dashed border-gray-300 rounded-xl hover:border-orange-400 transition-all duration-300 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center group-hover:from-orange-50 group-hover:to-red-50">
                                        <div class="text-center">
                                            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                                <span class="text-2xl">📎</span>
                                            </div>
                                            <p class="text-sm text-gray-600 font-medium">
                                                {{ documentFileName || 'Cliquez pour remplacer le document' }}
                                            </p>
                                            <p v-if="currentDocument && !documentFileName" class="text-xs text-gray-500 mt-1">
                                                Document actuel disponible
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
                            <div class="w-10 h-10 bg-gradient-to-r from-red-500 to-pink-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">2</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Localisation</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Ville -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    🏙️ Ville <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.city"
                                    type="text"
                                    required
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-red-500 focus:ring-4 focus:ring-red-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    :class="{ 'border-red-500': form.errors.city }"
                                    placeholder="Ex: Dakar"
                                    @input="debugFormData"
                                />
                                <div v-if="form.errors.city" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.city }}
                                </div>
                            </div>

                            <!-- Adresse -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    📍 Adresse complète <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    required
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-red-500 focus:ring-4 focus:ring-red-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    :class="{ 'border-red-500': form.errors.address }"
                                    placeholder="Ex: Parcelles Assainies, Unité 15"
                                    @input="debugFormData"
                                />
                                <div v-if="form.errors.address" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.address }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Caractéristiques -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-pink-500 to-rose-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">3</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Caractéristiques</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <!-- Superficie -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    📐 Superficie (m²) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model.number="form.superficy"
                                    type="number"
                                    required
                                    min="1"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-pink-500 focus:ring-4 focus:ring-pink-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    :class="{ 'border-red-500': form.errors.superficy }"
                                    placeholder="200"
                                    @input="debugFormData"
                                />
                                <div v-if="form.errors.superficy" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.superficy }}
                                </div>
                            </div>

                            <!-- Prix -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    💰 Prix (FCFA) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model.number="form.price"
                                    type="number"
                                    required
                                    min="1"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-pink-500 focus:ring-4 focus:ring-pink-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    :class="{ 'border-red-500': form.errors.price }"
                                    placeholder="50000000"
                                    @input="debugFormData"
                                />
                                <div v-if="form.errors.price" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.price }}
                                </div>
                            </div>

                            <!-- Chambres -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    🛏️ Nombre de chambres
                                </label>
                                <input
                                    v-model.number="form.rooms"
                                    type="number"
                                    min="0"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-pink-500 focus:ring-4 focus:ring-pink-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="3"
                                    @input="debugFormData"
                                />
                            </div>

                            <!-- Étages -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    🏢 Nombre d'étages
                                </label>
                                <input
                                    v-model.number="form.floors"
                                    type="number"
                                    min="0"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-pink-500 focus:ring-4 focus:ring-pink-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="2"
                                    @input="debugFormData"
                                />
                            </div>

                            <!-- Salles de bain -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    🚿 Salles de bain
                                </label>
                                <input
                                    v-model.number="form.bathrooms"
                                    type="number"
                                    min="0"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-pink-500 focus:ring-4 focus:ring-pink-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="2"
                                    @input="debugFormData"
                                />
                            </div>

                            <!-- Statut -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    📊 Statut <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.status"
                                    required
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-pink-500 focus:ring-4 focus:ring-pink-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm appearance-none cursor-pointer"
                                    :class="{ 'border-red-500': form.errors.status }"
                                    @change="debugFormData"
                                >
                                    <option value="" class="text-gray-500">-- Sélectionner --</option>
                                    <option v-for="status in statusOptions" :key="status.value" :value="status.value" class="text-gray-800">
                                        {{ status.label }}
                                    </option>
                                </select>
                                <div v-if="form.errors.status" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.status }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Catégorie -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-rose-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">4</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Catégorie</h2>
                        </div>

                        <div class="group max-w-md">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                🏷️ Type de bien <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model.number="form.categorie_id"
                                required
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-rose-500 focus:ring-4 focus:ring-rose-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm appearance-none cursor-pointer"
                                :class="{ 'border-red-500': form.errors.categorie_id }"
                                @change="debugFormData"
                            >
                                <option value="">-- Sélectionner une catégorie --</option>
                                <option v-for="categorie in categories" :key="categorie.id" :value="categorie.id" class="text-gray-800">
                                    {{ categorie.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.categorie_id" class="text-red-500 text-sm mt-1">
                                {{ form.errors.categorie_id }}
                            </div>
                        </div>
                    </div>

                    <!-- Section Image -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">5</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Image du Bien</h2>
                        </div>

                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                📸 Photo principale
                            </label>
                            <div class="relative">
                                <input
                                    type="file"
                                    @change="handleImageChange"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                    accept="image/*"
                                />
                                <div class="w-full h-64 border-2 border-dashed border-gray-300 rounded-xl hover:border-purple-400 transition-all duration-300 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center group-hover:from-purple-50 group-hover:to-indigo-50">
                                    <div v-if="!imagePreview" class="text-center">
                                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
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
                            <div class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">6</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Description</h2>
                        </div>

                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                📝 Description détaillée
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="6"
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm resize-none"
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
                            ✖️ Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-12 py-4 bg-gradient-to-r from-orange-600 via-red-600 to-pink-600 hover:from-orange-700 hover:via-red-700 hover:to-pink-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing" class="inline-block animate-spin mr-2">⭕</span>
                            ✨ Enregistrer
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
