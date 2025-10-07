<script setup>
import { router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { route } from 'ziggy-js'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    conversations: Array,
    userRoles: Array,
})

const searchTerm = ref('')
const showNewConversationModal = ref(false)
const newConversation = ref({
    subject: '',
    message: '',
})

const page = usePage()
const currentUser = computed(() => page.props.auth?.user)

const filteredConversations = computed(() => {
    if (!searchTerm.value) return props.conversations

    return props.conversations.filter(conv => {
        const otherUser = getOtherUser(conv)
        const searchLower = searchTerm.value.toLowerCase()

        return (
            otherUser.name?.toLowerCase().includes(searchLower) ||
            conv.subject?.toLowerCase().includes(searchLower) ||
            conv.last_message?.message?.toLowerCase().includes(searchLower)
        )
    })
})

const isAdmin = computed(() => {
    return props.userRoles && props.userRoles.includes('admin')
})

const getOtherUser = (conversation) => {
    return isAdmin.value ? conversation.client : conversation.admin
}

const formatTime = (date) => {
    if (!date) return ''
    const messageDate = new Date(date)
    const today = new Date()
    const yesterday = new Date(today)
    yesterday.setDate(yesterday.getDate() - 1)

    if (messageDate.toDateString() === today.toDateString()) {
        return messageDate.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    } else if (messageDate.toDateString() === yesterday.toDateString()) {
        return 'Hier'
    } else {
        return messageDate.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
    }
}

const openNewConversationModal = () => {
    showNewConversationModal.value = true
}

const closeNewConversationModal = () => {
    showNewConversationModal.value = false
    newConversation.value = {
        subject: '',
        message: '',
    }
}

const createConversation = () => {
    router.post(route('conversations.store'), newConversation.value, {
        onSuccess: () => {
            closeNewConversationModal()
        },
        onError: (errors) => {
            console.error('Erreur:', errors)
            alert('Erreur lors de la création de la conversation')
        }
    })
}

const showConversation = (conversation) => {
    router.visit(route('conversations.show', conversation.id))
}
</script>

<template>
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar - Liste des conversations -->
        <div class="w-full md:w-96 bg-white border-r border-gray-200 flex flex-col">
            <!-- Header -->
            <div class="px-4 py-5 border-b border-gray-200 bg-white">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-2xl font-bold text-gray-900">Messages</h1>
                    <button
                        @click="openNewConversationModal"
                        class="p-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition-colors"
                        title="Nouvelle conversation"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>

                <!-- Barre de recherche -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="searchTerm"
                        type="text"
                        placeholder="Rechercher..."
                        class="w-full pl-10 pr-4 py-2 bg-gray-100 border-0 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all"
                    />
                </div>
            </div>

            <!-- Liste des conversations -->
            <div class="flex-1 overflow-y-auto">
                <!-- Message si aucune conversation -->
                <div v-if="props.conversations.length === 0" class="flex flex-col items-center justify-center h-full px-4 text-center">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune conversation</h3>
                    <p class="text-sm text-gray-500 mb-4">Commencez une nouvelle conversation</p>
                    <button
                        @click="openNewConversationModal"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        Créer une conversation
                    </button>
                </div>

                <!-- Liste -->
                <div v-else>
                    <div
                        v-for="conversation in filteredConversations"
                        :key="conversation.id"
                        @click="showConversation(conversation)"
                        class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 transition-colors"
                        :class="{ 'bg-blue-50': conversation.unread_count > 0 }"
                    >
                        <!-- Avatar -->
                        <div class="flex-shrink-0 mr-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                                {{ getOtherUser(conversation).name?.charAt(0).toUpperCase() || 'A' }}
                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-sm font-semibold text-gray-900 truncate">
                                    {{ getOtherUser(conversation).name || 'Admin' }}
                                </h3>
                                <span class="text-xs text-gray-500">
                                    {{ formatTime(conversation.last_message_at) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <p class="text-sm text-gray-600 truncate">
                                    <span v-if="conversation.last_message?.sender_id === currentUser.id" class="text-gray-500">Vous: </span>
                                    {{ conversation.last_message?.message || 'Aucun message' }}
                                </p>
                                <span
                                    v-if="conversation.unread_count > 0"
                                    class="ml-2 bg-blue-600 text-white text-xs font-bold rounded-full px-2 py-0.5"
                                >
                                    {{ conversation.unread_count }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Aucun résultat de recherche -->
                    <div v-if="searchTerm && filteredConversations.length === 0" class="flex flex-col items-center justify-center py-12 px-4 text-center">
                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <p class="text-gray-600 mb-4">Aucun résultat pour "{{ searchTerm }}"</p>
                        <button
                            @click="searchTerm = ''"
                            class="text-blue-600 hover:text-blue-700 font-medium"
                        >
                            Effacer la recherche
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zone principale (message d'accueil sur desktop) -->
        <div class="hidden md:flex flex-1 items-center justify-center bg-gray-50">
            <div class="text-center">
                <div class="w-32 h-32 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Sélectionnez une conversation</h2>
                <p class="text-gray-500">Choisissez une conversation pour commencer à discuter</p>
            </div>
        </div>

        <!-- Modal Nouvelle Conversation -->
        <div
            v-if="showNewConversationModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click="closeNewConversationModal"
        >
            <div
                class="bg-white rounded-2xl shadow-2xl max-w-md w-full"
                @click.stop
            >
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Nouvelle conversation</h3>
                        <button
                            @click="closeNewConversationModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Formulaire -->
                    <form @submit.prevent="createConversation">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Sujet (optionnel)
                            </label>
                            <input
                                v-model="newConversation.subject"
                                type="text"
                                placeholder="Ex: Question sur mon compte"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Message <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="newConversation.message"
                                rows="4"
                                required
                                placeholder="Écrivez votre message..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                            ></textarea>
                        </div>

                        <!-- Actions -->
                        <div class="flex space-x-3">
                            <button
                                type="button"
                                @click="closeNewConversationModal"
                                class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
                            >
                                Créer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
