<template>
    <div class="site-mobile-menu site-navbar-target">
        <div class="site-mobile-menu-header">
            <div class="site-mobile-menu-close">
                <span class="icofont-close js-menu-toggle"></span>
            </div>
        </div>
        <div class="site-mobile-menu-body"></div>
    </div>

    <!-- Hero Section avec image du bien -->
    <div class="hero" style="height: 50vh;">
        <div class="hero-slide" :style="{ backgroundImage: `url(${bien.image ? `/storage/${bien.image}` : '/images/placeholder.jpg'})` }">
            <div class="container">
                <div class="row justify-content-center align-items-center" style="height: 50vh;">
                    <div class="col-lg-9 text-center">
                        <h1 class="heading text-white" data-aos="fade-up">{{ bien.title }}</h1>
                        <p class="text-white mb-5" data-aos="fade-up" data-aos-delay="100">
                            <i class="fas fa-map-marker-alt me-2"></i>{{ bien.address }}, {{ bien.city }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section principale des détails -->
    <section class="section">
        <div class="container">
            <div class="row">
                <!-- Colonne principale - Détails du bien -->
                <div class="col-lg-8">
                    <div class="property-single-content">
                        <!-- Prix et statut -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="price-tag">
                                <h2 class="text-primary font-weight-bold mb-0">{{ formatPrice(bien.price) }} FCFA</h2>
                                <span class="badge badge-success" :class="getStatusClass(bien.status)">{{ getStatusText(bien.status) }}</span>
                            </div>
                            <div class="property-meta">
                                <span class="badge bg-light text-dark px-3 py-2">{{ bien.category?.name || 'Non spécifiée' }}</span>
                            </div>
                        </div>

                        <!-- Caractéristiques principales -->
                        <div class="property-specs mb-5">
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <div class="spec-item text-center p-3 bg-light rounded">
                                        <i class="fas fa-bed text-primary fs-4 mb-2"></i>
                                        <div class="spec-number fw-bold">{{ bien.rooms }}</div>
                                        <div class="spec-label text-muted small">Chambres</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="spec-item text-center p-3 bg-light rounded">
                                        <i class="fas fa-bath text-primary fs-4 mb-2"></i>
                                        <div class="spec-number fw-bold">{{ bien.bathrooms }}</div>
                                        <div class="spec-label text-muted small">Salles de bain</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="spec-item text-center p-3 bg-light rounded">
                                        <i class="fas fa-layer-group text-primary fs-4 mb-2"></i>
                                        <div class="spec-number fw-bold">{{ bien.floors }}</div>
                                        <div class="spec-label text-muted small">Étages</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="spec-item text-center p-3 bg-light rounded">
                                        <i class="fas fa-ruler-combined text-primary fs-4 mb-2"></i>
                                        <div class="spec-number fw-bold">{{ bien.superficy }}</div>
                                        <div class="spec-label text-muted small">m²</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="property-description mb-5">
                            <h3 class="section-title mb-3">Description</h3>
                            <div class="description-content">
                                <p class="text-black-50 line-height-relaxed">
                                    {{ bien.description || 'Aucune description disponible pour cette propriété.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Localisation -->
                        <div class="property-location mb-5">
                            <h3 class="section-title mb-3">Localisation</h3>
                            <div class="location-info p-4 bg-light rounded">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="location-item mb-3">
                                            <i class="fas fa-map-marker-alt text-primary me-3"></i>
                                            <div>
                                                <strong>Adresse:</strong><br>
                                                <span class="text-muted">{{ bien.address }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="location-item mb-3">
                                            <i class="fas fa-city text-primary me-3"></i>
                                            <div>
                                                <strong>Ville:</strong><br>
                                                <span class="text-muted">{{ bien.city }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar - Actions et contact -->
                <div class="col-lg-4">
                    <div class="property-sidebar">
                        <!-- Carte de prise de rendez-vous pour visite -->
                        <div v-if="bien.status === 'disponible'" class="visit-card mb-4 p-4 bg-light border-primary border-2 rounded shadow">
                            <h4 class="text-primary mb-3">
                                <i class="fas fa-calendar-check me-2"></i>
                                Planifier une visite
                            </h4>
                            <p class="text-muted mb-3">
                                Prenez rendez-vous pour visiter cette propriété avant de la réserver
                            </p>

                            <button
                                @click="planifierVisite"
                                class="btn btn-primary btn-lg w-100 mb-3"
                                :disabled="visiteEnCours"
                            >
                                <i class="fas fa-eye me-2"></i>
                                {{ visiteEnCours ? 'Chargement...' : 'Prendre rendez-vous pour une visite' }}
                            </button>

                            <div class="alert alert-info mb-0 small">
                                <i class="fas fa-info-circle me-2"></i>
                                La visite est gratuite et vous permet de découvrir le bien avant toute réservation
                            </div>
                        </div>

                        <!-- Bouton de réservation principal -->
                        <div class="reservation-card mb-4 p-4 bg-white border rounded shadow-sm">
                            <h4 class="text-center mb-3">Intéressé par cette propriété ?</h4>
                            <div class="text-center mb-3">
                                <div class="price-display mb-2">
                                    <span class="fs-4 fw-bold text-primary">{{ formatPrice(bien.price) }} FCFA</span>
                                </div>
                            </div>

                            <div v-if="bien.status === 'disponible'" class="d-grid gap-2">
                                <button
                                    @click="ouvrirModalReservation"
                                    class="btn btn-success btn-lg py-3"
                                    :disabled="reservationEnCours"
                                >
                                    <i class="fas fa-handshake me-2"></i>
                                    {{ reservationEnCours ? 'Réservation en cours...' : 'Réserver ce bien' }}
                                </button>

                                <div v-if="bien.mandat" class="small text-muted text-center">
                                    <i class="fas fa-info-circle me-1"></i>
                                    {{ bien.mandat.type_mandat === 'vente' ? 'Disponible à la vente' : 'Disponible en location' }}
                                </div>

                                <button class="btn btn-outline-primary" @click="contacterAgent">
                                    <i class="fas fa-phone me-2"></i>Contacter l'agent
                                </button>
                            </div>

                            <div v-else-if="bien.status === 'reserve'" class="text-center">
                                <div class="alert alert-warning mb-3">
                                    <i class="fas fa-clock me-2"></i>
                                    Cette propriété est actuellement réservée
                                </div>
                                <button class="btn btn-outline-primary w-100" @click="contacterAgent">
                                    <i class="fas fa-bell me-2"></i>Être notifié si disponible
                                </button>
                            </div>

                            <div v-else-if="bien.status === 'vendu' || bien.status === 'loue'" class="text-center">
                                <div class="alert alert-secondary mb-3">
                                    <i class="fas fa-check-circle me-2"></i>
                                    {{ bien.status === 'vendu' ? 'Cette propriété a été vendue' : 'Cette propriété est louée' }}
                                </div>
                                <button class="btn btn-outline-primary w-100" @click="voirProprieteSimilaires">
                                    <i class="fas fa-search me-2"></i>Voir des propriétés similaires
                                </button>
                            </div>

                            <div v-else class="text-center">
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Cette propriété n'est pas disponible actuellement ({{ bien.status }})
                                </div>
                            </div>
                        </div>

                        <!-- Informations complémentaires -->
                        <div class="info-card mb-4 p-4 bg-light rounded">
                            <h5 class="mb-3">Informations supplémentaires</h5>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-tag text-primary me-2"></i>
                                    <strong>Référence:</strong> #{{ bien.id }}
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-calendar text-primary me-2"></i>
                                    <strong>Ajouté le:</strong> {{ formatDate(bien.created_at) }}
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-eye text-primary me-2"></i>
                                    <strong>Type:</strong> {{ bien.category?.name }}
                                </li>
                            </ul>
                        </div>

                        <!-- Partage sur les réseaux sociaux -->
                        <div class="share-card p-4 bg-light rounded">
                            <h5 class="mb-3">Partager cette propriété</h5>
                            <div class="social-share d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm flex-fill" @click="partagerSur('facebook')">
                                    <i class="fab fa-facebook-f"></i>
                                </button>
                                <button class="btn btn-outline-info btn-sm flex-fill" @click="partagerSur('twitter')">
                                    <i class="fab fa-twitter"></i>
                                </button>
                                <button class="btn btn-outline-success btn-sm flex-fill" @click="partagerSur('whatsapp')">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm flex-fill" @click="copierLien">
                                    <i class="fas fa-link"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bouton retour -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="text-center">
                        <Link
                            :href="route('biens.index')"
                            class="btn btn-outline-primary btn-lg px-5"
                        >
                            <i class="fas fa-arrow-left me-2"></i>Retour à la liste des biens
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal de confirmation de réservation -->
    <div class="modal fade" id="modalConfirmationReservation" tabindex="-1" aria-labelledby="modalConfirmationLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-primary fw-bold" id="modalConfirmationLabel">
                        <i class="fas fa-lock me-2"></i>Confirmation de réservation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="mb-4">
                        <p class="text-dark mb-3">
                            En cliquant sur <strong>« Confirmer la réservation »</strong>, vous effectuez un dépôt de garantie destiné à bloquer le bien immobilier sélectionné.
                        </p>
                        <p class="text-dark mb-3">
                            Ce dépôt constitue un <strong>engagement de réservation</strong> et sera déduit du montant total lors de la signature du contrat de vente ou de location.
                        </p>
                        <p class="text-dark mb-3">
                            Afin de valider définitivement votre réservation, vous devrez fournir certains <strong>documents justificatifs</strong> (pièce d'identité, justificatif de revenus, etc.).
                        </p>
                    </div>

                    <div class="bg-light p-3 rounded mb-4">
                        <p class="text-dark mb-2 fw-semibold">
                            <i class="fas fa-shield-alt text-primary me-2"></i>Ces documents permettent à notre agence de :
                        </p>
                        <ul class="mb-0 text-dark">
                            <li class="mb-2">Vérifier votre identité et la conformité de la transaction</li>
                            <li class="mb-2">Évaluer votre capacité financière avant validation</li>
                            <li class="mb-0">Garantir la sécurité juridique du processus de réservation</li>
                        </ul>
                    </div>

                    <div class="alert alert-info mb-4">
                        <i class="fas fa-user-shield me-2"></i>
                        Les fichiers transmis sont traités de manière <strong>confidentielle et sécurisée</strong> par notre équipe, conformément à notre politique de protection des données.
                    </div>

                    <div class="alert alert-success mb-4">
                        <i class="fas fa-undo me-2"></i>
                        En cas de refus de dossier ou d'indisponibilité du bien, votre dépôt de garantie vous sera <strong>intégralement remboursé</strong>.
                    </div>

                    <div class="alert alert-primary mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Le paiement est <strong>100% sécurisé</strong> via PayDunya (Wave, Orange Money, Free Money…).
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-success btn-lg px-4" @click="confirmerReservation">
                        <i class="fas fa-check-circle me-2"></i>Confirmer la réservation
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Layout from '@/Pages/Layout.vue'

export default {
    layout: Layout
}
</script>

<script setup>
import { Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref, onMounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    bien: {
        type: Object,
        required: true
    }
})

const reservationEnCours = ref(false)
const visiteEnCours = ref(false)
let modalInstance = null

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const formatDate = (dateString) => {
    if (!dateString) return 'Non spécifié'
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const getStatusText = (status) => {
    const statusTexts = {
        'disponible': 'Disponible',
        'reserve': 'Réservé',
        'vendu': 'Vendu',
        'loue': 'Loué'
    }
    return statusTexts[status] || status
}

const getStatusClass = (status) => {
    const statusClasses = {
        'disponible': 'badge-success',
        'reserve': 'badge-warning',
        'vendu': 'badge-danger',
        'loue': 'badge-secondary'
    }
    return statusClasses[status] || 'badge-secondary'
}

const ouvrirModalReservation = () => {
    console.log('=== OUVERTURE MODAL ===')

    const modalElement = document.getElementById('modalConfirmationReservation')
    console.log('modalElement trouvé?', modalElement !== null)

    if (!modalElement) {
        console.error('❌ Élément modal non trouvé')
        return
    }

    // ✅ Utiliser window.bootstrap au lieu de bootstrap
    if (typeof window.bootstrap !== 'undefined') {
        try {
            // Récupérer ou créer l'instance du modal
            let modal = window.bootstrap.Modal.getInstance(modalElement)
            if (!modal) {
                modal = new window.bootstrap.Modal(modalElement)
                console.log('✅ Modal créé')
            }
            modal.show()
            modalInstance = modal
            console.log('✅ Modal affiché')
        } catch (error) {
            console.error('❌ Erreur lors de l\'ouverture du modal:', error)
        }
    } else {
        console.error('❌ Bootstrap non disponible')
        alert('Erreur: Bootstrap non chargé correctement')
    }
}

const confirmerReservation = () => {
    console.log('=== CONFIRMER RÉSERVATION ===')
    console.log('bien_id:', props.bien.id)

    if (reservationEnCours.value) {
        console.log('⚠️ Réservation déjà en cours')
        return
    }

    // Fermer le modal
    if (modalInstance) {
        modalInstance.hide()
    }

    reservationEnCours.value = true
    console.log('✅ Tentative de redirection vers:', route('reservations.create', { bien_id: props.bien.id }))

    // ✅ nextTick est maintenant disponible
    nextTick(() => {
        router.get(route('reservations.create', { bien_id: props.bien.id }), {}, {
            onSuccess: () => {
                console.log('✅ Redirection réussie')
            },
            onError: (errors) => {
                console.error('❌ Erreur de redirection:', errors)
                alert('Erreur lors de la redirection. Consultez la console.')
                reservationEnCours.value = false
            },
            onFinish: () => {
                console.log('⏹️ Requête terminée')
            }
        })
    })
}

onMounted(() => {
    console.log('=== COMPONENT MOUNTED ===')

    if (typeof AOS !== 'undefined') {
        AOS.refresh()
    }

    // ✅ Vérification avec window.bootstrap
    nextTick(() => {
        const modalElement = document.getElementById('modalConfirmationReservation')
        console.log('Modal element existe?', modalElement !== null)
        console.log('Bootstrap existe?', typeof window.bootstrap !== 'undefined')

        if (modalElement && typeof window.bootstrap !== 'undefined') {
            modalInstance = new window.bootstrap.Modal(modalElement, {
                backdrop: 'static',
                keyboard: false
            })
            console.log('✅ Modal initialisé dans onMounted')
        } else {
            console.warn('⚠️ Modal ou Bootstrap non disponible au montage')
        }
    })
})
const planifierVisite = () => {
    if (visiteEnCours.value) return

    visiteEnCours.value = true

    router.get(route('visites.create', { bien_id: props.bien.id }), {}, {
        onError: (error) => {
            console.error('Erreur:', error)
            alert('Erreur lors de la planification de la visite')
        },
        onFinish: () => {
            visiteEnCours.value = false
        }
    })
}
const contacterAgent = () => {
    alert('Fonctionnalité de contact en cours de développement')
}

const voirProprieteSimilaires = () => {
    router.visit(route('biens.index', {
        categorie: props.bien.categorie_id,
        ville: props.bien.city
    }))
}

const partagerSur = (plateforme) => {
    const url = window.location.href
    const titre = `${props.bien.title} - ${formatPrice(props.bien.price)} FCFA`

    let shareUrl = ''

    switch(plateforme) {
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`
            break
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(titre)}`
            break
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${encodeURIComponent(titre + ' ' + url)}`
            break
    }

    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400')
    }
}

const copierLien = async () => {
    try {
        await navigator.clipboard.writeText(window.location.href)
        alert('Lien copié dans le presse-papiers !')
    } catch (error) {
        console.error('Erreur lors de la copie:', error)
        alert('Impossible de copier le lien')
    }
}

</script>

<style scoped>
.hero-slide {
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
}

.hero-slide::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.4);
}

.hero-slide > * {
    position: relative;
    z-index: 1;
}

.property-specs .spec-item {
    transition: transform 0.3s ease;
}

.property-specs .spec-item:hover {
    transform: translateY(-2px);
}

.property-sidebar {
    position: sticky;
    top: 2rem;
}

.visit-card {
    border: 2px solid #17a2b8;
    background: linear-gradient(135deg, #e8f5f7 0%, #ffffff 100%);
    transition: all 0.3s ease;
}

.visit-card:hover {
    box-shadow: 0 8px 20px rgba(23, 162, 184, 0.2);
    transform: translateY(-2px);
}

.reservation-card {
    border: 2px solid #e9ecef;
    transition: border-color 0.3s ease;
}

.reservation-card:hover {
    border-color: var(--bs-primary);
}

.btn-primary {
    background-color: #17a2b8;
    border-color: #17a2b8;
}

.btn-primary:hover {
    background-color: #138496;
    border-color: #117a8b;
}

.btn-success {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

.text-primary {
    color: #17a2b8 !important;
}

.section-title {
    color: #17a2b8;
    font-weight: 600;
    border-bottom: 2px solid #17a2b8;
    padding-bottom: 0.5rem;
    display: inline-block;
}

.line-height-relaxed {
    line-height: 1.8;
}

.social-share .btn {
    border-radius: 50%;
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.location-item {
    display: flex;
    align-items: flex-start;
}

.badge-success {
    background-color: #28a745;
}

.badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.badge-danger {
    background-color: #dc3545;
}

.badge-secondary {
    background-color: #6c757d;
}

.modal-content {
    border-radius: 15px;
    border: none;
}

.modal-header {
    background: linear-gradient(135deg, #e8f5f7 0%, #ffffff 100%);
    border-radius: 15px 15px 0 0;
}

.modal-body ul {
    padding-left: 1.5rem;
}

.modal-body ul li {
    line-height: 1.6;
}

@media (max-width: 768px) {
    .property-sidebar {
        position: static;
        margin-top: 2rem;
    }

    .hero {
        height: 40vh !important;
    }

    .hero .row {
        height: 40vh !important;
    }

    .modal-dialog {
        margin: 0.5rem;
    }
}
</style>
