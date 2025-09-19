<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Categorie;
use App\Models\Mandat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class BienController extends Controller
{
    // Pourcentage de commission fixe
    const COMMISSION_PERCENTAGE = 10;

    // GET /biens
    public function index()
    {
        $user = auth()->user();

        // Si l'utilisateur a le rôle admin, il voit tous les biens avec leurs relations
        if ($user->hasRole('admin')) {
            $biens = Bien::with(['category', 'mandat', 'proprietaire'])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        // Si l'utilisateur a le rôle proprietaire, il ne voit que ses biens
        elseif ($user->hasRole('proprietaire')) {
            $biens = Bien::with(['category', 'mandat'])
                ->where('proprietaire_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        // Sinon, il voit tous les biens disponibles seulement (catalogue public)
        else {
            $biens = Bien::with(['category', 'mandat'])
                ->where('status', 'disponible')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return Inertia::render('Biens/Index', [
            'biens' => $biens,
            'userRoles' => $user->roles->pluck('name'),
        ]);
    }

    // GET /biens/create
    public function create()
    {
        $categories = Categorie::all();
        return Inertia::render('Biens/Create', [
            'categories' => $categories
        ]);
    }

    // POST /biens
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'property_title' => 'required|file',
            'description' => 'nullable|string',
            'image' => 'required|image',
            'rooms' => 'nullable|integer|min:0',
            'floors' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'superficy' => 'required|numeric|min:1',
            'price' => 'required|numeric|min:1',
            'categorie_id' => 'required|exists:categories,id',

            // Données du mandat
            'type_mandat' => 'required|in:vente,gestion_locative',
            'type_mandat_vente' => 'nullable|in:exclusif,simple,semi_exclusif',
            'conditions_particulieres' => 'nullable|string',
        ]);

        $user = auth()->user();

        // Validation conditionnelle : si type_mandat = 'vente', type_mandat_vente est requis
        if ($validated['type_mandat'] === 'vente' && empty($validated['type_mandat_vente'])) {
            return back()->withErrors([
                'type_mandat_vente' => 'Le type de mandat de vente est requis pour un mandat de vente.'
            ])->withInput();
        }

        // Utiliser une transaction pour s'assurer que tout est créé ou rien
        DB::beginTransaction();

        try {
            // Préparer les données du bien
            $bienData = [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? '',
                'rooms' => $validated['rooms'] ?? 0,
                'floors' => $validated['floors'] ?? 0,
                'bathrooms' => $validated['bathrooms'] ?? 0,
                'city' => $validated['city'],
                'address' => $validated['address'],
                'superficy' => $validated['superficy'],
                'price' => $validated['price'],
                'status' => 'en_validation', // Statut par défaut - en attente de validation
                'categorie_id' => $validated['categorie_id'],
                'proprietaire_id' => $user->id,
            ];

            // Gérer l'upload de l'image
            if ($request->hasFile('image')) {
                $bienData['image'] = $request->file('image')->store('biens', 'public');
            }

            // Gérer l'upload du document
            if ($request->hasFile('property_title')) {
                $bienData['property_title'] = $request->file('property_title')->store('documents', 'public');
            }

            // Créer le bien
            $bien = Bien::create($bienData);

            // Calculer la commission automatiquement
            $commissionMontant = ($validated['price'] * self::COMMISSION_PERCENTAGE) / 100;

            // Dates automatiques du mandat : aujourd'hui + 1 an
            $dateDebut = now()->format('Y-m-d');
            $dateFin = now()->addYear()->format('Y-m-d');

            // Créer le mandat associé
            $mandatData = [
                'bien_id' => $bien->id,
                'type_mandat' => $validated['type_mandat'],
                'type_mandat_vente' => $validated['type_mandat_vente'] ?? null,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'commission_pourcentage' => self::COMMISSION_PERCENTAGE,
                'commission_montant' => $commissionMontant,
                'conditions_particulieres' => $validated['conditions_particulieres'] ?? '',
                'statut' => 'en_attente', // En attente de validation du bien
            ];

            Mandat::create($mandatData);

            // Attribuer automatiquement le rôle 'proprietaire' si l'utilisateur ne l'a pas déjà
            if (!$user->hasRole('proprietaire') && !$user->hasRole('admin')) {
                $proprietaireRole = Role::firstOrCreate(['name' => 'proprietaire']);
                $user->assignRole('proprietaire');
            }

            DB::commit();

            $typeMessage = $validated['type_mandat_vente'] ?
                $this->getTypeMandatVenteLabel($validated['type_mandat_vente']) :
                'Mandat de ' . ucfirst($validated['type_mandat']);

            return redirect()->route('biens.index')->with('success',
                'Bien immobilier soumis avec succès avec un ' . $typeMessage . '. ' .
                'Il sera visible une fois validé par l\'administration. ' .
                'Commission calculée automatiquement à ' . self::COMMISSION_PERCENTAGE . '% (' .
                number_format($commissionMontant, 0, ',', ' ') . ' FCFA).'
            );

        } catch (\Exception $e) {
            DB::rollback();

            // Supprimer les fichiers uploadés en cas d'erreur
            if (isset($bienData['image']) && Storage::disk('public')->exists($bienData['image'])) {
                Storage::disk('public')->delete($bienData['image']);
            }
            if (isset($bienData['property_title']) && Storage::disk('public')->exists($bienData['property_title'])) {
                Storage::disk('public')->delete($bienData['property_title']);
            }

            throw $e;
        }
    }

    // Méthode utilitaire pour obtenir le libellé du type de mandat de vente
    private function getTypeMandatVenteLabel($type)
    {
        $labels = [
            'exclusif' => 'Mandat Exclusif',
            'simple' => 'Mandat Simple',
            'semi_exclusif' => 'Mandat Semi-Exclusif'
        ];

        return $labels[$type] ?? $type;
    }

    // POST /biens/{bien}/valider - Méthode pour valider un bien
    public function valider(Bien $bien)
    {
        $user = auth()->user();

        // Seuls les admins peuvent valider
        if (!$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à valider des biens.');
        }

        // Vérifier que le bien est en cours de validation
        if ($bien->status !== 'en_validation') {
            return redirect()->back()->with('error', 'Ce bien ne peut pas être validé car il n\'est pas en cours de validation.');
        }

        DB::beginTransaction();

        try {
            // Mettre à jour le statut du bien
            $bien->update([
                'status' => 'disponible',
                'validated_at' => now(),
                'validated_by' => $user->id,
            ]);

            // Activer le mandat associé
            if ($bien->mandat) {
                $bien->mandat->update([
                    'statut' => 'actif'
                ]);
            }

            DB::commit();

            return redirect()->route('biens.index')->with('success', 'Bien "' . $bien->title . '" validé avec succès. Il est maintenant visible publiquement.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur lors de la validation du bien:', [
                'bien_id' => $bien->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Erreur lors de la validation du bien.');
        }
    }

    // POST /biens/{bien}/rejeter - Méthode pour rejeter un bien
    public function rejeter(Request $request, Bien $bien)
    {
        $user = auth()->user();

        // Seuls les admins peuvent rejeter
        if (!$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à rejeter des biens.');
        }

        $request->validate([
            'motif_rejet' => 'required|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            // Mettre à jour le statut du bien
            $bien->update([
                'status' => 'rejete',
                'motif_rejet' => $request->motif_rejet,
                'rejected_at' => now(),
                'rejected_by' => $user->id,
            ]);

            // Désactiver le mandat associé
            if ($bien->mandat) {
                $bien->mandat->update([
                    'statut' => 'rejete'
                ]);
            }

            DB::commit();

            return redirect()->route('biens.index')->with('success', 'Bien "' . $bien->title . '" rejeté. Le propriétaire pourra le modifier et le soumettre à nouveau.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors du rejet du bien.');
        }
    }

    // GET /biens/{bien}
    public function show(Bien $bien)
    {
        $categories = Categorie::all();
        return Inertia::render('Biens/Show', [
            'bien' => $bien->load(['category', 'mandat', 'proprietaire']),
            'categories' => $categories,
            'userRoles' => auth()->user()->roles->pluck('name'),
        ]);
    }

    // GET /biens/{bien}/edit
    public function edit(Bien $bien)
    {
        $user = auth()->user();

        // Vérification des permissions : seul le propriétaire du bien ou un admin peut modifier
        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier ce bien.');
        }

        $categories = Categorie::all();

        return Inertia::render('Biens/Edit', [
            'bien' => $bien->load(['category', 'mandat']),
            'categories' => $categories
        ]);
    }

    // PUT /biens/{bien}
    public function update(Request $request, Bien $bien)
    {
        $user = auth()->user();

        // Vérification des permissions
        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier ce bien.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'property_title' => 'nullable|file',
            'description' => 'nullable|string',
            'image' => 'nullable|image',
            'rooms' => 'nullable|integer|min:0',
            'floors' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'superficy' => 'required|numeric|min:1',
            'price' => 'required|numeric|min:1',
            'status' => 'nullable|in:disponible,loue,vendu,reserve',
            'categorie_id' => 'required|exists:categories,id',

            // Données du mandat (optionnelles pour la mise à jour)
            'type_mandat' => 'nullable|in:vente,gestion_locative',
            'type_mandat_vente' => 'nullable|in:exclusif,simple,semi_exclusif',
            'conditions_particulieres' => 'nullable|string',
        ]);

        // Validation conditionnelle pour la mise à jour
        if (isset($validated['type_mandat']) && $validated['type_mandat'] === 'vente' && empty($validated['type_mandat_vente'])) {
            return back()->withErrors([
                'type_mandat_vente' => 'Le type de mandat de vente est requis pour un mandat de vente.'
            ])->withInput();
        }

        DB::beginTransaction();

        try {
            $data = [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? $bien->description,
                'rooms' => $validated['rooms'] ?? $bien->rooms,
                'floors' => $validated['floors'] ?? $bien->floors,
                'bathrooms' => $validated['bathrooms'] ?? $bien->bathrooms,
                'city' => $validated['city'],
                'address' => $validated['address'],
                'superficy' => $validated['superficy'],
                'price' => $validated['price'],
                'categorie_id' => $validated['categorie_id'],
            ];

            // Si c'est un admin qui modifie, il peut changer le statut
            if ($user->hasRole('admin') && isset($validated['status'])) {
                $data['status'] = $validated['status'];
            }
            // Si c'est le propriétaire qui modifie un bien rejeté, remettre en validation
            elseif ($bien->status === 'rejete' && $bien->proprietaire_id === $user->id) {
                $data['status'] = 'en_validation';
                $data['motif_rejet'] = null;
                $data['rejected_at'] = null;
                $data['rejected_by'] = null;
            }

            // Gestion du document
            if ($request->hasFile('property_title')) {
                if ($bien->property_title && Storage::disk('public')->exists($bien->property_title)) {
                    Storage::disk('public')->delete($bien->property_title);
                }
                $data['property_title'] = $request->file('property_title')->store('documents', 'public');
            }

            // Gestion de l'image
            if ($request->hasFile('image')) {
                if ($bien->image && Storage::disk('public')->exists($bien->image)) {
                    Storage::disk('public')->delete($bien->image);
                }
                $data['image'] = $request->file('image')->store('biens', 'public');
            }

            $bien->update($data);

            // Mettre à jour le mandat si les données sont fournies
            $mandat = $bien->mandat;
            if ($mandat) {
                $mandatData = [];

                // Recalculer la commission si le prix a changé
                if ($validated['price'] != $bien->getOriginal('price')) {
                    $mandatData['commission_montant'] = ($validated['price'] * self::COMMISSION_PERCENTAGE) / 100;
                }

                // Mettre à jour les autres champs du mandat si fournis
                if (isset($validated['type_mandat'])) {
                    $mandatData['type_mandat'] = $validated['type_mandat'];
                }

                if (isset($validated['type_mandat_vente'])) {
                    $mandatData['type_mandat_vente'] = $validated['type_mandat_vente'];
                }

                if (isset($validated['conditions_particulieres'])) {
                    $mandatData['conditions_particulieres'] = $validated['conditions_particulieres'];
                }

                // Si le bien repasse en validation, le mandat aussi
                if (isset($data['status']) && $data['status'] === 'en_validation') {
                    $mandatData['statut'] = 'en_attente';
                }

                if (!empty($mandatData)) {
                    $mandat->update($mandatData);
                }
            }

            DB::commit();

            return redirect()->route('biens.index')->with('success', 'Bien immobilier et mandat modifiés avec succès');

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    // DELETE /biens/{bien}
    public function destroy(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer ce bien.');
        }

        DB::beginTransaction();

        try {
            // Supprimer les fichiers associés
            if ($bien->image && Storage::disk('public')->exists($bien->image)) {
                Storage::disk('public')->delete($bien->image);
            }

            if ($bien->property_title && Storage::disk('public')->exists($bien->property_title)) {
                Storage::disk('public')->delete($bien->property_title);
            }

            // Le mandat sera supprimé automatiquement grâce à la contrainte de clé étrangère
            // ou vous pouvez l'expliciter si nécessaire
            if ($bien->mandat) {
                $bien->mandat->delete();
            }

            $bien->delete();

            DB::commit();

            return redirect()->route('biens.index')->with('success', 'Bien immobilier et mandat associé supprimés avec succès');

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    // Méthode pour obtenir les biens publics (catalogue)
    public function catalogue()
    {
        $biens = Bien::with(['category', 'mandat'])->where('status', 'disponible')->get();

        return Inertia::render('Layout', [
            'biens' => $biens,
            'userRoles' => auth()->user()->roles->pluck('name'),
        ]);
    }
}
