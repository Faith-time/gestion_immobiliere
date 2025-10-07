<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ConversationController extends Controller
{
    // Lister toutes les conversations de l'utilisateur
    public function index(Request $request)
    {
        $user = auth()->user();

        // Correction: suppression de 'bien' de la liste des relations
        $conversations = Conversation::with(['lastMessage.sender', 'client', 'admin'])
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('status', 'active')
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($conversation) use ($user) {
                $conversation->unread_count = $conversation->getUnreadCountFor($user->id);
                return $conversation;
            });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'conversations' => $conversations,
                'total_unread' => $user->getTotalUnreadCount(),
            ]);
        }

        return Inertia::render('Conversation/Index', [
            'conversations' => $conversations,
        ]);
    }

    // Créer une nouvelle conversation
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_id' => 'nullable|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();

        // Créer la conversation
        $conversation = Conversation::create([
            'client_id' => $user->id,
            'admin_id' => $request->admin_id,
            'subject' => $request->subject ?? 'Nouvelle demande',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        // Ajouter les participants
        $conversation->participants()->attach($user->id);
        if ($request->admin_id) {
            $conversation->participants()->attach($request->admin_id);
        } else {
            // Assigner automatiquement au premier admin disponible
            $admin = User::role('admin')->first();
            if ($admin) {
                $conversation->update(['admin_id' => $admin->id]);
                $conversation->participants()->attach($admin->id);
            }
        }

        // Créer le premier message
        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'message' => $request->message,
            'type' => 'text',
        ]);

        // Incrémenter le compteur non lu pour l'admin
        if ($conversation->admin_id) {
            $conversation->participantDetails()
                ->where('user_id', $conversation->admin_id)
                ->first()
                ?->incrementUnread();
        }

        // Correction: suppression de 'bien' de la liste des relations
        return response()->json([
            'success' => true,
            'conversation' => $conversation->load(['messages.sender', 'client', 'admin']),
            'message' => 'Conversation créée avec succès',
        ], 201);
    }

    // Afficher une conversation
    public function show(Conversation $conversation)
    {
        $user = auth()->user();

        // Vérifier que l'utilisateur participe à la conversation
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        // Marquer les messages comme lus
        $conversation->markAsReadFor($user->id);

        // Correction: suppression de 'bien' de la liste des relations
        $conversation->load([
            'messages.sender',
            'client',
            'admin',
            'participantDetails',
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'conversation' => $conversation,
            ]);
        }

        return Inertia::render('Conversation/Show', [
            'conversation' => $conversation,
        ]);
    }

    // Envoyer un message
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = auth()->user();

        // Vérifier que l'utilisateur participe à la conversation
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required_without:file|string',
            'file' => 'required_without:message|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $messageData = [
            'sender_id' => $user->id,
            'message' => $request->message ?? '',
            'type' => 'text',
        ];

        // Gérer l'upload de fichier
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('conversations/' . $conversation->id, 'public');

            $messageData['file_path'] = $path;
            $messageData['file_name'] = $file->getClientOriginalName();
            $messageData['file_type'] = $file->getMimeType();

            // Déterminer le type de message
            if (str_starts_with($file->getMimeType(), 'image/')) {
                $messageData['type'] = 'image';
            } else {
                $messageData['type'] = 'file';
            }
        }

        $message = $conversation->messages()->create($messageData);

        // Mettre à jour la date du dernier message
        $conversation->update(['last_message_at' => now()]);

        // Incrémenter le compteur non lu pour les autres participants
        $otherParticipants = $conversation->participantDetails()
            ->where('user_id', '!=', $user->id)
            ->get();

        foreach ($otherParticipants as $participant) {
            $participant->incrementUnread();
        }

        return response()->json([
            'success' => true,
            'message' => $message->load('sender'),
        ], 201);
    }

    // Marquer comme lu
    public function markAsRead(Conversation $conversation)
    {
        $user = auth()->user();

        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $conversation->markAsReadFor($user->id);
    }

    // Mettre à jour le statut "en train d'écrire"
    public function updateTyping(Request $request, Conversation $conversation)
    {
        $user = auth()->user();

        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $participant = $conversation->participantDetails()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->setTyping($request->is_typing ?? false);
        }

    }

    // Fermer une conversation
    public function close(Conversation $conversation)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin') && $conversation->client_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $conversation->update(['status' => 'closed']);
    }

    // Supprimer une conversation
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
