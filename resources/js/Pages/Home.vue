<template>
    <div>
        <div class="site-mobile-menu site-navbar-target">
            <div class="site-mobile-menu-header">
                <div class="site-mobile-menu-close">
                    <span class="icofont-close js-menu-toggle"></span>
                </div>
            </div>
            <div class="site-mobile-menu-body"></div>
        </div>
    </div>

    <div class="hero-section position-relative">
        <!-- Slider avec <img> -->
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
            </div>
            <!-- Fallback slides si moins de 3 biens -->
            <div v-if="heroSliderBiens.length === 0" class="slide-item">
                <img :src="image1" alt="Slide par défaut 1" class="img-fluid w-100 h-100 object-cover" />
            </div>
            <div v-if="heroSliderBiens.length <= 1" class="slide-item">
                <img :src="image2" alt="Slide par défaut 2" class="img-fluid w-100 h-100 object-cover" />
            </div>
            <div v-if="heroSliderBiens.length <= 2" class="slide-item">
                <img :src="image3" alt="Slide par défaut 3" class="img-fluid w-100 h-100 object-cover" />
            </div>
        </div>

        <!-- Texte et bouton superposés -->
        <div class="hero-content position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
            <div class="text-center text-light">
                <h1 class="heading mb-4" data-aos="fade-up">Trouvez facilement la maison de vos rêves au Sénégal</h1>
                <form
                    @submit.prevent="performSearch"
                    class="narrow-w form-search d-flex align-items-stretch mb-3"
                    data-aos="fade-up"
                    data-aos-delay="200"
                >
                    <input
                        type="text"
                        class="form-control px-4"
                        placeholder="Votre quartier ou ville. ex: Dakar, Almadies"
                        v-model="searchQuery"
                    />
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </form>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="container">
            <div class="row mb-5 align-items-center">
                <div class="col-lg-6">
                    <h2 class="font-weight-bold text-primary heading">
                        {{ hasActiveFilters ? 'Propriétés Filtrées' : 'Nos Propriétés Vedettes' }}
                    </h2>
                    <p v-if="hasActiveFilters" class="text-muted">
                        {{ filteredBiens.length }} propriété{{ filteredBiens.length > 1 ? 's' : '' }} trouvée{{ filteredBiens.length > 1 ? 's' : '' }}
                    </p>
                </div>
                <div class="col-lg-6 text-lg-end">
                    <p>
                        <Link
                            :href="route('biens.index')"
                            class="btn btn-primary text-white py-3 px-4"
                        >Voir toutes les propriétés</Link>
                    </p>
                </div>
            </div>

            <!-- Contrôles de tri et filtres rapides -->
            <div class="row mb-4" v-if="biens.length > 0">
                <div class="col-md-8">
                    <div class="d-flex gap-2 flex-wrap">
                        <button
                            @click="quickFilter('all')"
                            :class="['btn btn-sm', currentQuickFilter === 'all' ? 'btn-primary' : 'btn-outline-primary']"
                        >
                            Tous ({{ biens.length }})
                        </button>
                        <button
                            @click="quickFilter('maison')"
                            :class="['btn btn-sm', currentQuickFilter === 'maison' ? 'btn-success' : 'btn-outline-success']"
                        >
                            Maisons ({{ getMaisonCount() }})
                        </button>
                        <button
                            @click="quickFilter('appartement')"
                            :class="['btn btn-sm', currentQuickFilter === 'appartement' ? 'btn-info' : 'btn-outline-info']"
                        >
                            Appartements ({{ getAppartementCount() }})
                        </button>
                        <button
                            @click="quickFilter('luxury')"
                            :class="['btn btn-sm', currentQuickFilter === 'luxury' ? 'btn-warning' : 'btn-outline-warning']"
                        >
                            Luxe ({{ getLuxuryCount() }})
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

            <!-- Message si aucun bien trouvé -->
            <div v-if="filteredBiens.length === 0 && hasActiveFilters" class="text-center py-5">
                <div class="alert alert-warning">
                    <h5>Aucune propriété ne correspond à vos critères</h5>
                    <p>Essayez de modifier vos filtres ou <a href="#" @click="clearAllFilters" class="alert-link">effacez tous les filtres</a> pour voir toutes les propriétés.</p>
                </div>
            </div>

            <!-- Grille des propriétés avec pagination -->
            <div class="row" v-if="displayedBiens.length > 0 && !useSlider">
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
                            </div>
                            <div class="location mb-3">
                                <span class="d-block text-muted">{{ bien.address }}</span>
                                <span class="d-block fw-bold">{{ bien.city }}</span>
                            </div>

                            <div class="specs d-flex justify-content-between mb-3">
                                <span class="d-flex align-items-center">
                                    <i class="fas fa-bed me-2 text-muted"></i>
                                    <span>{{ bien.rooms }}</span>
                                </span>
                                <span class="d-flex align-items-center">
                                    <i class="fas fa-bath me-2 text-muted"></i>
                                    <span>{{ bien.bathrooms }}</span>
                                </span>
                                <span class="d-flex align-items-center" v-if="bien.floors">
                                    <i class="fas fa-building me-2 text-muted"></i>
                                    <span>{{ bien.floors }}</span>
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <Link
                                    :href="route('biens.show', bien.id)"
                                    class="btn btn-outline-primary btn-sm"
                                >
                                    Voir détails
                                </Link>

                                <span
                                    :class="['badge', getBienTypeBadge(bien)]"
                                    v-if="getBienType(bien)"
                                >
                                    {{ getBienType(bien) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="col-12" v-if="totalPages > 1">
                    <nav class="d-flex justify-content-center mt-4">
                        <ul class="pagination">
                            <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                <button class="page-link" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">
                                    Précédent
                                </button>
                            </li>

                            <li
                                v-for="page in visiblePages"
                                :key="page"
                                class="page-item"
                                :class="{ active: currentPage === page }"
                            >
                                <button class="page-link" @click="changePage(page)">
                                    {{ page }}
                                </button>
                            </li>

                            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                <button class="page-link" @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages">
                                    Suivant
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <!-- Vue slider (original) -->
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
                                    </div>
                                    <div>
                                        <span class="d-block mb-2 text-black-50">{{ bien.address }}</span>
                                        <span class="city d-block mb-3">{{ bien.city }}</span>

                                        <div class="specs d-flex mb-4">
                                            <span class="d-block d-flex align-items-center me-3">
                                                <span class="icon-bed me-2"></span>
                                                <span class="caption">{{ bien.rooms }} Chambres</span>
                                            </span>
                                            <span class="d-block d-flex align-items-center">
                                                <span class="icon-bath me-2"></span>
                                                <span class="caption">{{ bien.bathrooms }} Salles de bain</span>
                                            </span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <Link
                                                :href="route('biens.show', bien.id)"
                                                class="btn btn-primary py-2 px-3"
                                            >Voir les détails</Link>

                                            <span
                                                :class="['badge', getBienTypeBadge(bien)]"
                                                v-if="getBienType(bien)"
                                            >
                                                {{ getBienType(bien) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            id="property-nav"
                            class="controls"
                            tabindex="0"
                            aria-label="Navigation du carrousel"
                            v-if="displayedBiens.length > 3"
                        >
                            <span
                                class="prev"
                                data-controls="prev"
                                aria-controls="property"
                                tabindex="-1"
                            >Précédent</span>
                            <span
                                class="next"
                                data-controls="next"
                                aria-controls="property"
                                tabindex="-1"
                            >Suivant</span>
                        </div>
                    </div>
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
                            @click="useSlider = true"
                        >
                            <i class="fas fa-th-list me-2"></i>Vue Carrousel
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-primary"
                            :class="{ active: !useSlider }"
                            @click="useSlider = false"
                        >
                            <i class="fas fa-th-large me-2"></i>Vue Grille
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Services Immobiliers Modernisée -->
    <section class="features-1 py-5">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <h2 class="font-weight-bold heading text-primary mb-4">
                        Nos Services Immobiliers au Sénégal
                    </h2>
                    <p class="text-black-50">
                        Découvrez nos services sur mesure pour tous vos besoins immobiliers, de la recherche à l'accompagnement personnalisé
                    </p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Maisons -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="service-card text-center p-4 h-100">
                        <div class="service-icon mb-3">
                            <i class="fas fa-home"></i>
                        </div>
                        <h3 class="service-title mb-3">Maisons</h3>
                        <p class="service-description mb-3">
                            Découvrez notre collection exclusive de maisons familiales, villas modernes et propriétés de charme dans tout le Sénégal.
                        </p>
                        <Link :href="route('biens.index')" class="service-btn">Voir les maisons</Link>
                    </div>
                </div>

                <!-- Appartements -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="service-card text-center p-4 h-100">
                        <div class="service-icon mb-3">
                            <i class="fas fa-building"></i>
                        </div>
                        <h3 class="service-title mb-3">Appartements</h3>
                        <p class="service-description mb-3">
                            Trouvez l'appartement idéal parmi notre sélection d'appartements modernes, studios et espaces de vie contemporains.
                        </p>
                        <Link :href="route('biens.index')" class="service-btn">Voir les appartements</Link>
                    </div>
                </div>

                <!-- Assistant IA -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="service-card text-center p-4 h-100">
                        <div class="service-icon mb-3">
                            <i class="fas fa-robot"></i>
                        </div>
                        <h3 class="service-title mb-3">Assistant IA</h3>
                        <p class="service-description mb-3">
                            Discutez avec notre assistant intelligent disponible 24h/24 pour obtenir des informations sur nos propriétés et services.
                        </p>
                        <Link href="#" class="service-btn">Discuter maintenant</Link>
                    </div>
                </div>

                <!-- Rendez-vous de visite -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="service-card text-center p-4 h-100">
                        <div class="service-icon mb-3">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3 class="service-title mb-3">Prendre Rendez-vous</h3>
                        <p class="service-description mb-3">
                            Planifiez facilement une visite personnalisée de nos propriétés avec nos agents expérimentés à votre convenance.
                        </p>
                        <Link href="#" class="service-btn">Planifier une visite</Link>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section statistiques -->
    <div class="section section-4 bg-light">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-5">
                    <h2 class="font-weight-bold heading text-primary mb-4">
                        Trouvons ensemble la propriété parfaite pour vous
                    </h2>
                    <p class="text-black-50">
                        Nous offrons des services immobiliers complets pour vous aider à trouver, acheter ou vendre votre propriété en toute confiance au Sénégal.
                    </p>
                </div>
            </div>
            <div class="row justify-content-between mb-5">
                <div class="col-lg-5 mb-5 mb-lg-0 order-lg-2">
                    <div class="">
                        <img
                            :src="biens[0]?.image ? `/storage/${biens[0].image}` : image6"
                            :alt="biens[0]?.title || 'Propriété exemple'"
                            class="img-fluid"
                        />
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex feature-h">
                        <span class="wrap-icon me-3">
                            <span class="icon-home2"></span>
                        </span>
                        <div class="feature-text">
                            <h3 class="heading">{{ totalProperties }}+ Propriétés</h3>
                            <p class="text-black-50">
                                Large portefeuille de propriétés vérifiées dans toutes les régions du Sénégal.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex feature-h">
                        <span class="wrap-icon me-3">
                            <span class="icon-person"></span>
                        </span>
                        <div class="feature-text">
                            <h3 class="heading">Agents Qualifiés</h3>
                            <p class="text-black-50">
                                Agents professionnels avec une expérience prouvée dans l'immobilier sénégalais.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex feature-h">
                        <span class="wrap-icon me-3">
                            <span class="icon-security"></span>
                        </span>
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

    <!-- Section équipe -->
    <div class="section section-5 bg-light">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-6 mb-5">
                    <h2 class="font-weight-bold heading text-primary mb-4">
                        Notre Équipe
                    </h2>
                    <p class="text-black-50">
                        Rencontrez notre équipe dédiée d'agents immobiliers professionnels prêts à vous accompagner dans tous vos projets immobiliers au Sénégal.
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-md-6 col-lg-4 mb-5 mb-lg-0">
                    <div class="h-100 person">
                        <img
                            :src="image13"
                            alt="Mamadou Diop"
                            class="img-fluid"
                        />

                        <div class="person-contents">
                            <h2 class="mb-0"><Link href="#">Mamadou Diop</Link></h2>
                            <span class="meta d-block mb-3">Agent Immobilier Senior</span>
                            <p>
                                Agent expérimenté spécialisé dans l'immobilier résidentiel avec plus de 8 ans d'expérience à Dakar.
                            </p>

                            <ul class="social list-unstyled list-inline dark-hover">
                                <li class="list-inline-item">
                                    <Link href="#"><span class="icon-twitter"></span></Link>
                                </li>
                                <li class="list-inline-item">
                                    <Link href="#"><span class="icon-facebook"></span></Link>
                                </li>
                                <li class="list-inline-item">
                                    <Link href="#"><span class="icon-linkedin"></span></Link>
                                </li>
                                <li class="list-inline-item">
                                    <Link href="#"><span class="icon-instagram"></span></Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-4 mb-5 mb-lg-0">
                    <div class="h-100 person">
                        <img
                            :src="image14"
                            alt="Aïda Ndiaye"
                            class="img-fluid"
                        />

                        <div class="person-contents">
                            <h2 class="mb-0"><Link href="#">Aïda Ndiaye</Link></h2>
                            <span class="meta d-block mb-3">Spécialiste Immobilier Commercial</span>
                            <p>
                                Experte en propriétés commerciales avec une expertise dans l'investissement et l'immobilier d'entreprise.
                            </p>

                            <ul class="social list-unstyled list-inline dark-hover">
                                <li class="list-inline-item">
                                    <Link href="#"><span class="icon-twitter"></span></Link>
                                </li>
                                <li class="list-inline-item">
                                    <Link href="#"><span class="icon-facebook"></span></Link>
                                </li>
                                <li class="list-inline-item">
                                    <Link href="#"><span class="icon-linkedin"></span></Link>
                                </li>
                                <li class="list-inline-item">
                                    <Link href="#"><span class="icon-instagram"></span></Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-4 mb-5 mb-lg-0">
                    <div class="h-100 person">
                        <img
                            :src="image15"
                            alt="Omar Sarr"
                            class="img-fluid"
                        />

                        <div class="person-contents">
                            <h2 class="mb-0"><Link href="#">Omar Sarr</Link></h2>
                            <span class="meta d-block mb-3">Expert en Propriétés de Luxe</span>
                            <p>
                                Expert en propriétés haut de gamme aidant les clients à trouver des maisons de prestige et des opportunités d'investissement premium.
                            </p>

                            <ul class="social list-unstyled list-inline dark-hover">
                                <li class="list-inline-item">
                                    <Link href="#"><span class="icon-twitter"></span></Link>
                                </li>
                                <li class="list-inline-item">
                                    <Link href="#"><span class="icon-facebook"></span></Link>
                                </li>
                                <li class="list-inline-item">
                                    <Link href="#"><span class="icon-linkedin"></span></Link>
                                </li>
                                <li class="list-inline-item">
                                    <Link href="#"><span class="icon-instagram"></span></Link>
                                </li>
                            </ul>
                        </div>
                    </div>
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
import { onMounted, computed, ref, inject, watch } from 'vue'
import { tns } from 'tiny-slider/src/tiny-slider'

// Props - données passées depuis le contrôleur
const props = defineProps({
    biens: {
        type: Array,
        default: () => []
    },
    totalBiens: {
        type: Number,
        default: 0
    }
})

// Images statiques
import image1 from '@/assets/images/hero_bg_3.jpg'
import image2 from '@/assets/images/hero_bg_2.jpg'
import image3 from '@/assets/images/hero_bg_1.jpg'
import image6 from '@/assets/images/img_2.jpg'
import image13 from '@/assets/images/person_1-min.jpg'
import image14 from '@/assets/images/person_2-min.jpg'
import image15 from '@/assets/images/person_3-min.jpg'

// État local
const searchQuery = ref('')
const currentQuickFilter = ref('all')
const sortBy = ref('default')
const useSlider = ref(true)
const currentPage = ref(1)
const itemsPerPage = ref(9)

// Injection des filtres depuis le Layout
const filters = inject('filters', ref({}))
const hasActiveFilters = inject('hasActiveFilters', ref(false))

// Computed properties
const totalProperties = computed(() => {
    return props.totalBiens || props.biens.length
})

// Obtenir les 3 premiers biens avec images pour le slider hero
const heroSliderBiens = computed(() => {
    return props.biens.filter(bien => bien.image).slice(0, 3)
})

// Appliquer les filtres du Layout
const filteredBiens = computed(() => {
    let result = [...props.biens]

    // Filtres du Layout
    if (hasActiveFilters.value) {
        result = result.filter(bien => {
            // Filtre par prix
            if (filters.value.minPrice && bien.price < parseInt(filters.value.minPrice)) {
                return false
            }
            if (filters.value.maxPrice && bien.price > parseInt(filters.value.maxPrice)) {
                return false
            }

            // Filtre par ville
            if (filters.value.city && !bien.city.toLowerCase().includes(filters.value.city.toLowerCase())) {
                return false
            }

            // Filtre par adresse
            if (filters.value.address && !bien.address.toLowerCase().includes(filters.value.address.toLowerCase())) {
                return false
            }

            // Filtre par nombre de chambres
            if (filters.value.rooms) {
                if (filters.value.rooms === '5') {
                    if (bien.rooms < 5) return false
                } else if (bien.rooms !== parseInt(filters.value.rooms)) {
                    return false
                }
            }

            // Filtre par nombre de salles de bain
            if (filters.value.bathrooms) {
                if (filters.value.bathrooms === '4') {
                    if (bien.bathrooms < 4) return false
                } else if (bien.bathrooms !== parseInt(filters.value.bathrooms)) {
                    return false
                }
            }

            // Filtre par nombre d'étages
            if (filters.value.floors) {
                if (filters.value.floors === '4') {
                    if (!bien.floors || bien.floors < 4) return false
                } else if (bien.floors !== parseInt(filters.value.floors)) {
                    return false
                }
            }

            return true
        })
    }

    // Filtre rapide
    if (currentQuickFilter.value !== 'all') {
        result = result.filter(bien => {
            switch (currentQuickFilter.value) {
                case 'maison':
                    return bien.type && bien.type.toLowerCase().includes('maison')
                case 'appartement':
                    return bien.type && bien.type.toLowerCase().includes('appartement')
                case 'luxury':
                    return bien.price >= 50000000 // 50M FCFA et plus pour le luxe
                default:
                    return true
            }
        })
    }

    // Filtre par recherche
    if (searchQuery.value) {
        result = result.filter(bien =>
            bien.city.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            bien.address.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (bien.title && bien.title.toLowerCase().includes(searchQuery.value.toLowerCase()))
        )
    }

    return result
})

// Appliquer le tri
const displayedBiens = computed(() => {
    let result = [...filteredBiens.value]

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
            result.sort((a, b) => b.id - a.id) // Supposant que l'ID plus élevé = plus récent
            break
        default:
            break
    }

    return result
})

// Pagination
const totalPages = computed(() => {
    return Math.ceil(displayedBiens.value.length / itemsPerPage.value)
})

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
    if (bien.type) {
        if (bien.type.toLowerCase().includes('maison')) return 'Maison'
        if (bien.type.toLowerCase().includes('appartement')) return 'Appartement'
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

const getMaisonCount = () => {
    return props.biens.filter(bien => bien.type && bien.type.toLowerCase().includes('maison')).length
}

const getAppartementCount = () => {
    return props.biens.filter(bien => bien.type && bien.type.toLowerCase().includes('appartement')).length
}

const getLuxuryCount = () => {
    return props.biens.filter(bien => bien.price >= 50000000).length
}

// Méthodes d'interaction
const performSearch = () => {
    currentPage.value = 1
    console.log('Recherche:', searchQuery.value)
}

const quickFilter = (filterType) => {
    currentQuickFilter.value = filterType
    currentPage.value = 1
}

const applySorting = () => {
    currentPage.value = 1
}

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value && page !== '...') {
        currentPage.value = page
        // Scroll vers le haut
        window.scrollTo({ top: 300, behavior: 'smooth' })
    }
}

const clearAllFilters = () => {
    searchQuery.value = ''
    currentQuickFilter.value = 'all'
    sortBy.value = 'default'
    currentPage.value = 1

    // Réinitialiser les filtres du Layout
    Object.keys(filters.value).forEach(key => {
        filters.value[key] = ''
    })
}

// Watcher pour réinitialiser la pagination quand les filtres changent
watch([hasActiveFilters, currentQuickFilter, searchQuery], () => {
    currentPage.value = 1
})

onMounted(() => {
    // Récupérer les paramètres de l'URL pour les filtres
    const urlParams = new URLSearchParams(window.location.search)

    // Appliquer les filtres depuis l'URL
    if (urlParams.get('minPrice')) filters.value.minPrice = urlParams.get('minPrice')
    if (urlParams.get('maxPrice')) filters.value.maxPrice = urlParams.get('maxPrice')
    if (urlParams.get('city')) filters.value.city = urlParams.get('city')
    if (urlParams.get('address')) filters.value.address = urlParams.get('address')
    if (urlParams.get('rooms')) filters.value.rooms = urlParams.get('rooms')
    if (urlParams.get('bathrooms')) filters.value.bathrooms = urlParams.get('bathrooms')
    if (urlParams.get('floors')) filters.value.floors = urlParams.get('floors')

    // Hero slider
    if (heroSliderBiens.value.length > 0) {
        tns({
            container: '.hero-slider',
            items: 1,
            autoplay: true,
            autoplayTimeout: 5000,
            autoplayButtonOutput: false,
            controls: false,
            nav: false,
            mode: 'carousel',
            mouseDrag: true
        });
    }

    // Property slider - seulement s'il y a des biens et qu'on utilise la vue slider
    if (props.biens.length > 0 && useSlider.value) {
        setTimeout(() => {
            if (document.querySelector('.property-slider') && displayedBiens.value.length > 3) {
                tns({
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
                });
            }
        }, 100)
    }
});
</script>

<style scoped>
/* Styles pour les cartes de propriétés */
.property-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.property-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.property-image {
    position: relative;
    display: block;
    overflow: hidden;
}

.property-image img {
    height: 250px;
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

.property-content {
    padding: 1.5rem;
}

.price {
    font-size: 1.25rem;
    font-weight: bold;
    color: #006064;
}

.location {
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
}

.specs {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.specs span {
    font-size: 0.9rem;
    color: #666;
}

/* Styles pour les services */
.service-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
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

.service-description {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.6;
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
}

.service-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 96, 100, 0.3);
    color: white;
    text-decoration: none;
}

/* Styles pour l'équipe */
.person {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.person:hover {
    transform: translateY(-5px);
}

.person img {
    width: 100%;
    height: 300px;
    object-fit: cover;
}

.person-contents {
    padding: 2rem;
}

.person-contents h2 a {
    color: #006064;
    text-decoration: none;
    font-size: 1.5rem;
    font-weight: 600;
}

.person-contents .meta {
    color: #00838f;
    font-weight: 500;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.social a {
    display: inline-block;
    width: 40px;
    height: 40px;
    background: #f8f9fa;
    border-radius: 50%;
    text-align: center;
    line-height: 40px;
    color: #666;
    transition: all 0.3s ease;
    margin-right: 0.5rem;
}

.social a:hover {
    background: #006064;
    color: white;
    transform: translateY(-2px);
}

/* Filtres rapides */
.btn-sm {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover,
.btn-outline-success:hover,
.btn-outline-info:hover,
.btn-outline-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Pagination */
.pagination {
    gap: 0.5rem;
}

.page-link {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    color: #006064;
    padding: 0.5rem 0.75rem;
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

/* Hero section améliorée */
.hero-section {
    height: 70vh;
    min-height: 500px;
}

.hero-slider {
    height: 100%;
}

.slide-item {
    height: 100%;
}

.slide-item img {
    height: 100%;
    object-fit: cover;
}

.hero-content {
    background: rgba(0, 0, 0, 0.4);
    z-index: 2;
}

.hero-content .heading {
    font-size: 3rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    margin-bottom: 2rem;
}

.form-search {
    max-width: 500px;
    margin: 0 auto;
    background: white;
    border-radius: 50px;
    padding: 0.5rem;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.form-search input {
    border: none;
    background: transparent;
    flex: 1;
}

.form-search input:focus {
    outline: none;
    box-shadow: none;
}

.form-search button {
    border-radius: 25px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    background: linear-gradient(135deg, #006064, #00838f);
    border: none;
    transition: all 0.3s ease;
}

.form-search button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 96, 100, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
    .hero-content .heading {
        font-size: 2rem;
    }

    .form-search {
        flex-direction: column;
        border-radius: 15px;
        padding: 1rem;
    }

    .form-search button {
        margin-top: 0.5rem;
        border-radius: 10px;
    }

    .property-card {
        margin-bottom: 2rem;
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

    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
}

/* Animation pour les cartes */
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

/* Animation pour les badges */
.badge {
    transition: all 0.3s ease;
}

.badge:hover {
    transform: scale(1.1);
}

/* Amélioration des contrôles de filtre rapide */
.btn-outline-primary,
.btn-outline-success,
.btn-outline-info,
.btn-outline-warning {
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.btn-outline-primary::before,
.btn-outline-success::before,
.btn-outline-info::before,
.btn-outline-warning::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn-outline-primary:hover::before,
.btn-outline-success:hover::before,
.btn-outline-info:hover::before,
.btn-outline-warning:hover::before {
    left: 100%;
}

/* Message d'aucun résultat */
.alert-warning {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.2);
}

/* Amélioration des spécifications */
.specs i {
    color: #006064 !important;
    font-size: 1.1rem;
}

.specs span {
    font-weight: 500;
}

/* Styles pour les statistiques */
.feature-h {
    margin-bottom: 2rem;
}

.wrap-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #006064, #00838f);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.wrap-icon span {
    font-size: 1.5rem;
    color: white;
}

.feature-text h3 {
    color: #006064;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}
</style>
