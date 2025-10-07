<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'last_read_at',
        'unread_count',
        'is_typing',
        'typing_at',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'typing_at' => 'datetime',
        'is_typing' => 'boolean',
        'unread_count' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Incrémenter le compteur de messages non lus
    public function incrementUnread(): void
    {
        $this->increment('unread_count');
    }

    // Réinitialiser le compteur de messages non lus
    public function resetUnread(): void
    {
        $this->update([
            'unread_count' => 0,
            'last_read_at' => now(),
        ]);
    }

    // Définir le statut "en train d'écrire"
    public function setTyping(bool $isTyping): void
    {
        $this->update([
            'is_typing' => $isTyping,
            'typing_at' => $isTyping ? now() : null,
        ]);
    }
}
