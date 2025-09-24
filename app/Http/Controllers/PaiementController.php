<?php
namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Vente;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\Bien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PaiementController extends Controller
{
    /**
     * Afficher la page de succès avec les options appropriées
     */
    public function showSucces(Paiement $paiement)
    {
        // Charger les relations selon le type
        $paiement->load([
            'reservation.bien.mandat',
            'location.bien.mandat',
            'vente.bien.mandat'
        ]);

        // Déterminer les actions disponibles selon le contexte
        $actionsDisponibles = $this->getActionsDisponibles($paiement);

        return Inertia::render('Paiement/Succes', [
            'paiement' => $paiement,
            'actionsDisponibles' => $actionsDisponibles
        ]);
    }

    /**
     * Déterminer les actions disponibles après paiement réussi
     */
    private function getActionsDisponibles(Paiement $paiement)
    {
        $actions = [
            'peutVisiter' => false,
            'peutProcederVente' => false,
            'peutProcederLocation' => false,
            'bien' => null,
            'typeMandat' => null
        ];

        // Si c'est une réservation avec paiement réussi
        if ($paiement->reservation_id && $paiement->statut === 'reussi') {
            $reservation = $paiement->reservation;
            $bien = $reservation->bien;
            $mandat = $bien->mandat;

            if ($bien && $mandat) {
                $actions['bien'] = $bien;
                $actions['typeMandat'] = $mandat->type_mandat;

                // Toujours permettre la visite
                $actions['peutVisiter'] = true;

                // Gérer les deux types de mandats
                if ($mandat->type_mandat === 'vente' && $bien->status !== 'vendu') {
                    $actions['peutProcederVente'] = true;
                } elseif ($mandat->type_mandat === 'gestion_locative' && $bien->status !== 'loue') {
                    $actions['peutProcederLocation'] = true;
                }
            }
        }

        return $actions;
    }
    /**
     * Afficher la page d'initiation du paiement
     */
    public function showInitierPaiement(Request $request): \Inertia\Response
    {
        $type = $request->input('type'); // reservation, location, vente
        $id = $request->input('id');
        $paiementId = $request->input('paiement_id');

        // Récupérer les données selon le type
        $item = null;
        $paiement = null;

        if ($paiementId) {
            $paiement = Paiement::findOrFail($paiementId);
        }

        switch ($type) {
            case 'reservation':
                $item = Reservation::with(['bien', 'client'])->findOrFail($id);
                break;
            case 'location':
                $item = Location::with(['bien', 'client'])->findOrFail($id);
                break;
            case 'vente':
                $item = Vente::with(['bien', 'client'])->findOrFail($id);
                break;
            default:
                abort(400, 'Type de paiement invalide');
        }

        // Vérifier les permissions
        if ($item->client_id !== auth()->id()) {
            abort(403, 'Accès non autorisé');
        }

        return Inertia::render('Paiement/InitierPaiement', [
            'type' => $type,
            'item' => $item,
            'paiement' => $paiement,
            'user' => auth()->user()
        ]);
    }

    /**
     * Afficher la page d'erreur
     */
    public function showErreur(Request $request)
    {
        $message = $request->session()->get('error', 'Une erreur est survenue lors du paiement');

        return Inertia::render('Paiement/Erreur', [
            'message' => $message
        ]);
    }

    /**
     * Lister tous les paiements
     */
    public function index()
    {
        $paiements = Paiement::with(['vente', 'location', 'reservation'])->get();
        return response()->json($paiements);
    }

    /**
     * Créer un paiement
     */
    public function store(Request $request)
    {
        $request->validate([
            'type'            => 'required|in:vente,location,reservation',
            'montant_total'   => 'required|numeric|min:0',
            'montant_paye'    => 'required|numeric|min:0',
            'mode_paiement'   => 'required|in:carte,mobile_money,virement',
            'transaction_id'  => 'nullable|string|max:255',
            'vente_id'        => 'nullable|exists:ventes,id',
            'location_id'     => 'nullable|exists:locations,id',
            'reservation_id'  => 'nullable|exists:reservations,id',
        ]);

        // Création du paiement
        $paiement = new Paiement();
        $paiement->type             = $request->type;
        $paiement->montant_total    = $request->montant_total;
        $paiement->montant_paye     = $request->montant_paye;
        $paiement->montant_restant  = $request->montant_total - $request->montant_paye;
        $paiement->commission_agence = $request->montant_total * 0.05;
        $paiement->mode_paiement    = $request->mode_paiement;
        $paiement->transaction_id   = $request->transaction_id;
        $paiement->statut           = 'en_attente';
        $paiement->date_transaction = now();

        // Associer selon type
        if ($request->type === 'vente') {
            $paiement->vente_id = $request->vente_id;
        } elseif ($request->type === 'location') {
            $paiement->location_id = $request->location_id;
        } elseif ($request->type === 'reservation') {
            $paiement->reservation_id = $request->reservation_id;
        }

        $paiement->save();

        return response()->json([
            'message' => 'Paiement enregistré avec succès.',
            'paiement' => $paiement
        ], 201);
    }

    /**
     * Afficher un paiement
     */
    public function show($id)
    {
        $paiement = Paiement::with(['vente', 'location', 'reservation'])->findOrFail($id);
        return response()->json($paiement);
    }

    /**
     * Mettre à jour un paiement (ex: après confirmation CinetPay)
     */
    public function update(Request $request, $id)
    {
        $paiement = Paiement::findOrFail($id);

        $request->validate([
            'statut' => 'in:en_attente,reussi,echoue',
            'montant_paye' => 'nullable|numeric|min:0',
        ]);

        if ($request->has('statut')) {
            $paiement->statut = $request->statut;
        }

        if ($request->has('montant_paye')) {
            $paiement->montant_paye = $request->montant_paye;
            $paiement->montant_restant = $paiement->montant_total - $paiement->montant_paye;
        }

        $paiement->save();

        return response()->json([
            'message' => 'Paiement mis à jour avec succès.',
            'paiement' => $paiement
        ]);
    }

    /**
     * Supprimer un paiement
     */
    public function destroy($id)
    {
        $paiement = Paiement::findOrFail($id);
        $paiement->delete();

        return response()->json(['message' => 'Paiement supprimé avec succès.']);
    }

    public function initierSansDelai(Request $request)
    {
        $request->validate([
            'paiement_id' => 'required|exists:paiements,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'description' => 'nullable|string|max:255',
            'mode_paiement' => 'required|in:mobile_money,carte,virement'
        ]);

        try {
            $paiement = Paiement::findOrFail($request->paiement_id);

            // Mettre à jour le mode de paiement
            $paiement->mode_paiement = $request->mode_paiement;

            // Générer un transaction_id unique si pas déjà défini
            if (!$paiement->transaction_id) {
                $paiement->transaction_id = 'TXN_' . Str::upper(Str::random(10)) . '_' . time();
            }

            // Vérifier si on doit simuler le paiement
            if (env('SIMULATE_PAYMENT', false) || env('APP_ENV') === 'local') {
                // MODE SIMULATION
                $paiement->statut = 'reussi';
                $paiement->montant_paye = $paiement->montant_total;
                $paiement->montant_restant = 0;
                $paiement->date_transaction = now();
                $paiement->save();

                // Mettre à jour le statut de l'élément associé
                $this->updateItemStatus($paiement);

                Log::info('Paiement simulé avec succès', [
                    'paiement_id' => $paiement->id,
                    'transaction_id' => $paiement->transaction_id,
                    'montant' => $paiement->montant_total,
                    'statut' => $paiement->statut
                ]);

                // Rediriger vers la page de succès
                return redirect()->route('paiement.succes', $paiement)
                    ->with('success', 'Paiement simulé avec succès !');
            }

            // Si simulation désactivée, utiliser le code CinetPay normal
            $paiement->save();

            // ... votre code CinetPay existant ici ...

        } catch (\Exception $e) {
            Log::error('Erreur simulation paiement: ' . $e->getMessage());

            return redirect()->route('paiement.erreur')
                ->with('error', 'Erreur lors de la simulation du paiement');
        }
    }

    // Méthode alternative avec délai pour simuler un traitement
    public function initier(Request $request)
    {
        $request->validate([
            'paiement_id' => 'required|exists:paiements,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'description' => 'nullable|string|max:255',
            'mode_paiement' => 'required|in:mobile_money,carte,virement'
        ]);

        try {
            $paiement = Paiement::findOrFail($request->paiement_id);

            // Mise à jour initiale
            $paiement->mode_paiement = $request->mode_paiement;
            if (!$paiement->transaction_id) {
                $paiement->transaction_id = 'TXN_' . Str::upper(Str::random(10)) . '_' . time();
            }
            $paiement->statut = 'en_attente';
            $paiement->save();

            // Simuler un délai de traitement (optionnel)
            sleep(2);

            // Marquer comme réussi
            $paiement->statut = 'reussi';
            $paiement->montant_paye = $paiement->montant_total;
            $paiement->montant_restant = 0;
            $paiement->date_transaction = now();
            $paiement->save();

            $this->updateItemStatus($paiement);

            return redirect()->route('paiement.succes', $paiement);

        } catch (\Exception $e) {
            return redirect()->route('paiement.erreur')
                ->with('error', 'Erreur lors de la simulation');
        }
    }

    // ... rest of your existing methods (notify, retour, etc.) ...

    /**
     * Mettre à jour le statut de l'élément associé au paiement
     */
    private function updateItemStatus(Paiement $paiement)
    {
        if ($paiement->reservation_id) {
            $paiement->reservation->update(['statut' => 'confirmée']);

            if ($paiement->reservation->bien) {
                $paiement->reservation->bien->update(['status' => 'reserve']);
            }
        } elseif ($paiement->location_id) {
            $paiement->location->update(['statut' => 'confirmée']);

            if ($paiement->location->bien) {
                $paiement->location->bien->update(['status' => 'loue']);
            }
        } elseif ($paiement->vente_id) {
            $paiement->vente->update(['statut' => 'confirmée']);

            if ($paiement->vente->bien) {
                $paiement->vente->bien->update(['status' => 'vendu']);
            }
        }
    }
}
