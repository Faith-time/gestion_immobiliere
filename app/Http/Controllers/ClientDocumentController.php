<?php

namespace App\Http\Controllers;

use App\Models\ClientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ClientDocumentController extends Controller
{
    /**
     * Enregistrer un document client
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

            // Créer l'enregistrement avec valeurs automatiques
            $document = ClientDocument::create([
                'client_id' => Auth::id(),
                'type_document' => $request->type_document,
                'fichier_path' => $filePath,
                'statut' => 'en_attente'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploadé avec succès',
                'document' => $document
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister les documents d'un client
     */
    public function index()
    {
        $documents = ClientDocument::where('client_id', Auth::id())->latest()->get();

        return response()->json($documents);
    }

    /**
     * Afficher un document
     */
    public function show($id)
    {
        $document = ClientDocument::where('client_id', Auth::id())->findOrFail($id);

        return response()->json($document);
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

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valider un document (Admin)
     */
    public function valider($id)
    {
        $document = ClientDocument::findOrFail($id);
        $document->update(['statut' => 'valide']);

        return back()->with('success', 'Document validé.');
    }

    /**
     * Refuser un document (Admin)
     */
    public function refuser($id)
    {
        $document = ClientDocument::findOrFail($id);
        $document->update(['statut' => 'refuse']);

        return back()->with('success', 'Document refusé.');
    }

    /**
     * Télécharger un document
     */
    public function download($id)
    {
        $document = ClientDocument::findOrFail($id);

        if (!Storage::disk('public')->exists($document->fichier_path)) {
            return back()->with('error', 'Fichier introuvable.');
        }

        return Storage::disk('public')->download($document->fichier_path);
    }
}
