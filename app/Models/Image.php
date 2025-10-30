<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle',
        'chemin_image',
        'bien_id',
        'appartement_id'
    ];

    // ✅ Ajouter les accesseurs à la réponse JSON
    protected $appends = ['url'];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function bien()
    {
        return $this->belongsTo(Bien::class, 'bien_id');
    }

    public function appartement()
    {
        return $this->belongsTo(Appartement::class, 'appartement_id');
    }



    public function getUrlAttribute()
    {
        if ($this->chemin_image) {
            return asset('storage/' . $this->chemin_image);
        }
        return null;
    }

    public function exists()
    {
        return \Illuminate\Support\Facades\Storage::disk('public')->exists($this->chemin_image);
    }

    public function scopeValid($query)
    {
        return $query->whereHas('bien');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($image) {
            if ($image->exists()) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($image->chemin_image);
            }
        });
    }
}
