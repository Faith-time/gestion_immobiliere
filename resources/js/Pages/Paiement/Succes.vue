<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Animation de succès -->
                <div class="text-center mb-5">
                    <div class="success-animation mb-4">
                        <div class="checkmark-circle">
                            <div class="checkmark"></div>
                        </div>
                    </div>
                    <h1 class="text-success mb-3">Paiement réussi !</h1>
                    <p class="lead text-muted">
                        Votre {{ getTypeLabel(paiement.type).toLowerCase() }} a été confirmée avec succès.
                    </p>
                </div>

                <!-- Détails du paiement -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-receipt me-3 fs-4"></i>
                            <div>
                                <h5 class="mb-0">Détails du paiement</h5>
                                <small class="opacity-75">Transaction #{{ paiement.transaction_id }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <!-- Image et détails du bien -->
                            <div class="col-md-4">
                                <img :src="getBienImage()"
                                     :alt="getBienTitle()"
                                     class="img-fluid rounded mb-3"
                                     style="width: 100%; height: 200px; object-fit: cover;">
                                <h6 class="text-primary">{{ getBienTitle() }}</h6>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    {{ getBienCity() }}
                                </p>
                            </div>

                            <!-- Informations de paiement -->
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="info-item">
                                            <label class="small text-muted">Type de transaction</label>
                                            <p class="mb-0 fw-bold">{{ getTypeLabel(paiement.type) }}</p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="info-item">
                                            <label class="small text-muted">Statut</label>
                                            <p class="mb-0">
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Confirmé
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="info-item">
                                            <label class="small text-muted">Montant payé</label>
                                            <p class="mb-0 fw-bold text-success fs-5">
                                                {{ formatPrice(paiement.montant_paye) }} FCFA
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="info-item">
                                            <label class="small text-muted">Mode de paiement</label>
                                            <p class="mb-0">
                                                <i :class="getModeIcon(paiement.mode_paiement)" class="me-2"></i>
                                                {{ getModeLabel(paiement.mode_paiement) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="info-item">
                                            <label class="small text-muted">Date de transaction</label>
                                            <p class="mb-0">{{ formatDateTime(paiement.date_transaction) }}</p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="info-item">
                                            <label class="small text-muted">ID Transaction</label>
                                            <p class="mb-0 font-monospace small">{{ paiement.transaction_id }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Prochaines étapes -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list-check me-2"></i>Prochaines étapes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item completed">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Paiement confirmé</h6>
                                    <p class="mb-0 text-muted small">
                                        Votre paiement a été traité avec succès
                                    </p>
                                    <small class="text-success">
                                        <i class="fas fa-check me-1"></i>Terminé
                                    </small>
                                </div>
                            </div>

                            <div class="timeline-item" :class="{ 'completed': isItemConfirmed() }">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">{{ getNextStepTitle() }}</h6>
                                    <p class="mb-0 text-muted small">{{ getNextStepDescription() }}</p>
                                    <small :class="isItemConfirmed() ? 'text-success' : 'text-warning'">
                                        <i :class="isItemConfirmed() ? 'fas fa-check' : 'fas fa-clock'" class="me-1"></i>
                                        {{ isItemConfirmed() ? 'Terminé' : 'En cours' }}
                                    </small>
                                </div>
                            </div>

                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Contact et finalisation</h6>
                                    <p class="mb-0 text-muted small">
                                        L'agence vous contactera pour finaliser les démarches
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-hourglass-half me-1"></i>À venir
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <button @click="imprimerRecu" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-print me-2"></i>Imprimer le reçu
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button @click="telechargerRecu" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-download me-2"></i>Télécharger PDF
                                </button>
                            </div>
                            <div class="col-md-4">
                                <Link :href="getReturnRoute()" class="btn btn-success w-100">
                                    <i class="fas fa-home me-2"></i>Retour à l'accueil
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation email -->
                <div class="alert alert-info mt-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-envelope me-3"></i>
                        <div>
                            <strong>Email de confirmation envoyé</strong>
                            <p class="mb-0">
                                Un email de confirmation avec tous les détails de votre transaction
                                a été envoyé à votre adresse email.
                            </p>
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

const props = defineProps({
    paiement: { type: Object, required: true }
})

// Méthodes
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getTypeLabel = (type) => {
    const labels = {
        'reservation': 'Réservation',
        'location': 'Location',
        'vente': 'Vente'
    }
    return labels[type] || type
}

const getModeLabel = (mode) => {
    const labels = {
        'mobile_money': 'Mobile Money',
        'carte': 'Carte bancaire',
        'virement': 'Virement bancaire'
    }
    return labels[mode] || mode
}

const getModeIcon = (mode) => {
    const icons = {
        'mobile_money': 'fas fa-mobile-alt',
        'carte': 'fas fa-credit-card',
        'virement': 'fas fa-university'
    }
    return icons[mode] || 'fas fa-money-bill'
}

const getBienImage = () => {
    const bien = props.paiement.reservation?.bien ||
        props.paiement.location?.bien ||
        props.paiement.vente?.bien
    return bien?.image ? `/storage/${bien.image}` : '/images/placeholder.jpg'
}

const getBienTitle = () => {
    const bien = props.paiement.reservation?.bien ||
        props.paiement.location?.bien ||
        props.paiement.vente?.bien
    return bien?.title || 'Bien immobilier'
}

const getBienCity = () => {
    const bien = props.paiement.reservation?.bien ||
        props.paiement.location?.bien ||
        props.paiement.vente?.bien
    return bien?.city || ''
}

const isItemConfirmed = () => {
    const item = props.paiement.reservation || props.paiement.location || props.paiement.vente
    return item?.statut === 'confirmee'
}

const getNextStepTitle = () => {
    switch (props.paiement.type) {
        case 'reservation':
            return 'Réservation confirmée'
        case 'location':
            return 'Location confirmée'
        case 'vente':
            return 'Vente confirmée'
        default:
            return 'Transaction confirmée'
    }
}

const getNextStepDescription = () => {
    switch (props.paiement.type) {
        case 'reservation':
            return 'Votre réservation est confirmée et le bien vous est réservé'
        case 'location':
            return 'Votre demande de location est confirmée'
        case 'vente':
            return 'Votre demande d\'achat est confirmée'
        default:
            return 'Votre transaction est confirmée'
    }
}

const getReturnRoute = () => {
    switch (props.paiement.type) {
        case 'reservation':
            return route('reservations.index')
        case 'location':
            return route('locations.index') // À adapter
        case 'vente':
            return route('ventes.index') // À adapter
        default:
            return route('home')
    }
}

const imprimerRecu = () => {
    window.print()
}

const telechargerRecu = () => {
    // Implémentation du téléchargement PDF
    alert('Fonctionnalité de téléchargement PDF à implémenter')
}
</script>

<style scoped>
/* Animation de succès */
.success-animation {
    animation: slideInUp 0.6s ease-out;
}

.checkmark-circle {
    width: 100px;
    height: 100px;
    position: relative;
    display: inline-block;
    vertical-align: top;
    margin: 0 auto;
}

.checkmark-circle::before {
    content: '';
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background-color: #28a745;
    display: block;
    animation: pulse 0.6s ease-out;
}

.checkmark {
    width: 60px;
    height: 30px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -60%) rotate(-45deg);
    border-left: 8px solid white;
    border-bottom: 8px solid white;
    animation: checkmark 0.3s 0.3s ease-out forwards;
    opacity: 0;
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 12px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -18px;
    top: 4px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #dee2e6;
    border: 3px solid #fff;
    box-shadow: 0 0 0 3px #dee2e6;
}

.timeline-item.completed .timeline-marker {
    background: #28a745;
    box-shadow: 0 0 0 3px #28a745;
}

.info-item {
    margin-bottom: 1rem;
}

.info-item label {
    display: block;
    margin-bottom: 4px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Animations */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes checkmark {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Impression */
@media print {
    .btn, .alert {
        display: none !important;
    }
}
</style>
