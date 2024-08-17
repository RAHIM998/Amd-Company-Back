<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Category extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'libelle',
    ];


    public function produit():HasMany
    {
        return $this->hasMany(Produit::class);
    }

}
