<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // Récupérer les biens disponibles avec leurs catégories
        // Limiter à 8 biens pour la page d'accueil et trier par les plus récents
        $biens = Bien::with('category')
            ->where('status', 'disponible') // Seulement les biens disponibles
            ->orderBy('created_at', 'desc') // Les plus récents en premier
            ->take(8) // Limiter à 8 biens pour le slider
            ->get();

        // Compter le nombre total de biens disponibles
        $totalBiens = Bien::where('status', 'disponible')->count();

        return Inertia::render('Home', [
            'biens' => $biens,
            'totalBiens' => $totalBiens, // Ajouter cette ligne
        ]);
    }
}
