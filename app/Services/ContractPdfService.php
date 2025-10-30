<?php

namespace App\Services;

use App\Models\Vente;
use App\Models\Location;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ContractPdfService
{
    /**
     * Générer le PDF d'un contrat (vente ou location)
     */
    public function generatePdf($contract, $type)
    {
        try {
            // Valider le type
            if (!in_array($type, ['vente', 'location'])) {
                throw new \Exception('Type de contrat non supporté');
            }

            $data = ($type === 'vente') ? $this->getVentePdfData($contract) : $this->getLocationPdfData($contract);

            // ✅ MODIFICATION : Passer le contrat à getTemplate()
            $template = $this->getTemplate($type, $contract);

            Log::info('📄 Génération PDF avec template', [
                'type' => $type,
                'type_contrat' => $contract instanceof Location ? $contract->type_contrat : null,
                'template' => $template
            ]);

            $pdf = Pdf::loadView($template, $data);
            $pdf->setPaper('A4', 'portrait');

            // Configuration DomPDF pour les images
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

            $fileName = $this->getPdfFileName($contract, $type);
            $pdfPath = 'contrats/' . $fileName;

            // Sauvegarder le PDF
            Storage::disk('public')->put($pdfPath, $pdf->output());

            // Mettre à jour le contrat avec le chemin du PDF
            $contract->update([
                'pdf_path' => $pdfPath,
                'pdf_generated_at' => now()
            ]);

            Log::info('✅ PDF généré avec succès', [
                'contract_id' => $contract->id,
                'type' => $type,
                'type_contrat' => $contract instanceof Location ? $contract->type_contrat : null,
                'pdf_path' => $pdfPath
            ]);

            return $pdfPath;

        } catch (\Exception $e) {
            Log::error('❌ Erreur génération PDF contrat:', [
                'contract_id' => $contract->id,
                'type' => $type,
                'type_contrat' => $contract instanceof Location ? $contract->type_contrat : null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }
    /**
     * Obtenir les données pour le PDF de location
     */
    private function getLocationPdfData(Location $location)
    {
        $bien = $location->bien;
        $bailleur = $bien->proprietaire;
        $locataire = $location->client;

        // Configuration de l'agence
        $agence = [
            'nom' => 'Votre Agence Immobilière',
            'adresse' => '123 Avenue de l\'Immobilier',
            'ville' => 'Dakar',
            'representant' => 'M. Directeur',
            'telephone' => '+221 77 123 45 67',
            'email' => 'contact@agence.sn'
        ];

        return [
            'location' => $location,
            'bien' => $bien,
            'bailleur' => $bailleur,
            'locataire' => $locataire,
            'agence' => $agence,
            'date_creation' => now()->format('d/m/Y'),
            'ville_signature' => 'Dakar',
            'titre_contrat' => 'CONTRAT DE LOCATION',
            'type_contrat' => 'location',

            'bailleur_signature' => $this->getSignatureData($location, 'bailleur'),
            'locataire_signature' => $this->getSignatureData($location, 'locataire'),
            'signature_status' => $location->signature_status ?? 'non_signe',
        ];
    }

    /**
     * Obtenir les données de signature - VERSION CORRIGÉE
     */
    private function getSignatureData($contract, $signatoryType)
    {
        $signatureData = null;
        $signedAt = null;
        $isSigned = false;

        if ($contract instanceof Location) {
            if ($signatoryType === 'bailleur') {
                $signatureData = $contract->bailleur_signature_data ?? null;
                $signedAt = $contract->bailleur_signed_at ?? null;
            } elseif ($signatoryType === 'locataire') {
                $signatureData = $contract->locataire_signature_data ?? null;
                $signedAt = $contract->locataire_signed_at ?? null;
            }
        } elseif ($contract instanceof Vente) {
            if ($signatoryType === 'vendeur') {
                $signatureData = $contract->vendeur_signature_data ?? null;
                $signedAt = $contract->vendeur_signed_at ?? null;
            } elseif ($signatoryType === 'acheteur') {
                $signatureData = $contract->acheteur_signature_data ?? null;
                $signedAt = $contract->acheteur_signed_at ?? null;
            }
        }

        $isSigned = !is_null($signatureData) && !is_null($signedAt);

        return [
            'data' => $isSigned ? $this->processSignatureForPdf($signatureData) : null,
            'signed_at' => $signedAt,
            'is_signed' => $isSigned,
        ];
    }

    /**
     * Traiter la signature pour l'affichage PDF - VERSION AMÉLIORÉE
     */
    private function processSignatureForPdf($signatureData)
    {
        if (!$signatureData) {
            return null;
        }

        try {
            // Si c'est déjà au bon format data:image, le retourner directement
            if (strpos($signatureData, 'data:image/png;base64,') === 0) {
                // Valider que le base64 est correct
                $base64Data = substr($signatureData, strpos($signatureData, ',') + 1);
                if (base64_decode($base64Data, true) !== false) {
                    return $signatureData;
                }
            }

            // Si c'est seulement du base64 sans préfixe
            if (base64_decode($signatureData, true) !== false && strpos($signatureData, 'data:') !== 0) {
                return 'data:image/png;base64,' . $signatureData;
            }

            // Si c'est du SVG
            if (strpos($signatureData, '<svg') !== false) {
                $base64Svg = base64_encode($signatureData);
                return 'data:image/svg+xml;base64,' . $base64Svg;
            }

            // Essayer de décoder et optimiser l'image
            $base64Data = str_replace('data:image/png;base64,', '', $signatureData);
            $imageData = base64_decode($base64Data, true);

            if ($imageData === false) {
                Log::warning('Impossible de décoder la signature base64');
                return null;
            }

            // Optimiser l'image pour DomPDF
            $optimizedImageData = $this->optimizeImageForPdf($imageData);
            if (!$optimizedImageData) {
                Log::warning('Impossible d\'optimiser l\'image de signature');
                return null;
            }

            return 'data:image/png;base64,' . base64_encode($optimizedImageData);

        } catch (\Exception $e) {
            Log::error('Erreur traitement signature pour PDF:', [
                'error' => $e->getMessage(),
                'signature_length' => strlen($signatureData ?? '')
            ]);
            return null;
        }
    }

    /**
     * Optimiser l'image pour DomPDF - VERSION AMÉLIORÉE
     */
    private function optimizeImageForPdf($imageData)
    {
        try {
            $sourceImage = imagecreatefromstring($imageData);
            if (!$sourceImage) {
                Log::warning('Impossible de créer l\'image source');
                return null;
            }

            $originalWidth = imagesx($sourceImage);
            $originalHeight = imagesy($sourceImage);

            // Limites optimisées pour PDF
            $maxWidth = 180;
            $maxHeight = 60;

            // Calculer nouvelles dimensions en gardant le ratio
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            $newWidth = max(1, (int)($originalWidth * $ratio));
            $newHeight = max(1, (int)($originalHeight * $ratio));

            // Créer image redimensionnée avec fond blanc
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            $white = imagecolorallocate($resizedImage, 255, 255, 255);
            imagefill($resizedImage, 0, 0, $white);

            // Redimensionner avec haute qualité
            imagecopyresampled(
                $resizedImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $originalWidth, $originalHeight
            );

            // Convertir en PNG avec compression optimisée pour PDF
            ob_start();
            imagepng($resizedImage, null, 6); // Compression modérée
            $pngData = ob_get_contents();
            ob_end_clean();

            // Nettoyer la mémoire
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);

            Log::info('Image de signature optimisée', [
                'original_size' => "{$originalWidth}x{$originalHeight}",
                'new_size' => "{$newWidth}x{$newHeight}",
                'data_length' => strlen($pngData)
            ]);

            return $pngData;

        } catch (\Exception $e) {
            Log::error('Erreur optimisation image signature:', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Obtenir les données pour le PDF de vente
     */
    private function getVentePdfData(Vente $vente)
    {
        $bien = $vente->bien;
        $vendeur = $bien->proprietaire;
        $acheteur = $vente->acheteur;

        // Configuration de l'agence
        $agence = [
            'nom' => 'Votre Agence Immobilière',
            'adresse' => '123 Avenue de l\'Immobilier',
            'ville' => 'Dakar',
            'representant' => 'M. Directeur',
            'telephone' => '+221 77 123 45 67',
            'email' => 'contact@agence.sn'
        ];

        return [
            'vente' => $vente,
            'bien' => $bien,
            'vendeur' => $vendeur,
            'acheteur' => $acheteur,
            'agence' => $agence,
            'date_creation' => now()->format('d/m/Y'),
            'ville_signature' => 'Dakar',
            'titre_contrat' => 'ACTE DE VENTE IMMOBILIÈRE',
            'type_contrat' => 'vente',

            // Données de signature
            'vendeur_signature' => $this->getSignatureData($vente, 'vendeur'),
            'acheteur_signature' => $this->getSignatureData($vente, 'acheteur'),
            'signature_status' => $vente->signature_status ?? 'non_signe',
        ];
    }

    /**
     * Obtenir le template approprié
     */
    /**
     * Obtenir le template approprié selon le type de contrat
     */
    private function getTemplate($type, $contract = null)
    {
        if ($type === 'vente') {
            return 'contrats.vente';
        }

        // Pour les locations, choisir le template selon le type_contrat
        if ($type === 'location' && $contract instanceof Location) {
            switch ($contract->type_contrat) {
                case 'bail_classique':
                    return 'contrats.bail-classique';
                case 'bail_meuble':
                    return 'contrats.bail-meuble';
                case 'bail_commercial':
                    return 'contrats.bail-commercial';
                default:
                    return 'contrats.bail-classique'; // Template par défaut
            }
        }

        return 'contrats.location'; // Fallback
    }
    /**
     * Générer le nom de fichier PDF
     */
    private function getPdfFileName($contract, $type)
    {
        $date = now()->format('Y-m-d-His');

        if ($type === 'vente') {
            $vendeur = str_replace(' ', '_', $contract->bien->proprietaire->name);
            $acheteur = str_replace(' ', '_', $contract->acheteur->name);
            return "contrat_vente_{$vendeur}_{$acheteur}_{$contract->id}_{$date}.pdf";
        } else {
            $bailleur = str_replace(' ', '_', $contract->bien->proprietaire->name);
            $locataire = str_replace(' ', '_', $contract->client->name);

            // ✅ Inclure le type de contrat dans le nom
            $typeContrat = $contract->type_contrat ?? 'location';
            return "contrat_{$typeContrat}_{$bailleur}_{$locataire}_{$contract->id}_{$date}.pdf";
        }
    }
    /**
     * Télécharger le PDF
     */
    public function downloadPdf($contract, $type)
    {
        // Générer le PDF s'il n'existe pas ou si les signatures ont changé
        if (!$this->hasPdf($contract) || $this->shouldRegeneratePdf($contract)) {
            $this->generatePdf($contract, $type);
        }

        if (!$this->hasPdf($contract)) {
            return false;
        }

        return Storage::disk('public')->download($contract->pdf_path, $this->getPdfFileName($contract, $type));
    }

    /**
     * Prévisualiser le PDF
     */
    public function previewPdf($contract, $type)
    {
        try {
            $data = ($type === 'vente') ? $this->getVentePdfData($contract) : $this->getLocationPdfData($contract);

            // ✅ MODIFICATION : Passer le contrat
            $template = $this->getTemplate($type, $contract);

            $pdf = Pdf::loadView($template, $data);
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

            return response($pdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $this->getPdfFileName($contract, $type) . '"');

        } catch (\Exception $e) {
            Log::error('Erreur prévisualisation PDF:', [
                'error' => $e->getMessage(),
                'type' => $type,
                'type_contrat' => $contract instanceof Location ? $contract->type_contrat : null
            ]);
            return false;
        }
    }

    /**
     * Vérifier si le PDF doit être régénéré
     */
    private function shouldRegeneratePdf($contract)
    {
        if (!$contract->pdf_generated_at) {
            return true;
        }

        // Vérifier si les signatures ont été modifiées après la génération du PDF
        $lastSignatureUpdate = null;

        if ($contract instanceof Location) {
            $lastSignatureUpdate = max(
                $contract->bailleur_signed_at,
                $contract->locataire_signed_at,
                $contract->updated_at
            );
        } elseif ($contract instanceof Vente) {
            $lastSignatureUpdate = max(
                $contract->vendeur_signed_at,
                $contract->acheteur_signed_at,
                $contract->updated_at
            );
        }

        return $lastSignatureUpdate && $lastSignatureUpdate > $contract->pdf_generated_at;
    }

    /**
     * Vérifier si le PDF existe
     */
    public function hasPdf($contract)
    {
        return $contract->pdf_path && Storage::disk('public')->exists($contract->pdf_path);
    }

    /**
     * Régénérer le PDF
     */
    public function regeneratePdf($contract, $type)
    {
        // Supprimer l'ancien PDF
        if ($contract->pdf_path && Storage::disk('public')->exists($contract->pdf_path)) {
            Storage::disk('public')->delete($contract->pdf_path);
        }

        // Réinitialiser les champs
        $contract->update([
            'pdf_path' => null,
            'pdf_generated_at' => null
        ]);

        // Générer le nouveau PDF
        return $this->generatePdf($contract, $type);
    }
}
