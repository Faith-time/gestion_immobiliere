<template>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center py-5">
                        <!-- Icône d'erreur -->
                        <div class="mb-4">
                            <div class="mx-auto d-flex align-items-center justify-content-center"
                                 style="width: 80px; height: 80px; background-color: #fee2e2; border-radius: 50%;">
                                <i class="fas fa-times-circle text-danger" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>

                        <!-- Titre -->
                        <h3 class="text-danger mb-3">Erreur de paiement</h3>

                        <!-- Message d'erreur -->
                        <div class="alert alert-danger text-start mb-4">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
                                <div>
                                    <strong>Une erreur est survenue :</strong>
                                    <p class="mb-0 mt-1">{{ message }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Suggestions -->
                        <div class="text-muted mb-4">
                            <p class="mb-2">Que faire maintenant ?</p>
                            <ul class="list-unstyled text-start">
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Vérifiez votre solde ou vos informations bancaires
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Réessayez avec un autre mode de paiement
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Contactez notre support si le problème persiste
                                </li>
                            </ul>
                        </div>

                        <!-- Actions -->
                        <div class="d-grid gap-2">
                            <Link :href="route('home')"
                                  class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-home me-2"></i>
                                Retour à l'accueil
                            </Link>

                            <button @click="$inertia.visit($page.props.previous_url || route('home'))"
                                    class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Retour à la page précédente
                            </button>
                        </div>

                        <!-- Support -->
                        <div class="mt-4 pt-4 border-top">
                            <p class="text-muted mb-2">
                                <i class="fas fa-life-ring me-1"></i>
                                Besoin d'aide ?
                            </p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="mailto:support@example.com" class="text-decoration-none">
                                    <i class="fas fa-envelope me-1"></i>
                                    support@example.com
                                </a>
                                <a href="tel:+221123456789" class="text-decoration-none">
                                    <i class="fas fa-phone me-1"></i>
                                    +221 12 345 67 89
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
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

const props = defineProps({
    message: {
        type: String,
        default: 'Une erreur est survenue lors du paiement. Veuillez réessayer.'
    }
})
</script>

<style scoped>
.card {
    border-radius: 15px;
}

.alert {
    border-radius: 10px;
}

.btn {
    border-radius: 8px;
}
</style>
