<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
        <!-- Header avec animation -->
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-800 py-16">
            <div class="absolute inset-0 bg-black/20"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-purple-300/20 rounded-full blur-3xl"></div>

            <div class="relative max-w-7xl mx-auto px-4 text-center">
                <h1 class="text-5xl font-extrabold text-white mb-4 tracking-tight">
                    Modifier le Bien Immobilier
                </h1>
                <p class="text-xl text-blue-100 max-w-2xl mx-auto">
                    Modifiez les informations de votre bien et de son mandat
                </p>
                <div class="mt-4 inline-flex items-center px-4 py-2 bg-white/20 rounded-full text-white font-semibold">
                    <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                    Bien #{{ bien.id }}
                </div>
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
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                                    <span class="text-white font-bold">1</span>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-800">Informations Principales</h2>
                            </div>
                            <div class="text-sm text-gray-500">
                                Créé le {{ formatDate(bien.created_at) }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Titre -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Titre du bien <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="Ex: Villa moderne avec piscine"
                                    required
                                />
                                <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</div>
                            </div>

                            <!-- Document actuel/nouveau -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Document commercial
                                </label>

                                <!-- Affichage du document existant -->
                                <div v-if="bien.property_title && !documentFileName" class="mb-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-green-600">📎</span>
                                            <span class="text-sm font-medium text-green-800">Document actuel</span>
                                        </div>
                                        <a :href="`/storage/${bien.property_title}`"
                                           target="_blank"
                                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            Voir le document
                                        </a>
                                    </div>
                                </div>

                                <!-- Zone d'upload pour nouveau document -->
                                <div class="relative">
                                    <input
                                        type="file"
                                        @change="handlePropertyTitleChange"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                        accept=".pdf,.doc,.docx"
                                    />
                                    <div class="w-full px-4 py-4 border-2 border-dashed border-gray-300 rounded-xl hover:border-blue-400 transition-all duration-300 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center group-hover:from-blue-50 group-hover:to-purple-50">
                                        <div class="text-center">
                                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                                <span class="text-2xl">📎</span>
                                            </div>
                                            <p class="text-sm text-gray-600 font-medium">
                                                {{ documentFileName || 'Cliquez pour remplacer le document' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="form.errors.property_title" class="text-red-500 text-sm mt-1">{{ form.errors.property_title }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Type de Mandat -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-teal-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">2</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Type de Mandat</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Type de mandat -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Type de mandat <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.type_mandat"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm appearance-none cursor-pointer"
                                    required
                                    @change="onTypeMandatChange"
                                >
                                    <option value="">-- Sélectionner un type de mandat --</option>
                                    <option v-for="type in typeMandatOptions" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </option>
                                </select>
                                <div v-if="form.errors.type_mandat" class="text-red-500 text-sm mt-1">{{ form.errors.type_mandat }}</div>
                            </div>

                            <!-- Type de mandat de vente (conditionnel) -->
                            <div v-if="form.type_mandat === 'vente'" class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Type de mandat de vente <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <select
                                        v-model="form.type_mandat_vente"
                                        class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm appearance-none cursor-pointer"
                                        required
                                    >
                                        <option value="">-- Sélectionner un type de mandat de vente --</option>
                                        <option
                                            v-for="type in typeMandatVenteOptions"
                                            :key="type.value"
                                            :value="type.value"
                                            :title="type.description"
                                        >
                                            {{ type.label }}
                                        </option>
                                    </select>

                                    <!-- Tooltip d'information -->
                                    <div
                                        v-if="form.type_mandat_vente"
                                        class="mt-3 p-4 bg-blue-50 border-l-4 border-blue-400 rounded-lg"
                                    >
                                        <h4 class="font-semibold text-blue-800 mb-2">
                                            {{ getSelectedMandatLabel() }}
                                        </h4>
                                        <p class="text-sm text-blue-700 leading-relaxed">
                                            {{ getSelectedMandatDescription() }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="form.errors.type_mandat_vente" class="text-red-500 text-sm mt-1">{{ form.errors.type_mandat_vente }}</div>
                            </div>

                            <!-- Conditions particulières -->
                            <div class="group" :class="form.type_mandat === 'vente' ? 'md:col-span-2' : 'md:col-span-1'">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Conditions particulières
                                </label>
                                <textarea
                                    v-model="form.conditions_particulieres"
                                    rows="4"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm resize-none"
                                    placeholder="Précisez les conditions spéciales du mandat..."
                                ></textarea>
                                <div v-if="form.errors.conditions_particulieres" class="text-red-500 text-sm mt-1">{{ form.errors.conditions_particulieres }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Localisation -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">3</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Localisation</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Ville -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Ville <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.city"
                                    type="text"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="Ex: Dakar"
                                    required
                                />
                                <div v-if="form.errors.city" class="text-red-500 text-sm mt-1">{{ form.errors.city }}</div>
                            </div>

                            <!-- Adresse -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Adresse complète <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="Ex: Parcelles Assainies, Unité 15"
                                    required
                                />
                                <div v-if="form.errors.address" class="text-red-500 text-sm mt-1">{{ form.errors.address }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Caractéristiques -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">4</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Caractéristiques</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <!-- Superficie -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Superficie (m²) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.superficy"
                                    type="number"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="200"
                                    required
                                />
                                <div v-if="form.errors.superficy" class="text-red-500 text-sm mt-1">{{ form.errors.superficy }}</div>
                            </div>

                            <!-- Prix -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Prix (FCFA) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.price"
                                    type="number"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="50000000"
                                    required
                                />
                                <div v-if="form.errors.price" class="text-red-500 text-sm mt-1">{{ form.errors.price }}</div>
                            </div>

                            <!-- Chambres -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Nombre de chambres
                                </label>
                                <input
                                    v-model="form.rooms"
                                    type="number"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="3"
                                />
                                <div v-if="form.errors.rooms" class="text-red-500 text-sm mt-1">{{ form.errors.rooms }}</div>
                            </div>

                            <!-- Étages -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Nombre d'étages
                                </label>
                                <input
                                    v-model="form.floors"
                                    type="number"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="2"
                                />
                                <div v-if="form.errors.floors" class="text-red-500 text-sm mt-1">{{ form.errors.floors }}</div>
                            </div>

                            <!-- Salles de bain -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Salles de bain
                                </label>
                                <input
                                    v-model="form.bathrooms"
                                    type="number"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm"
                                    placeholder="2"
                                />
                                <div v-if="form.errors.bathrooms" class="text-red-500 text-sm mt-1">{{ form.errors.bathrooms }}</div>
                            </div>

                            <!-- Statut du bien -->
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Statut du bien <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.status"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm appearance-none cursor-pointer"
                                    required
                                >
                                    <option value="disponible">Disponible</option>
                                    <option value="reserve">Réservé</option>
                                    <option value="vendu">Vendu</option>
                                    <option value="loue">Loué</option>
                                </select>
                                <div v-if="form.errors.status" class="text-red-500 text-sm mt-1">{{ form.errors.status }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Catégorie -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-pink-500 to-rose-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">5</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Catégorie</h2>
                        </div>

                        <div class="group max-w-md">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Type de bien <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.categorie_id"
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-pink-500 focus:ring-4 focus:ring-pink-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm appearance-none cursor-pointer"
                                required
                            >
                                <option value="" class="text-gray-500">-- Sélectionner une catégorie --</option>
                                <option v-for="categorie in categories" :key="categorie.id" :value="categorie.id" class="text-gray-800">
                                    {{ categorie.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.categorie_id" class="text-red-500 text-sm mt-1">{{ form.errors.categorie_id }}</div>
                        </div>
                    </div>

                    <!-- Section Image -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-teal-500 to-cyan-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">6</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Image du Bien</h2>
                        </div>

                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Photo principale
                            </label>

                            <!-- Image actuelle -->
                            <div v-if="!imagePreview && bien.image" class="mb-4">
                                <div class="relative w-32 h-32 rounded-lg overflow-hidden border-2 border-gray-200">
                                    <img :src="getCurrentImageUrl()" alt="Image actuelle" class="w-full h-full object-cover" />
                                    <div class="absolute inset-0 bg-black/40 opacity-0 hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                        <p class="text-white text-xs font-semibold">Image actuelle</p>
                                    </div>
                                </div>
                            </div>

                            <div class="relative">
                                <input
                                    type="file"
                                    @change="handleImageChange"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                    accept="image/*"
                                />
                                <div class="w-full h-64 border-2 border-dashed border-gray-300 rounded-xl hover:border-teal-400 transition-all duration-300 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center group-hover:from-teal-50 group-hover:to-cyan-50">
                                    <div v-if="!imagePreview" class="text-center">
                                        <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <span class="text-3xl">📸</span>
                                        </div>
                                        <p class="text-gray-600 font-medium mb-2">Cliquez pour changer la photo</p>
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
                            <div v-if="form.errors.image" class="text-red-500 text-sm mt-1">{{ form.errors.image }}</div>
                        </div>
                    </div>

                    <!-- Section Description -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">7</span>
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
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all duration-300 group-hover:border-gray-300 bg-white/80 backdrop-blur-sm resize-none"
                                placeholder="Décrivez votre bien immobilier en détail : équipements, points forts, environnement..."
                            ></textarea>
                            <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</div>
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
                            :disabled="form.processing"
                            class="px-12 py-4 bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 hover:from-blue-700 hover:via-purple-700 hover:to-indigo-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl disabled:opacity-50"
                        >
                            <span v-if="form.processing">Mise à jour...</span>
                            <span v-else>Mettre à jour le Bien</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Espacement en bas -->
        <div class="h-20"></div>
    </div>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { route } from 'ziggy-js'

const props = defineProps({
    categories: Array,
    bien: Object,
    mandat: Object
})

const form = useForm({
    // Données du bien
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
    categorie_id: '',
    status: '',

    // Données du mandat
    type_mandat: '',
    type_mandat_vente: '',
    conditions_particulieres: ''
})

const imagePreview = ref(null)
const documentFileName = ref(null)

const typeMandatOptions = [
    { value: 'vente', label: 'Mandat de Vente' },
    { value: 'gestion_locative', label: 'Mandat de Gérance (Location)' },
]

const typeMandatVenteOptions = [
    {
        value: 'exclusif',
        label: 'Mandat Exclusif',
        description: 'Le propriétaire confie la vente uniquement à une seule agence. Il ne peut ni vendre par lui-même, ni mandater une autre agence. L\'agence a l\'assurance d\'être rémunérée si la vente se réalise pendant la durée du mandat. Avantage : plus d\'implication et d\'efforts marketing de l\'agence.'
    },
    {
        value: 'simple',
        label: 'Mandat Simple',
        description: 'Le propriétaire peut confier le bien à plusieurs agences et vendre également par ses propres moyens. L\'agence qui réalise la vente perçoit la commission. Avantage : plus de visibilité, mais moins d\'engagement des agences.'
    },
    {
        value: 'semi_exclusif',
        label: 'Mandat Semi-Exclusif',
        description: 'Le propriétaire confie son bien à une seule agence mais garde la possibilité de vendre par lui-même. L\'agence est rémunérée uniquement si elle trouve l\'acquéreur. C\'est un compromis entre exclusif et simple.'
    }
]

// Initialisation des données au montage
onMounted(() => {
    // Pré-remplir le formulaire avec les données du bien existant
    form.title = props.bien.title || ''
    form.description = props.bien.description || ''
    form.rooms = props.bien.rooms || ''
    form.floors = props.bien.floors || ''
    form.bathrooms = props.bien.bathrooms || ''
    form.city = props.bien.city || ''
    form.address = props.bien.address || ''
    form.superficy = props.bien.superficy || ''
    form.price = props.bien.price || ''
    form.categorie_id = props.bien.categorie_id || ''
    form.status = props.bien.status || 'disponible'

    // Pré-remplir les données du mandat si elles existent
    if (props.mandat) {
        form.type_mandat = props.mandat.type_mandat || ''
        form.type_mandat_vente = props.mandat.type_mandat_vente || ''
        form.conditions_particulieres = props.mandat.conditions_particulieres || ''
    }
})

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

const onTypeMandatChange = () => {
    // Réinitialiser le type de mandat de vente quand on change le type de mandat
    if (form.type_mandat !== 'vente') {
        form.type_mandat_vente = ''
    }
}

const getSelectedMandatLabel = () => {
    const selected = typeMandatVenteOptions.find(option => option.value === form.type_mandat_vente)
    return selected ? selected.label : ''
}

const getSelectedMandatDescription = () => {
    const selected = typeMandatVenteOptions.find(option => option.value === form.type_mandat_vente)
    return selected ? selected.description : ''
}

const getCurrentImageUrl = () => {
    if (props.bien.image) {
        return props.bien.image.startsWith('/') ? props.bien.image : `/storage/${props.bien.image}`
    }
    return '/images/placeholder-house.jpg'
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const submit = () => {
    form.put(route('biens.update', props.bien.id), {
        forceFormData: true,
        preserveScroll: true
    })
}

const cancel = () => {
    router.visit(route('biens.show', props.bien.id))
}

</script>
