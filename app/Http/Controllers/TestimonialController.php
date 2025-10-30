<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TestimonialController extends Controller
{
    // Afficher tous les témoignages approuvés (page publique)
    public function index()
    {
        $testimonials = Testimonial::with(['user', 'bien'])
            ->approved()
            ->recent()
            ->paginate(12);

        return Inertia::render('Testimonials/Index', [
            'testimonials' => $testimonials
        ]);
    }

    // Formulaire de création (réservé aux clients ayant une location terminée)
    public function create()
    {
        $user = auth()->user();

        // Récupérer les locations terminées du client sans témoignage
        $locations = Location::where('client_id', $user->id)
            ->whereDoesntHave('testimonial')
            ->where('statut', 'termine')
            ->with('bien')
            ->get();

        if ($locations->isEmpty()) {
            return redirect()->back()->with('error', 'Vous devez avoir une location terminée pour laisser un témoignage.');
        }

        return Inertia::render('Testimonials/Create', [
            'locations' => $locations
        ]);
    }

    // Enregistrer un nouveau témoignage
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|min:20|max:500',
            'rating' => 'required|integer|min:1|max:5',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $location = Location::findOrFail($request->location_id);

        // Vérifier que c'est bien le client de cette location
        if ($location->client_id !== auth()->id()) {
            return redirect()->back()->with('error', 'Action non autorisée.');
        }

        // Vérifier qu'il n'y a pas déjà un témoignage pour cette location
        if ($location->testimonial) {
            return redirect()->back()->with('error', 'Vous avez déjà laissé un témoignage pour cette location.');
        }

        $data = $request->only(['content', 'rating', 'location_id']);
        $data['user_id'] = auth()->id();
        $data['bien_id'] = $location->bien_id;

        // Upload de l'avatar si fourni
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('testimonials', 'public');
        }

        Testimonial::create($data);

        return redirect()->route('dashboard')->with('success', 'Merci ! Votre témoignage a été soumis et sera publié après validation.');
    }

    // Afficher les témoignages du client connecté
    public function myTestimonials()
    {
        $testimonials = Testimonial::where('user_id', auth()->id())
            ->with(['bien',])
            ->recent()
            ->get();

        return Inertia::render('Testimonials/MyTestimonials', [
            'testimonials' => $testimonials
        ]);
    }

    // Admin - Liste des témoignages à valider
    public function admin()
    {
        $testimonials = Testimonial::with(['user', 'bien'])
            ->recent()
            ->paginate(20);

        return Inertia::render('Admin/Testimonials/Index', [
            'testimonials' => $testimonials
        ]);
    }

    // Admin - Approuver un témoignage
    public function approve($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->update([
            'is_approved' => true,
            'approved_at' => now()
        ]);

        return redirect()->back()->with('success', 'Témoignage approuvé avec succès.');
    }

    // Admin - Mettre en avant un témoignage
    public function toggleFeatured($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->update([
            'is_featured' => !$testimonial->is_featured
        ]);

        return redirect()->back()->with('success', 'Statut mis à jour.');
    }

    // Admin - Supprimer un témoignage
    public function destroy($id)
    {
        $testimonial = Testimonial::findOrFail($id);

        if ($testimonial->avatar) {
            \Storage::disk('public')->delete($testimonial->avatar);
        }

        $testimonial->delete();

        return redirect()->back()->with('success', 'Témoignage supprimé.');
    }
}
