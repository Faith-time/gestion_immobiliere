<?php

namespace App\Services;

use App\Models\Paiement;
use App\Models\Location;
use App\Models\Vente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class QuittanceService
{
    /**
     * ✅ PAIEMENT INITIAL LOCATION : Dépôt réservation + Caution + 1er mois = 3 mois
     */
    public function genererEtEnvoyerQuittancePaiementLocation(Paiement $paiement)
    {
        try {
            if ($paiement->type !== 'location' || !$paiement->location) {
                throw new \Exception('Ce paiement n\'est pas de type location');
            }

            // ✅ CHARGER TOUTES LES RELATIONS NÉCESSAIRES INCLUANT LE DOSSIER CLIENT
            $location = $paiement->location->load([
                'client',
                'client.dossierClient', // ✅ AJOUT : Charger le dossier client
                'reservation.bien.proprietaire',
                'reservation.bien.proprietaire.dossierClient', // ✅ AJOUT : Dossier propriétaire si existe
                'reservation.appartement',
                'reservation.paiement'
            ]);

            // Calculer les pénalités de retard
            $penalites = $this->calculerPenalitesRetardPremierPaiement($paiement, $location);

            // Générer le numéro de quittance
            $numeroQuittance = $this->genererNumeroQuittance($paiement);

            // ✅ RÉCUPÉRER LE DÉPÔT DE GARANTIE DE LA RÉSERVATION
            $depotGarantieReservation = 0;
            if ($location->reservation && $location->reservation->paiement) {
                $paiementReservation = $location->reservation->paiement;
                if ($paiementReservation->statut === 'reussi') {
                    $depotGarantieReservation = $paiementReservation->montant_total;
                }
            }

            // Calcul des montants
            $loyerMensuel = $location->loyer_mensuel;
            $caution = $loyerMensuel; // 1 mois
            $premierMois = $loyerMensuel; // 1 mois
            $commissionAgence = $loyerMensuel * 0.10;

            // ✅ TOTAL = 3 MOIS (Dépôt réservation + Caution + 1er mois)
            $totalAvecDepot = $depotGarantieReservation + $caution + $premierMois + $penalites['montant'];

            // ✅ RÉCUPÉRER LES DONNÉES DU DOSSIER CLIENT
            $dossierClient = $location->client->dossierClient;

            $nomCompletLocataire = $location->client->name;
            $telephoneLocataire = 'N/A';
            $civiliteLocataire = 'M.';

            if ($dossierClient) {
                // Utiliser le téléphone de contact du dossier en priorité
                $telephoneLocataire = $dossierClient->telephone_contact ?? $location->client->telephone ?? 'N/A';

                // Vous pouvez ajouter un champ 'civilite' dans client_dossiers si besoin
                // $civiliteLocataire = $dossierClient->civilite ?? 'M.';
            } else {
                $telephoneLocataire = $location->client->telephone ?? 'N/A';
            }

            // ✅ RÉCUPÉRER LES DONNÉES DU PROPRIÉTAIRE
            $proprietaire = $location->reservation->bien->proprietaire;
            $dossierProprietaire = $proprietaire->dossierClient ?? null;

            $nomCompletProprietaire = $proprietaire->name;

            // Données pour la quittance
            $data = [
                'numero_quittance' => $numeroQuittance,
                'matricule' => $this->genererMatricule($location),
                'type_paiement' => 'Premier paiement de location',

                // ✅ Informations locataire CORRIGÉES
                'locataire' => [
                    'nom' => $nomCompletLocataire,
                    'civilite' => $civiliteLocataire,
                    'telephone' => $telephoneLocataire,
                    'email' => $location->client->email,
                ],

                // ✅ Informations propriétaire CORRIGÉES
                'proprietaire' => [
                    'nom' => $nomCompletProprietaire,
                ],

                // Informations bien
                'bien' => [
                    'adresse' => $location->reservation->bien->address,
                    'ville' => $location->reservation->bien->city,
                ],

                // Informations appartement (si applicable)
                'appartement' => $location->reservation->appartement ? [
                    'numero' => $location->reservation->appartement->numero,
                    'etage' => $location->reservation->appartement->etage,
                ] : null,

                // ✅ DÉTAIL DES MONTANTS - 3 MOIS
                'depot_garantie_reservation' => $depotGarantieReservation,
                'caution' => $caution,
                'loyer' => $premierMois,
                'commission' => $commissionAgence,

                // Autres frais
                'arrieres' => 0,
                'penalites' => $penalites['montant'],
                'jours_retard' => $penalites['jours'],
                'avance' => 0,
                'tom' => 0,
                'charges' => 0,
                'taxe_tva' => 0,
                'frais_justice' => 0,
                'provision_eau' => 0,
                'provision_elect' => 0,
                'reliquat' => 0,

                // Totaux
                'total' => $totalAvecDepot,
                'montant_paye' => $paiement->montant_total,
                'mois_concerne' => Carbon::parse($location->date_debut)->translatedFormat('F Y'),
                'date_paiement' => Carbon::parse($paiement->date_transaction)->format('d/m/Y'),
                'montant_lettre' => $this->nombreEnLettres($totalAvecDepot),
            ];

            // 📄 GÉNÉRER LE PDF
            $pdf = Pdf::loadView('emails.quittance-loyer', $data);
            $pdfContent = $pdf->output();

            // 💾 SAUVEGARDER LE PDF LOCALEMENT
            $pdfFileName = 'quittance-' . $numeroQuittance . '.pdf';
            $pdfPath = 'quittances/' . $location->id . '/' . $pdfFileName;
            Storage::disk('public')->put($pdfPath, $pdfContent);

            // 📧 ENVOYER PAR EMAIL
            Mail::send('emails.quittance-loyer', $data, function ($message) use ($location, $pdfContent, $numeroQuittance, $nomCompletLocataire) {
                $message->to($location->client->email, $nomCompletLocataire)
                    ->subject('Quittance de paiement N°' . $numeroQuittance . ' - Cauris Immobilier')
                    ->attachData($pdfContent, 'quittance-' . $numeroQuittance . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            });

            // 💬 ENVOYER DANS LA MESSAGERIE INTERNE
            $this->envoyerQuittanceDansMessagerie($location, $pdfPath, $pdfFileName, $numeroQuittance, $data);

            Log::info('📧 Quittance paiement location envoyée (Email + Messagerie)', [
                'paiement_id' => $paiement->id,
                'location_id' => $location->id,
                'numero_quittance' => $numeroQuittance,
                'email' => $location->client->email,
                'telephone' => $telephoneLocataire,
                'pdf_path' => $pdfPath,
                'depot_reservation' => $depotGarantieReservation,
                'caution' => $caution,
                'premier_mois' => $premierMois,
                'total_3_mois' => $totalAvecDepot,
            ]);

            return [
                'success' => true,
                'message' => 'Quittance envoyée avec succès (Email + Messagerie)',
                'numero_quittance' => $numeroQuittance,
                'pdf_path' => $pdfPath,
            ];

        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi quittance paiement location', [
                'error' => $e->getMessage(),
                'paiement_id' => $paiement->id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la quittance : ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ✅ LOYER MENSUEL : Uniquement le loyer du mois (+ pénalités si retard)
     */
    public function genererEtEnvoyerQuittanceLoyer(Paiement $paiement)
    {
        try {
            if ($paiement->type !== 'loyer_mensuel' || !$paiement->location) {
                throw new \Exception('Ce paiement n\'est pas un loyer mensuel');
            }

            // ✅ CHARGER AVEC LE DOSSIER CLIENT
            $location = $paiement->location->load([
                'client',
                'client.dossierClient', // ✅ AJOUT
                'reservation.bien.proprietaire',
                'reservation.bien.proprietaire.dossierClient', // ✅ AJOUT
                'reservation.appartement'
            ]);

            // Calculer les pénalités de retard
            $penalites = $this->calculerPenalitesRetard($paiement, $location);

            // Générer le numéro de quittance
            $numeroQuittance = $this->genererNumeroQuittance($paiement);

            // ✅ RÉCUPÉRER LES DONNÉES DU DOSSIER CLIENT
            $dossierClient = $location->client->dossierClient;

            $nomCompletLocataire = $location->client->name;
            $telephoneLocataire = 'N/A';
            $civiliteLocataire = 'M.';

            if ($dossierClient) {
                $telephoneLocataire = $dossierClient->telephone_contact ?? $location->client->telephone ?? 'N/A';
            } else {
                $telephoneLocataire = $location->client->telephone ?? 'N/A';
            }

            // ✅ RÉCUPÉRER LES DONNÉES DU PROPRIÉTAIRE
            $proprietaire = $location->reservation->bien->proprietaire;
            $nomCompletProprietaire = $proprietaire->name;

            // Données pour la quittance
            $data = [
                'numero_quittance' => $numeroQuittance,
                'matricule' => $this->genererMatricule($location),
                'type_paiement' => 'Loyer mensuel',

                // ✅ Informations locataire CORRIGÉES
                'locataire' => [
                    'nom' => $nomCompletLocataire,
                    'civilite' => $civiliteLocataire,
                    'telephone' => $telephoneLocataire,
                    'email' => $location->client->email,
                ],

                // ✅ Informations propriétaire CORRIGÉES
                'proprietaire' => [
                    'nom' => $nomCompletProprietaire,
                ],

                'bien' => [
                    'adresse' => $location->reservation->bien->address,
                    'ville' => $location->reservation->bien->city,
                ],

                'appartement' => $location->reservation->appartement ? [
                    'numero' => $location->reservation->appartement->numero,
                    'etage' => $location->reservation->appartement->etage,
                ] : null,

                // ✅ LOYER MENSUEL UNIQUEMENT
                'depot_garantie_reservation' => 0,
                'caution' => 0,
                'loyer' => $location->loyer_mensuel,
                'arrieres' => 0,
                'penalites' => $penalites['montant'],
                'jours_retard' => $penalites['jours'],
                'avance' => 0,
                'commission' => $location->loyer_mensuel * 0.10,
                'tom' => 0,
                'charges' => 0,
                'taxe_tva' => 0,
                'frais_justice' => 0,
                'provision_eau' => 0,
                'provision_elect' => 0,
                'reliquat' => 0,

                'total' => $location->loyer_mensuel + $penalites['montant'],
                'montant_paye' => $paiement->montant_total,
                'mois_concerne' => Carbon::parse($paiement->created_at)->translatedFormat('F Y'),
                'date_paiement' => Carbon::parse($paiement->date_transaction)->format('d/m/Y'),
                'montant_lettre' => $this->nombreEnLettres($paiement->montant_total),
            ];

            // Générer le PDF
            $pdf = Pdf::loadView('emails.quittance-loyer', $data);
            $pdfContent = $pdf->output();

            // 💾 SAUVEGARDER LE PDF LOCALEMENT
            $pdfFileName = 'quittance-loyer-' . $numeroQuittance . '.pdf';
            $pdfPath = 'quittances/' . $location->id . '/' . $pdfFileName;
            Storage::disk('public')->put($pdfPath, $pdfContent);

            // Envoyer l'email
            Mail::send('emails.quittance-loyer', $data, function ($message) use ($location, $pdfContent, $numeroQuittance, $nomCompletLocataire) {
                $message->to($location->client->email, $nomCompletLocataire)
                    ->subject('Quittance de loyer N°' . $numeroQuittance . ' - Cauris Immobilier')
                    ->attachData($pdfContent, 'quittance-loyer-' . $numeroQuittance . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            });

            // 💬 ENVOYER DANS LA MESSAGERIE INTERNE
            $this->envoyerQuittanceDansMessagerie($location, $pdfPath, $pdfFileName, $numeroQuittance, $data);

            Log::info('📧 Quittance de loyer envoyée', [
                'paiement_id' => $paiement->id,
                'location_id' => $location->id,
                'numero_quittance' => $numeroQuittance,
                'email' => $location->client->email,
                'telephone' => $telephoneLocataire,
            ]);

            return [
                'success' => true,
                'message' => 'Quittance envoyée avec succès',
                'numero_quittance' => $numeroQuittance,
            ];

        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi quittance loyer', [
                'error' => $e->getMessage(),
                'paiement_id' => $paiement->id,
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la quittance : ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ✅ REÇU DE VENTE
     */
    public function genererEtEnvoyerRecuVente(Vente $vente, Paiement $paiement)
    {
        try {
            if ($paiement->type !== 'vente' || !$vente) {
                throw new \Exception('Ce paiement n\'est pas une vente');
            }

            // ✅ CHARGER AVEC LES DOSSIERS
            $vente->load([
                'acheteur',
                'acheteur.dossierClient', // ✅ AJOUT
                'reservation.bien.proprietaire',
                'reservation.bien.proprietaire.dossierClient', // ✅ AJOUT
                'reservation.bien.category',
            ]);

            $numeroRecu = $this->genererNumeroRecu($vente);
            $prixTotal = $vente->prix_vente;
            $depotGarantie = $prixTotal * 0.10;
            $montantRestant = $prixTotal - $depotGarantie;
            $commissionAgence = $prixTotal * 0.05;

            // ✅ RÉCUPÉRER LES DONNÉES DE L'ACHETEUR
            $dossierAcheteur = $vente->acheteur->dossierClient;
            $nomCompletAcheteur = $vente->acheteur->name;
            $telephoneAcheteur = 'N/A';
            $adresseAcheteur = 'N/A';

            if ($dossierAcheteur) {
                $telephoneAcheteur = $dossierAcheteur->telephone_contact ?? $vente->acheteur->telephone ?? 'N/A';
                $adresseAcheteur = $dossierAcheteur->quartier_souhaite ?? 'N/A';
            } else {
                $telephoneAcheteur = $vente->acheteur->telephone ?? 'N/A';
            }

            // ✅ RÉCUPÉRER LES DONNÉES DU VENDEUR
            $dossierVendeur = $vente->reservation->bien->proprietaire->dossierClient ?? null;
            $nomCompletVendeur = $vente->reservation->bien->proprietaire->name;
            $telephoneVendeur = $dossierVendeur
                ? ($dossierVendeur->telephone_contact ?? $vente->reservation->bien->proprietaire->telephone ?? 'N/A')
                : ($vente->reservation->bien->proprietaire->telephone ?? 'N/A');

            $data = [
                'numero_recu' => $numeroRecu,

                // ✅ Informations acheteur CORRIGÉES
                'acheteur' => [
                    'nom' => $nomCompletAcheteur,
                    'telephone' => $telephoneAcheteur,
                    'email' => $vente->acheteur->email,
                    'adresse' => $adresseAcheteur,
                ],

                // ✅ Informations vendeur CORRIGÉES
                'vendeur' => [
                    'nom' => $nomCompletVendeur,
                    'telephone' => $telephoneVendeur,
                ],

                'bien' => [
                    'titre' => $vente->reservation->bien->title,
                    'adresse' => $vente->reservation->bien->address,
                    'ville' => $vente->reservation->bien->city,
                    'superficie' => number_format($vente->reservation->bien->superficy, 0, ',', ' ') . ' m²',
                    'type' => $vente->reservation->bien->category->name ?? 'N/A',
                    'description' => $vente->reservation->bien->description,
                ],

                'transaction' => [
                    'prix_total' => $prixTotal,
                    'depot_garantie' => $depotGarantie,
                    'montant_restant_paye' => $montantRestant,
                    'commission_agence' => $commissionAgence,
                    'montant_net_vendeur' => $prixTotal - $commissionAgence,
                ],

                'paiement' => [
                    'montant_paye' => $paiement->montant_total,
                    'mode_paiement' => $this->getModePaiementLabel($paiement->mode_paiement),
                    'date_paiement' => Carbon::parse($paiement->date_transaction)->format('d/m/Y'),
                    'transaction_id' => $paiement->transaction_id,
                ],

                'date_vente' => Carbon::parse($vente->date_vente)->format('d/m/Y'),
                'montant_lettre' => $this->nombreEnLettres($prixTotal),
                'vente' => $vente,
            ];

            $pdf = Pdf::loadView('pdf.recu-vente', $data);
            $pdfContent = $pdf->output();

            Mail::send('emails.recu-vente', $data, function ($message) use ($vente, $pdfContent, $numeroRecu, $nomCompletAcheteur) {
                $message->to($vente->acheteur->email, $nomCompletAcheteur)
                    ->subject('Reçu de vente N°' . $numeroRecu . ' - Cauris Immobilier')
                    ->attachData($pdfContent, 'recu-vente-' . $numeroRecu . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            });

            Log::info('📧 Reçu de vente envoyé', [
                'vente_id' => $vente->id,
                'paiement_id' => $paiement->id,
                'numero_recu' => $numeroRecu,
                'email' => $vente->acheteur->email,
                'telephone' => $telephoneAcheteur,
            ]);

            return [
                'success' => true,
                'message' => 'Reçu de vente envoyé avec succès',
                'numero_recu' => $numeroRecu,
            ];

        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi reçu vente', [
                'error' => $e->getMessage(),
                'vente_id' => $vente->id,
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du reçu : ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 💬 Envoyer la quittance dans la messagerie interne
     */
    private function envoyerQuittanceDansMessagerie($location, $pdfPath, $pdfFileName, $numeroQuittance, $data)
    {
        try {
            $adminId = \App\Models\User::role('admin')->first()?->id;

            $conversation = \App\Models\Conversation::firstOrCreate(
                [
                    'client_id' => $location->client_id,
                    'admin_id' => $adminId,
                    'subject' => '💰 Quittances de loyer - Location #' . $location->id,
                ],
                [
                    'status' => 'active',
                    'last_message_at' => now(),
                ]
            );

            if (!$conversation->hasParticipant($location->client_id)) {
                $conversation->participants()->attach($location->client_id);
            }
            if ($adminId && !$conversation->hasParticipant($adminId)) {
                $conversation->participants()->attach($adminId);
            }

            $messageText = "✅ **Quittance de paiement N°{$numeroQuittance}**\n\n";
            $messageText .= "Bonjour,\n\n";
            $messageText .= "Votre paiement a été confirmé avec succès.\n";
            $messageText .= "Voici votre quittance officielle :\n\n";
            $messageText .= "📅 **Période** : {$data['mois_concerne']}\n";
            $messageText .= "💰 **Montant payé** : " . number_format($data['montant_paye'], 0, ',', ' ') . " FCFA\n";
            $messageText .= "📍 **Bien** : {$data['bien']['adresse']}, {$data['bien']['ville']}\n";

            if ($data['appartement']) {
                $messageText .= "🏢 **Appartement** : N°{$data['appartement']['numero']} - Étage {$data['appartement']['etage']}\n";
            }

            $messageText .= "\n📎 Téléchargez votre quittance ci-dessous.\n";
            $messageText .= "\nCordialement,\n**L'équipe Cauris Immobilier**";

            $message = $conversation->messages()->create([
                'sender_id' => $adminId ?? 1,
                'message' => $messageText,
                'type' => 'file',
                'file_path' => $pdfPath,
                'file_name' => $pdfFileName,
                'file_type' => 'application/pdf',
            ]);

            $conversation->update(['last_message_at' => now()]);

            $conversation->participantDetails()
                ->where('user_id', $location->client_id)
                ->first()
                ?->incrementUnread();

            Log::info('💬 Quittance envoyée dans la messagerie', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'client_id' => $location->client_id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi quittance dans messagerie', [
                'error' => $e->getMessage(),
                'location_id' => $location->id,
            ]);
            return false;
        }
    }

    /**
     * 📊 Calculer les pénalités de retard pour loyer mensuel
     */
    private function calculerPenalitesRetard(Paiement $paiement, Location $location)
    {
        $moisPaiement = Carbon::parse($paiement->created_at)->startOfMonth();
        $dateEcheance = $moisPaiement->copy()->day(10);
        $datePaiement = Carbon::parse($paiement->date_transaction);

        $joursRetard = 0;
        $montantPenalites = 0;

        if ($datePaiement->gt($dateEcheance)) {
            $joursRetard = $datePaiement->diffInDays($dateEcheance);
            $montantPenalites = ($location->loyer_mensuel * 0.05) * $joursRetard;
        }

        return [
            'jours' => $joursRetard,
            'montant' => $montantPenalites,
        ];
    }

    /**
     * 📊 Calculer les pénalités de retard pour premier paiement
     */
    private function calculerPenalitesRetardPremierPaiement(Paiement $paiement, Location $location)
    {
        $dateCreation = Carbon::parse($location->created_at);
        $dateLimite = $dateCreation->copy()->addDays(3);
        $datePaiement = Carbon::parse($paiement->date_transaction);

        $joursRetard = 0;
        $montantPenalites = 0;

        if ($datePaiement->gt($dateLimite)) {
            $joursRetard = $datePaiement->diffInDays($dateLimite);
            $montantPenalites = ($paiement->montant_total * 0.05) * $joursRetard;
        }

        return [
            'jours' => $joursRetard,
            'montant' => $montantPenalites,
        ];
    }

    /**
     * 🔢 Générer un numéro de quittance
     */
    private function genererNumeroQuittance(Paiement $paiement)
    {
        return 'Q' . date('Y') . str_pad($paiement->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * 🔢 Générer un numéro de reçu de vente
     */
    private function genererNumeroRecu(Vente $vente)
    {
        return 'RV' . date('Y') . str_pad($vente->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * 🔢 Générer un matricule de location
     */
    private function genererMatricule(Location $location)
    {
        return 'LOC' . str_pad($location->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 💳 Obtenir le libellé du mode de paiement
     */
    private function getModePaiementLabel($mode)
    {
        $modes = [
            'orange_money' => 'Orange Money',
            'wave' => 'Wave',
            'free_money' => 'Free Money',
            'especes' => 'Espèces',
            'cheque' => 'Chèque',
            'virement' => 'Virement bancaire',
        ];

        return $modes[$mode] ?? 'Autre';
    }

    /**
     * 🔤 Convertir un nombre en lettres (Français)
     */
    private function nombreEnLettres($nombre)
    {
        $nombre = intval($nombre);

        if ($nombre == 0) return 'zéro Francs CFA';

        $unites = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
        $dizaines = ['', 'dix', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante-dix', 'quatre-vingt', 'quatre-vingt-dix'];
        $speciaux = ['dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize'];

        $resultat = '';

        // Millions
        $millions = intval($nombre / 1000000);
        if ($millions > 0) {
            $resultat .= ($millions == 1 ? 'un million' : $this->nombreEnLettresPartiel($millions) . ' millions') . ' ';
            $nombre = $nombre % 1000000;
        }

        // Milliers
        $milliers = intval($nombre / 1000);
        if ($milliers > 0) {
            $resultat .= ($milliers == 1 ? 'mille' : $this->nombreEnLettresPartiel($milliers) . ' mille') . ' ';
            $nombre = $nombre % 1000;
        }

        // Centaines
        $centaines = intval($nombre / 100);
        if ($centaines > 0) {
            $resultat .= ($centaines == 1 ? 'cent' : $unites[$centaines] . ' cent') . ' ';
            $nombre = $nombre % 100;
        }

        // Dizaines et unités
        if ($nombre >= 17 && $nombre <= 99) {
            $dizaine = intval($nombre / 10);
            $unite = $nombre % 10;

            if ($dizaine == 7 || $dizaine == 9) {
                $resultat .= $dizaines[$dizaine - 1] . '-' . ($dizaine == 7 ? $speciaux[$unite] : $dizaines[1] . '-' . $unites[$unite]);
            } else if ($dizaine == 8 && $unite == 0) {
                $resultat .= 'quatre-vingts';
            } else if ($dizaine == 8) {
                $resultat .= 'quatre-vingt-' . $unites[$unite];
            } else {
                $resultat .= $dizaines[$dizaine];
                if ($unite > 0) {
                    $resultat .= ($unite == 1 && $dizaine < 8 ? ' et ' : '-') . $unites[$unite];
                }
            }
        } else if ($nombre >= 10 && $nombre <= 16) {
            $resultat .= $speciaux[$nombre - 10];
        } else if ($nombre > 0) {
            $resultat .= $unites[$nombre];
        }

        return trim($resultat) . ' Francs CFA';
    }

    /**
     * 🔤 Convertir une partie d'un nombre en lettres (helper)
     */
    private function nombreEnLettresPartiel($nombre)
    {
        $unites = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
        $dizaines = ['', 'dix', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante-dix', 'quatre-vingt', 'quatre-vingt-dix'];
        $speciaux = ['dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize'];

        if ($nombre == 0) return '';

        $resultat = '';

        // Centaines
        $centaines = intval($nombre / 100);
        if ($centaines > 0) {
            $resultat .= ($centaines == 1 ? 'cent' : $unites[$centaines] . ' cent') . ' ';
            $nombre = $nombre % 100;
        }

        // Dizaines et unités
        if ($nombre >= 17 && $nombre <= 99) {
            $dizaine = intval($nombre / 10);
            $unite = $nombre % 10;

            if ($dizaine == 7 || $dizaine == 9) {
                $resultat .= $dizaines[$dizaine - 1] . '-' . ($dizaine == 7 ? $speciaux[$unite] : $dizaines[1] . '-' . $unites[$unite]);
            } else if ($dizaine == 8 && $unite == 0) {
                $resultat .= 'quatre-vingts';
            } else if ($dizaine == 8) {
                $resultat .= 'quatre-vingt-' . $unites[$unite];
            } else {
                $resultat .= $dizaines[$dizaine];
                if ($unite > 0) {
                    $resultat .= ($unite == 1 && $dizaine < 8 ? ' et ' : '-') . $unites[$unite];
                }
            }
        } else if ($nombre >= 10 && $nombre <= 16) {
            $resultat .= $speciaux[$nombre - 10];
        } else if ($nombre > 0) {
            $resultat .= $unites[$nombre];
        }

        return trim($resultat);
    }
}
