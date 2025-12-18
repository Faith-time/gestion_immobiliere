<template>
    <div class="locations-wrapper">
        <div class="container py-5">
            <div class="row">
                <div class="col-12">
                    <!-- En-tête avec gradient -->
                    <div class="header-section mb-5">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h2 class="header-title mb-2">
                                    <i class="fas fa-key me-3"></i>Mes Contrats de Location
                                </h2>
                                <p class="header-subtitle mb-0">
                                    Gérez tous vos contrats en un seul endroit
                                </p>
                            </div>
                            <div class="total-badge">
                                <span class="total-number">{{ locations.length }}</span>
                                <span class="total-label">location{{ locations.length > 1 ? 's' : '' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Messages de session -->
                    <div v-if="$page.props.flash?.success" class="alert alert-success-custom alert-dismissible fade show mb-4">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ $page.props.flash.success }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>

                    <div v-if="$page.props.flash?.error" class="alert alert-danger-custom alert-dismissible fade show mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ $page.props.flash.error }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>

                    <!-- Statistiques rapides -->
                    <div class="stats-grid mb-5">
                        <div class="stat-card stat-active">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number">{{ getLocationsByStatus('active').length }}</h3>
                                <p class="stat-label">Locations Actives</p>
                            </div>
                        </div>

                        <div class="stat-card stat-completed">
                            <div class="stat-icon">
                                <i class="fas fa-flag-checkered"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number">{{ getLocationsByStatus('terminee').length }}</h3>
                                <p class="stat-label">Terminées</p>
                            </div>
                        </div>

                        <div class="stat-card stat-total">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number">{{ locations.length }}</h3>
                                <p class="stat-label">Total</p>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ LISTE DES LOCATIONS AVEC VÉRIFICATIONS -->
                    <div v-if="locations.length > 0" class="locations-grid">
                        <div v-for="location in locations" :key="location.id" class="location-card">
                            <!-- ✅ VÉRIFICATION : Afficher seulement si bien existe -->
                            <template v-if="location.bien && location.bien.titre">
                                <!-- Badge statut flottant -->
                                <div class="status-badge-float" :class="getStatutClass(location.statut)">
                                    {{ getStatutLabel(location.statut) }}
                                </div>

                                <!-- En-tête avec infos bien -->
                                <div class="card-header-custom">
                                    <div class="property-image-container">
                                        <img
                                            :src="getBienImageUrl(location.bien)"
                                            :alt="location.bien.titre"
                                            class="property-image"
                                            @error="handleImageError"
                                        />
                                    </div>

                                    <div class="property-info">
                                        <h5 class="property-title">
                                            {{ location.bien.titre }}
                                        </h5>
                                        <p class="property-address">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            {{ location.bien.adresse }}, {{ location.bien.ville }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Corps de la carte -->
                                <div class="card-body-custom">
                                    <!-- Informations principales -->
                                    <div class="info-grid">
                                        <!-- ✅ Locataire avec vérification -->
                                        <div class="info-item" v-if="location.locataire">
                                            <div class="info-icon tenant">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="info-details">
                                                <span class="info-label">Locataire</span>
                                                <span class="info-value">{{ location.locataire.name || 'Non défini' }}</span>
                                            </div>
                                        </div>

                                        <!-- ✅ Loyer avec vérification -->
                                        <div class="info-item">
                                            <div class="info-icon price">
                                                <i class="fas fa-coins"></i>
                                            </div>
                                            <div class="info-details">
                                                <span class="info-label">Loyer mensuel</span>
                                                <span class="info-value price-value">
                                                    {{ formatPrice(location.montant || 0) }} FCFA
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Dates -->
                                        <div class="info-item">
                                            <div class="info-icon date-start">
                                                <i class="fas fa-calendar-check"></i>
                                            </div>
                                            <div class="info-details">
                                                <span class="info-label">Date début</span>
                                                <span class="info-value">{{ formatDate(location.date_debut) }}</span>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-icon date-end">
                                                <i class="fas fa-calendar-times"></i>
                                            </div>
                                            <div class="info-details">
                                                <span class="info-label">Date fin</span>
                                                <span class="info-value">{{ formatDate(location.date_fin) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Durée restante -->
                                    <div class="duration-info">
                                        <i class="fas fa-hourglass-half me-2"></i>
                                        <strong>{{ getDureeRestante(location) }}</strong>
                                        <span class="text-muted ms-1">sur {{ getDureeTotal(location) }} mois</span>
                                    </div>

                                    <!-- Barre de progression -->
                                    <div class="progress-section">
                                        <div class="progress-header">
                                            <span class="progress-label">Progression du contrat</span>
                                            <span class="progress-percentage">{{ getProgressPercentage(location) }}%</span>
                                        </div>
                                        <div class="progress-bar-custom">
                                            <div
                                                class="progress-fill"
                                                :class="getProgressClass(location.statut)"
                                                :style="`width: ${getProgressPercentage(location)}%`"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Informations de signature -->
                                    <div v-if="location.signature_stats" class="signature-info">
                                        <div class="signature-badges">
                                            <span class="signature-badge" :class="getSignatureStatusClass(location.signature_stats.signature_status)">
                                                <i class="fas fa-pen me-1"></i>
                                                {{ getSignatureStatusText(location.signature_stats.signature_status) }}
                                            </span>
                                            <span v-if="location.signature_stats.locataire_signed" class="signature-badge signed">
                                                <i class="fas fa-check me-1"></i>Locataire signé
                                            </span>
                                            <span v-if="location.signature_stats.bailleur_signed" class="signature-badge signed">
                                                <i class="fas fa-check me-1"></i>Bailleur signé
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="actions-grid">
                                        <Link
                                            v-if="location.bien.id"
                                            :href="route('biens.show', location.bien.id)"
                                            class="action-btn action-view">
                                            <i class="fas fa-eye"></i>
                                            <span>Voir le bien</span>
                                        </Link>

                                        <Link
                                            v-if="isLocataire(location) && location.statut === 'active'"
                                            :href="route('locations.mes-loyers')"
                                            class="action-btn action-manage">
                                            <i class="fas fa-wallet"></i>
                                            <span>Mes loyers</span>
                                        </Link>

                                        <button
                                            @click="previewPdf(location)"
                                            class="action-btn action-preview">
                                            <i class="fas fa-file-alt"></i>
                                            <span>Aperçu</span>
                                        </button>

                                        <button
                                            @click="downloadContract(location)"
                                            class="action-btn action-download">
                                            <i class="fas fa-download"></i>
                                            <span>PDF</span>
                                        </button>

                                        <button
                                            @click="goToSignature(location)"
                                            class="action-btn"
                                            :class="location.signature_stats?.fully_signed ? 'action-signed' : 'action-sign'">
                                            <i class="fas" :class="location.signature_stats?.fully_signed ? 'fa-check-circle' : 'fa-pen'"></i>
                                            <span>{{ getSignatureButtonText(location) }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="card-footer-custom">
                                    <i class="fas fa-clock me-2"></i>
                                    Créée le {{ formatDate(location.created_at) }}
                                </div>
                            </template>

                            <!-- ✅ FALLBACK SI BIEN MANQUANT -->
                            <template v-else>
                                <div class="alert alert-warning m-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Données incomplètes</strong>
                                    <p class="mb-0 small">Le bien associé à cette location est introuvable.</p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- État vide -->
                    <div v-else class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <h4 class="empty-title">Aucune location trouvée</h4>
                        <p class="empty-description">
                            Vous n'avez pas encore de contrat de location.<br>
                            Explorez nos biens disponibles à la location.
                        </p>
                        <Link href="/" class="btn-explore">
                            <i class="fas fa-search me-2"></i>Explorer les biens
                        </Link>
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
import { Link, router } from '@inertiajs/vue3'
import { onMounted } from 'vue'
import {route} from "ziggy-js";

const props = defineProps({
    locations: { type: Array, default: () => [] },
    userRoles: { type: Array, default: () => [] },
    location: Object,
})

onMounted(() => {
    console.log('📦 Locations reçues:', props.locations)
    console.log('📊 Nombre de locations:', props.locations.length)

    // Vérifier les données de chaque location
    props.locations.forEach((loc, index) => {
        console.log(`Location ${index + 1}:`, {
            id: loc.id,
            has_bien: !!loc.bien,
            bien_titre: loc.bien?.titre,
            montant: loc.montant
        })
    })
})

const isLocataire = (location) => {
    return location.user_role_in_location === 'locataire'
}

// ✅ FORMATAGE SÉCURISÉ
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price || 0)
}

const formatDate = (dateString) => {
    if (!dateString) return 'Non spécifié'
    try {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        })
    } catch (e) {
        return 'Date invalide'
    }
}


const getStatutLabel = (statut) => {
    const labels = {
        'en_attente_paiement': 'En attente',
        'active': 'Active',
        'terminee': 'Terminée',
        'en_retard': 'En retard',
        'suspendue': 'Suspendue',
        'annulee': 'Annulée'
    }
    return labels[statut] || statut
}

const getStatutClass = (statut) => {
    const classes = {
        'en_attente_paiement': 'status-pending',
        'active': 'status-active',
        'terminee': 'status-completed',
        'en_retard': 'status-late',
        'suspendue': 'status-suspended',
        'annulee': 'status-cancelled'
    }
    return classes[statut] || 'status-default'
}

const getProgressClass = (statut) => {
    const classes = {
        'en_attente_paiement': 'progress-pending',
        'active': 'progress-active',
        'terminee': 'progress-completed',
        'en_retard': 'progress-late',
        'suspendue': 'progress-suspended',
        'annulee': 'progress-cancelled'
    }
    return classes[statut] || 'progress-default'
}

const getLocationsByStatus = (statut) => {
    return props.locations.filter(location => location.statut === statut)
}

const getProgressPercentage = (location) => {
    if (!location.date_debut || !location.date_fin) return 0
    const debut = new Date(location.date_debut)
    const fin = new Date(location.date_fin)
    const maintenant = new Date()
    if (maintenant < debut) return 0
    if (maintenant > fin) return 100
    const dureeTotal = fin.getTime() - debut.getTime()
    const dureeEcoulee = maintenant.getTime() - debut.getTime()
    return Math.round((dureeEcoulee / dureeTotal) * 100)
}

const getBienImageUrl = (bien) => {
    // Si l'image est déjà une URL complète
    if (bien?.image) {
        return bien.image
    }

    // Sinon, essayer de récupérer depuis le tableau images
    if (bien?.images && Array.isArray(bien.images) && bien.images.length > 0) {
        return bien.images[0].url
    }

    return placeholderImage
}

const getDureeRestante = (location) => {
    if (!location.date_fin) return 'Non spécifié'
    const fin = new Date(location.date_fin)
    const maintenant = new Date()
    if (maintenant > fin) return 'Terminée'
    const diffTime = fin.getTime() - maintenant.getTime()
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    if (diffDays < 30) {
        return `${diffDays} jour${diffDays > 1 ? 's' : ''} restant${diffDays > 1 ? 's' : ''}`
    } else {
        const diffMonths = Math.ceil(diffDays / 30)
        return `${diffMonths} mois restant${diffMonths > 1 ? 's' : ''}`
    }
}

const getDureeTotal = (location) => {
    if (!location.date_debut || !location.date_fin) return 0
    const debut = new Date(location.date_debut)
    const fin = new Date(location.date_fin)
    const diffTime = fin.getTime() - debut.getTime()
    const diffMonths = Math.round(diffTime / (1000 * 60 * 60 * 24 * 30))
    return diffMonths
}

// ✅ CORRECTION 1 : Méthode previewPdf avec paramètre location
const previewPdf = (location) => {
    if (!location || !location.id) {
        console.error('❌ Location invalide pour l\'aperçu')
        return
    }

    console.log('🔍 Aperçu du contrat:', location.id)
    window.open(route('locations.preview-contract', location.id), '_blank')
}

// ✅ CORRECTION 2 : Méthode downloadContract avec route correcte
const downloadContract = (location) => {
    if (!location || !location.id) {
        console.error('❌ Location invalide pour le téléchargement')
        return
    }

    console.log('📥 Téléchargement du contrat:', location.id)
    window.open(route('locations.download-contract', location.id), '_blank')
}
const goToSignature = (location) => {
    router.visit(route('locations.signature', location.id))
}
const getSignatureButtonText = (location) => {
    if (location.signature_stats?.fully_signed) return 'Signé'
    const userRole = location.user_role_in_location
    if (userRole === 'locataire') {
        return location.signature_stats?.locataire_signed ? 'Signé' : 'Signer'
    } else if (userRole === 'bailleur') {
        return location.signature_stats?.bailleur_signed ? 'Signé' : 'Signer'
    }
    return 'Signer'
}

const getSignatureStatusText = (status) => {
    const statusLabels = {
        'non_signe': 'Non signé',
        'partiellement_signe': 'Partiellement signé',
        'entierement_signe': 'Entièrement signé'
    }
    return statusLabels[status] || 'Statut inconnu'
}

const getSignatureStatusClass = (status) => {
    const statusClasses = {
        'non_signe': 'sig-not-signed',
        'partiellement_signe': 'sig-partial',
        'entierement_signe': 'sig-complete'
    }
    return statusClasses[status] || 'sig-default'
}
</script>

<style scoped>
/* Variables de couleurs */
:root {
    --primary: #005F5F;
    --secondary: #00B8A9;
    --accent: #6C63FF;
    --success: #00A884;
    --warning: #FFD166;
    --danger: #FF6B6B;
    --dark: #1E2A2A;
    --light-bg: #F8FAFA;
    --card-bg: #FFFFFF;
}

/* Wrapper général */
.locations-wrapper {
    background: linear-gradient(135deg, #F8FAFA 0%, #E8F4F4 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

/* En-tête */
.header-section {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    padding: 2.5rem;
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0, 95, 95, 0.15);
}

.header-title {
    color: rgb(26, 217, 201);
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
}

.header-subtitle {
    color: rgb(239, 220, 7);
    font-size: 1rem;
    margin: 0;
}

.total-badge {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    padding: 1rem 1.5rem;
    border-radius: 15px;
    display: flex;
    flex-direction: column;
    align-items: center;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.total-number {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    line-height: 1;
}

.total-label {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.9);
    margin-top: 0.25rem;
}

/* Alertes personnalisées */
.alert-success-custom,
.alert-danger-custom {
    border: none;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.alert-success-custom {
    background: linear-gradient(135deg, #D4EDDA 0%, #C3E6CB 100%);
    color: #155724;
}

.alert-danger-custom {
    background: linear-gradient(135deg, #F8D7DA 0%, #F5C6CB 100%);
    color: #721C24;
}

/* Grille de statistiques */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 1.75rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
    border: 2px solid transparent;
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

.stat-active .stat-icon {
    background: linear-gradient(135deg, #00A884 0%, #00C897 100%);
    color: white;
}

.stat-pending .stat-icon {
    background: linear-gradient(135deg, #FFD166 0%, #FCA311 100%);
    color: #2E2E2E;
}

.stat-completed .stat-icon {
    background: linear-gradient(135deg, #6C63FF 0%, #8B83FF 100%);
    color: white;
}

.stat-total .stat-icon {
    background: linear-gradient(135deg, #005F5F 0%, #00B8A9 100%);
    color: white;
}

.stat-content {
    flex: 1;
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

/* Grille des locations */
.locations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
    gap: 2rem;
}

/* Carte de location */
.location-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 95, 95, 0.08);
    transition: all 0.3s ease;
    position: relative;
    border: 2px solid #E8F4F4;
}

.location-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 95, 95, 0.15);
    border-color: var(--secondary);
}

/* Badge de statut flottant */
.status-badge-float {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    z-index: 10;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.status-active {
    background: rgba(0, 168, 132, 0.9);
    color: white;
}

.status-pending {
    background: rgba(255, 209, 102, 0.9);
    color: #2E2E2E;
}

.status-completed {
    background: rgba(108, 99, 255, 0.9);
    color: white;
}

.status-late {
    background: rgba(255, 107, 107, 0.9);
    color: white;
}

/* En-tête de carte */
.card-header-custom {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    padding: 2rem 1.5rem 1.5rem;
    position: relative;
}

.card-header-custom::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.4;
}

.property-info {
    position: relative;
    z-index: 1;
}

.property-title {
    color: white;
    font-size: 1.4rem;
    font-weight: 600;
    margin: 0 0 0.75rem 0;
}

.property-address {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.95rem;
    margin: 0;
    display: flex;
    align-items: center;
}

/* Corps de carte */
.card-body-custom {
    padding: 1.75rem 1.5rem;
}

/* Grille d'informations */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.info-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.info-icon.tenant {
    background: linear-gradient(135deg, #6C63FF 0%, #8B83FF 100%);
    color: white;
}

.info-icon.price {
    background: linear-gradient(135deg, #00A884 0%, #00C897 100%);
    color: white;
}

.info-icon.date-start {
    background: linear-gradient(135deg, #FFD166 0%, #FCA311 100%);
    color: #2E2E2E;
}

.info-icon.date-end {
    background: linear-gradient(135deg, #FF6B6B 0%, #FF8787 100%);
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
    font-weight: 600;
}

.price-value {
    color: var(--success);
}

/* Durée restante */
.duration-info {
    background: linear-gradient(135deg, #F8FAFA 0%, #E8F4F4 100%);
    padding: 1rem 1.25rem;
    border-radius: 12px;
    font-size: 0.9rem;
    color: var(--dark);
    margin-bottom: 1.5rem;
    border-left: 4px solid var(--secondary);
}

/* Section de progression */
.progress-section {
    margin-bottom: 1.5rem;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.progress-label {
    font-size: 0.85rem;
    color: #6C757D;
    font-weight: 500;
}

.progress-percentage {
    font-size: 0.9rem;
    color: var(--dark);
    font-weight: 700;
}

.progress-bar-custom {
    height: 10px;
    background: #E8F4F4;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.6s ease;
}

.progress-active {
    background: linear-gradient(90deg, #00A884 0%, #00C897 100%);
}

.progress-pending {
    background: linear-gradient(90deg, #FFD166 0%, #FCA311 100%);
}

.progress-completed {
    background: linear-gradient(90deg, #6C63FF 0%, #8B83FF 100%);
}

.progress-late {
    background: linear-gradient(90deg, #FF6B6B 0%, #FF8787 100%);
}

/* Informations de signature */
.signature-info {
    margin-bottom: 1.5rem;
}

.signature-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.signature-badge {
    padding: 0.4rem 0.9rem;
    border-radius: 8px;
    font-size: 0.75rem;
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

.signature-badge.signed {
    background: #E8F5E9;
    color: #2E7D32;
}

/* Grille d'actions */
.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
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
}

.action-btn i {
    font-size: 1.2rem;
}

.action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.action-view {
    background: linear-gradient(135deg, #F8FAFA 0%, #E8F4F4 100%);
    color: var(--primary);
    border-color: #E8F4F4;
}

.action-view:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.action-manage {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
}

.action-manage:hover {
    background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
    color: white;
}

.action-preview {
    background: linear-gradient(135deg, #F3F4FF 0%, #E8E9FF 100%);
    color: var(--accent);
    border-color: #E8E9FF;
}

.action-preview:hover {
    background: var(--accent);
    color: white;
    border-color: var(--accent);
}

.action-download {
    background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
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

/* Footer de carte */
.card-footer-custom {
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #F8FAFA 0%, #E8F4F4 100%);
    border-top: 1px solid #E8F4F4;
    font-size: 0.8rem;
    color: #6C757D;
    display: flex;
    align-items: center;
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
    opacity: 0.3;
}

.empty-title {
    color: var(--dark);
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.empty-description {
    color: #6C757D;
    font-size: 1.1rem;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.btn-explore {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 95, 95, 0.25);
}

.btn-explore:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 95, 95, 0.35);
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .locations-grid {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .header-title {
        font-size: 1.5rem;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .actions-grid {
        grid-template-columns: repeat(2, 1fr);
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
}

</style>
