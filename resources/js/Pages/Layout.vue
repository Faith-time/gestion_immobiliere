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
                                <ul class="dropdown">
                                    <li>
                                        <Link :href="route('biens.index', { filter: 'price' })" @click="handleNavClick">
                                            <i class="fas fa-money-bill-wave me-2"></i>Filtrer par prix
                                        </Link>
                                    </li>
                                    <li>
                                        <Link :href="route('biens.index', { filter: 'location' })" @click="handleNavClick">
                                            <i class="fas fa-map-marker-alt me-2"></i>Filtrer par ville/adresse
                                        </Link>
                                    </li>
                                    <li>
                                        <Link :href="route('biens.index', { filter: 'features' })" @click="handleNavClick">
                                            <i class="fas fa-th-large me-2"></i>Filtrer par caractéristiques
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
                                    @click="handleNavClick"
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

// Gestion des clics de navigation avec debug
const handleNavClick = (event) => {
    console.log('Navigation click:', event.target.href)
}

const handleLogout = () => {
    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        // Le logout se fera automatiquement via Inertia
        console.log('Logout initiated')
    } else {
        event.preventDefault()
    }
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
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    min-width: 250px;
    display: none;
    z-index: 1000;
    top: 100%;
    left: 0;
    border: 1px solid #e0e0e0;
}

.has-children:hover > .dropdown {
    display: block;
}

.dropdown li {
    margin: 0;
}

.dropdown a {
    color: #333;
    padding: 0.8rem 1rem;
    display: flex;
    align-items: center;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.dropdown a:hover {
    background-color: #f8f9fa;
    color: #006064;
    transform: translateX(5px);
}

.dropdown a i {
    width: 20px;
    color: #006064;
}

.dropdown .has-children {
    position: relative;
}

.dropdown .dropdown {
    left: 100%;
    top: 0;
}

/* Styles pour les alertes */
.alert {
    border-radius: 4px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}
</style>
