<template>
    <div class="min-h-screen bg-gradient-to-br from-green-50 via-blue-50 to-indigo-50">

        <!-- Header -->
        <header class="bg-gradient-to-r from-green-600 via-blue-600 to-indigo-800 py-12">
            <div class="max-w-4xl mx-auto px-4 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">
                        Signature du Contrat de Location
                    </h1>
                    <p class="text-blue-100">
                        {{ location.bien.title }} - {{ location.bien.city }}
                    </p>
                    <p class="text-sm text-blue-200 mt-2">
                        Loyer : {{ formatPrice(location.loyer_mensuel) }} FCFA/mois
                    </p>
                    <p class="text-xs text-blue-200 mt-1">
                        Cette signature sera horodatée et aura une valeur légale.
                    </p>
                </div>
                <button
                    @click="goBack"
                    class="px-4 py-2 bg-white/20 text-white rounded-lg hover:bg-white/30 transition-colors"
                >
                    ← Retour
                </button>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 py-8 space-y-8">

            <!-- Statut de signature -->
            <section class="bg-white rounded-2xl shadow-lg border border-white/20 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Statut du Contrat</h2>
                    <span :class="`px-4 py-2 rounded-full text-sm font-medium border ${getSignatureStatusColor}`">
                        {{ getSignatureStatusText }}
                    </span>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Bailleur (Propriétaire) -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-gray-800">Bailleur (Propriétaire)</h3>
                            <div v-if="signatureStats.bailleur_signed" class="flex items-center text-green-600">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Signé
                            </div>
                            <div v-else class="text-gray-500">Non signé</div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <div><strong>{{ location.bien?.proprietaire?.name }}</strong></div>
                            <div v-if="signatureStats.bailleur_signed_at">
                                Signé le {{ formatDate(signatureStats.bailleur_signed_at) }}
                            </div>
                            <div v-else-if="isProprietaire && signatureStats.can_sign_bailleur" class="text-blue-600">
                                En attente de votre signature
                            </div>
                        </div>
                        <div v-if="signatureStats.bailleur_signed && isProprietaire" class="mt-3">
                            <button @click="cancelExistingSignature('bailleur')" :disabled="isLoading"
                                    class="text-sm text-red-600 hover:text-red-700 underline disabled:opacity-50">
                                Annuler ma signature
                            </button>
                        </div>
                    </div>

                    <!-- Locataire -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-gray-800">Locataire</h3>
                            <div v-if="signatureStats.locataire_signed" class="flex items-center text-green-600">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Signé
                            </div>
                            <div v-else class="text-gray-500">Non signé</div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <div><strong>{{ location.client.name }}</strong></div>
                            <div v-if="signatureStats.locataire_signed_at">
                                Signé le {{ formatDate(signatureStats.locataire_signed_at) }}
                            </div>
                            <div v-else-if="isLocataire && signatureStats.can_sign_locataire" class="text-blue-600">
                                En attente de votre signature
                            </div>
                        </div>
                        <div v-if="signatureStats.locataire_signed && isLocataire" class="mt-3">
                            <button @click="cancelExistingSignature('locataire')" :disabled="isLoading"
                                    class="text-sm text-red-600 hover:text-red-700 underline disabled:opacity-50">
                                Annuler ma signature
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Zone de signature - Uniquement pour les parties autorisées -->
            <section v-if="hasSignatureRights" class="bg-white rounded-2xl shadow-lg border border-white/20 p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Signer le Contrat</h2>

                <!-- Alerte de vérification d'identité -->
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center text-blue-800">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">
                            Vous signez en tant que :
                            <strong>{{ isLocataire ? 'Locataire' : 'Propriétaire' }}</strong>
                        </span>
                    </div>
                </div>

                <!-- Canvas de signature -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Dessinez votre signature ci-dessous :
                    </label>
                    <div class="border-2 border-gray-300 border-dashed rounded-lg p-4 bg-gray-50">
                        <canvas ref="canvasRef"
                                class="border border-gray-300 bg-white rounded cursor-crosshair mx-auto block"
                                :width="canvasConfig.width" :height="canvasConfig.height"
                                @mousedown="startDrawing"
                                @mousemove="draw"
                                @mouseup="stopDrawing"
                                @mouseout="stopDrawing"
                                @touchstart="handleTouchStart"
                                @touchmove="handleTouchMove"
                                @touchend="stopDrawing"></canvas>
                        <div class="text-center mt-3">
                            <button @click="clearSignature"
                                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                                Effacer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bouton de signature unique selon le rôle -->
                <div class="flex justify-center">
                    <button
                        v-if="(isProprietaire && signatureStats.can_sign_bailleur) || (isLocataire && signatureStats.can_sign_locataire)"
                        @click="confirmSignature(isProprietaire && signatureStats.can_sign_bailleur ? 'bailleur' : 'locataire')"
                        :disabled="isLoading"
                        :class="`w-full max-w-md px-8 py-4 text-white rounded-lg font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center ${
                            isProprietaire && signatureStats.can_sign_bailleur
                                ? 'bg-green-600 hover:bg-green-700'
                                : 'bg-blue-600 hover:bg-blue-700'
                        }`"
                    >
                        <svg v-if="!isLoading" class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="isProprietaire && signatureStats.can_sign_bailleur" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <div v-if="isLoading" class="w-6 h-6 mr-3 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        <span class="text-lg">
                            {{ isProprietaire && signatureStats.can_sign_bailleur ? 'Signer comme Propriétaire' : 'Signer comme Locataire' }}
                        </span>
                    </button>
                </div>
            </section>

            <!-- Message pour utilisateurs non autorisés -->
            <section v-else class="bg-yellow-50 rounded-2xl shadow-lg border border-yellow-200 p-6">
                <div class="flex items-center text-yellow-800">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <div>
                        <h3 class="font-semibold">Accès restreint</h3>
                        <p class="text-sm mt-1">Seuls le locataire et le propriétaire peuvent signer ce contrat.</p>
                    </div>
                </div>
            </section>

            <!-- Actions PDF -->
            <section class="bg-white rounded-2xl shadow-lg border border-white/20 p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Document de Location</h2>
                <div class="flex flex-wrap gap-4">
                    <button
                        @click="previewContract"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition-colors flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Prévisualiser le contrat
                    </button>
                    <button
                        @click="downloadContract"
                        class="px-6 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-colors flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Télécharger le contrat
                    </button>
                </div>
                <div v-if="signatureStats.fully_signed"
                     class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center text-green-800">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Contrat entièrement signé et valide légalement
                </div>
            </section>
        </main>

        <!-- Modal de confirmation -->
        <div v-if="showConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="showConfirmModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300" @click.stop>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Confirmer la Signature
                        </h3>
                        <button @click="showConfirmModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mb-6">
                        <p class="text-gray-700 mb-4">
                            Vous êtes sur le point de signer ce contrat de location en tant que
                            <strong>{{ signatoryType === 'bailleur' ? 'Propriétaire (Bailleur)' : 'Locataire' }}</strong>.
                        </p>
                        <p class="text-sm text-gray-600">
                            Cette signature sera horodatée et aura une valeur légale. Une fois confirmée, elle sera ajoutée au document PDF.
                        </p>
                    </div>

                    <div class="flex space-x-3">
                        <button
                            @click="showConfirmModal = false"
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-medium hover:bg-gray-300 transition-colors"
                        >
                            Annuler
                        </button>
                        <button
                            @click="submitSignature"
                            :disabled="isLoading"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                        >
                            <div v-if="isLoading" class="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                            Confirmer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps({
    location: Object,
    signatureStats: Object,
    userRoles: Array,
    isLocataire: Boolean,
    isBailleur: Boolean, // Propriétaire du bien
    isAdmin: Boolean,
})

// États réactifs
const canvasRef = ref(null)
const isDrawing = ref(false)
const lastX = ref(0)
const lastY = ref(0)
const signatureData = ref('')
const isLoading = ref(false)
const showConfirmModal = ref(false)
const signatoryType = ref('')

// Configuration du canvas
const canvasConfig = {
    width: 600,
    height: 200,
    color: '#000',
    lineWidth: 2,
}

// États computés - Contrôle strict des accès
const isProprietaire = computed(() => {
    return props.isBailleur || (props.location.bien?.proprietaire_id === getCurrentUserId())
})

const hasSignatureRights = computed(() => {
    // Seuls le locataire (client_id) et le propriétaire (proprietaire_id) peuvent signer
    return (props.isLocataire && props.signatureStats.can_sign_locataire) ||
        (isProprietaire.value && props.signatureStats.can_sign_bailleur)
})

const getSignatureStatusText = computed(() => {
    if (props.signatureStats.fully_signed) {
        return 'Entièrement signé'
    }
    if (props.signatureStats.bailleur_signed || props.signatureStats.locataire_signed) {
        return 'Partiellement signé'
    }
    return 'Aucune signature'
})

const getSignatureStatusColor = computed(() => {
    if (props.signatureStats.fully_signed) {
        return 'text-green-600 bg-green-50 border-green-200'
    }
    if (props.signatureStats.bailleur_signed || props.signatureStats.locataire_signed) {
        return 'text-orange-600 bg-orange-50 border-orange-200'
    }
    return 'text-red-600 bg-red-50 border-red-200'
})

// Fonction utilitaire pour obtenir l'ID utilisateur actuel
const getCurrentUserId = () => {
    // Cette fonction devrait retourner l'ID de l'utilisateur connecté
    // À adapter selon votre système d'authentification
    return window.user?.id || null
}

// Initialisation du canvas
const initializeCanvas = () => {
    const canvas = canvasRef.value
    if (!canvas) return

    const ctx = canvas.getContext('2d')
    ctx.strokeStyle = canvasConfig.color
    ctx.lineWidth = canvasConfig.lineWidth
    ctx.lineJoin = 'round'
    ctx.lineCap = 'round'

    // Fond blanc
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, canvas.width, canvas.height)
}

// Gestion du formatage
const formatPrice = (value) => {
    if (typeof value !== 'number') return value
    return value.toLocaleString('fr-FR', { minimumFractionDigits: 0 })
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Gestion du dessin
const startDrawing = (e) => {
    isDrawing.value = true
    const rect = canvasRef.value.getBoundingClientRect()
    lastX.value = e.clientX - rect.left
    lastY.value = e.clientY - rect.top
}

const draw = (e) => {
    if (!isDrawing.value) return
    const rect = canvasRef.value.getBoundingClientRect()
    const ctx = canvasRef.value.getContext('2d')
    const x = e.clientX - rect.left
    const y = e.clientY - rect.top
    ctx.beginPath()
    ctx.moveTo(lastX.value, lastY.value)
    ctx.lineTo(x, y)
    ctx.stroke()
    lastX.value = x
    lastY.value = y
}

const stopDrawing = () => {
    isDrawing.value = false
}

const handleTouchStart = (e) => {
    e.preventDefault()
    const touch = e.touches[0]
    startDrawing({ clientX: touch.clientX, clientY: touch.clientY })
}

const handleTouchMove = (e) => {
    e.preventDefault()
    const touch = e.touches[0]
    draw({ clientX: touch.clientX, clientY: touch.clientY })
}

const clearSignature = () => {
    const canvas = canvasRef.value
    const ctx = canvas.getContext('2d')
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, canvas.width, canvas.height)
}

const isSignatureEmpty = () => {
    const canvas = canvasRef.value
    const ctx = canvas.getContext('2d')
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)

    // Vérifier si tous les pixels sont blancs
    for (let i = 0; i < imageData.data.length; i += 4) {
        if (imageData.data[i] !== 255 || imageData.data[i + 1] !== 255 || imageData.data[i + 2] !== 255) {
            return false
        }
    }
    return true
}

const confirmSignature = (type) => {
    if (isSignatureEmpty()) {
        alert('Veuillez dessiner votre signature avant de confirmer.')
        return
    }

    // Vérification supplémentaire de sécurité
    if (type === 'bailleur' && !isProprietaire.value) {
        alert('Vous n\'êtes pas autorisé à signer en tant que propriétaire.')
        return
    }

    if (type === 'locataire' && !props.isLocataire) {
        alert('Vous n\'êtes pas autorisé à signer en tant que locataire.')
        return
    }

    signatoryType.value = type
    showConfirmModal.value = true
}

const getSignatureData = () => {
    return canvasRef.value.toDataURL('image/png')
}

const submitSignature = async () => {
    isLoading.value = true
    const data = getSignatureData()

    try {
        // Utilisation des routes définies dans votre contrôleur
        const url = signatoryType.value === 'bailleur'
            ? route('signature.bailleur', props.location.id)
            : route('signature.locataire', props.location.id)

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                signature: data
            })
        })

        const result = await response.json()

        if (result.success) {
            showConfirmModal.value = false
            clearSignature()
            alert(result.message)

            // Recharger la page pour mettre à jour les statuts
            setTimeout(() => {
                window.location.reload()
            }, 1000)
        } else {
            alert(result.message || 'Erreur lors de la signature')
        }
    } catch (error) {
        console.error('Erreur lors de la signature:', error)
        alert('Erreur lors de la signature. Veuillez réessayer.')
    } finally {
        isLoading.value = false
    }
}

const cancelExistingSignature = async (type) => {
    if (!confirm('Êtes-vous sûr de vouloir annuler votre signature ?')) {
        return
    }

    isLoading.value = true

    try {
        await router.delete(route('locations.cancel-signature', [props.location.id, type]), {
            preserveScroll: true
        })
    } catch (error) {
        console.error('Erreur lors de l\'annulation:', error)
        alert('Erreur lors de l\'annulation. Veuillez réessayer.')
    } finally {
        isLoading.value = false
    }
}

const previewContract = () => {
    window.open(route('locations.contract.preview', props.location.id), '_blank')
}

const downloadContract = () => {
    window.open(route('locations.contract.download', props.location.id))
}

const goBack = () => {
    router.visit(route('locations.index'))
}

onMounted(() => {
    nextTick(() => initializeCanvas())
})
</script>

<style scoped>
canvas {
    touch-action: none;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.bg-white {
    animation: fadeInUp 0.6s ease-out;
}

.bg-white:nth-child(1) { animation-delay: 0.1s; }
.bg-white:nth-child(2) { animation-delay: 0.2s; }
.bg-white:nth-child(3) { animation-delay: 0.3s; }
</style>
