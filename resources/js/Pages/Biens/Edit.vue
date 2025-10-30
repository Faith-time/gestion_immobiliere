<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-800 py-16">
            <div class="relative max-w-7xl mx-auto px-4 text-center">
                <h1 class="text-5xl font-extrabold text-white mb-4">
                    Modifier le Bien
                </h1>
                <p class="text-xl text-blue-100">
                    Modifiez les informations de votre bien immobilier
                </p>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 -mt-8 relative z-10">
            <div class="bg-white/70 backdrop-blur-lg rounded-3xl shadow-2xl border border-white/20 overflow-hidden">
                <div class="h-2 bg-gradient-to-r from-blue-500 via-purple-500 to-indigo-600"></div>

                <form @submit.prevent="submit" class="p-12 space-y-10">
                    <!-- Section Photos Générales -->
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-teal-500 to-cyan-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">📸</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Photos Générales du Bien</h2>
                        </div>

                        <!-- Images existantes -->
                        <div v-if="existingImages.length > 0">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">
                                Images actuelles
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
                                <div v-for="image in existingImages" :key="image.id" class="relative group">
                                    <div class="relative aspect-square">
                                        <img :src="`/storage/${image.chemin_image}`" class="w-full h-full object-cover rounded-lg border-2" :class="form.deleted_images.includes(image.id) ? 'border-red-500 opacity-50' : 'border-gray-200'" />
                                        <button
                                            type="button"
                                            @click="toggleDeleteImage(image.id)"
                                            class="absolute top-2 right-2 rounded-full w-8 h-8 flex items-center justify-center transition-all shadow-lg"
                                            :class="form.deleted_images.includes(image.id) ? 'bg-green-500 hover:bg-green-600 text-white' : 'bg-red-500 hover:bg-red-600 text-white opacity-0 group-hover:opacity-100'"
                                        >
                                            {{ form.deleted_images.includes(image.id) ? '↺' : '✕' }}
                                        </button>
                                        <div class="absolute bottom-2 left-2 right-2 bg-black/50 text-white text-xs px-2 py-1 rounded">
                                            {{ image.libelle || 'Image ' + image.id }}
                                        </div>
                                        <div v-if="form.deleted_images.includes(image.id)" class="absolute inset-0 flex items-center justify-center bg-red-500/20 rounded-lg">
                                            <span class="text-red-600 font-bold bg-white px-3 py-1 rounded">À SUPPRIMER</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ajouter nouvelles images -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Ajouter de nouvelles photos générales
                            </label>
                            <div class="relative mb-4">
                                <input
                                    type="file"
                                    @change="handleNewImagesChange"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                    accept="image/*"
                                    multiple
                                />
                                <div class="w-full h-48 border-2 border-dashed border-gray-300 rounded-xl hover:border-teal-400 transition-all bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                                    <div class="text-center">
                                        <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <span class="text-3xl">📸</span>
                                        </div>
                                        <p class="text-gray-600 font-medium mb-2">Cliquez pour ajouter de nouvelles photos</p>
                                        <p class="text-sm text-gray-500">PNG, JPG, WebP - Max 10MB par image</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ newImagePreviews.length }} nouvelle(s) image(s)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Prévisualisation nouvelles images -->
                            <div v-if="newImagePreviews.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <div v-for="(preview, index) in newImagePreviews" :key="'new-' + index" class="relative group">
                                    <div class="relative aspect-square">
                                        <img :src="preview.url" class="w-full h-full object-cover rounded-lg border-2 border-green-400" />
                                        <button
                                            type="button"
                                            @click="removeNewImage(index)"
                                            class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg"
                                        >
                                            ✕
                                        </button>
                                        <div class="absolute bottom-2 left-2 right-2 bg-green-500/80 text-white text-xs px-2 py-1 rounded">
                                            NOUVELLE
                                        </div>
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

                    <!-- Titre et Document -->
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
                                    Document commercial
                                </label>
                                <div v-if="bien.property_title && !documentFileName" class="mb-2 text-sm text-green-600">
                                    ✓ Document actuel disponible
                                </div>
                                <div class="relative">
                                    <input
                                        type="file"
                                        @change="handlePropertyTitleChange"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                        accept=".pdf,.doc,.docx"
                                    />
                                    <div class="w-full px-4 py-4 border-2 border-dashed border-gray-300 rounded-xl hover:border-blue-400 transition-all bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                                        <div class="text-center">
                                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                                <span class="text-2xl">📎</span>
                                            </div>
                                            <p class="text-sm text-gray-600 font-medium">
                                                {{ documentFileName || 'Remplacer le document' }}
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
                    <div class="space-y-8">
                        <!-- Alerte mode Appartement -->
                        <div v-if="estCategorieAppartement" class="bg-blue-50 border-l-4 border-blue-500 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Mode Appartement activé :</strong> Les champs chambres, étages, salles de bain, cuisines et salons sont calculés automatiquement.
                                    </p>
                                </div>
                            </div>
                        </div>

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
                                    :title="estCategorieAppartement ? 'Calculé automatiquement' : ''"
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
                                    :title="estCategorieAppartement ? 'Calculé automatiquement' : ''"
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
                                    :title="estCategorieAppartement ? 'Calculé automatiquement' : ''"
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
                                    :title="estCategorieAppartement ? 'Calculé automatiquement' : ''"
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
                                    :title="estCategorieAppartement ? 'Calculé automatiquement' : ''"
                                />
                                <div v-if="estCategorieAppartement" class="absolute right-3 top-9 text-gray-400">
                                    <i class="fas fa-lock text-sm"></i>
                                </div>
                            </div>

                            <div class="group">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Statut
                                </label>
                                <select
                                    v-model="form.status"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all bg-white/80"
                                >
                                    <option value="disponible">Disponible</option>
                                    <option value="reserve">Réservé</option>
                                    <option value="vendu">Vendu</option>
                                    <option value="loue">Loué</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaires Appartements -->
                    <div v-if="afficherFormulairesAppartements" class="space-y-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">🏢</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Informations des Appartements</h2>
                        </div>

                        <div v-for="(appartement, index) in form.appartements" :key="index" class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-6 border-2 border-indigo-200">
                            <h3 class="text-lg font-bold text-indigo-800 mb-4">
                                {{ getEtageLabel(appartement.etage) }} - {{ appartement.numero }}
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
                                        :placeholder="'Description pour ' + getEtageLabel(appartement.etage)"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Images de l'appartement -->
                            <div class="mt-6 pt-6 border-t border-indigo-200">
                                <label class="block text-sm font-semibold text-gray-700 mb-3">
                                    Photos de l'appartement
                                </label>

                                <!-- Images existantes -->
                                <div v-if="appartement.existing_images && appartement.existing_images.length > 0" class="mb-4">
                                    <p class="text-xs text-gray-600 mb-2">Images actuelles</p>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <div v-for="image in appartement.existing_images" :key="image.id" class="relative group">
                                            <div class="relative aspect-square">
                                                <img :src="`/storage/${image.chemin_image}`" class="w-full h-full object-cover rounded-lg border-2" :class="appartement.deleted_images.includes(image.id) ? 'border-red-500 opacity-50' : 'border-indigo-200'" />
                                                <button
                                                    type="button"
                                                    @click="toggleDeleteAppartementImage(index, image.id)"
                                                    class="absolute top-1 right-1 rounded-full w-6 h-6 flex items-center justify-center transition-all text-xs shadow-lg"
                                                    :class="appartement.deleted_images.includes(image.id) ? 'bg-green-500 hover:bg-green-600 text-white' : 'bg-red-500 hover:bg-red-600 text-white opacity-0 group-hover:opacity-100'"
                                                >
                                                    {{ appartement.deleted_images.includes(image.id) ? '↺' : '✕' }}
                                                </button>
                                                <div v-if="appartement.deleted_images.includes(image.id)" class="absolute inset-0 flex items-center justify-center bg-red-500/20 rounded-lg">
                                                    <span class="text-red-600 text-xs font-bold bg-white px-2 py-1 rounded">SUPPR</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ajouter nouvelles images -->
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
                                                Ajouter des photos
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ appartement.new_images.length }} nouvelle(s)
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nouvelles images -->
                                <div v-if="appartement.new_images.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div v-for="(img, imgIndex) in appartement.new_images" :key="imgIndex" class="relative group">
                                        <div class="relative aspect-square">
                                            <img :src="img.url" class="w-full h-full object-cover rounded-lg border-2 border-green-400" />
                                            <button
                                                type="button"
                                                @click="removeNewAppartementImage(index, imgIndex)"
                                                class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-xs"
                                            >
                                                ✕
                                            </button>
                                            <div class="absolute bottom-1 left-1 right-1 bg-green-500/80 text-white text-xs px-1 py-0.5 rounded text-center">
                                                NOUVELLE
                                            </div>
                                        </div>
                                        <input
                                            v-model="img.label"
                                            type="text"
                                            class="mt-1 w-full px-2 py-1 text-xs border border-gray-300 rounded focus:border-indigo-500"
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

                    <!-- Conditions particulières -->
                    <div class="space-y-8">
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Conditions particulières du mandat
                            </label>
                            <textarea
                                v-model="form.conditions_particulieres"
                                rows="4"
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all bg-white/80 resize-none"
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
                            :disabled="form.processing"
                            class="px-12 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-semibold transition-all shadow-lg disabled:opacity-50"
                        >
                            {{ form.processing ? 'Mise à jour...' : 'Mettre à jour' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import { route } from 'ziggy-js'

const props = defineProps({
    bien: Object,
    categories: Array,
})

const form = useForm({
    title: props.bien?.title || '',
    property_title: null,
    description: props.bien?.description || '',
    images: [],
    images_labels: [],
    deleted_images: [],
    rooms: Number(props.bien?.rooms) || 0,
    floors: Number(props.bien?.floors) || 0,
    bathrooms: Number(props.bien?.bathrooms) || 0,
    kitchens: Number(props.bien?.kitchens) || 0,
    living_rooms: Number(props.bien?.living_rooms) || 0,
    city: props.bien?.city || '',
    address: props.bien?.address || '',
    superficy: Number(props.bien?.superficy) || 0,
    price: Number(props.bien?.price) || 0,
    categorie_id: Number(props.bien?.categorie_id) || null,
    status: props.bien?.status || 'disponible',
    type_mandat: props.bien?.mandat?.type_mandat || '',
    type_mandat_vente: props.bien?.mandat?.type_mandat_vente || '',
    conditions_particulieres: props.bien?.mandat?.conditions_particulieres || '',
    appartements: [],
    appartements_images: [],
    appartements_images_labels: [],
    appartements_deleted_images: []
})

const existingImages = ref(props.bien?.images?.filter(img => !img.appartement_id) || [])
const newImagePreviews = ref([])
const documentFileName = ref(null)
const afficherFormulairesAppartements = ref(false)

const categorieSelectionnee = computed(() => {
    return props.categories.find(cat => cat.id === form.categorie_id)
})

const estCategorieAppartement = computed(() => {
    return form.categorie_id === 5
})

const estCategorieMaisonOuStudio = computed(() => {
    return form.categorie_id === 4 || form.categorie_id === 10
})

if (props.bien?.appartements && props.bien.appartements.length > 0) {
    afficherFormulairesAppartements.value = true
    form.appartements = props.bien.appartements.map(apt => ({
        id: apt.id,
        numero: apt.numero,
        etage: apt.etage,
        superficie: apt.superficie || '',
        pieces: apt.pieces || '',
        chambres: apt.chambres || '',
        salles_bain: apt.salles_bain || '',
        description: apt.description || '',
        existing_images: props.bien.images?.filter(img => img.appartement_id === apt.id) || [],
        new_images: [],
        deleted_images: []
    }))
}

const calculerTotauxAppartements = () => {
    if (!estCategorieAppartement.value || form.appartements.length === 0) {
        return
    }

    form.rooms = form.appartements.reduce((total, apt) => {
        return total + (parseInt(apt.chambres) || 0)
    }, 0)

    form.bathrooms = form.appartements.reduce((total, apt) => {
        return total + (parseInt(apt.salles_bain) || 0)
    }, 0)

    form.kitchens = form.appartements.length
    form.living_rooms = form.appartements.reduce((total, apt) => {
        return total + (parseInt(apt.pieces) || 0)
    }, 0)

    const maxEtage = Math.max(...form.appartements.map(apt => apt.etage))
    form.floors = maxEtage
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

const handlePropertyTitleChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        form.property_title = file
        documentFileName.value = file.name
    }
}

const handleNewImagesChange = (e) => {
    const files = Array.from(e.target.files)

    files.forEach(file => {
        if (file.size > 10240 * 1024) {
            alert(`L'image ${file.name} dépasse 10MB`)
            return
        }

        form.images.push(file)
        newImagePreviews.value.push({
            url: URL.createObjectURL(file),
            label: ''
        })
    })
}

const removeNewImage = (index) => {
    URL.revokeObjectURL(newImagePreviews.value[index].url)
    newImagePreviews.value.splice(index, 1)
    form.images.splice(index, 1)
}

const toggleDeleteImage = (imageId) => {
    const index = form.deleted_images.indexOf(imageId)
    if (index > -1) {
        form.deleted_images.splice(index, 1)
    } else {
        form.deleted_images.push(imageId)
    }
}

const handleAppartementImagesChange = (e, appartementIndex) => {
    const files = Array.from(e.target.files)

    files.forEach(file => {
        if (file.size > 10240 * 1024) {
            alert(`L'image ${file.name} dépasse 10MB`)
            return
        }

        form.appartements[appartementIndex].new_images.push({
            file: file,
            url: URL.createObjectURL(file),
            label: ''
        })
    })
}

const removeNewAppartementImage = (appartementIndex, imageIndex) => {
    const img = form.appartements[appartementIndex].new_images[imageIndex]
    URL.revokeObjectURL(img.url)
    form.appartements[appartementIndex].new_images.splice(imageIndex, 1)
}

const toggleDeleteAppartementImage = (appartementIndex, imageId) => {
    const deletedImages = form.appartements[appartementIndex].deleted_images
    const index = deletedImages.indexOf(imageId)
    if (index > -1) {
        deletedImages.splice(index, 1)
    } else {
        deletedImages.push(imageId)
    }
}

const onTypeMandatChange = () => {
    if (form.type_mandat !== 'vente') {
        form.type_mandat_vente = ''
    }
}

const onCategorieChange = () => {
    if (estCategorieAppartement.value) {
        if (!form.floors || form.floors < 1) {
            alert('Veuillez d\'abord saisir le nombre d\'étages pour générer les appartements')
            return
        }
        genererFormulairesAppartements()
    } else if (estCategorieMaisonOuStudio.value) {
        afficherFormulairesAppartements.value = false
        form.appartements = []
        if (form.rooms === 0) form.rooms = ''
        if (form.bathrooms === 0) form.bathrooms = ''
        if (form.kitchens === 0) form.kitchens = ''
        if (form.living_rooms === 0) form.living_rooms = ''
    } else {
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
    const appartementExistants = form.appartements.filter(apt => apt.id)

    form.appartements = []

    for (let i = 0; i < nombreAppartements; i++) {
        const existant = appartementExistants.find(apt => apt.etage === i)

        if (existant) {
            form.appartements.push(existant)
        } else {
            form.appartements.push({
                numero: `APT-${String(i + 1).padStart(3, '0')}`,
                etage: i,
                superficie: form.superficy ? (form.superficy / nombreAppartements).toFixed(2) : '',
                pieces: 1,
                chambres: 2,
                salles_bain: 1,
                description: getEtageLabel(i),
                existing_images: [],
                new_images: [],
                deleted_images: []
            })
        }
    }

    calculerTotauxAppartements()
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
    const remainingGeneralImages = existingImages.value.length - form.deleted_images.length + newImagePreviews.value.length

    if (remainingGeneralImages === 0 && !afficherFormulairesAppartements.value) {
        alert('Le bien doit avoir au moins une image')
        return
    }

    form.images_labels = newImagePreviews.value.map(p => p.label || '')

    if (afficherFormulairesAppartements.value) {
        form.appartements_images = []
        form.appartements_images_labels = []
        form.appartements_deleted_images = []

        form.appartements.forEach((appartement, index) => {
            appartement.deleted_images.forEach(imgId => {
                form.appartements_deleted_images.push({
                    image_id: imgId,
                    appartement_index: index
                })
            })

            appartement.new_images.forEach(img => {
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

    const formData = new FormData()
    formData.append('_method', 'PUT')

    Object.keys(form.data()).forEach(key => {
        if (key === 'images') {
            form.images.forEach(img => formData.append('images[]', img))
        } else if (key === 'images_labels') {
            form.images_labels.forEach(label => formData.append('images_labels[]', label))
        } else if (key === 'deleted_images') {
            form.deleted_images.forEach(id => formData.append('deleted_images[]', id))
        } else if (key === 'property_title' && form.property_title) {
            formData.append('property_title', form.property_title)
        } else if (key === 'appartements') {
            formData.append('appartements', JSON.stringify(
                form.appartements.map(apt => ({
                    id: apt.id,
                    numero: apt.numero,
                    etage: apt.etage,
                    superficie: apt.superficie,
                    pieces: apt.pieces,
                    chambres: apt.chambres,
                    salles_bain: apt.salles_bain,
                    description: apt.description
                }))
            ))
        } else if (key === 'appartements_images') {
            form.appartements_images.forEach((imgData, idx) => {
                formData.append(`appartements_images[${idx}][file]`, imgData.file)
                formData.append(`appartements_images[${idx}][appartement_index]`, imgData.appartement_index)
            })
        } else if (key === 'appartements_images_labels') {
            form.appartements_images_labels.forEach((labelData, idx) => {
                formData.append(`appartements_images_labels[${idx}][label]`, labelData.label)
                formData.append(`appartements_images_labels[${idx}][appartement_index]`, labelData.appartement_index)
            })
        } else if (key === 'appartements_deleted_images') {
            form.appartements_deleted_images.forEach((delData, idx) => {
                formData.append(`appartements_deleted_images[${idx}][image_id]`, delData.image_id)
                formData.append(`appartements_deleted_images[${idx}][appartement_index]`, delData.appartement_index)
            })
        } else if (form[key] !== null && form[key] !== undefined) {
            formData.append(key, form[key])
        }
    })

    router.post(route('biens.update', props.bien.id), formData, {
        preserveScroll: true,
        onBefore: () => {
            form.processing = true
        },
        onFinish: () => {
            form.processing = false
        },
        onSuccess: () => {
            newImagePreviews.value.forEach(p => URL.revokeObjectURL(p.url))

            if (afficherFormulairesAppartements.value) {
                form.appartements.forEach(apt => {
                    apt.new_images.forEach(img => URL.revokeObjectURL(img.url))
                })
            }
        },
        onError: (errors) => {
            console.log('Erreurs:', errors)
        }
    })
}

const cancel = () => {
    newImagePreviews.value.forEach(p => URL.revokeObjectURL(p.url))

    if (afficherFormulairesAppartements.value) {
        form.appartements.forEach(apt => {
            apt.new_images.forEach(img => URL.revokeObjectURL(img.url))
        })
    }

    router.visit(route('biens.show', props.bien.id))
}
</script>
