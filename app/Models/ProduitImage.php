<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProduitImage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'produit_id',
        'image',
    ];

    // Définir la relation inverse avec le modèle Produit
    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}
