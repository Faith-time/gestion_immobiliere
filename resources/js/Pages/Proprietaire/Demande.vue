<template>
    <div class="container py-5">
        <h2 class="mb-4">Confier vos biens à l'agence</h2>
        <p>
            Vous êtes propriétaire et souhaitez que l'agence gère vos biens (location, entretien, etc.) ?
            Cliquez sur le bouton ci-dessous pour activer votre espace propriétaire.
        </p>
        <form @submit.prevent="submitDemande">
            <button type="submit" class="btn btn-primary">
                Activer mon espace propriétaire
            </button>
        </form>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'

const submitDemande = () => {
    router.post(route('proprietaire.store'))
}
</script>
