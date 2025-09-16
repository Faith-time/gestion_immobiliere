<script setup>
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

defineProps({
    users: Array
})

const deleteUser = (user) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        router.delete(route('users.destroy', user.id))
    }
}

const createUser = () => {
    router.visit(route('users.create'))
}

const editUser = (user) => {
    router.visit(route('users.edit', user.id))
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

            <center>
            <div class="card-header bg-light text-black d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Liste des utilisateurs</h4>
            </div>
            </center>

            <button @click="createUser" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>
                + Nouvel Utilisateur
            </button>

            <div class="card-body">
                <div v-if="users.length === 0" class="text-center py-4">
                    <p class="text-muted mb-3">Aucun utilisateur trouvé</p>
                    <button @click="createUser" class="btn btn-primary">
                        Créer le premier utilisateur
                    </button>
                </div>

                <div v-else class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th class="text-center">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="user in users" :key="user.id">
                            <td>{{ user.name }}</td>
                            <td>{{ user.email }}</td>
                            <td class="text-center">
                                    <button
                                        @click="editUser(user)"
                                        class="btn btn-outline-primary btn-sm"
                                        title="Modifier"
                                    >
                                        <i class="fas fa-edit"></i>Modifier
                                    </button>
                                     <button
                                        @click="deleteUser(user)"
                                        class="btn btn-outline-danger btn-sm"
                                        title="Supprimer"
                                    >
                                    <i class="fas fa-trash"></i>Supprimer
                                    </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
