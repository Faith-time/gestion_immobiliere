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
            'conversations' => $conversations ?? [],  // ✅ Valeur par défaut
            'userRoles' => $user->roles->pluck('name')->toArray(),  // ✅ CORRIGÉ
        ]);
    }

    public function show(Conversation $conversation)
    {
        $user = auth()->user();

        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $conversation->markAsReadFor($user->id);

        $conversation->load([
            'messages.sender',
            'client',
            'admin',
            'participantDetails',
        ]);

        $conversations = Conversation::with(['lastMessage.sender', 'client', 'admin'])
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('status', 'active')
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($conv) use ($user) {
                $conv->unread_count = $conv->getUnreadCountFor($user->id);
                return $conv;
            });

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'conversation' => $conversation,
                'conversations' => $conversations,
            ]);
        }

        return Inertia::render('Conversation/Show', [
            'conversation' => $conversation,
            'conversations' => $conversations ?? [],  // ✅ Valeur par défaut
            'userRoles' => $user->roles->pluck('name')->toArray(),  // ✅ CORRIGÉ
        ]);
    }
    // Créer une nouvelle conversation
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'nullable|exists:users,id', // ✅ Ajout de client_id
            'admin_id' => 'nullable|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        $user = auth()->user();

        // ✅ Déterminer qui est le client et qui est l'admin
        if ($user->hasRole('admin')) {
            // Si c'est un admin qui crée la conversation
            $clientId = $request->client_id ?? null;
            $adminId = $user->id;

            if (!$clientId) {
                return back()->withErrors(['message' => 'Veuillez sélectionner un client']);
            }
        } else {
            // Si c'est un client qui crée la conversation
            $clientId = $user->id;
            $adminId = $request->admin_id ?? User::role('admin')->first()?->id;
        }

        // Créer la conversation avec les bons IDs
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
        ]);

        // Incrémenter le compteur non lu pour le destinataire
        $recipientId = ($user->id === $clientId) ? $adminId : $clientId;
        if ($recipientId) {
            $conversation->participantDetails()
                ->where('user_id', $recipientId)
                ->first()
                ?->incrementUnread();
        }

        return redirect()->route('conversations.show', $conversation->id);
    }

    // Envoyer un message
// Envoyer un message
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = auth()->user();

        // Vérifier que l'utilisateur participe à la conversation
        if (!$conversation->hasParticipant($user->id)) {
            return back()->withErrors(['message' => 'Accès non autorisé']);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required_without:file|string',
            'file' => 'required_without:message|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
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

        // ✅ Retourner une réponse Inertia au lieu de JSON
        return back();
    }
    // Marquer comme lu


    // Marquer comme lu
    public function markAsRead(Conversation $conversation)
    {
        $user = auth()->user();

        if (!$conversation->hasParticipant($user->id)) {
            return back()->withErrors(['message' => 'Accès non autorisé']);
        }

        $conversation->markAsReadFor($user->id);

        return back(); // ✅ Ajout du retour
    }

// Mettre à jour le statut "en train d'écrire"
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

        return back(); // ✅ Ajout du retour
    }

// Fermer une conversation
    public function close(Conversation $conversation)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin') && $conversation->client_id !== $user->id) {
            return back()->withErrors(['message' => 'Accès non autorisé']);
        }

        $conversation->update(['status' => 'closed']);

        return back(); // ✅ Ajout du retour si ce n'est pas déjà fait
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
