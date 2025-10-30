<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-800 py-16">
            <div class="relative max-w-7xl mx-auto px-4 text-center">
                <h1 class="text-5xl font-extrabold text-white mb-4">
                    Ajouter un Bien Immobilier
                </h1>
                <p class="text-xl text-blue-100">
                    Créez votre bien avec plusieurs photos
                </p>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 -mt-8 relative z-10">
            <div class="bg-white/70 backdrop-blur-lg rounded-3xl shadow-2xl border border-white/20 overflow-hidden">
                <div class="h-2 bg-gradient-to-r from-blue-500 via-purple-500 to-indigo-600"></div>

                <form @submit.prevent="submit" class="p-12 space-y-10">
                    <!-- Section Photos Générales du Bien -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-teal-500 to-cyan-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">📸</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Photos Générales du Bien</h2>
                        </div>

                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Ajouter plusieurs photos <span class="text-red-500">*</span>
                            </label>

                            <div class="relative mb-4">
                                <input
                                    type="file"
                                    @change="handleImagesChange"
                                    ref="imageInput"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                    accept="image/*"
                                    multiple
                                />
                                <div class="w-full h-48 border-2 border-dashed border-gray-300 rounded-xl hover:border-teal-400 transition-all bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                                    <div class="text-center">
                                        <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <span class="text-3xl">📸</span>
                                        </div>
                                        <p class="text-gray-600 font-medium mb-2">Cliquez pour ajouter plusieurs photos</p>
                                        <p class="text-sm text-gray-500">PNG, JPG, WebP - Max 10MB par image</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ imagePreviews.length }} image(s) sélectionnée(s)</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="imagePreviews.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-4">
                                <div v-for="(preview, index) in imagePreviews" :key="index" class="relative group">
                                    <div class="relative aspect-square">
                                        <img :src="preview.url" class="w-full h-full object-cover rounded-lg border-2 border-gray-200" />
                                        <button
                                            type="button"
                                            @click="removeImage(index)"
                                            class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                    <input
                                        v-model="preview.label"
                                        type="text"
                                        class="mt-2 w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-lg focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                                        placeholder="Libellé (ex: Façade, Salon...)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Titre et Document -->
                    <div class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Titre du bien <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all bg-white/80"
                                    placeholder="Ex: Villa moderne avec piscine"
                                    required
                                />
                            </div>

                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Document commercial <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="file"
                                        @change="handlePropertyTitleChange"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                        accept=".pdf,.doc,.docx"
                                        required
                                    />
                                    <div class="w-full px-4 py-4 border-2 border-dashed border-gray-300 rounded-xl hover:border-blue-400 transition-all bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                                        <div class="text-center">
                                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                                <span class="text-2xl">📎</span>
                                            </div>
                                            <p class="text-sm text-gray-600 font-medium">
                                                {{ documentFileName || 'Cliquez pour ajouter' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Type de Mandat -->
                    <div class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Type de mandat <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.type_mandat"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all bg-white/80"
                                    required
                                    @change="onTypeMandatChange"
                                >
                                    <option value="">-- Sélectionner --</option>
                                    <option value="vente">Mandat de Vente</option>
                                    <option value="gestion_locative">Mandat de Gérance (Location)</option>
                                </select>
                            </div>

                            <div v-if="form.type_mandat === 'vente'" class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Type de mandat de vente <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.type_mandat_vente"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all bg-white/80"
                                    required
                                >
                                    <option value="">-- Sélectionner --</option>
                                    <option value="exclusif">Mandat Exclusif</option>
                                    <option value="simple">Mandat Simple</option>
                                    <option value="semi_exclusif">Mandat Semi-Exclusif</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Localisation -->
                    <div class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Ville <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.city"
                                    type="text"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all bg-white/80"
                                    required
                                />
                            </div>

                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Adresse <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all bg-white/80"
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Caractéristiques -->
                    <!-- Caractéristiques -->
                    <div class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Superficie (m²) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.superficy"
                                    type="number"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all bg-white/80"
                                    required
                                />
                            </div>

                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Prix (FCFA) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.price"
                                    type="number"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all bg-white/80"
                                    required
                                />
                            </div>

                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Catégorie <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.categorie_id"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-pink-500 focus:ring-4 focus:ring-pink-500/20 transition-all bg-white/80"
                                    required
                                    @change="onCategorieChange"
                                >
                                    <option value="">-- Sélectionner --</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>

                            <!-- Chambres -->
                            <div class="group relative">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Chambres
                                </label>
                                <input
                                    v-model="form.rooms"
                                    type="number"
                                    :disabled="estCategorieAppartement"
                                    :class="[
                    'w-full px-4 py-4 border-2 rounded-xl transition-all',
                    estCategorieAppartement
                        ? 'bg-gray-100 border-gray-300 text-gray-500 cursor-not-allowed'
                        : 'bg-white/80 border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20'
                ]"
                                    :title="estCategorieAppartement ? 'Calculé automatiquement (total des appartements)' : ''"
                                />
                                <div v-if="estCategorieAppartement" class="absolute right-3 top-9 text-gray-400">
                                    <i class="fas fa-lock text-sm"></i>
                                </div>
                            </div>

                            <!-- Étages -->
                            <div class="group relative">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Étages
                                </label>
                                <input
                                    v-model.number="form.floors"
                                    type="number"
                                    :disabled="estCategorieAppartement"
                                    :class="[
                    'w-full px-4 py-4 border-2 rounded-xl transition-all',
                    estCategorieAppartement
                        ? 'bg-gray-100 border-gray-300 text-gray-500 cursor-not-allowed'
                        : 'bg-white/80 border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20'
                ]"
                                    :title="estCategorieAppartement ? 'Calculé automatiquement (nombre d\'étages des appartements)' : ''"
                                    @input="!estCategorieAppartement && genererFormulairesAppartements()"
                                />
                                <div v-if="estCategorieAppartement" class="absolute right-3 top-9 text-gray-400">
                                    <i class="fas fa-lock text-sm"></i>
                                </div>
                            </div>

                            <!-- Salles de bain -->
                            <div class="group relative">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Salles de bain
                                </label>
                                <input
                                    v-model="form.bathrooms"
                                    type="number"
                                    :disabled="estCategorieAppartement"
                                    :class="[
                    'w-full px-4 py-4 border-2 rounded-xl transition-all',
                    estCategorieAppartement
                        ? 'bg-gray-100 border-gray-300 text-gray-500 cursor-not-allowed'
                        : 'bg-white/80 border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20'
                ]"
                                    :title="estCategorieAppartement ? 'Calculé automatiquement (total des appartements)' : ''"
                                />
                                <div v-if="estCategorieAppartement" class="absolute right-3 top-9 text-gray-400">
                                    <i class="fas fa-lock text-sm"></i>
                                </div>
                            </div>

                            <!-- Cuisines -->
                            <div class="group relative">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Cuisines
                                </label>
                                <input
                                    v-model="form.kitchens"
                                    type="number"
                                    :disabled="estCategorieAppartement"
                                    :class="[
                    'w-full px-4 py-4 border-2 rounded-xl transition-all',
                    estCategorieAppartement
                        ? 'bg-gray-100 border-gray-300 text-gray-500 cursor-not-allowed'
                        : 'bg-white/80 border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20'
                ]"
                                    :title="estCategorieAppartement ? 'Calculé automatiquement (total des appartements)' : ''"
                                />
                                <div v-if="estCategorieAppartement" class="absolute right-3 top-9 text-gray-400">
                                    <i class="fas fa-lock text-sm"></i>
                                </div>
                            </div>

                            <!-- Salons -->
                            <div class="group relative">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Salons
                                </label>
                                <input
                                    v-model="form.living_rooms"
                                    type="number"
                                    :disabled="estCategorieAppartement"
                                    :class="[
                    'w-full px-4 py-4 border-2 rounded-xl transition-all',
                    estCategorieAppartement
                        ? 'bg-gray-100 border-gray-300 text-gray-500 cursor-not-allowed'
                        : 'bg-white/80 border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20'
                ]"
                                    :title="estCategorieAppartement ? 'Calculé automatiquement (total des appartements)' : ''"
                                />
                                <div v-if="estCategorieAppartement" class="absolute right-3 top-9 text-gray-400">
                                    <i class="fas fa-lock text-sm"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ✅ FORMULAIRES DES APPARTEMENTS AVEC IMAGES -->
                    <div v-if="afficherFormulairesAppartements" class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">🏢</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Informations des Appartements</h2>
                        </div>

                        <div v-for="(appartement, index) in form.appartements" :key="index" class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-6 border-2 border-indigo-200">
                            <h3 class="text-lg font-bold text-indigo-800 mb-4">
                                {{ getEtageLabel(index) }} - {{ appartement.numero }}
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Superficie (m²)
                                    </label>
                                    <input
                                        v-model.number="appartement.superficie"
                                        type="number"
                                        step="0.01"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-white"
                                        placeholder="Ex: 75.5"
                                    />
                                </div>

                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Nombre de salons
                                    </label>
                                    <input
                                        v-model.number="appartement.salons"
                                        type="number"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-white"
                                        placeholder="Ex: 1"
                                    />
                                </div>

                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Chambres
                                    </label>
                                    <input
                                        v-model.number="appartement.chambres"
                                        type="number"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-white"
                                        placeholder="Ex: 2"
                                    />
                                </div>

                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Salles de bain
                                    </label>
                                    <input
                                        v-model.number="appartement.salles_bain"
                                        type="number"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-white"
                                        placeholder="Ex: 1"
                                    />
                                </div>

                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Cuisines
                                    </label>
                                    <input
                                        v-model.number="appartement.cuisines"
                                        type="number"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-white"
                                        placeholder="Ex: 1"
                                    />
                                </div>

                                <div class="group md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        v-model="appartement.description"
                                        rows="2"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-white resize-none"
                                        :placeholder="'Description pour ' + getEtageLabel(index)"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- 📸 SECTION IMAGES DE L'APPARTEMENT -->
                            <div class="mt-6 pt-6 border-t border-indigo-200">
                                <label class="block text-sm font-semibold text-gray-700 mb-3">
                                    Photos de l'appartement
                                </label>

                                <div class="relative mb-4">
                                    <input
                                        type="file"
                                        @change="(e) => handleAppartementImagesChange(e, index)"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                        accept="image/*"
                                        multiple
                                    />
                                    <div class="w-full h-32 border-2 border-dashed border-indigo-300 rounded-lg hover:border-indigo-500 transition-all bg-white flex items-center justify-center">
                                        <div class="text-center">
                                            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                                <span class="text-2xl">🖼️</span>
                                            </div>
                                            <p class="text-sm text-gray-600 font-medium">
                                                Ajouter des photos pour cet appartement
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ appartement.images.length }} image(s)
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Aperçu des images de l'appartement -->
                                <div v-if="appartement.images.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div v-for="(img, imgIndex) in appartement.images" :key="imgIndex" class="relative group">
                                        <div class="relative aspect-square">
                                            <img :src="img.url" class="w-full h-full object-cover rounded-lg border-2 border-indigo-200" />
                                            <button
                                                type="button"
                                                @click="removeAppartementImage(index, imgIndex)"
                                                class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-xs"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                        <input
                                            v-model="img.label"
                                            type="text"
                                            class="mt-1 w-full px-2 py-1 text-xs border border-gray-300 rounded focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/20"
                                            placeholder="Libellé"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-8">
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Description
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="6"
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all bg-white/80 resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Boutons -->
                    <div class="flex justify-center space-x-6 pt-8 border-t">
                        <button
                            type="button"
                            @click="cancel"
                            class="px-8 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-all"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing || imagePreviews.length === 0"
                            class="px-12 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-semibold transition-all shadow-lg disabled:opacity-50"
                        >
                            {{ form.processing ? 'Traitement...' : 'Créer le Bien' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { route } from 'ziggy-js'
import { watch } from 'vue'

const props = defineProps({
    categories: Array,
})

const form = useForm({
    title: '',
    property_title: null,
    description: '',
    images: [],
    images_labels: [],
    rooms: '',
    floors: '',
    bathrooms: '',
    kitchens: '',
    living_rooms: '',
    city: '',
    address: '',
    superficy: '',
    price: '',
    categorie_id: '',
    type_mandat: '',
    type_mandat_vente: '',
    conditions_particulieres: '',
    appartements: [],
    // ✅ Nouvelles données pour les images des appartements
    appartements_images: [],
    appartements_images_labels: []
})

const categorieSelectionnee = computed(() => {
    return props.categories.find(cat => cat.id === form.categorie_id)
})

const estCategorieAppartement = computed(() => {
    return form.categorie_id === 5 // ID de la catégorie Appartement
})

const estCategorieMaisonOuStudio = computed(() => {
    return form.categorie_id === 4 || form.categorie_id === 10 // ID Maison ou Studio
})

// Fonction pour calculer les totaux des appartements
const calculerTotauxAppartements = () => {
    if (!estCategorieAppartement.value || form.appartements.length === 0) {
        return
    }

    // ✅ Chambres : somme des chambres de tous les appartements
    form.rooms = form.appartements.reduce((total, apt) => {
        return total + (parseInt(apt.chambres) || 0)
    }, 0)

    // ✅ Salles de bain : somme des salles de bain
    form.bathrooms = form.appartements.reduce((total, apt) => {
        return total + (parseInt(apt.salles_bain) || 0)
    }, 0)

    // ✅ Cuisines : 1 par appartement
    form.kitchens = form.appartements.length

    // ✅ Salons : somme des salons (pieces devient salons)
    form.living_rooms = form.appartements.reduce((total, apt) => {
        return total + (parseInt(apt.salons) || 0)  // ✅ CHANGÉ de 'pieces' à 'salons'
    }, 0)

    // ✅ Étages : max des étages
    const maxEtage = Math.max(...form.appartements.map(apt => apt.etage))
    form.floors = maxEtage
}

const onCategorieChange = () => {
    if (estCategorieAppartement.value) {
        // Pour appartement, générer les formulaires
        if (!form.floors || form.floors < 1) {
            alert('Veuillez d\'abord saisir le nombre d\'étages pour générer les appartements')
            return
        }
        genererFormulairesAppartements()
    } else if (estCategorieMaisonOuStudio.value) {
        // Pour maison ou studio, réactiver tous les champs
        afficherFormulairesAppartements.value = false
        form.appartements = []
        // Réinitialiser si nécessaire
        if (form.rooms === 0) form.rooms = ''
        if (form.bathrooms === 0) form.bathrooms = ''
        if (form.kitchens === 0) form.kitchens = ''
        if (form.living_rooms === 0) form.living_rooms = ''
    } else {
        // Autre catégorie
        afficherFormulairesAppartements.value = false
        form.appartements = []
    }
}

const genererFormulairesAppartements = () => {
    if (!estCategorieAppartement.value || !form.floors || form.floors < 1) {
        afficherFormulairesAppartements.value = false
        form.appartements = []
        return
    }

    afficherFormulairesAppartements.value = true

    const nombreAppartements = parseInt(form.floors) + 1
    form.appartements = []

    for (let i = 0; i < nombreAppartements; i++) {
        form.appartements.push({
            numero: `APT-${String(i + 1).padStart(3, '0')}`,
            etage: i,
            superficie: form.superficy ? (form.superficy / nombreAppartements).toFixed(2) : '',
            salons: 1,          // ✅ CHANGÉ de 'pieces' à 'salons'
            chambres: 2,
            salles_bain: 1,
            cuisines: 1,        // ✅ AJOUTÉ
            description: getEtageLabel(i),
            images: []
        })
    }

    calculerTotauxAppartements()
}

watch(
    () => form.appartements,
    () => {
        if (estCategorieAppartement.value) {
            calculerTotauxAppartements()
        }
    },
    { deep: true }
)

const imagePreviews = ref([])
const documentFileName = ref(null)
const afficherFormulairesAppartements = ref(false)


const handlePropertyTitleChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        form.property_title = file
        documentFileName.value = file.name
    }
}

const handleImagesChange = (e) => {
    const files = Array.from(e.target.files)

    files.forEach(file => {
        if (file.size > 10240 * 1024) {
            alert(`L'image ${file.name} dépasse 10MB`)
            return
        }

        form.images.push(file)
        imagePreviews.value.push({
            url: URL.createObjectURL(file),
            label: ''
        })
    })
}

const removeImage = (index) => {
    URL.revokeObjectURL(imagePreviews.value[index].url)
    imagePreviews.value.splice(index, 1)
    form.images.splice(index, 1)
}

// ✅ Gestion des images des appartements
const handleAppartementImagesChange = (e, appartementIndex) => {
    const files = Array.from(e.target.files)

    files.forEach(file => {
        if (file.size > 10240 * 1024) {
            alert(`L'image ${file.name} dépasse 10MB`)
            return
        }

        // Ajouter l'image avec preview
        form.appartements[appartementIndex].images.push({
            file: file,
            url: URL.createObjectURL(file),
            label: ''
        })
    })
}

const removeAppartementImage = (appartementIndex, imageIndex) => {
    const img = form.appartements[appartementIndex].images[imageIndex]
    URL.revokeObjectURL(img.url)
    form.appartements[appartementIndex].images.splice(imageIndex, 1)
}

const onTypeMandatChange = () => {
    if (form.type_mandat !== 'vente') {
        form.type_mandat_vente = ''
    }
}


const getEtageLabel = (etage) => {
    const labels = {
        0: 'Rez-de-chaussée',
        1: '1er étage',
        2: '2ème étage',
        3: '3ème étage'
    }
    return labels[etage] || `${etage}ème étage`
}

const submit = () => {
    if (imagePreviews.value.length === 0) {
        alert('Veuillez ajouter au moins une image')
        return
    }

    // Labels des images générales
    form.images_labels = imagePreviews.value.map(p => p.label || '')

    // ✅ Préparer les images des appartements pour l'envoi
    if (afficherFormulairesAppartements.value) {
        form.appartements_images = []
        form.appartements_images_labels = []

        form.appartements.forEach((appartement, index) => {
            appartement.images.forEach(img => {
                form.appartements_images.push({
                    file: img.file,
                    appartement_index: index
                })
                form.appartements_images_labels.push({
                    label: img.label || '',
                    appartement_index: index
                })
            })
        })
    }

    form.post(route('biens.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            imagePreviews.value.forEach(p => URL.revokeObjectURL(p.url))

            // Nettoyer les URLs des images des appartements
            if (afficherFormulairesAppartements.value) {
                form.appartements.forEach(apt => {
                    apt.images.forEach(img => URL.revokeObjectURL(img.url))
                })
            }
        }
    })
}

const cancel = () => {
    imagePreviews.value.forEach(p => URL.revokeObjectURL(p.url))

    // Nettoyer les URLs des images des appartements
    if (afficherFormulairesAppartements.value) {
        form.appartements.forEach(apt => {
            apt.images.forEach(img => URL.revokeObjectURL(img.url))
        })
    }

    router.visit(route('biens.index'))
}
</script>
