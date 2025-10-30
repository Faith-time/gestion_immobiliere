<template>
    <div class="site-mobile-menu site-navbar-target">
        <div class="site-mobile-menu-header">
            <div class="site-mobile-menu-close">
                <span class="icofont-close js-menu-toggle"></span>
            </div>
        </div>
        <div class="site-mobile-menu-body"></div>
    </div>

    <!-- Hero Section avec Carousel d'images -->
    <div class="hero">
        <div class="carousel-container">
            <div v-for="(image, index) in generalImages"
                 :key="index"
                 class="carousel-slide"
                 :style="{
                    backgroundImage: `url(${getImageUrl(image)})`,
                    opacity: currentImageIndex === index ? 1 : 0,
                    zIndex: currentImageIndex === index ? 10 : 0,
                    transition: 'opacity 0.5s ease-in-out'
                }">
            </div>

            <div v-if="generalImages.length === 0"
                 class="carousel-slide"
                 :style="{ backgroundImage: `url('/images/placeholder.jpg')` }">
            </div>

            <div class="carousel-overlay"></div>

            <div class="container hero-content">
                <div class="row justify-content-center align-items-center h-100">
                    <div class="col-lg-9 text-center">
                        <h1 class="heading text-white" data-aos="fade-up">{{ bien.title }}</h1>
                        <p class="text-white mb-5" data-aos="fade-up" data-aos-delay="100">
                            <i class="fas fa-map-marker-alt me-2"></i>{{ bien.address }}, {{ bien.city }}
                        </p>
                        <div v-if="isAppartementCategory" data-aos="fade-up" data-aos-delay="200">
                            <span class="badge bg-white text-primary px-4 py-2 fs-5">
                                <i class="fas fa-building me-2"></i>Immeuble d'appartements
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="generalImages.length > 1" class="carousel-controls">
                <button @click="previousImage" class="carousel-btn carousel-btn-prev">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="carousel-indicators">
                    <button v-for="(image, index) in generalImages"
                            :key="index"
                            @click="currentImageIndex = index"
                            :class="['indicator', { active: currentImageIndex === index }]"
                            :style="{ backgroundImage: `url(${getImageUrl(image)})` }">
                    </button>
                </div>
                <button @click="nextImage" class="carousel-btn carousel-btn-next">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <div v-if="generalImages.length > 0" class="image-counter">
                {{ currentImageIndex + 1 }} / {{ generalImages.length }}
            </div>
        </div>
    </div>

    <!-- Section principale -->
    <section class="section">
        <div class="container">
            <div class="row">
                <!-- Colonne principale -->
                <div class="col-lg-8">
                    <div class="property-single-content">
                        <!-- Prix et statut -->
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                            <div class="price-tag">
                                <h2 class="text-primary font-weight-bold mb-0">{{ formatPrice(bien.price) }} FCFA</h2>
                                <span class="badge" :class="getStatusClass(bien.status)">{{ getStatusText(bien.status) }}</span>
                            </div>
                            <div class="property-meta">
                                <span class="badge bg-light text-dark px-3 py-2">{{ bien.category?.name || 'Non spécifiée' }}</span>
                            </div>
                        </div>

                        <!-- SECTION APPARTEMENTS -->
                        <div v-if="isAppartementCategory && bien.appartements && bien.appartements.length > 0" class="appartements-section mb-5">
                            <div class="card border-primary shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h3 class="mb-0 d-flex align-items-center justify-content-between">
                                        <span>
                                            <i class="fas fa-building me-2"></i>
                                            Appartements de l'immeuble
                                        </span>
                                        <span class="badge bg-white text-primary">{{ bien.appartements.length }} appartements</span>
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <!-- Statistiques -->
                                    <div v-if="occupationStats" class="row g-3 mb-4">
                                        <div class="col-6 col-md-3">
                                            <div class="stat-card bg-light p-3 rounded text-center">
                                                <div class="stat-number text-primary fw-bold fs-4">{{ occupationStats.total }}</div>
                                                <div class="stat-label text-muted small">Total</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="stat-card bg-success bg-opacity-10 p-3 rounded text-center">
                                                <div class="stat-number text-success fw-bold fs-4">{{ occupationStats.disponibles }}</div>
                                                <div class="stat-label text-muted small">Disponibles</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="stat-card bg-info bg-opacity-10 p-3 rounded text-center">
                                                <div class="stat-number text-info fw-bold fs-4">{{ occupationStats.loues }}</div>
                                                <div class="stat-label text-muted small">Loués</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="stat-card bg-primary bg-opacity-10 p-3 rounded text-center">
                                                <div class="stat-number text-primary fw-bold fs-4">{{ occupationStats.taux_occupation }}%</div>
                                                <div class="stat-label text-muted small">Occupation</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Barre de progression -->
                                    <div v-if="occupationStats" class="mb-4">
                                        <div class="d-flex justify-content-between text-sm mb-2">
                                            <span class="text-muted">Taux d'occupation</span>
                                            <span class="fw-semibold">{{ occupationStats.taux_occupation }}%</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-primary"
                                                 :style="`width: ${occupationStats.taux_occupation}%`">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Liste des appartements CLIQUABLES -->
                                    <div class="appartements-list">
                                        <h5 class="mb-3">Sélectionnez un appartement</h5>
                                        <div class="row g-3">
                                            <div v-for="appartement in bien.appartements"
                                                 :key="appartement.id"
                                                 class="col-md-6 col-lg-4">
                                                <div
                                                    @click="selectionnerAppartement(appartement)"
                                                    class="appartement-card border rounded p-3 h-100 position-relative"
                                                    :class="{
                                                        'border-success': appartement.statut === 'disponible',
                                                        'border-info': appartement.statut === 'loue',
                                                        'border-warning': appartement.statut === 'reserve',
                                                        'border-secondary': appartement.statut === 'maintenance',
                                                        'selected-appartement': selectedAppartement?.id === appartement.id,
                                                        'appartement-disabled': !isAppartementSelectable(appartement),
                                                        'appartement-clickable': isAppartementSelectable(appartement)
                                                    }">

                                                    <!-- Badge sélectionné -->
                                                    <div v-if="selectedAppartement?.id === appartement.id"
                                                         class="selected-badge">
                                                        <i class="fas fa-check-circle"></i>
                                                    </div>

                                                    <!-- Badge statut -->
                                                    <span class="badge position-absolute top-0 end-0 m-2"
                                                          :class="{
                                                              'bg-success': appartement.statut === 'disponible',
                                                              'bg-info': appartement.statut === 'loue',
                                                              'bg-warning': appartement.statut === 'reserve',
                                                              'bg-secondary': appartement.statut === 'maintenance'
                                                          }">
                                                        {{ getStatutLabel(appartement.statut) }}
                                                    </span>

                                                    <!-- Image -->
                                                    <div v-if="getAppartementFirstImage(appartement)"
                                                         class="appartement-image mb-3 rounded overflow-hidden"
                                                         style="height: 150px;">
                                                        <img :src="getAppartementFirstImage(appartement)"
                                                             :alt="appartement.numero"
                                                             class="w-100 h-100 object-fit-cover">
                                                    </div>

                                                    <h6 class="fw-bold mb-2">{{ appartement.numero }}</h6>
                                                    <div class="text-muted small mb-2">{{ getEtageLabel(appartement.etage) }}</div>

                                                    <!-- Détails -->
                                                    <div class="appartement-details small">
                                                        <div class="d-flex align-items-center mb-1">
                                                            <i class="fas fa-ruler-combined text-primary me-2"></i>
                                                            <span>{{ appartement.superficie }} m²</span>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-1">
                                                            <i class="fas fa-door-open text-primary me-2"></i>
                                                            <span>{{ appartement.pieces }} pièce(s)</span>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-1">
                                                            <i class="fas fa-bed text-primary me-2"></i>
                                                            <span>{{ appartement.chambres }} chambre(s)</span>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-bath text-primary me-2"></i>
                                                            <span>{{ appartement.salles_bain }} sdb</span>
                                                        </div>
                                                    </div>

                                                    <!-- Locataire si loué -->
                                                    <div v-if="appartement.location_active && appartement.location_active.client"
                                                         class="mt-2 pt-2 border-top">
                                                        <div class="text-muted small">
                                                            <i class="fas fa-user me-1"></i>
                                                            {{ appartement.location_active.client.name }}
                                                        </div>
                                                    </div>

                                                    <!-- Bouton voir images -->
                                                    <button v-if="getAppartementImagesCount(appartement) > 0"
                                                            @click.stop="voirImagesAppartement(appartement)"
                                                            class="btn btn-sm btn-outline-primary w-100 mt-2">
                                                        <i class="fas fa-images me-1"></i>
                                                        Voir {{ getAppartementImagesCount(appartement) }} photo(s)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Caractéristiques (biens non-appartement) -->
                        <div v-else class="property-specs mb-5">
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

                                <div v-if="bien.kitchens" class="col-6 col-md-3">
                                    <div class="spec-item text-center p-3 bg-light rounded">
                                        <i class="fas fa-utensils text-primary fs-4 mb-2"></i>
                                        <div class="spec-number fw-bold">{{ bien.kitchens }}</div>
                                        <div class="spec-label text-muted small">Cuisines</div>
                                    </div>
                                </div>
                                <div v-if="bien.living_rooms" class="col-6 col-md-3">
                                    <div class="spec-item text-center p-3 bg-light rounded">
                                        <i class="fas fa-couch text-primary fs-4 mb-2"></i>
                                        <div class="spec-number fw-bold">{{ bien.living_rooms }}</div>
                                        <div class="spec-label text-muted small">Salons</div>
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

                        <!-- Galerie -->
                        <div v-if="generalImages.length > 1" class="property-gallery mb-5">
                            <h3 class="section-title mb-3">Galerie photos</h3>
                            <div class="gallery-grid">
                                <div v-for="(image, index) in generalImages"
                                     :key="index"
                                     @click="currentImageIndex = index"
                                     :class="['gallery-item', { active: currentImageIndex === index }]"
                                     :style="{ backgroundImage: `url(${getImageUrl(image)})` }">
                                    <span v-if="image.libelle" class="gallery-label">{{ image.libelle }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="property-description mb-5">
                            <h3 class="section-title mb-3">Description</h3>
                            <div class="description-content">
                                <p class="text-black-50 line-height-relaxed">
                                    {{ bien.description || 'Aucune description disponible.' }}
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

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="property-sidebar">
                        <!-- Appartement sélectionné -->
                        <div v-if="isAppartementCategory && selectedAppartement"
                             class="selected-appartement-card mb-4 p-4 bg-light border-success border-2 rounded shadow">
                            <h5 class="text-success mb-3">
                                <i class="fas fa-check-circle me-2"></i>
                                Appartement sélectionné
                            </h5>
                            <div class="mb-3">
                                <strong>{{ selectedAppartement.numero }}</strong><br>
                                <small class="text-muted">{{ getEtageLabel(selectedAppartement.etage) }}</small>
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <i class="fas fa-ruler-combined text-primary me-1"></i>
                                    {{ selectedAppartement.superficie }} m²
                                </div>
                                <div class="col-6">
                                    <i class="fas fa-bed text-primary me-1"></i>
                                    {{ selectedAppartement.chambres }} ch.
                                </div>
                            </div>
                            <button @click="deselectionnerAppartement"
                                    class="btn btn-sm btn-outline-secondary w-100 mt-3">
                                Changer d'appartement
                            </button>
                        </div>

                        <!-- Carte visite -->
                        <div v-if="canTakeAction" class="visit-card mb-4 p-4 bg-light border-primary border-2 rounded shadow">
                            <h4 class="text-primary mb-3">
                                <i class="fas fa-calendar-check me-2"></i>
                                Planifier une visite
                            </h4>
                            <p class="text-muted mb-3">
                                Visitez {{ isAppartementCategory ? 'l\'immeuble' : 'la propriété' }} avant de réserver
                            </p>
                            <button
                                @click="ouvrirModalVisite"
                                class="btn btn-primary btn-lg w-100 mb-3"
                                :disabled="!canTakeVisiteAction || visiteEnCours"
                                :class="{ 'disabled-action': !canTakeVisiteAction }">
                                <i class="fas fa-eye me-2"></i>
                                Prendre rendez-vous
                            </button>
                            <div class="alert alert-info mb-0 small">
                                <i class="fas fa-info-circle me-2"></i>
                                Visite gratuite sans engagement
                            </div>
                        </div>

                        <!-- Carte réservation -->
                        <div class="reservation-card mb-4 p-4 bg-white border rounded shadow-sm">
                            <h4 class="text-center mb-3">Intéressé par cette propriété ?</h4>
                            <div class="text-center mb-3">
                                <span class="fs-4 fw-bold text-primary">{{ formatPrice(bien.price) }} FCFA</span>
                            </div>

                            <!-- Immeubles -->
                            <div v-if="isAppartementCategory">
                                <div v-if="hasAppartementDisponible" class="d-grid gap-2">
                                    <button
                                        @click="ouvrirModalReservation"
                                        class="btn btn-success btn-lg py-3"
                                        :disabled="!canTakeReservationAction || reservationEnCours"
                                        :class="{ 'disabled-action': !canTakeReservationAction }">
                                        <i class="fas fa-handshake me-2"></i>
                                        {{ reservationEnCours ? 'Réservation...' : 'Réserver cet appartement' }}
                                    </button>

                                    <div v-if="!selectedAppartement" class="alert alert-warning small mb-0">
                                        <i class="fas fa-hand-pointer me-1"></i>
                                        Sélectionnez un appartement disponible ci-dessus
                                    </div>

                                    <div class="small text-muted text-center">
                                        <i class="fas fa-info-circle me-1"></i>
                                        {{ occupationStats.disponibles }} appartement(s) disponible(s)
                                    </div>

                                    <button class="btn btn-outline-primary" @click="contacterAgent">
                                        <i class="fas fa-phone me-2"></i>Contacter l'agent
                                    </button>
                                </div>

                                <div v-else class="text-center">
                                    <div class="alert alert-warning mb-3">
                                        <i class="fas fa-building me-2"></i>
                                        Tous les appartements sont occupés
                                    </div>
                                    <button class="btn btn-secondary btn-lg py-3 w-100 disabled-action" disabled>
                                        <i class="fas fa-ban me-2"></i>
                                        Aucun appartement disponible
                                    </button>
                                    <button class="btn btn-outline-primary w-100 mt-2" @click="contacterAgent">
                                        <i class="fas fa-bell me-2"></i>Être notifié
                                    </button>
                                </div>
                            </div>

                            <!-- Autres biens -->
                            <div v-else>
                                <div v-if="bien.status === 'disponible'" class="d-grid gap-2">
                                    <button @click="ouvrirModalReservation"
                                            class="btn btn-success btn-lg py-3"
                                            :disabled="reservationEnCours">
                                        <i class="fas fa-handshake me-2"></i>
                                        {{ reservationEnCours ? 'Réservation...' : 'Réserver ce bien' }}
                                    </button>
                                    <button class="btn btn-outline-primary" @click="contacterAgent">
                                        <i class="fas fa-phone me-2"></i>Contacter l'agent
                                    </button>
                                </div>
                                <div v-else class="text-center">
                                    <div class="alert alert-secondary mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Ce bien n'est pas disponible ({{ getStatusText(bien.status) }})
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informations -->
                        <div class="info-card mb-4 p-4 bg-light rounded">
                            <h5 class="mb-3">Informations</h5>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-tag text-primary me-2"></i>
                                    <strong>Référence:</strong> #{{ bien.id }}
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-calendar text-primary me-2"></i>
                                    <strong>Ajouté le:</strong> {{ formatDate(bien.created_at) }}
                                </li>
                                <li v-if="isAppartementCategory" class="mb-2">
                                    <i class="fas fa-building text-primary me-2"></i>
                                    <strong>Appartements:</strong> {{ bien.appartements.length }}
                                </li>
                            </ul>
                        </div>

                        <!-- Partage -->
                        <div class="share-card p-4 bg-light rounded">
                            <h5 class="mb-3">Partager</h5>
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

            <!-- Retour -->
            <div class="row mt-5">
                <div class="col-12 text-center">
                    <Link :href="route('biens.index')" class="btn btn-outline-primary btn-lg px-5">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                    </Link>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal confirmation réservation -->
    <div class="modal fade" id="modalConfirmationReservation" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-primary fw-bold">
                        <i class="fas fa-lock me-2"></i>Confirmation de réservation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <!-- Appartement sélectionné -->
                    <div v-if="isAppartementCategory && selectedAppartement" class="alert alert-success mb-4">
                        <h6 class="alert-heading">
                            <i class="fas fa-home me-2"></i>Appartement sélectionné
                        </h6>
                        <p class="mb-0">
                            <strong>{{ selectedAppartement.numero }}</strong> -
                            {{ getEtageLabel(selectedAppartement.etage) }} -
                            {{ selectedAppartement.superficie }} m²
                        </p>
                    </div>

                    <div class="mb-4">
                        <p class="text-dark mb-3">
                            En confirmant, vous effectuez un <strong>dépôt de garantie</strong> pour bloquer
                            {{ isAppartementCategory ? 'cet appartement' : 'ce bien' }}.
                        </p>
                        <p class="text-dark mb-3">
                            Ce dépôt sera déduit du montant total lors de la signature du contrat.
                        </p>
                    </div>

                    <div class="bg-light p-3 rounded mb-4">
                        <p class="text-dark mb-2 fw-semibold">
                            <i class="fas fa-shield-alt text-primary me-2"></i>Documents requis :
                        </p>
                        <ul class="mb-0 text-dark small">
                            <li>Pièce d'identité valide</li>
                            <li>Justificatif de revenus</li>
                            <li>Autres documents selon le bien</li>
                        </ul>
                    </div>

                    <div class="alert alert-info mb-4 small">
                        <i class="fas fa-user-shield me-2"></i>
                        Données traitées de manière <strong>confidentielle et sécurisée</strong>
                    </div>

                    <div class="alert alert-success mb-4 small">
                        <i class="fas fa-undo me-2"></i>
                        <strong>Remboursement intégral</strong> en cas de refus ou indisponibilité
                    </div>

                    <div class="alert alert-primary mb-0 small">
                        <i class="fas fa-credit-card me-2"></i>
                        Paiement <strong>100% sécurisé</strong> via PayDunya
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-success btn-lg px-4" @click="confirmerReservation">
                        <i class="fas fa-check-circle me-2"></i>Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal images appartement -->
    <div class="modal fade" id="modalImagesAppartement" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ selectedAppartement?.numero }} - {{ selectedAppartement?.etage !== undefined ? getEtageLabel(selectedAppartement.etage) : '' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div v-if="appartementImages.length > 0" class="row g-3">
                        <div v-for="(image, index) in appartementImages" :key="index" class="col-md-4">
                            <div class="appartement-image-modal">
                                <img :src="getImageUrl(image)" :alt="image.libelle" class="w-100 rounded">
                                <div v-if="image.libelle" class="image-label-modal">{{ image.libelle }}</div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-5 text-muted">
                        <i class="fas fa-images fs-1 mb-3"></i>
                        <p>Aucune image disponible</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ModalDemandeVisite :bien="bien" :appartement="selectedAppartement" />
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
import { ref, computed, onMounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import ModalDemandeVisite from '../Visites/Create.vue'

const props = defineProps({
    bien: {
        type: Object,
        required: true
    }
})

const currentImageIndex = ref(0)
const reservationEnCours = ref(false)
const visiteEnCours = ref(false)
const selectedAppartement = ref(null)
let modalInstance = null

// Vérifier si c'est un immeuble d'appartements
const isAppartementCategory = computed(() => {
    return props.bien.category &&
        props.bien.category.name &&
        props.bien.category.name.toLowerCase() === 'appartement'
})

// Images générales (sans appartement_id)
const generalImages = computed(() => {
    if (!props.bien.images || props.bien.images.length === 0) return []
    return props.bien.images.filter(img => !img.appartement_id)
})

// Statistiques d'occupation
const occupationStats = computed(() => {
    if (!isAppartementCategory.value) return null

    const appartements = props.bien.appartements || []
    const total = appartements.length

    if (total === 0) {
        return { total: 0, disponibles: 0, loues: 0, reserves: 0, taux_occupation: 0 }
    }

    const disponibles = appartements.filter(a => a.statut === 'disponible').length
    const loues = appartements.filter(a => a.statut === 'loue').length
    const reserves = appartements.filter(a => a.statut === 'reserve').length

    return {
        total,
        disponibles,
        loues,
        reserves,
        taux_occupation: total > 0 ? Math.round((loues / total) * 100) : 0
    }
})

// Y a-t-il au moins un appartement disponible ?
const hasAppartementDisponible = computed(() => {
    if (!isAppartementCategory.value) return false
    return occupationStats.value && occupationStats.value.disponibles > 0
})

// Peut-on prendre une visite ? (au moins 1 appartement disponible)
const canTakeVisiteAction = computed(() => {
    if (isAppartementCategory.value) {
        return hasAppartementDisponible.value
    }
    return props.bien.status === 'disponible'
})

// Peut-on faire une réservation ? (appartement sélectionné ET disponible)
const canTakeReservationAction = computed(() => {
    if (isAppartementCategory.value) {
        return selectedAppartement.value && isAppartementSelectable(selectedAppartement.value)
    }
    return props.bien.status === 'disponible'
})

// Alias pour compatibilité
const canTakeAction = computed(() => canTakeVisiteAction.value)

// Images de l'appartement sélectionné
const appartementImages = computed(() => {
    if (!selectedAppartement.value || !props.bien.images) return []
    return props.bien.images.filter(img => img.appartement_id === selectedAppartement.value.id)
})

const pricePerSquareMeter = computed(() => {
    return props.bien.superficy ? Math.round((props.bien.price / props.bien.superficy) * 100) / 100 : 0
})

// Vérifier si un appartement est sélectionnable
const isAppartementSelectable = (appartement) => {
    return appartement.statut === 'disponible'
}

// Sélectionner un appartement
const selectionnerAppartement = (appartement) => {
    if (!isAppartementSelectable(appartement)) {
        return
    }
    selectedAppartement.value = appartement
}

// Désélectionner
const deselectionnerAppartement = () => {
    selectedAppartement.value = null
}

const getImageUrl = (image) => {
    return image.url || `/storage/${image.chemin_image}`
}

const getAppartementFirstImage = (appartement) => {
    if (!props.bien.images) return null
    const images = props.bien.images.filter(img => img.appartement_id === appartement.id)
    return images.length > 0 ? getImageUrl(images[0]) : null
}

const getAppartementImagesCount = (appartement) => {
    if (!props.bien.images) return 0
    return props.bien.images.filter(img => img.appartement_id === appartement.id).length
}

const voirImagesAppartement = (appartement) => {
    selectedAppartement.value = appartement
    const modalElement = document.getElementById('modalImagesAppartement')
    if (modalElement && typeof window.bootstrap !== 'undefined') {
        const modal = new window.bootstrap.Modal(modalElement)
        modal.show()
    }
}

const getEtageLabel = (etage) => {
    const labels = {
        0: 'Rez-de-chaussée',
        1: '1er étage',
        2: '2ème étage',
        3: '3ème étage'
    }
    return labels[etage] || `${etage}ème étage`
}

const getStatutLabel = (statut) => {
    const labels = {
        'disponible': 'Disponible',
        'loue': 'Loué',
        'reserve': 'Réservé',
        'maintenance': 'Maintenance'
    }
    return labels[statut] || statut
}

const formatPrice = (price) => {
    if (!price) return '0'
    return new Intl.NumberFormat('fr-FR').format(Math.round(price))
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
        'loue': 'Loué',
        'en_validation': 'En validation',
        'rejete': 'Rejeté'
    }
    return statusTexts[status] || status
}

const getStatusClass = (status) => {
    const statusClasses = {
        'disponible': 'badge-success',
        'reserve': 'badge-warning',
        'vendu': 'badge-danger',
        'loue': 'badge-secondary',
        'en_validation': 'badge-info',
        'rejete': 'badge-danger'
    }
    return statusClasses[status] || 'badge-secondary'
}

const nextImage = () => {
    if (generalImages.value.length > 0) {
        currentImageIndex.value = (currentImageIndex.value + 1) % generalImages.value.length
    }
}

const previousImage = () => {
    if (generalImages.value.length > 0) {
        currentImageIndex.value = (currentImageIndex.value - 1 + generalImages.value.length) % generalImages.value.length
    }
}

const ouvrirModalReservation = () => {
    // Pour les immeubles, vérifier qu'un appartement est sélectionné
    if (isAppartementCategory.value && !selectedAppartement.value) {
        alert('Veuillez sélectionner un appartement disponible avant de réserver.')
        return
    }

    if (!canTakeReservationAction.value) {
        alert('Cette action n\'est pas disponible.')
        return
    }

    const modalElement = document.getElementById('modalConfirmationReservation')
    if (!modalElement) return

    if (typeof window.bootstrap !== 'undefined') {
        let modal = window.bootstrap.Modal.getInstance(modalElement)
        if (!modal) {
            modal = new window.bootstrap.Modal(modalElement, {
                backdrop: 'static',
                keyboard: false
            })
        }
        modal.show()
    }
}

const confirmerReservation = () => {
    if (reservationEnCours.value) return
    if (!canTakeReservationAction.value) return

    const modalElement = document.getElementById('modalConfirmationReservation')
    if (modalElement && typeof window.bootstrap !== 'undefined') {
        const modal = window.bootstrap.Modal.getInstance(modalElement)
        if (modal) modal.hide()
    }

    reservationEnCours.value = true

    nextTick(() => {
        let url = route('reservations.create', { bien_id: props.bien.id })

        // ✅ Ajouter appartement_id si disponible
        if (isAppartementCategory.value && selectedAppartement.value) {
            url = route('reservations.create', {
                bien_id: props.bien.id,
                appartement_id: selectedAppartement.value.id
            })

            console.log('✅ Redirection avec URL:', url)
            console.log('✅ Appartement sélectionné:', selectedAppartement.value.id)
        }

        window.location.href = url
    })
}

const ouvrirModalVisite = () => {
    if (!canTakeVisiteAction.value) {
        if (isAppartementCategory.value) {
            alert('Aucun appartement disponible actuellement.')
        } else {
            alert('Ce bien n\'est pas disponible.')
        }
        return
    }

    const modalElement = document.getElementById('modalDemandeVisite')
    if (modalElement && typeof window.bootstrap !== 'undefined') {
        let modal = window.bootstrap.Modal.getInstance(modalElement)
        if (!modal) {
            modal = new window.bootstrap.Modal(modalElement, {
                backdrop: 'static',
                keyboard: false
            })
        }
        modal.show()
    }
}

const contacterAgent = () => {
    alert('Fonctionnalité en cours de développement')
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
    switch (plateforme) {
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
        alert('Lien copié !')
    } catch (error) {
        console.error('Erreur:', error)
        alert('Impossible de copier le lien')
    }
}

onMounted(() => {
    if (typeof AOS !== 'undefined') {
        AOS.refresh()
    }

    nextTick(() => {
        const modalElement = document.getElementById('modalConfirmationReservation')
        if (modalElement && typeof window.bootstrap !== 'undefined') {
            modalInstance = new window.bootstrap.Modal(modalElement, {
                backdrop: 'static',
                keyboard: false
            })
        }
    })
})
</script>

<style scoped>
/* Appartements cliquables */
.appartement-clickable {
    cursor: pointer;
    transition: all 0.3s ease;
}

.appartement-clickable:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
}

.appartement-disabled {
    cursor: not-allowed;
    opacity: 0.6;
    filter: grayscale(30%);
}

/* Appartement sélectionné */
.selected-appartement {
    border-color: #28a745 !important;
    border-width: 3px !important;
    background: linear-gradient(135deg, #e8f5e9 0%, #ffffff 100%);
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3) !important;
}

.selected-badge {
    position: absolute;
    top: -10px;
    left: -10px;
    background: #28a745;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    z-index: 10;
    border: 3px solid white;
    box-shadow: 0 4px 10px rgba(40, 167, 69, 0.4);
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

/* Carte appartement sélectionné */
.selected-appartement-card {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Boutons désactivés */
.disabled-action {
    cursor: not-allowed !important;
    opacity: 0.6;
    pointer-events: none;
}

.disabled-action:hover {
    transform: none !important;
    box-shadow: none !important;
}

/* Carousel */
.carousel-container {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.carousel-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.carousel-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.4);
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
    height: 100%;
}

.carousel-controls {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.carousel-btn {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #17a2b8;
    font-size: 1.2rem;
}

.carousel-btn:hover {
    background: white;
    transform: scale(1.1);
}

.carousel-indicators {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding: 0.5rem;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 10px;
    max-width: 400px;
}

.indicator {
    width: 80px;
    height: 60px;
    border: 3px solid transparent;
    border-radius: 5px;
    background-size: cover;
    background-position: center;
    cursor: pointer;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.indicator:hover,
.indicator.active {
    border-color: white;
    transform: scale(1.05);
}

.image-counter {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    z-index: 10;
    font-size: 0.9rem;
}

.hero {
    height: 50vh;
    position: relative;
}

.property-gallery {
    margin-top: 2rem;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
}

.gallery-item {
    position: relative;
    width: 100%;
    padding-bottom: 100%;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    background-size: cover;
    background-position: center;
    transition: all 0.3s ease;
    border: 3px solid transparent;
}

.gallery-item:hover {
    transform: scale(1.05);
}

.gallery-item.active {
    border-color: #17a2b8;
    box-shadow: 0 0 10px rgba(23, 162, 184, 0.5);
}

.gallery-label {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 0.5rem;
    font-size: 0.75rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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

.btn-primary:hover:not(.disabled-action) {
    background-color: #138496;
    border-color: #117a8b;
}

.btn-success {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-success:hover:not(.disabled-action) {
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

.badge-info {
    background-color: #17a2b8;
    color: white;
}

.appartements-section .card {
    border-width: 2px;
}

.stat-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.appartement-card {
    transition: all 0.3s ease;
    background: white;
}

.appartement-image {
    background: #f8f9fa;
}

.appartement-image img {
    object-fit: cover;
}

.appartement-image-modal {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
}

.appartement-image-modal img {
    transition: transform 0.3s ease;
}

.appartement-image-modal:hover img {
    transform: scale(1.05);
}

.image-label-modal {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 0.5rem;
    font-size: 0.875rem;
}

.object-fit-cover {
    object-fit: cover;
}

.modal-content {
    border-radius: 15px;
    border: none;
}

.modal-header {
    background: linear-gradient(135deg, #e8f5f7 0%, #ffffff 100%);
    border-radius: 15px 15px 0 0;
}

@media (max-width: 768px) {
    .property-sidebar {
        position: static;
        margin-top: 2rem;
    }

    .hero {
        height: 40vh !important;
    }

    .carousel-controls {
        flex-wrap: wrap;
        bottom: 1rem;
        gap: 0.5rem;
    }

    .carousel-indicators {
        max-width: 100%;
    }

    .gallery-grid {
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 0.5rem;
    }
}
</style>
