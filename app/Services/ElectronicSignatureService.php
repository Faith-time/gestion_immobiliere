<?php

namespace App\Services;

use App\Models\Mandat;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ElectronicSignatureService
{
    protected $mandatPdfService;

    public function __construct(MandatPdfService $mandatPdfService)
    {
        $this->mandatPdfService = $mandatPdfService;
    }

    /**
     * Signer le mandat par le propriétaire
     */
    public function signByProprietaire(Mandat $mandat, $signatureData)
    {
        try {
            // Valider les données de signature
            if (!$this->validateSignatureData($signatureData)) {
                throw new \Exception('Données de signature invalides');
            }

            // Signer le mandat
            $mandat->signByProprietaire($signatureData, request()->ip());

            // CORRECTION : Recharger le mandat pour avoir les dernières données
            $mandat->refresh();

            // Générer le PDF avec la signature
            $this->generateSignedPdf($mandat);

            // Log de l'action
            Log::info('Mandat signé par propriétaire', [
                'mandat_id' => $mandat->id,
                'bien_id' => $mandat->bien_id,
                'proprietaire_id' => $mandat->bien->proprietaire_id,
                'signed_at' => $mandat->proprietaire_signed_at,
                'ip' => $mandat->proprietaire_signature_ip,
                'signature_status' => $mandat->signature_status
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur signature propriétaire', [
                'mandat_id' => $mandat->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Signer le mandat par l'agence
     */
    public function signByAgence(Mandat $mandat, $signatureData)
    {
        try {
            // Valider les données de signature
            if (!$this->validateSignatureData($signatureData)) {
                throw new \Exception('Données de signature invalides');
            }

            // Signer le mandat
            $mandat->signByAgence($signatureData, request()->ip());

            // CORRECTION : Recharger le mandat pour avoir les dernières données
            $mandat->refresh();

            // Générer le PDF avec la signature
            $this->generateSignedPdf($mandat);

            // Log de l'action
            Log::info('Mandat signé par agence', [
                'mandat_id' => $mandat->id,
                'bien_id' => $mandat->bien_id,
                'signed_at' => $mandat->agence_signed_at,
                'ip' => $mandat->agence_signature_ip,
                'signature_status' => $mandat->signature_status
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur signature agence', [
                'mandat_id' => $mandat->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Générer le PDF avec les signatures
     */
    public function generateSignedPdf(Mandat $mandat)
    {
        try {
            // Utiliser la nouvelle logique pour les signatures
            $data = $mandat->getPdfData();
            $signatures = $this->prepareSignatureForPdf($mandat);
            $data = array_merge($data, $signatures);

            $template = $mandat->type_mandat === 'vente' ? 'mandats.vente' : 'mandats.gerance';

            $pdf = Pdf::loadView($template, $data);
            $pdf->setPaper('A4', 'portrait');

            // Nom de fichier unique pour la version signée
            $fileName = $this->getSignedPdfFileName($mandat);
            $signedPdfPath = 'mandats/signed/' . $fileName;

            // Créer le dossier s'il n'existe pas
            Storage::disk('public')->makeDirectory('mandats/signed');

            // Sauvegarder le PDF signé
            Storage::disk('public')->put($signedPdfPath, $pdf->output());

            // Mettre à jour le mandat
            $mandat->update([
                'signed_pdf_path' => $signedPdfPath,
                'final_pdf_generated_at' => now()
            ]);

            // Nettoyer les fichiers temporaires
            $this->cleanupTempSignatureFiles($mandat);

            return $signedPdfPath;

        } catch (\Exception $e) {
            Log::error('Erreur génération PDF signé:', [
                'mandat_id' => $mandat->id,
                'error' => $e->getMessage()
            ]);

            $this->cleanupTempSignatureFiles($mandat);
            throw $e;
        }
    }

    /**
     * Valider les données de signature
     */
    private function validateSignatureData($signatureData)
    {
        if (empty($signatureData)) {
            return false;
        }

        // Vérifier si c'est du SVG valide ou du base64
        if (strpos($signatureData, '<svg') !== false) {
            // Validation SVG basique
            return strpos($signatureData, '</svg>') !== false;
        }

        // Validation base64
        if (strpos($signatureData, 'data:image/') === 0) {
            return true;
        }

        // Validation base64 pur
        return base64_decode($signatureData, true) !== false;
    }

    /**
     * Obtenir le nom du fichier PDF signé
     */
    private function getSignedPdfFileName(Mandat $mandat)
    {
        $bien = $mandat->bien;
        $proprietaire = $bien->proprietaire;

        $type = $mandat->type_mandat === 'vente' ? 'vente' : 'gerance';
        $date = now()->format('Y-m-d-His');
        $proprietaireName = str_replace(' ', '_', $proprietaire->name);
        $signatureStatus = $mandat->signature_status;

        return "mandat_{$type}_{$proprietaireName}_{$bien->id}_{$signatureStatus}_{$date}.pdf";
    }

    /**
     * Télécharger le PDF signé
     */
    public function downloadSignedPdf(Mandat $mandat)
    {
        $pdfPath = $mandat->getFinalPdfPath();

        if (!$pdfPath || !Storage::disk('public')->exists($pdfPath)) {
            return false;
        }

        return Storage::disk('public')->download($pdfPath, $mandat->getPdfFileName());
    }

    /**
     * Obtenir les statistiques de signature - MÉTHODE MANQUANTE
     */
    public function getSignatureStats(Mandat $mandat)
    {
        // Recharger le mandat pour avoir les dernières données
        $mandat->refresh();

        return [
            'proprietaire_signed' => $mandat->isSignedByProprietaire(),
            'agence_signed' => $mandat->isSignedByAgence(),
            'fully_signed' => $mandat->isFullySigned(),
            'signature_status' => $mandat->signature_status,
            'proprietaire_signed_at' => $mandat->proprietaire_signed_at,
            'agence_signed_at' => $mandat->agence_signed_at,
            'can_sign_proprietaire' => $mandat->canBeSignedByProprietaire(),
            'can_sign_agence' => $mandat->canBeSignedByAgence(),
        ];
    }

    /**
     * Annuler une signature (CORRIGÉ)
     */
    public function cancelSignature(Mandat $mandat, $signatoryType)
    {
        try {
            if ($signatoryType === 'proprietaire') {
                $mandat->update([
                    'proprietaire_signature_data' => null,
                    'proprietaire_signed_at' => null,
                    'proprietaire_signature_ip' => null,
                ]);
            } elseif ($signatoryType === 'agence') {
                $mandat->update([
                    'agence_signature_data' => null,
                    'agence_signed_at' => null,
                    'agence_signature_ip' => null,
                ]);
            }

            // Recalculer le statut avec la méthode publique
            $mandat->updateSignatureStatus();

            // Supprimer le PDF signé si plus de signatures
            if ($mandat->signature_status === 'non_signe' && $mandat->signed_pdf_path) {
                if (Storage::disk('public')->exists($mandat->signed_pdf_path)) {
                    Storage::disk('public')->delete($mandat->signed_pdf_path);
                }
                $mandat->update([
                    'signed_pdf_path' => null,
                    'final_pdf_generated_at' => null
                ]);
            } else if ($mandat->signature_status !== 'non_signe') {
                // Régénérer le PDF avec les signatures restantes
                $this->generateSignedPdf($mandat);
            }

            Log::info('Signature annulée', [
                'mandat_id' => $mandat->id,
                'signatory_type' => $signatoryType,
                'new_status' => $mandat->signature_status
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur annulation signature:', [
                'mandat_id' => $mandat->id,
                'signatory_type' => $signatoryType,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Préparer les données de signature pour le PDF
     */
    // Modifiez cette méthode dans votre ElectronicSignatureService

    /**
     * Préparer les données de signature pour le PDF - VERSION FINALE
     */
    public function prepareSignatureForPdf(Mandat $mandat)
    {
        $signatures = [];

        // Traiter la signature propriétaire
        if ($mandat->proprietaire_signature_data && $mandat->isSignedByProprietaire()) {
            // DEBUG : Vérifier les données brutes
            \Log::info('Données signature propriétaire:', [
                'mandat_id' => $mandat->id,
                'data_length' => strlen($mandat->proprietaire_signature_data),
                'data_start' => substr($mandat->proprietaire_signature_data, 0, 50),
                'signed_at' => $mandat->proprietaire_signed_at
            ]);

            $proprietaireBase64 = $this->prepareBase64ForPdf($mandat->proprietaire_signature_data);

            if ($proprietaireBase64) {
                $signatures['proprietaire_signature'] = [
                    'data' => $proprietaireBase64,
                    'signed_at' => $mandat->proprietaire_signed_at,
                    'is_signed' => true,
                ];

                \Log::info('Signature propriétaire préparée avec succès');
            } else {
                \Log::error('Échec préparation signature propriétaire');
                $signatures['proprietaire_signature'] = [
                    'data' => null,
                    'signed_at' => null,
                    'is_signed' => false,
                ];
            }
        } else {
            $signatures['proprietaire_signature'] = [
                'data' => null,
                'signed_at' => null,
                'is_signed' => false,
            ];
        }

        // Traiter la signature agence
        if ($mandat->agence_signature_data && $mandat->isSignedByAgence()) {
            \Log::info('Données signature agence:', [
                'mandat_id' => $mandat->id,
                'data_length' => strlen($mandat->agence_signature_data),
                'data_start' => substr($mandat->agence_signature_data, 0, 50),
                'signed_at' => $mandat->agence_signed_at
            ]);

            $agenceBase64 = $this->prepareBase64ForPdf($mandat->agence_signature_data);

            if ($agenceBase64) {
                $signatures['agence_signature'] = [
                    'data' => $agenceBase64,
                    'signed_at' => $mandat->agence_signed_at,
                    'is_signed' => true,
                ];

                \Log::info('Signature agence préparée avec succès');
            } else {
                \Log::error('Échec préparation signature agence');
                $signatures['agence_signature'] = [
                    'data' => null,
                    'signed_at' => null,
                    'is_signed' => false,
                ];
            }
        } else {
            $signatures['agence_signature'] = [
                'data' => null,
                'signed_at' => null,
                'is_signed' => false,
            ];
        }

        return $signatures;
    }
    /**
     * Préparer une chaîne base64 optimisée pour DomPDF
     */
    private function prepareBase64ForPdf($signatureData)
    {
        try {
            \Log::info('=== PREPARATION BASE64 POUR DOMPDF ===', [
                'input_length' => strlen($signatureData),
            ]);

            // Extraire les données base64
            if (strpos($signatureData, 'data:image/') === 0) {
                $base64Data = substr($signatureData, strpos($signatureData, ',') + 1);
            } else {
                $base64Data = $signatureData;
            }

            // Nettoyer les données
            $base64Data = trim(str_replace([' ', '\n', '\r'], '', $base64Data));

            // Décoder
            $imageData = base64_decode($base64Data, true);
            if ($imageData === false) {
                \Log::error('Impossible de décoder le base64');
                return null;
            }

            // SOLUTION PRINCIPALE : Redimensionner et optimiser pour DomPDF
            $optimizedImageData = $this->optimizeImageForDomPdf($imageData);

            if (!$optimizedImageData) {
                \Log::error('Impossible d\'optimiser l\'image');
                return null;
            }

            // Encoder en base64 propre
            $optimizedBase64 = base64_encode($optimizedImageData);

            // Format final pour DomPDF
            $result = 'data:image/png;base64,' . $optimizedBase64;

            \Log::info('Image optimisée pour DomPDF', [
                'original_size' => strlen($imageData),
                'optimized_size' => strlen($optimizedImageData),
                'final_length' => strlen($result)
            ]);

            return $result;

        } catch (\Exception $e) {
            \Log::error('Erreur préparation image DomPDF:', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    private function optimizeImageForDomPdf($imageData)
    {
        try {
            // Créer l'image depuis les données
            $sourceImage = imagecreatefromstring($imageData);
            if (!$sourceImage) {
                \Log::error('Impossible de créer l\'image depuis les données');
                return null;
            }

            $originalWidth = imagesx($sourceImage);
            $originalHeight = imagesy($sourceImage);

            // Limites strictes pour DomPDF (plus petites)
            $maxWidth = 250;  // Réduit de 400 à 250
            $maxHeight = 80;  // Réduit de 160 à 80

            // Calculer les nouvelles dimensions
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            $newWidth = max(1, (int)($originalWidth * $ratio));
            $newHeight = max(1, (int)($originalHeight * $ratio));

            \Log::info('Redimensionnement image', [
                'original' => $originalWidth . 'x' . $originalHeight,
                'new' => $newWidth . 'x' . $newHeight,
                'ratio' => $ratio
            ]);

            // Créer l'image redimensionnée
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Fond blanc (DomPDF préfère les fonds opaques)
            $white = imagecolorallocate($resizedImage, 255, 255, 255);
            imagefill($resizedImage, 0, 0, $white);

            // Copier l'image redimensionnée
            imagecopyresampled(
                $resizedImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $originalWidth, $originalHeight
            );

            // Convertir en PNG avec compression élevée
            ob_start();
            imagepng($resizedImage, null, 9); // Compression maximale
            $pngData = ob_get_contents();
            ob_end_clean();

            // Nettoyer la mémoire
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);

            \Log::info('Image PNG créée', [
                'size' => strlen($pngData)
            ]);

            return $pngData;

        } catch (\Exception $e) {
            \Log::error('Erreur optimisation image:', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }


    /**
     * Redimensionner une image pour optimiser l'affichage PDF
     */
    private function resizeImageForPdf($imageData, $maxWidth = 400, $maxHeight = 160)
    {
        try {
            // Créer une image depuis les données binaires
            $sourceImage = imagecreatefromstring($imageData);

            if (!$sourceImage) {
                return null;
            }

            // Obtenir les dimensions originales
            $originalWidth = imagesx($sourceImage);
            $originalHeight = imagesy($sourceImage);

            // Calculer les nouvelles dimensions en gardant les proportions
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            $newWidth = (int)($originalWidth * $ratio);
            $newHeight = (int)($originalHeight * $ratio);

            // Créer la nouvelle image
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Gérer la transparence pour PNG
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefill($resizedImage, 0, 0, $transparent);

            // Redimensionner
            imagecopyresampled(
                $resizedImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight, $originalWidth, $originalHeight
            );

            // Convertir en PNG
            ob_start();
            imagepng($resizedImage, null, 6); // Compression niveau 6
            $resizedData = ob_get_contents();
            ob_end_clean();

            // Nettoyer
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);

            return $resizedData;

        } catch (\Exception $e) {
            Log::error('Erreur redimensionnement image:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Sauvegarder une signature comme fichier temporaire
     */
    private function saveSignatureAsTempFile($signatureData, $filename)
    {
        try {
            // Nettoyer les données base64
            if (strpos($signatureData, 'data:image/') === 0) {
                // Extraire juste la partie base64
                $base64Data = substr($signatureData, strpos($signatureData, ',') + 1);
            } else {
                $base64Data = $signatureData;
            }

            // Décoder
            $imageData = base64_decode($base64Data);

            if ($imageData === false) {
                Log::error('Impossible de décoder la signature base64', [
                    'filename' => $filename
                ]);
                return null;
            }

            // Créer le dossier s'il n'existe pas
            $tempDir = 'temp/signatures';
            Storage::disk('public')->makeDirectory($tempDir);

            // Sauvegarder le fichier
            $filePath = $tempDir . '/' . $filename . '.png';
            $saved = Storage::disk('public')->put($filePath, $imageData);

            if ($saved) {
                Log::info('Signature sauvegardée avec succès', [
                    'filename' => $filename,
                    'path' => $filePath,
                    'size' => strlen($imageData)
                ]);
                return $filePath;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde signature:', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Nettoyer les fichiers temporaires de signatures
     */
    public function cleanupTempSignatureFiles(Mandat $mandat)
    {
        $tempDir = 'temp/signatures';
        $patterns = [
            'proprietaire_' . $mandat->id . '.png',
            'agence_' . $mandat->id . '.png'
        ];

        foreach ($patterns as $pattern) {
            $filePath = $tempDir . '/' . $pattern;
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        }
    }

    /**
     * Prévisualiser le PDF signé - VERSION CORRIGÉE
     */
    public function previewSignedPdf(Mandat $mandat)
    {
        try {
            $mandat->refresh();

            // Obtenir les données de base et les signatures optimisées
            $data = $mandat->getPdfData();
            $signatures = $this->prepareSignatureForPdf($mandat);
            $data = array_merge($data, $signatures);

            // Ajouter les autres données nécessaires
            $data['signature_status'] = $mandat->signature_status;
            $data['is_fully_signed'] = $mandat->isFullySigned();

            $template = $mandat->type_mandat === 'vente' ? 'mandats.vente' : 'mandats.gerance';

            $pdf = Pdf::loadView($template, $data);
            $pdf->setPaper('A4', 'portrait');

            return response($pdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $mandat->getPdfFileName() . '"');

        } catch (\Exception $e) {
            Log::error('Erreur prévisualisation PDF:', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
