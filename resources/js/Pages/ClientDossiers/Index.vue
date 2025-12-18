<template>
    <Layout>
        <Head title="Mes Dossiers" />

        <div class="container-fluid py-4">
            <!-- En-tête -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="h3 mb-2">
                            <i class="fas fa-folder-open text-primary me-2"></i>
                            Ma Fiche de Dépôt
                        </h1>
                        <p class="text-muted mb-0">Vos critères et préférences de logement</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <button
                            v-if="!hasDossier"
                            @click="showCreateModal = true"
                            class="btn btn-primary btn-lg shadow-sm"
                        >
                            <i class="fas fa-plus-circle me-2"></i>
                            Créer mon dossier
                        </button>
                    </div>
                </div>
            </div>

            <!-- Messages Flash -->
            <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show mb-4">
                <i class="fas fa-check-circle me-2"></i>{{ $page.props.flash.success }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="fas fa-exclamation-circle me-2"></i>{{ $page.props.flash.error }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <!-- Contenu Principal -->
            <div class="row">
                <!-- Pas de dossier -->
                <div v-if="!hasDossier" class="col-12">
                    <div class="empty-state-card">
                        <div class="empty-state-icon">
                            <i class="fas fa-folder-plus"></i>
                        </div>
                        <h3>Aucun dossier trouvé</h3>
                        <p class="text-muted mb-4">
                            Créez votre dossier client pour faciliter vos démarches de location ou d'achat
                        </p>
                        <button @click="showCreateModal = true" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus-circle me-2"></i>
                            Créer mon premier dossier
                        </button>
                    </div>
                </div>

                <!-- Affichage du dossier -->
                <div v-else class="col-12">
                    <!-- Carte du dossier -->
                    <div class="dossier-card">
                        <!-- En-tête de la carte -->
                        <div class="dossier-header">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="dossier-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-1">
                                            Ma Demande de Logement
                                            <span v-if="isRecentlyUpdated" class="badge bg-info ms-2" style="font-size: 0.6rem;">
                                            Mis à jour récemment
                                        </span>
                                        </h4>
                                        <p class="text-muted mb-0">
                                            <small>
                                                <i class="far fa-calendar-plus me-1"></i>
                                                Créé le {{ formatDate(dossier.created_at) }}
                                                <span class="mx-2">•</span>
                                                <i class="far fa-edit me-1"></i>
                                                Dernière modification : {{ formatDate(dossier.updated_at) }}
                                            </small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions rapides -->
                        <div class="dossier-actions">
                            <button @click="showDetails" class="action-btn btn-info">
                                <i class="fas fa-eye"></i>
                                <span>Voir détails</span>
                            </button>
                            <button @click="showEditModal = true" class="action-btn btn-warning">
                                <i class="fas fa-edit"></i>
                                <span>Modifier</span>
                            </button>
                            <button @click="confirmDelete" class="action-btn btn-danger">
                                <i class="fas fa-trash"></i>
                                <span>Supprimer</span>
                            </button>
                        </div>

                        <!-- Résumé du dossier -->
                        <div class="dossier-summary">
                            <div class="row g-3">
                                <!-- Informations personnelles -->
                                <div class="col-md-6">
                                    <div class="summary-section">
                                        <h6 class="section-title">
                                            <i class="fas fa-user text-primary"></i>
                                            Informations Personnelles
                                        </h6>
                                        <div class="info-grid">
                                            <div class="info-item">
                                                <span class="info-label">Profession</span>
                                                <span class="info-value">{{ dossier.profession || 'Non renseigné' }}</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">N° CNI</span>
                                                <span class="info-value">{{ dossier.numero_cni || 'Non renseigné' }}</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Situation familiale</span>
                                                <span class="info-value">{{ formatSituationFamiliale(dossier.situation_familiale) }}</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Revenus mensuels</span>
                                                <span class="info-value">{{ formatRevenus(dossier.revenus_mensuels) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Critères de recherche -->
                                <div class="col-md-6">
                                    <div class="summary-section">
                                        <h6 class="section-title">
                                            <i class="fas fa-search text-success"></i>
                                            Critères de Recherche
                                        </h6>
                                        <div class="info-grid">
                                            <div class="info-item">
                                                <span class="info-label">Type de logement</span>
                                                <span class="info-value">
                                                    {{ formatTypeLogement(dossier.type_logement) }}
                                                </span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Quartier souhaité</span>
                                                <span class="info-value">{{ dossier.quartier_souhaite || 'Indifférent' }}</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Date d'entrée</span>
                                                <span class="info-value">
                                                    {{ dossier.date_entree_souhaitee ? formatDate(dossier.date_entree_souhaitee) : 'Flexible' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact d'urgence -->
                                <div class="col-12">
                                    <div class="summary-section">
                                        <h6 class="section-title">
                                            <i class="fas fa-phone text-warning"></i>
                                            Contact d'Urgence
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <span class="info-label">Personne à contacter</span>
                                                    <span class="info-value">{{ dossier.personne_contact || 'Non renseigné' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <span class="info-label">Téléphone</span>
                                                    <span class="info-value">{{ dossier.telephone_contact || 'Non renseigné' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Documents -->
                                <div class="col-12">
                                    <div class="summary-section">
                                        <h6 class="section-title">
                                            <i class="fas fa-paperclip text-info"></i>
                                            Documents Joints
                                        </h6>
                                        <div class="documents-list">
                                            <div v-if="dossier.carte_identite_path" class="document-item">
                                                <div class="document-icon">
                                                    <i class="fas fa-id-card"></i>
                                                </div>
                                                <div class="document-info">
                                                    <span class="document-name">Carte d'identité</span>
                                                    <small class="text-muted">Document officiel</small>
                                                </div>
                                                <button @click="viewDocument('carte_identite')" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Voir
                                                </button>
                                            </div>
                                            <div v-else class="alert alert-warning mb-0">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Carte d'identité non fournie
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de création/modification -->
        <DossierFormModal
            :show="showCreateModal || showEditModal"
            :dossier="showEditModal ? dossier : null"
            :is-edit="showEditModal"
            @close="closeModals"
            @success="handleSuccess"
        />

        <!-- Modal de détails complets -->
        <DossierDetailsModal
            :show="showDetailsModal"
            :dossier="dossier"
            @close="showDetailsModal = false"
        />

        <!-- Modal de confirmation de suppression -->
        <ConfirmDeleteModal
            :show="showDeleteModal"
            @close="showDeleteModal = false"
            @confirm="deleteDossier"
        />
    </Layout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Pages/Layout.vue'
import DossierFormModal from '../ClientDossiers/Edit.vue'
import DossierDetailsModal from '../ClientDossiers/Show.vue'
import ConfirmDeleteModal from '../ClientDossiers/ConfirmDelete.vue'

const props = defineProps({
    dossiers: { type: Array, default: () => [] },
    dossier: { type: Object, default: null }
})

// États
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDetailsModal = ref(false)
const showDeleteModal = ref(false)

// Computed
const hasDossier = computed(() => props.dossier !== null)
const dossier = computed(() => props.dossier)

// Méthodes de formatage
const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const formatSituationFamiliale = (situation) => {
    const situations = {
        'celibataire': 'Célibataire',
        'marie': 'Marié(e)'
    }
    return situations[situation] || 'Non renseigné'
}

const formatRevenus = (revenus) => {
    const revenuLabels = {
        'plus_100000': '+ 100 000 FCFA',
        'plus_200000': '+ 200 000 FCFA',
        'plus_300000': '+ 300 000 FCFA',
        'plus_500000': '+ 500 000 FCFA'
    }
    return revenuLabels[revenus] || 'Non renseigné'
}

// ✅ NOUVELLE VERSION
const formatTypeLogement = (type) => {
    if (!type) return 'Non défini'

    const typeLabels = {
        'appartement': 'Appartement',
        'studio': 'Studio'
    }

    return typeLabels[type] || type
}
// Actions
const closeModals = () => {
    showCreateModal.value = false
    showEditModal.value = false
}

const handleSuccess = () => {
    closeModals()
    router.reload({ only: ['dossier', 'dossiers'] })
}

const showDetails = () => {
    showDetailsModal.value = true
}

const viewDocument = (type) => {
    if (type === 'carte_identite' && dossier.value?.carte_identite_path) {
        window.open(`/storage/${dossier.value.carte_identite_path}`, '_blank')
    }
}

const downloadDossier = () => {
    // Logique de téléchargement du dossier complet
    alert('Fonctionnalité de téléchargement à implémenter')
}

const confirmDelete = () => {
    showDeleteModal.value = true
}

const deleteDossier = () => {
    router.delete(route('client-dossiers.destroy', dossier.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false
        }
    })
}
</script>

<style scoped>
.page-header {
    background: linear-gradient(135deg, #1ad9c9 0%, #1ad9c9 100%);
    padding: 2rem;
    border-radius: 16px;
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.empty-state-card {
    background: white;
    border-radius: 20px;
    padding: 4rem 2rem;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.empty-state-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-state-icon i {
    font-size: 3rem;
    color: white;
}

.dossier-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.dossier-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 2rem;
    border-bottom: 2px solid #dee2e6;
}

.dossier-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.5rem;
}

.dossier-icon i {
    font-size: 1.8rem;
    color: white;
}

.dossier-status .badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    font-weight: 600;
}

.dossier-actions {
    display: flex;
    gap: 1rem;
    padding: 1.5rem 2rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    flex-wrap: wrap;
}

.action-btn {
    flex: 1;
    min-width: 150px;
    padding: 0.75rem 1.5rem;
    border: 2px solid transparent;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
}

.action-btn.btn-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
}

.action-btn.btn-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    color: white;
}

.action-btn.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #1ad9c9 100%);
    color: white;
}

.action-btn.btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.dossier-summary {
    padding: 2rem;
}

.summary-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    height: 100%;
}

.section-title {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-grid {
    display: grid;
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 1rem;
    color: #212529;
    font-weight: 500;
}

.documents-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.document-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.document-icon {
    width: 40px;
    height: 40px;
    background: #e7f3ff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0066cc;
}

.document-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.document-name {
    font-weight: 600;
    color: #212529;
}

@media (max-width: 768px) {
    .dossier-actions {
        flex-direction: column;
    }

    .action-btn {
        width: 100%;
    }
}
</style>
