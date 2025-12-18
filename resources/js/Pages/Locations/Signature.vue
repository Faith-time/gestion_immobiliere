<script setup>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps({
    location: Object,
    signatureStats: Object,
    userRoles: Array,
    isLocataire: Boolean,
    isBailleur: Boolean,
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
const stats = ref(props.signatureStats)

// Configuration du canvas
const canvasConfig = {
    width: 600,
    height: 200,
    strokeColor: '#000000',
    strokeWidth: 2
}

// États computés
const canSignAsLocataire = computed(() => {
    return props.isLocataire && stats.value.can_sign_locataire
})

const canSignAsBailleur = computed(() => {
    return props.isBailleur && stats.value.can_sign_bailleur
})

const hasAnySignatureRights = computed(() => {
    return canSignAsLocataire.value || canSignAsBailleur.value
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

    // Event listeners souris
    canvas.addEventListener('mousedown', startDrawing)
    canvas.addEventListener('mousemove', draw)
    canvas.addEventListener('mouseup', stopDrawing)
    canvas.addEventListener('mouseout', stopDrawing)

    // Event listeners tactile
    canvas.addEventListener('touchstart', handleTouch)
    canvas.addEventListener('touchmove', handleTouch)
    canvas.addEventListener('touchend', stopDrawing)
}

const startDrawing = (e) => {
    isDrawing.value = true
    const rect = canvasRef.value.getBoundingClientRect()
    lastX.value = e.clientX - rect.left
    lastY.value = e.clientY - rect.top
}

const hasDrawn = ref(false)

const draw = (e) => {
    if (!isDrawing.value) return

    hasDrawn.value = true

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

const clearSignature = () => {
    const canvas = canvasRef.value
    const ctx = canvas.getContext('2d')
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, canvas.width, canvas.height)
    signatureData.value = ''
    hasDrawn.value = false
}

const isSignatureEmpty = () => {
    const canvas = canvasRef.value
    const ctx = canvas.getContext('2d')
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)

    for (let i = 0; i < imageData.data.length; i += 4) {
        if (imageData.data[i] !== 255 || imageData.data[i + 1] !== 255 || imageData.data[i + 2] !== 255) {
            return false
        }
    }
    return true
}

const getSignatureData = () => {
    if (!hasDrawn.value) {
        return null
    }
    return canvasRef.value.toDataURL('image/png')
}


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

// ✅ Soumission de signature pour location
const submitSignature = async () => {
    if (!signatureData.value) {
        alert('Aucune signature à soumettre')
        return
    }

    isLoading.value = true
    showConfirmModal.value = false

    try {
        const endpoint = signatoryType.value === 'locataire'
            ? route('locations.sign-locataire', props.location.id)
            : route('locations.sign-bailleur', props.location.id)

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                signature: signatureData.value
            })
        })

        const result = await response.json()

        if (result.success) {
            // ✅ Mettre à jour dynamiquement les stats
            stats.value = result.signatureStats

            // Effacer le canvas
            clearSignature()

            // ✅ Afficher l'alert
            alert(result.message || 'Signature enregistrée avec succès !')

            // ✅ Si entièrement signé, recharger après 1 seconde
            if (stats.value.fully_signed) {
                setTimeout(() => {
                    router.reload({ preserveScroll: true })
                }, 1000)
            }
        } else {
            alert(result.message || 'Erreur lors de la signature')
        }

    } catch (error) {
        console.error('Erreur:', error)
        alert('Erreur de communication avec le serveur')
    } finally {
        isLoading.value = false
        signatureData.value = ''
        signatoryType.value = ''
    }
}

// ✅ Annulation de signature pour location
const cancelExistingSignature = async (type) => {
    if (!confirm('Êtes-vous sûr de vouloir annuler cette signature ?')) {
        return
    }

    isLoading.value = true

    try {
        const response = await fetch(route('locations.cancel-signature', [props.location.id, type]), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })

        const result = await response.json()

        if (result.success) {
            // ✅ Mettre à jour dynamiquement les stats
            stats.value = result.signatureStats
            alert(result.message || 'Signature annulée avec succès')
        } else {
            alert(result.message || 'Erreur lors de l\'annulation')
        }
    } catch (error) {
        console.error('Erreur:', error)
        alert('Erreur de communication avec le serveur')
    } finally {
        isLoading.value = false
    }
}

const downloadPdf = () => {
    window.open(route('locations.download-contract', props.location.id))
}

const previewPdf = () => {
    window.open(route('locations.preview-contract', props.location.id), '_blank')
}

const goBack = () => {
    router.visit(route('locations.show', props.location.id))
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

const getTypeContratLabel = () => {
    if (props.location.type_contrat_info) {
        return props.location.type_contrat_info.label
    }
    return 'Contrat de Location'
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
                            Signature Électronique - Contrat de Location
                        </h1>
                        <p class="text-blue-100">
                            {{ location.bien.title }} - {{ location.bien.adresse }}
                        </p>
                        <p class="text-blue-200 text-sm mt-1">
                            {{ getTypeContratLabel() }}
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
            <!-- Informations du contrat -->
            <div class="bg-white rounded-2xl shadow-lg border border-white/20 p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    Informations du Contrat
                </h2>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-semibold text-gray-700">Période :</span>
                        <span class="text-gray-600 ml-2">
                            Du {{ new Date(location.date_debut).toLocaleDateString('fr-FR') }}
                            au {{ new Date(location.date_fin).toLocaleDateString('fr-FR') }}
                        </span>
                    </div>
                    <div v-if="location.appartement">
                        <span class="font-semibold text-gray-700">Appartement :</span>
                        <span class="text-gray-600 ml-2">
                            N° {{ location.appartement.numero }} - Étage {{ location.appartement.etage }}
                        </span>
                    </div>
                </div>
            </div>

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
                    <!-- Signature Locataire -->
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-gray-800">Locataire</h3>
                            <div v-if="stats.locataire_signed" class="flex items-center text-green-600">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Signé
                            </div>
                            <div v-else class="text-gray-500">Non signé</div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <div><strong>{{ location.client.name }}</strong></div>
                            <div class="text-xs text-gray-500">{{ location.client.email }}</div>
                            <div v-if="stats.locataire_signed_at" class="mt-2">
                                Signé le {{ formatDate(stats.locataire_signed_at) }}
                            </div>
                            <div v-else-if="canSignAsLocataire" class="text-blue-600 mt-2">
                                ✍️ En attente de votre signature
                            </div>
                        </div>
                        <div v-if="stats.locataire_signed && isLocataire" class="mt-3">
                            <button
                                @click="cancelExistingSignature('locataire')"
                                :disabled="isLoading"
                                class="text-sm text-red-600 hover:text-red-700 underline disabled:opacity-50"
                            >
                                Annuler ma signature
                            </button>
                        </div>
                    </div>

                    <!-- Signature Bailleur -->
                    <div class="bg-green-50 rounded-lg p-4 border border-green-100">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-gray-800">Bailleur (Propriétaire)</h3>
                            <div v-if="stats.bailleur_signed" class="flex items-center text-green-600">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Signé
                            </div>
                            <div v-else class="text-gray-500">Non signé</div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <div><strong>{{ location.bien.proprietaire.name }}</strong></div>
                            <div class="text-xs text-gray-500">{{ location.bien.proprietaire.email }}</div>
                            <div v-if="stats.bailleur_signed_at" class="mt-2">
                                Signé le {{ formatDate(stats.bailleur_signed_at) }}
                            </div>
                            <div v-else-if="canSignAsBailleur" class="text-green-600 mt-2">
                                ✍️ En attente de votre signature
                            </div>
                        </div>
                        <div v-if="stats.bailleur_signed && isBailleur" class="mt-3">
                            <button
                                @click="cancelExistingSignature('bailleur')"
                                :disabled="isLoading"
                                class="text-sm text-red-600 hover:text-red-700 underline disabled:opacity-50"
                            >
                                Annuler ma signature
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone de signature -->
            <div v-if="hasAnySignatureRights" class="bg-white rounded-2xl shadow-lg border border-white/20 p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    Signer le Contrat
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
                        v-if="canSignAsLocataire"
                        @click="confirmSignature('locataire')"
                        :disabled="isLoading"
                        class="flex-1 min-w-48 px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                    >
                        <svg v-if="!isLoading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <div v-if="isLoading" class="w-5 h-5 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        Signer comme Locataire
                    </button>

                    <button
                        v-if="canSignAsBailleur"
                        @click="confirmSignature('bailleur')"
                        :disabled="isLoading"
                        class="flex-1 min-w-48 px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                    >
                        <svg v-if="!isLoading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0H3m2 0v-6a2 2 0 012-2h4a2 2 0 012 2v6" />
                        </svg>
                        <div v-if="isLoading" class="w-5 h-5 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        Signer comme Propriétaire
                    </button>
                </div>
            </div>

            <!-- Actions PDF -->
            <div class="bg-white rounded-2xl shadow-lg border border-white/20 p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    Document PDF du Contrat
                </h2>

                <div class="flex flex-wrap gap-4">
                    <button
                        @click="previewPdf"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition-colors flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
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
                        Contrat entièrement signé et valide légalement
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
                            Vous êtes sur le point de signer ce contrat de location en tant que
                            <strong>{{ signatoryType === 'locataire' ? 'Locataire' : 'Propriétaire/Bailleur' }}</strong>.
                        </p>
                        <p class="text-sm text-gray-600">
                            Cette signature sera horodatée et aura une valeur légale. Une fois confirmée, elle sera ajoutée au document PDF du contrat.
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
canvas {
    touch-action: none;
}

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
</style>
