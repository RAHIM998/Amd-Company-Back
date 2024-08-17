<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Retours extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'commande_id',
        'raison',
        'status'
    ];

    public function commande(): HasMany
    {
        return $this->hasMany(Commande::class);
    }
}
