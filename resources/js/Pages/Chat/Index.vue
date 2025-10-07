<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Assistant IA</h1>
                <p class="text-gray-600">Posez vos questions sur nos biens immobiliers</p>
            </div>

            <!-- Zone principale -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Liste des chats -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-gray-800">Mes Conversations</h2>
                            <button
                                @click="startNewChat"
                                class="bg-gradient-to-r from-blue-600 to-green-600 hover:from-blue-700 hover:to-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 shadow-md hover:shadow-lg flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Nouveau
                            </button>
                        </div>

                        <div class="space-y-3 max-h-[600px] overflow-y-auto">
                            <div
                                v-for="chat in chatsList"
                                :key="chat.id"
                                @click="loadChat(chat.id)"
                                class="p-4 rounded-xl cursor-pointer transition-all duration-200 group hover:shadow-md relative"
                                :class="
                  currentChatId === chat.id
                    ? 'bg-gradient-to-r from-blue-50 to-green-50 border-2 border-blue-500'
                    : 'bg-gray-50 hover:bg-gray-100 border-2 border-transparent'
                "
                            >
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-semibold text-gray-800 truncate flex-1 group-hover:text-blue-600 transition-colors pr-2">
                                        {{ chat.title }}
                                    </h3>
                                    <div class="flex items-center gap-2">
                                        <!-- Bouton modifier -->
                                        <button
                                            @click.stop="editChat(chat)"
                                            class="opacity-0 group-hover:opacity-100 text-blue-500 hover:text-blue-700 transition-all"
                                            title="Modifier"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <!-- Bouton supprimer -->
                                        <button
                                            @click.stop="deleteChat(chat.id)"
                                            class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 transition-all"
                                            title="Supprimer"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500 truncate">{{ chat.preview }}</p>
                                <p class="text-xs text-gray-400 mt-2">{{ chat.updated_at }}</p>
                            </div>

                            <div v-if="!chatsList || chatsList.length === 0" class="text-center py-12">
                                <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="text-gray-400">Aucune conversation</p>
                                <p class="text-sm text-gray-400 mt-1">Commencez une nouvelle conversation</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zone de chat -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 h-[700px] flex flex-col overflow-hidden">
                        <!-- Header du chat -->
                        <div class="bg-gradient-to-r from-blue-600 to-green-600 text-white p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="relative">
                                        <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <span class="absolute bottom-0 right-0 w-4 h-4 bg-green-400 border-2 border-white rounded-full"></span>
                                    </div>
                                    <div>
                                        <h2 class="text-2xl font-bold">Assistant IA</h2>
                                        <p class="text-sm text-white/80">Toujours disponible pour vous aider</p>
                                    </div>
                                </div>

                                <!-- Bouton voir tous les chats (mobile) -->
                                <button
                                    @click="showChatsList"
                                    class="lg:hidden bg-white/20 hover:bg-white/30 p-2 rounded-lg transition-colors"
                                    title="Voir tous les chats"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div
                            ref="messagesContainer"
                            class="flex-1 overflow-y-auto p-6 space-y-6 bg-gradient-to-b from-gray-50 to-white"
                        >
                            <div v-if="messages.length === 0" class="h-full flex items-center justify-center">
                                <div class="text-center max-w-md">
                                    <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-blue-100 to-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-2xl font-bold text-gray-800 mb-3">Bonjour !</h3>
                                    <p class="text-gray-600 mb-6">Je suis votre assistant virtuel. Posez-moi des questions sur nos biens immobiliers, les locations, les visites, et bien plus encore !</p>

                                    <div class="grid grid-cols-2 gap-3 mt-6">
                                        <button
                                            @click="quickMessage('Quels biens sont disponibles ?')"
                                            class="p-3 bg-blue-50 hover:bg-blue-100 rounded-lg text-left text-sm text-gray-700 transition-colors"
                                        >
                                            <svg class="w-5 h-5 mb-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                            Biens disponibles
                                        </button>
                                        <button
                                            @click="quickMessage('Comment organiser une visite ?')"
                                            class="p-3 bg-green-50 hover:bg-green-100 rounded-lg text-left text-sm text-gray-700 transition-colors"
                                        >
                                            <svg class="w-5 h-5 mb-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            Organiser une visite
                                        </button>
                                        <button
                                            @click="quickMessage('Quels sont les prix de location ?')"
                                            class="p-3 bg-green-50 hover:bg-green-100 rounded-lg text-left text-sm text-gray-700 transition-colors"
                                        >
                                            <svg class="w-5 h-5 mb-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Prix de location
                                        </button>
                                        <button
                                            @click="quickMessage('Documents nécessaires pour louer')"
                                            class="p-3 bg-yellow-50 hover:bg-yellow-100 rounded-lg text-left text-sm text-gray-700 transition-colors"
                                        >
                                            <svg class="w-5 h-5 mb-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Documents requis
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <TransitionGroup name="message" tag="div" class="space-y-6">
                                <div
                                    v-for="message in messages"
                                    :key="message.id"
                                    class="flex"
                                    :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                                >
                                    <div
                                        class="max-w-[75%] rounded-2xl px-5 py-3 shadow-md"
                                        :class="
                      message.role === 'user'
                        ? 'bg-gradient-to-r from-blue-600 to-green-600 text-white rounded-br-none'
                        : 'bg-white text-gray-800 rounded-bl-none border border-gray-200'
                    "
                                    >
                                        <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ message.content }}</p>
                                        <p
                                            class="text-xs mt-2"
                                            :class="message.role === 'user' ? 'text-white/70' : 'text-gray-400'"
                                        >
                                            {{ message.created_at }}
                                        </p>
                                    </div>
                                </div>
                            </TransitionGroup>

                            <div v-if="isTyping" class="flex justify-start">
                                <div class="bg-white rounded-2xl rounded-bl-none px-5 py-4 shadow-md border border-gray-200">
                                    <div class="flex space-x-2">
                                        <div class="w-2.5 h-2.5 bg-gray-400 rounded-full animate-bounce"></div>
                                        <div class="w-2.5 h-2.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                        <div class="w-2.5 h-2.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Zone de saisie -->
                        <div class="p-6 bg-gray-50 border-t border-gray-100">
                            <form @submit.prevent="sendMessage" class="flex items-end space-x-4">
                                <div class="flex-1 relative">
                  <textarea
                      v-model="newMessage"
                      @keydown.enter.exact.prevent="sendMessage"
                      placeholder="Écrivez votre message ici..."
                      rows="1"
                      class="w-full px-5 py-4 pr-14 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 resize-none transition-all text-gray-800"
                      :disabled="isSending"
                  ></textarea>
                                    <button
                                        type="button"
                                        class="absolute right-4 bottom-4 text-gray-400 hover:text-gray-600 transition-colors"
                                        title="Emoji"
                                    >
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="!newMessage.trim() || isSending"
                                    class="bg-gradient-to-r from-blue-600 to-green-600 hover:from-blue-700 hover:to-green-700 text-white p-4 rounded-xl shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 transform hover:scale-105"
                                >
                                    <svg v-if="!isSending" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    <svg v-else class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </form>
                            <p class="text-xs text-gray-500 mt-3 text-center">
                                Appuyez sur <kbd class="px-2 py-1 bg-gray-200 rounded text-xs">Entrée</kbd> pour envoyer votre message
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal pour éditer le titre du chat -->
        <div v-if="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showEditModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Modifier le titre</h3>
                <input
                    v-model="editingTitle"
                    type="text"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                    placeholder="Nouveau titre"
                    @keydown.enter="saveEditChat"
                />
                <div class="flex gap-3 mt-6">
                    <button
                        @click="showEditModal = false"
                        class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        @click="saveEditChat"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-green-600 hover:from-blue-700 hover:to-green-700 text-white rounded-lg font-medium transition-all"
                    >
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    chats: {
        type: Array,
        default: () => []
    }
});

const messages = ref([]);
const chatsList = ref([]);
const newMessage = ref('');
const currentChatId = ref(null);
const isSending = ref(false);
const isTyping = ref(false);
const messagesContainer = ref(null);
const showEditModal = ref(false);
const editingChat = ref(null);
const editingTitle = ref('');

const loadChats = async () => {
    try {
        const response = await axios.get('/chat');
        chatsList.value = response.data.chats;
    } catch (error) {
        console.error('Erreur lors du chargement des chats:', error);
    }
};

const loadChat = async (chatId) => {
    try {
        const response = await axios.get(`/chat/${chatId}`);
        messages.value = response.data.messages;
        currentChatId.value = chatId;
        await nextTick();
        scrollToBottom();
    } catch (error) {
        console.error('Erreur lors du chargement du chat:', error);
    }
};

const startNewChat = () => {
    messages.value = [];
    currentChatId.value = null;
};

const quickMessage = (text) => {
    newMessage.value = text;
    sendMessage();
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || isSending.value) return;

    const messageContent = newMessage.value;
    newMessage.value = '';
    isSending.value = true;

    try {
        let response;

        if (currentChatId.value) {
            response = await axios.post(`/chat/${currentChatId.value}/message`, {
                message: messageContent,
            });
            messages.value.push(...response.data.messages);
        } else {
            response = await axios.post('/chat', {
                message: messageContent,
            });
            currentChatId.value = response.data.chat.id;
            messages.value.push(...response.data.messages);
            await loadChats();
        }

        await nextTick();
        scrollToBottom();
    } catch (error) {
        console.error('Erreur lors de l\'envoi du message:', error);
        alert('Une erreur est survenue. Veuillez réessayer.');
    } finally {
        isSending.value = false;
    }
};

const editChat = (chat) => {
    editingChat.value = chat;
    editingTitle.value = chat.title;
    showEditModal.value = true;
};

const saveEditChat = async () => {
    if (!editingTitle.value.trim() || !editingChat.value) return;

    try {
        await axios.put(`/chat/${editingChat.value.id}`, {
            title: editingTitle.value,
        });

        const chatIndex = chatsList.value.findIndex(c => c.id === editingChat.value.id);
        if (chatIndex !== -1) {
            chatsList.value[chatIndex].title = editingTitle.value;
        }

        showEditModal.value = false;
        editingChat.value = null;
        editingTitle.value = '';
    } catch (error) {
        console.error('Erreur lors de la modification du chat:', error);
        alert('Impossible de modifier le titre.');
    }
};

const deleteChat = async (chatId) => {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce chat ?')) return;

    try {
        await axios.delete(`/chat/${chatId}`);
        chatsList.value = chatsList.value.filter(chat => chat.id !== chatId);

        if (currentChatId.value === chatId) {
            startNewChat();
        }
    } catch (error) {
        console.error('Erreur lors de la suppression du chat:', error);
        alert('Impossible de supprimer ce chat.');
    }
};

const showChatsList = () => {
    console.log('Afficher la liste des chats');
};

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

watch(messages, () => {
    nextTick(() => scrollToBottom());
}, { deep: true });

onMounted(async () => {
    await loadChats();
    scrollToBottom();
});
</script>

<style scoped>
.message-enter-active,
.message-leave-active {
    transition: all 0.3s ease;
}

.message-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.message-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}

textarea {
    max-height: 120px;
    min-height: 56px;
}

@keyframes bounce {
    0%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-8px);
    }
}

.animate-bounce {
    animation: bounce 1.4s infinite;
}

.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
