<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Paiement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        $paiementDuJour = Paiement::whereDate('created_at', Carbon::today())->with('commande')->get();

        return $this->jsonResponse(true, "Liste des paiements du jour", $paiementDuJour);
    }

    //---------------------------------------------------------------------Api de sauvegarde des paiements---------------------------------------------------
    public function store($commandeId)
    {
        try {
            $commande = Commande::findOrFail($commandeId);

            if ($commande){
                $paiement = new Paiement();
                $paiement->commande_id = $commande->id;
                $paiement->montant = $commande->montant;
                $paiement->datePaiement = now();
                $paiement->save();

                return $this->jsonResponse(true, 'Paiement enregistré avec succès !', $paiement, 201);

            }
        }catch (ModelNotFoundException $e) {
            return $this->jsonResponse(false, 'Commande non trouvée.', [], 404);
        }catch (\Exception $exception){
            return $this->jsonResponse(false, 'Erreur de chargement du paiement dans la bd !', $exception->getMessage(), 500);
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
