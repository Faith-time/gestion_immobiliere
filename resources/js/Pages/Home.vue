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
                    action="#"
                    class="narrow-w form-search d-flex align-items-stretch mb-3"
                    data-aos="fade-up"
                    data-aos-delay="200"
                >
                    <input
                        type="text"
                        class="form-control px-4"
                        placeholder="Votre quartier ou ville. ex: Dakar, Almadies"
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
                        Nos Propriétés Vedettes
                    </h2>
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

            <div class="row">
                <div class="col-12">
                    <div class="property-slider-wrap">
                        <div class="property-slider">
                            <div
                                v-for="bien in biens"
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

                                        <Link
                                            :href="route('biens.show', bien.id)"
                                            class="btn btn-primary py-2 px-3"
                                        >Voir les détails</Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            id="property-nav"
                            class="controls"
                            tabindex="0"
                            aria-label="Navigation du carrousel"
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

    <!-- Section Témoignages clients -->

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
                            :src="biens[0]?.image ? `/storage/${biens[3].image}` : '/images/placeholder.jpg'"
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
import { onMounted, computed } from 'vue'
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

// Computed properties
const totalProperties = computed(() => {
    return props.totalBiens || props.biens.length
})

// Obtenir les 3 premiers biens avec images pour le slider hero
const heroSliderBiens = computed(() => {
    return props.biens.filter(bien => bien.image).slice(0, 3)
})

// Méthodes utilitaires
const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR').format(price)
}

onMounted(() => {
    // Hero slider
    tns({
        container: '.hero-slider',
        items: 1,
        autoplay: true,
        autoplayButtonOutput: false,
        controls: false,
        nav: false,
        mode: 'carousel'
    });

    // Property slider - seulement s'il y a des biens
    if (props.biens.length > 0) {
        tns({
            container: '.property-slider',
            items: 3,
            gutter: 30,
            autoplay: true,
            autoplayButtonOutput: false,
            controlsContainer: '#property-nav',
            responsive: {
                0: { items: 1 },
                700: { items: 2 },
                1024: { items: 3 }
            }
        });
    }
});
</script>
