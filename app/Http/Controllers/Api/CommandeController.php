<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandeRequest;
use App\Mail\CommandeAcceptee;
use App\Mail\CommandeAnnulee;
use App\Mail\CommandeLivree;
use App\Mail\CommandeRecue;
use App\Models\Commande;
use App\Models\Paiement;
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

        // Récupérer les commandes avec les produits et les images associées
        if ($user->isAdmin()) {
            $commandes = Commande::with(['produits.images'])->get();
        } else {
            $commandes = Commande::with(['produits.images'])
                ->where('user_id', $user->id)
                ->get();
        }

        // S'assurer que chaque produit n'a qu'une seule image
        foreach ($commandes as $commande) {
            foreach ($commande->produits as $produit) {
                // Si le produit a des images, ne garder que la première
                if ($produit->images->isNotEmpty()) {
                    $produit->images = $produit->images->take(1); // Conserver seulement la première image
                }
            }
        }

        if ($commandes->isEmpty()) {
            return $this->jsonResponse(false, "Vous n'avez passé aucune commande.");
        } else {
            return $this->jsonResponse(true, "Vos commandes", $commandes);
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

            // Création du paiement
            $paiement = Paiement::create([
                'commande_id' => $Commande->id,
                'montant' => $totalCommande,
                'datePaiement' => $validated['method'] === 'orange_money' ? now() : null,
                'method' => $validated['method'],
                'status' => $validated['method'] === 'orange_money' ? true : false,
            ]);

            Mail::to(Auth::user()->email)->send(new CommandeRecue($Commande));

            DB::commit();
            return $this->jsonResponse(true, 'Commande créée avec succès !', ['commande' => $Commande, 'paiement' => $paiement], 201);
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

            if ($this->canTransitionTo($commande, $statut, $user)) {
                $commande->status = $statut;
                $commande->save();

                switch ($statut) {
                    case 'accepted':
                        Mail::to($commande->user->email)->send(new CommandeAcceptee($commande));
                        return $this->jsonResponse(true, "Statut modifié avec succès !", $commande);
                        break;

                    case 'rejected':
                        foreach ($commande->produits as $produit) {
                            $produit->increment('stock', $produit->pivot->quantite);
                        }

                        $paiement = Paiement::where('commande_id', $commande->id)->first();
                        if ($paiement) {
                            $paiement->delete();
                        }

                        Mail::to($commande->user->email)->send(new CommandeAnnulee($commande));
                        $this->destroy($commande->id);
                        return $this->jsonResponse(true, "Commande annulée avec succès !");
                        break;

                    case 'delivered':
                        // Mettre à jour le paiement si le paiement était à la livraison
                        $paiement = Paiement::where('commande_id', $commande->id)->first();
                        if ($paiement) {
                            if ($paiement->method === 'delivery' && !$paiement->status) {
                                $paiement->update([
                                    'status' => 1,
                                    'datePaiement' => now(),
                                ]);
                            }
                        }

                        Mail::to($commande->user->email)->send(new CommandeLivree($commande));
                        return $this->jsonResponse(true, "Commande livrée avec succès !");
                        break;
                }
            } else {
                return $this->jsonResponse(false, "Changement de statut non autorisé.", [], 403);
            }
        } catch (\Exception $exception) {
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
            ->where('status', 'delivered')
            ->get();

        $NbCommandeDuJour = $commandeValideDuJour->count();

        $totalAmount = $commandeValideDuJour->sum('montant');

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

        $totalAmount = $commandeAnnulee->sum('montant');

        return $this->jsonResponse(true, "Liste des commandes annulées du jour", [
            'NbCommandeAnnulee' => $NbCommandeAnnuleeDuJour,
            'MontantTotal' => $totalAmount,
            'Commande' => $commandeAnnulee
        ]);
    }

}
