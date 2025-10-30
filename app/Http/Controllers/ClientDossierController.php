<?php

namespace App\Http\Controllers;

use App\Models\ClientDossier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
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
     * Afficher la liste des dossiers clients
     */
    public function index()
    {
        $dossiers = ClientDossier::with('client')->latest()->get();

        return Inertia::render('ClientDossiers/Index', [
            'dossiers' => $dossiers,
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
     * Enregistrer un nouveau dossier client
     */
    public function store(Request $request)
    {
        $request->validate([
            // Validation pour la table users
            'name' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|min:8|confirmed',

            // Validation pour la table client_dossiers
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
            'type_logement' => 'nullable|array',
            'type_logement_autres' => 'nullable|string|max:255',
            'quartier_souhaite' => 'nullable|string|max:255',
            'budget_mensuel' => 'nullable|numeric|min:0',
            'date_entree_souhaitee' => 'nullable|date',
            'carte_identite' => 'boolean',
            'dernier_recu_loyer' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            // 1. Vérifier si l'utilisateur existe déjà
            $user = User::where('email', $request->email)->first();

            if ($user) {
                if ($user->dossierClient) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Un dossier existe déjà pour cet email. Veuillez vous connecter pour le modifier.');
                }
            } else {
                // Créer un nouvel utilisateur
                $user = User::create([
                    'name' => $request->name . ' ' . $request->prenom,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);
            }

            // 2. Assigner le rôle "client" (Spatie)
            if (!$user->hasRole('client')) {
                $user->assignRole('client');
            }

            // 3. Créer le dossier client
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
                'type_logement' => $request->type_logement,
                'type_logement_autres' => $request->type_logement_autres,
                'quartier_souhaite' => $request->quartier_souhaite,
                'budget_mensuel' => $request->budget_mensuel,
                'date_entree_souhaitee' => $request->date_entree_souhaitee,
                'carte_identite' => $request->boolean('carte_identite'),
                'dernier_recu_loyer' => $request->boolean('dernier_recu_loyer'),
            ]);

            // 4. Connecter automatiquement l'utilisateur
            Auth::login($user);

            DB::commit();

            // 🔥 Rechercher des biens correspondants
            $biensCorrespondants = $this->matchingService->rechercherBiensCorrespondants($dossier);

            if ($biensCorrespondants->count() > 0) {
                return redirect()->route('home')->with('success',
                    "Demande enregistrée avec succès ! Nous avons trouvé {$biensCorrespondants->count()} bien(s) correspondant à vos critères."
                );
            }

            return redirect()->route('home')->with('success',
                'Demande enregistrée avec succès ! Vous serez notifié dès qu\'un logement correspondant sera disponible.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
        }
    }

    /**
     * Afficher le formulaire d'édition d’un dossier
     */
    public function edit(string $id)
    {
        $dossier = ClientDossier::with('client')->findOrFail($id);

        return Inertia::render('ClientDossiers/Edit', [
            'dossier' => $dossier,
        ]);
    }

    /**
     * Mettre à jour un dossier client
     */
    public function update(Request $request, string $id)
    {
        $dossier = ClientDossier::findOrFail($id);

        $request->validate([
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
            'type_logement' => 'nullable|array',
            'type_logement_autres' => 'nullable|string|max:255',
            'quartier_souhaite' => 'nullable|string|max:255',
            'budget_mensuel' => 'nullable|numeric|min:0',
            'date_entree_souhaitee' => 'nullable|date',
            'carte_identite' => 'boolean',
            'dernier_recu_loyer' => 'boolean',
        ]);

        $dossier->update($request->only([
            'profession',
            'numero_cni',
            'personne_contact',
            'telephone_contact',
            'revenus_mensuels',
            'nombre_personnes',
            'nbchambres',
            'nbsalons',
            'nbcuisines',
            'nbsalledebains',
            'situation_familiale',
            'type_logement',
            'type_logement_autres',
            'quartier_souhaite',
            'budget_mensuel',
            'date_entree_souhaitee',
            'carte_identite',
            'dernier_recu_loyer',
        ]));

        return redirect()->back()->with('success', 'Dossier mis à jour avec succès !');
    }

    /**
     * Supprimer un dossier client
     */
    public function destroy(string $id)
    {
        $dossier = ClientDossier::findOrFail($id);
        $dossier->delete();

        return redirect()->back()->with('success', 'Dossier supprimé avec succès !');
    }
}
