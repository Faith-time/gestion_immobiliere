<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    visites: { type: Array, required: true },
    userRoles: { type: Array, default: () => [] },
})

const filtreActif = ref('all')
const showModalConfirmation = ref(false)
const showModalRejet = ref(false)
const showModalEffectuee = ref(false)
const visiteSelectionnee = ref(null)
const processing = ref(false)

const formConfirmation = ref({
    date_visite: '',
    notes: ''
})

const formRejet = ref({
    motif_rejet: ''
})

const formEffectuee = ref({
    commentaire_visite: ''
})

const filtreStatuts = [
    { value: 'all', label: 'Toutes' },
    { value: 'en_attente', label: 'En attente' },
    { value: 'planifiee', label: 'Confirmées' },
    { value: 'effectuee', label: 'Effectuées' },
]

const visitesFiltrees = computed(() => {
    if (filtreActif.value === 'all') return props.visites
    return props.visites.filter(v => v.statut === filtreActif.value)
})

// ✅ NOUVEAU : Vérifier si une visite peut être marquée comme effectuée
const peutMarquerEffectuee = (visite) => {
    if (visite.statut !== 'planifiee') return false

    const dateVisite = new Date(visite.date_visite)
    const dateVisitePlus2h = new Date(dateVisite.getTime() + (2 * 60 * 60 * 1000))
    const maintenant = new Date()

    return maintenant > dateVisitePlus2h
}

// ✅ NOUVEAU : Calculer le temps restant avant de pouvoir marquer effectuée
const getTempsRestant = (visite) => {
    const dateVisite = new Date(visite.date_visite)
    const dateVisitePlus2h = new Date(dateVisite.getTime() + (2 * 60 * 60 * 1000))
    const maintenant = new Date()

    if (maintenant > dateVisitePlus2h) return null

    const diffMs = dateVisitePlus2h - maintenant
    const heures = Math.floor(diffMs / (1000 * 60 * 60))
    const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60))

    if (heures > 0) {
        return `${heures}h ${minutes}min`
    }
    return `${minutes} min`
}

const getCountByStatut = (statut) => {
    if (statut === 'all') return props.visites.length
    return props.visites.filter(v => v.statut === statut).length
}

const getFiltreLabel = () => {
    const filtre = filtreStatuts.find(f => f.value === filtreActif.value)
    return filtre ? filtre.label.toLowerCase() : ''
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatDateShort = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    })
}

const getStatutLabel = (statut) => {
    const labels = {
        'en_attente': 'En attente',
        'planifiee': 'Confirmée',
        'effectuee': 'Effectuée',
        'annulee': 'Annulée',
        'rejetee': 'Rejetée'
    }
    return labels[statut] || statut
}

const getStatutBadgeClass = (statut) => {
    const classes = {
        'en_attente': 'badge bg-warning text-dark',
        'planifiee': 'badge bg-success',
        'effectuee': 'badge bg-primary',
        'annulee': 'badge bg-secondary',
        'rejetee': 'badge bg-danger'
    }
    return classes[statut] || 'badge bg-secondary'
}

const getTypeMandatLabel = (type) => {
    return type === 'vente' ? 'Vente' : 'Location'
}

const getMinDateTime = () => {
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    return tomorrow.toISOString().slice(0, 16)
}

const ouvrirModalConfirmation = (visite) => {
    visiteSelectionnee.value = visite
    formConfirmation.value.date_visite = visite.date_visite.slice(0, 16)
    formConfirmation.value.notes = ''
    showModalConfirmation.value = true
}

const fermerModalConfirmation = () => {
    showModalConfirmation.value = false
    visiteSelectionnee.value = null
    formConfirmation.value = { date_visite: '', notes: '' }
}

const confirmerVisite = () => {
    if (!visiteSelectionnee.value) return

    processing.value = true

    router.post(
        '/visites/action-confirmer',
        {
            visite_id: visiteSelectionnee.value.id,
            date_visite: formConfirmation.value.date_visite,
            notes: formConfirmation.value.notes
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                fermerModalConfirmation()
                processing.value = false
            },
            onError: (errors) => {
                console.error('❌ Erreurs:', errors)
                alert('Erreur: ' + JSON.stringify(errors))
                processing.value = false
            }
        }
    )
}

const ouvrirModalRejet = (visite) => {
    visiteSelectionnee.value = visite
    formRejet.value.motif_rejet = ''
    showModalRejet.value = true
}

const fermerModalRejet = () => {
    showModalRejet.value = false
    visiteSelectionnee.value = null
    formRejet.value = { motif_rejet: '' }
}

const rejeterVisite = () => {
    if (!visiteSelectionnee.value) return

    processing.value = true

    router.post(
        route('visites.rejeter', visiteSelectionnee.value.id),
        formRejet.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                fermerModalRejet()
                processing.value = false
            },
            onError: () => {
                processing.value = false
            }
        }
    )
}

// ✅ NOUVEAU : Ouvrir modal avec vérification
const ouvrirModalEffectuee = (visite) => {
    if (!peutMarquerEffectuee(visite)) {
        const tempsRestant = getTempsRestant(visite)
        alert(`⏰ Cette visite ne peut pas encore être marquée comme effectuée.\n\nVeuillez attendre au moins 2 heures après l'heure prévue de la visite.\n\nTemps restant : ${tempsRestant}`)
        return
    }

    visiteSelectionnee.value = visite
    formEffectuee.value.commentaire_visite = ''
    showModalEffectuee.value = true
}

const fermerModalEffectuee = () => {
    showModalEffectuee.value = false
    visiteSelectionnee.value = null
    formEffectuee.value = { commentaire_visite: '' }
}

const marquerEffectuee = () => {
    if (!visiteSelectionnee.value) return

    processing.value = true

    router.post(
        route('visites.marquer-effectuee', visiteSelectionnee.value.id),
        formEffectuee.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                fermerModalEffectuee()
                processing.value = false
            },
            onError: () => {
                processing.value = false
            }
        }
    )
}
</script>

<template>
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <!-- En-tête -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">
                            <i class="fas fa-calendar-check me-3 text-primary"></i>
                            Gestion des Visites
                        </h2>
                        <p class="text-muted mb-0">
                            {{ visites.length }} visite{{ visites.length > 1 ? 's' : '' }} au total
                        </p>
                    </div>

                    <!-- Filtres rapides -->
                    <div class="btn-group">
                        <button
                            v-for="status in filtreStatuts"
                            :key="status.value"
                            @click="filtreActif = status.value"
                            :class="['btn', filtreActif === status.value ? 'btn-primary' : 'btn-outline-primary']"
                        >
                            {{ status.label }} ({{ getCountByStatut(status.value) }})
                        </button>
                    </div>
                </div>

                <!-- Messages de session -->
                <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ $page.props.flash.success }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ $page.props.flash.error }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <div v-if="$page.props.flash?.warning" class="alert alert-warning alert-dismissible fade show mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ $page.props.flash.warning }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <!-- Tableau des visites -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3">Client</th>
                                    <th class="px-4 py-3">Bien / Appartement</th>
                                    <th class="px-4 py-3">Date souhaitée</th>
                                    <th class="px-4 py-3">Statut</th>
                                    <th class="px-4 py-3 text-end">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-if="visitesFiltrees.length === 0">
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block opacity-50"></i>
                                        Aucune visite {{ filtreActif !== 'all' ? getFiltreLabel() : '' }}
                                    </td>
                                </tr>

                                <tr v-for="visite in visitesFiltrees" :key="visite.id">
                                    <!-- Client -->
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3">
                                                {{ visite.client.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ visite.client.name }}</div>
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope me-1"></i>{{ visite.client.email }}
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-phone me-1"></i>{{ visite.client.telephone }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Bien / Appartement -->
                                    <td class="px-4 py-3">
                                        <div class="fw-semibold mb-1">{{ visite.bien.title }}</div>
                                        <small class="text-muted d-block mb-1">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            {{ visite.bien.address }}, {{ visite.bien.city }}
                                        </small>
                                        <small class="text-muted d-block mb-1">
                                            <i class="fas fa-tag me-1"></i>
                                            {{ visite.bien.type }} • {{ getTypeMandatLabel(visite.bien.type_mandat) }}
                                        </small>

                                        <div v-if="visite.appartement" class="mt-2">
                                            <span class="badge bg-info text-dark">
                                                <i class="fas fa-door-open me-1"></i>
                                                App. {{ visite.appartement.numero }} - {{ visite.appartement.etage_label }}
                                                ({{ visite.appartement.superficie }} m², {{ visite.appartement.pieces }} pièces)
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Date -->
                                    <td class="px-4 py-3">
                                        <div class="fw-medium">
                                            <i class="fas fa-calendar me-2 text-primary"></i>
                                            {{ formatDate(visite.date_visite) }}
                                        </div>
                                        <small class="text-muted">
                                            Demandée le {{ formatDateShort(visite.created_at) }}
                                        </small>
                                    </td>

                                    <!-- Statut -->
                                    <td class="px-4 py-3">
                                        <span :class="getStatutBadgeClass(visite.statut)">
                                            {{ getStatutLabel(visite.statut) }}
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-3 text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button
                                                v-if="visite.statut === 'en_attente'"
                                                @click="ouvrirModalConfirmation(visite)"
                                                class="btn btn-success"
                                                title="Confirmer et envoyer un message"
                                            >
                                                <i class="fas fa-check-circle me-1"></i>
                                                Confirmer par message
                                            </button>

                                            <button
                                                v-if="visite.statut === 'en_attente'"
                                                @click="ouvrirModalRejet(visite)"
                                                class="btn btn-danger"
                                                title="Rejeter"
                                            >
                                                <i class="fas fa-times-circle"></i>
                                            </button>

                                            <!-- ✅ NOUVEAU : Bouton avec état visuel -->
                                            <button
                                                v-if="visite.statut === 'planifiee'"
                                                @click="ouvrirModalEffectuee(visite)"
                                                :class="[
                                                    'btn',
                                                    peutMarquerEffectuee(visite) ? 'btn-primary' : 'btn-secondary'
                                                ]"
                                                :disabled="!peutMarquerEffectuee(visite)"
                                                :title="peutMarquerEffectuee(visite)
                                                    ? 'Marquer comme effectuée'
                                                    : `Disponible dans ${getTempsRestant(visite)}`"
                                            >
                                                <i class="fas fa-check-double me-1"></i>
                                                <span v-if="!peutMarquerEffectuee(visite)" class="badge bg-warning text-dark ms-1">
                                                    {{ getTempsRestant(visite) }}
                                                </span>
                                            </button>

                                            <Link
                                                :href="route('visites.show', visite.id)"
                                                class="btn btn-outline-secondary"
                                                title="Voir détails"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Confirmation -->
        <div v-if="showModalConfirmation" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle me-2"></i>
                            Confirmer la visite
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="fermerModalConfirmation"></button>
                    </div>
                    <form @submit.prevent="confirmerVisite">
                        <div class="modal-body">
                            <div v-if="visiteSelectionnee" class="mb-3">
                                <div class="alert alert-info">
                                    <strong>Client :</strong> {{ visiteSelectionnee.client.name }}<br>
                                    <strong>Bien :</strong> {{ visiteSelectionnee.bien.title }}<br>
                                    <span v-if="visiteSelectionnee.appartement">
                                        <strong>Appartement :</strong> {{ visiteSelectionnee.appartement.numero }} - {{ visiteSelectionnee.appartement.etage_label }}
                                    </span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Date et heure de la visite <span class="text-danger">*</span>
                                </label>
                                <input
                                    v-model="formConfirmation.date_visite"
                                    type="datetime-local"
                                    class="form-control"
                                    required
                                    :min="getMinDateTime()"
                                />
                                <small class="text-muted">
                                    Cette date sera communiquée au client par message
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Notes internes (optionnel)</label>
                                <textarea
                                    v-model="formConfirmation.notes"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Ex: Prévoir les clés, agent disponible..."
                                ></textarea>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Un message automatique sera envoyé au client</strong> avec tous les détails de la visite dans la section Conversations.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="fermerModalConfirmation">
                                Annuler
                            </button>
                            <button type="submit" class="btn btn-success" :disabled="processing">
                                <i class="fas fa-paper-plane me-2"></i>
                                Confirmer et envoyer le message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Rejet -->
        <div v-if="showModalRejet" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-times-circle me-2"></i>
                            Rejeter la visite
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="fermerModalRejet"></button>
                    </div>
                    <form @submit.prevent="rejeterVisite">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Motif du rejet <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    v-model="formRejet.motif_rejet"
                                    class="form-control"
                                    rows="4"
                                    required
                                    placeholder="Expliquez pourquoi cette visite est rejetée..."
                                ></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="fermerModalRejet">
                                Annuler
                            </button>
                            <button type="submit" class="btn btn-danger" :disabled="processing">
                                <i class="fas fa-times-circle me-2"></i>
                                Rejeter la visite
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ✅ NOUVEAU : Modal Marquer Effectuée -->
        <div v-if="showModalEffectuee" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-check-double me-2"></i>
                            Marquer la visite comme effectuée
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="fermerModalEffectuee"></button>
                    </div>
                    <form @submit.prevent="marquerEffectuee">
                        <div class="modal-body">
                            <div v-if="visiteSelectionnee" class="mb-3">
                                <div class="alert alert-info">
                                    <strong>Client :</strong> {{ visiteSelectionnee.client.name }}<br>
                                    <strong>Bien :</strong> {{ visiteSelectionnee.bien.title }}<br>
                                    <strong>Date de visite :</strong> {{ formatDate(visiteSelectionnee.date_visite) }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Commentaire sur la visite (optionnel)
                                </label>
                                <textarea
                                    v-model="formEffectuee.commentaire_visite"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Ex: Le client est très intéressé, souhaite réserver..."
                                ></textarea>
                                <small class="text-muted">
                                    Ces notes seront visibles uniquement par l'équipe administrative
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="fermerModalEffectuee">
                                Annuler
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="processing">
                                <i class="fas fa-check-double me-2"></i>
                                Confirmer que la visite est effectuée
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.avatar-circle {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
}

.table > :not(caption) > * > * {
    padding: 1rem;
    vertical-align: middle;
}

.modal.show {
    display: block;
}

.btn-group-sm .btn {
    padding: 0.4rem 0.8rem;
    font-size: 0.875rem;
}

/* ✅ Style pour le bouton désactivé */
.btn-secondary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary:disabled .badge {
    font-size: 0.7rem;
    font-weight: bold;
}
</style>
