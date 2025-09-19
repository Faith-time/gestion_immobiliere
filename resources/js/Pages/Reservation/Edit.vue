<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- En-tête -->
                <div class="text-center mb-4">
                    <h1 class="h2 text-primary">Modifier la réservation #{{ reservation.id }}</h1>
                    <p class="text-muted">Vous pouvez modifier votre document tant que la réservation n'est pas validée</p>
                </div>

                <!-- Informations du bien (lecture seule) -->
                <div class="card mb-4 shadow">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-home me-2"></i>
                            Propriété réservée
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <img
                                    :src="bien?.image ? `/storage/${bien.image}` : '/images/placeholder.jpg'"
                                    :alt="bien?.title"
                                    class="img-fluid rounded"
                                />
                            </div>
                            <div class="col-md-8">
                                <h5 class="text-primary">{{ bien?.title }}</h5>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    {{ bien?.address }}, {{ bien?.city }}
                                </p>
                                <div class="h4 text-success mb-0">{{ formatPrice(bien?.price) }} FCFA</div>
                                <div class="small text-muted mt-2">
                                    <strong>Caution requise:</strong> 25,000 FCFA
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de modification -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Modifier votre document
                        </h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="updateReservation">
                            <!-- Document actuel -->
                            <div v-if="reservation.client_documents && reservation.client_documents.length > 0" class="mb-4">
                                <h6 class="text-primary mb-3">Document actuellement fourni :</h6>
                                <div v-for="document in reservation.client_documents" :key="document.id"
                                     class="alert alert-info d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i :class="getFileIcon(document.fichier_path)" class="me-3" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <strong>{{ getDocumentTypeLabel(document.type_document) }}</strong><br>
                                            <small class="text-muted">{{ getFileName(document.fichier_path) }}</small>
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" @click="viewDocument(document.fichier_path)"
                                                class="btn btn-outline-primary"
                                                :disabled="!canPreview(document.fichier_path)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a :href="`/storage/${document.fichier_path}`"
                                           :download="getFileName(document.fichier_path)"
                                           class="btn btn-outline-success">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button type="button" @click="confirmDeleteDocument"
                                                class="btn btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Type de document -->
                            <div class="mb-3">
                                <label for="type_document" class="form-label">
                                    <i class="fas fa-file-alt me-2"></i>Type de document *
                                </label>
                                <select
                                    id="type_document"
                                    v-model="form.type_document"
                                    class="form-select"
                                    :class="{ 'is-invalid': errors.type_document }"
                                    required
                                >
                                    <option value="">-- Sélectionnez un type de document --</option>
                                    <option value="carte_identite">Carte d'identité</option>
                                    <option value="passeport">Passeport</option>
                                    <option value="permis_conduire">Permis de conduire</option>
                                    <option value="justificatif_revenus">Justificatif de revenus</option>
                                    <option value="contrat_travail">Contrat de travail</option>
                                    <option value="autre">Autre document</option>
                                </select>
                                <div v-if="errors.type_document" class="invalid-feedback">
                                    {{ errors.type_document }}
                                </div>
                            </div>

                            <!-- Upload de fichier -->
                            <div class="mb-3">
                                <label for="fichier" class="form-label">
                                    <i class="fas fa-upload me-2"></i>Nouveau document (optionnel)
                                </label>
                                <input
                                    type="file"
                                    id="fichier"
                                    @change="handleFileChange"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.fichier }"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                />
                                <div v-if="errors.fichier" class="invalid-feedback">
                                    {{ errors.fichier }}
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Formats acceptés : PDF, JPG, PNG. Taille max : 5MB
                                </div>
                            </div>

                            <!-- Aperçu du nouveau fichier -->
                            <div v-if="filePreview" class="mb-3">
                                <div class="alert alert-success">
                                    <strong>Nouveau fichier sélectionné :</strong><br>
                                    <i :class="getFileIcon(filePreview.name)" class="me-2"></i>
                                    {{ filePreview.name }} ({{ formatFileSize(filePreview.size) }})
                                </div>
                            </div>

                            <!-- Messages d'erreur globaux -->
                            <div v-if="errors.error" class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ errors.error }}
                            </div>

                            <!-- Boutons d'action -->
                            <div class="d-flex gap-3 justify-content-center">
                                <button
                                    type="submit"
                                    class="btn btn-primary px-4"
                                    :disabled="processing"
                                >
                                    <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
                                    <i v-else class="fas fa-save me-2"></i>
                                    {{ processing ? 'Mise à jour...' : 'Mettre à jour' }}
                                </button>

                                <Link :href="route('reservations.show', reservation.id)"
                                      class="btn btn-outline-secondary px-4">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="text-center mt-4">
                    <Link :href="route('reservations.index')"
                          class="btn btn-outline-primary me-3">
                        <i class="fas fa-list me-2"></i>Mes réservations
                    </Link>
                    <Link :href="route('reservations.show', reservation.id)"
                          class="btn btn-outline-info">
                        <i class="fas fa-eye me-2"></i>Voir la réservation
                    </Link>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation de suppression -->
        <div class="modal fade" id="deleteModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Confirmer la suppression
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer ce document ?</p>
                        <div class="alert alert-warning">
                            <small>
                                <i class="fas fa-info-circle me-2"></i>
                                Cette action est irréversible. Vous devrez fournir un nouveau document.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Annuler
                        </button>
                        <button type="button" class="btn btn-danger" @click="deleteDocument">
                            <i class="fas fa-trash me-2"></i>Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Layout from '@/Pages/Layout.vue'

export default {
    layout: Layout
}
</script>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

// Props
const props = defineProps({
    reservation: {
        type: Object,
        required: true
    },
    bien: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    }
})

// États réactifs
const filePreview = ref(null)
const processing = ref(false)

// Formulaire
const form = useForm({
    type_document: props.reservation.client_documents?.[0]?.type_document || '',
    fichier: null,
    supprimer_document: false
})

// Méthodes utilitaires
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const getFileName = (filePath) => {
    if (!filePath) return 'Document'
    return filePath.split('/').pop() || filePath
}

const getFileIcon = (filePath) => {
    if (!filePath) return 'fas fa-file'
    const extension = filePath.split('.').pop()?.toLowerCase()
    const icons = {
        'pdf': 'fas fa-file-pdf text-danger',
        'jpg': 'fas fa-file-image text-info',
        'jpeg': 'fas fa-file-image text-info',
        'png': 'fas fa-file-image text-info',
        'gif': 'fas fa-file-image text-info'
    }
    return icons[extension] || 'fas fa-file'
}

const getDocumentTypeLabel = (type) => {
    const types = {
        'carte_identite': 'Carte d\'identité',
        'passeport': 'Passeport',
        'permis_conduire': 'Permis de conduire',
        'justificatif_revenus': 'Justificatif de revenus',
        'contrat_travail': 'Contrat de travail',
        'autre': 'Autre document'
    }
    return types[type] || type
}

const canPreview = (filePath) => {
    if (!filePath) return false
    const extension = filePath.split('.').pop()?.toLowerCase()
    return ['pdf', 'jpg', 'jpeg', 'png', 'gif'].includes(extension)
}

const handleFileChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        form.fichier = file
        filePreview.value = {
            name: file.name,
            size: file.size
        }
    } else {
        form.fichier = null
        filePreview.value = null
    }
}

const viewDocument = (filePath) => {
    if (canPreview(filePath)) {
        window.open(`/storage/${filePath}`, '_blank')
    }
}

const confirmDeleteDocument = () => {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'))
    modal.show()
}

const deleteDocument = () => {
    form.supprimer_document = true
    form.fichier = null
    filePreview.value = null

    updateReservation()

    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'))
    if (modal) {
        modal.hide()
    }
}

const updateReservation = () => {
    processing.value = true

    form.post(route('reservations.update', props.reservation.id), {
        forceFormData: true,
        onFinish: () => {
            processing.value = false
        },
        onSuccess: () => {
            filePreview.value = null
            form.supprimer_document = false
        }
    })
}
</script>

<style scoped>
.card {
    border: none;
    border-radius: 12px;
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
}

.btn:disabled {
    opacity: 0.6;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

.form-control:focus,
.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.alert {
    border-radius: 8px;
}

.btn-group .btn {
    border-radius: 4px;
}

.btn-group .btn:not(:last-child) {
    margin-right: 2px;
}
</style>
