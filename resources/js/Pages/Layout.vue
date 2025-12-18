<template>
    <div class="Layout d-flex flex-column min-vh-100">
        <FlashMessages />

        <!-- Skip to content -->
        <a href="#main-content" class="skip-to-content">Aller au contenu principal</a>

        <!-- Navbar -->
        <nav class="site-nav fixed-top w-100">
            <div class="menu-bg-wrap">
                <div class="container">
                    <div class="site-navigation py-3">
                        <Link :href="route('home')" class="logo m-0 float-start d-flex align-items-center">
                            <img :src="logoImage" alt="Cauris Immo Logo" class="logo-img me-2">
                            <span class="logo-text">Cauris Immo</span>
                        </Link>

                        <ul class="js-clone-nav d-none d-lg-inline-block text-start site-menu float-end">
                            <!-- ========== MENU VISITEUR ========== -->
                            <template v-if="isGuest">
                                <!-- Catalogue avec filtres (pour visiteur) -->
                                <li class="has-children">
                                    <a href="#" @click.prevent>Catalogue</a>
                                    <ul class="dropdown filter-dropdown">
                                        <li class="filter-section">
                                            <h6 class="filter-title">Filtres de recherche</h6>
                                        </li>
                                        <li>
                                            <a href="#" @click.prevent="showFilterModal('price')" class="filter-option">
                                                <i class="fas fa-euro-sign me-2"></i>
                                                Filtrer par prix
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#" @click.prevent="showFilterModal('location')" class="filter-option">
                                                <i class="fas fa-map-marker-alt me-2"></i>
                                                Filtrer par Ville/Adresse
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#" @click.prevent="showFilterModal('features')" class="filter-option">
                                                <i class="fas fa-home me-2"></i>
                                                Filtrer par caractéristiques
                                                <small class="d-block text-muted">Chambres • Salles de bain • Étages</small>
                                            </a>
                                        </li>
                                        <li class="filter-section mt-3 pt-2">
                                            <h6 class="filter-title">Navigation rapide</h6>
                                        </li>
                                        <li>
                                            <Link :href="route('home')" class="filter-option" @click="handleNavClick">
                                                <i class="fas fa-list me-2"></i>
                                                Tous les biens disponibles
                                            </Link>
                                        </li>
                                    </ul>
                                </li>

                                <!-- Boutons Connexion et Inscription (pour visiteur) -->
                                <li>
                                    <Link :href="route('login')" class="btn btn-outline-light btn-sm me-2">
                                        <i class="fas fa-sign-in-alt me-1"></i>
                                        Connexion
                                    </Link>
                                </li>
                                <li>
                                    <Link :href="route('register')" class="btn btn-light btn-sm">
                                        <i class="fas fa-user-plus me-1"></i>
                                        Inscription
                                    </Link>
                                </li>
                            </template>

                            <!-- ========== MENU UTILISATEUR AUTHENTIFIÉ ========== -->
                            <template v-else>
                                <!-- Catalogue avec filtres (pour non-admin) -->
                                <li class="has-children" v-if="!hasRole('admin')">
                                    <a href="#" @click.prevent>Catalogue</a>
                                    <ul class="dropdown filter-dropdown">
                                        <li class="filter-section">
                                            <h6 class="filter-title">Filtres de recherche</h6>
                                        </li>
                                        <li>
                                            <a href="#" @click.prevent="showFilterModal('price')" class="filter-option">
                                                <i class="fas fa-euro-sign me-2"></i>
                                                Filtrer par prix
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#" @click.prevent="showFilterModal('location')" class="filter-option">
                                                <i class="fas fa-map-marker-alt me-2"></i>
                                                Filtrer par Ville/Adresse
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#" @click.prevent="showFilterModal('features')" class="filter-option">
                                                <i class="fas fa-home me-2"></i>
                                                Filtrer par caractéristiques
                                                <small class="d-block text-muted">Chambres • Salles de bain • Étages</small>
                                            </a>
                                        </li>
                                        <li class="filter-section mt-3 pt-2">
                                            <h6 class="filter-title">Navigation rapide</h6>
                                        </li>
                                        <li>
                                            <Link :href="route('home')" class="filter-option" @click="handleNavClick">
                                                <i class="fas fa-list me-2"></i>
                                                Tous les biens disponibles
                                            </Link>
                                        </li>
                                        <!-- Gestion des catégories - Admin uniquement -->
                                        <li v-if="hasRole('admin')">
                                            <Link :href="route('categories.index')" class="filter-option" @click="handleNavClick">
                                                <i class="fas fa-tags me-2"></i>
                                                Gestion des catégories
                                            </Link>
                                        </li>
                                    </ul>
                                </li>

                                <!-- Mes réservations (non-admin) -->
                                <li v-if="!hasRole('admin')">
                                    <Link
                                        :href="route('reservations.index')"
                                        :class="{ 'active': isCurrentRoute('reservations.*') }"
                                        @click="handleNavClick"
                                    >
                                        Mes réservations
                                    </Link>
                                </li>

                                <!-- Mes Baux (non-admin) -->
                                <li v-if="!hasRole('admin')">
                                    <Link
                                        :href="route('locations.index')"
                                        :class="{ 'active': isCurrentRoute('locations.client.*') }"
                                        @click="handleNavClick"
                                    >
                                        Mes Contrats de location
                                    </Link>
                                </li>

                                <!-- Mes Locations (non-admin) -->
                                <li v-if="!hasRole('admin')">
                                    <Link
                                        :href="route('locations.mes-loyers')"
                                        :class="{ 'active': isCurrentRoute('locations.mes-loyers') }"
                                        @click="handleNavClick"
                                    >
                                        Mes Locations
                                    </Link>
                                </li>

                                <!-- Mes Contrats de Vente (non-admin) -->
                                <li v-if="!hasRole('admin')">
                                    <Link
                                        :href="route('ventes.index')"
                                        :class="{ 'active': isCurrentRoute('ventes.*') }"
                                        @click="handleNavClick"
                                    >
                                        Mes Contrats de Vente
                                    </Link>
                                </li>

                                <!-- Toutes les réservations (admin) -->
                                <li v-if="hasRole('admin')">
                                    <Link
                                        :href="route('admin.reservations.index')"
                                        :class="{ 'active': isCurrentRoute('admin.reservations.*') }"
                                        @click="handleNavClick"
                                    >
                                        Toutes les réservations
                                    </Link>
                                </li>

                                <!-- Gestion des Visites (admin) -->
                                <li v-if="hasRole('admin')">
                                    <Link
                                        :href="route('visites.index')"
                                        :class="{ 'active': isCurrentRoute('visites.*') }"
                                        @click="handleNavClick"
                                    >
                                        <i class="fas fa-calendar-check me-1"></i>
                                        Gestion des Visites
                                    </Link>
                                </li>

                                <!-- Mes Biens / Tous les Biens (propriétaire ou admin) -->
                                <li v-if="hasRole('proprietaire') || hasRole('admin')">
                                    <Link
                                        :href="route('biens.index')"
                                        :class="{ 'active': isCurrentRoute('biens.*') }"
                                        @click="handleNavClick"
                                    >
                                        {{ hasRole('admin') ? 'Tous les Biens' : 'Mes Biens' }}
                                    </Link>
                                </li>

                                <!-- Suivi de mes biens (propriétaire uniquement) -->
                                <li v-if="hasRole('proprietaire')">
                                    <Link
                                        :href="route('dashboard.proprietaire')"
                                        :class="{ 'active': isCurrentRoute('dashboard.proprietaire') }"
                                        @click="handleNavClick"
                                    >
                                        Suivi de mes biens
                                    </Link>
                                </li>

                                <!-- Dashboard Admin Global (admin uniquement) -->
                                <li v-if="hasRole('admin')">
                                    <Link
                                        :href="route('dashboard.admin.global')"
                                        :class="{ 'active': isCurrentRoute('dashboard.admin.global') }"
                                        @click="handleNavClick"
                                    >
                                        <i class="fas fa-crown"></i>
                                        Dashboard Admin Global
                                    </Link>
                                </li>

                                <!-- Toutes les Catégories (admin uniquement) -->
                                <li v-if="hasRole('admin')">
                                    <Link
                                        :href="route('categories.index')"
                                        :class="{ 'active': isCurrentRoute('categories.*') }"
                                        @click="handleNavClick"
                                    >
                                        Toutes les Catégories
                                    </Link>
                                </li>



                                <li v-if="!hasRole('proprietaire') && !hasRole('admin')">
                                    <Link
                                        :href="route('client-dossiers.index')"
                                        :class="{ 'active': isCurrentRoute('client-dossiers.index') }"
                                        @click="handleNavClick"
                                    >
                                        Gérer mon dossier
                                    </Link>
                                </li>

                                <li>
                                    <Link :href="route('conversations.index')" @click="handleNavClick">
                                        Messages
                                    </Link>
                                </li>

                                <!-- Faire gérer mes biens (clients non-propriétaires) -->
                                <li v-if="!hasRole('proprietaire') && !hasRole('admin')">
                                    <Link
                                        :href="route('proprietaire.demande')"
                                        :class="{ 'active': isCurrentRoute('proprietaire.demande') }"
                                        @click="handleNavClick"
                                    >
                                        Faire gérer mes biens
                                    </Link>
                                </li>
                                <!-- Déconnexion -->
                                <li>
                                    <Link
                                        :href="route('auth.logout')"
                                        method="post"
                                        as="button"
                                        class="btn btn-outline-danger btn-secondary"
                                        @click="handleLogout"
                                    >
                                        Déconnexion
                                    </Link>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Filter Modal -->
        <FilterModal
            v-if="showModal"
            :active-filter="activeFilter"
            :filters="filters"
            @close="closeModal"
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <!-- Main Content -->
        <main id="main-content" class="main-content flex-grow-1 pt-nav mt-nav">
            <div class="container-fluid">
                <!-- Success notifications -->
                <div v-if="successMessage" class="alert alert-success mt-3 alert-dismissible fade show">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-3 fs-4"></i>
                        <div>
                            <strong>Succès !</strong><br>
                            {{ successMessage }}
                        </div>
                    </div>
                    <button type="button" class="btn-close" @click="dismissMessage"></button>
                </div>

                <!-- Error notifications -->
                <div v-if="hasErrors" class="alert alert-danger mt-3 alert-dismissible fade show">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Erreurs détectées</h5>
                    <ul class="mb-0">
                        <li v-for="(error, key) in $page.props.errors" :key="key">{{ error }}</li>
                    </ul>
                    <button type="button" class="btn-close" @click="dismissErrors"></button>
                </div>

                <!-- Active filters display -->
                <div v-if="hasActiveFilters" class="alert alert-info mt-3 d-flex align-items-center justify-content-between">
                    <div class="filter-badges">
                        <strong><i class="fas fa-filter me-2"></i>Filtres actifs:</strong>
                        <span v-for="badge in activeFilterBadges" :key="badge.key" :class="`badge ${badge.class} ms-2`">
                            {{ badge.label }}
                        </span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" @click="clearAllFilters">
                        <i class="fas fa-times me-1"></i> Effacer tous
                    </button>
                </div>

                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="site-footer bg-dark text-white py-4 mt-auto">
            <div class="container">
                <div class="row gy-4">
                    <div class="col-md-4">
                        <h5 class="mb-3">Contact</h5>
                        <address class="mb-0">
                            <p class="mb-1">Parcelles assainies, Keur Massar, Dakar</p>
                            <p class="mb-1">Tél : <a href="tel:+221782915318" class="text-white">+221 78 291 53 18</a></p>
                            <p>Email : <a href="mailto:caurisimmobiliere@gmail.com" class="text-white">caurisimmobiliere@gmail.com</a></p>
                        </address>
                    </div>
                    <div class="col-md-4">
                        <h5 class="mb-3">Navigation</h5>
                        <ul class="list-unstyled">
                            <li><Link :href="route('home')" class="text-white">Accueil</Link></li>
                            <li><Link :href="route('biens.catalogue')" class="text-white">Catalogue</Link></li>
                            <li><a href="#" class="text-white">Services</a></li>
                            <li><Link :href="route('conversations.index')" class="text-white">Contact</Link></li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h5 class="mb-3">Suivez-nous</h5>
                        <div class="d-flex gap-3">
                            <a href="#" class="text-white fs-4"><i class="icon-facebook"></i></a>
                            <a href="#" class="text-white fs-4"><i class="icon-instagram"></i></a>
                            <a href="#" class="text-white fs-4"><i class="icon-linkedin"></i></a>
                            <a href="#" class="text-white fs-4"><i class="icon-twitter"></i></a>
                        </div>
                    </div>
                </div>
                <hr class="bg-light my-4" />
                <div class="text-center">
                    <small>&copy; {{ currentYear }} Cauris Immo. Tous droits réservés.</small>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref, computed, provide, onMounted, watch } from 'vue'
import FlashMessages from '../Pages/Components/FlashMessages.vue'
import FilterModal from '../Pages/Components/FilterModal.vue'
import logoImage from '../assets/images/cauris_immo_logo.jpg'

const page = usePage()

// Props
const props = defineProps({
    errors: Object,
    auth: Object,
    flash: Object
})

// State
const showModal = ref(false)
const activeFilter = ref('')
const filters = ref({
    minPrice: '',
    maxPrice: '',
    city: '',
    address: '',
    rooms: '',
    bathrooms: '',
    floors: ''
})

// Computed properties
const currentYear = computed(() => new Date().getFullYear())

const hasErrors = computed(() => {
    return props.errors && Object.keys(props.errors).length > 0
})

const successMessage = computed(() => {
    return page.props.flash?.success || null
})

const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => value !== '')
})

const activeFilterBadges = computed(() => {
    const badges = []

    if (filters.value.minPrice || filters.value.maxPrice) {
        badges.push({
            key: 'price',
            label: `Prix: ${formatPriceRange()}`,
            class: 'bg-primary'
        })
    }

    if (filters.value.city) {
        badges.push({
            key: 'city',
            label: `Ville: ${filters.value.city}`,
            class: 'bg-success'
        })
    }

    if (filters.value.address) {
        badges.push({
            key: 'address',
            label: `Adresse: ${filters.value.address}`,
            class: 'bg-success'
        })
    }

    if (filters.value.rooms) {
        badges.push({
            key: 'rooms',
            label: `${filters.value.rooms} chambre${filters.value.rooms > 1 ? 's' : ''}`,
            class: 'bg-info'
        })
    }

    if (filters.value.bathrooms) {
        badges.push({
            key: 'bathrooms',
            label: `${filters.value.bathrooms} SdB`,
            class: 'bg-info'
        })
    }

    if (filters.value.floors) {
        badges.push({
            key: 'floors',
            label: `${filters.value.floors} étage${filters.value.floors > 1 ? 's' : ''}`,
            class: 'bg-info'
        })
    }

    return badges
})

// ✅ DÉTECTION VISITEUR (3 méthodes)
const isGuest = computed(() => {
    const user = page.props.auth?.user

    console.log('🔍 Layout - User data:', user)
    console.log('🔍 Layout - Is guest?', user?.is_guest)
    console.log('🔍 Layout - User roles:', user?.roles)

    // 1. Vérifier le flag is_guest
    if (user?.is_guest === true || user?.is_guest === 1) {
        return true
    }

    // 2. Vérifier si l'utilisateur a UNIQUEMENT le rôle "visiteur"
    if (user?.roles && Array.isArray(user.roles)) {
        const hasVisiteurRole = user.roles.includes('visiteur')
        const hasOnlyVisiteurRole = user.roles.length === 1 && hasVisiteurRole

        if (hasOnlyVisiteurRole) {
            return true
        }
    }

    // 3. Vérifier si l'email contient "guest_"
    if (user?.email && user.email.includes('guest_') && user.email.includes('@temporary.local')) {
        return true
    }

    // Par défaut, si aucun utilisateur n'est connecté
    if (!user) {
        return true
    }

    return false
})

// ✅ Fonction hasRole pour vérifier les rôles
const hasRole = (roleName) => {
    // Un visiteur ne peut avoir aucun rôle
    if (isGuest.value) return false

    const user = page.props.auth?.user
    return user?.roles?.includes(roleName) || false
}

// ✅ Fonction isCurrentRoute pour vérifier la route active
const isCurrentRoute = (routePattern) => {
    if (!routePattern) return false
    return route().current(routePattern)
}

// Watch pour debug
watch(isGuest, (newValue) => {
    console.log('🎯 Layout - isGuest changed to:', newValue)
})

// Methods
const handleNavClick = () => {
    // Fermer les menus mobiles si nécessaire
}

const handleLogout = () => {
    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        router.post(route('auth.logout'))
    }
}

const showFilterModal = (filterType) => {
    activeFilter.value = filterType
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    activeFilter.value = ''
}

const applyFilters = (newFilters) => {
    filters.value = { ...newFilters }

    const queryParams = new URLSearchParams()
    Object.entries(filters.value).forEach(([key, value]) => {
        if (value) queryParams.set(key, value)
    })

    router.visit(route('home') + (queryParams.toString() ? '?' + queryParams.toString() : ''), {
        preserveState: false,
        preserveScroll: false
    })
    closeModal()
}

const resetFilters = () => {
    filters.value = {
        minPrice: '',
        maxPrice: '',
        city: '',
        address: '',
        rooms: '',
        bathrooms: '',
        floors: ''
    }
}

const clearAllFilters = () => {
    resetFilters()
    router.visit(route('home'), {
        preserveState: false,
        preserveScroll: false
    })
}

const formatPriceRange = () => {
    const format = (val) => new Intl.NumberFormat('fr-FR').format(val)

    if (filters.value.minPrice && filters.value.maxPrice) {
        return `${format(filters.value.minPrice)} - ${format(filters.value.maxPrice)} FCFA`
    } else if (filters.value.minPrice) {
        return `À partir de ${format(filters.value.minPrice)} FCFA`
    } else if (filters.value.maxPrice) {
        return `Jusqu'à ${format(filters.value.maxPrice)} FCFA`
    }
    return ''
}

const dismissMessage = () => {
    page.props.flash.success = null
}

const dismissErrors = () => {
    // Errors will be cleared on next navigation
}

// Initialize filters from URL
const initializeFiltersFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search)

    filters.value = {
        minPrice: urlParams.get('minPrice') || '',
        maxPrice: urlParams.get('maxPrice') || '',
        city: urlParams.get('city') || '',
        address: urlParams.get('address') || '',
        rooms: urlParams.get('rooms') || '',
        bathrooms: urlParams.get('bathrooms') || '',
        floors: urlParams.get('floors') || ''
    }
}

// Lifecycle
onMounted(() => {
    initializeFiltersFromUrl()

    console.log('🚀 Layout mounted')
    console.log('🚀 Initial isGuest:', isGuest.value)
    console.log('🚀 Auth user:', page.props.auth?.user)
})

// Provide to child components
provide('filters', filters)
provide('hasActiveFilters', hasActiveFilters)
provide('isGuest', isGuest)
provide('hasRole', hasRole)
</script>


<style scoped>
/* Skip to content link (accessibility) */
.skip-to-content {
    position: absolute;
    top: -40px;
    left: 0;
    background: #006064;
    color: white;
    padding: 8px;
    text-decoration: none;
    z-index: 100;
}

.skip-to-content:focus {
    top: 0;
}

/* Layout */
.Layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* Navigation */
.site-nav {
    background-color: #006064;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    z-index: 1050;
    height: 70px;
}

.pt-nav {
    padding-top: 90px;
}

.menu-bg-wrap {
    padding: 0.5rem 0;
}

.site-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Logo */
.logo {
    color: white;
    font-weight: bold;
    text-decoration: none;
    font-size: 1.5rem;
    transition: color 0.3s ease;
}

.logo:hover {
    color: #e0f7fa;
}

.logo-img {
    height: 50px;
    width: auto;
    border-radius: 50%;
}

/* Mobile menu toggle */
.mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
}

.hamburger-icon {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.hamburger-icon span {
    display: block;
    width: 25px;
    height: 3px;
    background: white;
    transition: all 0.3s ease;
}

/* Desktop menu */
.site-menu {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    align-items: center;
    gap: 1.5rem;
}

.site-menu li {
    position: relative;
}

.site-menu a,
.site-menu button {
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem 0.75rem;
}

.site-menu a:hover,
.site-menu button:hover,
.site-menu li.active > a {
    color: #e0f7fa;
}

/* Dropdown */
.has-children > a .dropdown-arrow {
    font-size: 0.75rem;
    transition: transform 0.3s ease;
}

.has-children:hover > a .dropdown-arrow {
    transform: rotate(180deg);
}

.dropdown {
    position: absolute;
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    min-width: 320px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    top: 100%;
    left: 0;
    z-index: 1000;
}

.has-children:hover > .dropdown,
.dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown li {
    margin: 0.5rem 0;
}

.filter-section {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
}

.filter-title {
    color: #006064;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

.filter-option {
    display: flex;
    align-items: center;
    padding: 0.75rem !important;
    border-radius: 6px;
    transition: all 0.3s ease;
    color: #333 !important;
}

.filter-option:hover {
    background: linear-gradient(135deg, #006064, #00838f);
    color: white !important;
    transform: translateX(5px);
}

.filter-option i {
    color: #006064;
    width: 20px;
    transition: color 0.3s ease;
}

.filter-option:hover i {
    color: white;
}

/* Alerts */
.alert {
    border-radius: 8px;
    border: none;
    animation: slideInDown 0.3s ease;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.filter-badges .badge {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Footer */
.site-footer {
    background: #263238;
}

.site-footer a {
    transition: color 0.3s ease;
}

.site-footer a:hover {
    color: #e0f7fa !important;
}

/* Mobile responsive */
@media (max-width: 991px) {
    .mobile-menu-toggle {
        display: block;
    }

    .site-menu {
        position: fixed;
        top: 70px;
        left: 0;
        right: 0;
        background: #006064;
        flex-direction: column;
        align-items: stretch;
        padding: 1rem;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .site-menu.mobile-menu-open {
        max-height: calc(100vh - 70px);
        overflow-y: auto;
    }

    .site-menu li {
        margin: 0;
    }

    .site-menu a,
    .site-menu button {
        display: block;
        width: 100%;
        text-align: left;
        padding: 1rem;
    }

    .dropdown {
        position: static;
        opacity: 1;
        visibility: visible;
        transform: none;
        box-shadow: none;
        margin-left: 1rem;
    }
}
</style>

