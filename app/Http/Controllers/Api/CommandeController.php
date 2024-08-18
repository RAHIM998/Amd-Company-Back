<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandeRequest;
use App\Mail\CommandeRecue;
use App\Models\Commande;
use App\Models\Produit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CommandeController extends Controller
{
    //------------------------------------------------------------------------Api d'affichage des commandes-------------------------------------------------
    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            // Récupérer toutes les commandes avec les produits associés et les données de la table pivot
            $commandes = Commande::with('produits')->get();
            return $this->jsonResponse(true, "Listes des commandes", $commandes);
        } else {
            // Récupérer les commandes de l'utilisateur connecté avec les produits associés et les données de la table pivot
            $commandes = Commande::with('produits')->where('user_id', $user->id)->get();

            if ($commandes->isEmpty()) {
                return $this->jsonResponse(false, "Vous n'avez passé aucune commande.");
            } else {
                return $this->jsonResponse(true, "Vos commandes", $commandes);
            }
        }
    }



    //----------------------------------------------------------------------Api de sauvegarde des commandes--------------------------------------------------
    public function store(CommandeRequest $request)
    {
        try {
            $validated = $request->validated();
            $numeroCommande = 'CMD' . date('ymd') . rand(1000, 9999);

            DB::beginTransaction();

            $totalCommande = 0;
            $TabCommande = [];

            foreach ($validated['produits'] as $produit) {
                $productSearch = Produit::findOrFail($produit['id']);

                if ($productSearch->stock < $produit['quantite']) {
                    return $this->jsonResponse(false, "Le stock de {$productSearch->libelle} est insuffisant !", [],  400);
                }else {
                    $prixUnitaire  = $productSearch->prix;
                    $totalCommande += $prixUnitaire * $produit['quantite'];
                    $TabCommande[$produit['id']] = [
                        'quantite' => $produit['quantite'],
                    ];
                }
            }

            $Commande = Commande::create([
                'user_id' => Auth::id(),
                'numeroCommande' => $numeroCommande,
                'dateCommande' => now(),
                'montant' => $totalCommande,
                'adresseLivraison' => $validated['adresseLivraison'],
                'status' => 'confirmation en attente',
            ]);

            foreach ($TabCommande as $CommandeId => $prod) {
                $commandeToSave = Produit::findOrFail($CommandeId);
                $commandeToSave->decrement('stock', $prod['quantite']);
                $Commande->produits()->attach($commandeToSave->id, $prod);
            }



            Mail::to(Auth::user()->email)->send(new CommandeRecue($Commande));

            DB::commit();
            return $this->jsonResponse(true, 'Commande créée avec succès !', $Commande, 201);
        }catch (\Exception $exception){
            DB::rollBack();
            return $this->jsonResponse(false, 'Erreur !', $exception->getMessage(), 500);
        }
    }

    //----------------------------------------------------------------------Api d'affichage des détails d'une commande----------------------------------------
    public function show(string $id)
    {

    }

    //-----------------------------------------------------------------------Api de modification du statut d'une commande---------------------------------------
    public function update(Request $request, string $id)
    {
        //
    }

    //-------------------------------------------------------------------------Api d'annulation de commande-----------------------------------------------------------
    public function destroy(string $id)
    {
        //
    }
}
