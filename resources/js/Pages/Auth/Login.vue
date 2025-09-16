<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AuthLayout from "@/Layout/AuthLayout.vue";

defineOptions({
    layout: AuthLayout
})

const form = useForm({
    email: '',
    password: '',
})

const submit = () => {
    form.post(route('auth.login'), {
        onError: () => 'Erreur de connexion'
    })
}

const signup = () => {
    router.visit(route('register'))
}
</script>

<template>
    <div class="auth-container">
        <div class="login-card">
            <!-- Section gauche bleue -->
            <div class="auth-left">
                <h2 class="auth-title">Bienvenue !</h2>
                <p class="auth-desc">
                    Connectez-vous à votre espace pour gérer vos biens immobiliers avec efficacité.
                </p>
            </div>

            <!-- Section droite blanche -->
            <div class="auth-right">
                <h3 class="auth-subtitle">Se connecter</h3>
                <form @submit.prevent="submit">
                    <div class="form-group">
                        <label>Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="neu-input"
                            :class="{ 'is-invalid': form.errors.email }"
                            required
                        />
                        <div v-if="form.errors.email" class="text-danger text-sm mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div class="form-group mt-4">
                        <label>Mot de passe</label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="neu-input"
                            :class="{ 'is-invalid': form.errors.password }"
                            required
                        />
                        <div v-if="form.errors.password" class="text-danger text-sm mt-1">{{ form.errors.password }}</div>
                    </div>

                    <div class="d-grid gap-3 mt-5">
                        <button
                            type="submit"
                            class="neu-button"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            Se connecter
                        </button>
                        <button type="button" class="neu-button-outline" @click="signup">
                            Pas encore de compte ? Créez-en un
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
    background: #ecf0f3;
    padding: 2rem;
}

.login-card {
    display: flex;
    max-width: 1000px;
    width: 100%;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 12px 12px 24px #c8d0e7, -12px -12px 24px #ffffff;
}

/* Left (bleu moderne) */
.auth-left {
    flex: 1;
    background: #3b82f6; /* Bleu moderne (tailwind blue-500) */
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

/* Right (blanc) */
.auth-right {
    flex: 1;
    background: #ffffff;
    padding: 3rem;
}

.auth-subtitle {
    font-size: 1.5rem;
    color: #111;
    font-weight: 600;
    margin-bottom: 2rem;
    text-align: center;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: inline-block;
}

.neu-input {
    width: 100%;
    padding: 12px 16px;
    border-radius: 10px;
    background: #f0f0f3;
    border: none;
    box-shadow: inset 3px 3px 6px #d1d9e6, inset -3px -3px 6px #ffffff;
    font-size: 1rem;
    outline: none;
}

/* Boutons */
.neu-button {
    background: #3b82f6;
    color: white;
    padding: 12px;
    border-radius: 10px;
    font-weight: bold;
    border: none;
    box-shadow: 3px 3px 8px #b1b5c4, -3px -3px 8px #ffffff;
    transition: 0.3s ease;
}

.neu-button:hover {
    background: #2563eb;
}

.neu-button-outline {
    background: transparent;
    border: 2px solid #3b82f6;
    color: #3b82f6;
    padding: 10px;
    border-radius: 10px;
    font-weight: bold;
    transition: 0.3s ease;
}

.neu-button-outline:hover {
    background: #3b82f6;
    color: white;
}
</style>
