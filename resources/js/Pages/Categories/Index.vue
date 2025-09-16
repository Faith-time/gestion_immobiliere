<script setup>
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps({
    categories: Array
})

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
    <div class="container my-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Liste des catégories</h4>
                <button @click="createCategory" class="btn btn-light btn-sm">
                    <i class="fas fa-plus me-1"></i>
                    Ajouter une catégorie
                </button>
            </div>
            <div class="card-body">
                <div v-if="categories.length === 0" class="text-center py-4">
                    <p class="text-muted mb-3">Aucune catégorie trouvée</p>
                    <button @click="createCategory" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Créer votre première catégorie
                    </button>
                </div>

                <div v-else class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Nom</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(category, index) in categories" :key="category.id">
                            <td>{{ index + 1 }}</td>
                            <td>
                                <strong>{{ category.name }}</strong>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <button
                                        @click="editCategory(category)"
                                        class="btn btn-outline-primary btn-sm"
                                        title="Modifier"
                                    >
                                        <i class="fas fa-edit"></i>Modifier
                                    </button>
                                    <button
                                        @click="deleteCategory(category)"
                                        class="btn btn-outline-danger btn-sm"
                                        title="Supprimer"
                                    >
                                        <i class="fas fa-trash"></i>Supprimer
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.btn-group .btn {
    margin: 0 2px;
}

.table td {
    vertical-align: middle;
}

.card-header .btn {
    border: none;
}

.card-header .btn:hover {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
}
</style>
