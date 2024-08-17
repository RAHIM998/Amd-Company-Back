<?php

namespace App\Models;

//use App\Notifications\StatusCommandeChangeNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Commande extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'numeroCommande',
        'dateCommande',
        'montant',
        'adresseLivraison',
        'status'
    ];

    public function produit(): BelongsToMany
    {
        return $this->belongsToMany(Produit::class, 'produit_commandes')->withPivot('quantite', 'prixUnitaire');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function retour(): BelongsTo
    {
        return $this->belongsTo(Retour::class);
    }

    public function paiement(): BelongsTo
    {
        return $this->belongsTo(Paiement::class);
    }


    /*public static $statusTransitions = [
        'admin' => [
            'confirmation en attente' => ['commande confirmée', 'commande annulée'],
            'commande confirmée' => ['en cours de livraison'],
            'en cours de livraison' => ['commande livrée']
        ],
        'livraison' => [
            'commande confirmée' => ['en cours de livraison'],
            'en cours de livraison' => ['commande livrée']
        ],
        // Ajoutez d'autres règles pour d'autres rôles si nécessaire
    ];

    public function Transition($newStatus, $user)
    {
        $statutActuel = $this->status;
        $verifAutorisation = self::$statusTransitions[$user->role] ?? [];

        return in_array($newStatus, $verifAutorisation[$statutActuel] ?? []);
    }

    public function updateStatus($newStatus)
    {
        $this->status = $newStatus;
        $this->save();

        // Envoyer une notification au client
        $this->user->notify(new StatusCommandeChangeNotification($this, $newStatus));
    }*/
}
