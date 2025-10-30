<template>
    <div class="contract-viewer-card">
        <div class="contract-header">
            <div class="header-left">
                <i class="fas fa-file-contract me-2"></i>
                <h5 class="mb-0">Contrat de Location - {{ typeContratLabel }}</h5>
            </div>
            <div class="header-actions">
                <button
                    @click="previewContract"
                    class="btn btn-outline-primary btn-sm me-2"
                    :disabled="loading"
                >
                    <i class="fas fa-eye me-1"></i>
                    Prévisualiser
                </button>
                <button
                    @click="downloadContract"
                    class="btn btn-primary btn-sm"
                    :disabled="loading"
                >
                    <i class="fas fa-download me-1"></i>
                    Télécharger PDF
                </button>
            </div>
        </div>

        <div v-if="location.pdf_generated_at" class="contract-info">
            <div class="info-item">
                <i class="fas fa-check-circle text-success me-2"></i>
                <span>Contrat généré le {{ formatDate(location.pdf_generated_at) }}</span>
            </div>
            <div class="info-item">
                <i class="fas fa-signature me-2" :class="signatureStatusClass"></i>
                <span>{{ signatureStatusText }}</span>
            </div>
        </div>

        <div v-else class="alert alert-info mb-0">
            <i class="fas fa-info-circle me-2"></i>
            Le contrat sera généré automatiquement lors de la création de la location.
        </div>

        <!-- Modal de prévisualisation -->
        <div v-if="showPreview" class="preview-modal" @click.self="closePreview">
            <div class="preview-container">
                <div class="preview-header">
                    <h5>Prévisualisation du Contrat</h5>
                    <button @click="closePreview" class="btn-close"></button>
                </div>
                <div class="preview-body">
                    <iframe
                        :src="previewUrl"
                        frameborder="0"
                        class="preview-iframe"
                    ></iframe>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { route } from 'ziggy-js'

const props = defineProps({
    location: {
        type: Object,
        required: true
    }
})

const loading = ref(false)
const showPreview = ref(false)
const previewUrl = ref('')

const typeContratLabel = computed(() => {
    const types = {
        'bail_classique': 'Bail d\'Habitation Classique',
        'bail_meuble': 'Bail Meublé',
        'bail_commercial': 'Bail Commercial'
    }
    return types[props.location.type_contrat] || 'Bail de Location'
})

const signatureStatusClass = computed(() => {
    if (props.location.signature_status === 'entierement_signe') {
        return 'text-success'
    } else if (props.location.signature_status === 'partiellement_signe') {
        return 'text-warning'
    }
    return 'text-muted'
})

const signatureStatusText = computed(() => {
    if (props.location.signature_status === 'entierement_signe') {
        return 'Contrat entièrement signé'
    } else if (props.location.signature_status === 'partiellement_signe') {
        return 'Contrat partiellement signé'
    }
    return 'En attente de signatures'
})

const previewContract = () => {
    loading.value = true
    previewUrl.value = route('locations.contract.preview', props.location.id)
    showPreview.value = true
    loading.value = false
}

const downloadContract = () => {
    loading.value = true
    window.location.href = route('locations.contract.download', props.location.id)
    setTimeout(() => {
        loading.value = false
    }, 1000)
}

const closePreview = () => {
    showPreview.value = false
    previewUrl.value = ''
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<style scoped>
.contract-viewer-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.contract-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.header-left {
    display: flex;
    align-items: center;
}

.header-left h5 {
    margin: 0;
    font-weight: 600;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.contract-info {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
}

.info-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    font-size: 14px;
}

.info-item:last-child {
    margin-bottom: 0;
}

/* Modal de prévisualisation */
.preview-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.preview-container {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 1200px;
    height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
}

.preview-header h5 {
    margin: 0;
    font-weight: 600;
}

.preview-body {
    flex: 1;
    overflow: hidden;
}

.preview-iframe {
    width: 100%;
    height: 100%;
}

/* Responsive */
@media (max-width: 768px) {
    .contract-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }

    .header-actions {
        width: 100%;
        flex-direction: column;
    }

    .header-actions button {
        width: 100%;
    }
}
</style>
