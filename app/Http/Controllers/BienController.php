<?php

namespace App\Http\Controllers;

use App\Models\Appartement;
use App\Models\Bien;
use App\Models\Commission;
use App\Models\Categorie;
use App\Models\Location;
use App\Models\Mandat;
use App\Models\Paiement;
use App\Models\User;
use App\Services\ElectronicSignatureService;
use App\Services\MandatPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Services\BienMatchingService;

class BienController extends Controller
{
    protected $mandatPdfService;
    protected $signatureService;
    protected $matchingService;
    public function __construct(MandatPdfService $mandatPdfService, ElectronicSignatureService $signatureService,    BienMatchingService $matchingService
    )
    {
        $this->mandatPdfService = $mandatPdfService;
        $this->signatureService = $signatureService;
        $this->matchingService = $matchingService;

    }

    const COMMISSION_PERCENTAGE = 10;

    public function create()
    {
        $categories = Categorie::all();
        return Inertia::render('Biens/Create', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        Log::info('🔥 DÉBUT store() - Données reçues', [
            'title' => $request->input('title'),
            'type_mandat' => $request->input('type_mandat'),
            'categorie_id' => $request->input('categorie_id'),
        ]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'property_title' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'rooms' => 'nullable|integer',
            'floors' => 'nullable|integer',
            'bathrooms' => 'nullable|integer',
            'kitchens' => 'nullable|integer',
            'living_rooms' => 'nullable|integer',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'superficy' => 'required|numeric',
            'price' => 'required|numeric',
            'categorie_id' => 'required|exists:categories,id',
            'type_mandat' => 'required|in:vente,gestion_locative',
            'type_mandat_vente' => 'nullable|in:exclusif,simple,semi_exclusif',
            'conditions_particulieres' => 'nullable|string',
        ]);

        Log::info('✅ Validation réussie');

        DB::beginTransaction();

        try {
            // 1. Sauvegarder le document commercial
            $propertyTitlePath = $request->file('property_title')->store('documents', 'public');
            Log::info('✅ Document sauvegardé', ['path' => $propertyTitlePath]);

            // 2. Déterminer si c'est un terrain
            $categorie = Categorie::find($validated['categorie_id']);
            $estTerrain = $categorie && strtolower($categorie->name) === 'terrain';

            Log::info('📋 Catégorie détectée', [
                'categorie_id' => $validated['categorie_id'],
                'categorie_name' => $categorie?->name,
                'est_terrain' => $estTerrain
            ]);

            // Pour les terrains, on met 0 au lieu de null
            $rooms = $estTerrain ? 0 : ($validated['rooms'] ?? 0);
            $floors = $estTerrain ? 0 : ($validated['floors'] ?? 0);
            $bathrooms = $estTerrain ? 0 : ($validated['bathrooms'] ?? 0);
            $kitchens = $estTerrain ? 0 : ($validated['kitchens'] ?? 0);
            $living_rooms = $estTerrain ? 0 : ($validated['living_rooms'] ?? 0);

            // 3. Créer le bien avec statut EN_VALIDATION
            $bien = Bien::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'property_title' => $propertyTitlePath,
                'rooms' => $rooms,
                'floors' => $floors,
                'bathrooms' => $bathrooms,
                'kitchens' => $kitchens,
                'living_rooms' => $living_rooms,
                'city' => $validated['city'],
                'address' => $validated['address'],
                'superficy' => $validated['superficy'],
                'price' => $validated['price'],
                'categorie_id' => $validated['categorie_id'],
                'status' => 'en_validation',
                'proprietaire_id' => auth()->id(),
            ]);

            Log::info('✅ Bien créé', [
                'bien_id' => $bien->id,
                'title' => $bien->title,
                'status' => $bien->status
            ]);

            // 4. Créer le mandat avec statut EN_ATTENTE
            Log::info('🔄 Tentative création mandat...', [
                'bien_id' => $bien->id,
                'type_mandat' => $validated['type_mandat'],
                'type_mandat_vente' => $validated['type_mandat_vente'] ?? null,
            ]);

            $mandatData = [
                'type_mandat' => $validated['type_mandat'],
                'type_mandat_vente' => $validated['type_mandat_vente'] ?? null,
                'conditions_particulieres' => $validated['conditions_particulieres'] ?? null,
                'date_debut' => now(),
                'date_fin' => now()->addYear(), // ✅ AJOUT : date de fin obligatoire
                'statut' => 'en_attente',
                'commission_pourcentage' => 10.00, // ✅ AJOUT : commission par défaut
                'commission_fixe' => 0, // ✅ AJOUT : commission fixe
            ];

            Log::info('📝 Données mandat à créer', $mandatData);

            $mandat = $bien->mandat()->create($mandatData);

            Log::info('✅ Mandat créé avec succès!', [
                'mandat_id' => $mandat->id,
                'bien_id' => $mandat->bien_id,
                'statut' => $mandat->statut,
                'type_mandat' => $mandat->type_mandat
            ]);

            // 5. Sauvegarder les images GÉNÉRALES du bien
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $labels = $request->input('images_labels', []);

                Log::info('📸 Traitement images', ['nombre' => count($images)]);

                foreach ($images as $index => $image) {
                    $path = $image->store('biens', 'public');

                    $bien->images()->create([
                        'chemin_image' => $path,
                        'libelle' => $labels[$index] ?? null,
                        'appartement_id' => null,
                    ]);
                }

                Log::info('✅ Images sauvegardées');
            }

            // 6. Créer les appartements et leurs images
            $categorieAppartement = Categorie::find($validated['categorie_id']);

            if ($categorieAppartement &&
                strtolower($categorieAppartement->name) === 'appartement' &&
                $request->has('appartements')) {

                Log::info('🏢 Création appartements...');

                $appartementsData = json_decode($request->input('appartements'), true);

                foreach ($appartementsData as $index => $appartementData) {
                    $appartement = $bien->appartements()->create([
                        'numero' => $appartementData['numero'],
                        'etage' => $appartementData['etage'],
                        'superficie' => $appartementData['superficie'] ?? null,
                        'salons' => $appartementData['salons'] ?? null,
                        'chambres' => $appartementData['chambres'] ?? null,
                        'salles_bain' => $appartementData['salles_bain'] ?? null,
                        'cuisines' => $appartementData['cuisines'] ?? null,
                        'statut' => 'disponible',
                        'description' => $appartementData['description'] ?? null,
                    ]);

                    Log::info('✅ Appartement créé', [
                        'appartement_id' => $appartement->id,
                        'numero' => $appartement->numero
                    ]);

                    // Sauvegarder les images de l'appartement
                    if ($request->has('appartements_images')) {
                        $appartementImagesData = $request->input('appartements_images', []);
                        $appartementLabelsData = $request->input('appartements_images_labels', []);

                        foreach ($appartementImagesData as $imgIndex => $imgData) {
                            if (isset($imgData['appartement_index']) && $imgData['appartement_index'] == $index) {
                                if (isset($imgData['file'])) {
                                    $path = $imgData['file']->store('appartements', 'public');

                                    $label = null;
                                    foreach ($appartementLabelsData as $labelData) {
                                        if (isset($labelData['appartement_index']) &&
                                            $labelData['appartement_index'] == $index) {
                                            $label = $labelData['label'] ?? null;
                                            break;
                                        }
                                    }

                                    $bien->images()->create([
                                        'chemin_image' => $path,
                                        'libelle' => $label,
                                        'appartement_id' => $appartement->id,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            // ✅ VÉRIFICATION FINALE avant commit
            $mandatVerif = Mandat::where('bien_id', $bien->id)->first();

            if (!$mandatVerif) {
                Log::error('❌ ERREUR : Mandat non trouvé après création!', [
                    'bien_id' => $bien->id
                ]);
                throw new \Exception('Le mandat n\'a pas été créé correctement');
            }

            Log::info('✅ Vérification finale OK', [
                'bien_id' => $bien->id,
                'mandat_id' => $mandatVerif->id
            ]);

            DB::commit();

            Log::info('🎉 Transaction COMMIT réussie');

            return redirect()->route('biens.show', $bien->id)
                ->with('success', 'Bien créé avec succès! Il sera disponible après validation par l\'administrateur.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ ERREUR CRÉATION BIEN', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($propertyTitlePath)) {
                Storage::disk('public')->delete($propertyTitlePath);
            }

            return back()->withErrors(['error' => 'Erreur lors de la création du bien: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Retourne le label de l'étage
     */
    private function getEtageLabelFor($etage)
    {
        $labels = [
            0 => 'Rez-de-chaussée',
            1 => '1er étage',
            2 => '2ème étage',
            3 => '3ème étage',
        ];

        return $labels[$etage] ?? $etage . 'ème étage';
    }

    public function valider(Bien $bien)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Non autorisé');
        }

        if ($bien->status !== 'en_validation') {
            return redirect()->back()->with('error', 'Ce bien ne peut pas être validé.');
        }

        DB::beginTransaction();

        try {
            if (!$bien->mandat) {
                throw new \Exception('Aucun mandat trouvé pour ce bien');
            }

            Log::info('🔍 Début validation bien', [
                'bien_id' => $bien->id,
                'mandat_id' => $bien->mandat->id,
                'mandat_statut_avant' => $bien->mandat->statut
            ]);

            // ✅ 1. Mettre à jour le statut du mandat à ACTIF
            $bien->mandat->update(['statut' => 'actif']);

            // ✅ 2. Générer le PDF du mandat
            try {
                $pdfPath = $this->mandatPdfService->generatePdf($bien->mandat);

                if (!$pdfPath) {
                    throw new \Exception('La génération du PDF a échoué');
                }

                Log::info('✅ PDF généré avec succès', ['pdf_path' => $pdfPath]);

            } catch (\Exception $e) {
                Log::error('❌ Erreur génération PDF', [
                    'message' => $e->getMessage()
                ]);
                throw new \Exception('Erreur lors de la génération du PDF : ' . $e->getMessage());
            }

            // ✅ 3. Le bien RESTE EN_VALIDATION jusqu'à signature complète
            $bien->update([
                'validated_at' => now(),
                'validated_by' => $user->id,
                // status reste 'en_validation'
            ]);

            DB::commit();

            return redirect()->route('biens.index')->with('success',
                'Bien validé avec succès. Le mandat est maintenant actif et prêt à être signé.'
            );

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('❌ ERREUR VALIDATION BIEN', [
                'bien_id' => $bien->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return redirect()->back()->with('error',
                'Erreur lors de la validation : ' . $e->getMessage()
            );
        }
    }

    public function rejeter(Request $request, Bien $bien)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin')) {
            abort(403);
        }

        $request->validate([
            'motif_rejet' => 'required|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $bien->update([
                'status' => 'rejete',
                'motif_rejet' => $request->motif_rejet,
                'rejected_at' => now(),
                'rejected_by' => $user->id,
            ]);

            if ($bien->mandat) {
                $bien->mandat->update(['statut' => 'rejete']);
            }

            DB::commit();

            return redirect()->route('biens.index')->with('success', 'Bien rejeté.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors du rejet.');
        }
    }

    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $biens = Bien::with(['category', 'mandat', 'proprietaire', 'images', 'appartements'])
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($user->hasRole('proprietaire')) {
            $biens = Bien::with(['category', 'mandat', 'images', 'appartements'])
                ->where('proprietaire_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $biens = Bien::with(['category', 'mandat', 'images', 'appartements'])
                ->where('status', 'disponible')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // ✅ CORRECTION : Vérifier que $biens existe avant de traiter
        if ($biens) {
            $biens->each(function($bien) {
                if ($bien->category && strtolower($bien->category->name) === 'appartement') {
                    $bien->occupation_stats = $bien->getOccupationStats();
                }

                // ✅ Formater les images
                $bien->image = $bien->images->first() ? $bien->images->first()->chemin_image : null;

                // ✅ CORRECTION : Ajouter l'URL du titre de propriété avec vérification NULL
                $bien->property_title_url = !empty($bien->property_title)
                    ? asset('storage/' . $bien->property_title)
                    : null;
            });
        }

        return Inertia::render('Biens/Index', [
            'biens' => $biens ?? [],
            'userRoles' => $user->roles->pluck('name')->toArray(),
            'isAdmin' => $user->hasRole('admin'),
            'isProprietaire' => $user->hasRole('proprietaire'),
        ]);
    }
    public function show(Bien $bien)
    {
        // Charger toutes les relations nécessaires
        $bien->load([
            'category',
            'mandat', // ✅ S'assurer que le mandat est chargé
            'proprietaire',
            'images',
            'appartements' => function($query) {
                $query->orderBy('etage', 'asc');
            },
            'appartements.locationActive.client'
        ]);

        // ✅ Ajouter les informations de signature
        if ($bien->mandat) {
            $bien->mandat->signature_stats = [
                'proprietaire_signed' => $bien->mandat->isSignedByProprietaire(),
                'agence_signed' => $bien->mandat->isSignedByAgence(),
                'fully_signed' => $bien->mandat->isFullySigned(),
                'signature_status' => $bien->mandat->signature_status,
            ];
        }

        return Inertia::render('Biens/Show', [
            'bien' => $bien,
            'userRoles' => auth()->user()->roles->pluck('name'),
            'canAccessPdf' => $this->canUserAccessPdf($bien, auth()->user()), // ✅ Calculer côté serveur
        ]);
    }

// ✅ Méthode helper pour vérifier l'accès PDF
    private function canUserAccessPdf(Bien $bien, $user)
    {
        if (!$bien->mandat) {
            return false;
        }

        if ($bien->mandat->statut !== 'actif' && $bien->mandat->signature_status !== 'entierement_signe') {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('proprietaire') && $bien->proprietaire_id === $user->id) {
            return true;
        }

        return false;
    }

    // GET /biens/{bien}/edit
    public function edit(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        $bien->load(['category', 'mandat', 'images']);
        $categories = Categorie::all();

        return Inertia::render('Biens/Edit', [
            'bien' => $bien,
            'categories' => $categories
        ]);
    }

    // PUT /biens/{bien}
    public function update(Request $request, Bien $bien)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'property_title' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'deleted_images.*' => 'nullable|integer',
            'rooms' => 'nullable|integer',
            'floors' => 'nullable|integer',
            'bathrooms' => 'nullable|integer',
            'kitchens' => 'nullable|integer',  // ✅ NOUVEAU
            'living_rooms' => 'nullable|integer',  // ✅ NOUVEAU
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'superficy' => 'required|numeric',
            'price' => 'required|numeric',
            'categorie_id' => 'required|exists:categories,id',
            'status' => 'nullable|in:disponible,reserve,vendu,loue',
            'type_mandat' => 'required|in:vente,gestion_locative',
            'type_mandat_vente' => 'nullable|in:exclusif,simple,semi_exclusif',
            'conditions_particulieres' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            if ($request->hasFile('property_title')) {
                if ($bien->property_title && Storage::disk('public')->exists($bien->property_title)) {
                    Storage::disk('public')->delete($bien->property_title);
                }
                $validated['property_title'] = $request->file('property_title')->store('documents', 'public');
            }

            $bien->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'property_title' => $validated['property_title'] ?? $bien->property_title,
                'rooms' => $validated['rooms'],
                'floors' => $validated['floors'],
                'bathrooms' => $validated['bathrooms'],
                'kitchens' => $validated['kitchens'] ?? null,  // ✅ NOUVEAU
                'living_rooms' => $validated['living_rooms'] ?? null,  // ✅ NOUVEAU
                'city' => $validated['city'],
                'address' => $validated['address'],
                'superficy' => $validated['superficy'],
                'price' => $validated['price'],
                'categorie_id' => $validated['categorie_id'],
                'status' => $validated['status'] ?? $bien->status,
            ]);

            $bien->mandat()->updateOrCreate(
                ['bien_id' => $bien->id],
                [
                    'type_mandat' => $validated['type_mandat'],
                    'type_mandat_vente' => $validated['type_mandat_vente'] ?? null,
                    'conditions_particulieres' => $validated['conditions_particulieres'] ?? null,
                ]
            );

            // Gérer les suppressions et ajouts d'images...
            // (code identique au store)

            DB::commit();

            return redirect()->route('biens.show', $bien->id)
                ->with('success', 'Bien mis à jour avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour bien: ' . $e->getMessage());

            return back()
                ->withErrors(['error' => 'Erreur lors de la mise à jour du bien: ' . $e->getMessage()])
                ->withInput();
        }
    }
    // DELETE /biens/{bien}
    public function destroy(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        DB::beginTransaction();

        try {
            // Supprimer PDF mandat
            if ($bien->mandat && $bien->mandat->pdf_path && Storage::disk('public')->exists($bien->mandat->pdf_path)) {
                Storage::disk('public')->delete($bien->mandat->pdf_path);
            }

            // Supprimer toutes les images
            foreach ($bien->images as $image) {
                if (Storage::disk('public')->exists($image->chemin_image)) {
                    Storage::disk('public')->delete($image->chemin_image);
                }
                $image->delete();
            }

            // Supprimer document
            if ($bien->property_title && Storage::disk('public')->exists($bien->property_title)) {
                Storage::disk('public')->delete($bien->property_title);
            }

            if ($bien->mandat) {
                $bien->mandat->delete();
            }

            $bien->delete();

            DB::commit();

            return redirect()->route('biens.index')->with('success', 'Bien supprimé avec succès');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Erreur lors de la suppression');
        }
    }

    // Méthodes PDF et signature (identiques à votre version)
    public function downloadMandatPdf(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        if (!$bien->mandat) {
            abort(404);
        }

        $response = $this->mandatPdfService->downloadPdf($bien->mandat);

        if (!$response) {
            return redirect()->back()->with('error', 'Impossible de télécharger le PDF.');
        }

        return $response;
    }

    public function previewMandatPdf(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        if (!$bien->mandat) {
            abort(404);
        }

        $response = $this->mandatPdfService->previewMandatPdf($bien->mandat);

        if (!$response) {
            return redirect()->back()->with('error', 'Impossible de prévisualiser le PDF.');
        }

        return $response;
    }

    public function regenerateMandatPdf(Bien $bien)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin')) {
            abort(403);
        }

        if (!$bien->mandat) {
            abort(404);
        }

        try {
            $pdfPath = $this->mandatPdfService->regeneratePdf($bien->mandat);

            if ($pdfPath) {
                return redirect()->back()->with('success', 'PDF régénéré avec succès.');
            } else {
                return redirect()->back()->with('error', 'Erreur lors de la régénération.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la régénération du PDF.');
        }
    }

    public function showSignaturePage(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        if (!$bien->mandat) {
            abort(404);
        }

        if ($bien->mandat->statut !== 'actif') {
            return redirect()->route('biens.index')
                ->with('error', 'Ce mandat n\'est pas actif.');
        }

        $signatureStats = $this->signatureService->getSignatureStats($bien->mandat);

        return Inertia::render('Biens/Signature', [
            'bien' => $bien->load(['category', 'mandat', 'proprietaire']),
            'signatureStats' => $signatureStats,
            'userRoles' => $user->roles->pluck('name'),
            'isProprietaire' => $bien->proprietaire_id === $user->id,
            'isAdmin' => $user->hasRole('admin'),
        ]);
    }

    public function signByProprietaire(Request $request, Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        if (!$bien->mandat) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun mandat trouvé'
            ], 404);
        }

        if (!$bien->mandat->canBeSignedByProprietaire()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce mandat ne peut pas être signé actuellement.'
            ], 400);
        }

        $request->validate([
            'signature_data' => 'required|string',
        ]);

        try {
            // ✅ Signer et recharger le mandat
            $mandat = $this->signatureService->signByProprietaire($bien->mandat, $request->signature_data);

            // ✅ CORRECTION : Retourner uniquement JSON (pas de redirect)
            return response()->json([
                'success' => true,
                'message' => 'Signature enregistrée avec succès !',
                'signature_stats' => $this->signatureService->getSignatureStats($mandat),
                'bien_status' => $bien->fresh()->status,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur signature propriétaire', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la signature : ' . $e->getMessage()
            ], 500);
        }
    }

    public function signByAgence(Request $request, Bien $bien)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        if (!$bien->mandat) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun mandat trouvé'
            ], 404);
        }

        if (!$bien->mandat->canBeSignedByAgence()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce mandat ne peut pas être signé actuellement.'
            ], 400);
        }

        $request->validate([
            'signature_data' => 'required|string',
        ]);

        try {
            // ✅ Signer et recharger le mandat
            $mandat = $this->signatureService->signByAgence($bien->mandat, $request->signature_data);

            // ✅ CORRECTION : Retourner uniquement JSON (pas de redirect)
            return response()->json([
                'success' => true,
                'message' => 'Signature de l\'agence enregistrée avec succès !',
                'signature_stats' => $this->signatureService->getSignatureStats($mandat),
                'bien_status' => $bien->fresh()->status,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur signature agence', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelSignature(Request $request, Bien $bien, $signatoryType)
    {
        $user = auth()->user();

        if ($signatoryType === 'proprietaire' && $bien->proprietaire_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        if ($signatoryType === 'agence' && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        if (!$bien->mandat) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun mandat trouvé'
            ], 404);
        }

        try {
            $this->signatureService->cancelSignature($bien->mandat, $signatoryType);

            // ✅ CORRECTION : Retourner uniquement JSON (pas de redirect)
            return response()->json([
                'success' => true,
                'message' => 'Signature annulée avec succès.',
                'signature_stats' => $this->signatureService->getSignatureStats($bien->mandat->fresh()),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur annulation signature', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }


    public function getSignatureStatus(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        if (!$bien->mandat) {
            abort(404);
        }

        return response()->json([
            'signature_stats' => $this->signatureService->getSignatureStats($bien->mandat)
        ]);
    }

    public function downloadSignedMandatPdf(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        if (!$bien->mandat) {
            abort(404);
        }

        $response = $this->signatureService->downloadSignedPdf($bien->mandat);

        if (!$response) {
            return redirect()->back()->with('error', 'Impossible de télécharger le PDF.');
        }

        return $response;
    }

    public function previewSignedMandatPdf(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        if (!$bien->mandat) {
            abort(404);
        }

        $response = $this->signatureService->previewSignedPdf($bien->mandat);

        if (!$response) {
            return redirect()->back()->with('error', 'Impossible de prévisualiser le PDF.');
        }

        return $response;
    }

    public function dashboardProprietaire($proprietaireId = null)
    {
        $user = auth()->user();

        // Déterminer quel propriétaire afficher
        if ($proprietaireId && $user->hasRole('admin')) {
            $proprietaire = User::findOrFail($proprietaireId);
        } elseif ($user->hasRole('proprietaire')) {
            $proprietaire = $user;
        } else {
            abort(403, 'Accès non autorisé');
        }

        Log::info('═══════════════════════════════════════════════════');
        Log::info('🔍 DASHBOARD PROPRIETAIRE - DEBUG COMPLET');
        Log::info('Propriétaire ID: ' . $proprietaire->id);
        Log::info('Propriétaire Name: ' . $proprietaire->name);
        Log::info('═══════════════════════════════════════════════════');

        // Charger les biens avec toutes les relations
        $biens = Bien::where('proprietaire_id', $proprietaire->id)
            ->with([
                'category',
                'mandat',
                'appartements.locationActive.client',
                'reservations.paiements',
                'reservations.location.paiements',
                'reservations.vente.paiements'
            ])
            ->get();

        Log::info('📦 Biens chargés: ' . $biens->count());

        // Initialiser les stats
        $stats_globales = [
            'total_biens' => $biens->count(),
            'biens_gestion_locative' => 0,
            'biens_vente' => 0,
            'total_appartements' => 0,
            'appartements_loues' => 0,
            'recettes_totales' => 0,
            'recettes_mois_courant' => 0,
            'loyers_en_attente' => 0,
        ];

        // Récupérer TOUS les IDs de biens de ce propriétaire
        $biensIds = $biens->pluck('id')->toArray();
        Log::info('🔑 IDs des biens: ' . json_encode($biensIds));

        // DEBUG: Compter les paiements disponibles
        $totalPaiementsLocation = Paiement::whereHas('location.reservation', function($q) use ($biensIds) {
            $q->whereIn('bien_id', $biensIds);
        })->where('statut', 'reussi')->count();

        $totalPaiementsVente = Paiement::whereHas('vente.reservation', function($q) use ($biensIds) {
            $q->whereIn('bien_id', $biensIds);
        })->where('statut', 'reussi')->count();

        Log::info("💰 Paiements de LOCATION trouvés: {$totalPaiementsLocation}");
        Log::info("💰 Paiements de VENTE trouvés: {$totalPaiementsVente}");

        // Calculer les recettes de LOCATION
        $paiementsLocation = Paiement::whereHas('location.reservation', function($q) use ($biensIds) {
            $q->whereIn('bien_id', $biensIds);
        })->where('statut', 'reussi')->get();

        $recettesLocation = $paiementsLocation->sum('montant_paye') * 0.90;
        $recettesLocationMois = $paiementsLocation->filter(function($p) {
                return \Carbon\Carbon::parse($p->date_transaction)->month === now()->month
                    && \Carbon\Carbon::parse($p->date_transaction)->year === now()->year;
            })->sum('montant_paye') * 0.90;

        Log::info("💵 Recettes LOCATION totales (90%): {$recettesLocation}");
        Log::info("💵 Recettes LOCATION ce mois (90%): {$recettesLocationMois}");

        // Calculer les recettes de VENTE
        $paiementsVente = Paiement::whereHas('vente.reservation', function($q) use ($biensIds) {
            $q->whereIn('bien_id', $biensIds);
        })->where('statut', 'reussi')->get();

        $recettesVente = $paiementsVente->sum('montant_paye') * 0.90;
        $recettesVenteMois = $paiementsVente->filter(function($p) {
                return \Carbon\Carbon::parse($p->date_transaction)->month === now()->month
                    && \Carbon\Carbon::parse($p->date_transaction)->year === now()->year;
            })->sum('montant_paye') * 0.90;

        Log::info("💵 Recettes VENTE totales (90%): {$recettesVente}");
        Log::info("💵 Recettes VENTE ce mois (90%): {$recettesVenteMois}");

        // Additionner les recettes
        $stats_globales['recettes_totales'] = $recettesLocation + $recettesVente;
        $stats_globales['recettes_mois_courant'] = $recettesLocationMois + $recettesVenteMois;

        Log::info("💰 TOTAL RECETTES: {$stats_globales['recettes_totales']}");
        Log::info("💰 TOTAL CE MOIS: {$stats_globales['recettes_mois_courant']}");

        // Compter les biens par type
        foreach ($biens as $bien) {
            $mandat = $bien->mandat()->where('statut', 'actif')->first();
            if ($mandat) {
                if ($mandat->type_mandat === 'gestion_locative') {
                    $stats_globales['biens_gestion_locative']++;

                    // Compter les appartements
                    if ($bien->isImmeuble()) {
                        $appts = $bien->appartements;
                        $stats_globales['total_appartements'] += $appts->count();
                        $stats_globales['appartements_loues'] += $appts->where('statut', 'loue')->count();
                    }
                } elseif ($mandat->type_mandat === 'vente') {
                    $stats_globales['biens_vente']++;
                }
            }
        }

        // Loyers en attente
        $stats_globales['loyers_en_attente'] = Paiement::whereHas('location.reservation', function($q) use ($biensIds) {
            $q->whereIn('bien_id', $biensIds);
        })->whereIn('statut', ['en_attente', 'en_retard'])->sum('montant_restant');

        Log::info('📊 Stats finales: ' . json_encode($stats_globales));

        // Formater les biens pour le frontend
        $biensFormates = $biens->map(function ($bien) use ($biensIds) {
            $mandat = $bien->mandat()->where('statut', 'actif')->first();

            // Calculer les recettes pour CE bien spécifique
            $recettesTotal = 0;
            $recettesMois = 0;
            $loyersAttente = 0;

            if ($mandat) {
                if ($mandat->type_mandat === 'vente') {
                    $paiements = Paiement::whereHas('vente.reservation', function($q) use ($bien) {
                        $q->where('bien_id', $bien->id);
                    })->where('statut', 'reussi')->get();

                    $recettesTotal = $paiements->sum('montant_paye') * 0.90;
                    $recettesMois = $paiements->filter(function($p) {
                            return \Carbon\Carbon::parse($p->date_transaction)->month === now()->month;
                        })->sum('montant_paye') * 0.90;

                } elseif ($mandat->type_mandat === 'gestion_locative') {
                    $paiements = Paiement::whereHas('location.reservation', function($q) use ($bien) {
                        $q->where('bien_id', $bien->id);
                    })->where('statut', 'reussi')->get();

                    $recettesTotal = $paiements->sum('montant_paye') * 0.90;
                    $recettesMois = $paiements->filter(function($p) {
                            return \Carbon\Carbon::parse($p->date_transaction)->month === now()->month;
                        })->sum('montant_paye') * 0.90;

                    $loyersAttente = Paiement::whereHas('location.reservation', function($q) use ($bien) {
                        $q->where('bien_id', $bien->id);
                    })->whereIn('statut', ['en_attente', 'en_retard'])->sum('montant_restant');
                }
            }

            return [
                'id' => $bien->id,
                'title' => $bien->title,
                'address' => $bien->address,
                'city' => $bien->city,
                'type' => $bien->category ? $bien->category->name : 'N/A',
                'floors' => $bien->floors,
                'type_mandat' => $mandat ? $mandat->type_mandat : null,
                'type_mandat_label' => $mandat && $mandat->type_mandat === 'gestion_locative'
                    ? 'Gestion Locative'
                    : 'Vente',
                'date_fin_mandat' => $mandat && $mandat->date_fin
                    ? $mandat->date_fin->format('d/m/Y')
                    : null,
                'recettes' => [
                    'total' => $recettesTotal,
                    'mois_courant' => $recettesMois,
                ],
                'loyers_stats' => [
                    'en_attente' => $loyersAttente,
                ],
                'appartements_par_etage' => $this->formatAppartementsParEtage($bien),
            ];
        });

        Log::info('✅ Rendu Inertia avec ' . $biensFormates->count() . ' biens');

        return Inertia::render('Dashboard/Proprietaire', [
            'biens' => $biensFormates,
            'stats_globales' => $stats_globales,
        ]);
    }

    public function dashboardAdminGlobal()
    {
        $user = auth()->user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        Log::info('═══════════════════════════════════════════════════');
        Log::info('👑 DÉBUT DASHBOARD ADMIN GLOBAL - VERSION CORRIGÉE V3');
        Log::info('═══════════════════════════════════════════════════');

        // ═══════════════════════════════════════════════════════════
        // ÉTAPE 1 : CALCULER LES STATISTIQUES GLOBALES
        // ═══════════════════════════════════════════════════════════

        // ✅ Compter uniquement les appartements en gestion locative
        $totalAppartements = Appartement::whereHas('bien', function($q) {
            $q->whereHas('mandat', function($subq) {
                $subq->where('type_mandat', 'gestion_locative')
                    ->where('statut', 'actif');
            });
        })->count();

        $appartementsLoues = Appartement::whereHas('bien', function($q) {
            $q->whereHas('mandat', function($subq) {
                $subq->where('type_mandat', 'gestion_locative')
                    ->where('statut', 'actif');
            });
        })->where('statut', 'loue')->count();

        Log::info("📊 Appartements: {$appartementsLoues}/{$totalAppartements}");

        // ✅ Calculer les recettes depuis TOUS les paiements réussis
        $paiementsReussisTotal = Paiement::where('statut', 'reussi')->get();

        // Commission agence : 10% de tout
        $recettesAgenceTotales = $paiementsReussisTotal->sum('montant_paye') * 0.10;
        $recettesAgenceMois = $paiementsReussisTotal
                ->filter(function($p) {
                    return \Carbon\Carbon::parse($p->date_transaction)->month === now()->month
                        && \Carbon\Carbon::parse($p->date_transaction)->year === now()->year;
                })
                ->sum('montant_paye') * 0.10;

        // Recettes propriétaires : 90% de tout
        $recettesProprioTotales = $paiementsReussisTotal->sum('montant_paye') * 0.90;
        $recettesProprioMois = $paiementsReussisTotal
                ->filter(function($p) {
                    return \Carbon\Carbon::parse($p->date_transaction)->month === now()->month
                        && \Carbon\Carbon::parse($p->date_transaction)->year === now()->year;
                })
                ->sum('montant_paye') * 0.90;

        Log::info("💰 Recettes Agence totales: {$recettesAgenceTotales}");
        Log::info("💰 Recettes Proprio totales: {$recettesProprioTotales}");

        $statsGlobales = [
            'nombre_proprietaires' => User::role('proprietaire')
                ->whereHas('biens', function($q) {
                    $q->whereHas('mandat', function($subq) {
                        $subq->where('statut', 'actif');
                    });
                })
                ->count(),

            'total_biens' => Bien::whereHas('mandat', function($q) {
                $q->where('statut', 'actif');
            })->count(),

            'biens_gestion_locative' => Bien::whereHas('mandat', function ($q) {
                $q->where('type_mandat', 'gestion_locative')
                    ->where('statut', 'actif');
            })->count(),

            'biens_vente' => Bien::whereHas('mandat', function ($q) {
                $q->where('type_mandat', 'vente')
                    ->where('statut', 'actif');
            })->count(),

            'total_appartements' => $totalAppartements,
            'appartements_loues' => $appartementsLoues,

            'recettes_agence_totales' => $recettesAgenceTotales,
            'recettes_agence_mois' => $recettesAgenceMois,
            'recettes_proprietaires_totales' => $recettesProprioTotales,
            'recettes_proprietaires_mois' => $recettesProprioMois,

            'loyers_en_attente' => Paiement::where('type', 'loyer_mensuel')
                ->whereIn('statut', ['en_attente', 'partiellement_paye', 'en_retard'])
                ->sum('montant_restant'),
        ];

        $statsGlobales['taux_occupation_global'] = $statsGlobales['total_appartements'] > 0
            ? round(($statsGlobales['appartements_loues'] / $statsGlobales['total_appartements']) * 100, 1)
            : 0;

        Log::info('📊 Stats globales calculées');

        // ═══════════════════════════════════════════════════════════
        // ÉTAPE 2 : RÉCUPÉRER LES PROPRIÉTAIRES AVEC LEURS BIENS
        // ═══════════════════════════════════════════════════════════
        $proprietaires = User::role('proprietaire')
            ->with(['biens' => function ($q) {
                $q->whereHas('mandat', function($subq) {
                    $subq->where('statut', 'actif');
                })->with(['mandat' => function($subq) {
                    $subq->where('statut', 'actif');
                }, 'category', 'appartements']);
            }])
            ->whereHas('biens.mandat', function($q) {
                $q->where('statut', 'actif');
            })
            ->get();

        Log::info('👥 Propriétaires chargés: ' . $proprietaires->count());

        // ═══════════════════════════════════════════════════════════
        // ÉTAPE 3 : CALCULER LES STATS PAR PROPRIÉTAIRE
        // ═══════════════════════════════════════════════════════════
        $statsParProprietaire = $proprietaires->map(function ($proprio) {
            $biens = $proprio->biens;
            $biensIds = $biens->pluck('id');

            Log::info('');
            Log::info('─────────────────────────────────────────────────');
            Log::info("👤 Propriétaire: {$proprio->name} (ID: {$proprio->id})");

            // ✅ Calculer depuis TOUS les paiements (locations + ventes)
            $paiementsReussis = Paiement::where(function($q) use ($biensIds) {
                // Paiements de location
                $q->whereHas('location', function($subq) use ($biensIds) {
                    $subq->whereHas('reservation', function($subsubq) use ($biensIds) {
                        $subsubq->whereIn('bien_id', $biensIds);
                    });
                })
                    // OU paiements de vente
                    ->orWhereHas('vente', function($subq) use ($biensIds) {
                        $subq->whereHas('reservation', function($subsubq) use ($biensIds) {
                            $subsubq->whereIn('bien_id', $biensIds);
                        });
                    });
            })
                ->where('statut', 'reussi')
                ->get();

            $recettesTotales = $paiementsReussis->sum('montant_paye') * 0.90;
            $recettesMois = $paiementsReussis
                    ->filter(function($p) {
                        return \Carbon\Carbon::parse($p->date_transaction)->month === now()->month
                            && \Carbon\Carbon::parse($p->date_transaction)->year === now()->year;
                    })
                    ->sum('montant_paye') * 0.90;

            Log::info("   💰 Recettes totales: {$recettesTotales}");
            Log::info("   💰 Recettes mois: {$recettesMois}");

            // Biens locatifs
            $biensLocatifs = $biens->filter(function ($bien) {
                $mandat = $bien->mandat->where('statut', 'actif')->first();
                return $mandat &&
                    $mandat->type_mandat === 'gestion_locative' &&
                    $bien->category &&
                    strtolower($bien->category->name) === 'appartement';
            });

            $totalAppartements = $biensLocatifs->sum(fn($bien) => $bien->appartements()->count());
            $appartementsLoues = $biensLocatifs->sum(fn($bien) =>
            $bien->appartements()->where('statut', 'loue')->count()
            );

            // Loyers en attente (uniquement locations)
            $loyersEnAttente = Paiement::whereHas('location', function($q) use ($biensIds) {
                $q->whereHas('reservation', function($subq) use ($biensIds) {
                    $subq->whereIn('bien_id', $biensIds);
                });
            })
                ->where('type', 'loyer_mensuel')
                ->whereIn('statut', ['en_attente', 'en_retard'])
                ->sum('montant_restant');

            // Biens vente
            $biensVente = $biens->filter(function($bien) {
                $mandat = $bien->mandat->where('statut', 'actif')->first();
                return $mandat && $mandat->type_mandat === 'vente';
            })->count();

            return [
                'proprietaire' => [
                    'id' => $proprio->id,
                    'nom' => $proprio->nom ?? explode(' ', $proprio->name)[0] ?? '',
                    'prenom' => $proprio->prenom ?? (isset(explode(' ', $proprio->name)[1]) ? explode(' ', $proprio->name)[1] : ''),
                    'email' => $proprio->email,
                    'telephone' => $proprio->telephone ?? $proprio->phone ?? 'N/A',
                ],
                'stats' => [
                    'total_biens' => $biens->count(),
                    'biens_gestion_locative' => $biensLocatifs->count(),
                    'biens_vente' => $biensVente,
                    'total_appartements' => $totalAppartements,
                    'appartements_loues' => $appartementsLoues,
                    'taux_occupation' => $totalAppartements > 0
                        ? round(($appartementsLoues / $totalAppartements) * 100, 1)
                        : 0,
                    'recettes_totales' => $recettesTotales,
                    'recettes_mois_courant' => $recettesMois,
                    'loyers_en_attente' => $loyersEnAttente,
                ]
            ];
        });

        // ═══════════════════════════════════════════════════════════
        // ÉTAPE 4 : STATISTIQUES LOCATAIRES
        // ═══════════════════════════════════════════════════════════
        $statsLocataires = [
            'total_locataires_actifs' => Location::whereIn('statut', ['active', 'en_retard'])
                ->distinct('client_id')
                ->count('client_id'),
            'locations_actives' => Location::where('statut', 'active')->count(),
            'locations_en_retard' => Location::where('statut', 'en_retard')->count(),
            'loyers_collectes' => $paiementsReussisTotal->sum('montant_paye'),
        ];

        Log::info('═══════════════════════════════════════════════════');
        Log::info('✅ FIN DASHBOARD ADMIN GLOBAL');
        Log::info('═══════════════════════════════════════════════════');

        return Inertia::render('Admin/DashboardGlobal', [
            'stats_globales' => $statsGlobales,
            'stats_par_proprietaire' => $statsParProprietaire,
            'stats_locataires' => $statsLocataires,
        ]);
    }
    /**
     * Formater les appartements groupés par étage avec toutes leurs données
     * CORRECTION COMPLÈTE - Version 2.0
     */
    private function formatAppartementsParEtage($bien)
    {
        if (!$bien->isImmeuble()) {
            return [];
        }

        Log::info("🏢 Formatage appartements pour bien #{$bien->id}");

        // ✅ CHARGER les appartements avec la location active ET les paiements
        $appartements = $bien->appartements()
            ->with([
                'locationActive' => function($q) {
                    $q->with(['client']);
                }
            ])
            ->orderBy('etage', 'asc')
            ->get();

        Log::info("  Total appartements: " . $appartements->count());

        return $appartements
            ->groupBy('etage')
            ->sortKeys()
            ->map(function ($apps, $etage) use ($bien) {
                Log::info("  📍 Étage {$etage}: " . $apps->count() . " appartements");

                return [
                    'etage' => $etage,
                    'label' => $this->getEtageLabel($etage),
                    'appartements' => $apps->map(function ($app) use ($bien) {
                        $locationActive = $app->locationActive;

                        Log::info("    App #{$app->id} - Statut: {$app->statut}");

                        // ✅ CORRECTION : Chercher le dernier paiement depuis la base
                        $dernierPaiement = null;
                        if ($locationActive) {
                            Log::info("      ✓ Location active ID: {$locationActive->id}");

                            $paiementRecent = Paiement::where('location_id', $locationActive->id)
                                ->where('statut', 'reussi')
                                ->orderBy('date_transaction', 'desc')
                                ->first();

                            if ($paiementRecent) {
                                $dernierPaiement = [
                                    'montant' => $paiementRecent->montant_paye,
                                    'date' => $paiementRecent->date_transaction,
                                    'statut' => $paiementRecent->statut,
                                ];
                                Log::info("      💰 Dernier paiement: {$paiementRecent->montant_paye} FCFA le {$paiementRecent->date_transaction}");
                            } else {
                                Log::warning("      ⚠️ Pas de paiement trouvé pour cette location");
                            }
                        } else {
                            Log::info("      ✗ Pas de location active");
                        }

                        return [
                            'id' => $app->id,
                            'numero' => $app->numero,
                            'superficie' => $app->superficie,
                            'salons' => $app->salons ?? 0,
                            'chambres' => $app->chambres ?? 0,
                            'salles_bain' => $app->salles_bain ?? 0,
                            'cuisines' => $app->cuisines ?? 0,
                            'pieces' => ($app->salons ?? 0) + ($app->chambres ?? 0),
                            'statut' => $app->statut,

                            // ✅ Informations du locataire
                            'locataire' => $locationActive && $locationActive->client ? [
                                'id' => $locationActive->client->id,
                                'nom' => $locationActive->client->nom,
                                'prenom' => $locationActive->client->prenom ?? '',
                                'email' => $locationActive->client->email,
                                'telephone' => $locationActive->client->telephone ?? 'N/A',
                            ] : null,

                            // ✅ Informations de la location
                            'location' => $locationActive ? [
                                'id' => $locationActive->id,
                                'loyer_mensuel' => $locationActive->loyer_mensuel,
                                'date_debut' => $locationActive->date_debut,
                                'date_fin' => $locationActive->date_fin,
                                'statut' => $locationActive->statut,
                                'dernier_paiement' => $dernierPaiement, // ✅ CORRECTION : Utiliser la variable calculée
                            ] : null,
                        ];
                    })->values(),
                ];
            })
            ->values();
    }

    /**
     * Helper : Obtenir le label d'un étage
     */
    private function getEtageLabel($etage)
    {
        $labels = [
            0 => 'Rez-de-chaussée',
            1 => '1er étage',
            2 => '2ème étage',
            3 => '3ème étage',
        ];
        return $labels[$etage] ?? $etage . 'ème étage';
    }
    /**
     * Obtenir les recettes basées sur les commissions
     */
    private function getRecettesBien(Bien $bien)
    {
        // ✅ CORRECTION : Inclure TOUTES les commissions payées pour ce bien
        // (location, vente, ET réservations)
        $commissions = Commission::where('bien_id', $bien->id)
            ->where('statut', 'payee')
            ->get(); // ← Pas de filtre sur le type

        // Calculer les totaux
        $total = (float) $commissions->sum('montant_net_proprietaire');
        $moisCourant = (float) $commissions
            ->where('mois_concerne', '>=', now()->startOfMonth())
            ->sum('montant_net_proprietaire');
        $anneeCourante = (float) $commissions
            ->where('mois_concerne', '>=', now()->startOfYear())
            ->sum('montant_net_proprietaire');

        // ✅ DEBUG : Logger les valeurs pour vérifier
        Log::info('💰 Calcul recettes bien', [
            'bien_id' => $bien->id,
            'nombre_commissions' => $commissions->count(),
            'total' => $total,
            'mois_courant' => $moisCourant,
            'annee_courante' => $anneeCourante,
            'detail_commissions' => $commissions->map(function($c) {
                return [
                    'type' => $c->type,
                    'montant_proprio' => $c->montant_net_proprietaire,
                    'statut' => $c->statut,
                ];
            })
        ]);

        return [
            'total' => $total,
            'mois_courant' => $moisCourant,
            'annee_courante' => $anneeCourante,
        ];
    }

    /**
     * Stats des loyers basées sur les paiements
     */
    private function getLoyersStatsBien(Bien $bien)
    {
        $locations = Location::whereHas('reservation', function($query) use ($bien) {
            $query->where('bien_id', $bien->id);
        })
            ->whereIn('statut', ['active', 'en_retard'])
            ->with('paiements')
            ->get();

        $loyerMensuelTotal = $locations->sum('loyer_mensuel');
        $paiements = $locations->pluck('paiements')->flatten();

        return [
            'loyer_mensuel_total' => (float) $loyerMensuelTotal,
            'payes' => (float) $paiements->where('statut', 'reussi')->sum('montant_total'),
            'en_attente' => (float) $paiements->where('statut', 'en_attente')->sum('montant_total'),
            'en_retard' => (float) $paiements->where('statut', 'en_retard')->sum('montant_total'),
            'total_du' => (float) $paiements->whereIn('statut', ['en_attente', 'en_retard'])->sum('montant_total'),
        ];
    }
    /**
     * Obtenir les appartements groupés par étage avec leurs locataires
     */
    private function getAppartementsParEtage(Bien $bien)
    {
        // ✅ CHARGER les appartements AVEC la relation locationActive
        $appartements = $bien->appartements()
            ->with([
                'locationActive.client',
                'locationActive.paiements' => function($query) {
                    $query->orderBy('date_transaction', 'desc');
                },
                'locationActive.reservation'
            ])
            ->orderBy('etage', 'asc')
            ->get();

        $parEtage = [];

        for ($etage = 0; $etage <= $bien->floors; $etage++) {
            $appsEtage = $appartements->where('etage', $etage)->values();

            $parEtage[] = [
                'etage' => $etage,
                'label' => $this->getEtageLabelFor($etage),
                'appartements' => $appsEtage->map(function($app) {
                    // ✅ UTILISER locationActive au lieu de chercher manuellement
                    $location = $app->locationActive;

                    return [
                        'id' => $app->id,
                        'numero' => $app->numero,
                        'superficie' => $app->superficie,
                        'salons' => $app->salons,
                        'chambres' => $app->chambres,
                        'salles_bain' => $app->salles_bain,
                        'cuisines' => $app->cuisines,
                        'statut' => $app->statut,
                        'locataire' => $location && $location->client ? [
                            'id' => $location->client->id,
                            'nom' => $location->client->nom,
                            'prenom' => $location->client->prenom,
                            'email' => $location->client->email,
                            'telephone' => $location->client->telephone,
                        ] : null,
                        'location' => $location ? [
                            'id' => $location->id,
                            'date_debut' => $location->date_debut,
                            'date_fin' => $location->date_fin,
                            'loyer_mensuel' => $location->loyer_mensuel,
                            'statut' => $location->statut,
                            'dernier_paiement' => $location->paiements->first() ? [
                                'date' => $location->paiements->first()->date_transaction,
                                'montant' => $location->paiements->first()->montant_total,
                                'statut' => $location->paiements->first()->statut,
                            ] : null,
                        ] : null,
                    ];
                })->toArray(),
            ];
        }

        return $parEtage;
    }

    private function getLoyersDetailsBien(Bien $bien)
    {
        $appartements = $bien->appartements()
            ->with(['locationActive.client', 'locationActive.paiements'])
            ->orderBy('etage', 'asc')
            ->get();

        return $appartements->map(function($app) {
            $location = $app->locationActive;

            if (!$location) {
                return null;
            }

            $paiements = $location->paiements;

            return [
                'appartement_id' => $app->id,
                'appartement_numero' => $app->numero,
                'etage' => $this->getEtageLabelFor($app->etage),
                'locataire' => [
                    'nom' => $location->client->nom,
                    'prenom' => $location->client->prenom,
                ],
                'loyer_mensuel' => (float) $location->loyer_mensuel,
                'paiements_payes' => $paiements->where('statut', 'paye')->count(),
                'paiements_en_attente' => $paiements->where('statut', 'en_attente')->count(),
                'paiements_en_retard' => $paiements->where('statut', 'en_retard')->count(),
                'montant_paye' => (float) $paiements->where('statut', 'paye')->sum('montant'),
                'montant_du' => (float) $paiements->whereIn('statut', ['en_attente', 'en_retard'])->sum('montant'),
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Historique des paiements d'un bien
     */
    private function getHistoriquePaiements(Bien $bien, $limit = 20)
    {
        $paiements = DB::table('paiements')
            ->join('locations', 'paiements.location_id', '=', 'locations.id')
            ->join('appartements', 'locations.appartement_id', '=', 'appartements.id')
            ->join('clients', 'locations.client_id', '=', 'clients.id')
            ->where('appartements.bien_id', $bien->id)
            ->select(
                'paiements.id',
                'paiements.montant',
                'paiements.date_paiement',
                'paiements.mois_concerne',
                'paiements.statut',
                'paiements.moyen_paiement',
                'appartements.numero as appartement_numero',
                'appartements.etage',
                'clients.nom as client_nom',
                'clients.prenom as client_prenom'
            )
            ->orderBy('paiements.date_paiement', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'montant' => (float) $p->montant,
                    'date_paiement' => $p->date_paiement,
                    'mois_concerne' => $p->mois_concerne,
                    'statut' => $p->statut,
                    'moyen_paiement' => $p->moyen_paiement,
                    'appartement' => $p->appartement_numero,
                    'etage' => $this->getEtageLabelFor($p->etage),
                    'locataire' => $p->client_nom . ' ' . $p->client_prenom,
                ];
            });

        return $paiements;
    }

    /**
     * Télécharger le titre de propriété
     */
    public function downloadPropertyTitle(Bien $bien)
    {
        $user = auth()->user();

        // Seuls les admins et le propriétaire peuvent télécharger
        if (!$user->hasRole('admin') && $bien->proprietaire_id !== $user->id) {
            abort(403, 'Non autorisé');
        }

        if (!$bien->property_title) {
            abort(404, 'Aucun titre de propriété trouvé');
        }

        $path = storage_path('app/public/' . $bien->property_title);

        if (!file_exists($path)) {
            abort(404, 'Fichier introuvable');
        }

        return response()->download($path, 'titre_propriete_' . $bien->id . '.pdf');
    }


}
