<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandeRequest;
use App\Mail\CommandeAcceptee;
use App\Mail\CommandeAnnulee;
use App\Mail\CommandeLivree;
use App\Mail\CommandeRecue;
use App\Models\Commande;
use App\Models\Produit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery\Exception;

class CommandeController extends Controller
{
    //------------------------------------------------------------Méthode de vérification du statut------------------------------------------------------
    public static $statusTransitions = [
        'admin' => [
            'pending' => ['accepted', 'rejected'],
            'accepted' => ['delivered'],
            'rejected' => [],
            'delivered' => []
        ],
        'user' => [
            'pending' => ['cancelled'],
        ],
    ];

    // Vérifie si la transition de statut est autorisée
    protected function canTransitionTo(Commande $commande, $newStatus, $user)
    {
        $currentStatus = $commande->status;
        $allowedTransitions = self::$statusTransitions[$user->role] ?? [];

        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
    }

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
                'status' => 'pending',
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
        try{
            $commande = Commande::with('produits')->findOrFail($id);

            return $this->jsonResponse(true, "Commande", $commande);

        }catch (Exception $exception){
            return $this->jsonResponse(true, 'Erreur !', $exception->getMessage(), 500);
        }
    }

    //-----------------------------------------------------------------------Api de modification du statut d'une commande---------------------------------------
    public function update(Request $request, string $id)
    {
        try {
            $user = Auth::user();
            $statut = $request->input("status");
            $commande = Commande::findOrFail($id);

            // Vérifier si la transition est valide
            if ($this->canTransitionTo($commande, $statut, $user)) {
                $commande->status = $statut;
                $commande->save();

                // Effectuer les actions en fonction du statut
                switch ($statut) {
                    case 'accepted':
                        Mail::to($commande->user->email)->send(new CommandeAcceptee($commande));
                        return $this->jsonResponse(true, "Statut modifié avec succès !", $commande);
                        break;

                    case 'rejected':
                        Mail::to($commande->user->email)->send(new CommandeAnnulee($commande));
                        $this->destroy($commande->id);
                        return $this->jsonResponse(true, "Commande annulée avec succès !");
                        break;

                    case 'delivered':
                        Mail::to($commande->user->email)->send(new CommandeLivree($commande));
                        $paie = new PaiementController();
                        $paie->store($commande->id);
                        return $this->jsonResponse(true, "Commande livrée avec succès !");
                        break;
                }

            } else {
                return $this->jsonResponse(false, "Changement de statut non autorisé.", [], 403);
            }
        }catch (\Exception $exception){
            return $this->jsonResponse(false, 'Erreur !', $exception->getMessage(), 500);
        }
    }

    //-------------------------------------------------------------------------Api d'annulation de commande-----------------------------------------------------------
    public function destroy(string $id)
    {
        try {
            $commande = Commande::findOrFail($id);
            $commande->delete();

            return $this->jsonResponse(true, "Commande annulée et supprimée avec succès !", $commande);
        } catch (\Exception $exception) {
            return $this->jsonResponse(false, 'Erreur !', $exception->getMessage(), 500);
        }
    }




    //---------------------------------------------------------Commandes en cours de la journée---------------------------------------------------------------------------

    public function commandeEnCoursDuJour()
    {
        $commandeEnCoursJournee= Commande::whereDate('created_at', Carbon::today())
            ->where('status', 'pending')
            ->get();

         $NbcommandeEnCoursJournee= $commandeEnCoursJournee->count();

        return $this->jsonResponse(true, "Liste des commandes en cours du jour", [
            'total' => $NbcommandeEnCoursJournee,
            'orders' => $commandeEnCoursJournee
        ]);
    }


    //---------------------------------------------------------Commandes validées de la journée---------------------------------------------------------------------------
    public function commandeValideeDuJour()
    {
        $commandeValideDuJour = Commande::whereDate('created_at', Carbon::today())
            ->where('status', 'paid')
            ->get();

        $NbCommandeDuJour = $commandeValideDuJour->count();

        $totalAmount = $commandeValideDuJour->sum('amountOrder');

        return $this->jsonResponse(true, "Liste des commandes validées du jour", [
            'nbCommandeValide' => $NbCommandeDuJour,
            'MontantTotal' => $totalAmount,
            'commandes' => $commandeValideDuJour
        ]);
    }

    //---------------------------------------------------------Commandes annulées de la journée---------------------------------------------------------------------------
    public function commandeAnnuleeDuJour()
    {
        $commandeAnnulee= Commande::onlyTrashed()
            ->whereDate('deleted_at', Carbon::today())
            ->get();

        $NbCommandeAnnuleeDuJour = $commandeAnnulee->count();

        $totalAmount = $commandeAnnulee->sum('amountOrder');

        return $this->jsonResponse(true, "Liste des commandes annulées du jour", [
            'NbCommandeAnnulee' => $NbCommandeAnnuleeDuJour,
            'MontantTotal' => $totalAmount,
            'Commande' => $commandeAnnulee
        ]);
    }

}
