<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const form = useForm({
    name: ''
})

const submit = () => {
    form.post(route('categories.store'), {
        onSuccess: () => router.visit(route('categories.index'))
    })
}

const cancel = () => {
    router.visit(route('categories.index'))
}
</script>

<script>
import Layout from '@/Pages/Layout.vue'

export default {
    layout: Layout
}
</script>

<template>
    <div class="container my-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Ajouter une catégorie</h4>
            </div>
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="mb-3">
                        <label class="form-label">Nom de la catégorie</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.name }"
                            placeholder="Entrez le nom de la catégorie"
                            required
                        />
                        <div v-if="form.errors.name" class="invalid-feedback">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button
                            type="button"
                            @click="cancel"
                            class="btn btn-secondary me-2"
                            :disabled="form.processing"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
