<template>
    <div class="home-page">
        <!-- Section Hero avec slider -->
        <section class="hero-section">
            <div class="hero-slider">
                <div
                    v-for="(bien, index) in heroSliderBiens"
                    :key="bien.id"
                    class="slide-item"
                >
                    <img
                        :src="bien.images[0]?.url || defaultImage1"
                        :alt="`${bien.title} - Slide ${index + 1}`"
                        class="slide-image"
                    />
                    <div class="slide-overlay"></div>
                </div>

                <div v-if="heroSliderBiens.length === 0" class="slide-item">
                    <img :src="defaultImage1" alt="Slide 1" class="slide-image" />
                    <div class="slide-overlay"></div>
                </div>
                <div v-if="heroSliderBiens.length <= 1" class="slide-item">
                    <img :src="defaultImage2" alt="Slide 2" class="slide-image" />
                    <div class="slide-overlay"></div>
                </div>
                <div v-if="heroSliderBiens.length <= 2" class="slide-item">
                    <img :src="defaultImage3" alt="Slide 3" class="slide-image" />
                    <div class="slide-overlay"></div>
                </div>
            </div>

            <div class="hero-content">
                <div class="hero-inner">
                    <div class="brand-header">
                        <div class="brand-logo">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                            </svg>
                        </div>
                        <h1 class="brand-name">Cauris Immo</h1>
                    </div>

                    <p class="hero-subtitle">
                        Votre partenaire de confiance pour tous vos projets immobiliers
                    </p>
                    <p class="hero-description">
                        Maisons • Appartements • Terrains • Studios • Construction • Gestion Locative
                    </p>

                    <form @submit.prevent="performMainSearch" class="search-form">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input
                                type="text"
                                class="search-input"
                                placeholder="Rechercher par quartier ou ville... (ex: Dakar, Almadies)"
                                v-model="mainSearchQuery"
                                @input="onMainSearchInput"
                            />
                            <div v-if="searchSuggestions.length > 0" class="suggestions">
                                <div
                                    v-for="suggestion in searchSuggestions"
                                    :key="suggestion.id"
                                    class="suggestion-item"
                                    @click="selectSuggestion(suggestion)"
                                >
                                    <i class="fas fa-map-marker-alt"></i>
                                    {{ suggestion.label }}
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="search-button">
                            Rechercher
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Section Statistiques -->
        <section class="stats-section">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">150+</div>
                            <div class="stat-label">Portefeuille biens</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">200+</div>
                            <div class="stat-label">Clients Satisfaits</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">8+</div>
                            <div class="stat-label">Années d'Expérience</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">100%</div>
                            <div class="stat-label">Biens Vérifiés</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section À Propos -->
        <section class="about-section">
            <div class="container">
                <div class="about-content">
                    <div class="about-text">
                        <h2 class="section-title">Cauris Immo, Votre Expert Immobilier au Sénégal</h2>
                        <div class="title-underline"></div>

                        <p class="lead-text">
                            Depuis plus de 8 ans, <strong>Cauris Immo</strong> s'impose comme l'agence immobilière de référence au Sénégal, accompagnant particuliers et professionnels dans la concrétisation de leurs projets immobiliers.
                        </p>

                        <div class="description-blocks">
                            <div class="desc-block">
                                <h3><i class="fas fa-check-circle"></i> Notre Expertise</h3>
                                <p>
                                    Spécialisés dans la <strong>vente, location et gestion de patrimoine immobilier</strong>, nous proposons une sélection rigoureuse de biens de qualité : <strong>maisons individuelles, appartements modernes, terrains viabilisés, studios fonctionnels</strong>, adaptés à tous les budgets et besoins.
                                </p>
                            </div>

                            <div class="desc-block">
                                <h3><i class="fas fa-check-circle"></i> Services Complets</h3>
                                <p>
                                    Au-delà de la simple transaction, nous offrons un <strong>accompagnement personnalisé</strong> incluant la <strong>construction clé en main, le suivi de projets, la gestion locative complète</strong> et des conseils juridiques pour sécuriser vos investissements.
                                </p>
                            </div>

                            <div class="desc-block">
                                <h3><i class="fas fa-check-circle"></i> Notre Engagement</h3>
                                <p>
                                    Chaque bien est <strong>vérifié, certifié et conforme aux normes</strong> légales sénégalaises. Notre équipe d'experts vous garantit transparence, professionnalisme et réactivité à chaque étape de votre projet immobilier.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="values-grid">
                        <div class="value-card">
                            <div class="value-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3 class="value-title">Fiabilité</h3>
                            <p class="value-description">
                                Tous nos biens sont vérifiés et conformes aux normes légales
                            </p>
                        </div>
                        <div class="value-card">
                            <div class="value-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h3 class="value-title">Excellence</h3>
                            <p class="value-description">
                                Service personnalisé et accompagnement professionnel
                            </p>
                        </div>
                        <div class="value-card">
                            <div class="value-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="value-title">Expertise</h3>
                            <p class="value-description">
                                Une équipe de professionnels expérimentés à votre service
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Propriétés -->
        <section class="properties-section">
            <div class="container">
                <div class="section-header-center">
                    <h2 class="section-title">
                        {{ hasActiveFilters ? 'Propriétés Filtrées' : 'Nos Propriétés Vedettes' }}
                    </h2>
                    <div class="title-underline"></div>
                    <p class="section-subtitle">
                        {{ currentBiensCount }} propriété{{ currentBiensCount > 1 ? 's' : '' }}
                        {{ hasActiveFilters ? 'trouvée' : 'disponible' }}{{ currentBiensCount > 1 ? 's' : '' }}
                    </p>
                </div>

                <div class="filters-container" v-if="biens.length > 0">
                    <!-- Filtres par catégorie -->
                    <div class="filters-section">
                        <h3 class="filters-section-title">Par Type de Bien</h3>
                        <div class="quick-filters">
                            <button
                                @click="setQuickFilter('all')"
                                :class="['filter-chip', { active: currentQuickFilter === 'all' }]"
                            >
                                <i class="fas fa-th"></i>
                                <span class="filter-label">Tous</span>
                                <span class="filter-count">{{ stats.total }}</span>
                            </button>
                            <button
                                @click="setQuickFilter('maison')"
                                :class="['filter-chip', { active: currentQuickFilter === 'maison' }]"
                            >
                                <i class="fas fa-home"></i>
                                <span class="filter-label">Maisons</span>
                                <span class="filter-count">{{ stats.maisons }}</span>
                            </button>
                            <button
                                @click="setQuickFilter('appartement')"
                                :class="['filter-chip', { active: currentQuickFilter === 'appartement' }]"
                            >
                                <i class="fas fa-building"></i>
                                <span class="filter-label">Appartements</span>
                                <span class="filter-count">{{ stats.appartements }}</span>
                            </button>
                            <button
                                @click="setQuickFilter('terrain')"
                                :class="['filter-chip', { active: currentQuickFilter === 'terrain' }]"
                            >
                                <i class="fas fa-map"></i>
                                <span class="filter-label">Terrains</span>
                                <span class="filter-count">{{ stats.terrains }}</span>
                            </button>
                            <button
                                @click="setQuickFilter('studio')"
                                :class="['filter-chip', { active: currentQuickFilter === 'studio' }]"
                            >
                                <i class="fas fa-door-open"></i>
                                <span class="filter-label">Studios</span>
                                <span class="filter-count">{{ stats.studios }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Filtres par type de transaction -->
                    <div class="filters-section">
                        <h3 class="filters-section-title">Par Type de Transaction</h3>
                        <div class="quick-filters">
                            <button
                                @click="setQuickFilter('vente')"
                                :class="['filter-chip filter-vente', { active: currentQuickFilter === 'vente' }]"
                            >
                                <i class="fas fa-tag"></i>
                                <span class="filter-label">À Vendre</span>
                                <span class="filter-count">{{ stats.vente }}</span>
                            </button>
                            <button
                                @click="setQuickFilter('location')"
                                :class="['filter-chip filter-location', { active: currentQuickFilter === 'location' }]"
                            >
                                <i class="fas fa-key"></i>
                                <span class="filter-label">À Louer</span>
                                <span class="filter-count">{{ stats.location }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Contrôles de vue et tri -->
                    <div class="controls-row">
                        <div class="view-controls">
                            <div class="view-toggle">
                                <button
                                    @click="viewMode = 'grid'"
                                    :class="['view-btn', { active: viewMode === 'grid' }]"
                                    title="Vue grille"
                                >
                                    <i class="fas fa-th"></i>
                                </button>
                                <button
                                    @click="viewMode = 'carousel'"
                                    :class="['view-btn', { active: viewMode === 'carousel' }]"
                                    title="Vue carrousel"
                                >
                                    <i class="fas fa-sliders-h"></i>
                                </button>
                            </div>
                        </div>
                        <select class="sort-select" v-model="sortBy" @change="applySorting">
                            <option value="default">Trier par défaut</option>
                            <option value="price_asc">Prix croissant</option>
                            <option value="price_desc">Prix décroissant</option>
                            <option value="recent">Plus récents</option>
                        </select>
                    </div>
                </div>

                <div class="properties-grid" v-if="viewMode === 'grid' && paginatedBiens.length > 0">
                    <div
                        v-for="bien in paginatedBiens"
                        :key="bien.id"
                        class="property-card"
                    >
                        <Link :href="route('biens.show', bien.id)" class="property-link">
                            <div class="property-image">
                                <img
                                    :src="bien.images[0]?.url || defaultImage1"
                                    :alt="bien.title"
                                />
                                <div class="property-badge" v-if="getBienType(bien)">
                                    {{ getBienType(bien) }}
                                </div>
                                <div class="property-mandat-badge" v-if="getMandatType(bien)">
                                    {{ getMandatType(bien) }}
                                </div>
                            </div>

                            <div class="property-body">
                                <div class="property-price">
                                    {{ formatPrice(bien.price) }} FCFA
                                </div>

                                <div class="property-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>{{ bien.city }}, {{ bien.address }}</span>
                                </div>

                                <div class="property-specs">
                                    <div class="spec-item" v-if="bien.rooms">
                                        <i class="fas fa-bed"></i>
                                        <span>{{ bien.rooms }}</span>
                                    </div>
                                    <div class="spec-item" v-if="bien.bathrooms">
                                        <i class="fas fa-bath"></i>
                                        <span>{{ bien.bathrooms }}</span>
                                    </div>
                                    <div class="spec-item" v-if="bien.superficy">
                                        <i class="fas fa-ruler-combined"></i>
                                        <span>{{ bien.superficy }}m²</span>
                                    </div>
                                </div>

                                <div class="property-footer">
                                    <span class="property-category">{{ getCategoryName(bien) }}</span>
                                    <span class="view-details">
                                        Voir détails
                                        <i class="fas fa-arrow-right"></i>
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <div class="properties-carousel-container" v-if="viewMode === 'carousel' && displayedBiens.length > 0">
                    <div class="carousel-wrapper">
                        <button
                            @click="previousSlide"
                            class="carousel-nav-btn prev"
                            :disabled="carouselIndex === 0"
                        >
                            <i class="fas fa-chevron-left"></i>
                        </button>

                        <div class="carousel-track-container">
                            <div class="carousel-track" :style="{ transform: `translateX(-${carouselIndex * 100}%)` }">
                                <div
                                    v-for="bien in displayedBiens"
                                    :key="bien.id"
                                    class="carousel-slide"
                                >
                                    <div class="property-card">
                                        <Link :href="route('biens.show', bien.id)" class="property-link">
                                            <div class="property-image">
                                                <img
                                                    :src="bien.images[0]?.url || defaultImage1"
                                                    :alt="bien.title"
                                                />
                                                <div class="property-badge" v-if="getBienType(bien)">
                                                    {{ getBienType(bien) }}
                                                </div>
                                                <div class="property-mandat-badge" v-if="getMandatType(bien)">
                                                    {{ getMandatType(bien) }}
                                                </div>
                                            </div>

                                            <div class="property-body">
                                                <div class="property-price">
                                                    {{ formatPrice(bien.price) }} FCFA
                                                </div>

                                                <div class="property-location">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span>{{ bien.city }}, {{ bien.address }}</span>
                                                </div>

                                                <div class="property-specs">
                                                    <div class="spec-item" v-if="bien.rooms">
                                                        <i class="fas fa-bed"></i>
                                                        <span>{{ bien.rooms }}</span>
                                                    </div>
                                                    <div class="spec-item" v-if="bien.bathrooms">
                                                        <i class="fas fa-bath"></i>
                                                        <span>{{ bien.bathrooms }}</span>
                                                    </div>
                                                    <div class="spec-item" v-if="bien.superficy">
                                                        <i class="fas fa-ruler-combined"></i>
                                                        <span>{{ bien.superficy }}m²</span>
                                                    </div>
                                                </div>

                                                <div class="property-footer">
                                                    <span class="property-category">{{ getCategoryName(bien) }}</span>
                                                    <span class="view-details">
                                                        Voir détails
                                                        <i class="fas fa-arrow-right"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button
                            @click="nextSlide"
                            class="carousel-nav-btn next"
                            :disabled="carouselIndex >= displayedBiens.length - 1"
                        >
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>

                    <div class="carousel-indicators">
                        <button
                            v-for="(bien, index) in displayedBiens"
                            :key="index"
                            @click="goToSlide(index)"
                            :class="['indicator-dot', { active: carouselIndex === index }]"
                        ></button>
                    </div>

                    <div class="carousel-counter">
                        {{ carouselIndex + 1 }} / {{ displayedBiens.length }}
                    </div>
                </div>

                <div v-if="displayedBiens.length === 0" class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>Aucune propriété trouvée</h3>
                    <p>Aucune propriété ne correspond à vos critères</p>
                    <button @click="clearAllFilters" class="btn-reset">
                        Effacer les filtres
                    </button>
                </div>

                <nav class="pagination" v-if="viewMode === 'grid' && totalPages > 1">
                    <button
                        @click="changePage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="page-btn"
                    >
                        <i class="fas fa-chevron-left"></i>
                        Précédent
                    </button>

                    <div class="page-numbers">
                        <button
                            v-for="page in visiblePages"
                            :key="page"
                            @click="page !== '...' && changePage(page)"
                            :class="['page-number', { active: currentPage === page, dots: page === '...' }]"
                            :disabled="page === '...'"
                        >
                            {{ page }}
                        </button>
                    </div>

                    <button
                        @click="changePage(currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        class="page-btn"
                    >
                        Suivant
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </nav>
            </div>
        </section>

        <!-- Section Services -->
        <section class="services-section">
            <div class="container">
                <div class="section-header-center">
                    <h2 class="section-title">Nos Services Immobiliers</h2>
                    <div class="title-underline"></div>
                    <p class="section-subtitle">
                        Des solutions complètes pour répondre à tous vos besoins immobiliers
                    </p>
                </div>

                <div class="services-carousel">
                    <div class="services-grid">
                        <div class="service-card">
                            <div class="service-icon-wrapper">
                                <div class="service-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                            </div>
                            <h3 class="service-title">Vente de Propriétés</h3>
                            <p class="service-description">
                                Large sélection de <strong>maisons, appartements, terrains et studios</strong> dans toutes les régions du Sénégal. Chaque bien est vérifié pour garantir qualité et sécurité juridique.
                            </p>
                            <div class="service-features">
                                <span><i class="fas fa-check"></i> Biens certifiés</span>
                                <span><i class="fas fa-check"></i> Prix transparents</span>
                                <span><i class="fas fa-check"></i> Visites organisées</span>
                            </div>
                        </div>

                        <div class="service-card">
                            <div class="service-icon-wrapper">
                                <div class="service-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                            </div>
                            <h3 class="service-title">Construction & Projets</h3>
                            <p class="service-description">
                                Accompagnement de vos <strong>projets de construction</strong> de A à Z. Conception architecturale, suivi de chantier et livraison clés en main avec notre équipe d'experts.
                            </p>
                            <div class="service-features">
                                <span><i class="fas fa-check"></i> Design personnalisé</span>
                                <span><i class="fas fa-check"></i> Suivi rigoureux</span>
                                <span><i class="fas fa-check"></i> Délais respectés</span>
                            </div>
                        </div>

                        <div class="service-card">
                            <div class="service-icon-wrapper">
                                <div class="service-icon">
                                    <i class="fas fa-file-signature"></i>
                                </div>
                            </div>
                            <h3 class="service-title">Gestion Locative</h3>
                            <p class="service-description">
                                <strong>Gestion complète de vos biens</strong> : recherche de locataires, gestion administrative, maintenance, recouvrement des loyers. Nous gérons tout pour vous en toute sérénité.
                            </p>
                            <div class="service-features">
                                <span><i class="fas fa-check"></i> Locataires vérifiés</span>
                                <span><i class="fas fa-check"></i> Maintenance assurée</span>
                                <span><i class="fas fa-check"></i> Paiements garantis</span>
                            </div>
                        </div>

                        <div class="service-card">
                            <div class="service-icon-wrapper">
                                <div class="service-icon">
                                    <i class="fas fa-handshake"></i>
                                </div>
                            </div>
                            <h3 class="service-title">Accompagnement Personnalisé</h3>
                            <p class="service-description">
                                <strong>Conseiller dédié</strong> qui analyse vos besoins, budget et attentes pour vous proposer les solutions les plus adaptées à votre situation personnelle.
                            </p>
                            <div class="service-features">
                                <span><i class="fas fa-check"></i> Conseils experts</span>
                                <span><i class="fas fa-check"></i> Écoute personnalisée</span>
                                <span><i class="fas fa-check"></i> Suivi continu</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cta-actions">
                    <button
                        @click="handleActionClick('deposer-fiche')"
                        class="cta-button primary"
                    >
                        <i class="fas fa-file-alt"></i>
                        Déposer une fiche de demande
                    </button>

                    <button
                        @click="handleActionClick('contacter-agence')"
                        class="cta-button primary"
                    >
                        <i class="fas fa-phone"></i>
                        Contacter l'agence
                    </button>
                </div>
            </div>
        </section>

        <!-- Section Équipe -->
        <section class="team-section">
            <div class="container">
                <div class="section-header-center">
                    <h2 class="section-title">Notre Équipe Professionnelle</h2>
                    <div class="title-underline"></div>
                    <p class="section-subtitle">
                        Des experts dévoués pour la réussite de vos projets immobiliers
                    </p>
                </div>

                <div class="team-structure">
                    <div class="direction-row">
                        <div class="team-member featured director">
                            <div class="member-icon">
                                <i class="fas fa-user-tie"></i>
                                <div class="member-badge">
                                    <i class="fas fa-crown"></i>
                                </div>
                            </div>
                            <h3 class="member-name">Yancouba Goudiaby</h3>
                            <div class="member-role">Directeur Général</div>
                            <p class="member-desc">
                                Visionnaire et stratège, supervise l'ensemble des opérations et le développement de l'entreprise
                            </p>
                        </div>

                        <div class="team-member featured assistant">
                            <div class="member-icon">
                                <i class="fas fa-user-circle"></i>
                                <div class="member-badge assistant-badge">
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                            <h3 class="member-name">Leyla Goudiaby</h3>
                            <div class="member-role assistant-role">Assistante DG</div>
                            <p class="member-desc">
                                Coordonne les activités et assure la liaison entre les services
                            </p>
                        </div>
                    </div>

                    <div class="team-grid">
                        <div class="team-member">
                            <div class="member-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3 class="member-name">Habib Seck</h3>
                            <div class="member-role commercial-role">Agent Commercial</div>
                            <p class="member-desc">
                                Expert en développement commercial et relations clients
                            </p>
                        </div>

                        <div class="team-member">
                            <div class="member-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3 class="member-name">Elias Sané</h3>
                            <div class="member-role recovery-role">Agent de Recouvrement</div>
                            <p class="member-desc">
                                Spécialiste en gestion des créances et suivi des paiements
                            </p>
                        </div>

                        <div class="team-member">
                            <div class="member-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3 class="member-name">Mr Badji</h3>
                            <div class="member-role recovery-role">Agent de Recouvrement</div>
                            <p class="member-desc">
                                Expert en recouvrement avec approche professionnelle
                            </p>
                        </div>

                        <div class="team-member">
                            <div class="member-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3 class="member-name">Ousmane Fall</h3>
                            <div class="member-role it-role">Responsable IT</div>
                            <p class="member-desc">
                                Gestion de l'infrastructure informatique de l'entreprise
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Contact -->
        <section class="contact-section">
            <div class="container">
                <div class="contact-wrapper">
                    <div class="contact-info">
                        <h2 class="contact-title">Prêt à Concrétiser Votre Projet ?</h2>
                        <p class="contact-text">
                            Contactez-nous dès aujourd'hui pour une consultation gratuite et personnalisée
                        </p>

                        <div class="contact-items">
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <h4>Téléphone</h4>
                                    <p>+221 78 291 53 18</p>
                                </div>
                            </div>

                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <h4>Email</h4>
                                    <p>caurisimmobiliere@gmail.com</p>
                                </div>
                            </div>

                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <h4>Adresse</h4>
                                    <p>Keur Massar, Parcelles Assainies, Jaxaay, Unité 14, Dakar, Sénégal</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="contact-action">
                        <button
                            @click="handleActionClick('contacter-agence')"
                            class="cta-button primary"
                        >
                            <i class="fas fa-phone"></i>
                            Contacter l'agence
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <ClientDossierModal
            :show="showModal"
            @close="showModal = false"
            @success="handleSuccess"
        />

        <Transition name="toast">
            <div v-if="showSuccessToast" class="success-toast">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h4>Demande envoyée avec succès !</h4>
                    <p>Nous vous contacterons très prochainement.</p>
                </div>
                <button @click="showSuccessToast = false">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { onMounted, computed, ref, watch, nextTick } from 'vue'
import { tns } from 'tiny-slider/src/tiny-slider'
import ClientDossierModal from '../Pages/ClientDossiers/Create.vue'

import defaultImage1 from '@/assets/images/hero_bg_3.jpg'
import defaultImage2 from '@/assets/images/hero_bg_2.jpg'
import defaultImage3 from '@/assets/images/hero_bg_1.jpg'

const page = usePage()

const props = defineProps({
    biens: { type: Array, default: () => [] },
    totalBiens: { type: Number, default: 0 },
    stats: { type: Object, default: () => ({
            total: 0,
            maisons: 0,
            appartements: 0,
            terrains: 0,
            studios: 0,
            vente: 0,
            location: 0
        }) },
    categories: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) }
})

const isGuest = computed(() => {
    const user = page.props.auth?.user

    if (user?.is_guest === true || user?.is_guest === 1) {
        return true
    }

    if (user?.roles && Array.isArray(user.roles)) {
        const hasVisiteurRole = user.roles.includes('visiteur')
        const hasOnlyVisiteurRole = user.roles.length === 1 && hasVisiteurRole

        if (hasOnlyVisiteurRole) {
            return true
        }
    }

    if (user?.email && user.email.includes('guest_') && user.email.includes('@temporary.local')) {
        return true
    }

    if (!user) {
        return true
    }

    return false
})

const handleActionClick = (action) => {
    if (isGuest.value) {
        sessionStorage.setItem('intended_action', action)
        sessionStorage.setItem('redirect_after_login', window.location.pathname)

        router.visit(route('login'), {
            data: {
                message: 'Veuillez vous connecter pour accéder à ce service'
            }
        })
        return
    }

    if (action === 'deposer-fiche') {
        showModal.value = true
    } else if (action === 'contacter-agence') {
        router.visit(route('conversations.index'))
    }
}

// State variables
const showModal = ref(false)
const showSuccessToast = ref(false)
const mainSearchQuery = ref('')
const searchSuggestions = ref([])
const currentQuickFilter = ref('all')
const sortBy = ref('default')
const currentPage = ref(1)
const itemsPerPage = ref(9)
const viewMode = ref('grid')
const carouselIndex = ref(0)

let heroSlider = null

// Computed properties
const heroSliderBiens = computed(() => props.biens.filter(bien => bien.images).slice(0, 3))
const hasActiveFilters = computed(() => currentQuickFilter.value !== 'all' || mainSearchQuery.value)

const filteredBiens = computed(() => {
    let result = [...props.biens]

    if (mainSearchQuery.value) {
        const query = mainSearchQuery.value.toLowerCase()
        result = result.filter(bien =>
            bien.city.toLowerCase().includes(query) ||
            bien.address.toLowerCase().includes(query) ||
            (bien.title && bien.title.toLowerCase().includes(query))
        )
    }

    if (currentQuickFilter.value !== 'all') {
        result = result.filter(bien => {
            const categoryName = bien.category?.name?.toLowerCase() || ''
            const mandatType = bien.mandat?.type_mandat?.toLowerCase() || ''

            switch (currentQuickFilter.value) {
                case 'maison':
                    return categoryName.includes('maison')
                case 'appartement':
                    return categoryName.includes('appartement')
                case 'terrain':
                    return categoryName.includes('terrain')
                case 'studio':
                    return categoryName.includes('studio')
                case 'vente':
                    return mandatType === 'vente'
                case 'location':
                    return mandatType === 'gestion_locative'
                default:
                    return true
            }
        })
    }

    return result
})

const displayedBiens = computed(() => {
    let result = [...filteredBiens.value]

    switch (sortBy.value) {
        case 'price_asc':
            result.sort((a, b) => a.price - b.price)
            break
        case 'price_desc':
            result.sort((a, b) => b.price - a.price)
            break
        case 'recent':
            result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
            break
    }

    return result
})

const currentBiensCount = computed(() => displayedBiens.value.length)
const totalPages = computed(() => Math.ceil(displayedBiens.value.length / itemsPerPage.value))
const paginatedBiens = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value
    return displayedBiens.value.slice(start, start + itemsPerPage.value)
})

const visiblePages = computed(() => {
    const pages = []
    const total = totalPages.value
    const current = currentPage.value

    if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i)
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

// Methods
const formatPrice = (price) => new Intl.NumberFormat('fr-FR').format(price)

const getBienType = (bien) => {
    if (bien.category && bien.category.name) {
        return bien.category.name
    }
    return ''
}

const getMandatType = (bien) => {
    if (bien.mandat && bien.mandat.type_mandat) {
        return bien.mandat.type_mandat === 'vente' ? 'À Vendre' : 'À Louer'
    }
    return ''
}

const getCategoryName = (bien) => bien.category ? bien.category.name : 'Non catégorisé'

const performMainSearch = () => currentPage.value = 1

const onMainSearchInput = async () => {
    if (mainSearchQuery.value.length >= 2) {
        const query = mainSearchQuery.value.toLowerCase()
        searchSuggestions.value = props.cities
            .filter(city => city.toLowerCase().includes(query))
            .slice(0, 5)
            .map(city => ({ id: city, label: city, city }))
    } else {
        searchSuggestions.value = []
    }
}

const selectSuggestion = (suggestion) => {
    mainSearchQuery.value = suggestion.city
    searchSuggestions.value = []
    performMainSearch()
}

const setQuickFilter = (filter) => {
    currentQuickFilter.value = filter
    currentPage.value = 1
}

const applySorting = () => currentPage.value = 1

const clearAllFilters = () => {
    mainSearchQuery.value = ''
    currentQuickFilter.value = 'all'
    sortBy.value = 'default'
    currentPage.value = 1
    searchSuggestions.value = []
}

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        window.scrollTo({ top: 300, behavior: 'smooth' })
    }
}

const previousSlide = () => {
    if (carouselIndex.value > 0) {
        carouselIndex.value--
    }
}

const nextSlide = () => {
    if (carouselIndex.value < displayedBiens.value.length - 1) {
        carouselIndex.value++
    }
}

const goToSlide = (index) => {
    carouselIndex.value = index
}

const handleSuccess = () => {
    showSuccessToast.value = true
    setTimeout(() => showSuccessToast.value = false, 5000)
}

const initHeroSlider = () => {
    if (heroSliderBiens.value.length > 0) {
        nextTick(() => {
            const element = document.querySelector('.hero-slider')
            if (element && !heroSlider) {
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

watch(viewMode, () => {
    carouselIndex.value = 0
})

onMounted(() => {
    initHeroSlider()

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-wrapper')) {
            searchSuggestions.value = []
        }
    })

    const intendedAction = sessionStorage.getItem('intended_action')
    if (intendedAction && !isGuest.value) {
        sessionStorage.removeItem('intended_action')
        sessionStorage.removeItem('redirect_after_login')

        if (intendedAction === 'deposer-fiche') {
            showModal.value = true
        } else if (intendedAction === 'contacter-agence') {
            router.visit(route('conversations.index'))
        }
    }
})
</script>

<style scoped>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.home-page {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: #1f2937;
    background: #ffffff;
}

.container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 2rem;
}

/* Hero Section */
.hero-section {
    height: 100vh;
    min-height: 650px;
    position: relative;
    overflow: hidden;
}

.hero-slider {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.slide-item {
    position: relative;
    width: 100%;
    height: 100%;
}

.slide-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.slide-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0, 96, 100, 0.9) 0%, rgba(0, 131, 143, 0.85) 100%);
}

.hero-content {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.hero-inner {
    text-align: center;
    max-width: 900px;
    padding: 2rem;
}

/* Brand Header */
.brand-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 2.5rem;
    animation: fadeInDown 1s ease;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.brand-logo {
    width: 90px;
    height: 90px;
    background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
    animation: pulse 3s infinite;
}

.brand-logo svg {
    width: 50px;
    height: 50px;
    color: #006064;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3); }
    50% { transform: scale(1.05); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4); }
}

.brand-name {
    font-size: 4.5rem;
    font-weight: 900;
    color: white;
    text-shadow: 3px 5px 10px rgba(0, 0, 0, 0.4);
    letter-spacing: -2px;
    line-height: 1;
}

.hero-subtitle {
    font-size: 1.6rem;
    color: rgba(255, 255, 255, 0.98);
    font-weight: 500;
    margin-bottom: 1.25rem;
    line-height: 1.5;
    animation: fadeIn 1.2s ease;
}

.hero-description {
    font-size: 1.15rem;
    color: rgba(255, 255, 255, 0.95);
    font-weight: 600;
    margin-bottom: 3.5rem;
    letter-spacing: 1.5px;
    animation: fadeIn 1.4s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Search Form */
.search-form {
    display: flex;
    max-width: 750px;
    margin: 0 auto;
    gap: 0;
    animation: fadeInUp 1.6s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.search-wrapper {
    position: relative;
    flex: 1;
}

.search-icon {
    position: absolute;
    left: 1.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 1.25rem;
    z-index: 1;
}

.search-input {
    width: 100%;
    padding: 1.5rem 1.75rem 1.5rem 4rem;
    border: none;
    border-radius: 60px 0 0 60px;
    font-size: 1.05rem;
    outline: none;
    background: white;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25);
    transition: all 0.3s ease;
    font-family: inherit;
}

.search-input:focus {
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
}

.search-input::placeholder {
    color: #9ca3af;
}

.search-button {
    padding: 1.5rem 3.5rem;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    border: none;
    border-radius: 0 60px 60px 0;
    font-size: 1.15rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 15px 50px rgba(251, 191, 36, 0.35);
    white-space: nowrap;
    font-family: inherit;
}

.search-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 60px rgba(251, 191, 36, 0.45);
}

.suggestions {
    position: absolute;
    top: calc(100% + 12px);
    left: 0;
    right: 0;
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    z-index: 100;
}

.suggestion-item {
    padding: 1.15rem 1.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 1.15rem;
    border-bottom: 1px solid #f3f4f6;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover {
    background: #f9fafb;
}

.suggestion-item i {
    color: #006064;
    font-size: 1.1rem;
}

/* Stats Section */
.stats-section {
    padding: 6rem 0;
    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
    margin-top: -100px;
    position: relative;
    z-index: 5;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 3rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(15px);
    padding: 3rem 2.5rem;
    border-radius: 25px;
    display: flex;
    align-items: center;
    gap: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.25);
    transition: all 0.4s ease;
}

.stat-card:hover {
    transform: translateY(-12px);
    background: rgba(255, 255, 255, 0.18);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
}

.stat-icon {
    width: 75px;
    height: 75px;
    min-width: 75px;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon i {
    font-size: 2.25rem;
    color: white;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 3.25rem;
    font-weight: 900;
    color: white;
    margin-bottom: 0.35rem;
    line-height: 1;
}

.stat-label {
    font-size: 1.05rem;
    color: rgba(255, 255, 255, 0.95);
    font-weight: 500;
    line-height: 1.3;
}

/* Section Headers */
.section-header-center {
    text-align: center;
    margin-bottom: 5rem;
}

.section-title {
    font-size: 3rem;
    font-weight: 900;
    color: #1f2937;
    margin-bottom: 1.25rem;
    line-height: 1.2;
}

.title-underline {
    width: 90px;
    height: 5px;
    background: linear-gradient(90deg, #006064, #fbbf24);
    margin: 0 auto 2rem;
    border-radius: 3px;
}

.section-subtitle {
    font-size: 1.15rem;
    color: #6b7280;
    margin-top: 0.75rem;
    line-height: 1.6;
}

/* About Section */
.about-section {
    padding: 8rem 0;
    background: linear-gradient(to bottom, #ffffff 0%, #f9fafb 100%);
}

.about-content {
    display: grid;
    gap: 5rem;
}

.about-text {
    max-width: 100%;
}

.about-text .section-title {
    text-align: left;
    font-size: 2.75rem;
}

.about-text .title-underline {
    margin: 0 0 2.5rem 0;
}

.lead-text {
    font-size: 1.35rem;
    line-height: 1.8;
    color: #374151;
    margin-bottom: 3rem;
    font-weight: 500;
}

.description-blocks {
    display: grid;
    gap: 2.5rem;
}

.desc-block {
    background: white;
    padding: 2.5rem;
    border-radius: 20px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
    border-left: 5px solid #006064;
    transition: all 0.3s ease;
}

.desc-block:hover {
    transform: translateX(8px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.1);
}

.desc-block h3 {
    font-size: 1.4rem;
    color: #006064;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 700;
}

.desc-block h3 i {
    font-size: 1.3rem;
}

.desc-block p {
    font-size: 1.05rem;
    line-height: 1.8;
    color: #4b5563;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 3rem;
}

.value-card {
    background: white;
    padding: 3rem 2.5rem;
    border-radius: 25px;
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
    transition: all 0.4s ease;
    border: 2px solid transparent;
    text-align: center;
}

.value-card:hover {
    transform: translateY(-12px);
    border-color: #006064;
    box-shadow: 0 25px 60px rgba(0, 96, 100, 0.18);
}

.value-icon {
    width: 90px;
    height: 90px;
    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
    border-radius: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.75rem;
    transition: transform 0.3s ease;
}

.value-card:hover .value-icon {
    transform: scale(1.1) rotate(5deg);
}

.value-icon i {
    font-size: 2.75rem;
    color: white;
}

.value-title {
    font-size: 1.6rem;
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 1rem;
}

.value-description {
    color: #6b7280;
    line-height: 1.7;
    font-size: 1.05rem;
}

/* Properties Section */
.properties-section {
    padding: 8rem 0;
    background: white;
}

.filters-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4rem;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.quick-filters {
    display: flex;
    gap: 1.25rem;
    flex-wrap: wrap;
}

.filter-chip {
    padding: 1rem 2rem;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 60px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #6b7280;
    font-family: inherit;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.filter-chip i {
    font-size: 1.1rem;
}

.filter-chip:hover {
    border-color: #006064;
    color: #006064;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.filter-chip.active {
    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
    border-color: #006064;
    color: white;
    box-shadow: 0 6px 20px rgba(0, 96, 100, 0.35);
}

.sort-select {
    padding: 1rem 2rem;
    border: 2px solid #e5e7eb;
    border-radius: 60px;
    font-weight: 700;
    cursor: pointer;
    outline: none;
    background: white;
    transition: all 0.3s ease;
    font-family: inherit;
    font-size: 1rem;
    color: #6b7280;
}

.sort-select:focus {
    border-color: #006064;
    box-shadow: 0 4px 15px rgba(0, 96, 100, 0.15);
}

/* Properties Grid */
.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 3rem;
}

/* Carousel Styles - NOUVEAU */
.properties-carousel-container {
    position: relative;
    margin-bottom: 3rem;
}

.carousel-wrapper {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.carousel-track-container {
    flex: 1;
    overflow: hidden;
    border-radius: 25px;
}

.carousel-track {
    display: flex;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.carousel-slide {
    min-width: 100%;
    padding: 0 1rem;
}

.carousel-nav-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(0, 96, 100, 0.3);
}

.carousel-nav-btn:hover:not(:disabled) {
    transform: scale(1.1);
    box-shadow: 0 12px 35px rgba(0, 96, 100, 0.4);
}

.carousel-nav-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.carousel-indicators {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
    margin-top: 2.5rem;
}

.indicator-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #d1d5db;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 0;
}

.indicator-dot:hover {
    background: #9ca3af;
    transform: scale(1.2);
}

.indicator-dot.active {
    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
    width: 32px;
    border-radius: 6px;
}

.carousel-counter {
    text-align: center;
    margin-top: 1.5rem;
    font-size: 1.15rem;
    font-weight: 700;
    color: #6b7280;
}

/* Styles pour les boutons de vue - NOUVEAU */
.view-controls {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.view-toggle {
    display: flex;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 60px;
    overflow: hidden;
}

.view-btn {
    padding: 1rem 1.75rem;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #6b7280;
    font-size: 1.15rem;
}

.view-btn:hover {
    background: #f9fafb;
    color: #006064;
}

.view-btn.active {
    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
    color: white;
}

.property-card {
    background: white;
    border-radius: 25px;
    overflow: hidden;
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.09);
    transition: all 0.4s ease;
    border: 2px solid transparent;
}

.property-card:hover {
    transform: translateY(-15px);
    box-shadow: 0 30px 70px rgba(0, 0, 0, 0.18);
    border-color: #006064;
}

.property-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.property-image {
    position: relative;
    height: 270px;
    overflow: hidden;
    background: #f3f4f6;
}

.property-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.property-card:hover .property-image img {
    transform: scale(1.15);
}

.property-badge {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    padding: 0.65rem 1.35rem;
    border-radius: 60px;
    font-weight: 800;
    font-size: 0.9rem;
    box-shadow: 0 6px 20px rgba(251, 191, 36, 0.45);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.property-body {
    padding: 2rem;
}

.property-price {
    font-size: 2rem;
    font-weight: 900;
    color: #006064;
    margin-bottom: 1.25rem;
}

.property-location {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    color: #6b7280;
    margin-bottom: 1.5rem;
    font-size: 1.05rem;
}

.property-location i {
    color: #006064;
    font-size: 1.15rem;
}

.property-specs {
    display: flex;
    gap: 2rem;
    margin-bottom: 1.75rem;
    padding: 1.25rem;
    background: #f9fafb;
    border-radius: 15px;
}

.spec-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    color: #4b5563;
    font-weight: 600;
    font-size: 1.05rem;
}

.spec-item i {
    color: #006064;
    font-size: 1.25rem;
}

.property-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1.5rem;
    border-top: 2px solid #e5e7eb;
}

.property-category {
    font-size: 0.95rem;
    color: #6b7280;
    font-weight: 600;
}

.view-details {
    font-weight: 800;
    color: #006064;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
}

.view-details i {
    font-size: 0.9rem;
    transition: transform 0.3s ease;
}

.property-card:hover .view-details {
    color: #00838f;
}

.property-card:hover .view-details i {
    transform: translateX(5px);
}

/* No Results */
.no-results {
    text-align: center;
    padding: 5rem 2rem;
}

.no-results i {
    font-size: 5rem;
    color: #d1d5db;
    margin-bottom: 2rem;
}

.no-results h3 {
    font-size: 2rem;
    color: #1f2937;
    margin-bottom: 1.25rem;
    font-weight: 700;
}

.no-results p {
    color: #6b7280;
    margin-bottom: 2.5rem;
    font-size: 1.1rem;
}

.btn-reset {
    padding: 1.15rem 2.5rem;
    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
    color: white;
    border: none;
    border-radius: 60px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: inherit;
    font-size: 1.05rem;
}

.btn-reset:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0, 96, 100, 0.35);
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1.25rem;
    margin-top: 5rem;
}

.page-btn {
    padding: 1rem 2rem;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 60px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #6b7280;
    font-family: inherit;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.page-btn i {
    font-size: 0.9rem;
}

.page-btn:hover:not(:disabled) {
    border-color: #006064;
    color: #006064;
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.page-numbers {
    display: flex;
    gap: 0.65rem;
}

.page-number {
    width: 50px;
    height: 50px;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 50%;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #6b7280;
    font-family: inherit;
    font-size: 1.05rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.page-number:hover:not(.dots):not(.active) {
    border-color: #006064;
    color: #006064;
    transform: scale(1.1);
}

.page-number.active {
    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
    border-color: #006064;
    color: white;
    box-shadow: 0 6px 20px rgba(0, 96, 100, 0.35);
}

.page-number.dots {
    border: none;
    cursor: default;
}

/* Services Section */
.services-section {
    padding: 8rem 0;
    background: linear-gradient(to bottom, #f9fafb 0%, #ffffff 100%);
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-bottom: 5rem;
}

.service-card {
    background: white;
    padding: 3rem 2.5rem;
    border-radius: 25px;
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
    transition: all 0.4s ease;
    border-top: 5px solid transparent;
    position: relative;
    overflow: hidden;
}

.service-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(0, 96, 100, 0.05), transparent);
    transition: left 0.6s ease;
}

.service-card:hover::before {
    left: 100%;
}

.service-card:nth-child(1) {
    border-top-color: #fbbf24;
}

.service-card:nth-child(2) {
    border-top-color: #006064;
}

.service-card:nth-child(3) {
    border-top-color: #10b981;
}

.service-card:nth-child(4) {
    border-top-color: #8b5cf6;
}

.service-card:hover {
    transform: translateY(-12px);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
}

.service-icon-wrapper {
    margin-bottom: 2rem;
}

.service-icon {
    width: 90px;
    height: 90px;
    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
    border-radius: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.4s ease;
}

.service-card:hover .service-icon {
    transform: scale(1.15) rotate(8deg);
}

.service-icon i {
    font-size: 2.75rem;
    color: white;
}

.service-title {
    font-size: 1.65rem;
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 1.25rem;
}

.service-description {
    color: #4b5563;
    line-height: 1.9;
    font-size: 1.05rem;
    margin-bottom: 2rem;
}

.service-features {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.service-features span {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    color: #6b7280;
    font-size: 0.95rem;
    font-weight: 600;
}

.service-features i {
    color: #10b981;
    font-size: 1rem;
}

.cta-actions {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.cta-button {
    padding: 1.5rem 3rem;
    border-radius: 60px;
    font-weight: 800;
    font-size: 1.15rem;
    cursor: pointer;
    transition: all 0.4s ease;
    font-family: inherit;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    border: none;
}

.cta-button i {
    font-size: 1.2rem;
}

.cta-button.primary {
    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
    color: white;
    box-shadow: 0 12px 35px rgba(0, 96, 100, 0.3);
}

.cta-button.primary:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 50px rgba(0, 96, 100, 0.4);
}

.cta-button.secondary {
    background: white;
    color: #006064;
    border: 3px solid #006064;
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.08);
}

.cta-button.secondary:hover {
    transform: translateY(-4px);
    background: #006064;
    color: white;
    box-shadow: 0 18px 50px rgba(0, 96, 100, 0.3);
}

/* Team Section */
.team-section {
    padding: 8rem 0;
    background: white;
}

.team-structure {
    display: grid;
    gap: 3rem;
}

.direction-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 3rem;
    margin-bottom: 2rem;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
}

.team-member {
    background: white;
    padding: 3rem 2.5rem;
    border-radius: 25px;
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
    transition: all 0.4s ease;
    text-align: center;
    border: 2px solid transparent;
    position: relative;
}

.team-member:hover {
    transform: translateY(-12px);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
    border-color: #e5e7eb;
}

.team-member.featured {
    border-color: #fbbf24;
    background: linear-gradient(to bottom, #fffbeb 0%, white 25%);
    box-shadow: 0 15px 45px rgba(251, 191, 36, 0.15);
}

.team-member.featured:hover {
    border-color: #fbbf24;
    box-shadow: 0 30px 70px rgba(251, 191, 36, 0.25);
}

.member-icon {
    position: relative;
    width: 130px;
    height: 130px;
    margin: 0 auto 2rem;
}

.member-icon i {
    width: 130px;
    height: 130px;
    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    color: white;
    box-shadow: 0 12px 35px rgba(0, 96, 100, 0.3);
    transition: transform 0.4s ease;
}

.team-member:hover .member-icon i {
    transform: scale(1.08);
}

.member-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    animation: pulse 3s infinite;
}

.member-badge.assistant-badge {
    background: linear-gradient(135deg, #c084fc 0%, #a855f7 100%);
}

.member-badge i {
    font-size: 1.25rem;
    width: auto;
    height: auto;
    background: none;
    box-shadow: none;
}

.member-name {
    font-size: 1.65rem;
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 1rem;
}

.member-role {
    display: inline-block;
    padding: 0.65rem 1.35rem;
    border-radius: 60px;
    font-size: 0.8rem;
    font-weight: 800;
    margin-bottom: 1.25rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
}

.assistant-role {
    background: linear-gradient(135deg, #e9d5ff 0%, #ddd6fe 100%);
    color: #7c3aed;
}

.commercial-role {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
}

.recovery-role {
    background: linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%);
    color: #115e59;
}

.it-role {
    background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
    color: #9a3412;
}

.member-desc {
    color: #6b7280;
    font-size: 1rem;
    line-height: 1.7;
}

/* Contact Section */
.contact-section {
    padding: 8rem 0;
    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
    color: white;
    position: relative;
    overflow: hidden;
}

.contact-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
}

.contact-section::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -5%;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 50%;
}

.contact-wrapper {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 5rem;
    align-items: center;
    position: relative;
    z-index: 1;
}

.contact-info {
    max-width: 600px;
}

.contact-title {
    font-size: 3.25rem;
    font-weight: 900;
    margin-bottom: 1.5rem;
    line-height: 1.2;
}

.contact-text {
    font-size: 1.25rem;
    margin-bottom: 3rem;
    color: rgba(255, 255, 255, 0.95);
    line-height: 1.6;
}

.contact-items {
    display: grid;
    gap: 2rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    padding: 1.75rem;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.contact-item:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateX(8px);
}

.contact-icon {
    width: 60px;
    height: 60px;
    min-width: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.contact-icon i {
    font-size: 1.75rem;
}

.contact-item h4 {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 0.35rem;
}

.contact-item p {
    font-size: 1.05rem;
    color: rgba(255, 255, 255, 0.9);
}

.contact-action {
    display: flex;
    align-items: center;
}

.contact-btn {
    padding: 1.75rem 3.5rem;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    border: none;
    border-radius: 60px;
    font-size: 1.2rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.4s ease;
    box-shadow: 0 15px 45px rgba(251, 191, 36, 0.35);
    font-family: inherit;
    display: flex;
    align-items: center;
    gap: 0.85rem;
    white-space: nowrap;
}

.contact-btn i {
    font-size: 1.3rem;
}

.contact-btn:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 60px rgba(251, 191, 36, 0.45);
}

/* Toast Notification */
.success-toast {
    position: fixed;
    top: 2rem;
    right: 2rem;
    background: white;
    padding: 1.75rem 2rem;
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25);
    display: flex;
    align-items: center;
    gap: 1.25rem;
    z-index: 1000;
    max-width: 450px;
    border-left: 5px solid #10b981;
}

.success-toast i:first-child {
    font-size: 2.25rem;
    color: #10b981;
}

.success-toast h4 {
    font-size: 1.1rem;
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 0.35rem;
}

.success-toast p {
    font-size: 0.95rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}

.success-toast button {
    background: none;
    border: none;
    cursor: pointer;
    color: #9ca3af;
    font-size: 1.35rem;
    padding: 0;
    margin-left: auto;
    transition: color 0.2s ease;
}

.success-toast button:hover {
    color: #6b7280;
}

.toast-enter-active,
.toast-leave-active {
    transition: all 0.4s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100px);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100px);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .contact-wrapper {
        grid-template-columns: 1fr;
        gap: 3rem;
    }

    .contact-action {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .container {
        padding: 0 1.5rem;
    }

    .brand-header {
        flex-direction: column;
        gap: 1.5rem;
    }

    .brand-name {
        font-size: 3rem;
    }

    .brand-logo {
        width: 70px;
        height: 70px;
    }

    .brand-logo svg {
        width: 40px;
        height: 40px;
    }

    .hero-subtitle {
        font-size: 1.25rem;
    }

    .hero-description {
        font-size: 1rem;
    }

    .search-form {
        flex-direction: column;
        gap: 1rem;
    }

    .search-input {
        border-radius: 60px;
        padding: 1.35rem 1.5rem 1.35rem 3.75rem;
    }

    .search-button {
        border-radius: 60px;
        width: 100%;
    }

    .section-title {
        font-size: 2.25rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .stat-card {
        flex-direction: column;
        text-align: center;
    }

    .properties-grid {
        grid-template-columns: 1fr;
    }

    .filters-container {
        flex-direction: column;
        align-items: stretch;
    }

    .quick-filters {
        flex-direction: column;
    }

    .filter-chip {
        width: 100%;
        justify-content: center;
    }

    .sort-select {
        width: 100%;
    }

    .services-grid {
        grid-template-columns: 1fr;
    }

    .cta-actions {
        flex-direction: column;
        gap: 1.5rem;
    }

    .cta-button {
        width: 100%;
        justify-content: center;
    }

    .direction-row {
        grid-template-columns: 1fr;
    }

    .team-grid {
        grid-template-columns: 1fr;
    }

    .contact-title {
        font-size: 2.25rem;
    }

    .contact-wrapper {
        gap: 2.5rem;
    }

    .contact-btn {
        width: 100%;
        justify-content: center;
        padding: 1.5rem 2.5rem;
    }

    .success-toast {
        right: 1rem;
        left: 1rem;
        max-width: none;
    }

    .pagination {
        flex-wrap: wrap;
    }

    .page-numbers {
        order: 3;
        width: 100%;
        justify-content: center;
        margin-top: 1rem;
    }
}

@media (max-width: 480px) {
    .brand-name {
        font-size: 2.25rem;
    }

    .hero-subtitle {
        font-size: 1.1rem;
    }

    .section-title {
        font-size: 1.85rem;
    }

    .property-specs {
        flex-wrap: wrap;
        gap: 1rem;
    }

    .contact-title {
        font-size: 1.85rem;
    }
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.home-page {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: #1f2937;
    background: #ffffff;
}

.container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 2rem;
}

/* Badge pour le type de mandat */
.property-mandat-badge {
    position: absolute;
    top: 1.25rem;
    left: 1.25rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 0.65rem 1.35rem;
    border-radius: 60px;
    font-weight: 800;
    font-size: 0.9rem;
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

</style>
