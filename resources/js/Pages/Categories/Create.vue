<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref } from 'vue'

const categories = [
    { value: 'appartements', label: 'Appartements', icon: '🏢', color: '#3b82f6' },
    { value: 'maisons', label: 'Maisons', icon: '🏠', color: '#10b981' },
    { value: 'studio', label: 'Studio', icon: '🏘️', color: '#8b5cf6' },
    { value: 'magasin', label: 'Magasin', icon: '🏪', color: '#f59e0b' },
    { value: 'terrain', label: 'Terrain', icon: '🌍', color: '#06b6d4' },
    { value: 'construction', label: 'Construction', icon: '🏗️', color: '#ef4444' }
]

const form = useForm({
    name: ''
})

const selectedCategory = ref(null)

const selectCategory = (cat) => {
    form.name = cat.value
    selectedCategory.value = cat
}

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
    <div class="modern-container">
        <div class="form-wrapper">
            <!-- Header avec gradient -->
            <div class="form-header">
                <div class="header-content">
                    <div class="icon-wrapper">
                        <span class="header-icon">✨</span>
                    </div>
                    <h2 class="form-title">Nouvelle Catégorie</h2>
                    <p class="form-subtitle">Sélectionnez le type de bien immobilier</p>
                </div>
            </div>

            <div class="form-body">
                <form @submit.prevent="submit">
                    <!-- Grille de catégories -->
                    <div class="categories-grid">
                        <div
                            v-for="cat in categories"
                            :key="cat.value"
                            class="category-item"
                            :class="{ 'selected': form.name === cat.value }"
                            :style="{ '--category-color': cat.color }"
                            @click="selectCategory(cat)"
                        >
                            <div class="category-icon">{{ cat.icon }}</div>
                            <div class="category-label">{{ cat.label }}</div>
                            <div class="category-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Message d'erreur -->
                    <div v-if="form.errors.name" class="error-message">
                        <svg class="error-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        {{ form.errors.name }}
                    </div>

                    <!-- Info Construction -->
                    <transition name="slide-fade">
                        <div v-if="form.name === 'construction'" class="construction-info">
                            <div class="info-header">
                                <span class="info-icon">💡</span>
                                <h4>Services de Construction</h4>
                            </div>
                            <ul class="info-list">
                                <li>
                                    <span class="check-icon">✓</span>
                                    Suivi de la construction jusqu'à l'achèvement
                                </li>
                                <li>
                                    <span class="check-icon">✓</span>
                                    Gestion de la location sur demande
                                </li>
                            </ul>
                        </div>
                    </transition>

                    <!-- Boutons d'action -->
                    <div class="action-buttons">
                        <button
                            type="button"
                            @click="cancel"
                            class="btn-cancel"
                            :disabled="form.processing"
                        >
                            <span>Annuler</span>
                        </button>
                        <button
                            type="submit"
                            class="btn-submit"
                            :disabled="form.processing || !form.name"
                        >
                            <span v-if="form.processing" class="spinner"></span>
                            <span v-else>Enregistrer</span>
                            <svg class="arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modern-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-wrapper {
    width: 100%;
    max-width: 900px;
    background: white;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 3rem 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.form-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.header-content {
    position: relative;
    z-index: 1;
}

.icon-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    margin-bottom: 1rem;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.header-icon {
    font-size: 2.5rem;
}

.form-title {
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    letter-spacing: -0.5px;
}

.form-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    margin: 0;
}

.form-body {
    padding: 3rem;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.category-item {
    position: relative;
    background: #f8fafc;
    border: 3px solid #e2e8f0;
    border-radius: 16px;
    padding: 2rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.category-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--category-color);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.category-item:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    border-color: var(--category-color);
}

.category-item:hover::before {
    transform: scaleX(1);
}

.category-item.selected {
    background: linear-gradient(135deg, var(--category-color) 0%, var(--category-color) 100%);
    border-color: var(--category-color);
    color: white;
    transform: scale(1.05);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.category-item.selected::before {
    transform: scaleX(1);
    height: 100%;
    opacity: 0.1;
}

.category-icon {
    font-size: 3rem;
    margin-bottom: 0.75rem;
    transition: transform 0.3s ease;
}

.category-item:hover .category-icon {
    transform: scale(1.2) rotate(5deg);
}

.category-item.selected .category-icon {
    transform: scale(1.1);
}

.category-label {
    font-weight: 600;
    font-size: 1rem;
    transition: color 0.3s ease;
}

.category-item.selected .category-label {
    color: white;
}

.category-check {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 28px;
    height: 28px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
}

.category-item.selected .category-check {
    opacity: 1;
    transform: scale(1);
}

.category-check svg {
    width: 18px;
    height: 18px;
    color: var(--category-color);
}

.error-message {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: #fee;
    color: #c00;
    padding: 1rem 1.25rem;
    border-radius: 12px;
    border-left: 4px solid #c00;
    margin-bottom: 1.5rem;
    animation: shake 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.error-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.construction-info {
    background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 2px solid #38bdf8;
}

.info-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.info-icon {
    font-size: 1.75rem;
}

.info-header h4 {
    margin: 0;
    color: #0369a1;
    font-size: 1.25rem;
    font-weight: 600;
}

.info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-list li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    color: #0c4a6e;
    font-size: 1rem;
}

.check-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: #38bdf8;
    color: white;
    border-radius: 50%;
    font-weight: bold;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn-cancel,
.btn-submit {
    flex: 1;
    padding: 1rem 2rem;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-cancel {
    background: #f1f5f9;
    color: #475569;
}

.btn-cancel:hover:not(:disabled) {
    background: #e2e8f0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    position: relative;
    overflow: hidden;
}

.btn-submit::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}

.btn-submit:hover:not(:disabled)::before {
    left: 100%;
}

.btn-submit:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
}

.btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.arrow-icon {
    width: 20px;
    height: 20px;
    transition: transform 0.3s ease;
}

.btn-submit:hover:not(:disabled) .arrow-icon {
    transform: translateX(4px);
}

.spinner {
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.slide-fade-enter-active {
    transition: all 0.3s ease;
}

.slide-fade-leave-active {
    transition: all 0.2s ease;
}

.slide-fade-enter-from {
    transform: translateY(-10px);
    opacity: 0;
}

.slide-fade-leave-to {
    transform: translateY(-10px);
    opacity: 0;
}

@media (max-width: 768px) {
    .modern-container {
        padding: 1rem;
    }

    .form-header {
        padding: 2rem 1.5rem;
    }

    .form-title {
        font-size: 2rem;
    }

    .form-body {
        padding: 2rem 1.5rem;
    }

    .categories-grid {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
    }

    .action-buttons {
        flex-direction: column;
    }
}
</style>
