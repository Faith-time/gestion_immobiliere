<?php

namespace App\Http\Controllers;

use App\Models\ClientDossier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Services\BienMatchingService;

class ClientDossierController extends Controller
{
    protected $matchingService;

    public function __construct(BienMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Afficher le dossier de l'utilisateur connecté
     */
    public function index()
    {
        $user = Auth::user();

        // Récupérer le dossier de l'utilisateur connecté
        $dossier = ClientDossier::where('client_id', $user->id)->first();

        return Inertia::render('ClientDossiers/Index', [
            'dossier' => $dossier,
        ]);
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        return Inertia::render('ClientDossiers/Create');
    }

    /**
     * ✅ Enregistrer un nouveau dossier client
     */
    public function store(Request $request)
    {
        // Vérifier que l'utilisateur est connecté
        if (!Auth::check()) {
            return redirect()->back()->with('error', 'Vous devez être connecté pour soumettre une demande.');
        }

        $user = Auth::user();

        // Vérifier si l'utilisateur a déjà un dossier
        if ($user->dossierClient) {
            return redirect()->back()->with('error', 'Vous avez déjà soumis un dossier. Vous pouvez le modifier depuis votre espace client.');
        }

        // ✅ VALIDATION avec type_logement comme string
        $request->validate([
            'telephone' => 'required|string|max:20',
            'profession' => 'required|string|max:255',
            'numero_cni' => 'required|string|max:50',
            'personne_contact' => 'required|string|max:255',
            'telephone_contact' => 'required|string|max:20',
            'revenus_mensuels' => 'required|in:plus_100000,plus_200000,plus_300000,plus_500000',
            'nombre_personnes' => 'nullable|integer|min:1',
            'nbchambres' => 'nullable|integer|min:0',
            'nbsalons' => 'nullable|integer|min:0',
            'nbcuisines' => 'nullable|integer|min:0',
            'nbsalledebains' => 'nullable|integer|min:0',
            'situation_familiale' => 'nullable|in:celibataire,marie',
            'type_logement' => 'required|in:appartement,studio', // ✅ Changé en string
            'quartier_souhaite' => 'nullable|string|max:255',
            'date_entree_souhaitee' => 'nullable|date',
            'carte_identite' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // Gérer l'upload de la carte d'identité
            $carteIdentitePath = null;

            if ($request->hasFile('carte_identite')) {
                $carteIdentitePath = $request->file('carte_identite')->store(
                    'documents/cartes_identite',
                    'public'
                );
                Log::info('✅ Carte d\'identité uploadée', ['path' => $carteIdentitePath]);
            }

            // ✅ Créer le dossier client avec type_logement comme string
            $dossier = ClientDossier::create([
                'client_id' => $user->id,
                'profession' => $request->profession,
                'numero_cni' => $request->numero_cni,
                'personne_contact' => $request->personne_contact,
                'telephone_contact' => $request->telephone_contact,
                'revenus_mensuels' => $request->revenus_mensuels,
                'nombre_personnes' => $request->nombre_personnes,
                'nbchambres' => $request->nbchambres,
                'nbsalons' => $request->nbsalons,
                'nbcuisines' => $request->nbcuisines,
                'nbsalledebains' => $request->nbsalledebains,
                'situation_familiale' => $request->situation_familiale,
                'type_logement' => $request->type_logement, // ✅ Stocké comme string
                'quartier_souhaite' => $request->quartier_souhaite,
                'date_entree_souhaitee' => $request->date_entree_souhaitee,
                'carte_identite_path' => $carteIdentitePath,
            ]);

            Log::info('✅ Dossier client créé', ['dossier_id' => $dossier->id]);

            // Mettre à jour le téléphone de l'utilisateur si fourni
            if ($request->telephone && $user->telephone !== $request->telephone) {
                $user->update(['telephone' => $request->telephone]);
            }

            DB::commit();

            // Rechercher des biens correspondants
            $biensCorrespondants = $this->matchingService->rechercherBiensCorrespondants($dossier);

            if ($biensCorrespondants->count() > 0) {
                return redirect()->route('client-dossiers.index')->with('success',
                    "Demande enregistrée avec succès ! Nous avons trouvé {$biensCorrespondants->count()} bien(s) correspondant à vos critères."
                );
            }

            return redirect()->route('client-dossiers.index')->with('success',
                'Demande enregistrée avec succès ! Vous serez notifié dès qu\'un logement correspondant sera disponible.'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Erreur création dossier client', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Supprimer le fichier uploadé en cas d'erreur
            if (isset($carteIdentitePath) && $carteIdentitePath) {
                Storage::disk('public')->delete($carteIdentitePath);
            }

            return redirect()->back()
                ->with('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Afficher le formulaire d'édition d'un dossier
     */
    public function edit(string $id)
    {
        $dossier = ClientDossier::with('client')->findOrFail($id);

        // Vérifier que l'utilisateur peut modifier ce dossier
        if (Auth::id() !== $dossier->client_id && !Auth::user()->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier ce dossier.');
        }

        return Inertia::render('ClientDossiers/Edit', [
            'dossier' => $dossier,
        ]);
    }

    /**
     * ✅ Mettre à jour un dossier client
     */
    public function update(Request $request, string $id)
    {
        Log::info('🔄 Début mise à jour dossier', [
            'dossier_id' => $id,
            'request_data' => $request->except(['carte_identite']),
            'has_file' => $request->hasFile('carte_identite')
        ]);

        // Récupérer le dossier
        $dossier = ClientDossier::findOrFail($id);

        // Vérifier que l'utilisateur peut modifier ce dossier
        if (Auth::id() !== $dossier->client_id && !Auth::user()->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier ce dossier.');
        }

        // ✅ VALIDATION avec type_logement comme string
        try {
            $validated = $request->validate([
                'telephone' => 'nullable|string|max:20',
                'profession' => 'nullable|string|max:255',
                'numero_cni' => 'nullable|string|max:50',
                'personne_contact' => 'nullable|string|max:255',
                'telephone_contact' => 'nullable|string|max:20',
                'revenus_mensuels' => 'nullable|in:plus_100000,plus_200000,plus_300000,plus_500000',
                'nombre_personnes' => 'nullable|integer|min:1',
                'nbchambres' => 'nullable|integer|min:0',
                'nbsalons' => 'nullable|integer|min:0',
                'nbcuisines' => 'nullable|integer|min:0',
                'nbsalledebains' => 'nullable|integer|min:0',
                'situation_familiale' => 'nullable|in:celibataire,marie',
                'type_logement' => 'nullable|in:appartement,studio',
                'quartier_souhaite' => 'nullable|string|max:255',
                'date_entree_souhaitee' => 'nullable|date',
                'carte_identite' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);
            Log::info('✅ Validation réussie');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Erreur de validation', [
                'errors' => $e->errors()
            ]);
            throw $e;
        }

        DB::beginTransaction();

        try {
            // Préparer les données à mettre à jour
            $updateData = [];

            // Champs texte simples
            $simpleFields = [
                'profession',
                'numero_cni',
                'personne_contact',
                'telephone_contact',
                'revenus_mensuels',
                'situation_familiale',
                'type_logement', // ✅ Ajouté
                'quartier_souhaite',
            ];

            foreach ($simpleFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->input($field);
                }
            }

            // Champs numériques
            $numericFields = [
                'nombre_personnes',
                'nbchambres',
                'nbsalons',
                'nbcuisines',
                'nbsalledebains',
            ];

            foreach ($numericFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->input($field);
                }
            }

            // Date d'entrée souhaitée
            if ($request->has('date_entree_souhaitee')) {
                $updateData['date_entree_souhaitee'] = $request->input('date_entree_souhaitee');
            }

            // Gérer l'upload de la carte d'identité
            if ($request->hasFile('carte_identite')) {
                // Supprimer l'ancien fichier si existe
                if ($dossier->carte_identite_path) {
                    Storage::disk('public')->delete($dossier->carte_identite_path);
                    Log::info('🗑️ Ancienne carte d\'identité supprimée', [
                        'path' => $dossier->carte_identite_path
                    ]);
                }

                // Stocker le nouveau fichier
                $carteIdentitePath = $request->file('carte_identite')->store(
                    'documents/cartes_identite',
                    'public'
                );

                $updateData['carte_identite_path'] = $carteIdentitePath;

                Log::info('✅ Nouvelle carte d\'identité uploadée', [
                    'path' => $carteIdentitePath,
                    'dossier_id' => $dossier->id
                ]);
            }

            // Mettre à jour le dossier
            $dossier->update($updateData);

            // Mettre à jour le téléphone de l'utilisateur si fourni
            if ($request->has('telephone') && $request->telephone) {
                $user = $dossier->client;
                if ($user->telephone !== $request->telephone) {
                    $user->update(['telephone' => $request->telephone]);
                    Log::info('📱 Téléphone utilisateur mis à jour', [
                        'user_id' => $user->id,
                        'nouveau_telephone' => $request->telephone
                    ]);
                }
            }

            DB::commit();

            Log::info('✅ Dossier client mis à jour avec succès', [
                'dossier_id' => $dossier->id,
                'client_id' => $dossier->client_id,
                'champs_mis_a_jour' => array_keys($updateData)
            ]);

            // ✅ Redirection vers la page index pour recharger les données
            return redirect()->route('client-dossiers.index')
                ->with('success', 'Votre dossier a été mis à jour avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Erreur lors de la mise à jour du dossier client', [
                'dossier_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Supprimer le fichier uploadé en cas d'erreur
            if (isset($carteIdentitePath) && $carteIdentitePath) {
                Storage::disk('public')->delete($carteIdentitePath);
                Log::info('🗑️ Fichier uploadé supprimé suite à erreur', [
                    'path' => $carteIdentitePath
                ]);
            }

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de votre dossier. Veuillez réessayer.')
                ->withInput();
        }
    }

    /**
     * ✅ Supprimer un dossier client
     */
    public function destroy(string $id)
    {
        $dossier = ClientDossier::findOrFail($id);

        // Vérifier que l'utilisateur peut supprimer ce dossier
        if (Auth::id() !== $dossier->client_id && !Auth::user()->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer ce dossier.');
        }

        DB::beginTransaction();

        try {
            // Supprimer la carte d'identité
            if ($dossier->carte_identite_path) {
                Storage::disk('public')->delete($dossier->carte_identite_path);
                Log::info('✅ Carte d\'identité supprimée', ['path' => $dossier->carte_identite_path]);
            }

            $dossier->delete();

            DB::commit();

            Log::info('✅ Dossier client supprimé', ['dossier_id' => $id]);

            return redirect()->route('client-dossiers.index')->with('success', 'Dossier supprimé avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Erreur suppression dossier client', [
                'message' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    /**
     * Afficher un dossier client
     */
    public function show(string $id)
    {
        $dossier = ClientDossier::with('client')->findOrFail($id);

        // Vérifier que l'utilisateur peut voir ce dossier
        if (Auth::id() !== $dossier->client_id && !Auth::user()->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à voir ce dossier.');
        }

        return Inertia::render('ClientDossiers/Show', [
            'dossier' => $dossier,
        ]);
    }
}
