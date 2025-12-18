<template>
    <div class="ventes-wrapper">
        <div class="container py-5">
            <div class="row">
                <div class="col-12">
                    <!-- Message informatif moderne -->
                    <div class="info-banner mb-4">
                        <div class="info-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="info-content">
                            <strong>Information importante</strong>
                            <p>Seules les transactions dont le paiement a été complété avec succès sont affichées ici. Vous pouvez consulter et signer les contrats une fois le paiement validé.</p>
                        </div>
                    </div>

                    <!-- En-tête moderne avec gradient -->
                    <div class="header-section mb-5">
                        <div class="header-content">
                            <div class="header-left">
                                <h1 class="header-title">
                                    <i class="fas fa-file-signature me-3"></i>
                                    Mes Contrats de Vente
                                </h1>
                                <p class="header-subtitle">Gérez vos contrats de vente immobilières</p>
                            </div>
                            <Link href="/" class="btn-home">
                                <i class="fas fa-home me-2"></i>
                                Accueil
                            </Link>
                        </div>
                    </div>

                    <!-- Onglets modernes (uniquement si l'utilisateur a les deux rôles) -->
                    <div v-if="hasMultipleRoles" class="tabs-container mb-5">
                        <div class="custom-tabs">
                            <button
                                @click="activeTab = 'achats'"
                                :class="['tab-button', { active: activeTab === 'achats' }]"
                            >
                                <div class="tab-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="tab-content">
                                    <span class="tab-label">Mes Achats</span>
                                    <span class="tab-count">{{ ventesAsAcheteur.length }}</span>
                                </div>
                            </button>
                            <button
                                @click="activeTab = 'ventes'"
                                :class="['tab-button', { active: activeTab === 'ventes' }]"
                            >
                                <div class="tab-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="tab-content">
                                    <span class="tab-label">Mes Ventes</span>
                                    <span class="tab-count">{{ ventesAsProprietaire.length }}</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Statistiques modernes -->
                    <div class="stats-grid mb-5">
                        <div class="stat-card stat-confirmed">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number">{{ getVentesByStatus('confirmée').length }}</h3>
                                <p class="stat-label">Confirmées</p>
                            </div>
                        </div>

                        <div class="stat-card stat-cancelled">
                            <div class="stat-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number">{{ getVentesByStatus('annulee').length }}</h3>
                                <p class="stat-label">Annulées</p>
                            </div>
                        </div>

                        <div class="stat-card stat-total">
                            <div class="stat-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number">{{ displayedVentes.length }}</h3>
                                <p class="stat-label">Total</p>
                            </div>
                        </div>
                    </div>

                    <!-- État vide -->
                    <div v-if="displayedVentes.length === 0" class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h4 class="empty-title">Aucune transaction trouvée</h4>
                        <p class="empty-description">
                            Vous n'avez encore effectué aucune transaction immobilière.
                        </p>
                        <Link href="/biens" class="btn-explore">
                            <i class="fas fa-search me-2"></i>Parcourir les biens disponibles
                        </Link>
                    </div>

                    <!-- Grille des ventes -->
                    <div v-else class="ventes-grid">
                        <div v-for="vente in displayedVentes" :key="vente.id" class="vente-card">
                            <!-- En-tête de la carte -->
                            <div class="vente-header">
                                <div class="header-left-content">
                                    <div class="transaction-info">
                                        <i :class="getTransactionIcon(vente)"></i>
                                    </div>
                                    <span class="role-badge" :class="getRoleBadgeClass(vente)">
                                        {{ getUserRoleLabel(vente) }}
                                    </span>
                                </div>
                                <span class="status-badge" :class="getStatusClass(vente.statut)">
                                    {{ getStatusLabel(vente.statut) }}
                                </span>
                            </div>

                            <!-- Corps de la carte -->
                            <div class="vente-body">
                                <div class="property-section">
                                    <div class="property-image">
                                        <img
                                            :src="getBienImageUrl(vente.bien)"
                                            :alt="vente.bien?.title || 'Image du bien'"
                                            @error="handleImageError"
                                        />
                                        <div class="image-overlay">
                                            <i class="fas fa-eye"></i>
                                        </div>
                                    </div>
                                    <div class="property-details">
                                        <h5 class="property-title">{{ vente.bien?.title || 'Bien non spécifié' }}</h5>
                                        <p class="property-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            {{ vente.bien?.address }}, {{ vente.bien?.city }}
                                        </p>
                                        <p class="property-category">
                                            <i class="fas fa-tag"></i>
                                            {{ vente.bien?.category?.name }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Informations principales -->
                                <div class="info-cards">
                                    <div class="info-card price-card">
                                        <div class="info-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="info-details">
                                            <span class="info-label">{{ getPriceLabel(vente) }}</span>
                                            <span class="info-value">{{ formatPrice(vente.prix_vente) }} FCFA</span>
                                        </div>
                                    </div>
                                    <div class="info-card date-card">
                                        <div class="info-icon">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <div class="info-details">
                                            <span class="info-label">{{ getDateLabel(vente) }}</span>
                                            <span class="info-value">{{ formatDate(vente.date_vente) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Autre partie -->
                                <div class="other-party">
                                    <i class="fas fa-user-circle"></i>
                                    <div class="party-info">
                                        <span class="party-label">{{ getOtherPartyLabel(vente) }}</span>
                                        <span class="party-name">{{ getOtherPartyName(vente) }}</span>
                                    </div>
                                </div>

                                <!-- Badge de signature -->
                                <div v-if="vente.signature_stats" class="signature-status">
                                    <span class="signature-badge" :class="getSignatureStatusClass(vente.signature_stats.signature_status)">
                                        <i class="fas fa-pen me-1"></i>
                                        {{ getSignatureStatusText(vente.signature_stats.signature_status) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="vente-footer">
                                <div class="actions-grid">
                                    <Link :href="route('ventes.show', vente.id)" class="action-btn action-view">
                                        <i class="fas fa-eye"></i>
                                        <span>Détails</span>
                                    </Link>

                                    <button @click="previewContract(vente)" class="action-btn action-preview">
                                        <i class="fas fa-file-alt"></i>
                                        <span>Contrat</span>
                                    </button>

                                    <button @click="downloadContract(vente)" class="action-btn action-download">
                                        <i class="fas fa-download"></i>
                                        <span>PDF</span>
                                    </button>

                                    <button
                                        v-if="vente.can_sign"
                                        @click="goToSignature(vente)"
                                        class="action-btn"
                                        :class="vente.signature_stats?.fully_signed ? 'action-signed' : 'action-sign'"
                                    >
                                        <i class="fas" :class="vente.signature_stats?.fully_signed ? 'fa-check-circle' : 'fa-pen'"></i>
                                        <span>{{ getSignatureButtonText(vente) }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import placeholderImage from '@/assets/images/hero_bg_1.jpg'

const props = defineProps({
    ventes: { type: Array, default: () => [] },
    vente: Object,
    userRoles: { type: Array, default: () => [] },
})

const activeTab = ref('achats')

const ventesAsAcheteur = computed(() =>
    props.ventes.filter(vente => vente.user_role_in_vente === 'acheteur')
)

const ventesAsProprietaire = computed(() =>
    props.ventes.filter(vente => vente.user_role_in_vente === 'vendeur')
)

const hasMultipleRoles = computed(() =>
    ventesAsAcheteur.value.length > 0 && ventesAsProprietaire.value.length > 0
)

const displayedVentes = computed(() => {
    if (!hasMultipleRoles.value) return props.ventes
    return activeTab.value === 'achats' ? ventesAsAcheteur.value : ventesAsProprietaire.value
})

const formatPrice = (value) => {
    if (!value) return '0'
    return Number(value).toLocaleString('fr-FR')
}

const formatDate = (dateString) => {
    if (!dateString) return 'Non définie'
    return new Date(dateString).toLocaleDateString('fr-FR')
}

const getStatusLabel = (statut) => ({
    en_cours: 'En cours',
    confirmée: 'Confirmée',
    annulee: 'Annulée'
}[statut] || statut)

const getStatusClass = (statut) => ({
    en_cours: 'status-progress',
    confirmée: 'status-confirmed',
    annulee: 'status-cancelled'
}[statut] || 'status-default')

const getVentesByStatus = (statut) => {
    return props.ventes.filter(vente => vente.status === statut)
}

const getTransactionIcon = (vente) => {
    return vente.user_role_in_vente === 'acheteur'
        ? 'fas fa-shopping-cart'
        : 'fas fa-home'
}

const getUserRoleLabel = (vente) => {
    return vente.user_role_in_vente === 'acheteur' ? 'Acheteur' : 'Vendeur'
}

const getRoleBadgeClass = (vente) => {
    return vente.user_role_in_vente === 'acheteur' ? 'role-buyer' : 'role-seller'
}

const getPriceLabel = (vente) => {
    return vente.user_role_in_vente === 'acheteur' ? 'Prix d\'achat' : 'Prix de vente'
}

const getDateLabel = (vente) => {
    return vente.user_role_in_vente === 'acheteur' ? 'Date d\'achat' : 'Date de vente'
}

const getOtherPartyLabel = (vente) => {
    return vente.user_role_in_vente === 'acheteur' ? 'Vendeur' : 'Acheteur'
}

const getBienImageUrl = (bien) => {
    if (bien?.images && Array.isArray(bien.images) && bien.images.length > 0) {
        return bien.images[0].url  // ✅ Déjà correct
    }
    return placeholderImage
}

const handleImageError = (event) => {
    event.target.src = placeholderImage
}

const getOtherPartyName = (vente) => {
    return vente.user_role_in_vente === 'acheteur'
        ? vente.bien?.proprietaire?.name || 'Non spécifié'
        : vente.acheteur?.name || 'Non spécifié'
}

const previewContract = (vente) => window.open(route('ventes.preview-contract', vente.id), '_blank')
const downloadContract = (vente) => window.open(route('ventes.download-contract', vente.id))
const goToSignature = (vente) => router.visit(route('ventes.signature', vente.id))

const getSignatureButtonText = (vente) => vente.signature_stats?.fully_signed ? 'Signé' : 'Signer'
const getSignatureStatusText = (status) => ({
    non_signe: 'Non signé',
    partiellement_signe: 'Partiellement signé',
    entierement_signe: 'Entièrement signé'
}[status] || 'Statut inconnu')
const getSignatureStatusClass = (status) => ({
    non_signe: 'sig-not-signed',
    partiellement_signe: 'sig-partial',
    entierement_signe: 'sig-complete'
}[status] || 'sig-default')
</script>

<style scoped>
:root {
    --primary: #005F5F;
    --secondary: #00B8A9;
    --accent: #6C63FF;
    --success: #00A884;
    --warning: #FFD166;
    --danger: #FF6B6B;
    --dark: #1E2A2A;
    --light-bg: #efdc07;
}

.ventes-wrapper {
    background: linear-gradient(135deg, #F8FAFA 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

/* Bannière d'information */
.info-banner {
    background: linear-gradient(135deg, #ffffff 0%, #BBDEFB 100%);
    border-left: 5px solid var(--secondary);
    border-radius: 15px;
    padding: 1.5rem;
    display: flex;
    gap: 1.25rem;
    box-shadow: 0 4px 15px rgba(0, 95, 95, 0.08);
}

.info-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--secondary), var(--primary));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1ad9c9;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.info-content strong {
    color: var(--primary);
    font-size: 1.1rem;
    display: block;
    margin-bottom: 0.5rem;
}

.info-content p {
    color: #1565C0;
    margin: 0;
    line-height: 1.6;
}

/* En-tête */
.header-section {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    padding: 2.5rem;
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0, 95, 95, 0.15);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.header-title {
    color: #1ad9c9;
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
}

.header-subtitle {
    color: rgb(239, 220, 7);
    margin: 0;
    font-size: 1.05rem;
}

.btn-home {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    color: #b5eae5;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    border: 2px solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
}

.btn-home:hover {
    background: white;
    color: var(--primary);
    transform: translateY(-2px);
}

/* Onglets modernes */
.tabs-container {
    background: #b5eae5;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 95, 95, 0.08);
}

.custom-tabs {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.tab-button {
    background: linear-gradient(135deg, #b5eae5 0%, #b5eae5 100%);
    border: 2px solid #E8F4F4;
    border-radius: 15px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tab-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 95, 95, 0.1);
}

.tab-button.active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-color: var(--secondary);
}

.tab-icon {
    width: 50px;
    height: 50px;
    background: #b5eae5;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--primary);
}

.tab-button.active .tab-icon {
    background: rgba(255, 255, 255, 0.2);
    color: #b5eae5;
}

.tab-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.tab-label {
    font-weight: 600;
    font-size: 1.05rem;
    color: var(--dark);
}

.tab-button.active .tab-label {
    color: #b5eae5;
}

.tab-count {
    font-size: 0.9rem;
    color: #6C757D;
}

.tab-button.active .tab-count {
    color: rgba(255, 255, 255, 0.9);
}

/* Statistiques */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: #b5eae5;
    border-radius: 16px;
    padding: 1.75rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-progress .stat-icon {
    background: linear-gradient(135deg, #FFD166 0%, #FCA311 100%);
    color: #2E2E2E;
}

.stat-confirmed .stat-icon {
    background: linear-gradient(135deg, #00A884 0%, #00C897 100%);
    color: #c7c7cd;
}

.stat-cancelled .stat-icon {
    background: linear-gradient(135deg, #FF6B6B 0%, #FF8787 100%);
    color: white;
}

.stat-total .stat-icon {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark);
    margin: 0;
    line-height: 1;
}

.stat-label {
    font-size: 0.9rem;
    color: #6C757D;
    margin: 0.5rem 0 0 0;
}

/* État vide */
.empty-state {
    text-align: center;
    padding: 5rem 2rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 95, 95, 0.08);
}

.empty-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 2rem;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: white;
    opacity: 0.5;
}

.empty-title {
    color: var(--dark) !important;
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.empty-description {
    color: #6C757D !important;
    font-size: 1.1rem;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.btn-explore {
    display: inline-flex;
    align-items: center;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgb(26, 217, 201);
}

.btn-explore:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 95, 95, 0.35);
    color: white;
}

/* Grille des ventes */
.ventes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
    gap: 2rem;
}

/* Carte de vente */
.vente-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 95, 95, 0.08);
    transition: all 0.3s ease;
    border: 2px solid #E8F4F4;
}

.vente-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 95, 95, 0.15);
    border-color: var(--secondary);
}

/* En-tête de carte */
.vente-header {
    background: linear-gradient(135deg, #F8FAFA 0%, #E8F4F4 100%);
    padding: 1.25rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #E8F4F4;
}

.header-left-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.transaction-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.transaction-info i {
    font-size: 1.1rem;
    color: var(--primary);
}

.transaction-id {
    font-weight: 700;
    color: var(--dark);
    font-size: 0.95rem;
}

.role-badge {
    padding: 0.35rem 0.85rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.role-buyer {
    background: linear-gradient(135deg, #6C63FF 0%, #8B83FF 100%);
    color: white;
}

.role-seller {
    background: linear-gradient(135deg, #FFD166 0%, #FCA311 100%);
    color: #2E2E2E;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-progress {
    background: rgba(255, 209, 102, 0.9);
    color: #2E2E2E;
}

.status-confirmed {
    background: rgba(0, 168, 132, 0.9);
    color: white;
}

.status-cancelled {
    background: rgba(255, 107, 107, 0.9);
    color: white;
}

/* Corps de carte */
.vente-body {
    padding: 1.75rem 1.5rem;
}

/* Section propriété */
.property-section {
    display: flex;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

.property-image {
    width: 140px;
    height: 140px;
    border-radius: 15px;
    overflow: hidden;
    position: relative;
    flex-shrink: 0;
}

.property-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(0, 95, 95, 0.8), rgba(0, 184, 169, 0.8));
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.property-image:hover .image-overlay {
    opacity: 1;
}

.image-overlay i {
    font-size: 2rem;
    color: white;
}

.property-details {
    flex: 1;
}

.property-title {
    color: var(--primary);
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 0.75rem 0;
}

.property-location,
.property-category {
    color: #6C757D;
    font-size: 0.9rem;
    margin: 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.property-location i,
.property-category i {
    color: var(--secondary);
}

/* Cartes d'information */
.info-cards {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.info-card {
    background: linear-gradient(135deg, #F8FAFA 0%, #E8F4F4 100%);
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 2px solid #E8F4F4;
}

.info-card .info-icon {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.price-card .info-icon {
    background: linear-gradient(135deg, #00A884 0%, #00C897 100%);
    color: white;
}

.date-card .info-icon {
    background: linear-gradient(135deg, #6C63FF 0%, #8B83FF 100%);
    color: white;
}

.info-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.75rem;
    color: #6C757D;
    font-weight: 500;
}

.info-value {
    font-size: 0.95rem;
    color: var(--dark);
    font-weight: 700;
}

/* Autre partie */
.other-party {
    background: linear-gradient(135deg, #F8FAFA 0%, #E8F4F4 100%);
    padding: 1rem 1.25rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    border-left: 4px solid var(--secondary);
}

.other-party i {
    font-size: 2rem;
    color: var(--primary);
}

.party-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.party-label {
    font-size: 0.8rem;
    color: #6C757D;
    font-weight: 500;
}

.party-name {
    font-size: 1rem;
    color: var(--dark);
    font-weight: 700;
}

/* Statut de signature */
.signature-status {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
}

.signature-badge {
    padding: 0.5rem 1.25rem;
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
}

.signature-badge.sig-not-signed {
    background: #FFE5E5;
    color: #D32F2F;
}

.signature-badge.sig-partial {
    background: #FFF4E5;
    color: #F57C00;
}

.signature-badge.sig-complete {
    background: #E8F5E9;
    color: #2E7D32;
}

/* Footer de carte */
.vente-footer {
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #F8FAFA 0%, #E8F4F4 100%);
    border-top: 2px solid #E8F4F4;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 0.9rem 0.5rem;
    border-radius: 12px;
    border: 2px solid transparent;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    background: white;
}

.action-btn i {
    font-size: 1.2rem;
}

.action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.action-view {
    color: var(--primary);
    border-color: #E8F4F4;
}

.action-view:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.action-preview {
    color: var(--accent);
    border-color: #E8E9FF;
}

.action-preview:hover {
    background: var(--accent);
    color: white;
    border-color: var(--accent);
}

.action-download {
    color: var(--success);
    border-color: #C8E6C9;
}

.action-download:hover {
    background: var(--success);
    color: white;
    border-color: var(--success);
}

.action-sign {
    background: linear-gradient(135deg, #FFF4E5 0%, #FFE8CC 100%);
    color: #F57C00;
    border-color: #FFE8CC;
}

.action-sign:hover {
    background: #FCA311;
    color: white;
    border-color: #FCA311;
}

.action-signed {
    background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
    color: var(--success);
    border-color: #C8E6C9;
}

.action-signed:hover {
    background: var(--success);
    color: white;
    border-color: var(--success);
}

/* Responsive */
@media (max-width: 1200px) {
    .ventes-grid {
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    }
}

@media (max-width: 768px) {
    .ventes-grid {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .custom-tabs {
        grid-template-columns: 1fr;
    }

    .header-title {
        font-size: 1.5rem;
    }

    .property-section {
        flex-direction: column;
    }

    .property-image {
        width: 100%;
        height: 200px;
    }

    .actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .info-cards {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .actions-grid {
        grid-template-columns: 1fr;
    }

    .header-section {
        padding: 1.5rem;
    }

    .header-content {
        flex-direction: column;
        align-items: flex-start;
    }

    .vente-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
}

/* Animations */
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

.vente-card {
    animation: fadeInUp 0.5s ease;
}

.vente-card:nth-child(1) { animation-delay: 0.1s; }
.vente-card:nth-child(2) { animation-delay: 0.2s; }
.vente-card:nth-child(3) { animation-delay: 0.3s; }
.vente-card:nth-child(4) { animation-delay: 0.4s; }

/* Effet de brillance sur les badges */
.status-badge,
.role-badge {
    position: relative;
    overflow: hidden;
}

.status-badge::before,
.role-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s ease;
}

.status-badge:hover::before,
.role-badge:hover::before {
    left: 100%;
}

/* Effet de pulse sur les icônes */
@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.stat-icon {
    animation: pulse 2s ease-in-out infinite;
}

.stat-card:hover .stat-icon {
    animation: none;
    transform: scale(1.1);
}
</style>
