<template>
    <Layout>
        <Head title="Dashboard Admin Global" />

        <!-- Header -->
        <div class="dashboard-header">
            <div class="container">
                <div class="header-content">
                    <div>
                        <div class="header-badge">
                            <i class="fas fa-crown"></i>
                            <span>Administration</span>
                        </div>
                        <h1 class="header-title">Dashboard Global</h1>
                        <p class="header-subtitle">Vue d'ensemble complète de la plateforme</p>
                    </div>
                    <div class="icon-wrapper">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="container">
                <!-- Stats Globales -->
                <div class="section-header">
                    <h2><i class="fas fa-globe"></i> Vue d'ensemble générale</h2>
                </div>

                <div class="stats-grid">
                    <!-- Propriétaires -->
                    <div class="stat-card card-purple">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">Propriétaires</div>
                            <div class="stat-value">{{ stats_globales.nombre_proprietaires }}</div>
                        </div>
                    </div>

                    <!-- Biens -->
                    <div class="stat-card card-blue">
                        <div class="stat-icon"><i class="fas fa-building"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">Total Biens</div>
                            <div class="stat-value">{{ stats_globales.total_biens }}</div>
                            <div class="stat-badges">
                                <span class="badge badge-teal">
                                    {{ stats_globales.biens_gestion_locative }} location
                                </span>
                                <span class="badge badge-orange">
                                    {{ stats_globales.biens_vente }} vente
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Occupation -->
                    <div class="stat-card card-green">
                        <div class="stat-icon"><i class="fas fa-door-open"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">Occupation Globale</div>
                            <div class="stat-value">
                                {{ stats_globales.appartements_loues }}/{{ stats_globales.total_appartements }}
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" :style="{ width: stats_globales.taux_occupation_global + '%' }">
                                    {{ stats_globales.taux_occupation_global }}%
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recettes Agence -->
                    <div class="stat-card card-cyan">
                        <div class="stat-icon"><i class="fas fa-coins"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">Recettes Agence (10%)</div>
                            <div class="stat-value">{{ formatMontant(stats_globales.recettes_agence_totales) }}</div>
                            <div class="stat-detail">
                                Ce mois: <strong>{{ formatMontant(stats_globales.recettes_agence_mois) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Recettes Propriétaires -->
                    <div class="stat-card card-indigo">
                        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">Recettes Propriétaires (90%)</div>
                            <div class="stat-value">{{ formatMontant(stats_globales.recettes_proprietaires_totales) }}</div>
                            <div class="stat-detail">
                                Ce mois: <strong>{{ formatMontant(stats_globales.recettes_proprietaires_mois) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Loyers en Attente -->
                    <div class="stat-card card-orange">
                        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">Loyers en Attente</div>
                            <div class="stat-value">{{ formatMontant(stats_globales.loyers_en_attente) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Stats Locataires -->
                <div class="section-header">
                    <h2><i class="fas fa-user-tie"></i> Statistiques Locataires</h2>
                </div>

                <div class="stats-grid-locataires">
                    <div class="mini-stat">
                        <i class="fas fa-users"></i>
                        <div>
                            <div class="mini-label">Locataires Actifs</div>
                            <div class="mini-value">{{ stats_locataires.total_locataires_actifs }}</div>
                        </div>
                    </div>
                    <div class="mini-stat">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <div class="mini-label">Locations Actives</div>
                            <div class="mini-value">{{ stats_locataires.locations_actives }}</div>
                        </div>
                    </div>
                    <div class="mini-stat">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <div class="mini-label">En Retard</div>
                            <div class="mini-value">{{ stats_locataires.locations_en_retard }}</div>
                        </div>
                    </div>
                    <div class="mini-stat">
                        <i class="fas fa-coins"></i>
                        <div>
                            <div class="mini-label">Loyers Collectés</div>
                            <div class="mini-value">{{ formatMontant(stats_locataires.loyers_collectes) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Détails par Propriétaire -->
                <div class="section-header">
                    <h2><i class="fas fa-user-friends"></i> Détails par Propriétaire</h2>
                </div>

                <div v-if="stats_par_proprietaire.length === 0" class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <p>Aucun propriétaire avec biens actifs</p>
                </div>

                <div v-else class="proprietaires-list">
                    <div v-for="(item, index) in stats_par_proprietaire" :key="index" class="proprietaire-card">
                        <div class="proprietaire-header">
                            <div class="proprietaire-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="proprietaire-info">
                                <h3>{{ item.proprietaire.nom }} {{ item.proprietaire.prenom }}</h3>
                                <div class="proprietaire-contact">
                                    <span><i class="fas fa-envelope"></i> {{ item.proprietaire.email }}</span>
                                    <span><i class="fas fa-phone"></i> {{ item.proprietaire.telephone }}</span>
                                </div>
                            </div>
                            <Link
                                :href="route('dashboard.proprietaire', item.proprietaire.id)"
                                class="btn-details"
                            >
                                Voir détails <i class="fas fa-arrow-right"></i>
                            </Link>
                        </div>

                        <div class="proprietaire-stats">
                            <div class="stat-item">
                                <i class="fas fa-building"></i>
                                <div>
                                    <div class="stat-item-label">Biens</div>
                                    <div class="stat-item-value">{{ item.stats.total_biens }}</div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-door-open"></i>
                                <div>
                                    <div class="stat-item-label">Appartements</div>
                                    <div class="stat-item-value">{{ item.stats.appartements_loues }}/{{ item.stats.total_appartements }}</div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-percentage"></i>
                                <div>
                                    <div class="stat-item-label">Occupation</div>
                                    <div class="stat-item-value">{{ item.stats.taux_occupation }}%</div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-wallet"></i>
                                <div>
                                    <div class="stat-item-label">Recettes totales (90%)</div>
                                    <div class="stat-item-value">{{ formatMontant(item.stats.recettes_totales) }}</div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-calendar-check"></i>
                                <div>
                                    <div class="stat-item-label">Ce mois</div>
                                    <div class="stat-item-value">{{ formatMontant(item.stats.recettes_mois_courant) }}</div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-hourglass-half"></i>
                                <div>
                                    <div class="stat-item-label">En attente</div>
                                    <div class="stat-item-value">{{ formatMontant(item.stats.loyers_en_attente) }}</div>
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

defineProps({
    stats_globales: Object,
    stats_par_proprietaire: Array,
    stats_locataires: Object,
});

const formatMontant = (montant) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(montant || 0);
};
</script>

<style scoped>
.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 3rem 0;
    color: white;
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.header-title {
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0 0 0.5rem 0;
}

.header-subtitle {
    font-size: 1.125rem;
    opacity: 0.9;
    margin: 0;
}

.icon-wrapper {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.main-content {
    background: #f8fafc;
    padding: 3rem 0;
    min-height: calc(100vh - 200px);
}

.section-header {
    margin: 2rem 0 1.5rem 0;
}

.section-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 1.75rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    margin-bottom: 1rem;
}

.card-purple .stat-icon { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
.card-blue .stat-icon { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.card-green .stat-icon { background: linear-gradient(135deg, #10b981, #047857); }
.card-cyan .stat-icon { background: linear-gradient(135deg, #06b6d4, #0e7490); }
.card-indigo .stat-icon { background: linear-gradient(135deg, #6366f1, #4338ca); }
.card-orange .stat-icon { background: linear-gradient(135deg, #f59e0b, #b45309); }

.stat-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
    margin-bottom: 0.75rem;
}

.stat-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.badge {
    padding: 0.375rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-teal { background: #d1fae5; color: #065f46; }
.badge-orange { background: #fed7aa; color: #92400e; }

.progress-bar {
    height: 8px;
    background: #e5e7eb;
    border-radius: 50px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    border-radius: 50px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 0.5rem;
    font-size: 0.625rem;
    font-weight: 700;
    color: white;
    transition: width 1s ease;
}

.stat-detail {
    font-size: 0.875rem;
    color: #64748b;
}

.stat-detail strong {
    color: #1e293b;
}

.stats-grid-locataires {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 3rem;
}

.mini-stat {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.mini-stat i {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.mini-label {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 0.25rem;
}

.mini-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
}

.proprietaires-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.proprietaire-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.proprietaire-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #f1f5f9;
    margin-bottom: 1.5rem;
}

.proprietaire-avatar {
    font-size: 4rem;
    color: #667eea;
}

.proprietaire-info {
    flex: 1;
}

.proprietaire-info h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
}

.proprietaire-contact {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: #64748b;
}

.proprietaire-contact span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-details {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: transform 0.3s ease;
}

.btn-details:hover {
    transform: translateX(4px);
}

.proprietaire-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 12px;
}

.stat-item i {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.stat-item-label {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
}

.stat-item-value {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1e293b;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
}

.empty-state i {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-state p {
    color: #64748b;
    font-size: 1.125rem;
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        text-align: center;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .proprietaire-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .btn-details {
        width: 100%;
        justify-content: center;
    }
}
</style>
