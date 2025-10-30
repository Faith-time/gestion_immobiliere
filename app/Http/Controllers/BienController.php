<?php

namespace App\Http\Controllers;

use App\Models\Appartement;
use App\Models\Bien;
use App\Models\Commission;
use App\Models\Image;
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
use Spatie\Permission\Models\Role;
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

    // GET /biens
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
            'description' => 'nullable|string',
            'property_title' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
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
            'type_mandat' => 'required|in:vente,gestion_locative',
            'type_mandat_vente' => 'nullable|in:exclusif,simple,semi_exclusif',
            'conditions_particulieres' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // 1. Sauvegarder le document commercial
            $propertyTitlePath = $request->file('property_title')->store('documents', 'public');

            // 2. Créer le bien
            $bien = Bien::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'property_title' => $propertyTitlePath,
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
                'status' => 'disponible',
                'proprietaire_id' => auth()->id(),
            ]);

            // 3. Créer le mandat
            $bien->mandat()->create([
                'type_mandat' => $validated['type_mandat'],
                'type_mandat_vente' => $validated['type_mandat_vente'] ?? null,
                'conditions_particulieres' => $validated['conditions_particulieres'] ?? null,
                'date_debut' => now(),
                'statut' => 'en_attente',
            ]);

            // 4. Sauvegarder les images GÉNÉRALES du bien
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $labels = $request->input('images_labels', []);

                foreach ($images as $index => $image) {
                    $path = $image->store('biens', 'public');

                    $bien->images()->create([
                        'chemin_image' => $path,
                        'libelle' => $labels[$index] ?? null,
                        'appartement_id' => null,
                    ]);
                }
            }

            // 5. Créer les appartements et leurs images
            $categorieAppartement = Categorie::find($validated['categorie_id']);

            if ($categorieAppartement &&
                strtolower($categorieAppartement->name) === 'appartement' &&
                $request->has('appartements')) {

                $appartementsData = json_decode($request->input('appartements'), true);

                foreach ($appartementsData as $index => $appartementData) {
                    $appartement = $bien->appartements()->create([
                        'numero' => $appartementData['numero'],
                        'etage' => $appartementData['etage'],
                        'superficie' => $appartementData['superficie'] ?? null,
                        'salons' => $appartementData['salons'] ?? null,          // ✅ CHANGÉ
                        'chambres' => $appartementData['chambres'] ?? null,
                        'salles_bain' => $appartementData['salles_bain'] ?? null,
                        'cuisines' => $appartementData['cuisines'] ?? null,      // ✅ AJOUTÉ
                        'statut' => 'disponible',
                        'description' => $appartementData['description'] ?? null,
                    ]);

                    // 6. Sauvegarder les images de l'appartement
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

            DB::commit();

            return redirect()->route('biens.show', $bien->id)
                ->with('success', 'Bien créé avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($propertyTitlePath)) {
                Storage::disk('public')->delete($propertyTitlePath);
            }

            Log::error('Erreur création bien: ' . $e->getMessage());

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

    // POST /biens/{bien}/valider
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
            $bien->update([
                'status' => 'disponible',
                'validated_at' => now(),
                'validated_by' => $user->id,
            ]);

            if ($bien->mandat) {
                $bien->mandat->update(['statut' => 'actif']);
                $this->mandatPdfService->generatePdf($bien->mandat);
            }

            DB::commit();

            // 🔥 NOUVEAU : Notifier les clients correspondants
            $nombreNotifications = $this->matchingService->notifierClientsCorrespondants($bien->fresh());

            if ($nombreNotifications > 0) {
                return redirect()->route('biens.index')->with('success',
                    "Bien validé avec succès. {$nombreNotifications} client(s) ont été notifié(s)."
                );
            }

            return redirect()->route('biens.index')->with('success', 'Bien validé avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur validation bien: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la validation.');
        }
    }
    // POST /biens/{bien}/rejeter
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

    // Dans BienController.php

// Dans BienController.php - Méthode index() mise à jour

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

        // ✅ Ajouter les stats d'occupation pour chaque bien avec appartements
        $biens->each(function($bien) {
            if ($bien->category && strtolower($bien->category->name) === 'appartement') {
                $bien->occupation_stats = $bien->getOccupationStats();
            }
        });

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
            'mandat',
            'mandatActuel', // ✅ Ajouter ceci si vous l'utilisez dans le template
            'proprietaire',
            'images',
            'appartements' => function($query) {
                $query->orderBy('etage', 'asc');
            },
            'appartements.locationActive.client'
        ]);

        // Ajouter des logs pour debug
        \Log::info('Bien loaded:', [
            'id' => $bien->id,
            'images_count' => $bien->images->count(),
            'appartements_count' => $bien->appartements->count(),
            'categorie_id' => $bien->categorie_id,
            'category_name' => $bien->category ? $bien->category->name : 'N/A'
        ]);

        return Inertia::render('Biens/Show', [
            'bien' => $bien,
            'userRoles' => auth()->user()->roles->pluck('name'),
        ]);
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
            abort(403);
        }

        if (!$bien->mandat) {
            abort(404);
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
            $this->signatureService->signByProprietaire($bien->mandat, $request->signature_data);

            return response()->json([
                'success' => true,
                'message' => 'Mandat signé avec succès !',
                'signature_stats' => $this->signatureService->getSignatureStats($bien->mandat->fresh()),
            ]);

        } catch (\Exception $e) {
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
            abort(403);
        }

        if (!$bien->mandat) {
            abort(404);
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
            $this->signatureService->signByAgence($bien->mandat, $request->signature_data);

            return response()->json([
                'success' => true,
                'message' => 'Mandat signé par l\'agence avec succès !',
                'signature_stats' => $this->signatureService->getSignatureStats($bien->mandat->fresh()),
            ]);

        } catch (\Exception $e) {
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
            abort(403);
        }

        if ($signatoryType === 'agence' && !$user->hasRole('admin')) {
            abort(403);
        }

        if (!$bien->mandat) {
            abort(404);
        }

        try {
            $this->signatureService->cancelSignature($bien->mandat, $signatoryType);

            return response()->json([
                'success' => true,
                'message' => 'Signature annulée avec succès.',
                'signature_stats' => $this->signatureService->getSignatureStats($bien->mandat->fresh()),
            ]);

        } catch (\Exception $e) {
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

    /**
     * Dashboard propriétaire - Vue d'ensemble de ses biens
     */
    public function dashboardProprietaire()
    {
        $user = auth()->user();

        if (!$user->hasRole('proprietaire') && !$user->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        // Récupérer les biens avec mandat actif
        $biens = Bien::with([
            'category',
            'mandatActuel',
            'appartements' => function($query) {
                $query->orderBy('etage', 'asc');
            },
            'appartements.locationActive.client',
            'appartements.locationActive.paiements'
        ])
            ->where('proprietaire_id', $user->id)
            ->whereHas('mandatActuel')
            ->get();

        // ✅ Calculer les statistiques pour chaque bien avec commissions
        $biensAvecStats = $biens->map(function($bien) {
            $mandatActuel = $bien->mandatActuel;
            $isGestionLocative = $mandatActuel &&
                $mandatActuel->type_mandat === 'gestion_locative' &&
                $bien->category &&
                strtolower($bien->category->name) === 'appartement';

            // ✅ Récupérer les commissions du bien
            $commissions = Commission::where('bien_id', $bien->id)
                ->where('statut', 'payee')
                ->get();

            return [
                'id' => $bien->id,
                'title' => $bien->title,
                'address' => $bien->address,
                'city' => $bien->city,
                'type' => $bien->category->name ?? 'N/A',
                'floors' => $bien->floors,
                'type_mandat' => $mandatActuel ? $mandatActuel->type_mandat : null,
                'type_mandat_label' => $mandatActuel ? $mandatActuel->getTypeMandatLabel() : 'Aucun mandat',
                'date_fin_mandat' => $mandatActuel ? $mandatActuel->date_fin->format('d/m/Y') : null,
                'occupation_stats' => $isGestionLocative ? $bien->getOccupationStats() : null,
                'appartements_par_etage' => $isGestionLocative ? $this->getAppartementsParEtage($bien) : [],

                // ✅ Recettes du propriétaire (90% via commissions)
                'recettes' => [
                    'total' => $commissions->sum('montant_net_proprietaire'),
                    'mois_courant' => $commissions->where('mois_concerne', '>=', now()->startOfMonth())->sum('montant_net_proprietaire'),
                    'annee_courante' => $commissions->where('mois_concerne', '>=', now()->startOfYear())->sum('montant_net_proprietaire'),
                ],

                // ✅ Stats des loyers
                'loyers_stats' => $isGestionLocative ? $this->getLoyersStatsBien($bien) : [
                    'loyer_mensuel_total' => 0,
                    'payes' => 0,
                    'en_attente' => 0,
                    'en_retard' => 0,
                    'total_du' => 0,
                ],
            ];
        });

        // Filtrer uniquement les biens en gestion locative pour stats globales
        $biensGestionLocative = $biensAvecStats->filter(function($bien) {
            return $bien['type_mandat'] === 'gestion_locative' && !empty($bien['appartements_par_etage']);
        });

        // ✅ Statistiques globales du propriétaire
        $statsGlobales = [
            'total_biens' => $biens->count(),
            'biens_gestion_locative' => $biensGestionLocative->count(),
            'biens_vente' => $biens->filter(fn($b) => $b->mandatActuel && $b->mandatActuel->type_mandat === 'vente')->count(),
            'total_appartements' => $biensGestionLocative->sum(fn($b) => $b['occupation_stats']['total'] ?? 0),
            'appartements_loues' => $biensGestionLocative->sum(fn($b) => $b['occupation_stats']['loues'] ?? 0),
            'recettes_totales' => $biensGestionLocative->sum('recettes.total'),
            'recettes_mois_courant' => $biensGestionLocative->sum('recettes.mois_courant'),
            'loyers_en_attente' => $biensGestionLocative->sum('loyers_stats.en_attente') + $biensGestionLocative->sum('loyers_stats.en_retard'),
        ];

        return Inertia::render('Dashboard/Proprietaire', [
            'biens' => $biensAvecStats,
            'stats_globales' => $statsGlobales,
        ]);
    }    /**
     * Détails d'un bien pour le propriétaire
     */
    public function detailsBienProprietaire(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        $bien->load([
            'category',
            'appartements' => function($query) {
                $query->orderBy('etage', 'asc');
            },
            'appartements.locationActive.client',
            'appartements.locationActive.paiements' => function($query) {
                $query->orderBy('date_paiement', 'desc');
            }
        ]);

        return Inertia::render('Dashboard/BienDetails', [
            'bien' => [
                'id' => $bien->id,
                'title' => $bien->title,
                'address' => $bien->address,
                'city' => $bien->city,
                'type' => $bien->category->name ?? 'N/A',
                'floors' => $bien->floors,
                'occupation_stats' => $bien->getOccupationStats(),
                'appartements_par_etage' => $this->getAppartementsParEtage($bien),
                'recettes' => $this->getRecettesBien($bien),
                'loyers_details' => $this->getLoyersDetailsBien($bien),
                'historique_paiements' => $this->getHistoriquePaiements($bien),
            ]
        ]);
    }

    /**
     * Dashboard Admin Global - Vue d'ensemble avec commissions
     */
    public function dashboardAdminGlobal()
    {
        $user = auth()->user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        // ✅ Récupérer toutes les commissions pour les statistiques globales
        $commissionsAgence = Commission::whereIn('type', ['reservation_vente', 'vente', 'location'])
            ->where('statut', 'payee')
            ->get();

        $commissionsProprietaires = Commission::whereIn('type', ['reservation_location', 'vente', 'location'])
            ->where('statut', 'payee')
            ->get();

        // ✅ Statistiques globales basées sur les commissions
        $statsGlobales = [
            'nombre_proprietaires' => User::role('proprietaire')->whereHas('biens')->count(),
            'total_biens' => Bien::whereHas('mandatActuel')->count(),
            'biens_gestion_locative' => Bien::whereHas('mandatActuel', function($q) {
                $q->where('type_mandat', 'gestion_locative');
            })->count(),
            'biens_vente' => Bien::whereHas('mandatActuel', function($q) {
                $q->where('type_mandat', 'vente');
            })->count(),

            // Total appartements tous biens confondus
            'total_appartements' => Appartement::count(),
            'appartements_loues' => Appartement::where('statut', 'loue')->count(),

            // Recettes AGENCE (10% + acomptes vente + cautions)
            'recettes_agence_totales' => $commissionsAgence->sum('montant_commission'),
            'recettes_agence_mois' => $commissionsAgence->where('mois_concerne', '>=', now()->startOfMonth())->sum('montant_commission'),

            // Recettes PROPRIÉTAIRES (90%)
            'recettes_proprietaires_totales' => $commissionsProprietaires->sum('montant_net_proprietaire'),
            'recettes_proprietaires_mois' => $commissionsProprietaires->where('mois_concerne', '>=', now()->startOfMonth())->sum('montant_net_proprietaire'),

            // Loyers en attente
            'loyers_en_attente' => Paiement::where('type', 'loyer_mensuel')
                ->whereIn('statut', ['en_attente', 'partiellement_paye'])
                ->sum('montant_restant'),
        ];

        $statsGlobales['taux_occupation_global'] = $statsGlobales['total_appartements'] > 0
            ? round(($statsGlobales['appartements_loues'] / $statsGlobales['total_appartements']) * 100, 1)
            : 0;

        // ✅ Stats par propriétaire avec commissions
        $proprietaires = User::role('proprietaire')
            ->with(['biens' => function($query) {
                $query->whereHas('mandatActuel');
            }])
            ->whereHas('biens.mandatActuel')
            ->get();

        $statsParProprietaire = [];

        foreach ($proprietaires as $proprietaire) {
            $biensProprietaire = $proprietaire->biens;
            $biensIds = $biensProprietaire->pluck('id');

            // ✅ Récupérer les commissions du propriétaire
            $commissionsProprietaire = Commission::whereIn('bien_id', $biensIds)
                ->where('statut', 'payee')
                ->get();

            $biensGestionLocative = $biensProprietaire->filter(function($bien) {
                return $bien->mandatActuel &&
                    $bien->mandatActuel->type_mandat === 'gestion_locative' &&
                    $bien->category &&
                    strtolower($bien->category->name) === 'appartement';
            });

            $totalAppartements = $biensGestionLocative->sum(function($bien) {
                return $bien->appartements()->count();
            });

            $appartementsLoues = $biensGestionLocative->sum(function($bien) {
                return $bien->appartements()->where('statut', 'loue')->count();
            });

            // ✅ Recettes du propriétaire (90% des loyers)
            $recettesTotales = $commissionsProprietaire->sum('montant_net_proprietaire');
            $recettesMoisCourant = $commissionsProprietaire
                ->where('mois_concerne', '>=', now()->startOfMonth())
                ->sum('montant_net_proprietaire');

            // ✅ Loyers en attente pour ce propriétaire
            $loyersEnAttente = Paiement::whereHas('location.reservation.bien', function($q) use ($proprietaire) {
                $q->where('proprietaire_id', $proprietaire->id);
            })
                ->where('type', 'loyer_mensuel')
                ->whereIn('statut', ['en_attente', 'en_retard'])
                ->sum('montant_restant');

            $statsParProprietaire[] = [
                'proprietaire' => [
                    'id' => $proprietaire->id,
                    'nom' => $proprietaire->nom,
                    'prenom' => $proprietaire->prenom,
                    'email' => $proprietaire->email,
                    'telephone' => $proprietaire->telephone ?? 'N/A',
                ],
                'stats' => [
                    'total_biens' => $biensProprietaire->count(),
                    'biens_gestion_locative' => $biensGestionLocative->count(),
                    'biens_vente' => $biensProprietaire->filter(fn($b) => $b->mandatActuel && $b->mandatActuel->type_mandat === 'vente')->count(),
                    'total_appartements' => $totalAppartements,
                    'appartements_loues' => $appartementsLoues,
                    'taux_occupation' => $totalAppartements > 0 ? round(($appartementsLoues / $totalAppartements) * 100, 1) : 0,
                    'recettes_totales' => $recettesTotales,
                    'recettes_mois_courant' => $recettesMoisCourant,
                    'loyers_en_attente' => $loyersEnAttente,
                ]
            ];
        }

        // ✅ Stats locataires
        $statsLocataires = [
            'total_locataires_actifs' => Location::whereIn('statut', ['active', 'en_retard'])->distinct('client_id')->count('client_id'),
            'locations_actives' => Location::where('statut', 'active')->count(),
            'locations_en_retard' => Location::where('statut', 'en_retard')->count(),
            'loyers_collectes' => Commission::where('type', 'location')
                ->where('statut', 'payee')
                ->sum('montant_base'),
        ];

        return Inertia::render('Admin/DashboardGlobal', [
            'stats_globales' => $statsGlobales,
            'stats_par_proprietaire' => $statsParProprietaire,
            'stats_locataires' => $statsLocataires,
        ]);
    }

    /**
     * Obtenir les recettes basées sur les commissions
     */
    private function getRecettesBien(Bien $bien)
    {
        $commissions = Commission::where('bien_id', $bien->id)
            ->where('statut', 'payee')
            ->get();

        return [
            'total' => (float) $commissions->sum('montant_net_proprietaire'),
            'mois_courant' => (float) $commissions->where('mois_concerne', '>=', now()->startOfMonth())->sum('montant_net_proprietaire'),
            'annee_courante' => (float) $commissions->where('mois_concerne', '>=', now()->startOfYear())->sum('montant_net_proprietaire'),
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
        $appartements = $bien->appartements()->orderBy('etage', 'asc')->get();
        $parEtage = [];

        for ($etage = 0; $etage <= $bien->floors; $etage++) {
            $appsEtage = $appartements->where('etage', $etage)->values();

            $parEtage[] = [
                'etage' => $etage,
                'label' => $this->getEtageLabelFor($etage),
                'appartements' => $appsEtage->map(function($app) {
                    $location = Location::whereHas('reservation', function($query) use ($app) {
                        $query->where('appartement_id', $app->id);
                    })
                        ->whereIn('statut', ['active', 'en_attente_paiement'])
                        ->with(['client', 'paiements'])
                        ->latest()
                        ->first();

                    return [
                        'id' => $app->id,
                        'numero' => $app->numero,
                        'superficie' => $app->superficie,
                        'salons' => $app->salons,              // ✅ CHANGÉ
                        'chambres' => $app->chambres,          // ✅ AJOUTÉ
                        'salles_bain' => $app->salles_bain,    // ✅ AJOUTÉ
                        'cuisines' => $app->cuisines,          // ✅ AJOUTÉ
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
}
