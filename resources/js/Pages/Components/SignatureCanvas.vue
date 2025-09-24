<template>
    <div class="signature-canvas-container">
        <!-- Canvas de signature -->
        <div class="mb-4">
            <label v-if="showLabel" class="block text-sm font-medium text-gray-700 mb-3">
                {{ label }}
            </label>

            <div
                :class="`border-2 border-dashed rounded-lg p-4 ${
                    hasError ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-gray-50'
                }`"
            >
                <canvas
                    ref="canvasRef"
                    class="border border-gray-300 bg-white rounded cursor-crosshair mx-auto block"
                    :width="config.width"
                    :height="config.height"
                    @mousedown="startDrawing"
                    @mousemove="draw"
                    @mouseup="stopDrawing"
                    @mouseout="stopDrawing"
                    @touchstart="handleTouchStart"
                    @touchmove="handleTouchMove"
                    @touchend="stopDrawing"
                ></canvas>

                <!-- Actions du canvas -->
                <div class="text-center mt-3 space-x-3">
                    <button
                        @click="clearSignature"
                        type="button"
                        class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm"
                    >
                        {{ clearButtonText }}
                    </button>

                    <button
                        v-if="showUndoButton"
                        @click="undoLastStroke"
                        type="button"
                        :disabled="strokes.length === 0"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Annuler
                    </button>
                </div>

                <!-- Indicateur d'état -->
                <div class="text-center mt-2 text-xs">
                    <span v-if="isEmpty && !hasError" class="text-gray-500">
                        {{ placeholderText }}
                    </span>
                    <span v-else-if="hasError" class="text-red-500">
                        {{ errorMessage }}
                    </span>
                    <span v-else class="text-green-600">
                        ✓ Signature prête
                    </span>
                </div>
            </div>
        </div>

        <!-- Prévisualisation (optionnelle) -->
        <div v-if="showPreview && !isEmpty" class="mt-4 p-4 bg-gray-50 rounded-lg">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Prévisualisation :</h4>
            <div class="border border-gray-200 rounded p-2 bg-white inline-block">
                <img
                    :src="previewData"
                    alt="Prévisualisation signature"
                    class="max-w-48 max-h-16 object-contain"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'

// Props
const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    width: {
        type: Number,
        default: 600
    },
    height: {
        type: Number,
        default: 200
    },
    strokeColor: {
        type: String,
        default: '#000000'
    },
    strokeWidth: {
        type: Number,
        default: 2
    },
    label: {
        type: String,
        default: 'Dessinez votre signature ci-dessous :'
    },
    showLabel: {
        type: Boolean,
        default: true
    },
    clearButtonText: {
        type: String,
        default: 'Effacer'
    },
    placeholderText: {
        type: String,
        default: 'Cliquez et glissez pour dessiner votre signature'
    },
    showPreview: {
        type: Boolean,
        default: false
    },
    showUndoButton: {
        type: Boolean,
        default: true
    },
    required: {
        type: Boolean,
        default: false
    },
    errorMessage: {
        type: String,
        default: 'Une signature est requise'
    }
})

// Émissions
const emit = defineEmits(['update:modelValue', 'signature-changed', 'signature-cleared'])

// États réactifs
const canvasRef = ref(null)
const isDrawing = ref(false)
const lastX = ref(0)
const lastY = ref(0)
const isEmpty = ref(true)
const hasError = ref(false)
const strokes = ref([])
const currentStroke = ref([])
const previewData = ref('')

// Configuration
const config = computed(() => ({
    width: props.width,
    height: props.height,
    strokeColor: props.strokeColor,
    strokeWidth: props.strokeWidth
}))

// Watchers
watch(() => props.modelValue, (newValue) => {
    if (!newValue) {
        clearCanvas()
    }
})

watch(isEmpty, (newValue) => {
    if (props.required) {
        hasError.value = newValue
    }
})

// Méthodes de dessin
const initCanvas = () => {
    const canvas = canvasRef.value
    if (!canvas) return

    const ctx = canvas.getContext('2d')
    ctx.strokeStyle = config.value.strokeColor
    ctx.lineWidth = config.value.strokeWidth
    ctx.lineCap = 'round'
    ctx.lineJoin = 'round'

    // Améliorer la qualité sur les écrans haute résolution
    const ratio = window.devicePixelRatio || 1
    const displayWidth = canvas.offsetWidth
    const displayHeight = canvas.offsetHeight

    canvas.width = displayWidth * ratio
    canvas.height = displayHeight * ratio
    canvas.style.width = displayWidth + 'px'
    canvas.style.height = displayHeight + 'px'

    ctx.scale(ratio, ratio)
    ctx.strokeStyle = config.value.strokeColor
    ctx.lineWidth = config.value.strokeWidth
    ctx.lineCap = 'round'
    ctx.lineJoin = 'round'
}

const getMousePos = (e) => {
    const canvas = canvasRef.value
    const rect = canvas.getBoundingClientRect()
    return {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top
    }
}

const startDrawing = (e) => {
    e.preventDefault()
    isDrawing.value = true

    const pos = getMousePos(e)
    lastX.value = pos.x
    lastY.value = pos.y

    currentStroke.value = [{ x: pos.x, y: pos.y }]
    isEmpty.value = false
    hasError.value = false
}

const draw = (e) => {
    if (!isDrawing.value) return

    e.preventDefault()
    const canvas = canvasRef.value
    const ctx = canvas.getContext('2d')
    const pos = getMousePos(e)

    ctx.beginPath()
    ctx.moveTo(lastX.value, lastY.value)
    ctx.lineTo(pos.x, pos.y)
    ctx.stroke()

    currentStroke.value.push({ x: pos.x, y: pos.y })
    lastX.value = pos.x
    lastY.value = pos.y

    updateSignature()
}

const stopDrawing = () => {
    if (isDrawing.value) {
        isDrawing.value = false
        if (currentStroke.value.length > 0) {
            strokes.value.push([...currentStroke.value])
            currentStroke.value = []
        }
        updateSignature()
    }
}

// Gestion tactile
const handleTouchStart = (e) => {
    e.preventDefault()
    const touch = e.touches[0]
    const mouseEvent = new MouseEvent('mousedown', {
        clientX: touch.clientX,
        clientY: touch.clientY
    })
    startDrawing(mouseEvent)
}

const handleTouchMove = (e) => {
    e.preventDefault()
    const touch = e.touches[0]
    const mouseEvent = new MouseEvent('mousemove', {
        clientX: touch.clientX,
        clientY: touch.clientY
    })
    draw(mouseEvent)
}

// Actions
const clearSignature = () => {
    clearCanvas()
    strokes.value = []
    currentStroke.value = []
    isEmpty.value = true
    hasError.value = props.required

    emit('update:modelValue', '')
    emit('signature-cleared')
}

const clearCanvas = () => {
    const canvas = canvasRef.value
    if (!canvas) return

    const ctx = canvas.getContext('2d')
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    previewData.value = ''
}

const undoLastStroke = () => {
    if (strokes.value.length === 0) return

    strokes.value.pop()
    redrawCanvas()
    updateSignature()

    if (strokes.value.length === 0) {
        isEmpty.value = true
        hasError.value = props.required
    }
}

const redrawCanvas = () => {
    const canvas = canvasRef.value
    if (!canvas) return

    const ctx = canvas.getContext('2d')
    ctx.clearRect(0, 0, canvas.width, canvas.height)

    ctx.strokeStyle = config.value.strokeColor
    ctx.lineWidth = config.value.strokeWidth
    ctx.lineCap = 'round'
    ctx.lineJoin = 'round'

    strokes.value.forEach(stroke => {
        if (stroke.length < 2) return

        ctx.beginPath()
        ctx.moveTo(stroke[0].x, stroke[0].y)

        for (let i = 1; i < stroke.length; i++) {
            ctx.lineTo(stroke[i].x, stroke[i].y)
        }

        ctx.stroke()
    })
}

const updateSignature = () => {
    const canvas = canvasRef.value
    if (!canvas) return

    const dataURL = canvas.toDataURL('image/png')
    previewData.value = dataURL

    emit('update:modelValue', dataURL)
    emit('signature-changed', dataURL)
}

// Méthodes publiques
const getSignatureData = () => {
    const canvas = canvasRef.value
    return canvas ? canvas.toDataURL('image/png') : null
}

const setSignatureData = (dataURL) => {
    if (!dataURL) {
        clearSignature()
        return
    }

    const canvas = canvasRef.value
    if (!canvas) return

    const ctx = canvas.getContext('2d')
    const img = new Image()

    img.onload = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height)
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height)
        isEmpty.value = false
        hasError.value = false
        previewData.value = dataURL
    }

    img.src = dataURL
}

const validateSignature = () => {
    const isValid = !isEmpty.value || !props.required
    hasError.value = !isValid
    return isValid
}

// Exposition des méthodes
defineExpose({
    getSignatureData,
    setSignatureData,
    clearSignature,
    validateSignature,
    isEmpty: computed(() => isEmpty.value)
})

// Initialisation
onMounted(() => {
    initCanvas()

    // Initialiser avec la valeur existante si fournie
    if (props.modelValue) {
        setSignatureData(props.modelValue)
    }
})
</script>

<style scoped>
.signature-canvas-container {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

canvas {
    touch-action: none;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}
</style>
