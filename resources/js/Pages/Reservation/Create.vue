<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- En-tête -->
                <div class="text-center mb-5">
                    <h1 class="h2 text-primary">Réservation de propriété</h1>
                    <p class="text-muted">Confirmez votre réservation pour cette propriété</p>
                </div>

                <!-- Récapitulatif du bien -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <img
                                    :src="bien.image ? `/storage/${bien.image}` : '/images/placeholder.jpg'"
                                    :alt="bien.title"
                                    class="img-fluid rounded"
                                />
                            </div>
                            <div class="col-md-9">
                                <h5 class="card-title text-primary">{{ bien.title }}</h5>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-map-marker-alt me-2"></i>{{ bien.address }}, {{ bien.city }}
                                </p>
                                <p class="text-muted mb-2">{{ bien.category?.name }}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-success mb-0">{{ formatPrice(bien.price) }} FCFA</span>
                                    <span class="badge bg-info">
                                        Caution: {{ formatPrice(calculateReservationAmount()) }} FCFA
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de réservation et upload document -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-file-upload me-2"></i>Document obligatoire
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Type de document -->
                        <div class="mb-4">
                            <label class="form-label" for="type_document">
                                Type de document <span class="text-danger">*</span>
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

                        <!-- Upload du document -->
                        <div class="mb-4">
                            <label class="form-label" for="fichier">
                                Document <span class="text-danger">*</span>
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

                        <!-- Informations importantes -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Informations importantes :</h6>
                            <ul class="mb-0 small">
                                <li>Un document obligatoire est requis pour valider votre réservation</li>
                                <li>Caution de {{ formatPrice(calculateReservationAmount()) }} FCFA
                                    ({{ props.bien.mandat?.type_mandat === 'vente' ? '5% du prix de vente' : '1 mois de loyer' }})
                                </li>
                                <li>Votre réservation sera validée par notre équipe</li>
                                <li>Vous pourrez payer après validation</li>
                                <li>Date de réservation: {{ formatDate(new Date()) }}</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between">
                            <Link
                                :href="route('biens.show', bien.id)"
                                class="btn btn-outline-secondary"
                            >
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </Link>
                            <button
                                @click="submitReservation"
                                class="btn btn-primary px-4"
                                :disabled="processing || !form.type_document || !form.fichier"
                            >
                                <i class="fas fa-check me-2" v-if="!processing"></i>
                                <i class="fas fa-spinner fa-spin me-2" v-if="processing"></i>
                                {{ processing ? 'Traitement...' : 'Confirmer la réservation' }}
                            </button>
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
import { ref, reactive } from 'vue'

// Props
const props = defineProps({
    bien: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) }
})

// État réactif
const processing = ref(false)
const form = reactive({
    bien_id: props.bien.id,
    type_document: '',
    fichier: null
})

// Fonction pour calculer le montant de réservation
const calculateReservationAmount = () => {
    console.log('=== DEBUG Frontend ===');
    console.log('Bien:', props.bien);
    console.log('Mandat:', props.bien.mandat);
    console.log('Prix:', props.bien.price);

    if (!props.bien.mandat) {
        console.log('Pas de mandat trouvé, retour 25000');
        return 25000;
    }

    console.log('Type mandat:', props.bien.mandat.type_mandat);

    switch (props.bien.mandat.type_mandat) {
        case 'vente':
            // 5% du prix de vente
            const montantVente = props.bien.price * 0.05;
            console.log('Vente - Montant calculé:', montantVente);
            return montantVente;

        case 'gestion_locative':
            // 1 mois de loyer
            console.log('Location - Montant calculé:', props.bien.price);
            return props.bien.price;

        default:
            console.log('Type inconnu, retour 25000');
            return 25000; // Montant par défaut
    }
}

// Fonction pour formater les prix
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

// Fonction pour formater les dates
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

// Fonction pour gérer le changement de fichier
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

// Fonction pour soumettre la réservation
const submitReservation = () => {
    if (!form.type_document || !form.fichier) {
        alert('Veuillez remplir tous les champs obligatoires')
        return
    }

    processing.value = true

    // Créer FormData pour inclure le fichier
    const formData = new FormData()
    formData.append('bien_id', form.bien_id)
    formData.append('type_document', form.type_document)
    formData.append('fichier', form.fichier)

    // Utiliser router.post avec FormData - Inertia gère automatiquement le CSRF
    router.post(route('reservations.store'), formData, {
        onSuccess: (page) => {
            console.log('Réservation créée avec succès')
            // Inertia gèrera automatiquement la redirection
        },
        onError: (errors) => {
            console.error('Erreurs:', errors)
            // Les erreurs seront automatiquement disponibles via les props errors

            // Afficher les erreurs à l'utilisateur
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

.alert-info {
    background-color: #e3f2fd;
    border-color: #2196f3;
    color: #1976d2;
}

.btn-primary {
    background-color: #17a2b8;
    border-color: #17a2b8;
}

.btn-primary:hover {
    background-color: #138496;
    border-color: #117a8b;
}

.btn-primary:disabled {
    opacity: 0.6;
}
</style>
