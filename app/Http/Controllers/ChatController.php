<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Services\AIChatService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatController extends Controller
{
    protected AIChatService $aiService;

    public function __construct(AIChatService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index(Request $request)
    {
        $chats = auth()->user()
            ->chats()
            ->with('lastMessage')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'title' => $chat->title,
                    'updated_at' => $chat->updated_at->diffForHumans(),
                    'preview' => $chat->lastMessage?->content
                        ? substr($chat->lastMessage->content, 0, 60) . '...'
                        : 'Nouveau chat',
                ];
            });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'chats' => $chats,
            ]);
        }

        return Inertia::render('Chat/Index', [
            'chats' => $chats,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $title = $this->aiService->generateTitle($request->message);

        $chat = Chat::create([
            'user_id' => auth()->id(),
            'title' => $title,
        ]);

        $userMessage = Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => $request->message,
        ]);

        $aiResponse = $this->aiService->generateResponse($request->message);

        $assistantMessage = Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => $aiResponse,
        ]);

        return response()->json([
            'chat' => [
                'id' => $chat->id,
                'title' => $chat->title,
                'updated_at' => $chat->updated_at->diffForHumans(),
            ],
            'messages' => [
                [
                    'id' => $userMessage->id,
                    'role' => 'user',
                    'content' => $userMessage->content,
                    'created_at' => $userMessage->created_at->format('H:i'),
                ],
                [
                    'id' => $assistantMessage->id,
                    'role' => 'assistant',
                    'content' => $assistantMessage->content,
                    'created_at' => $assistantMessage->created_at->format('H:i'),
                ],
            ],
        ]);
    }

    public function show(Chat $chat)
    {
        $this->authorize('view', $chat);

        $messages = $chat->messages()
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'created_at' => $message->created_at->format('H:i'),
                ];
            });

        return response()->json([
            'chat' => [
                'id' => $chat->id,
                'title' => $chat->title,
            ],
            'messages' => $messages,
        ]);
    }

    public function sendMessage(Request $request, Chat $chat)
    {
        $this->authorize('update', $chat);

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $userMessage = Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => $request->message,
        ]);

        $aiResponse = $this->aiService->generateResponse($request->message);

        $assistantMessage = Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => $aiResponse,
        ]);

        $chat->touch();

        return response()->json([
            'messages' => [
                [
                    'id' => $userMessage->id,
                    'role' => 'user',
                    'content' => $userMessage->content,
                    'created_at' => $userMessage->created_at->format('H:i'),
                ],
                [
                    'id' => $assistantMessage->id,
                    'role' => 'assistant',
                    'content' => $assistantMessage->content,
                    'created_at' => $assistantMessage->created_at->format('H:i'),
                ],
            ],
        ]);
    }

    public function update(Request $request, Chat $chat)
    {
        $this->authorize('update', $chat);

        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $chat->update([
            'title' => $request->title,
        ]);

        return response()->json([
            'message' => 'Chat mis à jour avec succès',
            'chat' => [
                'id' => $chat->id,
                'title' => $chat->title,
            ],
        ]);
    }

    public function destroy(Chat $chat)
    {
        $this->authorize('delete', $chat);

        $chat->delete();

        return response()->json([
            'message' => 'Chat supprimé avec succès',
        ]);
    }

    // Nouvelle méthode pour mettre à jour un message
    public function updateMessage(Request $request, Message $message)
    {
        $this->authorize('update', $message->chat);

        // Vérifier que c'est bien un message utilisateur
        if ($message->role !== 'user') {
            return response()->json([
                'message' => 'Seuls les messages utilisateurs peuvent être modifiés',
            ], 403);
        }

        $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $message->update([
            'content' => $request->content,
        ]);

        // Régénérer la réponse de l'IA
        $aiResponse = $this->aiService->generateResponse($request->content);

        // Trouver le message assistant qui suit
        $assistantMessage = Message::where('chat_id', $message->chat_id)
            ->where('role', 'assistant')
            ->where('id', '>', $message->id)
            ->first();

        if ($assistantMessage) {
            $assistantMessage->update([
                'content' => $aiResponse,
            ]);
        }

        $message->chat->touch();

        return response()->json([
            'message' => 'Message mis à jour avec succès',
            'data' => [
                'id' => $message->id,
                'content' => $message->content,
                'created_at' => $message->created_at->format('H:i'),
            ],
            'assistant_message' => $assistantMessage ? [
                'id' => $assistantMessage->id,
                'content' => $assistantMessage->content,
                'created_at' => $assistantMessage->created_at->format('H:i'),
            ] : null,
        ]);
    }

    // Nouvelle méthode pour supprimer un message
    public function destroyMessage(Message $message)
    {
        $this->authorize('update', $message->chat);

        // Vérifier que c'est bien un message utilisateur
        if ($message->role !== 'user') {
            return response()->json([
                'message' => 'Seuls les messages utilisateurs peuvent être supprimés',
            ], 403);
        }

        // Trouver et supprimer le message assistant qui suit
        $assistantMessage = Message::where('chat_id', $message->chat_id)
            ->where('role', 'assistant')
            ->where('id', '>', $message->id)
            ->first();

        if ($assistantMessage) {
            $assistantMessage->delete();
        }

        $message->delete();
        $message->chat->touch();

        return response()->json([
            'message' => 'Message supprimé avec succès',
        ]);
    }
}
