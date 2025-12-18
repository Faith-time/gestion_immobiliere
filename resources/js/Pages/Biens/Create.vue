
<template>
    <div class="min-h-screen bg-gradient-to-br from-teal-50 via-cyan-50 to-emerald-50">
        <!-- Header avec dégradé teal -->
        <div class="relative overflow-hidden bg-gradient-to-r from-teal-600 via-cyan-600 to-teal-700 py-20">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="absolute inset-0 opacity-20">
                <div class="absolute top-0 left-1/4 w-96 h-96 bg-yellow-400 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-teal-400 rounded-full blur-3xl"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 text-center">
                <div class="inline-block mb-4">
                    <span class="px-4 py-2 bg-yellow-400/20 backdrop-blur-sm border border-yellow-400/30 rounded-full text-yellow-300 text-sm font-semibold">
                        Nouveau Bien
                    </span>
                </div>
                <h1 class="text-6xl font-extrabold text-white mb-4 tracking-tight">
                    Ajouter un Bien Immobilier
                </h1>
                <p class="text-xl text-teal-100 max-w-2xl mx-auto">
                    Créez votre bien avec plusieurs photos et informations détaillées
                </p>
            </div>
        </div>

        <!-- Formulaire principal -->
        <div class="max-w-6xl mx-auto px-4 -mt-12 relative z-10 pb-20">
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-teal-100/50 overflow-hidden">
                <!-- Barre décorative -->
                <div class="h-2 bg-gradient-to-r from-teal-500 via-yellow-400 to-cyan-500"></div>

                <form @submit.prevent="submit" class="p-8 md:p-12 space-y-12">

                    <!-- Section Photos avec design moderne -->
                    <div class="space-y-6">
                        <div class="flex items-center space-x-4 pb-4 border-b-2 border-teal-100">
                            <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-2xl flex items-center justify-center shadow-lg transform hover:scale-105 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-3xl font-bold bg-gradient-to-r from-teal-700 to-cyan-600 bg-clip-text text-transparent">
                                    Photos Générales du Bien
                                </h2>
                                <p class="text-sm text-gray-500 mt-1">Ajoutez des images de haute qualité</p>
                            </div>
                        </div>

                        <!-- Zone de téléchargement avec effet glassmorphism -->
                        <div class="group relative">
                            <label class="block text-sm font-bold text-gray-700 mb-3 flex items-center">
                                <span class="mr-2">Ajouter plusieurs photos</span>
                                <span class="text-red-500">*</span>
                                <span class="ml-auto text-xs text-teal-600 font-normal">
                                    {{ imagePreviews.length }} image(s) sélectionnée(s)
                                </span>
                            </label>

                            <div class="relative mb-6">
                                <input
                                    type="file"
                                    @change="handleImagesChange"
                                    ref="imageInput"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                    accept="image/*"
                                    multiple
                                />
                                <div class="relative w-full h-56 border-3 border-dashed border-teal-300 rounded-2xl hover:border-teal-500 hover:bg-teal-50/50 transition-all duration-300 bg-gradient-to-br from-teal-50/50 via-cyan-50/30 to-yellow-50/20 flex items-center justify-center group-hover:shadow-xl">
                                    <div class="text-center">
                                        <div class="w-20 h-20 bg-gradient-to-br from-teal-100 to-cyan-100 rounded-3xl flex items-center justify-center mx-auto mb-4 shadow-lg group-hover:scale-110 transition-transform">
                                            <svg class="w-10 h-10 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </div>
                                        <p class="text-lg text-gray-700 font-bold mb-2">Cliquez pour ajouter plusieurs photos</p>
                                        <p class="text-sm text-gray-500">PNG, JPG, WebP - Max 10MB par image</p>
                                        <div class="mt-3 inline-flex items-center space-x-2 px-4 py-2 bg-teal-100 rounded-full">
                                            <div class="w-2 h-2 bg-teal-500 rounded-full animate-pulse"></div>
                                            <span class="text-xs font-semibold text-teal-700">Prêt à recevoir vos fichiers</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Grille d'aperçu moderne -->
                            <div v-if="imagePreviews.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <div v-for="(preview, index) in imagePreviews" :key="index"
                                     class="relative group transform hover:scale-105 transition-all duration-300">
                                    <div class="relative aspect-square rounded-2xl overflow-hidden shadow-lg ring-2 ring-teal-100 group-hover:ring-teal-400">
                                        <img :src="preview.url" class="w-full h-full object-cover" />
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button
                                                type="button"
                                                @click="removeImage(index)"
                                                class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-xl w-10 h-10 flex items-center justify-center shadow-xl transform hover:scale-110 transition-all"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <input
                                        v-model="preview.label"
                                        type="text"
                                        class="mt-2 w-full px-3 py-2.5 text-sm border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 bg-white/80 backdrop-blur transition-all"
                                        placeholder="Libellé (ex: Façade, Salon...)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Titre et Document -->
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Titre du bien -->
                            <div class="group">
                                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-teal-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 3.5a1.5 1.5 0 013 0V4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-.5a1.5 1.5 0 000 3h.5a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-.5a1.5 1.5 0 00-3 0v.5a1 1 0 01-1 1H6a1 1 0 01-1-1v-3a1 1 0 00-1-1h-.5a1.5 1.5 0 010-3H4a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1v-.5z"/>
                                    </svg>
                                    Titre du bien <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="form.title"
                                        type="text"
                                        class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all bg-white shadow-sm hover:shadow-md group-hover:border-teal-300"
                                        placeholder="Ex: Villa moderne avec piscine"
                                        required
                                    />
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 text-teal-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Document commercial -->
                            <div class="group">
                                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"/>
                                    </svg>
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
                                    <div class="w-full px-5 py-4 border-2 border-dashed border-gray-300 rounded-xl hover:border-yellow-400 hover:bg-yellow-50/30 transition-all bg-gradient-to-br from-gray-50 to-yellow-50/20 flex items-center space-x-3 shadow-sm group-hover:shadow-md">
                                        <div class="w-14 h-14 bg-gradient-to-br from-yellow-100 to-amber-100 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-md">
                                            <svg class="w-7 h-7 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-700 font-semibold truncate">
                                                {{ documentFileName || 'Cliquez pour ajouter un document' }}
                                            </p>
                                            <p class="text-xs text-gray-500 mt-0.5">PDF, DOC, DOCX</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Type de Mandat avec nouveau design -->
                    <div class="space-y-6">
                        <div class="flex items-center space-x-4 pb-4 border-b-2 border-teal-100">
                            <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-2xl flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-3xl font-bold bg-gradient-to-r from-cyan-700 to-teal-600 bg-clip-text text-transparent">
                                    Type de Mandat
                                </h2>
                                <p class="text-sm text-gray-500 mt-1">Choisissez le type de mandat approprié</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="group">
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    Type de mandat <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.type_mandat"
                                    class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all bg-white shadow-sm hover:shadow-md appearance-none cursor-pointer"
                                    required
                                    @change="onTypeMandatChange"
                                >
                                    <option value="">-- Sélectionner --</option>
                                    <option value="vente">Mandat de Vente</option>
                                    <option value="gestion_locative">Mandat de Gérance (Location)</option>
                                </select>
                            </div>

                            <div v-if="form.type_mandat === 'vente'" class="group">
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    Type de mandat de vente <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.type_mandat_vente"
                                    class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all bg-white shadow-sm hover:shadow-md appearance-none cursor-pointer"
                                    required
                                >
                                    <option value="">-- Sélectionner --</option>
                                    <option value="exclusif">Mandat Exclusif</option>
                                    <option value="simple">Mandat Simple</option>
                                    <option value="semi_exclusif">Mandat Semi-Exclusif</option>
                                </select>
                            </div>
                        </div>

                        <!-- Explications avec nouveau design glassmorphism -->
                        <div v-if="form.type_mandat === 'vente' && form.type_mandat_vente"
                             class="relative overflow-hidden bg-gradient-to-br from-teal-50/80 via-cyan-50/60 to-yellow-50/40 backdrop-blur-sm border-2 border-teal-200/50 rounded-3xl p-8 shadow-xl">

                            <!-- Décoration d'arrière-plan -->
                            <div class="absolute top-0 right-0 w-64 h-64 bg-yellow-200/20 rounded-full blur-3xl"></div>
                            <div class="absolute bottom-0 left-0 w-64 h-64 bg-teal-200/20 rounded-full blur-3xl"></div>

                            <!-- Mandat Exclusif -->
                            <div v-if="form.type_mandat_vente === 'exclusif'" class="relative space-y-5">
                                <div class="flex items-start space-x-5">
                                    <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-2xl flex items-center justify-center shadow-2xl">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-3">
                                            <h3 class="text-2xl font-extrabold text-teal-800">Mandat Exclusif</h3>
                                        </div>
                                        <p class="text-base text-gray-700 leading-relaxed mb-4">
                                            Vous confiez la vente de votre bien <strong class="text-teal-700">uniquement à notre agence</strong> pendant toute la durée du mandat.
                                        </p>
                                        <div class="bg-white/80 backdrop-blur rounded-2xl p-5 space-y-3 shadow-lg">
                                            <div class="flex items-start space-x-3">
                                                <div class="w-6 h-6 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-700 font-medium">Accompagnement prioritaire et personnalisé</span>
                                            </div>
                                            <div class="flex items-start space-x-3">
                                                <div class="w-6 h-6 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-700 font-medium">Visibilité maximale sur tous nos canaux</span>
                                            </div>
                                            <div class="flex items-start space-x-3">
                                                <div class="w-6 h-6 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-700 font-medium">Commission optimisée</span>
                                            </div>
                                            <div class="h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent my-2"></div>
                                            <div class="flex items-start space-x-3">
                                                <div class="w-6 h-6 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-700 font-medium">Vous ne pouvez pas mandater une autre agence</span>
                                            </div>
                                            <div class="flex items-start space-x-3">
                                                <div class="w-6 h-6 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-700 font-medium">Vous ne pouvez pas vendre vous-même</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mandat Simple -->
                            <div v-if="form.type_mandat_vente === 'simple'" class="relative space-y-5">
                                <div class="flex items-start space-x-5">
                                    <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-2xl flex items-center justify-center shadow-2xl">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-3">
                                            <h3 class="text-2xl font-extrabold text-cyan-800">Mandat Simple</h3>
                                            <span class="px-3 py-1 bg-cyan-500 text-white text-xs font-bold rounded-full">FLEXIBLE</span>
                                        </div>
                                        <p class="text-base text-gray-700 leading-relaxed mb-4">
                                            Vous pouvez <strong class="text-cyan-700">mandater plusieurs agences</strong> simultanément et également <strong class="text-cyan-700">vendre vous-même</strong> votre bien.
                                        </p>
                                        <div class="bg-white/80 backdrop-blur rounded-2xl p-5 space-y-3 shadow-lg">
                                            <div class="flex items-start space-x-3">
                                                <div class="w-6 h-6 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-700 font-medium">Liberté totale : plusieurs agences possibles</span>
                                            </div>
                                            <div class="flex items-start space-x-3">
                                                <div class="w-6 h-6 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-700 font-medium">Vous pouvez vendre par vos propres moyens</span>
                                            </div>
                                            <div class="flex items-start space-x-3">
                                                <div class="w-6 h-6 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-700 font-medium">Flexibilité maximale</span>
                                            </div>
                                            <div class="h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent my-2"></div>
                                            <div class="flex items-start space-x-3">
                                                <div class="w-6 h-6 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-700 font-medium">Commission due uniquement à l'agence qui trouve l'acheteur</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mandat Semi-Exclusif -->
                            <div v-if="form.type_mandat_vente === 'semi_exclusif'" class="relative space-y-5">
                                <div class="flex items-start space-x-5">
                                    <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-2xl flex items-center justify-center shadow-2xl">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-3">
                                            <h3 class="text-2xl font-extrabold text-amber-800">Mandat Semi-Exclusif</h3>
                                        </div>
                                        <p class="text-base text-gray-700 leading-relaxed mb-4">
                                            Vous confiez la vente <strong class="text-amber-700">exclusivement à notre agence</strong>, mais vous conservez le droit de <strong class="text-amber-700">vendre vous-même</strong>.
                                        </p>
                                        <div class="bg-white/80 backdrop-blur rounded-2xl p-5 space-y-3 shadow-lg">
                                            <div class="flex items-start space-x-3">
                                                <div class="w-6 h-6 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-700 font-medium">Accompagnement dédié de notre agence</span>
                                            </div>
                                            <div class="flex items-start space-x-3">
                                                <div class="w-6 h-6 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-700 font-medium">Vous gardez le droit de vendre par vous-même</span>
                                            </div>
                                            <div class="flex items-start space-x-3">
                                                <div class="w-6 h-6 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-700 font-medium">Pas de commission si vous trouvez l'acheteur</span>
                                            </div>
                                            <div class="h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent my-2"></div>
                                            <div class="flex items-start space-x-3">
                                                <div class="w-6 h-6 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-700 font-medium">Vous ne pouvez pas mandater une autre agence</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Localisation -->
                    <div class="space-y-6">
                        <div class="flex items-center space-x-4 pb-4 border-b-2 border-teal-100">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-2xl flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-3xl font-bold bg-gradient-to-r from-yellow-600 to-amber-600 bg-clip-text text-transparent">
                                    Localisation
                                </h2>
                                <p class="text-sm text-gray-500 mt-1">Indiquez l'emplacement du bien</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="group">
                                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                    </svg>
                                    Ville <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.city"
                                    type="text"
                                    class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/20 transition-all bg-white shadow-sm hover:shadow-md group-hover:border-yellow-300"
                                    placeholder="Ex: Dakar"
                                    required
                                />
                            </div>

                            <div class="group">
                                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                    </svg>
                                    Adresse <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:border-amber-500 focus:ring-4 focus:ring-amber-500/20 transition-all bg-white shadow-sm hover:shadow-md group-hover:border-amber-300"
                                    placeholder="Ex: Avenue Cheikh Anta Diop"
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Caractéristiques -->
                    <div class="space-y-6">
                        <div class="flex items-center space-x-4 pb-4 border-b-2 border-teal-100">
                            <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-2xl flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-3xl font-bold bg-gradient-to-r from-teal-700 to-cyan-600 bg-clip-text text-transparent">
                                    Caractéristiques
                                </h2>
                                <p class="text-sm text-gray-500 mt-1">Détails techniques du bien immobilier</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Superficie -->
                            <div class="group">
                                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                    </svg>
                                    Superficie (m²) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.superficy"
                                    type="number"
                                    class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all bg-white shadow-sm hover:shadow-md"
                                    placeholder="250"
                                    required
                                />
                            </div>

                            <!-- Prix -->
                            <div class="group">
                                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Prix (FCFA) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.price"
                                    type="number"
                                    class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/20 transition-all bg-white shadow-sm hover:shadow-md"
                                    placeholder="50000000"
                                    required
                                />
                            </div>

                            <!-- Catégorie -->
                            <div class="group">
                                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-cyan-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>
                                    </svg>
                                    Catégorie <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.categorie_id"
                                    class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all bg-white shadow-sm hover:shadow-md appearance-none cursor-pointer"
                                    required
                                    @change="onCategorieChange"
                                >
                                    <option value="">-- Sélectionner --</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>

                            <!-- Chambres -->
                            <div class="group relative">
                                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                    Chambres
                                </label>
                                <input
                                    v-model="form.rooms"
                                    type="number"
                                    :disabled="estCategorieAppartement || estCategorieTerrain"
                                    :class="[
                                        'w-full px-5 py-4 border-2 rounded-xl transition-all shadow-sm hover:shadow-md',
                                        (estCategorieAppartement || estCategorieTerrain)
                                            ? 'bg-gray-100 border-gray-300 text-gray-500 cursor-not-allowed'
                                            : 'bg-white border-gray-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20'
                                    ]"
                                    :title="estCategorieAppartement ? 'Calculé automatiquement' : (estCategorieTerrain ? 'Non applicable pour un terrain' : '')"
                                    placeholder="4"
                                />
                                <div v-if="estCategorieAppartement || estCategorieTerrain" class="absolute right-4 top-11 text-gray-400">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Étages -->
                            <div class="group relative">
                                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    Étages
                                </label>
                                <input
                                    v-model.number="form.floors"
                                    type="number"
                                    :disabled="estCategorieAppartement || estCategorieTerrain"
                                    :class="[
                                        'w-full px-5 py-4 border-2 rounded-xl transition-all shadow-sm hover:shadow-md',
                                        (estCategorieAppartement || estCategorieTerrain)
                                            ? 'bg-gray-100 border-gray-300 text-gray-500 cursor-not-allowed'
                                            : 'bg-white border-gray-200 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20'
                                    ]"
                                    :title="estCategorieAppartement ? 'Calculé automatiquement' : (estCategorieTerrain ? 'Non applicable pour un terrain' : '')"
                                    placeholder="2"
                                    @input="!estCategorieAppartement && !estCategorieTerrain && genererFormulairesAppartements()"
                                />
                                <div v-if="estCategorieAppartement || estCategorieTerrain" class="absolute right-4 top-11 text-gray-400">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Salles de bain -->
                            <div class="group relative">
                                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                                    </svg>
                                    Salles de bain
                                </label>
                                <input
                                    v-model="form.bathrooms"
                                    type="number"
                                    :disabled="estCategorieAppartement || estCategorieTerrain"
                                    :class="[
                                        'w-full px-5 py-4 border-2 rounded-xl transition-all shadow-sm hover:shadow-md',
                                        (estCategorieAppartement || estCategorieTerrain)
                                            ? 'bg-gray-100 border-gray-300 text-gray-500 cursor-not-allowed'
                                            : 'bg-white border-gray-200 focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/20'
                                    ]"
                                    :title="estCategorieAppartement ? 'Calculé automatiquement' : (estCategorieTerrain ? 'Non applicable pour un terrain' : '')"
                                    placeholder="2"
                                />
                                <div v-if="estCategorieAppartement || estCategorieTerrain" class="absolute right-4 top-11 text-gray-400">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Cuisines -->
                            <div class="group relative">
                                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                    </svg>
                                    Cuisines
                                </label>
                                <input
                                    v-model="form.kitchens"
                                    type="number"
                                    :disabled="estCategorieAppartement || estCategorieTerrain"
                                    :class="[
                                        'w-full px-5 py-4 border-2 rounded-xl transition-all shadow-sm hover:shadow-md',
                                        (estCategorieAppartement || estCategorieTerrain)
                                            ? 'bg-gray-100 border-gray-300 text-gray-500 cursor-not-allowed'
                                            : 'bg-white border-gray-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/20'
                                    ]"
                                    :title="estCategorieAppartement ? 'Calculé automatiquement' : (estCategorieTerrain ? 'Non applicable pour un terrain' : '')"
                                    placeholder="1"
                                />
                                <div v-if="estCategorieAppartement || estCategorieTerrain" class="absolute right-4 top-11 text-gray-400">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Salons -->
                            <div class="group relative">
                                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-teal-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                                    </svg>
                                    Salons
                                </label>
                                <input
                                    v-model="form.living_rooms"
                                    type="number"
                                    :disabled="estCategorieAppartement || estCategorieTerrain"
                                    :class="[
                                        'w-full px-5 py-4 border-2 rounded-xl transition-all shadow-sm hover:shadow-md',
                                        (estCategorieAppartement || estCategorieTerrain)
                                            ? 'bg-gray-100 border-gray-300 text-gray-500 cursor-not-allowed'
                                            : 'bg-white border-gray-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20'
                                    ]"
                                    :title="estCategorieAppartement ? 'Calculé automatiquement' : (estCategorieTerrain ? 'Non applicable pour un terrain' : '')"
                                    placeholder="1"
                                />
                                <div v-if="estCategorieAppartement || estCategorieTerrain" class="absolute right-4 top-11 text-gray-400">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Appartements -->
                    <div v-if="afficherFormulairesAppartements" class="space-y-8">
                        <div class="flex items-center space-x-4 pb-4 border-b-2 border-teal-100">
                            <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-2xl flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-3xl font-bold bg-gradient-to-r from-cyan-700 to-teal-600 bg-clip-text text-transparent">
                                    Informations des Appartements
                                </h2>
                                <p class="text-sm text-gray-500 mt-1">{{ form.appartements.length }} appartement(s) à configurer</p>
                            </div>
                        </div>

                        <div v-for="(appartement, index) in form.appartements" :key="index"
                             class="relative overflow-hidden bg-gradient-to-br from-cyan-50/80 via-teal-50/60 to-yellow-50/40 backdrop-blur-sm rounded-3xl p-8 border-2 border-cyan-200/50 shadow-xl hover:shadow-2xl transition-all duration-300">

                            <!-- Décoration -->
                            <div class="absolute top-0 right-0 w-48 h-48 bg-cyan-200/20 rounded-full blur-3xl"></div>

                            <!-- En-tête -->
                            <div class="relative flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-extrabold text-cyan-800">
                                            {{ getEtageLabel(index) }}
                                        </h3>
                                        <p class="text-sm text-cyan-600 font-medium">{{ appartement.numero }}</p>
                                    </div>
                                </div>
                                <div class="px-4 py-2 bg-white/70 backdrop-blur rounded-xl border border-cyan-200">
                                    <span class="text-xs font-bold text-cyan-700">Étage {{ appartement.etage }}</span>
                                </div>
                            </div>

                            <!-- Formulaire de l'appartement -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">
                                        Superficie (m²)
                                    </label>
                                    <input
                                        v-model.number="appartement.superficie"
                                        type="number"
                                        step="0.01"
                                        class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 bg-white shadow-sm hover:shadow-md transition-all"
                                        placeholder="75.5"
                                    />
                                </div>

                                <div class="group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">
                                        Salons
                                    </label>
                                    <input
                                        v-model.number="appartement.salons"
                                        type="number"
                                        class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 bg-white shadow-sm hover:shadow-md transition-all"
                                        placeholder="1"
                                    />
                                </div>

                                <div class="group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">
                                        Chambres
                                    </label>
                                    <input
                                        v-model.number="appartement.chambres"
                                        type="number"
                                        class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 bg-white shadow-sm hover:shadow-md transition-all"
                                        placeholder="2"
                                    />
                                </div>

                                <div class="group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">
                                        Salles de bain
                                    </label>
                                    <input
                                        v-model.number="appartement.salles_bain"
                                        type="number"
                                        class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 bg-white shadow-sm hover:shadow-md transition-all"
                                        placeholder="1"
                                    />
                                </div>

                                <div class="group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">
                                        Cuisines
                                    </label>
                                    <input
                                        v-model.number="appartement.cuisines"
                                        type="number"
                                        class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/20 bg-white shadow-sm hover:shadow-md transition-all"
                                        placeholder="1"
                                    />
                                </div>

                                <div class="group md:col-span-2">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        v-model="appartement.description"
                                        rows="3"
                                        class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 bg-white resize-none shadow-sm hover:shadow-md transition-all"
                                        :placeholder="'Description pour ' + getEtageLabel(index)"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Section Photos de l'appartement -->
                            <div class="mt-8 pt-8 border-t-2 border-cyan-200">
                                <label class="block text-sm font-bold text-gray-700 mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Photos de l'appartement
                                    <span class="ml-auto text-xs text-cyan-600 font-normal">{{ appartement.images.length }} image(s)</span>
                                </label>

                                <div class="relative mb-4">
                                    <input
                                        type="file"
                                        @change="(e) => handleAppartementImagesChange(e, index)"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                        accept="image/*"
                                        multiple
                                    />
                                    <div class="w-full h-40 border-2 border-dashed border-cyan-300 rounded-xl hover:border-cyan-500 hover:bg-cyan-50 transition-all bg-white flex items-center justify-center">
                                        <div class="text-center">
                                            <div class="w-14 h-14 bg-gradient-to-br from-cyan-100 to-teal-100 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                                <svg class="w-7 h-7 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                            </div>
                                            <p class="text-sm text-gray-600 font-semibold">
                                                Ajouter des photos
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Grille d'aperçu des images -->
                                <div v-if="appartement.images.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div v-for="(img, imgIndex) in appartement.images" :key="imgIndex"
                                         class="relative group transform hover:scale-105 transition-all duration-300">
                                        <div class="relative aspect-square rounded-xl overflow-hidden shadow-lg ring-2 ring-cyan-100 group-hover:ring-cyan-400">
                                            <img :src="img.url" class="w-full h-full object-cover" />
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button
                                                    type="button"
                                                    @click="removeAppartementImage(index, imgIndex)"
                                                    class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-lg w-8 h-8 flex items-center justify-center shadow-xl transform hover:scale-110 transition-all"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <input
                                            v-model="img.label"
                                            type="text"
                                            class="mt-2 w-full px-3 py-2 text-xs border-2 border-gray-200 rounded-lg focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 bg-white"
                                            placeholder="Libellé"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-6">
                        <div class="flex items-center space-x-4 pb-4 border-b-2 border-teal-100">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-2xl flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-3xl font-bold bg-gradient-to-r from-yellow-600 to-amber-600 bg-clip-text text-transparent">
                                    Description
                                </h2>
                                <p class="text-sm text-gray-500 mt-1">Décrivez votre bien en détail</p>
                            </div>
                        </div>

                        <div class="group">
                            <textarea
                                v-model="form.description"
                                rows="8"
                                class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/20 transition-all bg-white resize-none shadow-sm hover:shadow-md"
                                placeholder="Décrivez les points forts de votre bien, son environnement, ses équipements..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-6 pt-10 border-t-2 border-gradient-to-r from-teal-200 via-yellow-200 to-cyan-200">
                        <button
                            type="button"
                            @click="cancel"
                            class="w-full sm:w-auto px-10 py-4 bg-white hover:bg-gray-50 text-gray-700 rounded-xl font-bold transition-all shadow-lg hover:shadow-xl border-2 border-gray-200 hover:border-gray-300 transform hover:scale-105"
                        >
                            <div class="flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span>Annuler</span>
                            </div>
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing || imagePreviews.length === 0"
                            class="w-full sm:w-auto px-14 py-4 bg-gradient-to-r from-teal-500 via-cyan-500 to-teal-600 hover:from-teal-600 hover:via-cyan-600 hover:to-teal-700 text-white rounded-xl font-extrabold transition-all shadow-xl hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-105 relative overflow-hidden group"
                        >
                            <div class="absolute inset-0 bg-yellow-400/20 translate-x-full group-hover:translate-x-0 transition-transform duration-500"></div>
                            <div class="relative flex items-center justify-center space-x-3">
                                <svg v-if="!form.processing" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <svg v-else class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-lg">{{ form.processing ? 'Traitement en cours...' : 'Créer le Bien' }}</span>
                            </div>
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
    appartements_images: [],
    appartements_images_labels: []
})

const categorieSelectionnee = computed(() => {
    return props.categories.find(cat => cat.id === form.categorie_id)
})

const estCategorieAppartement = computed(() => {
    return form.categorie_id === 5 // ID de la catégorie Appartement
})

// ✅ NOUVEAU: Computed pour la catégorie Terrain
const estCategorieTerrain = computed(() => {
    // Vous devez mettre l'ID correct de la catégorie "Terrain" ici
    // Par exemple, si l'ID est 3, mettez 3
    return form.categorie_id === 3 // Remplacez 3 par l'ID réel de votre catégorie "Terrain"
})

const estCategorieMaisonOuStudio = computed(() => {
    return form.categorie_id === 4 || form.categorie_id === 10 // ID Maison ou Studio
})

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
        return total + (parseInt(apt.salons) || 0)
    }, 0)

    const maxEtage = Math.max(...form.appartements.map(apt => apt.etage))
    form.floors = maxEtage
}

const onCategorieChange = () => {
    if (estCategorieAppartement.value) {
        if (!form.floors || form.floors < 1) {
            alert('Veuillez d\'abord saisir le nombre d\'étages pour générer les appartements')
            return
        }
        genererFormulairesAppartements()
    } else if (estCategorieTerrain.value) {
        // ✅ Pour terrain, on grise les champs mais on ne réinitialise pas
        afficherFormulairesAppartements.value = false
        form.appartements = []
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
    form.appartements = []

    for (let i = 0; i < nombreAppartements; i++) {
        form.appartements.push({
            numero: `APT-${String(i + 1).padStart(3, '0')}`,
            etage: i,
            superficie: form.superficy ? (form.superficy / nombreAppartements).toFixed(2) : '',
            salons: 1,
            chambres: 2,
            salles_bain: 1,
            cuisines: 1,
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

const handleAppartementImagesChange = (e, appartementIndex) => {
    const files = Array.from(e.target.files)

    files.forEach(file => {
        if (file.size > 10240 * 1024) {
            alert(`L'image ${file.name} dépasse 10MB`)
            return
        }

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

    form.images_labels = imagePreviews.value.map(p => p.label || '')

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

    if (afficherFormulairesAppartements.value) {
        form.appartements.forEach(apt => {
            apt.images.forEach(img => URL.revokeObjectURL(img.url))
        })
    }

    router.visit(route('biens.index'))
}
</script>
