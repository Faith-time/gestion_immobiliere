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
            <!-- Section gauche avec branding -->
            <div class="auth-left">
                <div class="logo-wrapper">
                    <div class="logo-container">
                        <img src="/resources/js/assets/images/cauris_immo_logo.jpg" alt="Cauris Immo" class="company-logo" />
                    </div>
                </div>

                <h1 class="brand-title">Cauris Immo</h1>
                <p class="brand-subtitle">Plateforme de gestion immobilière</p>

                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <div class="feature-text">
                            <h4>Gestion complète</h4>
                            <p>Maisons, appartements, terrains & studios</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="feature-text">
                            <h4>Espaces dédiés</h4>
                            <p>Propriétaires et clients</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        <div class="feature-text">
                            <h4>Sécurisé</h4>
                            <p>Vos données protégées</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section droite avec formulaire -->
            <div class="auth-right">
                <div class="form-header">
                    <h2 class="form-title">Créer un compte</h2>
                    <p class="form-subtitle">Rejoignez notre plateforme immobilière</p>
                </div>

                <form @submit.prevent="submit" class="auth-form">
                    <div class="form-group">
                        <label class="form-label">Nom complet</label>
                        <div class="input-wrapper">
                            <input
                                v-model="form.name"
                                type="text"
                                class="neu-input"
                                :class="{ 'input-error': form.errors.name }"
                                placeholder="Jean Dupont"
                                required
                            />
                        </div>
                        <div v-if="form.errors.name" class="error-message">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Adresse email</label>
                        <div class="input-wrapper">
                            <input
                                v-model="form.email"
                                type="email"
                                class="neu-input"
                                :class="{ 'input-error': form.errors.email }"
                                placeholder="exemple@email.com"
                                required
                            />
                        </div>
                        <div v-if="form.errors.email" class="error-message">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Mot de passe</label>
                            <div class="input-wrapper">
                                <input
                                    v-model="form.password"
                                    type="password"
                                    class="neu-input"
                                    :class="{ 'input-error': form.errors.password }"
                                    placeholder="••••••••"
                                    required
                                />
                            </div>
                            <div v-if="form.errors.password" class="error-message">
                                {{ form.errors.password }}
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirmation</label>
                            <div class="input-wrapper">
                                <input
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="neu-input"
                                    :class="{ 'input-error': form.errors.password_confirmation }"
                                    placeholder="••••••••"
                                    required
                                />
                            </div>
                            <div v-if="form.errors.password_confirmation" class="error-message">
                                {{ form.errors.password_confirmation }}
                            </div>
                        </div>
                    </div>

                    <div class="terms-agreement">
                        <label class="checkbox-label">
                            <input type="checkbox" class="neu-checkbox" required />
                            <span class="checkbox-text">
                                J'accepte les <a href="#" class="link-text">conditions d'utilisation</a>
                                et la <a href="#" class="link-text">politique de confidentialité</a>
                            </span>
                        </label>
                    </div>

                    <div class="button-group">
                        <button
                            type="submit"
                            class="neu-button neu-button-primary"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="spinner"></span>
                            <span v-else>Créer mon compte</span>
                        </button>

                        <button type="button" class="neu-button neu-button-secondary" @click="signin">
                            J'ai déjà un compte
                        </button>
                    </div>
                </form>

                <div class="user-info">
                    <div class="info-badges">
                        <span class="info-badge">Espace Propriétaires</span>
                        <span class="info-badge">Espace Clients</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.auth-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: #e0e5ec;
    padding: 2rem;
}

.register-card {
    display: flex;
    max-width: 1200px;
    width: 100%;
    min-height: 700px;
    border-radius: 30px;
    overflow: hidden;
    background: #e0e5ec;
    box-shadow: 20px 20px 60px #bec3c9, -20px -20px 60px #ffffff;
}

/* Section gauche - Branding neumorphique */
.auth-left {
    flex: 1;
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    padding: 3rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    position: relative;
}

.auth-left::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.3;
}

.logo-wrapper {
    margin-bottom: 2rem;
}

.logo-container {
    width: 200px;
    height: 200px;
    border-radius: 30px;
    background: white;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.company-logo {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.brand-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.5rem;
    letter-spacing: -0.5px;
    position: relative;
    z-index: 1;
}

.brand-subtitle {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 3rem;
    font-weight: 500;
    position: relative;
    z-index: 1;
}

.features-list {
    width: 100%;
    max-width: 380px;
    position: relative;
    z-index: 1;
}

.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 1.25rem;
    padding: 1.5rem;
    margin-bottom: 1.25rem;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.feature-item:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateX(5px);
}

.feature-icon {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.feature-icon svg {
    width: 24px;
    height: 24px;
}

.feature-text h4 {
    font-size: 1rem;
    font-weight: 600;
    color: white;
    margin-bottom: 0.25rem;
}

.feature-text p {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.4;
}

/* Section droite - Formulaire neumorphique */
.auth-right {
    flex: 1;
    background: #e0e5ec;
    padding: 3rem 2.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    overflow-y: auto;
}

.form-header {
    margin-bottom: 2.5rem;
    text-align: center;
}

.form-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 0.5rem;
}

.form-subtitle {
    font-size: 0.95rem;
    color: #718096;
}

.auth-form {
    width: 100%;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.form-label {
    display: block;
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
}

.input-wrapper {
    position: relative;
}

.neu-input {
    width: 100%;
    padding: 1rem 1.25rem;
    border: none;
    border-radius: 15px;
    font-size: 1rem;
    color: #2d3748;
    background: #e0e5ec;
    box-shadow: inset 6px 6px 12px #bec3c9, inset -6px -6px 12px #ffffff;
    transition: all 0.3s ease;
    outline: none;
}

.neu-input:focus {
    box-shadow: inset 8px 8px 16px #bec3c9, inset -8px -8px 16px #ffffff;
}

.neu-input::placeholder {
    color: #a0aec0;
}

.input-error {
    box-shadow: inset 6px 6px 12px #d9a5a5, inset -6px -6px 12px #ffffff;
}

.error-message {
    color: #e53e3e;
    font-size: 0.85rem;
    margin-top: 0.5rem;
    font-weight: 500;
}

.terms-agreement {
    margin-bottom: 1.75rem;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
}

.neu-checkbox {
    margin-top: 0.2rem;
    cursor: pointer;
    width: 18px;
    height: 18px;
}

.checkbox-text {
    font-size: 0.875rem;
    color: #718096;
    line-height: 1.5;
}

.link-text {
    color: #059669;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.link-text:hover {
    color: #047857;
}

.button-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.neu-button {
    width: 100%;
    padding: 1rem;
    border: none;
    border-radius: 15px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.neu-button-primary {
    background: linear-gradient(145deg, #059669, #047857);
    color: white;
    box-shadow: 8px 8px 16px #bec3c9, -8px -8px 16px #ffffff;
}

.neu-button-primary:hover:not(:disabled) {
    box-shadow: 12px 12px 24px #bec3c9, -12px -12px 24px #ffffff;
    transform: translateY(-2px);
}

.neu-button-primary:active:not(:disabled) {
    box-shadow: inset 4px 4px 8px #047857, inset -4px -4px 8px #059669;
    transform: translateY(0);
}

.neu-button-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.neu-button-secondary {
    background: #e0e5ec;
    color: #4a5568;
    box-shadow: 8px 8px 16px #bec3c9, -8px -8px 16px #ffffff;
}

.neu-button-secondary:hover {
    box-shadow: 12px 12px 24px #bec3c9, -12px -12px 24px #ffffff;
    transform: translateY(-2px);
}

.neu-button-secondary:active {
    box-shadow: inset 4px 4px 8px #bec3c9, inset -4px -4px 8px #ffffff;
    transform: translateY(0);
}

.spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.user-info {
    margin-top: 2.5rem;
    padding-top: 2rem;
    border-top: 1px solid #cbd5e0;
}

.info-badges {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.info-badge {
    padding: 0.625rem 1.25rem;
    background: #e0e5ec;
    color: #4a5568;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: 6px 6px 12px #bec3c9, -6px -6px 12px #ffffff;
}

/* Responsive */
@media (max-width: 968px) {
    .register-card {
        flex-direction: column;
    }

    .auth-left {
        padding: 2rem;
    }

    .brand-title {
        font-size: 2rem;
    }

    .features-list {
        max-width: 100%;
    }

    .auth-right {
        padding: 2rem 1.5rem;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
