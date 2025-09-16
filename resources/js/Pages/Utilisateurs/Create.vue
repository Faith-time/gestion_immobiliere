<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
})

const submit = () => {
    form.post(route('users.store'), {
        onSuccess: () => router.visit(route('users.index'))
    })
}

const cancel = () => {
    router.visit(route('users.index'))
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
                <h4 class="mb-0">Ajouter un utilisateur</h4>
            </div>
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="mb-3">
                        <label class="form-label">Nom complet</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.name }"
                            placeholder="Entrez le nom complet"
                            required
                        />
                        <div v-if="form.errors.name" class="invalid-feedback">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adresse email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.email }"
                            placeholder="Entrez l'adresse email"
                            required
                        />
                        <div v-if="form.errors.email" class="invalid-feedback">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.password }"
                            placeholder="Entrez le mot de passe"
                            required
                        />
                        <div v-if="form.errors.password" class="invalid-feedback">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirmer le mot de passe</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.password_confirmation }"
                            placeholder="Confirmez le mot de passe"
                            required
                        />
                        <div v-if="form.errors.password_confirmation" class="invalid-feedback">
                            {{ form.errors.password_confirmation }}
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
