<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        Log::info('📋 Chargement conversations INDEX', [
            'user_id' => $user->id,
            'is_admin' => $user->hasRole('admin')
        ]);

        // ✅ CORRECTION : Charger les messages avec la conversation
        $conversations = Conversation::with([
            'client',
            'admin',
            'messages' => function ($query) {
                $query->latest('created_at')->limit(1); // Seulement le dernier message
            },
            'messages.sender' // ✅ Charger aussi l'expéditeur du dernier message
        ])
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('status', 'active')
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(fn($conv) => $this->formatConversation($conv, $user));

        Log::info('✅ Conversations INDEX chargées', [
            'count' => $conversations->count(),
            'conversations' => $conversations->map(fn($c) => [
                'id' => $c['id'],
                'has_last_message' => !is_null($c['last_message']),
                'last_message_preview' => $c['last_message']
                    ? substr($c['last_message']['message'], 0, 50)
                    : 'Aucun message'
            ])
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'conversations' => $conversations,
            ]);
        }

        return Inertia::render('Conversation/Index', [
            'conversations' => $conversations,
            'userRoles' => $user->roles->pluck('name')->toArray(),
        ]);
    }

    /**
     * ✅ CORRECTION : Utiliser les messages déjà chargés au lieu de refaire une requête
     */
    private function formatConversation($conversation, $user)
    {
        // ✅ Si les messages sont déjà chargés (eager loading), les utiliser
        // Sinon, faire la requête (pour compatibilité avec show())
        $lastMessage = $conversation->relationLoaded('messages') && $conversation->messages->isNotEmpty()
            ? $conversation->messages->first() // Premier élément car déjà trié en DESC
            : $conversation->messages()->latest('created_at')->first();

        // ✅ Calculer le nombre de messages non lus
        $unreadCount = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();

        return [
            'id' => $conversation->id,
            'client_id' => $conversation->client_id,
            'admin_id' => $conversation->admin_id,
            'subject' => $conversation->subject,
            'status' => $conversation->status,
            'last_message_at' => $conversation->last_message_at,
            'unread_count' => $unreadCount,

            'client' => $conversation->client ? [
                'id' => $conversation->client->id,
                'name' => $conversation->client->name,
                'email' => $conversation->client->email,
            ] : null,

            'admin' => $conversation->admin ? [
                'id' => $conversation->admin->id,
                'name' => $conversation->admin->name,
            ] : null,

            'last_message' => $lastMessage ? [
                'id' => $lastMessage->id,
                'message' => $lastMessage->message,
                'sender_id' => $lastMessage->sender_id,
                'created_at' => $lastMessage->created_at,
                'is_read' => $lastMessage->is_read,
            ] : null,
        ];
    }

    /**
     * ✅ Affiche une conversation avec TOUS les messages
     */
    public function show(Conversation $conversation)
    {
        $user = auth()->user();

        if (!$conversation->hasParticipant($user->id)) {
            abort(403, 'Accès non autorisé');
        }

        Log::info('🔍 Chargement conversation SHOW', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id
        ]);

        $conversation->load([
            'messages' => function ($query) {
                $query->with('sender')->orderBy('created_at', 'asc');
            },
            'client',
            'admin',
        ]);

        Log::info('📨 Messages SHOW chargés', [
            'conversation_id' => $conversation->id,
            'total_messages' => $conversation->messages->count(),
        ]);

        $conversation->markAsReadFor($user->id);

        // Charger la liste pour la sidebar
        $conversations = Conversation::with([
            'client',
            'admin',
            'messages' => function ($query) {
                $query->latest('created_at')->limit(1);
            },
            'messages.sender'
        ])
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('status', 'active')
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(fn($conv) => $this->formatConversation($conv, $user));

        $messagesFormatted = $conversation->messages->map(function ($message) {
            return [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_id' => $message->sender_id,
                'message' => $message->message,
                'type' => $message->type,
                'is_read' => $message->is_read,
                'file_path' => $message->file_path,
                'file_name' => $message->file_name,
                'file_type' => $message->file_type,
                'file_url' => $message->file_path ? asset('storage/' . $message->file_path) : null,
                'created_at' => $message->created_at,
                'updated_at' => $message->updated_at,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                    'email' => $message->sender->email,
                ],
            ];
        });

        return Inertia::render('Conversation/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'client_id' => $conversation->client_id,
                'admin_id' => $conversation->admin_id,
                'subject' => $conversation->subject,
                'status' => $conversation->status,
                'last_message_at' => $conversation->last_message_at,
                'client' => $conversation->client ? [
                    'id' => $conversation->client->id,
                    'name' => $conversation->client->name,
                    'email' => $conversation->client->email,
                ] : null,
                'admin' => $conversation->admin ? [
                    'id' => $conversation->admin->id,
                    'name' => $conversation->admin->name,
                ] : null,
                'messages' => $messagesFormatted,
            ],
            'conversations' => $conversations,
            'userRoles' => $user->roles->pluck('name')->toArray(),
        ]);
    }

    /**
     * Créer une nouvelle conversation
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'nullable|exists:users,id',
            'admin_id' => 'nullable|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        $user = auth()->user();

        // Déterminer qui est le client et qui est l'admin
        if ($user->hasRole('admin')) {
            $clientId = $request->client_id ?? null;
            $adminId = $user->id;

            if (!$clientId) {
                return back()->withErrors(['message' => 'Veuillez sélectionner un client']);
            }
        } else {
            $clientId = $user->id;
            $adminId = $request->admin_id ?? User::role('admin')->first()?->id;
        }

        // Créer la conversation
        $conversation = Conversation::create([
            'client_id' => $clientId,
            'admin_id' => $adminId,
            'subject' => $request->subject ?? 'Nouvelle demande',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        // Ajouter les participants
        $conversation->participants()->attach($clientId);
        if ($adminId) {
            $conversation->participants()->attach($adminId);
        }

        // Créer le premier message
        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'message' => $request->message,
            'type' => 'text',
            'is_read' => false,
        ]);

        // Incrémenter le compteur non lu pour le destinataire
        $recipientId = ($user->id === $clientId) ? $adminId : $clientId;
        if ($recipientId) {
            $conversation->participantDetails()
                ->where('user_id', $recipientId)
                ->first()
                ?->incrementUnread();
        }

        Log::info('✅ Conversation créée', [
            'conversation_id' => $conversation->id,
            'client_id' => $clientId,
            'admin_id' => $adminId,
            'message_id' => $message->id
        ]);

        return redirect()->route('conversations.show', $conversation->id);
    }

    /**
     * Envoyer un message
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = auth()->user();

        if (!$conversation->hasParticipant($user->id)) {
            return back()->withErrors(['message' => 'Accès non autorisé']);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required_without:file|string',
            'file' => 'required_without:message|file|max:10240',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        $messageData = [
            'sender_id' => $user->id,
            'message' => $request->message ?? '',
            'type' => 'text',
            'is_read' => false,
        ];

        // Gérer l'upload de fichier
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('conversations/' . $conversation->id, 'public');

            $messageData['file_path'] = $path;
            $messageData['file_name'] = $file->getClientOriginalName();
            $messageData['file_type'] = $file->getMimeType();

            if (str_starts_with($file->getMimeType(), 'image/')) {
                $messageData['type'] = 'image';
            } else {
                $messageData['type'] = 'file';
            }
        }

        $message = $conversation->messages()->create($messageData);

        // Mettre à jour la conversation
        $conversation->update(['last_message_at' => now()]);

        // Incrémenter le compteur pour les autres participants
        $otherParticipants = $conversation->participantDetails()
            ->where('user_id', '!=', $user->id)
            ->get();

        foreach ($otherParticipants as $participant) {
            $participant->incrementUnread();
        }

        Log::info('✅ Message envoyé', [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'sender_id' => $user->id
        ]);

        return redirect()->route('conversations.show', $conversation->id);
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead(Conversation $conversation)
    {
        $user = auth()->user();

        if (!$conversation->hasParticipant($user->id)) {
            return back()->withErrors(['message' => 'Accès non autorisé']);
        }

        $conversation->markAsReadFor($user->id);

        return back();
    }

    /**
     * Mettre à jour le statut "en train d'écrire"
     */
    public function updateTyping(Request $request, Conversation $conversation)
    {
        $user = auth()->user();

        if (!$conversation->hasParticipant($user->id)) {
            return back()->withErrors(['message' => 'Accès non autorisé']);
        }

        $participant = $conversation->participantDetails()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->setTyping($request->is_typing ?? false);
        }

        return back();
    }

    /**
     * Fermer une conversation
     */
    public function close(Conversation $conversation)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin') && $conversation->client_id !== $user->id) {
            return back()->withErrors(['message' => 'Accès non autorisé']);
        }

        $conversation->update(['status' => 'closed']);

        return back();
    }

    /**
     * Supprimer une conversation
     */
    public function destroy(Conversation $conversation)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        // Supprimer les fichiers associés
        $messages = $conversation->messages()->whereNotNull('file_path')->get();
        foreach ($messages as $message) {
            Storage::disk('public')->delete($message->file_path);
        }

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation supprimée',
        ]);
    }
}
