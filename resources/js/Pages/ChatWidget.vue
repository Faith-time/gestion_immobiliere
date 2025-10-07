<template>
    <div class="fixed bottom-6 right-6 z-50">
        <!-- Bouton d'ouverture -->
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-150" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
            <button v-if="!isOpen" @click="toggleChat" class="relative bg-gradient-to-br from-blue-600 via-blue-500 to-green-600 hover:from-blue-700 hover:via-blue-600 hover:to-green-700 text-white rounded-full w-16 h-16 shadow-2xl hover:shadow-green-500/50 transition-all duration-300 group flex items-center justify-center">
                <span class="absolute inset-0 rounded-full bg-gradient-to-br from-blue-400 to-green-400 opacity-75 animate-ping"></span>
                <div class="relative z-10 flex items-center justify-center">
                    <svg class="w-8 h-8 transform group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
                <span v-if="unreadCount > 0" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full min-w-[24px] h-6 flex items-center justify-center font-bold px-2 shadow-lg animate-bounce">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
                <span class="absolute bottom-1 right-1 w-4 h-4 bg-green-400 border-2 border-white rounded-full"></span>
            </button>
        </Transition>

        <!-- Fenêtre de chat -->
        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="transform opacity-0 translate-y-4 scale-95" enter-to-class="transform opacity-100 translate-y-0 scale-100" leave-active-class="transition ease-in duration-200" leave-from-class="transform opacity-100 translate-y-0 scale-100" leave-to-class="transform opacity-0 translate-y-4 scale-95">
            <div v-if="isOpen" class="bg-white rounded-3xl shadow-2xl w-[420px] h-[650px] flex flex-col overflow-hidden border-2 border-gray-100">
                <!-- Header -->
                <div class="relative bg-gradient-to-br from-blue-600 via-blue-500 to-green-600 text-white p-5 overflow-hidden">
                    <div class="absolute inset-0 opacity-20">
                        <div class="absolute top-0 left-0 w-32 h-32 bg-white rounded-full blur-3xl animate-pulse"></div>
                        <div class="absolute bottom-0 right-0 w-40 h-40 bg-green-300 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s"></div>
                    </div>
                    <div class="relative flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <div class="w-12 h-12 bg-white/30 backdrop-blur-md rounded-2xl flex items-center justify-center shadow-lg border border-white/40">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 border-3 border-white rounded-full shadow-md"></span>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg tracking-wide">Assistant IA</h3>
                                <div class="flex items-center space-x-1">
                                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                    <p class="text-xs text-white/90 font-medium">En ligne</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-1">
                            <button @click="showChatList = !showChatList" class="p-2.5 hover:bg-white/20 rounded-xl transition-all duration-200 backdrop-blur-sm group" title="Historique">
                                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </button>
                            <button @click="toggleChat" class="p-2.5 hover:bg-white/20 rounded-xl transition-all duration-200 backdrop-blur-sm group">
                                <svg class="w-5 h-5 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Liste des chats -->
                <div v-if="showChatList" class="flex-1 overflow-y-auto bg-gradient-to-b from-gray-50 to-white p-4 space-y-3">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-bold text-gray-800 text-base flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Historique
                        </h4>
                        <button @click="startNewChat" class="text-xs bg-gradient-to-r from-blue-600 to-green-600 text-white px-4 py-2 rounded-xl hover:shadow-lg transition-all duration-200 font-medium flex items-center space-x-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Nouveau</span>
                        </button>
                    </div>

                    <div v-for="chat in chats" :key="chat.id" @click="loadChat(chat.id)" class="bg-white p-4 rounded-2xl cursor-pointer hover:shadow-lg transition-all duration-200 group border-2" :class="currentChatId === chat.id ? 'ring-2 ring-blue-500 border-blue-200 shadow-md' : 'border-gray-100 hover:border-blue-200'">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-start space-x-2 flex-1">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-100 to-green-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                </div>
                                <h5 class="font-semibold text-sm text-gray-800 truncate flex-1 group-hover:text-blue-600 transition-colors">{{ chat.title }}</h5>
                            </div>
                            <div class="flex items-center space-x-1">
                                <button @click.stop="editChat(chat)" class="text-blue-500 hover:text-blue-700 transition-all hover:scale-110" title="Modifier">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click.stop="deleteChat(chat.id)" class="text-red-500 hover:text-red-700 transition-all hover:scale-110" title="Supprimer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 truncate ml-10">{{ chat.preview }}</p>
                        <p class="text-xs text-gray-400 mt-2 ml-10">{{ chat.updated_at }}</p>
                    </div>

                    <div v-if="!chats || chats.length === 0" class="text-center py-12">
                        <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-blue-100 to-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-blue-600 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-500">Aucun chat</p>
                        <p class="text-xs text-gray-400 mt-1">Créez une conversation</p>
                    </div>
                </div>

                <!-- Messages -->
                <div v-else class="flex-1 overflow-y-auto p-5 space-y-4 bg-gradient-to-b from-gray-50 via-white to-gray-50" ref="messagesContainer">
                    <div v-if="messages.length === 0" class="h-full flex items-center justify-center">
                        <div class="text-center max-w-sm">
                            <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-blue-100 via-blue-50 to-green-100 rounded-3xl flex items-center justify-center shadow-lg">
                                <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <h4 class="text-xl font-bold text-gray-800 mb-2">Bonjour !</h4>
                            <p class="text-sm text-gray-600 leading-relaxed">Comment puis-je vous aider ?</p>
                            <div class="mt-6 space-y-2">
                                <button @click="quickMessage('Voir les biens disponibles')" class="w-full text-left px-4 py-3 bg-gradient-to-r from-blue-50 to-green-50 hover:from-blue-100 hover:to-green-100 rounded-xl text-sm text-gray-700 transition-all duration-200 border border-blue-100 hover:border-blue-300 hover:shadow-md">
                                    💼 Biens disponibles
                                </button>
                                <button @click="quickMessage('Organiser une visite')" class="w-full text-left px-4 py-3 bg-gradient-to-r from-blue-50 to-green-50 hover:from-blue-100 hover:to-green-100 rounded-xl text-sm text-gray-700 transition-all duration-200 border border-blue-100 hover:border-blue-300 hover:shadow-md">
                                    📅 Organiser visite
                                </button>
                            </div>
                        </div>
                    </div>

                    <TransitionGroup name="message" tag="div" class="space-y-4">
                        <div v-for="message in messages" :key="message.id" class="flex group" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                            <div class="flex flex-col" :class="message.role === 'user' ? 'items-end' : 'items-start'">
                                <div class="max-w-[85%] rounded-2xl px-4 py-3 shadow-md relative" :class="message.role === 'user' ? 'bg-gradient-to-br from-blue-600 via-blue-500 to-green-600 text-white rounded-br-md' : 'bg-white text-gray-800 rounded-bl-md border-2 border-gray-100'">
                                    <div v-if="message.role === 'user'" class="absolute -top-8 right-0 flex space-x-1 bg-white rounded-lg shadow-lg p-1">
                                        <button @click="editMessage(message)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors" title="Modifier">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="deleteMessage(message.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors" title="Supprimer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ message.content }}</p>
                                    <p class="text-xs mt-2 flex items-center" :class="message.role === 'user' ? 'text-white/80 justify-end' : 'text-gray-400'">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ message.created_at }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </TransitionGroup>

                    <div v-if="isTyping" class="flex justify-start">
                        <div class="bg-white rounded-2xl rounded-bl-md px-5 py-4 shadow-md border-2 border-gray-100">
                            <div class="flex space-x-2">
                                <div class="w-2.5 h-2.5 bg-gradient-to-r from-blue-500 to-green-500 rounded-full animate-bounce"></div>
                                <div class="w-2.5 h-2.5 bg-gradient-to-r from-blue-500 to-green-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                <div class="w-2.5 h-2.5 bg-gradient-to-r from-blue-500 to-green-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input -->
                <div class="p-4 bg-gradient-to-t from-gray-50 to-white border-t-2 border-gray-100">
                    <form @submit.prevent="sendMessage" class="flex items-end space-x-3">
                        <div class="flex-1 relative">
                            <textarea v-model="newMessage" @keydown.enter.exact.prevent="sendMessage" placeholder="Votre message..." rows="1" class="w-full px-5 py-3.5 pr-12 rounded-2xl border-2 border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 resize-none transition-all shadow-sm" :disabled="isSending"></textarea>
                        </div>
                        <button type="submit" :disabled="!newMessage.trim() || isSending" class="bg-gradient-to-br from-blue-600 via-blue-500 to-green-600 hover:from-blue-700 hover:via-blue-600 hover:to-green-700 text-white p-4 rounded-2xl shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 transform hover:scale-105 active:scale-95">
                            <svg v-if="!isSending" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- Modal edit chat -->
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeEditModal">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Modifier le titre</h3>
                    <input v-model="editingTitle" type="text" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all" placeholder="Nouveau titre" @keydown.enter="saveEditChat" />
                    <div class="flex gap-3 mt-6">
                        <button @click="closeEditModal" class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium transition-colors">Annuler</button>
                        <button @click="saveEditChat" class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-green-600 hover:from-blue-700 hover:to-green-700 text-white rounded-xl font-medium transition-all shadow-lg hover:shadow-xl">Enregistrer</button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Modal edit message -->
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showEditMessageModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeEditMessageModal">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Modifier le message</h3>
                    <textarea v-model="editingMessageContent" rows="4" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all resize-none" placeholder="Modifier..."></textarea>
                    <div class="flex gap-3 mt-6">
                        <button @click="closeEditMessageModal" class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium transition-colors">Annuler</button>
                        <button @click="saveEditMessage" class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-green-600 hover:from-blue-700 hover:to-green-700 text-white rounded-xl font-medium transition-all shadow-lg hover:shadow-xl">Enregistrer</button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import axios from 'axios';

const isOpen = ref(false);
const showChatList = ref(false);
const messages = ref([]);
const chats = ref([]);
const newMessage = ref('');
const currentChatId = ref(null);
const isSending = ref(false);
const isTyping = ref(false);
const unreadCount = ref(0);
const messagesContainer = ref(null);
const showEditModal = ref(false);
const editingChat = ref(null);
const editingTitle = ref('');
const showEditMessageModal = ref(false);
const editingMessage = ref(null);
const editingMessageContent = ref('');

const toggleChat = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value && chats.value.length === 0) loadChats();
};

const loadChats = async () => {
    try {
        const response = await axios.get(route('chat.index'));
        console.log('Chats loaded:', response.data); // Debug
        chats.value = response.data.chats || [];
    } catch (error) {
        console.error('Erreur chargement chats:', error);
        console.error('Error details:', error.response?.data); // Debug
    }
};
const loadChat = async (chatId) => {
    try {
        const response = await axios.get(`/chat/${chatId}`);
        messages.value = response.data.messages;
        currentChatId.value = chatId;
        showChatList.value = false;
        await nextTick();
        scrollToBottom();
    } catch (error) {
        console.error('Erreur chargement chat:', error);
    }
};

const startNewChat = () => {
    messages.value = [];
    currentChatId.value = null;
    showChatList.value = false;
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
            response = await axios.post(`/chat/${currentChatId.value}/message`, { message: messageContent });
            messages.value.push(...response.data.messages);
        } else {
            response = await axios.post('/chat', { message: messageContent });
            currentChatId.value = response.data.chat.id;
            messages.value.push(...response.data.messages);
            await loadChats();
        }

        await nextTick();
        scrollToBottom();
    } catch (error) {
        console.error('Erreur envoi:', error);
        alert('Erreur lors de l\'envoi. Réessayez.');
    } finally {
        isSending.value = false;
    }
};

const editChat = (chat) => {
    editingChat.value = chat;
    editingTitle.value = chat.title;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingChat.value = null;
    editingTitle.value = '';
};

const saveEditChat = async () => {
    if (!editingTitle.value.trim() || !editingChat.value) return;

    try {
        await axios.put(`/chat/${editingChat.value.id}`, {
            title: editingTitle.value
        });

        // Mettre à jour le titre dans la liste
        const chatIndex = chats.value.findIndex(c => c.id === editingChat.value.id);
        if (chatIndex !== -1) {
            chats.value[chatIndex].title = editingTitle.value;
        }

        closeEditModal();
    } catch (error) {
        console.error('Erreur modification chat:', error);
        alert('Erreur lors de la modification du titre.');
    }
};

const deleteChat = async (chatId) => {
    if (!confirm('Voulez-vous vraiment supprimer cette conversation ?')) return;

    try {
        await axios.delete(`/chat/${chatId}`);

        // Retirer le chat de la liste
        chats.value = chats.value.filter(c => c.id !== chatId);

        // Si c'était le chat actif, réinitialiser
        if (currentChatId.value === chatId) {
            messages.value = [];
            currentChatId.value = null;
        }
    } catch (error) {
        console.error('Erreur suppression chat:', error);
        alert('Erreur lors de la suppression.');
    }
};

const editMessage = (message) => {
    editingMessage.value = message;
    editingMessageContent.value = message.content;
    showEditMessageModal.value = true;
};

const closeEditMessageModal = () => {
    showEditMessageModal.value = false;
    editingMessage.value = null;
    editingMessageContent.value = '';
};

const saveEditMessage = async () => {
    if (!editingMessageContent.value.trim() || !editingMessage.value) return;

    try {
        await axios.put(`/chat/message/${editingMessage.value.id}`, {
            content: editingMessageContent.value
        });

        // Mettre à jour le message dans la liste
        const messageIndex = messages.value.findIndex(m => m.id === editingMessage.value.id);
        if (messageIndex !== -1) {
            messages.value[messageIndex].content = editingMessageContent.value;
        }

        closeEditMessageModal();
    } catch (error) {
        console.error('Erreur modification message:', error);
        alert('Erreur lors de la modification du message.');
    }
};

const deleteMessage = async (messageId) => {
    if (!confirm('Voulez-vous vraiment supprimer ce message ?')) return;

    try {
        await axios.delete(`/chat/message/${messageId}`);

        // Retirer le message de la liste
        messages.value = messages.value.filter(m => m.id !== messageId);
    } catch (error) {
        console.error('Erreur suppression message:', error);
        alert('Erreur lors de la suppression du message.');
    }
};

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// Charger les chats au montage du composant
onMounted(() => {
    loadChats();
});

// Auto-scroll quand les messages changent
watch(messages, () => {
    nextTick(() => scrollToBottom());
}, { deep: true });
</script>

<style scoped>
/* Animations pour les messages */
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

/* Animation du ping pour le bouton */
@keyframes ping {
    75%, 100% {
        transform: scale(2);
        opacity: 0;
    }
}

.animate-ping {
    animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
}

/* Animation bounce */
@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-25%);
    }
}

.animate-bounce {
    animation: bounce 1s infinite;
}

/* Animation pulse */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Amélioration du scrollbar */
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

/* Auto-resize du textarea */
textarea {
    min-height: 48px;
    max-height: 120px;
    overflow-y: auto;
    field-sizing: content;
}

/* Styles pour les transitions */
.transition {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

.ease-out {
    transition-timing-function: cubic-bezier(0, 0, 0.2, 1);
}

.ease-in {
    transition-timing-function: cubic-bezier(0.4, 0, 1, 1);
}

.duration-200 {
    transition-duration: 200ms;
}

.duration-300 {
    transition-duration: 300ms;
}

.duration-150 {
    transition-duration: 150ms;
}

/* Effet de survol sur les boutons */
button {
    -webkit-tap-highlight-color: transparent;
}

/* Animation pour le spinner de chargement */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Effet de blur pour le backdrop */
.backdrop-blur-sm {
    backdrop-filter: blur(4px);
}

.backdrop-blur-md {
    backdrop-filter: blur(12px);
}

/* Gradient animé pour le header */
@keyframes gradient-shift {
    0%, 100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}

/* Effet de focus amélioré */
input:focus,
textarea:focus {
    outline: none;
}

/* Amélioration de l'accessibilité */
button:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Effet de glassmorphism */
.glass {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Animation pour les badges de notification */
@keyframes badge-bounce {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

/* Responsive design pour les petits écrans */
@media (max-width: 640px) {
    .fixed.bottom-6.right-6 > div {
        width: calc(100vw - 2rem);
        max-width: 420px;
    }
}

/* Animation de typing indicator */
@keyframes typing-dot {
    0%, 60%, 100% {
        transform: translateY(0);
    }
    30% {
        transform: translateY(-10px);
    }
}

/* Styles pour les messages en markdown (si nécessaire) */
.message-content {
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.message-content p {
    margin-bottom: 0.5rem;
}

.message-content p:last-child {
    margin-bottom: 0;
}

/* Effet hover sur les cartes de chat */
.chat-card {
    transition: all 0.2s ease;
}

.chat-card:hover {
    transform: translateY(-2px);
}

/* Animation de fade pour les modals */
.modal-backdrop {
    backdrop-filter: blur(4px);
}

/* Styles pour le bouton flottant */
.floating-button {
    box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.5);
}

.floating-button:hover {
    box-shadow: 0 20px 35px -5px rgba(59, 130, 246, 0.6);
}

/* Amélioration des transitions de hauteur */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Styles pour les tooltips */
[title] {
    position: relative;
}

/* Animation de shake pour les erreurs */
@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    10%, 30%, 50%, 70%, 90% {
        transform: translateX(-5px);
    }
    20%, 40%, 60%, 80% {
        transform: translateX(5px);
    }
}

.shake {
    animation: shake 0.5s ease-in-out;
}

/* Optimisation des performances */
* {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Désactivation des animations si préférence utilisateur */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
