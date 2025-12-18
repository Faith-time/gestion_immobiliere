<!-- resources/js/Pages/Biens/Signature.vue -->
<script setup>
import { ref, onMounted, computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps({
    bien: Object,
    signatureStats: Object,
    userRoles: Array,
    isProprietaire: Boolean,
    isAdmin: Boolean,
})

// États réactifs
const signaturePad = ref(null)
const canvasRef = ref(null)
const isDrawing = ref(false)
const lastX = ref(0)
const lastY = ref(0)
const signatureData = ref('')
const isLoading = ref(false)
const showConfirmModal = ref(false)
const signatoryType = ref('')
const stats = ref(props.signatureStats)

// Configuration du canvas
const canvasConfig = {
    width: 600,
    height: 200,
    strokeColor: '#000000',
    strokeWidth: 2
}

// États computés
const canSignAsProprietaire = computed(() => {
    return props.isProprietaire && stats.value.can_sign_proprietaire
})

const canSignAsAgence = computed(() => {
    return props.isAdmin && stats.value.can_sign_agence
})

const hasAnySignatureRights = computed(() => {
    return canSignAsProprietaire.value || canSignAsAgence.value
})

const getSignatureStatusText = computed(() => {
    const statusLabels = {
        'non_signe': 'Aucune signature',
        'partiellement_signe': 'Partiellement signé',
        'entierement_signe': 'Entièrement signé'
    }
    return statusLabels[stats.value.signature_status] || 'Statut inconnu'
})

const getSignatureStatusColor = computed(() => {
    const statusColors = {
        'non_signe': 'text-red-600 bg-red-50 border-red-200',
        'partiellement_signe': 'text-orange-600 bg-orange-50 border-orange-200',
        'entierement_signe': 'text-green-600 bg-green-50 border-green-200'
    }
    return statusColors[stats.value.signature_status] || 'text-gray-600 bg-gray-50 border-gray-200'
})

// Initialisation du canvas
onMounted(() => {
    initializeCanvas()
})

const initializeCanvas = () => {
    const canvas = canvasRef.value
    if (!canvas) return

    canvas.width = canvasConfig.width
    canvas.height = canvasConfig.height

    const ctx = canvas.getContext('2d')
    ctx.lineCap = 'round'
    ctx.lineJoin = 'round'
    ctx.strokeStyle = canvasConfig.strokeColor
    ctx.lineWidth = canvasConfig.strokeWidth

    // Fond blanc
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, canvas.width, canvas.height)

    // Ajouter les event listeners
    canvas.addEventListener('mousedown', startDrawing)
    canvas.addEventListener('mousemove', draw)
    canvas.addEventListener('mouseup', stopDrawing)
    canvas.addEventListener('mouseout', stopDrawing)

    // Support tactile
    canvas.addEventListener('touchstart', handleTouch)
    canvas.addEventListener('touchmove', handleTouch)
    canvas.addEventListener('touchend', stopDrawing)
}

// Gestion du dessin avec la souris
const startDrawing = (e) => {
    isDrawing.value = true
    const rect = canvasRef.value.getBoundingClientRect()
    lastX.value = e.clientX - rect.left
    lastY.value = e.clientY - rect.top
}

const draw = (e) => {
    if (!isDrawing.value) return

    const canvas = canvasRef.value
    const ctx = canvas.getContext('2d')
    const rect = canvas.getBoundingClientRect()

    const currentX = e.clientX - rect.left
    const currentY = e.clientY - rect.top

    ctx.beginPath()
    ctx.moveTo(lastX.value, lastY.value)
    ctx.lineTo(currentX, currentY)
    ctx.stroke()

    lastX.value = currentX
    lastY.value = currentY
}

const stopDrawing = () => {
    isDrawing.value = false
}

// Gestion du tactile
const handleTouch = (e) => {
    e.preventDefault()
    const touch = e.touches[0]
    const mouseEvent = new MouseEvent(e.type === 'touchstart' ? 'mousedown' :
        e.type === 'touchmove' ? 'mousemove' : 'mouseup', {
        clientX: touch.clientX,
        clientY: touch.clientY
    })
    canvasRef.value.dispatchEvent(mouseEvent)
}

// Effacer la signature
const clearSignature = () => {
    const canvas = canvasRef.value
    const ctx = canvas.getContext('2d')
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, canvas.width, canvas.height)
    signatureData.value = ''
}

// Vérifier si la signature est vide
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

// Obtenir les données de signature
const getSignatureData = () => {
    if (isSignatureEmpty()) {
        return null
    }
    return canvasRef.value.toDataURL('image/png')
}

// Confirmer la signature
const confirmSignature = (type) => {
    const data = getSignatureData()
    if (!data) {
        alert('Veuillez d\'abord dessiner votre signature')
        return
    }

    signatoryType.value = type
    signatureData.value = data
    showConfirmModal.value = true
}

const submitSignature = async () => {
    if (!signatureData.value) {
        alert('Aucune signature à soumettre')
        return
    }

    isLoading.value = true
    showConfirmModal.value = false

    try {
        // ✅ CORRECTION : Utiliser router.post au lieu de fetch
        const endpoint = signatoryType.value === 'proprietaire'
            ? 'biens.mandat.sign-proprietaire'
            : 'biens.mandat.sign-agence'

        // Utiliser Inertia pour gérer le CSRF automatiquement
        router.post(route(endpoint, props.bien.id), {
            signature_data: signatureData.value
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                // Vérifier s'il y a des données dans la réponse
                const result = page.props.flash || {}

                if (result.success || !result.error) {
                    // Mettre à jour les stats depuis les props
                    if (page.props.signatureStats) {
                        stats.value = page.props.signatureStats
                    }

                    // Effacer le canvas
                    clearSignature()

                    // Afficher un message de succès
                    alert('Signature enregistrée avec succès !')

                    // Recharger la page si entièrement signé
                    if (stats.value.fully_signed) {
                        setTimeout(() => {
                            router.reload()
                        }, 1000)
                    }
                } else {
                    alert(result.error || 'Erreur lors de la signature')
                }
            },
            onError: (errors) => {
                console.error('Erreurs:', errors)
                alert(errors.signature_data || errors.message || 'Erreur lors de la signature')
            },
            onFinish: () => {
                isLoading.value = false
                signatureData.value = ''
                signatoryType.value = ''
            }
        })

    } catch (error) {
        console.error('Erreur:', error)
        alert('Erreur de communication avec le serveur')
        isLoading.value = false
        signatureData.value = ''
        signatoryType.value = ''
    }
}
// Annuler une signature
const cancelExistingSignature = async (type) => {
    if (!confirm('Êtes-vous sûr de vouloir annuler cette signature ?')) {
        return
    }

    isLoading.value = true

    try {
        const response = await fetch(
            route('ventes.cancel-signature', [vente.id, type]),
            {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            }
        )

        const result = await response.json()

        if (result.success) {
            // Mettre à jour les stats de signature
            signatureStats.value = result.signatureStats
            alert(result.message || 'Signature annulée avec succès')

            // Optionnel : recharger la page
            router.reload({ preserveScroll: true })
        } else {
            alert(result.error || 'Erreur lors de l\'annulation')
        }

    } catch (error) {
        console.error('❌ Erreur:', error)
        alert('Erreur de communication avec le serveur')
    } finally {
        isLoading.value = false
    }
}
// Télécharger le PDF
const downloadPdf = () => {
    if (stats.value.fully_signed) {
        window.open(route('biens.mandat.download-signed', props.bien.id))
    } else {
        window.open(route('biens.download-mandat-pdf', props.bien.id))
    }
}

// Prévisualiser le PDF
const previewPdf = () => {
    if (stats.value.fully_signed) {
        window.open(route('biens.mandat.preview-signed', props.bien.id), '_blank')
    } else {
        window.open(route('biens.preview-mandat-pdf', props.bien.id), '_blank')
    }
}

// Retourner à la liste
const goBack = () => {
    router.visit(route('biens.index'))
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
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-green-50">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 via-green-600 to-indigo-800 py-12">
            <div class="max-w-4xl mx-auto px-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">
                            Signature Électronique
                        </h1>
                        <p class="text-blue-100">
                            {{ bien.title }} - {{ bien.city }}
                        </p>
                        <p class="text-sm text-gray-600 mt-2">
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
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Statut de signature -->
            <div class="bg-white rounded-2xl shadow-lg border border-white/20 p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">
                        Statut de Signature
                    </h2>
                    <span :class="`px-4 py-2 rounded-full text-sm font-medium border ${getSignatureStatusColor}`">
                        {{ getSignatureStatusText }}
                    </span>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Signature Propriétaire -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-gray-800">
                                Propriétaire
                            </h3>
                            <div v-if="stats.proprietaire_signed" class="flex items-center text-green-600">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Signé
                            </div>
                            <div v-else class="text-gray-500">
                                Non signé
                            </div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <div><strong>{{ bien.proprietaire.name }}</strong></div>
                            <div v-if="stats.proprietaire_signed_at">
                                Signé le {{ formatDate(stats.proprietaire_signed_at) }}
                            </div>
                            <div v-else-if="canSignAsProprietaire" class="text-blue-600">
                                En attente de votre signature
                            </div>
                        </div>
                        <div v-if="stats.proprietaire_signed && isProprietaire" class="mt-3">
                            <button
                                @click="cancelExistingSignature('proprietaire')"
                                :disabled="isLoading"
                                class="text-sm text-red-600 hover:text-red-700 underline disabled:opacity-50"
                            >
                                Annuler ma signature
                            </button>
                        </div>
                    </div>

                    <!-- Signature Agence -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-gray-800">
                                Agence
                            </h3>
                            <div v-if="stats.agence_signed" class="flex items-center text-green-600">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Signé
                            </div>
                            <div v-else class="text-gray-500">
                                Non signé
                            </div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <div><strong>Votre Agence Immobilière</strong></div>
                            <div v-if="stats.agence_signed_at">
                                Signé le {{ formatDate(stats.agence_signed_at) }}
                            </div>
                            <div v-else-if="canSignAsAgence" class="text-blue-600">
                                En attente de signature de l'agence
                            </div>
                        </div>
                        <div v-if="stats.agence_signed && isAdmin" class="mt-3">
                            <button
                                @click="cancelExistingSignature('agence')"
                                :disabled="isLoading"
                                class="text-sm text-red-600 hover:text-red-700 underline disabled:opacity-50"
                            >
                                Annuler signature agence
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone de signature -->
            <div v-if="hasAnySignatureRights" class="bg-white rounded-2xl shadow-lg border border-white/20 p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    Signer le Document
                </h2>

                <!-- Canvas de signature -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Dessinez votre signature ci-dessous :
                    </label>
                    <div class="border-2 border-gray-300 border-dashed rounded-lg p-4 bg-gray-50">
                        <canvas
                            ref="canvasRef"
                            class="border border-gray-300 bg-white rounded cursor-crosshair mx-auto block"
                            :width="canvasConfig.width"
                            :height="canvasConfig.height"
                        ></canvas>
                        <div class="text-center mt-3">
                            <button
                                @click="clearSignature"
                                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                            >
                                Effacer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Boutons de signature -->
                <div class="flex flex-wrap gap-4">
                    <button
                        v-if="canSignAsProprietaire"
                        @click="confirmSignature('proprietaire')"
                        :disabled="isLoading"
                        class="flex-1 min-w-48 px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                    >
                        <svg v-if="!isLoading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <div v-if="isLoading" class="w-5 h-5 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        Signer comme Propriétaire
                    </button>

                    <button
                        v-if="canSignAsAgence"
                        @click="confirmSignature('agence')"
                        :disabled="isLoading"
                        class="flex-1 min-w-48 px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                    >
                        <svg v-if="!isLoading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0H3m2 0v-6a2 2 0 012-2h4a2 2 0 012 2v6" />
                        </svg>
                        <div v-if="isLoading" class="w-5 h-5 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        Signer pour l'Agence
                    </button>
                </div>
            </div>

            <!-- Actions PDF -->
            <div class="bg-white rounded-2xl shadow-lg border border-white/20 p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    Document PDF
                </h2>

                <div class="flex flex-wrap gap-4">
                    <button
                        @click="previewPdf"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition-colors flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Prévisualiser
                    </button>

                    <button
                        @click="downloadPdf"
                        class="px-6 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-colors flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Télécharger
                    </button>
                </div>

                <div v-if="stats.fully_signed" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center text-green-800">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Document entièrement signé et valide légalement
                    </div>
                </div>
            </div>
        </div>

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
                            Vous êtes sur le point de signer ce mandat en tant que
                            <strong>{{ signatoryType === 'proprietaire' ? 'Propriétaire' : 'Représentant de l\'Agence' }}</strong>.
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

<style scoped>
/* Styles pour le canvas de signature */
canvas {
    touch-action: none;
}

/* Animation pour les éléments */
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

/* Styles pour les modals */
.modal-enter-active, .modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
    opacity: 0;
}
</style>
