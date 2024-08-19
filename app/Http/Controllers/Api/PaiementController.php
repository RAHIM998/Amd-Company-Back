<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Paiement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Mockery\Exception;

class PaiementController extends Controller
{
    //--------------------------------------------------------------------Api de liste des paiements-----------------------------------------------------
    public function index()
    {
        try {
            $paiement = Paiement::with('commande')->get();
            return $this->jsonResponse(true, 'Liste des paiements', $paiement);
        }catch (Exception $exception){
            return $this->jsonResponse(false, 'error', $exception, 500);
        }
    }

    //----------------------------------------------------------------------Payements du jours-------------------------------------------------------------------
    public function paiementjournalier()
    {
        $paiementDuJour = Paiement::whereDate('created_at', Carbon::today())
            ->where('status', true)
            ->with('commande')
            ->get();

        $montantTotal = $paiementDuJour->sum('montant');

        return $this->jsonResponse(true, "Liste des paiements du jour", [
            'CAJournalier' => $montantTotal,
            'PaiementDuJour' => $paiementDuJour,
        ]);
    }


    //----------------------------------------------------------------------Payements du jours-------------------------------------------------------------------
    public function paiementEnAttentes()
    {
        $paiementEnAttente= Paiement::where('status', false)->with('commande')->get();

        $montantTotal = $paiementEnAttente->sum('montant');

        return $this->jsonResponse(true, "Liste des paiements du jour", [
            'MontantPaiementEnAttente:' => $montantTotal,
            'PaiementDuJour' => $paiementEnAttente,
        ]);
    }


    //---------------------------------------------------------------------Api de sauvegarde des paiements---------------------------------------------------
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'commande_id' => 'required|exists:commandes,id',
                'method' => 'required|in:delivery,orange_money',
            ]);

            $commande = Commande::findOrFail($validated['commande_id']);

            $paiement = Paiement::create([
                'commande_id' => $commande->id,
                'montant' => $commande->montant,
                'datePaiement' => $validated['method'] === 'orange_money' ? now() : null,
                'method' => $validated['method'],
                'status' => $validated['method'] === 'orange_money' ? true : false,
            ]);

            return $this->jsonResponse(true, 'Paiement enregistré avec succès !', $paiement, 201);
        } catch (\Exception $exception) {
            return $this->jsonResponse(false, 'Erreur !', $exception->getMessage(), 500);
        }
    }

    //--------------------------------------------------------------------Api de visionage des détails de commandes-----------------------------------------------
    public function show(string $id)
    {
        try {
            $paiement = Paiement::with('commande')->findOrFail($id);

            return $this->jsonResponse(true, 'Details du paiement', $paiement);
        }catch (Exception $exception){
            return $this->jsonResponse(false, 'Erreur de chargement du paiement !', $exception, 500);
        }
    }

    //----------------------------------------------------------------------Api de modification des paiements------------------------------------------------
    public function update(Request $request, string $id)
    {
        //
    }

    //----------------------------------------------------------------------Api de suppression des paiements----------------------------------------------------
    public function destroy(string $id)
    {
        //
    }
}
