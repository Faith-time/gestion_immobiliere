<template>
    <Transition name="modal">
        <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
            <div class="modal-container">
                <div class="modal-header">
                    <h2 class="modal-title">
                        <i class="fas fa-file-alt"></i>
                        Détails Complets du Dossier
                    </h2>
                    <button @click="$emit('close')" class="close-button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div v-if="dossier" class="details-container">
                        <!-- Statut -->
                        <div class="status-banner" :class="dossier.is_complete ? 'complete' : 'incomplete'">
                            <i :class="['fas', dossier.is_complete ? 'fa-check-circle' : 'fa-exclamation-circle']"></i>
                            <span>Dossier {{ dossier.is_complete ? 'complet' : 'incomplet' }}</span>
                        </div>

                        <!-- Informations personnelles -->
                        <div class="details-section">
                            <h4 class="section-header">
                                <i class="fas fa-user text-primary"></i>
                                Informations Personnelles
                            </h4>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Profession</span>
                                    <span class="detail-value">{{ dossier.profession || 'Non renseigné' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Numéro CNI</span>
                                    <span class="detail-value">{{ dossier.numero_cni || 'Non renseigné' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Situation familiale</span>
                                    <span class="detail-value">{{ formatSituationFamiliale(dossier.situation_familiale) }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Nombre de personnes</span>
                                    <span class="detail-value">{{ dossier.nombre_personnes || 'Non renseigné' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Revenus mensuels</span>
                                    <span class="detail-value">{{ formatRevenus(dossier.revenus_mensuels) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Contact d'urgence -->
                        <div class="details-section">
                            <h4 class="section-header">
                                <i class="fas fa-phone text-warning"></i>
                                Contact d'Urgence
                            </h4>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Personne à contacter</span>
                                    <span class="detail-value">{{ dossier.personne_contact || 'Non renseigné' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Téléphone</span>
                                    <span class="detail-value">{{ dossier.telephone_contact || 'Non renseigné' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Critères de recherche -->
                        <div class="details-section">
                            <h4 class="section-header">
                                <i class="fas fa-search text-success"></i>
                                Critères de Recherche
                            </h4>
                            <div class="details-grid">
                                <div class="detail-item full-width">
                                    <span class="detail-label">Type de logement souhaité</span>
                                    <div class="type-logement-tags">
                                        <span
                                            v-for="type in (dossier.type_logement || [])"
                                            :key="type"
                                            class="type-tag"
                                        >
                                            {{ formatTypeLogement(type) }}
                                        </span>
                                        <span v-if="!dossier.type_logement || dossier.type_logement.length === 0" class="text-muted">
                                            Aucun type spécifié
                                        </span>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Chambres</span>
                                    <span class="detail-value">{{ dossier.nbchambres || '0' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Salons</span>
                                    <span class="detail-value">{{ dossier.nbsalons || '0' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Cuisines</span>
                                    <span class="detail-value">{{ dossier.nbcuisines || '0' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Salles de bain</span>
                                    <span class="detail-value">{{ dossier.nbsalledebains || '0' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Quartier souhaité</span>
                                    <span class="detail-value">{{ dossier.quartier_souhaite || 'Indifférent' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Date d'entrée souhaitée</span>
                                    <span class="detail-value">
                                        {{ dossier.date_entree_souhaitee ? formatDate(dossier.date_entree_souhaitee) : 'Flexible' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Documents -->
                        <div class="details-section">
                            <h4 class="section-header">
                                <i class="fas fa-paperclip text-info"></i>
                                Documents Joints
                            </h4>
                            <div class="documents-list">
                                <div v-if="dossier.carte_identite_path" class="document-card">
                                    <div class="document-icon">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <div class="document-info">
                                        <span class="document-name">Carte d'identité</span>
                                        <small class="text-muted">Document officiel</small>
                                    </div>
                                    <a
                                        :href="`/storage/${dossier.carte_identite_path}`"
                                        target="_blank"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                </div>
                                <div v-else class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Aucun document fourni
                                </div>
                            </div>
                        </div>

                        <!-- Métadonnées -->
                        <div class="details-section">
                            <h4 class="section-header">
                                <i class="fas fa-info-circle text-secondary"></i>
                                Informations Système
                            </h4>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Date de création</span>
                                    <span class="detail-value">{{ formatDate(dossier.created_at) }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Dernière modification</span>
                                    <span class="detail-value">{{ formatDate(dossier.updated_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button @click="$emit('close')" class="btn btn-primary">
                        <i class="fas fa-times"></i> Fermer
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
defineProps({
    show: Boolean,
    dossier: { type: Object, default: null }
})

defineEmits(['close'])

const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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

const formatTypeLogement = (type) => {
    const types = {
        'chambre_simple': 'Chambre simple',
        'salon': 'Salon',
        'studio': 'Studio',
        '2_chambres_salon': '2 Chambres salon',
        '3_chambres_salon': '3 Chambres salon',
        '4_chambres_salon': '4 Chambres salon',
        'magasin': 'Magasin',
        'autres': 'Autres'
    }
    return types[type] || type
}
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
    overflow-y: auto;
}

.modal-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.modal-header {
    padding: 2rem;
    border-bottom: 2px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    border-radius: 20px 20px 0 0;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.close-button {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.3s;
}

.close-button:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 2rem;
}

.details-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.status-banner {
    padding: 1rem 1.5rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 1rem;
    font-weight: 600;
    font-size: 1.1rem;
}

.status-banner.complete {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.status-banner.incomplete {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
}

.details-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
}

.section-header {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.25rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-item.full-width {
    grid-column: 1 / -1;
}

.detail-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 1rem;
    color: #212529;
    font-weight: 500;
}

.type-logement-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.type-tag {
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.documents-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.document-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.document-icon {
    width: 50px;
    height: 50px;
    background: #e7f3ff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0066cc;
    font-size: 1.5rem;
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

.modal-footer {
    padding: 1.5rem 2rem;
    border-top: 2px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    background: #f8f9fa;
    border-radius: 0 0 20px 20px;
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
