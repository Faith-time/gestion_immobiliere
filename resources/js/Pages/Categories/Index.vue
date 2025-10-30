<script setup>
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps({
    categories: Array
})

const categoryIcons = {
    'appartements': { icon: '🏢', color: '#3b82f6', gradient: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)' },
    'maisons': { icon: '🏠', color: '#10b981', gradient: 'linear-gradient(135deg, #10b981 0%, #059669 100%)' },
    'studio': { icon: '🏘️', color: '#8b5cf6', gradient: 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)' },
    'magasin': { icon: '🏪', color: '#f59e0b', gradient: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)' },
    'terrain': { icon: '🌍', color: '#06b6d4', gradient: 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)' },
    'construction': { icon: '🏗️', color: '#ef4444', gradient: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' }
}

const getCategoryStyle = (categoryName) => {
    return categoryIcons[categoryName] || { icon: '📦', color: '#6366f1', gradient: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)' }
}

const createCategory = () => {
    router.visit(route('categories.create'))
}

const editCategory = (category) => {
    router.visit(route('categories.edit', category.id))
}

const deleteCategory = (category) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer la catégorie "${category.name}" ?`)) {
        router.delete(route('categories.destroy', category.id))
    }
}
</script>

<script>
import Layout from '@/Pages/Layout.vue'

export default {
    layout: Layout
}
</script>

<template>
    <div class="modern-index-container">
        <!-- Header Section -->
        <div class="page-header">
            <div class="header-content">
                <div class="title-section">
                    <div class="icon-badge">
                        <span class="badge-icon">🏘️</span>
                    </div>
                    <div>
                        <h1 class="page-title">Catégories de Biens</h1>
                        <p class="page-subtitle">Gérez vos différents types de propriétés</p>
                    </div>
                </div>
                <button @click="createCategory" class="btn-add-new">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Nouvelle Catégorie</span>
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="categories.length === 0" class="empty-state">
            <div class="empty-content">
                <div class="empty-icon">📋</div>
                <h3 class="empty-title">Aucune catégorie</h3>
                <p class="empty-text">Commencez par créer votre première catégorie de bien immobilier</p>
                <button @click="createCategory" class="btn-empty-action">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Créer la première catégorie</span>
                </button>
            </div>
        </div>

        <!-- Categories Grid -->
        <div v-else class="categories-grid">
            <div
                v-for="(category, index) in categories"
                :key="category.id"
                class="category-card"
                :style="{ animationDelay: `${index * 0.1}s` }"
            >
                <!-- Card Header with Icon -->
                <div class="card-header-custom" :style="{ background: getCategoryStyle(category.name).gradient }">
                    <div class="header-icon-wrapper">
                        <span class="header-icon">{{ getCategoryStyle(category.name).icon }}</span>
                    </div>
                    <div class="card-number">#{{ index + 1 }}</div>
                </div>

                <!-- Card Body -->
                <div class="card-body-custom">
                    <h3 class="category-name">{{ category.name }}</h3>
                    <div class="category-meta">
                        <div class="meta-item">
                            <svg class="meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="9" y1="9" x2="15" y2="9"></line>
                                <line x1="9" y1="15" x2="15" y2="15"></line>
                            </svg>
                            <span>Type de bien</span>
                        </div>
                    </div>
                </div>

                <!-- Card Footer with Actions -->
                <div class="card-footer-custom">
                    <button
                        @click="editCategory(category)"
                        class="action-btn btn-edit"
                        title="Modifier"
                    >
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        <span>Modifier</span>
                    </button>
                    <button
                        @click="deleteCategory(category)"
                        class="action-btn btn-delete"
                        title="Supprimer"
                    >
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <span>Supprimer</span>
                    </button>
                </div>

                <!-- Decorative Element -->
                <div class="card-decoration" :style="{ background: getCategoryStyle(category.name).gradient }"></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modern-index-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
}

/* Page Header */
.page-header {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    animation: slideDown 0.6s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.title-section {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.icon-badge {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.badge-icon {
    font-size: 2.5rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    letter-spacing: -0.5px;
}

.page-subtitle {
    color: #64748b;
    margin: 0.25rem 0 0 0;
    font-size: 1rem;
}

.btn-add-new {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-add-new:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
}

.btn-add-new .icon {
    width: 20px;
    height: 20px;
}

/* Empty State */
.empty-state {
    background: white;
    border-radius: 20px;
    padding: 4rem 2rem;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.empty-content {
    max-width: 400px;
    margin: 0 auto;
}

.empty-icon {
    font-size: 5rem;
    margin-bottom: 1.5rem;
    animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

.empty-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.75rem 0;
}

.empty-text {
    color: #64748b;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.btn-empty-action {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-empty-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
}

.btn-empty-action .icon {
    width: 22px;
    height: 22px;
}

/* Categories Grid */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Category Card */
.category-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    animation: slideUp 0.6s ease-out both;
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

.category-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
}

.card-header-custom {
    padding: 2rem;
    position: relative;
    overflow: hidden;
}

.card-header-custom::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

.header-icon-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    margin-bottom: 1rem;
    transition: transform 0.3s ease;
}

.category-card:hover .header-icon-wrapper {
    transform: scale(1.1) rotate(5deg);
}

.header-icon {
    font-size: 3rem;
}

.card-number {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.875rem;
}

.card-body-custom {
    padding: 1.5rem 2rem;
}

.category-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 1rem 0;
    text-transform: capitalize;
}

.category-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #64748b;
    font-size: 0.875rem;
}

.meta-icon {
    width: 18px;
    height: 18px;
}

.card-footer-custom {
    padding: 1.5rem 2rem;
    background: #f8fafc;
    display: flex;
    gap: 1rem;
    border-top: 1px solid #e2e8f0;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1rem;
    border: 2px solid;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.btn-icon {
    width: 18px;
    height: 18px;
}

.btn-edit {
    border-color: #3b82f6;
    color: #3b82f6;
}

.btn-edit:hover {
    background: #3b82f6;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-delete {
    border-color: #ef4444;
    color: #ef4444;
}

.btn-delete:hover {
    background: #ef4444;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.card-decoration {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.category-card:hover .card-decoration {
    opacity: 1;
}

/* Responsive */
@media (max-width: 768px) {
    .modern-index-container {
        padding: 1rem;
    }

    .page-header {
        padding: 1.5rem;
    }

    .header-content {
        flex-direction: column;
        align-items: stretch;
    }

    .title-section {
        flex-direction: column;
        text-align: center;
    }

    .icon-badge {
        margin: 0 auto;
    }

    .page-title {
        font-size: 1.5rem;
    }

    .btn-add-new {
        width: 100%;
        justify-content: center;
    }

    .categories-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .card-footer-custom {
        flex-direction: column;
    }
}
</style>
