<script setup>
import { router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { route } from 'ziggy-js'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    conversation: Object,
    conversations: Array,
    userRoles: Array,
})

const messageInput = ref('')
const messagesContainer = ref(null)
const fileInput = ref(null)
const selectedFile = ref(null)
const showImageModal = ref(false)
const modalImageUrl = ref('')
const isTyping = ref(false)
const searchTerm = ref('')
const showNewConversationModal = ref(false)
const newConversation = ref({
    subject: '',
    message: '',
})
let pollingInterval = null
let typingTimeout = null

const page = usePage()
const currentUser = computed(() => page.props.auth?.user)

const isAdmin = computed(() => {
    return props.userRoles && props.userRoles.includes('admin')
})

const otherUser = computed(() => {
    return isAdmin.value ? props.conversation.client : props.conversation.admin
})

const filteredConversations = computed(() => {
    if (!props.conversations || !searchTerm.value) return props.conversations

    return props.conversations.filter(conv => {
        const other = isAdmin.value ? conv.client : conv.admin
        const searchLower = searchTerm.value.toLowerCase()

        return (
            other.name?.toLowerCase().includes(searchLower) ||
            conv.subject?.toLowerCase().includes(searchLower) ||
            conv.last_message?.message?.toLowerCase().includes(searchLower)
        )
    })
})

const scrollToBottom = (smooth = true) => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTo({
                top: messagesContainer.value.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto'
            })
        }
    })
}

const sendMessage = () => {
    const message = messageInput.value.trim()

    if (!message && !selectedFile.value) return

    const formData = new FormData()
    if (message) formData.append('message', message)
    if (selectedFile.value) formData.append('file', selectedFile.value)

    router.post(route('conversations.message', props.conversation.id), formData, {
        preserveScroll: true,
        onSuccess: () => {
            messageInput.value = ''
            clearFile()
            scrollToBottom()
        },
        onError: (errors) => {
            console.error('Erreur:', errors)
            alert('Erreur lors de l\'envoi du message')
        }
    })
}

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        if (file.size > 10 * 1024 * 1024) {
            alert('Le fichier ne doit pas dépasser 10 MB')
            return
        }
        selectedFile.value = file
    }
}

const clearFile = () => {
    selectedFile.value = null
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

const handleKeyPress = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault()
        sendMessage()
    }
}

const handleTyping = () => {
    clearTimeout(typingTimeout)

    router.post(route('conversations.typing', props.conversation.id), {
        is_typing: true
    }, {
        preserveState: true,
        preserveScroll: true,
        only: []
    })

    typingTimeout = setTimeout(() => {
        router.post(route('conversations.typing', props.conversation.id), {
            is_typing: false
        }, {
            preserveState: true,
            preserveScroll: true,
            only: []
        })
    }, 3000)
}

const viewImage = (url) => {
    modalImageUrl.value = url
    showImageModal.value = true
}

const closeImageModal = () => {
    showImageModal.value = false
    modalImageUrl.value = ''
}

const closeConversation = () => {
    if (!confirm('Voulez-vous vraiment fermer cette conversation ?')) return

    router.post(route('conversations.close', props.conversation.id), {}, {
        onSuccess: () => {
            router.visit(route('conversations.index'))
        },
        onError: (errors) => {
            console.error('Erreur:', errors)
            alert('Erreur lors de la fermeture de la conversation')
        }
    })
}

const markAsRead = () => {
    router.post(route('conversations.mark-read', props.conversation.id), {}, {
        preserveState: true,
        preserveScroll: true,
        only: []
    })
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

const formatMessageTime = (date) => {
    if (!date) return ''
    const messageDate = new Date(date)
    const today = new Date()

    if (messageDate.toDateString() === today.toDateString()) {
        return messageDate.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    }

    return messageDate.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getFileIcon = (fileType) => {
    if (fileType?.includes('pdf')) {
        return 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'
    }
    return 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'
}

const getOtherUserFromConv = (conv) => {
    // Si je suis admin, afficher le client
    if (isAdmin.value) {
        return conv.client || { name: 'Client', id: null }
    }
    // Si je suis client, afficher l'admin
    return conv.admin || { name: 'Admin', id: null }
}

const debugConversation = computed(() => {
    if (!props.conversations?.[0]) return null
    const conv = props.conversations[0]
    console.log('Debug conversation:', {
        conversationId: conv.id,
        clientId: conv.client_id,
        clientName: conv.client?.name,
        adminId: conv.admin_id,
        adminName: conv.admin?.name,
        currentUserId: currentUser.value?.id,
        isAdmin: isAdmin.value
    })
    return conv
})

const showConversation = (conversation) => {
    router.visit(route('conversations.show', conversation.id))
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

const formatMessageWithLinks = (text) => {
    if (!text) return '';

    // Échapper le HTML pour la sécurité
    let escaped = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    // Remplacer les retours à la ligne par <br>
    escaped = escaped.replace(/\n/g, '<br>');

    // Détecter et rendre cliquables les URLs
    const urlPattern = /(https?:\/\/[^\s<]+)/gi;
    escaped = escaped.replace(urlPattern, (match) => {
        return `<a href="${match}" target="_blank" class="text-blue-500 underline hover:text-blue-700 font-medium">${match}</a>`;
    });

    return escaped;
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

onMounted(() => {
    scrollToBottom(false)
    markAsRead()

    pollingInterval = setInterval(() => {
        router.reload({
            only: ['conversation', 'conversations'],
            preserveState: true,
            preserveScroll: true,
        })
    }, 3000)
})

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval)
    if (typingTimeout) clearTimeout(typingTimeout)
})

watch(() => props.conversation.messages?.length, () => {
    scrollToBottom()
})
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
                <div
                    v-for="conv in filteredConversations"
                    :key="conv.id"
                    @click="showConversation(conv)"
                    class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 transition-colors"
                    :class="{
                        'bg-blue-50': conv.unread_count > 0,
                        'bg-gray-100': conv.id === conversation.id
                    }"
                >
                    <!-- Avatar -->
                    <div class="flex-shrink-0 mr-3">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                            {{ getOtherUserFromConv(conv).name?.charAt(0).toUpperCase() || 'A' }}
                        </div>
                    </div>

                    <!-- Contenu -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">
                                {{ getOtherUserFromConv(conv).name || 'Admin' }}
                            </h3>
                            <span class="text-xs text-gray-500">
                                {{ formatTime(conv.last_message_at) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-600 truncate">
                                <span v-if="conv.last_message?.sender_id === currentUser.id" class="text-gray-500">Vous: </span>
                                {{ conv.last_message?.message || 'Aucun message' }}
                            </p>
                            <span
                                v-if="conv.unread_count > 0"
                                class="ml-2 bg-blue-600 text-white text-xs font-bold rounded-full px-2 py-0.5"
                            >
                                {{ conv.unread_count }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zone de conversation -->
        <div class="flex-1 flex flex-col">
            <!-- Header conversation -->
            <div class="bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <!-- Avatar -->
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                            {{ otherUser.name?.charAt(0).toUpperCase() || 'A' }}
                        </div>

                        <!-- Infos -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                {{ otherUser.name || 'Admin' }}
                            </h2>
                            <p v-if="isTyping" class="text-xs text-green-600">
                                En train d'écrire...
                            </p>
                            <p v-else-if="conversation.subject" class="text-xs text-gray-500">
                                {{ conversation.subject }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions admin -->
                    <div v-if="isAdmin && conversation.status !== 'closed'" class="flex space-x-2">
                        <button
                            @click="closeConversation"
                            class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                            title="Fermer la conversation"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50">
                <div v-if="!conversation.messages || conversation.messages.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <p class="text-gray-500">Aucun message</p>
                    <p class="text-sm text-gray-400 mt-1">Commencez la conversation !</p>
                </div>

                <div
                    v-for="message in conversation.messages"
                    :key="message.id"
                    class="flex"
                    :class="message.sender_id === currentUser.id ? 'justify-end' : 'justify-start'"
                >
                    <div class="max-w-xs lg:max-w-md xl:max-w-lg">
                        <!-- Bulle de message -->
                        <div
                            class="rounded-2xl px-4 py-2 shadow-sm"
                            :class="message.sender_id === currentUser.id
                                ? 'bg-blue-600 text-white rounded-br-none'
                                : 'bg-white text-gray-900 rounded-bl-none'"
                        >
                            <!-- Image -->
                            <div v-if="message.type === 'image'" class="mb-2">
                                <img
                                    :src="message.file_url"
                                    alt="Image"
                                    class="rounded-lg max-w-full cursor-pointer hover:opacity-90 transition-opacity"
                                    @click="viewImage(message.file_url)"
                                />
                            </div>

                            <!-- Fichier -->
                            <a
                                v-else-if="message.type === 'file'"
                                :href="message.file_url"
                                :download="message.file_name"
                                class="flex items-center space-x-3 p-2 rounded-lg hover:bg-black/5 transition-colors"
                            >
                                <svg class="w-10 h-10" :class="message.sender_id === currentUser.id ? 'text-white' : 'text-blue-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getFileIcon(message.file_type)" />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium truncate">{{ message.file_name }}</p>
                                    <p class="text-xs opacity-75">Cliquer pour télécharger</p>
                                </div>
                            </a>

                            <!-- Texte avec liens cliquables -->
                            <p v-if="message.message" class="whitespace-pre-wrap break-words" v-html="formatMessageWithLinks(message.message)"></p>

                            <!-- Heure et statut -->
                            <div class="flex items-center justify-end space-x-1 mt-1">
                                <span class="text-xs opacity-75">
                                    {{ formatMessageTime(message.created_at) }}
                                </span>
                                <svg
                                    v-if="message.sender_id === currentUser.id"
                                    class="w-4 h-4"
                                    :class="message.is_read ? 'text-blue-300' : 'opacity-50'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone de saisie -->
            <div class="bg-white border-t border-gray-200 p-4">
                <!-- Aperçu fichier -->
                <div v-if="selectedFile" class="mb-3 flex items-center space-x-2 bg-blue-50 px-3 py-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <span class="text-sm text-gray-700 flex-1 truncate">{{ selectedFile.name }}</span>
                    <button
                        type="button"
                        @click="clearFile"
                        class="text-red-500 hover:text-red-700"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Formulaire -->
                <form @submit.prevent="sendMessage" class="flex items-end space-x-2">
                    <!-- Bouton fichier -->
                    <div class="relative">
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*,.pdf,.doc,.docx"
                            class="hidden"
                            @change="handleFileSelect"
                        />
                        <button
                            type="button"
                            @click="fileInput.click()"
                            class="p-3 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                        </button>
                    </div>

                    <!-- Zone de texte -->
                    <div class="flex-1 bg-gray-100 rounded-full px-4 py-2 flex items-center">
                        <textarea
                            v-model="messageInput"
                            rows="1"
                            placeholder="Écrivez un message..."
                            class="flex-1 bg-transparent border-0 focus:ring-0 resize-none max-h-32 py-1"
                            @keydown="handleKeyPress"
                            @input="handleTyping"
                        ></textarea>
                    </div>

                    <!-- Bouton envoyer -->
                    <button
                        type="submit"
                        :disabled="!messageInput.trim() && !selectedFile"
                        class="p-3 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </form>
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

        <!-- Modal Image -->
        <div
            v-if="showImageModal"
            class="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4"
            @click="closeImageModal"
        >
            <div class="relative max-w-5xl max-h-[90vh]" @click.stop>
                <button
                    @click="closeImageModal"
                    class="absolute -top-12 right-0 text-white hover:text-gray-300 transition-colors"
                >
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <img
                    :src="modalImageUrl"
                    alt="Image"
                    class="max-w-full max-h-[90vh] rounded-lg"
                />
            </div>
        </div>
    </div>
</template>
