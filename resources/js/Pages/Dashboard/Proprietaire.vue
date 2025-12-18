<template>
    <Layout>
        <Head title="Dashboard Propriétaire" />

        <!-- Header avec gradient vibrant -->
        <div class="dashboard-header">
            <div class="header-container">
                <div class="header-content">
                    <div>
                        <h1 class="header-title">📊 Dashboard Propriétaire</h1>
                        <p class="header-subtitle">Gérez vos biens et suivez vos revenus locatifs en temps réel</p>
                    </div>
                    <div class="dashboard-icon-wrapper">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="content-container">
                <!-- Statistiques Globales avec animations -->
                <div class="stats-section">
                    <div class="stats-grid">
                        <!-- Card Total Biens -->
                        <div class="stat-card card-primary">
                            <div class="stat-icon icon-primary">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-label">Total Biens</div>
                                <div class="stat-value">{{ stats_globales.total_biens }}</div>
                                <div class="stat-badges">
                                    <span class="badge badge-teal">
                                        <i class="fas fa-key"></i>
                                        {{ stats_globales.biens_gestion_locative }} location
                                    </span>
                                    <span class="badge badge-orange">
                                        <i class="fas fa-tag"></i>
                                        {{ stats_globales.biens_vente }} vente
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Taux d'Occupation -->
                        <div class="stat-card card-success">
                            <div class="stat-icon icon-success">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-label">Taux d'Occupation</div>
                                <div class="stat-value">
                                    {{ stats_globales.appartements_loues }}/{{ stats_globales.total_appartements }}
                                </div>
                                <div class="progress-container">
                                    <div class="progress-bar-bg">
                                        <div
                                            class="progress-bar-fill progress-success"
                                            :style="{ width: occupationRate + '%' }"
                                        >
                                            <span class="progress-text">{{ occupationRate }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Recettes -->
                        <div class="stat-card card-info">
                            <div class="stat-icon icon-info">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-label">Recettes Totales</div>
                                <div class="stat-value stat-money">
                                    {{ formatMontant(stats_globales.recettes_totales) }}
                                </div>
                                <div class="stat-detail">
                                    <i class="fas fa-calendar-alt"></i>
                                    Ce mois: <strong>{{ formatMontant(stats_globales.recettes_mois_courant) }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Card Loyers en Attente -->
                        <div class="stat-card card-warning">
                            <div class="stat-icon icon-warning">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-label">Loyers en Attente</div>
                                <div class="stat-value stat-money">
                                    {{ formatMontant(stats_globales.loyers_en_attente) }}
                                </div>
                                <div class="stat-detail alert-detail">
                                    <i class="fas fa-exclamation-circle"></i>
                                    À encaisser rapidement
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des Biens -->
                <div class="biens-section">
                    <div v-if="biens.length === 0" class="empty-state">
                        <i class="fas fa-home"></i>
                        <h3>Aucun de vos bien n'a été acheté ou loué pour le moment</h3>
                        <p>Vos biens apparaîtront ici une fois qu'ils auront acheté ou mis en location</p>
                    </div>

                    <div v-for="bien in biens" :key="bien.id" class="bien-card">
                        <!-- Header du bien -->
                        <div class="bien-header">
                            <div class="bien-header-left">
                                <div class="bien-title-section">
                                    <h3 class="bien-title">{{ bien.title }}</h3>
                                    <span class="mandat-badge" :class="getMandatClass(bien.type_mandat)">
                                        <i :class="getMandatIcon(bien.type_mandat)"></i>
                                        {{ bien.type_mandat_label }}
                                    </span>
                                </div>
                                <div class="bien-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    {{ bien.address }}, {{ bien.city }}
                                </div>
                                <div class="bien-meta">
                                    <span><i class="fas fa-home"></i> {{ bien.type }}</span>
                                    <span><i class="fas fa-layer-group"></i> {{ bien.floors + 1 }} étages</span>
                                    <span v-if="bien.date_fin_mandat" class="mandat-expiry">
                                        <i class="fas fa-calendar-check"></i>
                                        Mandat jusqu'au {{ bien.date_fin_mandat }}
                                    </span>
                                </div>
                            </div>

                        </div>

                        <!-- Stats du bien (location seulement) -->
                        <div v-if="bien.type_mandat === 'gestion_locative' && bien.appartements_par_etage.length > 0" class="bien-stats">
                            <div class="mini-stats-grid">
                                <div class="mini-stat">
                                    <div class="mini-stat-icon icon-primary">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div>
                                        <div class="mini-stat-label">Recettes Totales</div>
                                        <div class="mini-stat-value">{{ formatMontant(bien.recettes.total) }}</div>
                                    </div>
                                </div>
                                <div class="mini-stat">
                                    <div class="mini-stat-icon icon-success">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div>
                                        <div class="mini-stat-label">Ce mois</div>
                                        <div class="mini-stat-value">{{ formatMontant(bien.recettes.mois_courant) }}</div>
                                    </div>
                                </div>
                                <div class="mini-stat">
                                    <div class="mini-stat-icon icon-warning">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <div class="mini-stat-label">En attente</div>
                                        <div class="mini-stat-value">{{ formatMontant(bien.loyers_stats.en_attente) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Locataires par étage -->
                        <div v-if="bien.type_mandat === 'gestion_locative' && bien.appartements_par_etage.length > 0" class="bien-content">
                            <h4 class="section-title">
                                <i class="fas fa-users"></i>
                                Locataires par étage
                            </h4>

                            <div v-for="etageData in bien.appartements_par_etage" :key="etageData.etage" class="etage-section">
                                <div class="etage-header">
                                    <div class="etage-badge">
                                        <i class="fas fa-layer-group"></i>
                                        {{ etageData.label }}
                                    </div>
                                    <div class="etage-count">
                                        {{ etageData.appartements.length }} appartement{{ etageData.appartements.length > 1 ? 's' : '' }}
                                    </div>
                                </div>

                                <div class="appartements-grid">
                                    <div
                                        v-for="app in etageData.appartements"
                                        :key="app.id"
                                        class="appartement-card"
                                        :class="getAppartementClass(app.statut)"
                                    >
                                        <div class="app-header">
                                            <div class="app-header-left">
                                                <div class="app-number">{{ app.numero }}</div>
                                                <div>
                                                    <h5 class="app-name">Appartement {{ app.numero }}</h5>
                                                    <div class="app-specs">
                                                        <span><i class="fas fa-expand-arrows-alt"></i> {{ app.superficie }} m²</span>
                                                        <span><i class="fas fa-door-open"></i> {{ app.pieces }} pièces</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="status-badge" :class="getStatusClass(app.statut)">
                                                <i :class="getStatusIcon(app.statut)"></i>
                                                {{ app.statut === 'loue' ? 'Loué' : 'Disponible' }}
                                            </span>
                                        </div>

                                        <!-- Info locataire -->
                                        <div v-if="app.locataire && app.location" class="locataire-section">
                                            <div class="locataire-grid">
                                                <div class="locataire-left">
                                                    <div class="locataire-avatar">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <div class="label-small">Locataire</div>
                                                        <div class="locataire-name">
                                                            {{ app.locataire.nom }} {{ app.locataire.prenom }}
                                                        </div>
                                                        <div class="locataire-contact">
                                                            <span><i class="fas fa-envelope"></i> {{ app.locataire.email }}</span>
                                                            <span><i class="fas fa-phone"></i> {{ app.locataire.telephone }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="locataire-right">
                                                    <div class="loyer-box">
                                                        <div class="label-small">Loyer mensuel</div>
                                                        <div class="loyer-amount">
                                                            {{ formatMontant(app.location.loyer_mensuel) }}
                                                        </div>
                                                    </div>
                                                    <div class="bail-info">
                                                        <i class="fas fa-calendar"></i>
                                                        {{ formatDateShort(app.location.date_debut) }} → {{ formatDateShort(app.location.date_fin) }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Dernier paiement -->
                                            <div v-if="app.location.dernier_paiement" class="paiement-section">
                                                <div class="paiement-label">
                                                    <i class="fas fa-receipt"></i>
                                                    Dernier paiement
                                                </div>
                                                <div class="paiement-info">
                                                    <div>
                                                        <span class="paiement-amount">
                                                            {{ formatMontant(app.location.dernier_paiement.montant) }}
                                                        </span>
                                                        <span class="paiement-date">
                                                            le {{ formatDateShort(app.location.dernier_paiement.date) }}
                                                        </span>
                                                    </div>
                                                    <span class="paiement-badge" :class="getPaiementClass(app.location.dernier_paiement.statut)">
                                                        <i :class="getPaiementIcon(app.location.dernier_paiement.statut)"></i>
                                                        {{ getStatutLabel(app.location.dernier_paiement.statut) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div v-else class="no-paiement">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                Aucun paiement enregistré
                                            </div>
                                        </div>

                                        <div v-else class="no-locataire">
                                            <i class="fas fa-user-times"></i>
                                            Aucun locataire actuellement
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Message pour vente -->
                        <div v-else class="vente-section">
                            <div class="vente-message">
                                <i class="fas fa-tag"></i>
                                <div>
                                    <h4>Bien destiné à la vente</h4>
                                    <p>Ce bien est actuellement sous mandat de vente. Consultez les détails pour plus d'informations.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Layout from '../Layout.vue';
import { computed } from 'vue';

const props = defineProps({
    biens: Array,
    stats_globales: Object
});

const formatMontant = (montant) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(montant || 0);
};

const formatDateShort = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getStatutLabel = (statut) => {
    const labels = {
        'paye': 'Payé',
        'en_attente': 'En attente',
        'en_retard': 'En retard'
    };
    return labels[statut] || statut;
};

const occupationRate = computed(() => {
    if (props.stats_globales.total_appartements === 0) return 0;
    return Math.round((props.stats_globales.appartements_loues / props.stats_globales.total_appartements) * 100);
});

const getMandatClass = (type) => type === 'gestion_locative' ? 'mandat-location' : 'mandat-vente';
const getMandatIcon = (type) => type === 'gestion_locative' ? 'fas fa-key' : 'fas fa-tag';
const getAppartementClass = (statut) => statut === 'loue' ? 'app-loue' : 'app-disponible';
const getStatusClass = (statut) => statut === 'loue' ? 'status-loue' : 'status-disponible';
const getStatusIcon = (statut) => statut === 'loue' ? 'fas fa-check-circle' : 'fas fa-circle';
const getPaiementClass = (statut) => `paiement-${statut}`;
const getPaiementIcon = (statut) => {
    const icons = { 'paye': 'fas fa-check-circle', 'en_attente': 'fas fa-clock', 'en_retard': 'fas fa-exclamation-circle' };
    return icons[statut] || 'fas fa-circle';
};
</script>

<style scoped>
/* === COULEURS CAURIS IMMO === */
:root {
    --primary: #006064;
    --primary-light: #00838f;
    --success: #00897b;
    --warning: #ff6f00;
    --danger: #c62828;
}

/* === HEADER === */
.dashboard-header {
    background: linear-gradient(135deg, #006064 0%, #00838f 50%, #00acc1 100%);
    padding: 2.5rem 0;
    box-shadow: 0 10px 30px rgba(0, 96, 100, 0.2);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.header-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: white;
    margin: 0 0 0.5rem 0;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.header-subtitle {
    font-size: 1.125rem;
    color: rgba(255, 255, 255, 0.95);
    margin: 0;
}

.dashboard-icon-wrapper {
    width: 90px;
    height: 90px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: white;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* === MAIN CONTENT === */
.main-content {
    padding: 2.5rem 0;
    background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
    min-height: calc(100vh - 200px);
}

.content-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* === STATS CARDS === */
.stats-section {
    margin-bottom: 2.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 96, 100, 0.15);
}

.stat-icon {
    width: 70px;
    height: 70px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 1.25rem;
    color: white;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.icon-primary { background: linear-gradient(135deg, #006064, #00838f); }
.icon-success { background: linear-gradient(135deg, #00897b, #26a69a); }
.icon-info { background: linear-gradient(135deg, #0288d1, #039be5); }
.icon-warning { background: linear-gradient(135deg, #ff6f00, #ff8f00); }

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.75rem;
}

.stat-value {
    font-size: 2.75rem;
    font-weight: 800;
    color: #1f2937;
    line-height: 1;
    margin-bottom: 1rem;
}

.stat-money {
    background: linear-gradient(135deg, #006064, #00838f);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    border-radius: 12px;
    font-size: 0.8125rem;
    font-weight: 600;
}

.badge-teal {
    background: linear-gradient(135deg, #e0f2f1, #b2dfdb);
    color: #00695c;
}

.badge-orange {
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    color: #e65100;
}

.progress-container {
    margin-top: 1rem;
}

.progress-bar-bg {
    height: 12px;
    background: #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.progress-bar-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 1s ease;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 0.5rem;
}

.progress-success {
    background: linear-gradient(90deg, #00897b, #26a69a);
    box-shadow: 0 2px 10px rgba(0, 137, 123, 0.3);
}

.progress-text {
    font-size: 0.75rem;
    font-weight: 700;
    color: white;
}

.stat-detail {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.alert-detail {
    color: #ff6f00;
    font-weight: 600;
}

/* === BIENS CARDS === */
.biens-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.bien-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.bien-card:hover {
    box-shadow: 0 12px 40px rgba(0, 96, 100, 0.12);
}

.bien-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 3px solid #dee2e6;
}

.bien-title-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
    flex-wrap: wrap;
}

.bien-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.mandat-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 700;
    color: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.mandat-location {
    background: linear-gradient(135deg, #00897b, #26a69a);
}

.mandat-vente {
    background: linear-gradient(135deg, #ff6f00, #ff8f00);
}

.bien-location {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 1rem;
    margin-bottom: 0.75rem;
}

.bien-meta {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
    color: #6b7280;
}

.bien-meta span {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.mandat-expiry {
    color: #00897b;
    font-weight: 600;
}

.btn-details {
    display: inline-flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.875rem 1.75rem;
    background: linear-gradient(135deg, #006064, #00838f);
    color: white;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 96, 100, 0.3);
}

.btn-details:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 96, 100, 0.4);
    color: white;
}

/* === BIEN STATS === */
.bien-stats {
    background: #f8f9fa;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #dee2e6;
}

.mini-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.mini-stat {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.mini-stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.mini-stat-label {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
}

.mini-stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
}

/* === CONTENT === */
.bien-content {
    padding: 2rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.375rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 1.5rem;
}

.etage-section {
    margin-bottom: 2rem;
}

.etage-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.etage-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #006064, #00838f);
    color: white;
    padding: 0.625rem 1.5rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9375rem;
    box-shadow: 0 4px 12px rgba(0, 96, 100, 0.2);
}

.etage-count {
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 600;
}

/* === APPARTEMENTS === */
.appartements-grid {
    display: grid;
    gap: 1rem;
}

.appartement-card {
    border: 3px solid;
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.appartement-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.app-loue {
    background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%);
    border-color: #00897b;
}

.app-loue::before {
    background: linear-gradient(90deg, #00897b, #26a69a);
}

.app-disponible {
    background: #f9fafb;
    border-color: #e5e7eb;
}

.app-disponible::before {
    background: linear-gradient(90deg, #9ca3af, #d1d5db);
}

.appartement-card:hover {
    transform: translateX(8px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.app-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.25rem;
}

.app-header-left {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.app-number {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #006064, #00838f);
    color: white;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1rem;
    box-shadow: 0 6px 16px rgba(0, 96, 100, 0.3);
}

.app-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.375rem 0;
}

.app-specs {
    display: flex;
    gap: 1.25rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.app-specs span {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.125rem;
    border-radius: 16px;
    font-size: 0.875rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.status-loue {
    background: linear-gradient(135deg, #00897b, #26a69a);
    color: white;
}

.status-disponible {
    background: linear-gradient(135deg, #9ca3af, #d1d5db);
    color: white;
}

/* === LOCATAIRE === */
.locataire-section {
    background: white;
    border-radius: 14px;
    padding: 1.5rem;
    border: 2px solid #e5e7eb;
}

.locataire-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.locataire-left {
    display: flex;
    gap: 1rem;
}

.locataire-avatar {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #006064, #00838f);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.75rem;
    flex-shrink: 0;
    box-shadow: 0 6px 16px rgba(0, 96, 100, 0.3);
}

.label-small {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.375rem;
}

.locataire-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.625rem;
}

.locataire-contact {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.locataire-contact span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.locataire-right {
    text-align: right;
}

.loyer-box {
    background: linear-gradient(135deg, #e0f2f1, #b2dfdb);
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 0.75rem;
}

.loyer-amount {
    font-size: 1.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #006064, #00838f);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.bail-info {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #f3f4f6;
    padding: 0.625rem 1rem;
    border-radius: 10px;
    font-size: 0.8125rem;
    color: #6b7280;
    font-weight: 600;
}

/* === PAIEMENT === */
.paiement-section {
    border-top: 2px solid #e5e7eb;
    padding-top: 1rem;
    margin-top: 1rem;
}

.paiement-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #6b7280;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.paiement-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.paiement-amount {
    font-weight: 700;
    color: #1f2937;
    font-size: 1.125rem;
}

.paiement-date {
    font-size: 0.875rem;
    color: #6b7280;
    margin-left: 0.75rem;
}

.paiement-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.125rem;
    border-radius: 16px;
    font-size: 0.875rem;
    font-weight: 700;
    color: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.paiement-paye {
    background: linear-gradient(135deg, #00897b, #26a69a);
}

.paiement-en_attente {
    background: linear-gradient(135deg, #ff6f00, #ff8f00);
}

.paiement-en_retard {
    background: linear-gradient(135deg, #c62828, #d32f2f);
}

.no-paiement {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 1rem;
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    border-radius: 10px;
    color: #e65100;
    font-size: 0.875rem;
    font-weight: 600;
    border: 2px solid #ffb74d;
}

.no-locataire {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 1.25rem;
    background: #f3f4f6;
    border-radius: 10px;
    color: #6b7280;
    font-style: italic;
    font-size: 0.9375rem;
}

/* === VENTE === */
.vente-section {
    padding: 2rem;
}

.vente-message {
    display: flex;
    gap: 1.25rem;
    padding: 2rem;
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    border-radius: 16px;
    border-left: 6px solid #ff6f00;
    box-shadow: 0 4px 16px rgba(255, 111, 0, 0.15);
}

.vente-message i {
    font-size: 2.5rem;
    color: #ff6f00;
}

.vente-message h4 {
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.625rem 0;
    font-size: 1.25rem;
}

.vente-message p {
    color: #6b7280;
    margin: 0;
    line-height: 1.6;
}

/* === EMPTY STATE === */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.empty-state i {
    font-size: 5rem;
    color: #d1d5db;
    margin-bottom: 1.5rem;
}

.empty-state h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.75rem;
}

.empty-state p {
    color: #6b7280;
    font-size: 1rem;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
    .header-title {
        font-size: 1.75rem;
    }

    .header-subtitle {
        font-size: 0.9375rem;
    }

    .dashboard-icon-wrapper {
        width: 70px;
        height: 70px;
        font-size: 2rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .stat-value {
        font-size: 2rem;
    }

    .bien-header {
        flex-direction: column;
        gap: 1rem;
    }

    .btn-details {
        width: 100%;
        justify-content: center;
    }

    .mini-stats-grid {
        grid-template-columns: 1fr;
    }

    .locataire-grid {
        grid-template-columns: 1fr;
    }

    .locataire-right {
        text-align: left;
    }

    .etage-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}

@media (max-width: 480px) {
    .main-content {
        padding: 1.5rem 0;
    }

    .content-container {
        padding: 0 1rem;
    }

    .stat-card {
        padding: 1.5rem;
    }

    .bien-header,
    .bien-content,
    .vente-section {
        padding: 1.5rem;
    }

    .appartement-card {
        padding: 1.25rem;
    }
}
</style>
