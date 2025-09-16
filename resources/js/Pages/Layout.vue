<template>
    <div class="Layout d-flex flex-column min-vh-100">
        <!-- Navbar -->
        <nav class="site-nav fixed-top w-100">
            <div class="menu-bg-wrap">
                <div class="container">
                    <div class="site-navigation py-3">
                        <Link :href="route('home')" class="logo m-0 float-start">Agence Immobilière</Link>

                        <ul class="js-clone-nav d-none d-lg-inline-block text-start site-menu float-end">
                            <li>
                                <Link
                                    :href="route('home')"
                                    :class="{ 'active': route().current('home') }"
                                    @click="handleNavClick"
                                >
                                    Accueil
                                </Link>
                            </li>
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
                                            Filtrer par City/Adresse
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
                                        <Link :href="route('biens.index')" class="filter-option" @click="handleNavClick">
                                            <i class="fas fa-list me-2"></i>
                                            Tous les biens
                                        </Link>
                                    </li>
                                    <li>
                                        <Link :href="route('categories.index')" class="filter-option" @click="handleNavClick">
                                            <i class="fas fa-tags me-2"></i>
                                            Gestion des catégories
                                        </Link>
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <Link
                                    :href="route('users.index')"
                                    :class="{ 'active': route().current('users.*') }"
                                    @click="handleNavClick"
                                >
                                    Utilisateurs
                                </Link>
                            </li>
                            <li>
                                <Link
                                    :href="route('biens.index')"
                                    :class="{ 'active': route().current('biens.*') }"
                                    @click="handleNavClick"
                                >
                                    Biens
                                </Link>
                            </li>
                            <li>
                                <Link
                                    :href="route('categories.index')"
                                    :class="{ 'active': route().current('categories.*') }"
                                >
                                    Catégories
                                </Link>
                            </li>
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
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Modal de filtres -->
        <div
            v-if="showModal"
            class="modal fade show d-block"
            tabindex="-1"
            style="background-color: rgba(0,0,0,0.5);"
            @click="closeModal"
        >
            <div class="modal-dialog modal-lg" @click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ getModalTitle() }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Filtre par prix -->
                        <div v-if="activeFilter === 'price'" class="filter-content">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Prix minimum (FCFA)</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        v-model="filters.minPrice"
                                        placeholder="Ex: 5000000"
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Prix maximum (FCFA)</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        v-model="filters.maxPrice"
                                        placeholder="Ex: 50000000"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Filtre par localisation -->
                        <div v-if="activeFilter === 'location'" class="filter-content">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Ville</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="filters.city"
                                        placeholder="Ex: Dakar, Thiès, Saint-Louis"
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Adresse/Quartier</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="filters.address"
                                        placeholder="Ex: Almadies, Plateau, Mermoz"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Filtre par caractéristiques -->
                        <div v-if="activeFilter === 'features'" class="filter-content">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Nombre de chambres</label>
                                    <select class="form-select" v-model="filters.rooms">
                                        <option value="">Toutes</option>
                                        <option value="1">1 chambre</option>
                                        <option value="2">2 chambres</option>
                                        <option value="3">3 chambres</option>
                                        <option value="4">4 chambres</option>
                                        <option value="5">5+ chambres</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nombre de salles de bain</label>
                                    <select class="form-select" v-model="filters.bathrooms">
                                        <option value="">Toutes</option>
                                        <option value="1">1 salle de bain</option>
                                        <option value="2">2 salles de bain</option>
                                        <option value="3">3 salles de bain</option>
                                        <option value="4">4+ salles de bain</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nombre d'étages</label>
                                    <select class="form-select" v-model="filters.floors">
                                        <option value="">Tous</option>
                                        <option value="1">1 étage</option>
                                        <option value="2">2 étages</option>
                                        <option value="3">3 étages</option>
                                        <option value="4">4+ étages</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="resetFilters">
                            Réinitialiser
                        </button>
                        <button type="button" class="btn btn-primary" @click="applyFilters">
                            Appliquer les filtres
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <main class="main-content flex-grow-1 pt-nav mt-nav">
            <div class="container-fluid">
                <!-- Affichage des erreurs -->
                <div v-if="$page.props.errors && Object.keys($page.props.errors).length > 0" class="alert alert-danger mt-3">
                    <h5>Erreurs détectées :</h5>
                    <ul class="mb-0">
                        <li v-for="(error, key) in $page.props.errors" :key="key">
                            {{ error }}
                        </li>
                    </ul>
                </div>

                <!-- Messages de succès -->
                <div v-if="$page.props.flash?.success" class="alert alert-success mt-3">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Affichage des filtres actifs -->
                <div v-if="hasActiveFilters" class="alert alert-info mt-3 d-flex align-items-center justify-content-between">
                    <div>
                        <strong>Filtres actifs:</strong>
                        <span v-if="filters.minPrice || filters.maxPrice" class="badge bg-primary ms-2">
                            Prix: {{ formatFilterDisplay() }}
                        </span>
                        <span v-if="filters.city" class="badge bg-success ms-2">
                            Ville: {{ filters.city }}
                        </span>
                        <span v-if="filters.address" class="badge bg-success ms-2">
                            Adresse: {{ filters.address }}
                        </span>
                        <span v-if="filters.rooms" class="badge bg-info ms-2">
                            {{ filters.rooms }} chambre{{ filters.rooms > 1 ? 's' : '' }}
                        </span>
                        <span v-if="filters.bathrooms" class="badge bg-info ms-2">
                            {{ filters.bathrooms }} SdB
                        </span>
                        <span v-if="filters.floors" class="badge bg-info ms-2">
                            {{ filters.floors }} étage{{ filters.floors > 1 ? 's' : '' }}
                        </span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" @click="clearAllFilters">
                        Effacer tous les filtres
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
                        <p class="mb-1">43 Raymouth Rd. Baltimore, London 3910</p>
                        <p class="mb-1">Tél : <a href="tel:+11234567890" class="text-white">+1 (123) 456-7890</a></p>
                        <p>Email : <a href="mailto:info@agence.com" class="text-white">info@agence.com</a></p>
                    </div>
                    <div class="col-md-4">
                        <h5 class="mb-3">Navigation</h5>
                        <ul class="list-unstyled">
                            <li><Link :href="route('home')" class="text-white">Accueil</Link></li>
                            <li><Link :href="route('biens.index')" class="text-white">Biens</Link></li>
                            <li><a href="#" class="text-white">Services</a></li>
                            <li><a href="#" class="text-white">Contact</a></li>
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
                    <small>&copy; {{ new Date().getFullYear() }} Agence Immobilière. Tous droits réservés.</small>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref, computed, provide } from 'vue'

// État des filtres
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

// Computed pour vérifier s'il y a des filtres actifs
const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => value !== '')
})

// Provide les filtres aux composants enfants
provide('filters', filters)
provide('hasActiveFilters', hasActiveFilters)

// Gestion des clics de navigation avec debug
const handleNavClick = (event) => {
    console.log('Navigation click:', event.target.href)
}

const handleLogout = () => {
    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        console.log('Logout initiated')
    } else {
        event.preventDefault()
    }
}

// Gestion des filtres
const showFilterModal = (filterType) => {
    activeFilter.value = filterType
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    activeFilter.value = ''
}

const getModalTitle = () => {
    const titles = {
        price: 'Filtrer par prix',
        location: 'Filtrer par localisation',
        features: 'Filtrer par caractéristiques'
    }
    return titles[activeFilter.value] || 'Filtres'
}

const applyFilters = () => {
    // Ici vous pouvez émettre un événement ou utiliser Inertia pour recharger la page avec les filtres
    console.log('Filtres appliqués:', filters.value)

    // Exemple de redirection avec filtres (à adapter selon votre routage)
    const queryParams = new URLSearchParams()
    Object.entries(filters.value).forEach(([key, value]) => {
        if (value) queryParams.set(key, value)
    })

    // Redirection vers la page d'accueil avec les paramètres de filtre
    window.location.href = route('home') + (queryParams.toString() ? '?' + queryParams.toString() : '')

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
    // Redirection sans paramètres
    window.location.href = route('home')
}

const formatFilterDisplay = () => {
    if (filters.value.minPrice && filters.value.maxPrice) {
        return `${new Intl.NumberFormat('fr-FR').format(filters.value.minPrice)} - ${new Intl.NumberFormat('fr-FR').format(filters.value.maxPrice)} FCFA`
    } else if (filters.value.minPrice) {
        return `À partir de ${new Intl.NumberFormat('fr-FR').format(filters.value.minPrice)} FCFA`
    } else if (filters.value.maxPrice) {
        return `Jusqu'à ${new Intl.NumberFormat('fr-FR').format(filters.value.maxPrice)} FCFA`
    }
    return ''
}
</script>

<style scoped>
.Layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.site-nav {
    background-color: #006064;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    z-index: 1050;
    height: 70px;
}

.pt-nav {
    padding-top: 90px;
}

.mt-nav {
    margin-top: 0;
}

.menu-bg-wrap {
    padding: 0.5rem 0;
}

.site-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    color: white;
    font-weight: bold;
    text-decoration: none;
    font-size: 1.5rem;
}

.logo:hover {
    color: #e0f7fa;
    text-decoration: none;
}

.site-menu {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    align-items: center;
}

.site-menu li {
    margin-left: 1.5rem;
    position: relative;
}

.site-menu a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.site-menu a:hover,
.site-menu a.active {
    color: #e0f7fa;
}

.site-footer {
    background: #263238;
    padding-top: 2rem;
    padding-bottom: 2rem;
}

/* Dropdowns */
.dropdown {
    position: absolute;
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    min-width: 280px;
    display: none;
    z-index: 1000;
    top: 100%;
    left: 0;
    border: 1px solid #e9ecef;
}

.filter-dropdown {
    min-width: 320px;
    padding: 1.5rem;
}

.has-children:hover > .dropdown {
    display: block;
}

.dropdown li {
    margin: 0.5rem 0;
}

.dropdown a {
    color: #333;
    padding: 0.75rem;
    display: block;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.dropdown a:hover {
    background-color: #f8f9fa;
    color: #006064;
    transform: translateX(5px);
}

.dropdown .has-children {
    position: relative;
}

.dropdown .dropdown {
    left: 100%;
    top: 0;
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

.filter-option small {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* Modal personnalisé */
.modal-content {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
    background: linear-gradient(135deg, #006064, #00838f);
    color: white;
    border-radius: 12px 12px 0 0;
}

.modal-title {
    font-weight: 600;
}

.btn-close {
    filter: invert(1);
}

.filter-content {
    padding: 1rem 0;
}

.form-label {
    font-weight: 500;
    color: #333;
    margin-bottom: 0.5rem;
}

.form-control,
.form-select {
    border-radius: 8px;
    border: 1px solid #ddd;
    padding: 0.75rem;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-control:focus,
.form-select:focus {
    border-color: #006064;
    box-shadow: 0 0 0 0.2rem rgba(0, 96, 100, 0.25);
}

/* Styles pour les alertes */
.alert {
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border: none;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
}

.alert-info {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    color: #1565c0;
    border-left: 4px solid #2196f3;
}

.badge {
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
}

/* Animation pour les badges */
.badge {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
