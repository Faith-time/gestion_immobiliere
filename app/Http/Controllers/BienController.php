<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Categorie;
use App\Models\Mandat;
use App\Services\ElectronicSignatureService;
use App\Services\MandatPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class BienController extends Controller
{
    protected $mandatPdfService;
    protected $signatureService;

    public function __construct(MandatPdfService $mandatPdfService, ElectronicSignatureService $signatureService)
    {
        $this->mandatPdfService = $mandatPdfService;
        $this->signatureService = $signatureService;
    }

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
            'isAdmin' => $user->hasRole('admin'),
            'isProprietaire' => $user->hasRole('proprietaire'),
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
            $commissionFixe = ($validated['price'] * self::COMMISSION_PERCENTAGE) / 100;

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
                'commission_fixe' => $commissionFixe,
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
                number_format($commissionFixe, 0, ',', ' ') . ' FCFA).'
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

    // POST /biens/{bien}/valider - Méthode pour valider un bien avec génération automatique du PDF
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

                // **GÉNÉRATION AUTOMATIQUE DU PDF DU MANDAT**
                $pdfPath = $this->mandatPdfService->generatePdf($bien->mandat);

                if ($pdfPath) {
                    \Log::info("PDF du mandat généré avec succès", [
                        'bien_id' => $bien->id,
                        'mandat_id' => $bien->mandat->id,
                        'pdf_path' => $pdfPath
                    ]);
                } else {
                    \Log::warning("Échec de génération du PDF du mandat", [
                        'bien_id' => $bien->id,
                        'mandat_id' => $bien->mandat->id
                    ]);
                }
            }

            DB::commit();

            $message = 'Bien "' . $bien->title . '" validé avec succès. Il est maintenant visible publiquement.';

            if ($bien->mandat && $bien->mandat->hasPdf()) {
                $message .= ' Le mandat PDF a été généré automatiquement.';
            }

            return redirect()->route('biens.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur lors de la validation du bien:', [
                'bien_id' => $bien->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

    // Nouvelle méthode pour télécharger le PDF du mandat
    public function downloadMandatPdf(Bien $bien)
    {
        $user = auth()->user();

        // Vérifier les permissions
        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à télécharger ce mandat.');
        }

        if (!$bien->mandat) {
            abort(404, 'Aucun mandat trouvé pour ce bien.');
        }

        $response = $this->mandatPdfService->downloadPdf($bien->mandat);

        if (!$response) {
            return redirect()->back()->with('error', 'Impossible de générer ou télécharger le PDF du mandat.');
        }

        return $response;
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
            'title' => 'string|max:255',
            'property_title' => 'nullable|file',
            'description' => 'nullable|string',
            'image' => 'nullable|image',
            'rooms' => 'nullable|integer|min:0',
            'floors' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'city' => 'string|max:255',
            'address' => 'string|max:255',
            'superficy' => 'numeric|min:1',
            'price' => 'numeric|min:1',
            'status' => 'nullable|in:disponible,loue,vendu,reserve',
            'categorie_id' => 'exists:categories,id',

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
            $mandatUpdated = false;

            if ($mandat) {
                $mandatData = [];

                // Recalculer la commission si le prix a changé
                if ($validated['price'] != $bien->getOriginal('price')) {
                    $mandatData['commission_fixe'] = ($validated['price'] * self::COMMISSION_PERCENTAGE) / 100;
                    $mandatUpdated = true;
                }

                // Mettre à jour les autres champs du mandat si fournis
                if (isset($validated['type_mandat'])) {
                    $mandatData['type_mandat'] = $validated['type_mandat'];
                    $mandatUpdated = true;
                }

                if (isset($validated['type_mandat_vente'])) {
                    $mandatData['type_mandat_vente'] = $validated['type_mandat_vente'];
                    $mandatUpdated = true;
                }

                if (isset($validated['conditions_particulieres'])) {
                    $mandatData['conditions_particulieres'] = $validated['conditions_particulieres'];
                    $mandatUpdated = true;
                }

                // Si le bien repasse en validation, le mandat aussi
                if (isset($data['status']) && $data['status'] === 'en_validation') {
                    $mandatData['statut'] = 'en_attente';
                    $mandatUpdated = true;
                }

                if (!empty($mandatData)) {
                    $mandat->update($mandatData);
                }

                // Régénérer le PDF si le mandat a été modifié et qu'il est actif
                if ($mandatUpdated && $mandat->statut === 'actif') {
                    $this->mandatPdfService->regeneratePdf($mandat);
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
            // Supprimer le PDF du mandat s'il existe
            if ($bien->mandat && $bien->mandat->pdf_path && Storage::disk('public')->exists($bien->mandat->pdf_path)) {
                Storage::disk('public')->delete($bien->mandat->pdf_path);
            }

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

    /**
     * Prévisualiser le PDF du mandat dans le navigateur
     */
    public function previewMandatPdf(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à prévisualiser ce mandat.');
        }

        if (!$bien->mandat) {
            abort(404, 'Aucun mandat trouvé pour ce bien.');
        }

        $response = $this->mandatPdfService->previewMandatPdf($bien->mandat);

        if (!$response) {
            return redirect()->back()->with('error', 'Impossible de prévisualiser le PDF.');
        }

        return $response;
    }
    /**
     * Générer le contenu PDF sans le sauvegarder
     */
    private function generatePdfContent(Mandat $mandat)
    {
        $data = $mandat->getPdfData();

        // Sélectionner le bon template
        $template = $mandat->type_mandat === 'vente' ? 'mandats.vente' : 'mandats.gerance';

        // Générer le PDF avec Dompdf ou votre système de PDF
        $pdf = app('dompdf.wrapper');
        $pdf->loadView($template, $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    /**
     * Régénérer le PDF du mandat
     */
    public function regenerateMandatPdf(Bien $bien)
    {
        $user = auth()->user();

        // Seuls les admins peuvent régénérer
        if (!$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à régénérer ce PDF.');
        }

        if (!$bien->mandat) {
            abort(404, 'Aucun mandat trouvé pour ce bien.');
        }

        try {
            $pdfPath = $this->mandatPdfService->regeneratePdf($bien->mandat);

            if ($pdfPath) {
                return redirect()->back()->with('success', 'PDF du mandat régénéré avec succès.');
            } else {
                return redirect()->back()->with('error', 'Erreur lors de la régénération du PDF.');
            }
        } catch (\Exception $e) {
            \Log::error('Erreur régénération PDF:', [
                'bien_id' => $bien->id,
                'mandat_id' => $bien->mandat->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Erreur lors de la régénération du PDF du mandat.');
        }
    }


    /**
     * Afficher la page de signature du mandat
     */
    public function showSignaturePage(Bien $bien)
    {
        $user = auth()->user();

        // Vérifier les permissions
        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à signer ce mandat.');
        }

        if (!$bien->mandat) {
            abort(404, 'Aucun mandat trouvé pour ce bien.');
        }

        if ($bien->mandat->statut !== 'actif') {
            return redirect()->route('biens.index')
                ->with('error', 'Ce mandat n\'est pas actif et ne peut pas être signé.');
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

    /**
     * Signature par le propriétaire - VERSION CORRIGÉE
     */
    public function signByProprietaire(Request $request, Bien $bien)
    {
        $user = auth()->user();

        // Vérifier que c'est le propriétaire du bien
        if ($bien->proprietaire_id !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à signer ce mandat.');
        }

        if (!$bien->mandat) {
            abort(404, 'Aucun mandat trouvé pour ce bien.');
        }

        // CORRECTION : Vérifier si le propriétaire PEUT signer, pas l'agence
        if (!$bien->mandat->canBeSignedByProprietaire()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce mandat ne peut pas être signé par le propriétaire actuellement.'
            ], 400);
        }

        $request->validate([
            'signature_data' => 'required|string',
        ]);

        try {
            // CORRECTION : Appeler signByProprietaire, pas signByAgence
            $this->signatureService->signByProprietaire($bien->mandat, $request->signature_data);

            $message = 'Mandat signé avec succès !';
            if ($bien->mandat->fresh()->isFullySigned()) {
                $message .= ' Le document est maintenant entièrement signé.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'signature_stats' => $this->signatureService->getSignatureStats($bien->mandat->fresh()),
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur signature propriétaire:', [
                'bien_id' => $bien->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la signature : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Signature par l'agence (admin seulement) - VERSION COMPLÈTE
     */
    public function signByAgence(Request $request, Bien $bien)
    {
        $user = auth()->user();

        // Seuls les admins peuvent signer pour l'agence
        if (!$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à signer pour l\'agence.');
        }

        if (!$bien->mandat) {
            abort(404, 'Aucun mandat trouvé pour ce bien.');
        }

        if (!$bien->mandat->canBeSignedByAgence()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce mandat ne peut pas être signé par l\'agence actuellement.'
            ], 400);
        }

        $request->validate([
            'signature_data' => 'required|string',
        ]);

        try {
            $this->signatureService->signByAgence($bien->mandat, $request->signature_data);

            $message = 'Mandat signé par l\'agence avec succès !';
            if ($bien->mandat->fresh()->isFullySigned()) {
                $message .= ' Le document est maintenant entièrement signé.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'signature_stats' => $this->signatureService->getSignatureStats($bien->mandat->fresh()),
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur signature agence:', [
                'bien_id' => $bien->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la signature : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler une signature
     */
    public function cancelSignature(Request $request, Bien $bien, $signatoryType)
    {
        $user = auth()->user();

        // Vérifier les permissions
        if ($signatoryType === 'proprietaire' && $bien->proprietaire_id !== $user->id) {
            abort(403, 'Vous ne pouvez annuler que votre propre signature.');
        }

        if ($signatoryType === 'agence' && !$user->hasRole('admin')) {
            abort(403, 'Seuls les administrateurs peuvent annuler la signature de l\'agence.');
        }

        if (!$bien->mandat) {
            abort(404, 'Aucun mandat trouvé pour ce bien.');
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
                'message' => 'Erreur lors de l\'annulation : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut de signature (AJAX)
     */
    public function getSignatureStatus(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Accès non autorisé.');
        }

        if (!$bien->mandat) {
            abort(404, 'Aucun mandat trouvé pour ce bien.');
        }

        return response()->json([
            'signature_stats' => $this->signatureService->getSignatureStats($bien->mandat)
        ]);
    }

    /**
     * Télécharger le PDF signé
     */
    public function downloadSignedMandatPdf(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à télécharger ce mandat.');
        }

        if (!$bien->mandat) {
            abort(404, 'Aucun mandat trouvé pour ce bien.');
        }

        $response = $this->signatureService->downloadSignedPdf($bien->mandat);

        if (!$response) {
            return redirect()->back()->with('error', 'Impossible de télécharger le PDF du mandat.');
        }

        return $response;
    }

    /**
     * Prévisualiser le PDF signé
     */
    public function previewSignedMandatPdf(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à prévisualiser ce mandat.');
        }

        if (!$bien->mandat) {
            abort(404, 'Aucun mandat trouvé pour ce bien.');
        }

        // PROBLÈME : Vous appelez probablement l'ancienne méthode
        $response = $this->signatureService->previewSignedPdf($bien->mandat);

        if (!$response) {
            return redirect()->back()->with('error', 'Impossible de prévisualiser le PDF.');
        }

        return $response;
    }

    public function debugSignatureData(Bien $bien)
    {
        $mandat = $bien->mandat;

        if (!$mandat) {
            return response()->json(['error' => 'Pas de mandat trouvé']);
        }

        // Test direct des données PDF
        $pdfData = $mandat->getPdfDataWithSignatures();

        // CORRECTION : Méthode améliorée pour tester le base64
        $proprietaireBase64Valid = $this->testBase64Validity($mandat->proprietaire_signature_data);
        $agenceBase64Valid = $this->testBase64Validity($mandat->agence_signature_data);

        $debug = [
            'mandat_id' => $mandat->id,
            'signature_status' => $mandat->signature_status,

            // Données brutes
            'raw_proprietaire_data' => $mandat->proprietaire_signature_data ? 'EXISTS' : 'NULL',
            'raw_proprietaire_length' => $mandat->proprietaire_signature_data ? strlen($mandat->proprietaire_signature_data) : 0,
            'raw_agence_data' => $mandat->agence_signature_data ? 'EXISTS' : 'NULL',
            'raw_agence_length' => $mandat->agence_signature_data ? strlen($mandat->agence_signature_data) : 0,

            // Méthodes de vérification
            'proprietaire_signed' => $mandat->isSignedByProprietaire(),
            'agence_signed' => $mandat->isSignedByAgence(),

            // Données préparées pour PDF
            'pdf_proprietaire_signature' => $pdfData['proprietaire_signature'] ?? 'NOT_SET',
            'pdf_agence_signature' => [
                'is_signed' => $pdfData['agence_signature']['is_signed'] ?? false,
                'has_data' => !empty($pdfData['agence_signature']['data']),
                'data_length' => !empty($pdfData['agence_signature']['data']) ? strlen($pdfData['agence_signature']['data']) : 0,
            ],
            'pdf_signature_status' => $pdfData['signature_status'] ?? 'NOT_SET',

            // Test de validité corrigé
            'proprietaire_base64_valid' => $proprietaireBase64Valid,
            'agence_base64_valid' => $agenceBase64Valid,

            // Test de décodage des données préparées
            'pdf_agence_data_can_decode' => $this->testImageData($pdfData['agence_signature']['data'] ?? null),
        ];

        return response()->json($debug, 200, [], JSON_PRETTY_PRINT);
    }

// NOUVELLE MÉTHODE pour tester correctement la validité base64
    private function testBase64Validity($data)
    {
        if (!$data) {
            return 'NULL';
        }

        try {
            // Si c'est au format data:image, extraire le base64
            if (strpos($data, 'data:image/') === 0) {
                $base64Data = substr($data, strpos($data, ',') + 1);
            } else {
                $base64Data = $data;
            }

            // Nettoyer et tester
            $base64Data = trim(str_replace([' ', '\n', '\r'], '', $base64Data));
            $decoded = base64_decode($base64Data, true);

            if ($decoded === false) {
                return 'INVALID_BASE64';
            }

            // Tester si c'est une image valide
            $imageInfo = @getimagesizefromstring($decoded);
            if ($imageInfo === false) {
                return 'VALID_BASE64_BUT_NOT_IMAGE';
            }

            return 'VALID_IMAGE';

        } catch (\Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

// NOUVELLE MÉTHODE pour tester les données d'image préparées
    private function testImageData($data)
    {
        if (!$data) {
            return 'NO_DATA';
        }

        if (strpos($data, 'data:image/') === 0) {
            $base64Data = substr($data, strpos($data, ',') + 1);
            $decoded = base64_decode($base64Data, true);

            if ($decoded === false) {
                return 'CANNOT_DECODE';
            }

            return @getimagesizefromstring($decoded) !== false ? 'VALID_IMAGE' : 'INVALID_IMAGE';
        }

        return 'NOT_DATA_URL';
    }
}
