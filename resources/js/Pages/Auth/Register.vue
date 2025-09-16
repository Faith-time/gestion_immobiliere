<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AuthLayout from "@/Layout/AuthLayout.vue";

defineOptions({
    layout: AuthLayout
})

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
})

const submit = () => {
    form.post(route('auth.register'), {
        onError: () => "Erreur lors de l'inscription"
    })
}

const signin = () => {
    router.visit(route('login'))
}
</script>

<template>
    <div class="auth-container">
        <div class="register-card">
            <!-- Section orange -->
            <div class="auth-left">
                <h2 class="auth-title">Rejoignez-nous</h2>
                <p class="auth-desc">Créez un compte pour accéder à votre tableau de bord et gérer vos biens facilement.</p>
            </div>

            <!-- Section blanche -->
            <div class="auth-right">
                <h3 class="auth-subtitle">Créer un compte</h3>
                <form @submit.prevent="submit">
                    <div class="form-group">
                        <label>Nom complet</label>
                        <input v-model="form.name" type="text" class="neu-input" required />
                        <div v-if="form.errors.name" class="text-danger text-sm mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div class="form-group mt-3">
                        <label>Adresse email</label>
                        <input v-model="form.email" type="email" class="neu-input" required />
                        <div v-if="form.errors.email" class="text-danger text-sm mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div class="form-group mt-3">
                        <label>Mot de passe</label>
                        <input v-model="form.password" type="password" class="neu-input" required />
                        <div v-if="form.errors.password" class="text-danger text-sm mt-1">{{ form.errors.password }}</div>
                    </div>

                    <div class="form-group mt-3">
                        <label>Confirmer le mot de passe</label>
                        <input v-model="form.password_confirmation" type="password" class="neu-input" required />
                        <div v-if="form.errors.password_confirmation" class="text-danger text-sm mt-1">{{ form.errors.password_confirmation }}</div>
                    </div>

                    <div class="d-grid gap-3 mt-4">
                        <button type="submit" class="neu-button" :disabled="form.processing">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            S'inscrire
                        </button>
                        <button type="button" class="neu-button-outline" @click="signin">
                            Déjà un compte ? Se connecter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.auth-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: #f3f4f6;
    padding: 2rem;
}

.register-card {
    display: flex;
    max-width: 1000px;
    width: 100%;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 12px 12px 24px #c8d0e7, -12px -12px 24px #ffffff;
}

/* Section orange */
.auth-left {
    flex: 1;
    background: green; /* orange-500 (tailwind) */
    color: white;
    padding: 3rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}

.auth-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.auth-desc {
    font-size: 1rem;
    max-width: 300px;
    opacity: 0.9;
}

.auth-image {
    width: 80%;
    max-width: 250px;
    margin-top: 2rem;
}

/* Section blanche */
.auth-right {
    flex: 1;
    background: #ffffff;
    padding: 3rem;
}

.auth-subtitle {
    font-size: 1.5rem;
    font-weight: 600;
    color: #222;
    margin-bottom: 2rem;
    text-align: center;
}

.form-group label {
    font-weight: 600;
    display: block;
    margin-bottom: 0.5rem;
    color: #555;
}

.neu-input {
    width: 100%;
    padding: 12px 18px;
    border-radius: 12px;
    border: none;
    background: #f0f0f3;
    box-shadow: inset 4px 4px 8px #d1d9e6, inset -4px -4px 8px #ffffff;
    font-size: 1rem;
    outline: none;
}

/* Buttons */
.neu-button {
    background: green; /* orange */
    color: white;
    padding: 12px;
    border-radius: 12px;
    font-weight: bold;
    border: none;
    box-shadow: 3px 3px 8px #c8d0e7, -3px -3px 8px #ffffff;
    transition: all 0.3s ease;
}

.neu-button:hover {
    background: green; /* darker orange */
}

.neu-button-outline {
    background: transparent;
    border: 2px solid green;
    color: green;
    padding: 10px;
    border-radius: 12px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.neu-button-outline:hover {
    background: green;
    color: white;
}
</style>
