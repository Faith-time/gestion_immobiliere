<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- En-tête -->
                <div class="text-center mb-5">
                    <h1 class="h2 text-warning">Modifier la réservation #{{ reservation.id }}</h1>
                    <p class="text-muted">Modifiez les informations de votre réservation</p>
                </div>

                <!-- Récapitulatif du bien -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <img
                                    :src="reservation.bien.image ? `/storage/${reservation.bien.image}` : '/images/placeholder.jpg'"
                                    :alt="reservation.bien.title"
                                    class="img-fluid rounded"
                                />
                            </div>
                            <div class="col-md-9">
                                <h5 class="card-title text-primary">{{ reservation.bien.title }}</h5>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-map-marker-alt me-2"></i>{{ reservation.bien.address }}, {{ reservation.bien.city }}
                                </p>
                                <p class="text-muted mb-2">{{ reservation.bien.category?.name }}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-success mb-0">{{ formatPrice(reservation.bien.price) }} FCFA</span>
                                    <span class="badge bg-info">
                                        Caution: {{ formatPrice(reservation.montant) }} FCFA
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations actuelles -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Informations actuelles
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Statut :</strong>
                                    <span class="badge bg-warning">{{ getStatutText(reservation.statut) }}</span>
                                </p>
                                <p><strong>Date de création :</strong> {{ formatDate(reservation.created_at) }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date de réservation :</strong> {{ formatDate(reservation.date_reservation) }}</p>
                                <p><strong>Montant caution :</strong> {{ formatPrice(reservation.montant) }} FCFA</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de modification -->
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-edit me-2"></i>Modifier les informations
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Date de réservation -->
                        <div class="mb-4">
                            <label class="form-label" for="date_reservation">
                                Date de réservation souhaitée <span class="text-danger">*</span>
                            </label>
                            <input
                                type="date"
                                id="date_reservation"
                                v-model="form.date_reservation"
                                class="form-control"
                                :class="{ 'is-invalid': errors.date_reservation }"
                                :min="getTomorrowDate()"
                            />
                            <div class="form-text">
                                La date doit être postérieure à aujourd'hui
                            </div>
                            <div v-if="errors.date_reservation" class="invalid-feedback">
                                {{ errors.date_reservation }}
                            </div>
                        </div>

                        <!-- Section document actuel -->
                        <div class="mb-4">
                            <h6 class="text-primary">Document actuel</h6>
                            <div class="alert alert-light border">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-1"><strong>Type :</strong> {{ getDocumentTypeText(currentDocument?.type_document) }}</p>
                                        <p class="mb-0"><strong>Statut :</strong>
                                            <span class="badge bg-warning">{{ currentDocument?.statut || 'En attente' }}</span>
                                        </p>
                                    </div>
                                    <small class="text-muted">{{ formatDate(currentDocument?.created_at) }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Option de changement de document -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="changer_document"
                                    v-model="changerDocument"
                                >
                                <label class="form-check-label" for="changer_document">
                                    Je souhaite changer mon document
                                </label>
                            </div>
                        </div>

                        <!-- Nouveau document (conditionnel) -->
                        <div v-if="changerDocument">
                            <!-- Type de document -->
                            <div class="mb-4">
                                <label class="form-label" for="type_document">
                                    Nouveau type de document <span class="text-danger">*</span>
                                </label>
                                <select
                                    id="type_document"
                                    v-model="form.type_document"
                                    class="form-select"
                                    :class="{ 'is-invalid': errors.type_document }"
                                >
                                    <option value="">Sélectionner le type de document...</option>
                                    <option value="cni">Carte Nationale d'Identité</option>
                                    <option value="passeport">Passeport</option>
                                    <option value="justificatif_domicile">Justificatif de domicile</option>
                                    <option value="bulletin_salaire">Bulletin de salaire</option>
                                    <option value="attestation_travail">Attestation de travail</option>
                                    <option value="autre">Autre document</option>
                                </select>
                                <div v-if="errors.type_document" class="invalid-feedback">
                                    {{ errors.type_document }}
                                </div>
                            </div>

                            <!-- Upload du nouveau document -->
                            <div class="mb-4">
                                <label class="form-label" for="fichier">
                                    Nouveau document <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="file"
                                    id="fichier"
                                    @change="handleFileChange"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.fichier }"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                />
                                <div class="form-text">
                                    Formats acceptés: PDF, JPG, PNG (maximum 5MB)
                                </div>
                                <div v-if="errors.fichier" class="invalid-feedback">
                                    {{ errors.fichier }}
                                </div>
                            </div>
                        </div>

                        <!-- Informations importantes -->
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Informations importantes :</h6>
                            <ul class="mb-0 small">
                                <li>Vous ne pouvez modifier que les réservations en attente</li>
                                <li>Le montant de la caution reste fixe ({{ formatPrice(reservation.montant) }} FCFA)</li>
                                <li>Toute modification devra être re-validée par notre équipe</li>
                                <li v-if="changerDocument">Le nouveau document remplacera l'ancien</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between">
                            <Link
                                :href="route('reservations.show', reservation.id)"
                                class="btn btn-outline-secondary"
                            >
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </Link>
                            <div class="d-flex gap-2">
                                <Link
                                    :href="route('reservations.index')"
                                    class="btn btn-outline-primary"
                                >
                                    <i class="fas fa-list me-2"></i>Mes réservations
                                </Link>
                                <button
                                    @click="submitUpdate"
                                    class="btn btn-warning px-4"
                                    :disabled="processing || (!hasChanges())"
                                >
                                    <i class="fas fa-save me-2" v-if="!processing"></i>
                                    <i class="fas fa-spinner fa-spin me-2" v-if="processing"></i>
                                    {{ processing ? 'Sauvegarde...' : 'Sauvegarder les modifications' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Layout from '@/Pages/Layout.vue'
export default { layout: Layout }
</script>

<script setup>
import { Link } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { ref, reactive, computed } from 'vue'

// Props
const props = defineProps({
    reservation: { type: Object, required: true },
    currentDocument: { type: Object, default: null },
    errors: { type: Object, default: () => ({}) }
})

// État réactif
const processing = ref(false)
const changerDocument = ref(false)

const form = reactive({
    date_reservation: props.reservation.date_reservation ?
        new Date(props.reservation.date_reservation).toISOString().split('T')[0] : '',
    type_document: '',
    fichier: null
})

// Méthodes
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getTomorrowDate = () => {
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    return tomorrow.toISOString().split('T')[0]
}

const getStatutText = (statut) => {
    const textes = {
        'en_attente': 'En attente',
        'validee': 'Validée',
        'confirmee': 'Confirmée',
        'annulee': 'Annulée'
    }
    return textes[statut] || statut
}

const getDocumentTypeText = (type) => {
    const types = {
        'cni': 'Carte Nationale d\'Identité',
        'passeport': 'Passeport',
        'justificatif_domicile': 'Justificatif de domicile',
        'bulletin_salaire': 'Bulletin de salaire',
        'attestation_travail': 'Attestation de travail',
        'autre': 'Autre document'
    }
    return types[type] || type
}

const handleFileChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        // Vérifier la taille (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('Le fichier ne doit pas dépasser 5MB')
            event.target.value = ''
            return
        }
        form.fichier = file
    }
}

const hasChanges = () => {
    const originalDate = props.reservation.date_reservation ?
        new Date(props.reservation.date_reservation).toISOString().split('T')[0] : ''

    const dateChanged = form.date_reservation !== originalDate
    const documentChanged = changerDocument.value && form.type_document && form.fichier

    return dateChanged || documentChanged
}

const submitUpdate = () => {
    if (!hasChanges()) {
        alert('Aucune modification détectée')
        return
    }

    if (changerDocument.value && (!form.type_document || !form.fichier)) {
        alert('Veuillez sélectionner un type de document et uploader un fichier')
        return
    }

    processing.value = true

    // Créer FormData pour inclure le fichier si nécessaire
    const formData = new FormData()
    formData.append('date_reservation', form.date_reservation)
    formData.append('_method', 'PUT') // Pour Laravel

    if (changerDocument.value) {
        formData.append('changer_document', '1')
        formData.append('type_document', form.type_document)
        formData.append('fichier', form.fichier)
    }

    // Utiliser router.post avec FormData (Laravel utilise _method pour PUT)
    router.post(route('reservations.update', props.reservation.id), formData, {
        onSuccess: (page) => {
            console.log('Réservation modifiée avec succès')
        },
        onError: (errors) => {
            console.error('Erreurs:', errors)
            let errorMessage = 'Erreurs détectées:\n'
            Object.values(errors).forEach(error => {
                errorMessage += '• ' + error + '\n'
            })
            alert(errorMessage)
        },
        onFinish: () => {
            processing.value = false
        }
    })
}
</script>

<style scoped>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.form-label {
    font-weight: 600;
    color: #495057;
}

.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
}

.alert-warning {
    background-color: #fff3cd;
    border-color: #ffc107;
    color: #856404;
}

.alert-light {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

.btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background-color: #ffca2c;
    border-color: #ffc720;
}

.btn-warning:disabled {
    opacity: 0.6;
}

.gap-2 {
    gap: 0.5rem;
}
</style>
