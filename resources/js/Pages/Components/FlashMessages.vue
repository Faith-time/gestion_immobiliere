<template>
    <!-- Conteneur des messages flash - Position fixe en haut -->
    <div class="flash-messages-container">
        <!-- Message de succès -->
        <Transition name="slide-fade">
            <div v-if="showSuccess"
                 class="flash-alert flash-success"
                 role="alert">
                <div class="flash-icon">
                    <svg class="icon-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flash-content">
                    <h4 class="flash-title">Succès !</h4>
                    <p class="flash-message">{{ $page.props.flash.success }}</p>
                </div>
                <button @click="showSuccess = false" class="flash-close">
                    <svg class="close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>

        <!-- Message d'erreur -->
        <Transition name="slide-fade">
            <div v-if="showError"
                 class="flash-alert flash-error"
                 role="alert">
                <div class="flash-icon">
                    <svg class="icon-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flash-content">
                    <h4 class="flash-title">Erreur</h4>
                    <p class="flash-message">{{ $page.props.flash.error }}</p>
                </div>
                <button @click="showError = false" class="flash-close">
                    <svg class="close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>

        <!-- Message d'information -->
        <Transition name="slide-fade">
            <div v-if="showInfo"
                 class="flash-alert flash-info"
                 role="alert">
                <div class="flash-icon">
                    <svg class="icon-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flash-content">
                    <h4 class="flash-title">Information</h4>
                    <p class="flash-message">{{ $page.props.flash.info }}</p>
                </div>
                <button @click="showInfo = false" class="flash-close">
                    <svg class="close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>

        <!-- Message d'avertissement -->
        <Transition name="slide-fade">
            <div v-if="showWarning"
                 class="flash-alert flash-warning"
                 role="alert">
                <div class="flash-icon">
                    <svg class="icon-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.964-1.333-2.732 0L3.732 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flash-content">
                    <h4 class="flash-title">Attention</h4>
                    <p class="flash-message">{{ $page.props.flash.warning }}</p>
                </div>
                <button @click="showWarning = false" class="flash-close">
                    <svg class="close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const showSuccess = ref(false)
const showError = ref(false)
const showInfo = ref(false)
const showWarning = ref(false)

// Auto-fermeture après 5 secondes
const autoClose = (ref) => {
    setTimeout(() => {
        ref.value = false
    }, 5000)
}

// Surveiller les messages flash
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        showSuccess.value = true
        autoClose(showSuccess)
    }
    if (flash?.error) {
        showError.value = true
        autoClose(showError)
    }
    if (flash?.info) {
        showInfo.value = true
        autoClose(showInfo)
    }
    if (flash?.warning) {
        showWarning.value = true
        autoClose(showWarning)
    }
}, { deep: true, immediate: true })

onMounted(() => {
    // Afficher immédiatement si présent au montage
    if (page.props.flash?.success) showSuccess.value = true
    if (page.props.flash?.error) showError.value = true
    if (page.props.flash?.info) showInfo.value = true
    if (page.props.flash?.warning) showWarning.value = true
})
</script>

<style scoped>
.flash-messages-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 500px;
    width: 100%;
    padding: 0 20px;
}

.flash-alert {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    border: 2px solid;
    margin-bottom: 16px;
    animation: pulse-border 2s infinite;
}

@keyframes pulse-border {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    }
    50% {
        transform: scale(1.02);
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25);
    }
}

.flash-success {
    background: linear-gradient(135deg, #D4EDDA 0%, #C3E6CB 100%);
    border-color: #28A745;
    color: #155724;
}

.flash-error {
    background: linear-gradient(135deg, #F8D7DA 0%, #F5C6CB 100%);
    border-color: #DC3545;
    color: #721C24;
}

.flash-info {
    background: linear-gradient(135deg, #D1ECF1 0%, #BEE5EB 100%);
    border-color: #17A2B8;
    color: #0C5460;
}

.flash-warning {
    background: linear-gradient(135deg, #FFF3CD 0%, #FFE8A1 100%);
    border-color: #FFC107;
    color: #856404;
}

.flash-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flash-success .flash-icon {
    background: #28A745;
    color: white;
}

.flash-error .flash-icon {
    background: #DC3545;
    color: white;
}

.flash-info .flash-icon {
    background: #17A2B8;
    color: white;
}

.flash-warning .flash-icon {
    background: #FFC107;
    color: #856404;
}

.icon-svg {
    width: 24px;
    height: 24px;
}

.flash-content {
    flex: 1;
}

.flash-title {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 8px 0;
}

.flash-message {
    font-size: 14px;
    line-height: 1.5;
    margin: 0;
    font-weight: 500;
}

.flash-close {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.2s;
}

.flash-close:hover {
    background: rgba(0, 0, 0, 0.2);
    transform: scale(1.1);
}

.close-icon {
    width: 16px;
    height: 16px;
}

/* Animations de transition */
.slide-fade-enter-active {
    transition: all 0.4s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.3s ease-in;
}

.slide-fade-enter-from {
    transform: translateX(100px);
    opacity: 0;
}

.slide-fade-leave-to {
    transform: translateX(100px);
    opacity: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .flash-messages-container {
        top: 10px;
        right: 10px;
        left: 10px;
        max-width: none;
        padding: 0 10px;
    }

    .flash-alert {
        padding: 16px;
        gap: 12px;
    }

    .flash-icon {
        width: 32px;
        height: 32px;
    }

    .icon-svg {
        width: 20px;
        height: 20px;
    }

    .flash-title {
        font-size: 16px;
    }

    .flash-message {
        font-size: 13px;
    }
}
</style>
