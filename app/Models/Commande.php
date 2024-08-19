<?php

namespace App\Models;

//use App\Notifications\StatusCommandeChangeNotification;
use App\Mail\CommandeAcceptee;
use App\Mail\CommandeAnnulee;
use App\Mail\CommandeLivree;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Mail;

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

    public function produits(): BelongsToMany
    {
        return $this->belongsToMany(Produit::class, 'produit_commandes')->withPivot('quantite');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function retour(): HasOne
    {
        return $this->hasOne(Retours::class, 'commande_id');
    }

    public function paiement(): HasOne
    {
        return $this->hasOne(Paiement::class, 'commande_id');
    }


}
