<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_guest',
        'session_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_guest' => 'boolean',
    ];

    public function isGuest(): bool
    {
        return $this->is_guest === true;
    }

    public function isAuthenticated(): bool
    {
        return !$this->is_guest;
    }

    // Relations
    public function biens()
    {
        return $this->hasMany(Bien::class, 'proprietaire_id');
    }

    public function visites()
    {
        return $this->hasMany(Visite::class, 'agent_id');
    }

    public function clientVisites()
    {
        return $this->hasMany(Visite::class, 'client_id');
    }

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class, 'acheteur_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'client_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'client_id');
    }

    public function clientDocuments()
    {
        return $this->hasMany(ClientDocument::class, 'client_id');
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function messages()
    {
        return $this->hasManyThrough(Message::class, Chat::class);
    }

    /**
     * Obtenir toutes les conversations de l'utilisateur
     */
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['last_read_at', 'unread_count', 'is_typing', 'typing_at'])
            ->withTimestamps();
    }

    /**
     * Obtenir le nombre total de messages non lus
     */
    public function getTotalUnreadCount(): int
    {
        return ConversationParticipant::where('user_id', $this->id)
            ->sum('unread_count');
    }

    /**
     * Obtenir les conversations en tant que client
     */
    public function clientConversations()
    {
        return $this->hasMany(Conversation::class, 'client_id');
    }

    /**
     * Obtenir les conversations en tant qu'admin
     */
    public function adminConversations()
    {
        return $this->hasMany(Conversation::class, 'admin_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class, 'sender_id');
    }

    public function getConversationsWithUnread()
    {
        return $this->conversations()
            ->with(['lastMessage.sender', 'client', 'admin', 'bien'])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($conversation) {
                $conversation->unread_count = $conversation->getUnreadCountFor($this->id);
                return $conversation;
            });
    }

    /**
     * Relation avec le dossier client
     */
    public function dossierClient()
    {
        return $this->hasOne(ClientDossier::class, 'client_id');
    }
}
