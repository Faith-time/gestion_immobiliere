<!-- resources/js/Pages/Visites/Index.vue -->
<template>
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <!-- En-tête -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">
                        <i class="fas fa-calendar-alt me-3 text-primary"></i>Mes visites
                    </h2>
                    <div class="text-muted">
                        {{ visites.length }} visite{{ visites.length > 1 ? 's' : '' }}
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

                <!-- Liste des visites -->
                <div v-if="visites.length > 0" class="row g-4">
                    <div v-for="visite in visites" :key="visite.id" class="col-lg-6">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <!-- En-tête de la carte avec statut -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            {{ visite.bien.title }}
                                        </h5>
                                        <p class="text-muted mb-0 small">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            {{ visite.bien.address }}, {{ visite.bien.city }}
                                        </p>
                                    </div>
                                    <span :class="getStatutBadgeClass(visite.statut)">
                                        {{ getStatutLabel(visite.statut) }}
                                    </span>
                                </div>

                                <!-- Informations de la visite -->
                                <div class="row g-3 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Date souhaitée</small>
                                        <div class="fw-medium">
                                            <i class="fas fa-calendar me-1 text-primary"></i>
                                            {{ formatDate(visite.date_visite) }}
                                        </div>
                                    </div>
                                    <div class="col-6" v-if="visite.agent">
                                        <small class="text-muted d-block">Agent assigné</small>
                                        <div class="fw-medium">
                                            <i class="fas fa-user me-1 text-success"></i>
                                            {{ visite.agent.name }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Message du client -->
                                <div v-if="visite.message" class="mb-3">
                                    <small class="text-muted d-block">Votre message</small>
                                    <p class="small mb-0 text-break">{{ visite.message }}</p>
                                </div>

                                <!-- Notes admin ou commentaires -->
                                <div v-if="visite.notes_admin && visite.statut === 'confirmee'" class="mb-3">
                                    <small class="text-muted d-block">Notes de l'agence</small>
                                    <div class="alert alert-info py-2 px-3 mb-0 small">
                                        {{ visite.notes_admin }}
                                    </div>
                                </div>

                                <div v-if="visite.motif_rejet && visite.statut === 'rejetee'" class="mb-3">
                                    <small class="text-muted d-block">Motif du rejet</small>
                                    <div class="alert alert-warning py-2 px-3 mb-0 small">
                                        {{ visite.motif_rejet }}
                                    </div>
                                </div>

                                <div v-if="visite.commentaire_visite && visite.statut === 'effectuee'" class="mb-3">
                                    <small class="text-muted d-block">Compte-rendu de visite</small>
                                    <div class="alert alert-success py-2 px-3 mb-0 small">
                                        {{ visite.commentaire_visite }}
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="d-flex gap-2 mt-auto">
                                    <!-- Voir le bien -->
                                    <Link
                                        :href="route('biens.show', visite.bien.id)"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Voir le bien
                                    </Link>

                                    <!-- Annuler (seulement si en_attente ou confirmee) -->
                                    <button
                                        v-if="['en_attente', 'confirmee'].includes(visite.statut)"
                                        @click="annulerVisite(visite.id)"
                                        class="btn btn-outline-danger btn-sm"
                                        :disabled="processing">
                                        <i class="fas fa-times me-1"></i>Annuler
                                    </button>

                                    <!-- Procéder à la vente (si visite effectuée et mandat vente) -->
                                    <Link
                                        v-if="visite.statut === 'effectuee' && visite.bien.mandat?.type_mandat === 'vente'"
                                        :href="route('ventes.create', { bien_id: visite.bien.id })"
                                        class="btn btn-success btn-sm">
                                        <i class="fas fa-handshake me-1"></i>Procéder à l'achat
                                    </Link>
                                </div>
                            </div>

                            <!-- Footer avec date de création -->
                            <div class="card-footer bg-transparent border-0 pt-0">
                                <small class="text-muted">
                                    Demandée le {{ formatDate(visite.created_at) }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- État vide -->
                <div v-else class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-calendar-times display-1 text-muted opacity-50"></i>
                    </div>
                    <h4 class="text-muted mb-3">Aucune visite programmée</h4>
                    <p class="text-muted mb-4">
                        Vous n'avez pas encore demandé de visite. <br>
                        Réservez d'abord un bien pour pouvoir programmer une visite.
                    </p>
                    <Link href="/" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Explorer les biens
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    visites: { type: Array, required: true },
    userRoles: { type: Array, default: () => [] }
})

const processing = ref(false)

// Méthodes utilitaires
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatutLabel = (statut) => {
    const labels = {
        'en_attente': 'En attente',
        'confirmee': 'Confirmée',
        'effectuee': 'Effectuée',
        'annulee': 'Annulée',
        'rejetee': 'Rejetée'
    }
    return labels[statut] || statut
}

const getStatutBadgeClass = (statut) => {
    const classes = {
        'en_attente': 'badge bg-warning text-dark',
        'confirmee': 'badge bg-success',
        'effectuee': 'badge bg-primary',
        'annulee': 'badge bg-secondary',
        'rejetee': 'badge bg-danger'
    }
    return classes[statut] || 'badge bg-secondary'
}

const annulerVisite = (visiteId) => {
    if (!confirm('Êtes-vous sûr de vouloir annuler cette visite ?')) {
        return
    }

    processing.value = true

    // Utiliser une requête POST vers la route d'annulation
    router.post(route('visites.annuler', visiteId), {}, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false
        }
    })
}
</script>

<style scoped>
.card {
    border-radius: 15px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.card-title {
    color: #2c3e50;
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.875rem;
    border-radius: 6px;
}

.alert {
    border-radius: 8px;
}

.badge {
    font-size: 0.75rem;
    padding: 0.4em 0.8em;
    border-radius: 8px;
}

.text-break {
    word-break: break-word;
}</style>
