<?php

namespace App\Services;

use App\Models\Mandat;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class MandatPdfService
{
    /**
     * Générer et sauvegarder le PDF du mandat
     */
    public function generatePdf(Mandat $mandat)
    {
        try {
            // Utiliser les données avec signatures si disponibles
            $data = $mandat->isFullySigned() || $mandat->signature_status !== 'non_signe'
                ? $mandat->getPdfDataWithSignatures()
                : $mandat->getPdfData();

            $template = $mandat->type_mandat === 'vente' ? 'mandats.vente' : 'mandats.gerance';

            $pdf = Pdf::loadView($template, $data);
            $pdf->setPaper('A4', 'portrait');

            $fileName = $mandat->getPdfFileName();
            $pdfPath = 'mandats/' . $fileName;

            // Sauvegarder le PDF
            Storage::disk('public')->put($pdfPath, $pdf->output());

            // Mettre à jour le mandat avec le chemin du PDF
            $mandat->update([
                'pdf_path' => $pdfPath,
                'pdf_generated_at' => now()
            ]);

            return $pdfPath;

        } catch (\Exception $e) {
            \Log::error('Erreur génération PDF mandat:', [
                'mandat_id' => $mandat->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Télécharger le PDF du mandat
     */
    public function downloadPdf(Mandat $mandat)
    {
        // Si le PDF n'existe pas, le générer
        if (!$mandat->hasPdf()) {
            $this->generatePdf($mandat);
        }

        if (!$mandat->hasPdf()) {
            return false;
        }

        return Storage::disk('public')->download($mandat->pdf_path, $mandat->getPdfFileName());
    }

// Dans MandatPdfService::previewMandatPdf()
    public function previewMandatPdf(Mandat $mandat)
    {
        try {
            $data = $mandat->getPdfDataWithSignatures();
            $template = $mandat->type_mandat === 'vente' ? 'mandats.vente' : 'mandats.gerance';

            // CORRIGÉ : Sans les options qui cassent le PDF
            $pdf = Pdf::loadView($template, $data);
            $pdf->setPaper('A4', 'portrait');

            // PAS d'options supplémentaires - elles cassent le PDF

            return response($pdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $mandat->getPdfFileName() . '"');

        } catch (\Exception $e) {
            \Log::error('Erreur PDF:', ['error' => $e->getMessage()]);
            return false;
        }
    }    /**
     * Régénérer le PDF du mandat avec les signatures actuelles
     */
    public function regeneratePdf(Mandat $mandat)
    {
        // Supprimer l'ancien PDF s'il existe
        if ($mandat->pdf_path && Storage::disk('public')->exists($mandat->pdf_path)) {
            Storage::disk('public')->delete($mandat->pdf_path);
        }

        // Supprimer aussi le PDF signé s'il existe
        if ($mandat->signed_pdf_path && Storage::disk('public')->exists($mandat->signed_pdf_path)) {
            Storage::disk('public')->delete($mandat->signed_pdf_path);
        }

        // Réinitialiser les champs PDF
        $mandat->update([
            'pdf_path' => null,
            'pdf_generated_at' => null,
            'signed_pdf_path' => null,
            'final_pdf_generated_at' => null
        ]);

        // Générer le nouveau PDF
        return $this->generatePdf($mandat);
    }

    /**
     * Générer le PDF final avec signatures
     */
    public function generateSignedPdf(Mandat $mandat)
    {
        try {
            $data = $mandat->getPdfDataWithSignatures();
            $template = $mandat->type_mandat === 'vente' ? 'mandats.vente' : 'mandats.gerance';

            $pdf = Pdf::loadView($template, $data);
            $pdf->setPaper('A4', 'portrait');

            $fileName = str_replace('.pdf', '_signe.pdf', $mandat->getPdfFileName());
            $pdfPath = 'mandats/signed/' . $fileName;

            // Créer le dossier s'il n'existe pas
            Storage::disk('public')->makeDirectory('mandats/signed');

            // Sauvegarder le PDF signé
            Storage::disk('public')->put($pdfPath, $pdf->output());

            // Mettre à jour le mandat
            $mandat->update([
                'signed_pdf_path' => $pdfPath,
                'final_pdf_generated_at' => now()
            ]);

            return $pdfPath;

        } catch (\Exception $e) {
            \Log::error('Erreur génération PDF signé:', [
                'mandat_id' => $mandat->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Télécharger le PDF signé
     */
    public function downloadSignedPdf(Mandat $mandat)
    {
        // Générer le PDF signé s'il n'existe pas
        if (!$mandat->hasSignedPdf()) {
            $this->generateSignedPdf($mandat);
        }

        if (!$mandat->hasSignedPdf()) {
            return false;
        }

        $fileName = str_replace('.pdf', '_signe.pdf', $mandat->getPdfFileName());
        return Storage::disk('public')->download($mandat->signed_pdf_path, $fileName);
    }

    /**
     * Prévisualiser le PDF signé
     */
    public function previewSignedPdf(Mandat $mandat)
    {
        try {
            $data = $mandat->getPdfDataWithSignatures();
            $template = $mandat->type_mandat === 'vente' ? 'mandats.vente' : 'mandats.gerance';

            $pdf = Pdf::loadView($template, $data);
            $pdf->setPaper('A4', 'portrait');

            $fileName = str_replace('.pdf', '_signe.pdf', $mandat->getPdfFileName());

            return response($pdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"')
                ->header('Cache-Control', 'private, max-age=0, must-revalidate')
                ->header('Pragma', 'public');

        } catch (\Exception $e) {
            \Log::error('Erreur prévisualisation PDF signé:', [
                'mandat_id' => $mandat->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
