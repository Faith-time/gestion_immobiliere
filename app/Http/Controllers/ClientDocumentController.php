<?php

namespace App\Http\Controllers;

use App\Models\ClientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ClientDocumentController extends Controller
{
    /**
     * Liste des documents du client connecté
     */
    public function index()
    {
        $documents = ClientDocument::where('client_id', Auth::id())
            ->latest()
            ->get();

        return Inertia::render('ClientDocuments/Index', [
            'documents' => $documents
        ]);
    }

    /**
     * Enregistrer un nouveau document
     */
    public function store(Request $request)
    {
        $request->validate([
            'type_document' => 'required|string|max:255',
            'fichier' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        try {
            // Upload du fichier
            $file = $request->file('fichier');
            $fileName = time() . '_' . Auth::id() . '_' . $request->type_document . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('documents/clients', $fileName, 'public');

            // Créer l'enregistrement
            $document = ClientDocument::create([
                'client_id' => Auth::id(),
                'type_document' => $request->type_document,
                'fichier_path' => $filePath,
                'statut' => 'en_attente'
            ]);

            return back()->with('success', 'Document uploadé avec succès');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de l\'upload: ' . $e->getMessage());
        }
    }

    /**
     * Afficher un document
     */
    public function show($id)
    {
        $document = ClientDocument::where('client_id', Auth::id())->findOrFail($id);

        return Inertia::render('ClientDocuments/Show', [
            'document' => $document
        ]);
    }

    /**
     * Modifier un document existant
     */
    public function update(Request $request, $id)
    {
        $document = ClientDocument::where('client_id', Auth::id())->findOrFail($id);

        $request->validate([
            'type_document' => 'sometimes|string|max:255',
            'fichier' => 'sometimes|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            // Si un nouveau fichier est uploadé
            if ($request->hasFile('fichier')) {
                // Supprimer l'ancien fichier
                Storage::disk('public')->delete($document->fichier_path);

                // Upload du nouveau fichier
                $file = $request->file('fichier');
                $fileName = time() . '_' . Auth::id() . '_' . $request->type_document . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('documents/clients', $fileName, 'public');

                $document->fichier_path = $filePath;
                $document->statut = 'en_attente'; // Remettre en attente après modification
            }

            // Mettre à jour le type si fourni
            if ($request->filled('type_document')) {
                $document->type_document = $request->type_document;
            }

            $document->save();

            return back()->with('success', 'Document modifié avec succès');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la modification: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un document
     */
    public function destroy($id)
    {
        try {
            $document = ClientDocument::where('client_id', Auth::id())->findOrFail($id);

            // Supprimer le fichier
            Storage::disk('public')->delete($document->fichier_path);

            // Supprimer l'enregistrement
            $document->delete();

            return back()->with('success', 'Document supprimé avec succès');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Valider un document (Admin uniquement)
     */
    public function valider($id)
    {
        $document = ClientDocument::findOrFail($id);
        $document->update(['statut' => 'valide']);

        return back()->with('success', 'Document validé avec succès');
    }

    /**
     * Refuser un document (Admin uniquement)
     */
    public function refuser(Request $request, $id)
    {
        $request->validate([
            'motif' => 'required|string|max:500'
        ]);

        $document = ClientDocument::findOrFail($id);
        $document->update([
            'statut' => 'refuse',
            'motif_refus' => $request->motif
        ]);

        return back()->with('success', 'Document refusé');
    }

    /**
     * Télécharger un document
     */
    public function download($id)
    {
        $document = ClientDocument::findOrFail($id);

        // Vérifier les permissions
        if (Auth::id() !== $document->client_id && !Auth::user()->hasRole('admin')) {
            abort(403, 'Action non autorisée');
        }

        if (!Storage::disk('public')->exists($document->fichier_path)) {
            return back()->with('error', 'Fichier introuvable');
        }

        return Storage::disk('public')->download($document->fichier_path);
    }
}
