<template>
    <div class="py-5">
        <div class="container">
            <!-- En-tête -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold text-primary mb-3">
                    <i class="fas fa-comments me-3"></i>Témoignages Clients
                </h1>
                <p class="lead text-muted">
                    Découvrez les expériences de nos clients satisfaits
                </p>
                <div class="rating-summary mt-4">
                    <div class="d-flex justify-content-center align-items-center gap-2">
                        <span class="display-6 fw-bold text-warning">4.8</span>
                        <div>
                            <div class="stars">
                                <i v-for="i in 5" :key="i" class="fas fa-star text-warning"></i>
                            </div>
                            <small class="text-muted">{{ testimonials.total }} avis clients</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bouton pour laisser un avis -->
            <div v-if="$page.props.auth.user" class="text-center mb-5">
                <Link
                    :href="route('testimonials.create')"
                    class="btn btn-primary btn-lg px-5"
                >
                    <i class="fas fa-pen me-2"></i>Laisser mon témoignage
                </Link>
            </div>

            <!-- Grille de témoignages -->
            <div class="row g-4">
                <div
                    v-for="testimonial in testimonials.data"
                    :key="testimonial.id"
                    class="col-lg-4 col-md-6"
                >
                    <div class="testimonial-card" :class="{ 'featured': testimonial.is_featured }">
                        <div v-if="testimonial.is_featured" class="featured-badge">
                            <i class="fas fa-star"></i> Coup de cœur
                        </div>

                        <!-- En-tête avec avatar -->
                        <div class="testimonial-header">
                            <div class="avatar">
                                <img
                                    v-if="testimonial.avatar"
                                    :src="`/storage/${testimonial.avatar}`"
                                    :alt="testimonial.user.name"
                                >
                                <div v-else class="avatar-initials">
                                    {{ testimonial.initials }}
                                </div>
                            </div>
                            <div class="user-info">
                                <h5 class="mb-1">{{ testimonial.user.name }}</h5>
                                <div class="rating">
                                    <i
                                        v-for="i in 5"
                                        :key="i"
                                        class="fas fa-star"
                                        :class="i <= testimonial.rating ? 'text-warning' : 'text-muted'"
                                    ></i>
                                </div>
                                <small class="text-muted">
                                    {{ formatDate(testimonial.created_at) }}
                                </small>
                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="testimonial-content">
                            <p class="testimonial-text">
                                <i class="fas fa-quote-left quote-icon"></i>
                                {{ testimonial.content }}
                                <i class="fas fa-quote-right quote-icon"></i>
                            </p>
                        </div>

                        <!-- Bien associé -->
                        <div v-if="testimonial.bien" class="testimonial-property">
                            <i class="fas fa-home me-2 text-primary"></i>
                            <small>{{ testimonial.bien.title }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message si aucun témoignage -->
            <div v-if="testimonials.data.length === 0" class="text-center py-5">
                <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Aucun témoignage pour le moment</h4>
                <p class="text-muted">Soyez le premier à partager votre expérience !</p>
            </div>

            <!-- Pagination -->
            <div v-if="testimonials.data.length > 0" class="mt-5">
                <nav>
                    <ul class="pagination justify-content-center">
                        <li
                            v-for="link in testimonials.links"
                            :key="link.label"
                            class="page-item"
                            :class="{ active: link.active, disabled: !link.url }"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="page-link"
                                v-html="link.label"
                            />
                            <span v-else class="page-link" v-html="link.label"></span>
                        </li>
                    </ul>
                </nav>
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
import { route } from 'ziggy-js'

defineProps({
    testimonials: Object
})

const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}
</script>

<style scoped>
.rating-summary {
    padding: 30px;
    background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
    border-radius: 16px;
    display: inline-block;
}

.stars i {
    font-size: 20px;
}

.testimonial-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    position: relative;
}

.testimonial-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.testimonial-card.featured {
    border: 3px solid #ffd700;
    background: linear-gradient(135deg, #fffef7 0%, #fff9e6 100%);
}

.featured-badge {
    position: absolute;
    top: -12px;
    right: 20px;
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #1a1a1a;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
}

.testimonial-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-initials {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    font-size: 20px;
    font-weight: 700;
}

.user-info h5 {
    color: #2c3e50;
    font-weight: 700;
}

.rating i {
    font-size: 14px;
}

.testimonial-content {
    flex: 1;
    margin-bottom: 20px;
}

.testimonial-text {
    color: #2c3e50;
    line-height: 1.8;
    font-style: italic;
    position: relative;
    margin: 0;
}

.quote-icon {
    color: #17a2b8;
    opacity: 0.3;
    font-size: 14px;
}

.fa-quote-left {
    margin-right: 8px;
}

.fa-quote-right {
    margin-left: 8px;
}

.testimonial-property {
    padding-top: 15px;
    border-top: 2px solid #e9ecef;
    color: #6c757d;
}

.pagination {
    gap: 10px;
}

.page-link {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    color: #17a2b8;
    font-weight: 600;
}

.page-item.active .page-link {
    background-color: #17a2b8;
    border-color: #17a2b8;
}

@media (max-width: 768px) {
    .testimonial-card {
        padding: 20px;
    }

    .avatar {
        width: 50px;
        height: 50px;
    }
}
</style>
