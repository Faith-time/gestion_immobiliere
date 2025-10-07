<template>
    <pre>{{ JSON.stringify($page.props.debug, null, 2) }}</pre>

    <div>
        <!-- Menu mobile -->
        <div class="site-mobile-menu site-navbar-target">
            <div class="site-mobile-menu-header">
                <div class="site-mobile-menu-close">
                    <span class="icofont-close js-menu-toggle"></span>
                </div>
            </div>
            <div class="site-mobile-menu-body"></div>
        </div>

        <!-- Section Hero avec slider -->
        <div class="hero-section position-relative">
            <div class="hero-slider">
                <div
                    v-for="(bien, index) in heroSliderBiens"
                    :key="bien.id"
                    class="slide-item"
                >
                    <img
                        :src="bien.image ? `/storage/${bien.image}` : '/images/placeholder.jpg'"
                        :alt="`${bien.title} - Slide ${index + 1}`"
                        class="img-fluid w-100 h-100 object-cover"
                    />
                    <div class="slide-overlay"></div>
                </div>

                <!-- Slides par défaut si pas assez de biens -->
                <div v-if="heroSliderBiens.length === 0" class="slide-item">
                    <img :src="defaultImage1" alt="Slide par défaut 1" class="img-fluid w-100 h-100 object-cover" />
                    <div class="slide-overlay"></div>
                </div>
                <div v-if="heroSliderBiens.length <= 1" class="slide-item">
                    <img :src="defaultImage2" alt="Slide par défaut 2" class="img-fluid w-100 h-100 object-cover" />
                    <div class="slide-overlay"></div>
                </div>
                <div v-if="heroSliderBiens.length <= 2" class="slide-item">
                    <img :src="defaultImage3" alt="Slide par défaut 3" class="img-fluid w-100 h-100 object-cover" />
                    <div class="slide-overlay"></div>
                </div>
            </div>

            <!-- Contenu Hero superposé -->
            <div class="hero-content position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                <div class="text-center text-light">
                    <h1 class="hero-title mb-4" data-aos="fade-up">
                        Trouvez facilement la maison de vos rêves au Sénégal
                    </h1>

                    <!-- Barre de recherche principale -->
                    <form
                        @submit.prevent="performMainSearch"
                        class="hero-search-form d-flex align-items-stretch mb-3"
                        data-aos="fade-up"
                        data-aos-delay="200"
                    >
                        <div class="search-input-wrapper">
                            <input
                                type="text"
                                class="form-control search-input"
                                placeholder="Votre quartier ou ville. ex: Dakar, Almadies"
                                v-model="mainSearchQuery"
                                @input="onMainSearchInput"
                            />

                            <!-- Suggestions de recherche -->
                            <div v-if="searchSuggestions.length > 0" class="search-suggestions">
                                <div
                                    v-for="suggestion in searchSuggestions"
                                    :key="suggestion.id"
                                    class="suggestion-item"
                                    @click="selectSuggestion(suggestion)"
                                >
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    {{ suggestion.label }}
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary search-btn">
                            <i class="fas fa-search me-2"></i>
                            Rechercher
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Section Propriétés principales -->
        <div class="section py-5">
            <div class="container">
                <!-- En-tête de section -->
                <div class="row mb-5 align-items-center">
                    <div class="col-lg-6">
                        <h2 class="font-weight-bold text-primary heading">
                            {{ hasActiveFilters ? 'Propriétés Filtrées' : 'Nos Propriétés Vedettes' }}
                        </h2>
                        <p class="text-muted">
                            {{ currentBiensCount }} propriété{{ currentBiensCount > 1 ? 's' : '' }}
                            {{ hasActiveFilters ? 'trouvée' : 'disponible' }}{{ currentBiensCount > 1 ? 's' : '' }}
                        </p>
                    </div>
                    <div class="col-lg-6 text-lg-end">
                        <Link
                            :href="route('biens.index')"
                            class="btn btn-primary text-white py-3 px-4"
                        >
                            Voir toutes les propriétés
                        </Link>
                    </div>
                </div>

                <!-- Filtres rapides et contrôles -->
                <div class="row mb-4" v-if="biens.length > 0">
                    <div class="col-md-8">
                        <div class="quick-filters d-flex gap-2 flex-wrap">
                            <button
                                @click="setQuickFilter('all')"
                                :class="['btn btn-sm filter-btn', currentQuickFilter === 'all' ? 'btn-primary' : 'btn-outline-primary']"
                            >
                                Tous ({{ stats.total }})
                            </button>
                            <button
                                @click="setQuickFilter('maison')"
                                :class="['btn btn-sm filter-btn', currentQuickFilter === 'maison' ? 'btn-success' : 'btn-outline-success']"
                            >
                                Maisons ({{ stats.maisons }})
                            </button>
                            <button
                                @click="setQuickFilter('appartement')"
                                :class="['btn btn-sm filter-btn', currentQuickFilter === 'appartement' ? 'btn-info' : 'btn-outline-info']"
                            >
                                Appartements ({{ stats.appartements }})
                            </button>
                            <button
                                @click="setQuickFilter('luxury')"
                                :class="['btn btn-sm filter-btn', currentQuickFilter === 'luxury' ? 'btn-warning' : 'btn-outline-warning']"
                            >
                                Luxe ({{ stats.luxury }})
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" v-model="sortBy" @change="applySorting">
                            <option value="default">Trier par</option>
                            <option value="price_asc">Prix croissant</option>
                            <option value="price_desc">Prix décroissant</option>
                            <option value="rooms_asc">Chambres croissant</option>
                            <option value="rooms_desc">Chambres décroissant</option>
                            <option value="recent">Plus récents</option>
                        </select>
                    </div>
                </div>

                <!-- Filtres avancés (repliables) -->
                <div class="advanced-filters mb-4" v-if="biens.length > 0">
                    <button
                        class="btn btn-outline-secondary btn-sm mb-3"
                        @click="showAdvancedFilters = !showAdvancedFilters"
                    >
                        <i :class="['fas', showAdvancedFilters ? 'fa-chevron-up' : 'fa-chevron-down', 'me-2']"></i>
                        Filtres avancés
                    </button>

                    <div v-show="showAdvancedFilters" class="advanced-filters-content">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Prix minimum</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    placeholder="Prix min"
                                    v-model="advancedFilters.minPrice"
                                    @input="applyAdvancedFilters"
                                />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Prix maximum</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    placeholder="Prix max"
                                    v-model="advancedFilters.maxPrice"
                                    @input="applyAdvancedFilters"
                                />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Chambres</label>
                                <select class="form-select" v-model="advancedFilters.rooms" @change="applyAdvancedFilters">
                                    <option value="">Toutes</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5+">5+</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Salles de bain</label>
                                <select class="form-select" v-model="advancedFilters.bathrooms" @change="applyAdvancedFilters">
                                    <option value="">Toutes</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4+">4+</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Actions</label>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary btn-sm" @click="applyAdvancedFilters">
                                        Appliquer
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" @click="clearAllFilters">
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message si aucun bien trouvé -->
                <div v-if="displayedBiens.length === 0" class="text-center py-5">
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-search me-2"></i>Aucune propriété trouvée</h5>
                        <p>
                            Aucune propriété ne correspond à vos critères de recherche.
                            <button class="btn btn-link p-0 alert-link" @click="clearAllFilters">
                                Effacer tous les filtres
                            </button> pour voir toutes les propriétés disponibles.
                        </p>
                    </div>
                </div>

                <!-- Grille des propriétés -->
                <div class="row" v-if="paginatedBiens.length > 0 && !useSlider">
                    <div
                        v-for="bien in paginatedBiens"
                        :key="bien.id"
                        class="col-lg-4 col-md-6 mb-4"
                    >
                        <div class="property-card h-100">
                            <Link :href="route('biens.show', bien.id)" class="property-image">
                                <img
                                    :src="bien.image ? `/storage/${bien.image}` : '/images/placeholder.jpg'"
                                    :alt="bien.title"
                                    class="img-fluid"
                                />
                                <div class="property-overlay">
                                    <span class="btn btn-primary">Voir détails</span>
                                </div>
                            </Link>

                            <div class="property-content p-3">
                                <div class="price mb-2">
                                    <span class="h5 text-primary">{{ formatPrice(bien.price) }} FCFA</span>
                                    <span v-if="getBienType(bien)" :class="['badge ms-2', getBienTypeBadge(bien)]">
                                        {{ getBienType(bien) }}
                                    </span>
                                </div>

                                <div class="location mb-3">
                                    <span class="d-block text-muted">{{ bien.address }}</span>
                                    <span class="d-block fw-bold">{{ bien.city }}</span>
                                </div>

                                <div class="specs d-flex justify-content-between mb-3">
                                    <span class="d-flex align-items-center">
                                        <i class="fas fa-bed me-2 text-primary"></i>
                                        <span>{{ bien.rooms || 0 }}</span>
                                    </span>
                                    <span class="d-flex align-items-center">
                                        <i class="fas fa-bath me-2 text-primary"></i>
                                        <span>{{ bien.bathrooms || 0 }}</span>
                                    </span>
                                    <span class="d-flex align-items-center" v-if="bien.floors">
                                        <i class="fas fa-building me-2 text-primary"></i>
                                        <span>{{ bien.floors }}</span>
                                    </span>
                                    <span class="d-flex align-items-center" v-if="bien.superficy">
                                        <i class="fas fa-ruler-combined me-2 text-primary"></i>
                                        <span>{{ bien.superficy }}m²</span>
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <Link
                                        :href="route('biens.show', bien.id)"
                                        class="btn btn-outline-primary btn-sm"
                                    >
                                        Voir détails
                                    </Link>
                                    <span class="text-muted small">
                                        {{ getCategoryName(bien) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vue slider (alternative) -->
                <div class="row" v-if="displayedBiens.length > 0 && useSlider">
                    <div class="col-12">
                        <div class="property-slider-wrap">
                            <div class="property-slider">
                                <div
                                    v-for="bien in displayedBiens"
                                    :key="bien.id"
                                    class="property-item"
                                >
                                    <Link :href="route('biens.show', bien.id)" class="img">
                                        <img
                                            :src="bien.image ? `/storage/${bien.image}` : '/images/placeholder.jpg'"
                                            :alt="bien.title"
                                            class="img-fluid"
                                        />
                                    </Link>

                                    <div class="property-content">
                                        <div class="price mb-2">
                                            <span>{{ formatPrice(bien.price) }} FCFA</span>
                                            <span v-if="getBienType(bien)" :class="['badge ms-2', getBienTypeBadge(bien)]">
                                                {{ getBienType(bien) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="d-block mb-2 text-black-50">{{ bien.address }}</span>
                                            <span class="city d-block mb-3">{{ bien.city }}</span>

                                            <div class="specs d-flex mb-4">
                                                <span class="d-block d-flex align-items-center me-3">
                                                    <span class="icon-bed me-2"></span>
                                                    <span class="caption">{{ bien.rooms || 0 }} Chambres</span>
                                                </span>
                                                <span class="d-block d-flex align-items-center">
                                                    <span class="icon-bath me-2"></span>
                                                    <span class="caption">{{ bien.bathrooms || 0 }} Salles de bain</span>
                                                </span>
                                            </div>

                                            <Link
                                                :href="route('biens.show', bien.id)"
                                                class="btn btn-primary py-2 px-3"
                                            >
                                                Voir les détails
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                id="property-nav"
                                class="controls"
                                v-if="displayedBiens.length > 3"
                            >
                                <span class="prev" data-controls="prev">Précédent</span>
                                <span class="next" data-controls="next">Suivant</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="row mt-4" v-if="totalPages > 1 && !useSlider">
                    <div class="col-12">
                        <nav class="d-flex justify-content-center">
                            <ul class="pagination">
                                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                    <button
                                        class="page-link"
                                        @click="changePage(currentPage - 1)"
                                        :disabled="currentPage === 1"
                                    >
                                        Précédent
                                    </button>
                                </li>

                                <li
                                    v-for="page in visiblePages"
                                    :key="page"
                                    class="page-item"
                                    :class="{ active: currentPage === page, disabled: page === '...' }"
                                >
                                    <button
                                        v-if="page !== '...'"
                                        class="page-link"
                                        @click="changePage(page)"
                                    >
                                        {{ page }}
                                    </button>
                                    <span v-else class="page-link">...</span>
                                </li>

                                <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                    <button
                                        class="page-link"
                                        @click="changePage(currentPage + 1)"
                                        :disabled="currentPage === totalPages"
                                    >
                                        Suivant
                                    </button>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- Bouton pour basculer entre les vues -->
                <div class="row mt-4" v-if="displayedBiens.length > 0">
                    <div class="col-12 text-center">
                        <div class="btn-group" role="group">
                            <button
                                type="button"
                                class="btn btn-outline-primary"
                                :class="{ active: useSlider }"
                                @click="toggleView(true)"
                            >
                                <i class="fas fa-th-list me-2"></i>Vue Carrousel
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-primary"
                                :class="{ active: !useSlider }"
                                @click="toggleView(false)"
                            >
                                <i class="fas fa-th-large me-2"></i>Vue Grille
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Services -->
        <section class="features-section py-5 bg-light">
            <div class="container">
                <div class="row justify-content-center text-center mb-5">
                    <div class="col-lg-8">
                        <h2 class="font-weight-bold heading text-primary mb-4">
                            Nos Services Immobiliers au Sénégal
                        </h2>
                        <p class="text-black-50">
                            Découvrez nos services sur mesure pour tous vos besoins immobiliers
                        </p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="service-card text-center p-4 h-100">
                            <div class="service-icon mb-3">
                                <i class="fas fa-home"></i>
                            </div>
                            <h3 class="service-title mb-3">Maisons</h3>
                            <p class="service-description mb-3">
                                Découvrez notre collection exclusive de maisons familiales et villas modernes.
                            </p>
                            <Link :href="route('biens.index')" class="service-btn">
                                Voir les maisons
                            </Link>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="service-card text-center p-4 h-100">
                            <div class="service-icon mb-3">
                                <i class="fas fa-building"></i>
                            </div>
                            <h3 class="service-title mb-3">Appartements</h3>
                            <p class="service-description mb-3">
                                Trouvez l'appartement idéal parmi notre sélection d'espaces modernes.
                            </p>
                            <Link :href="route('biens.index')" class="service-btn">
                                Voir les appartements
                            </Link>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="service-card text-center p-4 h-100">
                            <div class="service-icon mb-3">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h3 class="service-title mb-3">Contactez-nous</h3>
                            <p class="service-description mb-3">
                                Besoin d'aide ? Discutez directement avec notre équipe d'experts immobiliers.
                            </p>
                            <Link :href="route('conversations.index')" class="service-btn">
                                Discuter avec l'agence
                            </Link>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="service-card text-center p-4 h-100">
                            <div class="service-icon mb-3">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h3 class="service-title mb-3">Prendre Rendez-vous</h3>
                            <p class="service-description mb-3">
                                Planifiez facilement une visite personnalisée avec nos agents.
                            </p>
                            <button class="service-btn" @click="scheduleVisit">
                                Planifier une visite
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section statistiques -->
        <div class="section section-stats py-5">
            <div class="container">
                <div class="row justify-content-center text-center mb-5">
                    <div class="col-lg-8">
                        <h2 class="font-weight-bold heading text-primary mb-4">
                            Trouvons ensemble la propriété parfaite pour vous
                        </h2>
                        <p class="text-black-50">
                            Nous offrons des services immobiliers complets pour vous aider à trouver votre propriété idéale.
                        </p>
                    </div>
                </div>

                <div class="row justify-content-between align-items-center">
                    <div class="col-lg-6 mb-5 mb-lg-0">
                        <div class="stats-image">
                            <img
                                :src="biens[0]?.image ? `/storage/${biens[0].image}` : defaultImage1"
                                :alt="biens[0]?.title || 'Propriété exemple'"
                                class="img-fluid rounded shadow"
                            />
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="stats-features">
                            <div class="feature-item d-flex mb-4">
                                <div class="feature-icon me-3">
                                    <span class="icon-home2"></span>
                                </div>
                                <div class="feature-text">
                                    <h3 class="heading">{{ totalBiens }}+ Propriétés</h3>
                                    <p class="text-black-50">
                                        Large portefeuille de propriétés vérifiées dans toutes les régions du Sénégal.
                                    </p>
                                </div>
                            </div>

                            <div class="feature-item d-flex mb-4">
                                <div class="feature-icon me-3">
                                    <span class="icon-person"></span>
                                </div>
                                <div class="feature-text">
                                    <h3 class="heading">Agents Qualifiés</h3>
                                    <p class="text-black-50">
                                        Agents professionnels avec une expérience prouvée dans l'immobilier sénégalais.
                                    </p>
                                </div>
                            </div>

                            <div class="feature-item d-flex">
                                <div class="feature-icon me-3">
                                    <span class="icon-security"></span>
                                </div>
                                <div class="feature-text">
                                    <h3 class="heading">Propriétés Vérifiées</h3>
                                    <p class="text-black-50">
                                        Toutes nos propriétés sont vérifiées et conformes à la législation sénégalaise.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section équipe -->
        <section class="team-section py-5 bg-light">
            <div class="container">
                <div class="row justify-content-center text-center mb-5">
                    <div class="col-lg-8">
                        <h2 class="font-weight-bold heading text-primary mb-4">
                            Notre Équipe
                        </h2>
                        <p class="text-black-50">
                            Rencontrez notre équipe dédiée d'agents immobiliers professionnels prêts à vous accompagner.
                        </p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6 col-md-6 col-lg-4 mb-4">
                        <div class="team-member text-center">
                            <div class="member-image mb-3">
                                <img
                                    :src="teamMember1"
                                    alt="Mamadou Diop"
                                    class="img-fluid rounded-circle"
                                />
                            </div>
                            <div class="member-info">
                                <h3 class="member-name">Mamadou Diop</h3>
                                <p class="member-role">Agent Immobilier Senior</p>
                                <p class="member-description">
                                    Agent expérimenté spécialisé dans l'immobilier résidentiel avec plus de 8 ans d'expérience à Dakar.
                                </p>
                                <div class="social-links">
                                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                                    <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-6 col-lg-4 mb-4">
                        <div class="team-member text-center">
                            <div class="member-image mb-3">
                                <img
                                    :src="teamMember2"
                                    alt="Aïda Ndiaye"
                                    class="img-fluid rounded-circle"
                                />
                            </div>
                            <div class="member-info">
                                <h3 class="member-name">Aïda Ndiaye</h3>
                                <p class="member-role">Spécialiste Immobilier Commercial</p>
                                <p class="member-description">
                                    Experte en propriétés commerciales avec une expertise dans l'investissement immobilier.
                                </p>
                                <div class="social-links">
                                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                                    <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-6 col-lg-4 mb-4">
                        <div class="team-member text-center">
                            <div class="member-image mb-3">
                                <img
                                    :src="teamMember3"
                                    alt="Omar Sarr"
                                    class="img-fluid rounded-circle"
                                />
                            </div>
                            <div class="member-info">
                                <h3 class="member-name">Omar Sarr</h3>
                                <p class="member-role">Expert en Propriétés de Luxe</p>
                                <p class="member-description">
                                    Expert en propriétés haut de gamme aidant les clients à trouver des opportunités d'investissement premium.
                                </p>
                                <div class="social-links">
                                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                                    <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Widget de Chat IA - AJOUTEZ CECI -->
        <ChatWidget />
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
import { onMounted, computed, ref, watch, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { tns } from 'tiny-slider/src/tiny-slider'
import ChatWidget from '../Pages/ChatWidget.vue'


// Images par défaut
import defaultImage1 from '@/assets/images/hero_bg_3.jpg'
import defaultImage2 from '@/assets/images/hero_bg_2.jpg'
import defaultImage3 from '@/assets/images/hero_bg_1.jpg'
import teamMember1 from '@/assets/images/person_1-min.jpg'
import teamMember2 from '@/assets/images/person_2-min.jpg'
import teamMember3 from '@/assets/images/person_3-min.jpg'

// Props du contrôleur
const props = defineProps({
    biens: {
        type: Array,
        default: () => []
    },
    totalBiens: {
        type: Number,
        default: 0
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            maisons: 0,
            appartements: 0,
            luxury: 0,
            recent: 0
        })
    },
    categories: {
        type: Array,
        default: () => []
    },
    cities: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    }
})

// État du composant
const mainSearchQuery = ref('')
const searchSuggestions = ref([])
const currentQuickFilter = ref('all')
const sortBy = ref('default')
const useSlider = ref(false)
const showAdvancedFilters = ref(false)
const currentPage = ref(1)
const itemsPerPage = ref(9)

// Filtres avancés
const advancedFilters = ref({
    minPrice: props.filters.min_price || '',
    maxPrice: props.filters.max_price || '',
    rooms: props.filters.rooms || '',
    bathrooms: props.filters.bathrooms || '',
    city: props.filters.city || ''
})

// Sliders
let heroSlider = null
let propertySlider = null

// Computed properties
const heroSliderBiens = computed(() => {
    return props.biens.filter(bien => bien.image).slice(0, 3)
})

const hasActiveFilters = computed(() => {
    return currentQuickFilter.value !== 'all' ||
        advancedFilters.value.minPrice ||
        advancedFilters.value.maxPrice ||
        advancedFilters.value.rooms ||
        advancedFilters.value.bathrooms ||
        mainSearchQuery.value
})

const filteredBiens = computed(() => {
    let result = [...props.biens]

    // Filtre par recherche principale
    if (mainSearchQuery.value) {
        const query = mainSearchQuery.value.toLowerCase()
        result = result.filter(bien =>
            bien.city.toLowerCase().includes(query) ||
            bien.address.toLowerCase().includes(query) ||
            (bien.title && bien.title.toLowerCase().includes(query))
        )
    }

    // Filtre rapide
    if (currentQuickFilter.value !== 'all') {
        result = result.filter(bien => {
            switch (currentQuickFilter.value) {
                case 'maison':
                    return bien.category &&
                        bien.category.name.toLowerCase().includes('maison')
                case 'appartement':
                    return bien.category &&
                        bien.category.name.toLowerCase().includes('appartement')
                case 'luxury':
                    return bien.price >= 50000000
                default:
                    return true
            }
        })
    }

    // Filtres avancés
    if (advancedFilters.value.minPrice) {
        result = result.filter(bien => bien.price >= parseInt(advancedFilters.value.minPrice))
    }
    if (advancedFilters.value.maxPrice) {
        result = result.filter(bien => bien.price <= parseInt(advancedFilters.value.maxPrice))
    }
    if (advancedFilters.value.rooms) {
        if (advancedFilters.value.rooms === '5+') {
            result = result.filter(bien => bien.rooms >= 5)
        } else {
            result = result.filter(bien => bien.rooms === parseInt(advancedFilters.value.rooms))
        }
    }
    if (advancedFilters.value.bathrooms) {
        if (advancedFilters.value.bathrooms === '4+') {
            result = result.filter(bien => bien.bathrooms >= 4)
        } else {
            result = result.filter(bien => bien.bathrooms === parseInt(advancedFilters.value.bathrooms))
        }
    }

    return result
})

const displayedBiens = computed(() => {
    let result = [...filteredBiens.value]

    // Tri
    switch (sortBy.value) {
        case 'price_asc':
            result.sort((a, b) => a.price - b.price)
            break
        case 'price_desc':
            result.sort((a, b) => b.price - a.price)
            break
        case 'rooms_asc':
            result.sort((a, b) => a.rooms - b.rooms)
            break
        case 'rooms_desc':
            result.sort((a, b) => b.rooms - a.rooms)
            break
        case 'recent':
            result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
            break
    }

    return result
})

const currentBiensCount = computed(() => displayedBiens.value.length)

// Pagination
const totalPages = computed(() => Math.ceil(displayedBiens.value.length / itemsPerPage.value))

const paginatedBiens = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value
    const end = start + itemsPerPage.value
    return displayedBiens.value.slice(start, end)
})

const visiblePages = computed(() => {
    const pages = []
    const total = totalPages.value
    const current = currentPage.value

    if (total <= 7) {
        for (let i = 1; i <= total; i++) {
            pages.push(i)
        }
    } else {
        if (current <= 4) {
            for (let i = 1; i <= 5; i++) pages.push(i)
            pages.push('...')
            pages.push(total)
        } else if (current >= total - 3) {
            pages.push(1)
            pages.push('...')
            for (let i = total - 4; i <= total; i++) pages.push(i)
        } else {
            pages.push(1)
            pages.push('...')
            for (let i = current - 1; i <= current + 1; i++) pages.push(i)
            pages.push('...')
            pages.push(total)
        }
    }

    return pages
})

// Méthodes utilitaires
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

const getBienType = (bien) => {
    if (bien.price >= 50000000) return 'Luxe'
    if (bien.category && bien.category.name) {
        const categoryName = bien.category.name.toLowerCase()
        if (categoryName.includes('maison')) return 'Maison'
        if (categoryName.includes('appartement')) return 'Appartement'
    }
    return ''
}

const getBienTypeBadge = (bien) => {
    const type = getBienType(bien)
    switch (type) {
        case 'Luxe': return 'bg-warning text-dark'
        case 'Maison': return 'bg-success'
        case 'Appartement': return 'bg-info'
        default: return 'bg-secondary'
    }
}

const getCategoryName = (bien) => {
    return bien.category ? bien.category.name : 'Non catégorisé'
}

// Méthodes de recherche
const performMainSearch = () => {
    currentPage.value = 1
    // Optionnel : rediriger vers la page de recherche complète
    // router.get('/biens', { search: mainSearchQuery.value })
}

const onMainSearchInput = async () => {
    if (mainSearchQuery.value.length >= 2) {
        // Simulation d'autocomplétion (remplacer par un appel API si nécessaire)
        const query = mainSearchQuery.value.toLowerCase()
        searchSuggestions.value = props.cities
            .filter(city => city.toLowerCase().includes(query))
            .slice(0, 5)
            .map(city => ({
                id: city,
                label: city,
                city: city
            }))
    } else {
        searchSuggestions.value = []
    }
}

const selectSuggestion = (suggestion) => {
    mainSearchQuery.value = suggestion.city
    searchSuggestions.value = []
    performMainSearch()
}

// Méthodes de filtrage
const setQuickFilter = (filter) => {
    currentQuickFilter.value = filter
    currentPage.value = 1
}

const applyAdvancedFilters = () => {
    currentPage.value = 1
}

const applySorting = () => {
    currentPage.value = 1
}

const clearAllFilters = () => {
    mainSearchQuery.value = ''
    currentQuickFilter.value = 'all'
    sortBy.value = 'default'
    advancedFilters.value = {
        minPrice: '',
        maxPrice: '',
        rooms: '',
        bathrooms: '',
        city: ''
    }
    currentPage.value = 1
    searchSuggestions.value = []
}

// Méthodes de pagination
const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value && page !== '...') {
        currentPage.value = page
        window.scrollTo({ top: 300, behavior: 'smooth' })
    }
}

// Méthodes de vue
const toggleView = (slider) => {
    useSlider.value = slider
    if (slider) {
        nextTick(() => {
            initPropertySlider()
        })
    }
}

const chatWidgetRef = ref(null)

const openChatAssistant = () => {
    // Le widget s'ouvrira automatiquement au clic sur le bouton
    // Le composant ChatWidget gère son propre état d'ouverture
    console.log('Ouverture du chat assistant demandée')
    // Vous pouvez aussi faire défiler vers le bas pour montrer le widget
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' })
}

const scheduleVisit = () => {
    // Implémenter la planification de visite
    console.log('Planification de visite')
}

// Initialisation des sliders
const initHeroSlider = () => {
    if (heroSliderBiens.value.length > 0) {
        nextTick(() => {
            const heroSliderElement = document.querySelector('.hero-slider')
            if (heroSliderElement && !heroSlider) {
                heroSlider = tns({
                    container: '.hero-slider',
                    items: 1,
                    autoplay: true,
                    autoplayTimeout: 5000,
                    controls: false,
                    nav: false,
                    mode: 'carousel',
                    mouseDrag: true,
                    speed: 1000
                })
            }
        })
    }
}

const initPropertySlider = () => {
    if (displayedBiens.value.length > 0 && useSlider.value) {
        nextTick(() => {
            const propertySliderElement = document.querySelector('.property-slider')
            if (propertySliderElement && !propertySlider && displayedBiens.value.length > 3) {
                propertySlider = tns({
                    container: '.property-slider',
                    items: 3,
                    gutter: 30,
                    autoplay: false,
                    controlsContainer: '#property-nav',
                    responsive: {
                        0: { items: 1 },
                        700: { items: 2 },
                        1024: { items: 3 }
                    }
                })
            }
        })
    }
}

// Watchers
watch([currentQuickFilter, mainSearchQuery, advancedFilters], () => {
    currentPage.value = 1
}, { deep: true })

watch(useSlider, (newVal) => {
    if (newVal && displayedBiens.value.length > 3) {
        nextTick(() => {
            initPropertySlider()
        })
    }
})

// Lifecycle
onMounted(() => {
    // Initialiser les filtres depuis les props
    if (props.filters) {
        Object.keys(props.filters).forEach(key => {
            if (props.filters[key] && advancedFilters.value.hasOwnProperty(key)) {
                advancedFilters.value[key] = props.filters[key]
            }
        })
    }

    // Initialiser les sliders
    initHeroSlider()

    // Initialiser le property slider si nécessaire
    if (useSlider.value) {
        initPropertySlider()
    }

    // Gestionnaire pour fermer les suggestions quand on clique ailleurs
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-input-wrapper')) {
            searchSuggestions.value = []
        }
    })
})
</script>

<style scoped>
/* Styles Hero Section */
.hero-section {
    height: 70vh;
    min-height: 500px;
    position: relative;
    overflow: hidden;
}

.hero-slider {
    height: 100%;
}

.slide-item {
    height: 100%;
    position: relative;
}

.slide-item img {
    height: 100%;
    object-fit: cover;
}

.slide-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.4);
}

.hero-content {
    z-index: 10;
}

.hero-title {
    font-size: 3rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    margin-bottom: 2rem;
}

/* Styles de recherche */
.hero-search-form {
    max-width: 600px;
    margin: 0 auto;
}

.search-input-wrapper {
    position: relative;
    flex: 1;
}

.search-input {
    border: none;
    border-radius: 50px 0 0 50px;
    padding: 1rem 1.5rem;
    font-size: 1rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.search-input:focus {
    outline: none;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.search-btn {
    border-radius: 0 50px 50px 0;
    padding: 1rem 2rem;
    font-weight: 600;
    background: linear-gradient(135deg, #006064, #00838f);
    border: none;
    transition: all 0.3s ease;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 96, 100, 0.4);
}

.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    z-index: 1000;
}

.suggestion-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    transition: background-color 0.2s ease;
}

.suggestion-item:hover {
    background-color: #f8f9fa;
}

.suggestion-item:last-child {
    border-bottom: none;
}

/* Styles des filtres */
.quick-filters {
    margin-bottom: 1rem;
}

.filter-btn {
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.filter-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.advanced-filters-content {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    margin-top: 1rem;
}

/* Styles des cartes de propriétés */
.property-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
}

.property-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.property-image {
    position: relative;
    display: block;
    overflow: hidden;
    height: 250px;
}

.property-image img {
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.property-image:hover img {
    transform: scale(1.05);
}

.property-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.property-image:hover .property-overlay {
    opacity: 1;
}

.specs {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 8px;
}

.specs i {
    color: #006064;
    font-size: 1.1rem;
}

/* Styles des services */
.service-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
}

.service-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.service-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    background: linear-gradient(135deg, #006064, #00838f);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
}

.service-card:hover .service-icon {
    transform: scale(1.1);
}

.service-icon i {
    font-size: 2rem;
    color: white;
}

.service-title {
    color: #006064;
    font-size: 1.5rem;
    font-weight: 600;
}

.service-btn {
    background: linear-gradient(135deg, #006064, #00838f);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-block;
    border: none;
    cursor: pointer;
}

.service-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 96, 100, 0.3);
    color: white;
    text-decoration: none;
}

/* Styles statistiques */
.feature-item {
    align-items: flex-start;
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #006064, #00838f);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.feature-icon span {
    font-size: 1.5rem;
    color: white;
}

.feature-text h3 {
    color: #006064;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

/* Styles équipe */
.team-member {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
    height: 100%;
}

.team-member:hover {
    transform: translateY(-5px);
}

.member-image img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    margin: 0 auto;
}

.member-name {
    color: #006064;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.member-role {
    color: #00838f;
    font-weight: 500;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1rem;
}

.social-links {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 1rem;
}

.social-link {
    display: inline-block;
    width: 40px;
    height: 40px;
    background: #f8f9fa;
    border-radius: 50%;
    text-align: center;
    line-height: 40px;
    color: #666;
    transition: all 0.3s ease;
    text-decoration: none;
}

.social-link:hover {
    background: #006064;
    color: white;
    transform: translateY(-2px);
    text-decoration: none;
}

/* Pagination */
.pagination {
    gap: 0.5rem;
}

.page-link {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    color: #006064;
    transition: all 0.3s ease;
}

.page-link:hover {
    background-color: #006064;
    border-color: #006064;
    color: white;
    transform: translateY(-2px);
}

.page-item.active .page-link {
    background-color: #006064;
    border-color: #006064;
    color: white;
}

/* Vue toggle buttons */
.btn-group .btn {
    border-radius: 25px;
}

.btn-group .btn:first-child {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.btn-group .btn:last-child {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.btn-group .btn.active {
    background-color: #006064;
    border-color: #006064;
    color: white;
    box-shadow: 0 4px 12px rgba(0, 96, 100, 0.3);
}

/* Messages d'alerte */
.alert-warning {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.2);
}

/* Responsive */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }

    .hero-search-form {
        flex-direction: column;
        max-width: 90%;
    }

    .search-input {
        border-radius: 15px;
        margin-bottom: 0.5rem;
    }

    .search-btn {
        border-radius: 15px;
    }

    .quick-filters {
        justify-content: center;
    }

    .filter-btn {
        margin-bottom: 0.5rem;
    }

    .advanced-filters-content {
        padding: 1rem;
    }

    .btn-group {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .btn-group .btn {
        border-radius: 8px !important;
        margin-bottom: 0.5rem;
    }

    .property-card {
        margin-bottom: 2rem;
    }

    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }

    .member-image img {
        width: 100px;
        height: 100px;
    }
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

.property-card {
    animation: slideInUp 0.5s ease-out;
}

/* Badges */
.badge {
    transition: all 0.3s ease;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.badge:hover {
    transform: scale(1.05);
}

/* Loading states */
.loading {
    opacity: 0.7;
    pointer-events: none;
}

/* Focus states pour l'accessibilité */
.search-input:focus,
.form-control:focus,
.form-select:focus {
    border-color: #006064;
    box-shadow: 0 0 0 0.2rem rgba(0, 96, 100, 0.25);
}

.btn:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 96, 100, 0.25);
}

/* Styles pour les sections */
.section {
    padding: 4rem 0;
}

.features-section,
.team-section {
    background-color: #f8f9fa;
}

.section-stats {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

/* Amélioration des titres */
.heading {
    font-weight: 700;
    line-height: 1.2;
}

.text-primary {
    color: #006064 !important;
}

/* Effets de brillance sur les boutons */
.filter-btn::before,
.service-btn::before,
.search-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.filter-btn:hover::before,
.service-btn:hover::before,
.search-btn:hover::before {
    left: 100%;
}

/* Amélioration des images */
.stats-image img,
.member-image img {
    transition: transform 0.3s ease;
}

.stats-image:hover img,
.member-image:hover img {
    transform: scale(1.02);
}

/* Styles pour les sliders */
.property-slider-wrap {
    position: relative;
}

.controls {
    text-align: center;
    margin-top: 2rem;
}

.controls span {
    background: #006064;
    color: white;
    padding: 0.5rem 1rem;
    margin: 0 0.5rem;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.controls span:hover {
    background: #00838f;
    transform: translateY(-2px);
}

/* Micro-interactions */
.property-card .btn,
.service-card .service-btn,
.team-member .social-link {
    transform: scale(1);
    transition: transform 0.2s ease;
}

.property-card .btn:active,
.service-card .service-btn:active,
.team-member .social-link:active {
    transform: scale(0.95);
}

/* Indicateur de chargement */
.hero-slider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 50px;
    height: 50px;
    margin: -25px 0 0 -25px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    z-index: 1;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Masquer l'indicateur une fois le slider initialisé */
.hero-slider.tns-slider::before {
    display: none;
}

/* Positionnement du widget de chat en bas à gauche */
:deep(.fixed) {
    position: fixed;
}

:deep(.bottom-6) {
    bottom: 1.5rem;
}

:deep(.right-6) {
    right: auto !important;
    left: 1.5rem !important; /* Change de droite à gauche */
}

:deep(.z-50) {
    z-index: 1050;
}

/* Ajustement pour mobile */
@media (max-width: 768px) {
    :deep(.bottom-6) {
        bottom: 1rem;
    }

    :deep(.right-6) {
        left: 1rem !important;
    }
}
</style>
